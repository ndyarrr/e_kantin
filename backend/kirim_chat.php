<?php
// backend/kirim_chat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal."]);
    exit;
}

$user_id_raw   = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$id_toko_sesi  = (int)($_SESSION['id_toko'] ?? 0);
$id_penerima   = $_POST['id_penerima'] ?? '';
$isi_pesan     = isset($_POST['isi_pesan']) ? trim($_POST['isi_pesan']) : '';

if (empty($user_id_raw)) {
    echo json_encode(["status" => "error", "message" => "Sesi habis. Login kembali."]);
    exit;
}
if (empty($id_penerima) || empty($isi_pesan)) {
    echo json_encode(["status" => "error", "message" => "Penerima atau pesan kosong."]);
    exit;
}

// Build prefixed ID pengirim
$id_pengirim    = '';
$id_staf_balas  = null; // Hanya diisi jika penjual yang kirim

if ($role_sekarang === 'siswa') {
    $id_pengirim = 'murid_' . $user_id_raw;
} elseif ($role_sekarang === 'guru') {
    $id_pengirim = 'guru_' . $user_id_raw;
} elseif ($role_sekarang === 'penjual') {
    // Penjual kirim atas nama toko, tapi rekam siapa staff-nya
    $id_pengirim   = 'toko_' . $id_toko_sesi;
    $id_staf_balas = (int)$user_id_raw; // ID penjual yang sebenarnya mengetik
} else {
    $id_pengirim = $role_sekarang . '_' . $user_id_raw;
}

try {
    if ($id_staf_balas !== null) {
        $query = "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca, id_staf_balasan)
                  VALUES (?, ?, ?, NOW(), 0, ?)";
        $stmt = $db->prepare($query);
        if (!$stmt) throw new Exception($db->error);
        $stmt->bind_param("sssi", $id_pengirim, $id_penerima, $isi_pesan, $id_staf_balas);
    } else {
        $query = "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                  VALUES (?, ?, ?, NOW(), 0)";
        $stmt = $db->prepare($query);
        if (!$stmt) throw new Exception($db->error);
        $stmt->bind_param("sss", $id_pengirim, $id_penerima, $isi_pesan);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}