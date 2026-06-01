<?php
require_once __DIR__ . '/../../includes/inbox_query.php';

$filterStatus = $_GET['status_filter'] ?? 'semua';
$inboxSearch = $_GET['inbox_search'] ?? '';

$inboxData = inbox_get_data($conn, $idToko, $filterStatus, $inboxSearch);
$daftarPesanan = $inboxData['daftarPesanan'];
$jumlahPerStatus = $inboxData['jumlahPerStatus'];
$filterStatus = $inboxData['filterStatus'];
$inboxSearch = $inboxData['inboxSearch'];
