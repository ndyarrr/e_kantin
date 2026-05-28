<?php
// views/pembeli/actions/favorit.php
// API: GET  ?action=list           → JSON array id_menu yang difavoritkan user
// API: POST action=toggle&id_menu= → toggle favorit, return {liked: bool, count: int}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// Harus login
$user_id   = $_SESSION['user_id']   ?? '';
$user_role = $_SESSION['user_role'] ?? 'siswa';

if (empty($user_id)) {
    echo json_encode(['error' => 'Belum login', 'code' => 401]);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

// ── LIST: GET semua id_menu favorit milik user ──
if ($action === 'list') {
    $stmt = mysqli_prepare($conn, "SELECT id_menu FROM favorit WHERE user_id = ? AND user_role = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $user_id, $user_role);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $ids[] = (int)$row['id_menu'];
    }
    mysqli_stmt_close($stmt);
    echo json_encode(['favorites' => $ids]);
    exit;
}

// ── TOGGLE: POST id_menu ──
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu = intval($_POST['id_menu'] ?? 0);
    if ($id_menu <= 0) {
        echo json_encode(['error' => 'id_menu tidak valid']);
        exit;
    }

    // Cek apakah sudah ada
    $stmt = mysqli_prepare($conn, "SELECT id_favorit FROM favorit WHERE user_id = ? AND user_role = ? AND id_menu = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $user_id, $user_role, $id_menu);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        // Hapus favorit
        $stmt = mysqli_prepare($conn, "DELETE FROM favorit WHERE user_id = ? AND user_role = ? AND id_menu = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $user_id, $user_role, $id_menu);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['liked' => false]);
    } else {
        // Tambah favorit
        $stmt = mysqli_prepare($conn, "INSERT INTO favorit (user_id, user_role, id_menu) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $user_id, $user_role, $id_menu);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['liked' => true]);
    }
    exit;
}

echo json_encode(['error' => 'Action tidak dikenali']);
