<?php
// views/penjual/owner/actions/proses_pesanan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    
    // 🌟 Tangkap data menu & jumlah langsung dari lemparan form view
    $id_menu     = (int)($_POST['id_menu'] ?? 0);
    $jumlahBeli  = (int)($_POST['jumlah_beli'] ?? 0);
    
    $activeSection = $_POST['_section'] ?? 'pesanan';
    $feedback = null;

    if ($action === 'selesaikan_pesanan' && $id_pesanan > 0) {
        
        // 1. Cek stok menu yang bersangkutan langsung ke tabel menu
        $queryMenu = mysqli_query($conn, "SELECT stok, nama_menu FROM menu WHERE id_menu = $id_menu LIMIT 1");
        
        if ($queryMenu && mysqli_num_rows($queryMenu) > 0) {
            $dataMenu = mysqli_fetch_assoc($queryMenu);
            $stokSekarang = (int)$dataMenu['stok'];
            $nama_menu    = $dataMenu['nama_menu'];

            // 2. Validasi stok gudang
            if ($stokSekarang < $jumlahBeli || $stokSekarang <= 0) {
                $feedback = [
                    'type' => 'danger', 
                    'msg' => "Gagal! Stok untuk [{$nama_menu}] sudah habis atau tidak mencukupi."
                ];
            } else {
                // 3. Potong stok menu asli
                mysqli_query($conn, "UPDATE menu SET stok = stok - $jumlahBeli WHERE id_menu = $id_menu");
                
                // Set tidak tersedia jika stok murni menyentuh angka 0
                mysqli_query($conn, "UPDATE menu SET tersedia = 0 WHERE id_menu = $id_menu AND stok <= 0");

                // 4. Update status order utama menjadi selesai
                $queryUpdateStatus = "UPDATE pesanan SET status = 'selesai' WHERE id_pesanan = $id_pesanan";
                
                if (mysqli_query($conn, $queryUpdateStatus)) {
                    catatLog($conn, 'Selesaikan Pesanan', "Owner menyelesaikan pesanan ID: $id_pesanan (Menu: $nama_menu, Jumlah: $jumlahBeli)");
                    $feedback = [
                        'type' => 'success', 
                        'msg' => 'Pesanan sukses diproses! Khodam Sugeng Rahayu meluncur ke Ngawi! 🚌💨'
                    ];
                } else {
                    $feedback = [
                        'type' => 'danger', 
                        'msg' => 'Gagal mengubah status pesanan: ' . mysqli_error($conn)
                    ];
                }
            }
        } else {
            // Jalur Bypass: Jika data id_menu dari form gagal dibaca, status tetap di-set selesai tanpa potong stok
            mysqli_query($conn, "UPDATE pesanan SET status = 'selesai' WHERE id_pesanan = $id_pesanan");
            catatLog($conn, 'Selesaikan Pesanan', "Owner menyelesaikan pesanan ID: $id_pesanan (Bypass Stok)");
            $feedback = [
                'type' => 'success', 
                'msg' => 'Pesanan selesai! (Stok menu tetap karena ID Menu tidak terkirim dari form).'
            ];
        }
    }

    if (isset($feedback)) {
        $_SESSION['feedback'] = $feedback;
    }

    session_write_close();
    header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
    exit;
}