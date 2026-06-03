<?php // sections/tools_data.php

/* ══ DOWNLOAD TEMPLATE ══ */
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    if ($type === 'template_murid') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_murid.csv"');
        echo "nisn,nama,password,id_kelas\n";
        echo "0123456789,Budi Santoso,,10 RPL 1\n";
        echo "0987654321,Siti Rahayu,password123,11 TKJ 2\n";
        echo "1122334455,Andi Kurniawan,,12 Akuntansi 1\n";
        exit;
    }
    if ($type === 'template_guru') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_guru.csv"');
        echo "nuptk,nama,password\n";
        echo "1234567890123456,Pak Fajar,\n";
        echo "6543210987654321,Bu Sari,password123\n";
        exit;
    }
}

/* ══ IMPORT RESULT ══ */
$importResult = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);

/* ══ LOG SISTEM ══ */
$logRole = $_GET['log_role'] ?? '';
$logPage = max(1, (int) ($_GET['log_page'] ?? 1));
$logPerPage = 10;
$logOffset = ($logPage - 1) * $logPerPage;

$whereLog = $logRole ? "WHERE user_role = '" . mysqli_real_escape_string($conn, $logRole) . "'" : '';
$logTotal = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM log_sistem $whereLog"))['c'];
$logSistem = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT * FROM log_sistem $whereLog ORDER BY dibuat_pada DESC LIMIT $logPerPage OFFSET $logOffset"
), MYSQLI_ASSOC);

/* ══ DATA TERHAPUS ══ */
$deletedMurid = mysqli_fetch_all(mysqli_query($conn, "SELECT nisn, nama, deleted_at FROM murid WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedGuru = mysqli_fetch_all(mysqli_query($conn, "SELECT nuptk, nama, deleted_at FROM guru WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedPenjual = mysqli_fetch_all(mysqli_query($conn, "SELECT id_penjual, nama, deleted_at FROM penjual WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedToko = mysqli_fetch_all(mysqli_query($conn, "SELECT id_toko, nama_toko as nama, deleted_at FROM toko WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedMenu = mysqli_fetch_all(mysqli_query($conn, "SELECT id_menu, nama_menu as nama, deleted_at FROM menu WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedAdmin = mysqli_fetch_all(mysqli_query($conn, "SELECT id_admin, nama, deleted_at FROM admin WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"), MYSQLI_ASSOC);
$deletedKelas = mysqli_fetch_all(mysqli_query($conn, "
    SELECT k.id_kelas, 
           CONCAT(k.kelas, ' ', COALESCE(j.nama_jurusan, CONCAT('ID-JUR-', k.id_jurusan)), ' ', k.rombel) as nama, 
           k.deleted_at 
    FROM kelas k 
    LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan 
    WHERE k.deleted_at IS NOT NULL 
    ORDER BY k.deleted_at DESC
"), MYSQLI_ASSOC);

$allDeleted = [
    'Murid' => ['data' => $deletedMurid, 'tabel' => 'murid', 'id_col' => 'nisn'],
    'Guru' => ['data' => $deletedGuru, 'tabel' => 'guru', 'id_col' => 'nuptk'],
    'Penjual' => ['data' => $deletedPenjual, 'tabel' => 'penjual', 'id_col' => 'id_penjual'],
    'Kantin' => ['data' => $deletedToko, 'tabel' => 'toko', 'id_col' => 'id_toko'],
    'Menu' => ['data' => $deletedMenu, 'tabel' => 'menu', 'id_col' => 'id_menu'],
    'Admin' => ['data' => $deletedAdmin, 'tabel' => 'admin', 'id_col' => 'id_admin'],
    'Kelas' => ['data' => $deletedKelas, 'tabel' => 'kelas', 'id_col' => 'id_kelas'],
];