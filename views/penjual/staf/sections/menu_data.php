<?php // sections/menu_data.php

/** @var mysqli $conn */
/** @var int $idToko */

// Ambil data filter dari URL
$search = $_GET['search'] ?? '';
$filterKategori = $_GET['kategori'] ?? 'semua';

// 🌟 Query dasar: Ambil menu yang BELUM di-softdelete
$querySql = "SELECT * FROM menu WHERE id_toko = $idToko AND deleted_at IS NULL";

// Filter berdasarkan pencarian nama menu
if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $querySql .= " AND nama_menu LIKE '%$searchEscaped%'";
}

// Filter menggunakan kolom kategori dari database
if ($filterKategori !== 'semua') {
    $kategoriEscaped = mysqli_real_escape_string($conn, $filterKategori);
    $querySql .= " AND kategori = '$kategoriEscaped'";
}

$querySql .= " ORDER BY id_menu DESC";

// Ambil data dan tampung ke $daftarMenu
$daftarMenu = mysqli_fetch_all(mysqli_query($conn, $querySql), MYSQLI_ASSOC);