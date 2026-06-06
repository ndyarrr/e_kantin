<?php
// sections/kantin_data.php
require_once __DIR__ . '/../../../config/kantin_slot.php';

$selectedToko = (int) ($_POST['_selected_toko'] ?? $_GET['toko'] ?? 0);

$slotRows = kantinSlotGetAll($conn);
$tokos = array_values(array_filter($slotRows, fn($s) => !empty($s['id_toko'])));
$slotKosongList = kantinSlotGetEmptyList($conn);
$lastSlot = end($slotRows);
$canKurangSlot = $slotKantin > 1 && $lastSlot && empty($lastSlot['id_toko']);
reset($slotRows);

$slotPerPage = 10;
$slotTotal = count($slotRows);
$showSlotPagination = $slotTotal > $slotPerPage;
$slotTotalPages = $showSlotPagination ? (int) ceil($slotTotal / $slotPerPage) : 1;
$slotPage = max(1, (int) ($_GET['slot_page'] ?? 1));
if ($slotPage > $slotTotalPages) {
    $slotPage = $slotTotalPages;
}
$slotRowsPage = $showSlotPagination
    ? array_slice($slotRows, ($slotPage - 1) * $slotPerPage, $slotPerPage)
    : $slotRows;
$slotPageQuery = ($showSlotPagination && $slotPage > 1) ? '&slot_page=' . $slotPage : '';

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
