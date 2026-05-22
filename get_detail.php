<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data toko
$queryToko = mysqli_query($conn, "SELECT * FROM toko WHERE id_toko = $id");
$toko = mysqli_fetch_assoc($queryToko);

// Ambil daftar menu
$queryMenu = mysqli_query($conn, "SELECT * FROM menu WHERE id_toko = $id AND tersedia = 1");
$menus = mysqli_fetch_all($queryMenu, MYSQLI_ASSOC);

// Kembalikan dalam bentuk JSON
echo json_encode([
    'toko' => $toko,
    'menus' => $menus
]);
?>