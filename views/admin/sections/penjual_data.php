<?php
// sections/penjual_data.php

$selectedPenjual = (int) ($_GET['penjual'] ?? $_POST['_selected_penjual'] ?? 0);

// Daftar semua OWNER + kantin yang dikelola (Di-filter hanya role 'owner')
$penjuals = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT p.*,
     GROUP_CONCAT(t.nama_toko SEPARATOR ', ') as kantin_dikelola,
     COUNT(DISTINCT tp.id_toko) as total_kantin
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual AND tp.status = 'aktif'
     LEFT JOIN toko t ON t.id_toko = tp.id_toko
     WHERE p.role = 'owner' -- FILTER KHUSUS OWNER SAJA
     GROUP BY p.id_penjual
     ORDER BY p.dibuat_pada DESC"
), MYSQLI_ASSOC);

$totalPenjual = count($penjuals);
$penjualAktif = count(array_filter($penjuals, fn($p) => $p['status'] === 'aktif'));

// Semua toko untuk dropdown
$semuaToko = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT id_toko, nama_toko FROM toko ORDER BY nama_toko ASC"
), MYSQLI_ASSOC);

// Detail owner yang dipilih
$detailPenjual = null;
$kantinPenjual = [];
if ($selectedPenjual) {
    $detailPenjual = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM penjual WHERE id_penjual=$selectedPenjual AND role='owner'"

    ));

    if ($detailPenjual) {
        $kantinPenjual = mysqli_fetch_all(mysqli_query(
            $conn,
            "SELECT tp.id, tp.id_toko, tp.shift, t.nama_toko
             FROM toko_penjual tp
             JOIN toko t ON t.id_toko = tp.id_toko
             WHERE tp.id_penjual=$selectedPenjual AND tp.status='aktif'
             ORDER BY t.nama_toko ASC"
        ), MYSQLI_ASSOC);
    }

    // --- QUERY BARU: Ambil daftar staf kasir yang bekerja di kantin milik Owner ini ---
    $stafOwner = [];
    $tokoIds = array_column($kantinPenjual, 'id_toko');
    if (!empty($tokoIds)) {
        $tokoIdsStr = implode(',', array_map('intval', $tokoIds));
        $stafOwner = mysqli_fetch_all(mysqli_query(
            $conn,
            "SELECT p.nama, p.username, p.status, tp.shift, t.nama_toko
             FROM penjual p
             JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual
             JOIN toko t ON t.id_toko = tp.id_toko
             WHERE tp.id_toko IN ($tokoIdsStr) 
               AND p.role = 'staf' 
               AND tp.status = 'aktif'
             ORDER BY p.nama ASC"
        ), MYSQLI_ASSOC);
    }
}