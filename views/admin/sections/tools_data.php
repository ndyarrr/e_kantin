<?php // sections/tools_data.php

/* ══ DOWNLOAD TEMPLATE ══ */
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    if ($type === 'template_murid') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_murid.csv"');
        echo "nisn,nama,password,id_kelas,id_jurusan\n";
        echo "0123456789,Budi Santoso,,1,1\n";
        echo "0987654321,Siti Rahayu,password123,2,2\n";
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
$logPerPage = 20;
$logOffset = ($logPage - 1) * $logPerPage;

$whereLog = $logRole ? "WHERE user_role = '" . mysqli_real_escape_string($conn, $logRole) . "'" : '';
$logTotal = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM log_sistem $whereLog"))['c'];
$logSistem = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT * FROM log_sistem $whereLog ORDER BY dibuat_pada DESC LIMIT $logPerPage OFFSET $logOffset"
), MYSQLI_ASSOC);