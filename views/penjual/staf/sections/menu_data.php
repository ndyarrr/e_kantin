<?php
/** @var mysqli $conn */
/** @var int $idToko */

$search         = $_GET['search'] ?? '';
$filterKategori = $_GET['kategori'] ?? 'semua';

$querySql = "SELECT * FROM menu WHERE id_toko = $idToko";

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $querySql .= " AND nama_menu LIKE '%$searchEscaped%'";
}

// Filter pakai kolom kategori, bukan tebak-tebak dari nama menu
if (in_array($filterKategori, ['Makanan', 'Minuman', 'Snack'])) {
    $querySql .= " AND kategori = '$filterKategori'";
}

$querySql .= " ORDER BY kategori ASC, nama_menu ASC";

$daftarMenu = mysqli_fetch_all(mysqli_query($conn, $querySql), MYSQLI_ASSOC);
?>