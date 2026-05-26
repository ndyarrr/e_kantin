<?php
// backend/ambil_unread_chat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$user_id_raw    = $_SESSION['user_id'] ?? '';
$role_sekarang  = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$id_toko_sesi   = (int)($_SESSION['id_toko'] ?? 0);

if (empty($user_id_raw) || empty($role_sekarang)) {
    echo json_encode(["unread_count" => 0]);
    exit;
}

$user_sekarang = '';
if ($role_sekarang === 'siswa') {
    $user_sekarang = 'murid_' . $user_id_raw;
} elseif ($role_sekarang === 'guru') {
    $user_sekarang = 'guru_' . $user_id_raw;
} elseif ($role_sekarang === 'penjual') {
    $user_sekarang = 'toko_' . $id_toko_sesi;
} else {
    $user_sekarang = $role_sekarang . '_' . $user_id_raw;
}

if (empty($user_sekarang)) {
    echo json_encode(["unread_count" => 0]);
    exit;
}

$unread_count = 0;
$query = "SELECT COUNT(*) as unread FROM pesan_chat WHERE id_penerima = ? AND sudah_dibaca = 0";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $user_sekarang);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $unread_count = (int)$row['unread'];
    }
    $stmt->close();
}

echo json_encode(["unread_count" => $unread_count]);
