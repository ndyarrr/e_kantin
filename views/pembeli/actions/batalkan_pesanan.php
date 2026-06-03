<?php
// views/pembeli/actions/batalkan_pesanan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

// ── 1. VALIDASI SESI PEMBELI ──
$user_id   = $_SESSION['user_id']   ?? '';
$user_role = $_SESSION['user_role'] ?? '';
$user_nama = $_SESSION['user_nama'] ?? 'Pembeli';

if (empty($user_id) || !in_array($user_role, ['siswa', 'guru'])) {
    echo json_encode(["status" => "error", "message" => "Sesi habis atau Anda tidak memiliki akses. Silakan login kembali."]);
    exit;
}

// ── 2. VALIDASI REQUEST DATA ──
$id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
if ($id_pesanan <= 0) {
    echo json_encode(["status" => "error", "message" => "ID pesanan tidak valid."]);
    exit;
}

// Tentukan kolom pembeli berdasarkan role
$col_pembeli = ($user_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';

// Cek keberadaan dan status pesanan milik user ini
$query_check = "SELECT status, id_toko FROM pesanan WHERE id_pesanan = $id_pesanan AND $col_pembeli = '$user_id' LIMIT 1";
$res_check = mysqli_query($db, $query_check);

if (!$res_check || mysqli_num_rows($res_check) === 0) {
    echo json_encode(["status" => "error", "message" => "Pesanan tidak ditemukan atau Anda tidak berwenang membatalkan pesanan ini."]);
    exit;
}

$pesanan = mysqli_fetch_assoc($res_check);
$status_saat_ini = $pesanan['status'];
$id_toko = (int)$pesanan['id_toko'];

// Pembatalan hanya diperbolehkan jika status masih 'menunggu'
if ($status_saat_ini !== 'menunggu') {
    $status_label = $status_saat_ini;
    if ($status_saat_ini === 'dikonfirmasi') $status_label = 'sedang disiapkan';
    elseif ($status_saat_ini === 'siap_diambil') $status_label = 'siap diambil';
    elseif ($status_saat_ini === 'selesai') $status_label = 'sudah selesai';
    elseif ($status_saat_ini === 'dibatalkan') $status_label = 'sudah dibatalkan';

    echo json_encode([
        "status" => "error", 
        "message" => "Pesanan tidak dapat dibatalkan karena saat ini statusnya " . $status_label . "."
    ]);
    exit;
}

// ── 3. PROSES PEMBATALAN (TRANSAKSI DATABASE) ──
mysqli_begin_transaction($db);
try {
    // A. Update status pesanan ke 'dibatalkan'
    $stmt_update = mysqli_prepare($db, "UPDATE pesanan SET status = 'dibatalkan' WHERE id_pesanan = ?");
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, 'i', $id_pesanan);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    } else {
        throw new Exception("Gagal menyiapkan query update status pesanan.");
    }

    // B. Kembalikan stok menu
    $q_items = mysqli_query($db, "SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
    if ($q_items) {
        while ($item = mysqli_fetch_assoc($q_items)) {
            $id_menu = (int)$item['id_menu'];
            $jumlah  = (int)$item['jumlah'];
            
            $stmt_stok = mysqli_prepare($db, "UPDATE menu SET stok = stok + ? WHERE id_menu = ?");
            if ($stmt_stok) {
                mysqli_stmt_bind_param($stmt_stok, 'ii', $jumlah, $id_menu);
                mysqli_stmt_execute($stmt_stok);
                mysqli_stmt_close($stmt_stok);
            }
        }
    }

    // C. Kirim pesan otomatis ke chat kantin/penjual
    $id_pengirim  = ($user_role === 'siswa') ? 'murid_' . $user_id : 'guru_' . $user_id;
    $id_penerima  = 'toko_' . $id_toko;
    
    $status_teks_chat = 'Pesanan #' . $id_pesanan . ' Dibatalkan!';
    $status_sub       = 'Pesanan ini telah dibatalkan oleh pembeli (' . htmlspecialchars($user_nama) . ').';
    $status_status    = 'Dibatalkan ❌';

    $auto_status_msg = '[AUTO_REPLY_STATUS]
    <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
        <div style="font-weight:800;font-size:14px;color:#0f172a;margin-bottom:6px;">' . $status_teks_chat . '</div>
        <div style="font-size:12px;color:#64748b;margin-bottom:12px;">' . $status_sub . '</div>
        <div style="padding:10px 12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;font-weight:600;color:#475569;">Status Terbaru</span>
            <span style="font-size:12px;font-weight:800;color:#1e293b;">' . $status_status . '</span>
        </div>
    </div>';

    $msg_escaped = mysqli_real_escape_string($db, $auto_status_msg);
    $q_chat = "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
               VALUES ('$id_pengirim', '$id_penerima', '$msg_escaped', NOW(), 0)";
    mysqli_query($db, $q_chat);

    // D. Catat log sistem
    if (function_exists('catatLog')) {
        catatLog($db, 'Batalkan Pesanan', "Pembeli $user_nama membatalkan Pesanan #$id_pesanan");
    } else {
        $log_desc = "Pembeli $user_nama membatalkan Pesanan #$id_pesanan";
        $stmt_log = mysqli_prepare($db, "INSERT INTO log_sistem (aksi, keterangan, dibuat_pada, user_role, user_id, user_nama) VALUES ('Batalkan Pesanan', ?, NOW(), ?, ?, ?)");
        if ($stmt_log) {
            mysqli_stmt_bind_param($stmt_log, 'ssss', $log_desc, $user_role, $user_id, $user_nama);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);
        }
    }

    mysqli_commit($db);
    echo json_encode(["status" => "success", "message" => "Pesanan Anda berhasil dibatalkan."]);

} catch (Exception $e) {
    mysqli_rollback($db);
    echo json_encode(["status" => "error", "message" => "Gagal membatalkan pesanan: " . $e->getMessage()]);
}
