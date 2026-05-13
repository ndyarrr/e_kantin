<?php
// sections/kantin_data.php

// selected toko dari URL/POST
$selectedToko = (int) ($_POST['_selected_toko'] ?? $_GET['toko'] ?? 0);

// daftar semua toko
$tokos = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.*,
            COUNT(DISTINCT m.id_menu) as total_menu,
            COUNT(DISTINCT tp.id) as total_penjual
     FROM toko t
     LEFT JOIN menu m ON m.id_toko = t.id_toko
     LEFT JOIN toko_penjual tp ON tp.id_toko = t.id_toko AND tp.status = 'aktif'
     GROUP BY t.id_toko
     ORDER BY t.dibuat_pada DESC"
), MYSQLI_ASSOC);



// detail toko yang dipilih
$detailToko = null;
$menuToko = [];
$penjualToko = [];
$semuaPenjual = [];

if ($selectedToko) {
    $detailToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM toko WHERE id_toko=$selectedToko"
    ));

    $menuToko = mysqli_fetch_all(mysqli_query(
        $conn,
        "SELECT * FROM menu WHERE id_toko=$selectedToko ORDER BY nama_menu ASC"
    ), MYSQLI_ASSOC);

    $penjualToko = mysqli_fetch_all(mysqli_query(
        $conn,
        "SELECT tp.id as id_tp, p.nama, tp.shift
         FROM toko_penjual tp
         JOIN penjual p ON p.id_penjual = tp.id_penjual
         WHERE tp.id_toko=$selectedToko AND tp.status='aktif'
         ORDER BY p.nama ASC"
    ), MYSQLI_ASSOC);

    $semuaPenjual = mysqli_fetch_all(mysqli_query(
        $conn,
        "SELECT * FROM penjual WHERE status='aktif' ORDER BY nama ASC"
    ), MYSQLI_ASSOC);
}