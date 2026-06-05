<?php
// sections/kantin_data.php
$selectedToko = (int) ($_POST['_selected_toko'] ?? $_GET['toko'] ?? 0);

$tokos = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.*,
        (SELECT COUNT(id_menu) FROM menu WHERE id_toko = t.id_toko AND deleted_at IS NULL) as total_menu,
        (SELECT COUNT(tp1.id) FROM toko_penjual tp1 
         JOIN penjual p1 ON p1.id_penjual = tp1.id_penjual 
         WHERE tp1.id_toko = t.id_toko AND tp1.status = 'aktif' 
         AND p1.role = 'staf' AND p1.deleted_at IS NULL) as total_penjual,
        (SELECT p2.nama FROM penjual p2 
         JOIN toko_penjual tp2 ON tp2.id_penjual = p2.id_penjual 
         WHERE tp2.id_toko = t.id_toko AND p2.role = 'owner' 
         AND tp2.status = 'aktif' AND p2.deleted_at IS NULL LIMIT 1) as nama_owner
     FROM toko t
     WHERE t.deleted_at IS NULL
     ORDER BY t.urutan ASC, t.id_toko ASC"
), MYSQLI_ASSOC);

$detailToko = null;
$menuToko = [];
$penjualToko = [];

if ($selectedToko) {
    $detailToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT t.*,
            (SELECT p2.nama FROM penjual p2 
             JOIN toko_penjual tp2 ON tp2.id_penjual = p2.id_penjual 
             WHERE tp2.id_toko = t.id_toko AND p2.role = 'owner' 
             AND tp2.status = 'aktif' AND p2.deleted_at IS NULL LIMIT 1) as nama_owner
         FROM toko t
         WHERE t.id_toko = $selectedToko AND t.deleted_at IS NULL"
    ));

    $penjualToko = mysqli_fetch_all(mysqli_query(
        $conn,
        "SELECT tp.id as id_tp, tp.shift, p.nama, p.foto_profil
         FROM toko_penjual tp
         JOIN penjual p ON p.id_penjual = tp.id_penjual
         WHERE tp.id_toko = $selectedToko 
         AND tp.status = 'aktif' 
         AND p.role = 'staf'
         AND p.deleted_at IS NULL
         ORDER BY p.nama ASC"
    ), MYSQLI_ASSOC);

    $menuToko = mysqli_fetch_all(mysqli_query(
        $conn,
        "SELECT * FROM menu 
         WHERE id_toko = $selectedToko 
         AND deleted_at IS NULL
         ORDER BY nama_menu ASC"
    ), MYSQLI_ASSOC);
}