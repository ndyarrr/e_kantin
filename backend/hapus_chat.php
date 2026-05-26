<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../config/database.php';
$db = $koneksi ?? $conn ?? null;
header('Content-Type: application/json');
ob_clean();

if (!$db) {
    echo json_encode(['status' => 'error', 'msg' => 'Koneksi DB tidak ditemukan']);
    exit;
}

$id_pesan = (int) ($_POST['id_pesan'] ?? 0);
$user_id = $_SESSION['user_id'] ?? '';
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

if (!$id_pesan || !$user_id || !$role) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
    exit;
}

// Bangun prefix sesuai format di DB: admin_2, toko_2, murid_xxx, dll
$prefix = match (true) {
    $role === 'admin'                                => 'admin_' . $user_id,
    in_array($role, ['owner', 'penjual', 'staf'])    => 'toko_'  . ($_SESSION['id_toko'] ?? $user_id),
    in_array($role, ['murid', 'siswa'])              => 'murid_' . $user_id, // user_id = NISN
    $role === 'guru'                                 => 'guru_'  . $user_id, // user_id = NUPTK
    default                                          => $role    . '_' . $user_id,
};

$uid_esc = mysqli_real_escape_string($db, $prefix);

$cek = mysqli_fetch_assoc(mysqli_query(
    $db,
    "SELECT id_pesan FROM pesan_chat 
     WHERE id_pesan = $id_pesan 
     AND id_pengirim = '$uid_esc' 
     AND deleted_at IS NULL"
));

if (!$cek) {
    echo json_encode(['status' => 'error', 'msg' => 'Pesan tidak ditemukan atau bukan milik Anda']);
    exit;
}

mysqli_query($db, "UPDATE pesan_chat SET deleted_at = NOW() WHERE id_pesan = $id_pesan");
echo json_encode(['status' => 'success']);