<?php
/**
 * export_csv.php
 * Menghasilkan file CSV berisi:
 *   1. Tren Penjualan Mingguan (7 hari terakhir)
 *   2. Distribusi Pesanan Produk (donat chart)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guard: owner atau staf yang boleh akses
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['penjual', 'staf'])) {
    http_response_code(403);
    exit('Akses ditolak.');
}

date_default_timezone_set('Asia/Jakarta'); // WIB UTC+7

require_once __DIR__ . '/../../../../config/database.php';

$idToko = (int) ($_SESSION['id_toko'] ?? 0);
if ($idToko === 0) {
    $penjualId = (int) ($_SESSION['user_id'] ?? 0);
    $rToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId AND status='aktif' ORDER BY id DESC LIMIT 1"
    ));
    $idToko = (int) ($rToko['id_toko'] ?? 0);
    $_SESSION['id_toko'] = $idToko;
}
$namaToko = 'E-Kantin';

// Ambil nama toko untuk header
$tokoRow = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT nama_toko FROM toko WHERE id_toko = $idToko LIMIT 1"
));
if ($tokoRow) $namaToko = $tokoRow['nama_toko'];

// ── Tren Penjualan 7 Hari Terakhir ──────────────────────────────────────────
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr  = date('Y-m-d', strtotime("-{$i} days"));
    $labelStr = date('d/m/Y', strtotime("-{$i} days"));
    $hari     = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w', strtotime($dateStr))];

    $total = (float) (mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total
         FROM detail_pesanan dp
         JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
         JOIN menu m    ON m.id_menu    = dp.id_menu
         WHERE m.id_toko = $idToko
           AND DATE(p.waktu_pesan) = '$dateStr'
           AND p.status = 'selesai'"
    ))['total'] ?? 0);

    $pesanan = (int) (mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) AS c FROM pesanan
         WHERE id_toko = $idToko
           AND DATE(waktu_pesan) = '$dateStr'
           AND status = 'selesai'"
    ))['c'] ?? 0);

    $trendData[] = [
        'tanggal'       => $labelStr,
        'hari'          => $hari,
        'pendapatan'    => $total,
        'jumlah_pesanan'=> $pesanan,
    ];
}

// ── Distribusi Produk Minggu Ini (donat chart) ───────────────────────────────
$distribusiData = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT m.nama_menu, SUM(dp.jumlah) AS total_porsi,
            COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total_omset
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     JOIN menu m    ON m.id_menu    = dp.id_menu
     WHERE m.id_toko = $idToko
       AND WEEK(p.waktu_pesan) = WEEK(CURDATE())
       AND YEAR(p.waktu_pesan) = YEAR(CURDATE())
       AND p.status = 'selesai'
     GROUP BY m.id_menu
     ORDER BY total_porsi DESC
     LIMIT 10"
), MYSQLI_ASSOC);

// ── Generate file CSV ────────────────────────────────────────────────────────
$type = $_GET['type'] ?? 'dashboard';

// Clean shop name for filename
$cleanNamaToko = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaToko);

if ($type === 'tren') {
    $filename = 'laporan_tren_penjualan_' . $cleanNamaToko . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['LAPORAN TREN PENJUALAN MINGGUAN (7 HARI TERAKHIR)']);
    fputcsv($out, ['Toko', $namaToko]);
    fputcsv($out, ['Dicetak', date('d/m/Y H:i:s')]);
    fputcsv($out, ['Periode', date('d/m/Y', strtotime('-6 days')) . ' s/d ' . date('d/m/Y')]);
    fputcsv($out, []);

    fputcsv($out, ['Tanggal', 'Hari', 'Pendapatan (Rp)', 'Jumlah Pesanan Selesai']);

    $totalPendapatan = 0;
    $totalPesananSelesai = 0;
    foreach ($trendData as $row) {
        fputcsv($out, [
            $row['tanggal'],
            $row['hari'],
            number_format($row['pendapatan'], 0, ',', '.'),
            $row['jumlah_pesanan'],
        ]);
        $totalPendapatan     += $row['pendapatan'];
        $totalPesananSelesai += $row['jumlah_pesanan'];
    }
    // Baris total
    fputcsv($out, [
        'TOTAL',
        '',
        number_format($totalPendapatan, 0, ',', '.'),
        $totalPesananSelesai,
    ]);

    fputcsv($out, []);
    fputcsv($out, ['--- Akhir Laporan ---']);
    fclose($out);
    exit;

} elseif ($type === 'distribusi') {
    $filename = 'laporan_distribusi_produk_' . $cleanNamaToko . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['LAPORAN DISTRIBUSI PESANAN PRODUK MINGGU INI']);
    fputcsv($out, ['Toko', $namaToko]);
    fputcsv($out, ['Dicetak', date('d/m/Y H:i:s')]);
    fputcsv($out, ['Periode', date('d/m/Y', strtotime('-6 days')) . ' s/d ' . date('d/m/Y')]);
    fputcsv($out, []);

    if (empty($distribusiData)) {
        fputcsv($out, ['Belum ada data penjualan minggu ini.']);
    } else {
        fputcsv($out, ['Nama Produk', 'Porsi Terjual', 'Total Omset (Rp)', 'Persentase (%)']);

        $grandTotalPorsi = array_sum(array_column($distribusiData, 'total_porsi'));
        foreach ($distribusiData as $d) {
            $persen = $grandTotalPorsi > 0
                ? round($d['total_porsi'] / $grandTotalPorsi * 100, 1)
                : 0;
            fputcsv($out, [
                $d['nama_menu'],
                $d['total_porsi'],
                number_format($d['total_omset'], 0, ',', '.'),
                $persen . '%',
            ]);
        }
        // Baris total
        $grandOmset = array_sum(array_column($distribusiData, 'total_omset'));
        fputcsv($out, [
            'TOTAL',
            $grandTotalPorsi,
            number_format($grandOmset, 0, ',', '.'),
            '100%',
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, ['--- Akhir Laporan ---']);
    fclose($out);
    exit;

} else {
    // Default: type = dashboard
    $filename = 'laporan_dashboard_' . $cleanNamaToko . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    // Calculate aggregate totals
    $totalPendapatan = 0;
    $totalPesananSelesai = 0;
    foreach ($trendData as $row) {
        $totalPendapatan     += $row['pendapatan'];
        $totalPesananSelesai += $row['jumlah_pesanan'];
    }

    fputcsv($out, ['LAPORAN RINGKASAN DASHBOARD E-KANTIN']);
    fputcsv($out, ['Toko', $namaToko]);
    fputcsv($out, ['Dicetak', date('d/m/Y H:i:s')]);
    fputcsv($out, ['Periode', date('d/m/Y', strtotime('-6 days')) . ' s/d ' . date('d/m/Y')]);
    fputcsv($out, []);

    fputcsv($out, ['Parameter Ringkasan', 'Nilai']);
    fputcsv($out, ['Total Pendapatan Mingguan', 'Rp ' . number_format($totalPendapatan, 0, ',', '.')]);
    fputcsv($out, ['Total Pesanan Selesai Mingguan', $totalPesananSelesai]);

    fputcsv($out, []);
    fputcsv($out, ['--- Akhir Laporan ---']);
    fclose($out);
    exit;
}
