<?php // sections/menu_data.php

/** @var mysqli $conn */
/** @var int $idToko */

$search = $_GET['search'] ?? '';
$filterKategori = $_GET['kategori'] ?? 'semua';

$querySql = "SELECT * FROM menu WHERE id_toko = $idToko";

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $querySql .= " AND nama_menu LIKE '%$searchEscaped%'";
}

if ($filterKategori === 'makanan') {
    $querySql .= " AND LOWER(nama_menu) NOT LIKE '%teh%' AND LOWER(nama_menu) NOT LIKE '%es%' AND LOWER(nama_menu) NOT LIKE '%minum%' AND LOWER(nama_menu) NOT LIKE '%jus%'";
} elseif ($filterKategori === 'minuman') {
    $querySql .= " AND (LOWER(nama_menu) LIKE '%teh%' OR LOWER(nama_menu) LIKE '%es%' OR LOWER(nama_menu) LIKE '%minum%' OR LOWER(nama_menu) LIKE '%jus%')";
}

$querySql .= " ORDER BY id_menu DESC";

// PASTIKAN NAMA VARIABEL DI BAWAH INI ADALAH $daftarMenu
$daftarMenu = mysqli_fetch_all(mysqli_query($conn, $querySql), MYSQLI_ASSOC);