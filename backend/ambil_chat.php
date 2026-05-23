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
$id_lawan = $_GET['id_lawan'] ?? '';
$terakhir_id = isset($_GET['terakhir_id']) ? (int) $_GET['terakhir_id'] : 0;

if (empty($user_id_raw) || empty($id_lawan)) {
    echo json_encode([]);
    exit;
}

// Buat prefixed ID untuk user login saat ini
$user_sekarang = '';
if ($role_sekarang === 'siswa') {
    $user_sekarang = 'murid_' . $user_id_raw;
} else if ($role_sekarang === 'guru') {
    $user_sekarang = 'guru_' . $user_id_raw;
} else {
    $user_sekarang = $role_sekarang . '_' . $user_id_raw;
}

$chats = [];

if ($terakhir_id > 0) {
    // Polling pesan baru
    $query = "SELECT id_pesan, id_pengirim, isi_pesan, DATE_FORMAT(waktu_kirim, '%H:%i') as jam
              FROM pesan_chat
              WHERE ((id_pengirim = ? AND id_penerima = ?) OR (id_pengirim = ? AND id_penerima = ?))
              AND id_pesan > ?
              ORDER BY id_pesan ASC";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssssi", $user_sekarang, $id_lawan, $id_lawan, $user_sekarang, $terakhir_id);
} else {
    // Load awal chat
    $query = "SELECT * FROM (
                SELECT id_pesan, id_pengirim, isi_pesan, DATE_FORMAT(waktu_kirim, '%H:%i') as jam
                FROM pesan_chat
                WHERE ((id_pengirim = ? AND id_penerima = ?) OR (id_pengirim = ? AND id_penerima = ?))
                ORDER BY id_pesan DESC
                LIMIT 40
              ) AS sub_chat
              ORDER BY id_pesan ASC";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $user_sekarang, $id_lawan, $id_lawan, $user_sekarang);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $chats[] = [
        'id' => (int) $row['id_pesan'],
        'is_me' => ($row['id_pengirim'] === $user_sekarang),
        'pesan' => htmlspecialchars($row['isi_pesan']),
        'jam' => $row['jam']
    ];
}

// Tandai pesan sudah dibaca
if (!empty($chats)) {
    $query_update = "UPDATE pesan_chat SET sudah_dibaca = 1 WHERE id_pengirim = ? AND id_penerima = ?";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->bind_param("ss", $id_lawan, $user_sekarang);
    $stmt_update->execute();
}

echo json_encode($chats);