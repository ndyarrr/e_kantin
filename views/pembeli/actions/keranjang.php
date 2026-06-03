<?php
// views/pembeli/actions/keranjang.php
// API untuk sinkronisasi keranjang belanja dengan database MySQL.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// Harus login sebagai pembeli (siswa/guru)
$user_id   = $_SESSION['user_id']   ?? '';
$user_role = $_SESSION['user_role'] ?? 'siswa';

if (empty($user_id)) {
    echo json_encode(['error' => 'Belum login', 'code' => 401]);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

// ── 1. LIST: Ambil data keranjang dari database (ditambah data menu & toko terbaru) ──
if ($action === 'list') {
    $stmt = mysqli_prepare($conn, "
        SELECT 
            k.id_menu, 
            k.jumlah, 
            k.catatan, 
            k.selected, 
            m.nama_menu, 
            m.harga AS menu_harga, 
            m.foto_menu, 
            m.stok,
            m.tersedia,
            t.nama_toko, 
            t.id_toko,
            t.status AS status_toko,
            k.harga AS keranjang_harga,
            m.is_fleksibel
        FROM keranjang k
        JOIN menu m ON k.id_menu = m.id_menu
        JOIN toko t ON k.id_toko = t.id_toko
        WHERE k.user_id = ? AND k.user_role = ?
    ");
    
    if (!$stmt) {
        echo json_encode(['error' => 'Gagal mempersiapkan query: ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $user_id, $user_role);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $cart = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $real_harga = ((int)$row['is_fleksibel'] === 1 && (int)$row['keranjang_harga'] > 0) ? (int)$row['keranjang_harga'] : (int)$row['menu_harga'];
        $cart[] = [
            'id_menu'     => (int)$row['id_menu'],
            'nama_menu'   => $row['nama_menu'],
            'harga'       => $real_harga,
            'jumlah'      => (int)$row['jumlah'],
            'foto_menu'   => $row['foto_menu'] ?? '',
            'nama_toko'   => $row['nama_toko'],
            'id_toko'     => (int)$row['id_toko'],
            'selected'    => (bool)$row['selected'],
            'catatan'     => $row['catatan'] ?? '',
            'stok'        => (int)$row['stok'],
            'tersedia'    => (int)$row['tersedia'],
            'status_toko' => strtolower($row['status_toko'] ?? 'tutup'),
            'is_fleksibel'=> (int)$row['is_fleksibel']
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode(['status' => 'success', 'cart' => $cart]);
    exit;
}

// ── 2. SYNC: Sinkronkan data dari frontend ke database ──
if ($action === 'sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    // Fallback jika dikirim menggunakan POST parameter standar
    if ($data === null && isset($_POST['cart_data'])) {
        $data = json_decode($_POST['cart_data'], true);
    }
    
    if (!is_array($data)) {
        echo json_encode(['error' => 'Data keranjang tidak valid']);
        exit;
    }
    
    // Jalankan transaksi database agar sinkronisasi bersifat atomik
    mysqli_begin_transaction($conn);
    try {
        $tuples = [];
        
        foreach ($data as $item) {
            $id_menu  = isset($item['id_menu']) ? (int)$item['id_menu'] : 0;
            $id_toko  = isset($item['id_toko']) ? (int)$item['id_toko'] : 0;
            $jumlah   = isset($item['jumlah']) ? (int)$item['jumlah'] : 1;
            $catatan  = isset($item['catatan']) ? trim($item['catatan']) : '';
            $selected = (isset($item['selected']) && $item['selected'] !== false) ? 1 : 0;
            $harga    = isset($item['harga']) ? (int)$item['harga'] : 0;
            
            if ($id_menu <= 0 || $id_toko <= 0 || $jumlah <= 0) {
                continue;
            }
            
            $tuples[] = "($id_menu, $harga)";
            
            // Masukkan atau perbarui data keranjang
            $stmt = mysqli_prepare($conn, "
                INSERT INTO keranjang (user_id, user_role, id_menu, id_toko, jumlah, catatan, selected, harga) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    jumlah = VALUES(jumlah), 
                    catatan = VALUES(catatan), 
                    selected = VALUES(selected),
                    harga = VALUES(harga)
            ");
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssiiiisi', $user_id, $user_role, $id_menu, $id_toko, $jumlah, $catatan, $selected, $harga);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Hapus item di database yang tidak ada di data kiriman frontend
        if (!empty($tuples)) {
            $tuple_str = implode(',', $tuples);
            mysqli_query($conn, "
                DELETE FROM keranjang 
                WHERE user_id = '" . mysqli_real_escape_string($conn, $user_id) . "' 
                  AND user_role = '" . mysqli_real_escape_string($conn, $user_role) . "' 
                  AND (id_menu, harga) NOT IN ($tuple_str)
            ");
        } else {
            // Jika kosong, kosongkan keranjang milik user ini di DB
            mysqli_query($conn, "
                DELETE FROM keranjang 
                WHERE user_id = '" . mysqli_real_escape_string($conn, $user_id) . "' 
                  AND user_role = '" . mysqli_real_escape_string($conn, $user_role) . "'
            ");
        }
        
        mysqli_commit($conn);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['error' => 'Gagal sinkronisasi: ' . $e->getMessage()]);
    }
    exit;
}

// ── 3. CLEAR: Kosongkan keranjang milik pembeli ──
if ($action === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE user_id = ? AND user_role = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $user_id, $user_role);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['error' => 'Gagal mengosongkan keranjang']);
    }
    exit;
}

echo json_encode(['error' => 'Aksi tidak valid atau tidak didukung']);
