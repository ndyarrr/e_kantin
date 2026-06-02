<?php
// backend/ambil_chat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo json_encode(["error" => "Koneksi database tidak ditemukan"]);
    exit;
}

$user_id_raw = $_SESSION['user_id'] ?? '';
$role_sekarang = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$id_toko_sesi = (int) ($_SESSION['id_toko'] ?? 0);
$id_lawan = $_GET['id_lawan'] ?? '';
$terakhir_id = isset($_GET['terakhir_id']) ? (int) $_GET['terakhir_id'] : 0;

if (empty($user_id_raw) || empty($id_lawan)) {
    echo json_encode([]);
    exit;
}

// Build prefixed ID user yang sedang login
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

$chats = [];

if ($terakhir_id > 0) {
    $query = "SELECT pc.id_pesan, pc.id_pengirim, pc.isi_pesan,
                     DATE_FORMAT(pc.waktu_kirim, '%Y-%m-%d %H:%i:%s') as jam,
                     pc.id_staf_balasan,
                     p.nama as nama_staf
              FROM pesan_chat pc
              LEFT JOIN penjual p ON p.id_penjual = pc.id_staf_balasan
              WHERE ((pc.id_pengirim = ? AND pc.id_penerima = ?) OR (pc.id_pengirim = ? AND pc.id_penerima = ?))
              AND pc.id_pesan > ?
              AND pc.deleted_at IS NULL  -- ← tambah ini
              ORDER BY pc.id_pesan ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssi", $user_sekarang, $id_lawan, $id_lawan, $user_sekarang, $terakhir_id);
} else {
    $query = "SELECT * FROM (
                SELECT pc.id_pesan, pc.id_pengirim, pc.isi_pesan,
                       DATE_FORMAT(pc.waktu_kirim, '%Y-%m-%d %H:%i:%s') as jam,
                       pc.id_staf_balasan,
                       p.nama as nama_staf
                FROM pesan_chat pc
                LEFT JOIN penjual p ON p.id_penjual = pc.id_staf_balasan
                WHERE ((pc.id_pengirim = ? AND pc.id_penerima = ?) OR (pc.id_pengirim = ? AND pc.id_penerima = ?))
                AND pc.deleted_at IS NULL  -- ← tambah ini
                ORDER BY pc.id_pesan DESC
                LIMIT 40
              ) AS sub_chat ORDER BY id_pesan ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $user_sekarang, $id_lawan, $id_lawan, $user_sekarang);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $chats[] = [
        'id' => (int) $row['id_pesan'],
        'is_me' => ($row['id_pengirim'] === $user_sekarang),
        'pesan' => $row['isi_pesan'],
        'jam' => $row['jam'],
        'nama_staf' => $row['nama_staf'] ?? null // Info "dibalas oleh siapa"
    ];
}

// Tandai sudah dibaca
if (!empty($chats)) {
    $q_update = "UPDATE pesan_chat SET sudah_dibaca = 1 WHERE id_pengirim = ? AND id_penerima = ?";
    $st_update = $db->prepare($q_update);
    $st_update->bind_param("ss", $id_lawan, $user_sekarang);
    $st_update->execute();
}

echo json_encode($chats);