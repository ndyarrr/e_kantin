<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['toko' => null, 'menus' => []]);
    exit;
}

// Ambil data toko
$queryToko = mysqli_query($conn, "
    SELECT t.*, s.nomor AS nomor_lapak
    FROM toko t
    LEFT JOIN slot_stand_kantin s ON s.id_toko = t.id_toko
    WHERE t.id_toko = $id
");
$toko = mysqli_fetch_assoc($queryToko);

if (!$toko) {
    echo json_encode(['toko' => null, 'menus' => []]);
    exit;
}

$nomor_lapak = (int) ($toko['nomor_lapak'] ?? 0);
if ($nomor_lapak < 1) {
    $nomor_lapak = (int) ($toko['urutan'] ?? 0) + 1;
}
$toko['nomor_lapak'] = $nomor_lapak;

// Ambil daftar menu
$queryMenu = mysqli_query($conn, "SELECT * FROM menu WHERE id_toko = $id AND tersedia = 1 AND deleted_at IS NULL");
$menus = mysqli_fetch_all($queryMenu, MYSQLI_ASSOC);

// Set header JSON
header('Content-Type: application/json');

echo json_encode([
    'toko' => $toko,
    'menus' => $menus
]);
?>