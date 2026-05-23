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

$user_id_raw = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$id_penerima = $_POST['id_penerima'] ?? '';
$isi_pesan = isset($_POST['isi_pesan']) ? trim($_POST['isi_pesan']) : '';

if (empty($user_id_raw)) {
    echo json_encode(["status" => "error", "message" => "Sesi Anda habis. Silakan login kembali."]);
    exit;
}

if (empty($id_penerima) || empty($isi_pesan)) {
    echo json_encode(["status" => "error", "message" => "Penerima atau isi pesan tidak boleh kosong."]);
    exit;
}

// Buat prefixed ID pengirim agar sinkron
$id_pengirim = '';
if ($role_sekarang === 'siswa') {
    $id_pengirim = 'murid_' . $user_id_raw;
} else if ($role_sekarang === 'guru') {
    $id_pengirim = 'guru_' . $user_id_raw;
} else {
    $id_pengirim = $role_sekarang . '_' . $user_id_raw;
}

try {
    $query = "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
              VALUES (?, ?, ?, NOW(), 0)";

    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception($db->error);
    }

    $stmt->bind_param("sss", $id_pengirim, $id_penerima, $isi_pesan);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Pesan berhasil dikirim"]);
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . $e->getMessage()]);
}