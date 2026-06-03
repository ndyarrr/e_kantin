<?php
// sections/penjual_data.php
$selectedPenjual = (int) ($_GET['penjual'] ?? $_POST['_selected_penjual'] ?? 0);

$penjuals = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT p.*,
        GROUP_CONCAT(t.nama_toko SEPARATOR ', ') as kantin_dikelola,
        COUNT(DISTINCT tp.id_toko) as total_kantin
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual AND tp.status = 'aktif'
     LEFT JOIN toko t ON t.id_toko = tp.id_toko AND t.deleted_at IS NULL
     WHERE p.role = 'owner'
     AND p.deleted_at IS NULL
     GROUP BY p.id_penjual
     ORDER BY p.dibuat_pada DESC"
), MYSQLI_ASSOC);

$totalPenjual = count($penjuals);
$penjualAktif = count(array_filter($penjuals, fn($p) => $p['status'] === 'aktif'));

$semuaToko = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT id_toko, nama_toko FROM toko 
     WHERE deleted_at IS NULL
     AND id_toko NOT IN (
         SELECT tp.id_toko FROM toko_penjual tp
         JOIN penjual p ON p.id_penjual = tp.id_penjual
         WHERE p.role = 'owner' AND tp.status = 'aktif' AND p.deleted_at IS NULL
     )
     ORDER BY nama_toko ASC"
), MYSQLI_ASSOC);

$detailPenjual = null;
$kantinPenjual = [];

if ($selectedPenjual) {
    $detailPenjual = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT * FROM penjual 
         WHERE id_penjual=$selectedPenjual 
         AND role='owner' 
         AND deleted_at IS NULL"
    ));

    if ($detailPenjual) {
        $kantinPenjual = mysqli_fetch_all(mysqli_query(
            $conn,
            "SELECT tp.id, tp.id_toko, tp.shift, t.nama_toko
             FROM toko_penjual tp
             JOIN toko t ON t.id_toko = tp.id_toko
             WHERE tp.id_penjual=$selectedPenjual 
             AND tp.status='aktif'
             AND t.deleted_at IS NULL
             ORDER BY t.nama_toko ASC"
        ), MYSQLI_ASSOC);

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
                 AND p.deleted_at IS NULL
                 ORDER BY p.nama ASC"
            ), MYSQLI_ASSOC);
        }
    }
}