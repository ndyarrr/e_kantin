<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'penjual') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../includes/inbox_query.php';
require_once __DIR__ . '/../../../config/toko_foto.php';

$penjualId = (int) ($_SESSION['user_id'] ?? 0);
$idToko = (int) ($_SESSION['id_toko'] ?? 0);

if ($idToko === 0) {
    $rToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId AND status='aktif' ORDER BY id DESC LIMIT 1"
    ));
    $idToko = (int) ($rToko['id_toko'] ?? 0);
    $_SESSION['id_toko'] = $idToko;
}

if ($idToko === 0) {
    echo json_encode(['success' => false, 'message' => 'Toko tidak ditemukan']);
    exit;
}

$filterStatus = $_GET['status_filter'] ?? 'semua';
$inboxSearch = $_GET['inbox_search'] ?? '';

$data = inbox_get_data($conn, $idToko, $filterStatus, $inboxSearch);
$daftarPesanan = $data['daftarPesanan'];
$jumlahPerStatus = $data['jumlahPerStatus'];
$filterStatus = $data['filterStatus'];
$inboxSearch = $data['inboxSearch'];

$profilPenjual = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT t.nama_toko, t.foto_toko FROM toko t WHERE t.id_toko = $idToko AND t.deleted_at IS NULL LIMIT 1"
)) ?: ['nama_toko' => 'Kantin', 'foto_toko' => ''];

$kasirInfo = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT p.nama, tp.shift 
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual AND tp.status = 'aktif'
     WHERE p.id_penjual = $penjualId
     LIMIT 1"
));
$profilPenjual['nama'] = $kasirInfo['nama'] ?? $_SESSION['user_nama'] ?? 'Kasir';
$profilPenjual['shift'] = !empty($kasirInfo['shift']) ? $kasirInfo['shift'] : 'Bebas';

$fotoTokoNota = !empty($profilPenjual['foto_toko'])
    ? tokoFotoUrl($profilPenjual['foto_toko'], '../../../')
    : '';

$tabs = [
    'semua'        => ['label' => 'Semua',        'icon' => 'fa-inbox'],
    'menunggu'     => ['label' => 'Menunggu',     'icon' => 'fa-clock'],
    'dikonfirmasi' => ['label' => 'Diproses',     'icon' => 'fa-fire-burner'],
    'siap_diambil' => ['label' => 'Siap Diambil', 'icon' => 'fa-bell-concierge'],
    'selesai'      => ['label' => 'Selesai',      'icon' => 'fa-circle-check'],
    'tidak_diambil'=> ['label' => 'Tidak Diambil', 'icon' => 'fa-circle-exclamation'],
    'dibatalkan'   => ['label' => 'Dibatalkan',   'icon' => 'fa-circle-xmark'],
];

ob_start();
require __DIR__ . '/../sections/inbox_fragment.php';
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html,
    'filterStatus' => $filterStatus,
    'inboxSearch' => $inboxSearch,
    'jumlahPerStatus' => $jumlahPerStatus,
    'totalPesanan' => count($daftarPesanan),
]);
