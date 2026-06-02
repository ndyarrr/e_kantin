<?php // sections/dashboard_data.php
/* ── Pendapatan hari ini ── */
$pendapatanHariIni = (float) (mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     JOIN menu m ON m.id_menu = dp.id_menu
     WHERE m.id_toko = $idToko
       AND DATE(p.waktu_pesan) = CURDATE()
       AND p.status = 'selesai'"
))['total'] ?? 0);
/* ── Pendapatan kemarin ── */
$pendapatanKemarin = (float) (mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     JOIN menu m ON m.id_menu = dp.id_menu
     WHERE m.id_toko = $idToko
       AND DATE(p.waktu_pesan) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
       AND p.status = 'selesai'"
))['total'] ?? 0);
$trendPendapatan = $pendapatanKemarin > 0
    ? round(($pendapatanHariIni - $pendapatanKemarin) / $pendapatanKemarin * 100, 1)
    : ($pendapatanHariIni > 0 ? 100 : 0);
/* ── Pesanan selesai hari ini ── */
$pesananSelesai = (int) (mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c FROM pesanan
     WHERE id_toko = $idToko
       AND DATE(waktu_pesan) = CURDATE()
       AND status = 'selesai'"
))['c'] ?? 0);
$pesananSelesaiKemarin = (int) (mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c FROM pesanan
     WHERE id_toko = $idToko
       AND DATE(waktu_pesan) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
       AND status = 'selesai'"
))['c'] ?? 0);
$trendPesanan = $pesananSelesaiKemarin > 0
    ? round(($pesananSelesai - $pesananSelesaiKemarin) / $pesananSelesaiKemarin * 100, 1)
    : ($pesananSelesai > 0 ? 100 : 0);
/* ── Item terlaris hari ini ── */
$itemTerlaris = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT m.nama_menu, SUM(dp.jumlah) AS total_jual
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     JOIN menu m ON m.id_menu = dp.id_menu
     WHERE m.id_toko = $idToko
       AND DATE(p.waktu_pesan) = CURDATE()
       AND p.status = 'selesai'
     GROUP BY dp.id_menu
     ORDER BY total_jual DESC
     LIMIT 1"
));
/* ── Grafik tren 7 hari ── */
$grafikLabels = [];
$grafikValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $grafikLabels[] = date('d/m', strtotime("-{$i} days"));
    $grafikValues[] = (float) (mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total
         FROM detail_pesanan dp
         JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
         JOIN menu m ON m.id_menu = dp.id_menu
         WHERE m.id_toko = $idToko
           AND DATE(p.waktu_pesan) = '$date'
           AND p.status = 'selesai'"
    ))['total'] ?? 0);
}
/* ── Distribusi per kategori minggu ini ── */
// Catatan: Karena di tabel menu tidak ada kolom 'kategori', query ini mengambil default 'Menu' 
// agar tidak menghasilkan error Unknown Column.
$distribusi = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT m.nama_menu AS kategori, SUM(dp.jumlah) AS total
     FROM detail_pesanan dp
     JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
     JOIN menu m ON m.id_menu = dp.id_menu
     WHERE m.id_toko = $idToko
       AND WEEK(p.waktu_pesan) = WEEK(CURDATE())
       AND p.status = 'selesai'
     GROUP BY m.id_menu
     ORDER BY total DESC
     LIMIT 5"
), MYSQLI_ASSOC);
/* ── Pesanan terbaru ── */
$pesananTerbaru = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT p.id_pesanan, p.waktu_pesan, p.status, p.total_harga,
            COALESCE(m2.nama, g.nama, 'Unknown') AS nama_pembeli
     FROM pesanan p
     LEFT JOIN murid m2 ON m2.nisn = p.nisn_pembeli
     LEFT JOIN guru g ON g.nuptk = p.nuptk_pembeli
     WHERE p.id_toko = $idToko
     ORDER BY p.waktu_pesan DESC
     LIMIT 8"
), MYSQLI_ASSOC);
/* ── Badge inbox ── */
$totalPesananBaru = (int) (mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c FROM pesanan
     WHERE id_toko = $idToko AND status = 'menunggu'"
))['c'] ?? 0);

/* ── Status Toko/Kantin ── */
$tokoStatusQuery = mysqli_query($conn, "SELECT status FROM toko WHERE id_toko = $idToko LIMIT 1");
$tokoStatusRow = mysqli_fetch_assoc($tokoStatusQuery);
$statusTokoAktif = $tokoStatusRow['status'] ?? 'buka';
