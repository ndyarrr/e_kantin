<?php
/** @var mysqli $conn */
/** @var int $idToko */

/* ── Filter status & pencarian dari URL ── */
$statusValid     = ['menunggu', 'dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];
$filterStatus    = $_GET['status_filter'] ?? 'semua';
if (!in_array($filterStatus, $statusValid)) {
    $filterStatus = 'semua';
}
$inboxSearch = $_GET['inbox_search'] ?? '';

/* ── Query pesanan ── */
$sqlPesanan = "SELECT p.id_pesanan, p.waktu_pesan, p.status, p.total_harga, p.waktu_ambil,
                      COALESCE(m2.nama, g.nama, 'Unknown') AS nama_pembeli,
                      COALESCE(CONCAT(k.kelas, ' ', j.nama_jurusan, ' ', k.rombel), '-') AS kelas_pembeli
               FROM pesanan p
               LEFT JOIN murid m2 ON m2.nisn  = p.nisn_pembeli
               LEFT JOIN kelas k ON k.id_kelas = m2.id_kelas
               LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan
               LEFT JOIN guru  g  ON g.nuptk  = p.nuptk_pembeli
               WHERE p.id_toko = $idToko";

if ($filterStatus !== 'semua') {
    $sqlFilter = $sqlPesanan . " AND p.status = ?";
    if (!empty($inboxSearch)) {
        $sqlFilter .= " AND (m2.nama LIKE ? OR g.nama LIKE ?)";
    }
    $sqlFilter .= " ORDER BY CASE p.status
            WHEN 'menunggu'     THEN 1
            WHEN 'dikonfirmasi' THEN 2
            WHEN 'siap_diambil' THEN 3
            WHEN 'selesai'      THEN 4
            WHEN 'dibatalkan'   THEN 5
        END, p.waktu_pesan DESC
        LIMIT 50";

    $stmt = mysqli_prepare($conn, $sqlFilter);
    if (!empty($inboxSearch)) {
        $likeSearch = '%' . $inboxSearch . '%';
        mysqli_stmt_bind_param($stmt, 'sss', $filterStatus, $likeSearch, $likeSearch);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $filterStatus);
    }
    mysqli_stmt_execute($stmt);
    $daftarPesanan = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    if (!empty($inboxSearch)) {
        $sqlFilter = $sqlPesanan . " AND (m2.nama LIKE ? OR g.nama LIKE ?)";
        $sqlFilter .= " ORDER BY
                            CASE p.status
                                WHEN 'menunggu'     THEN 1
                                WHEN 'dikonfirmasi' THEN 2
                                WHEN 'siap_diambil' THEN 3
                                WHEN 'selesai'      THEN 4
                                WHEN 'dibatalkan'   THEN 5
                            END,
                            p.waktu_pesan DESC
                         LIMIT 50";
        $stmt = mysqli_prepare($conn, $sqlFilter);
        $likeSearch = '%' . $inboxSearch . '%';
        mysqli_stmt_bind_param($stmt, 'ss', $likeSearch, $likeSearch);
        mysqli_stmt_execute($stmt);
        $daftarPesanan = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        $sqlPesanan .= " ORDER BY
                            CASE p.status
                                WHEN 'menunggu'     THEN 1
                                WHEN 'dikonfirmasi' THEN 2
                                WHEN 'siap_diambil' THEN 3
                                WHEN 'selesai'      THEN 4
                                WHEN 'dibatalkan'   THEN 5
                            END,
                            p.waktu_pesan DESC
                         LIMIT 50";
        $daftarPesanan = mysqli_fetch_all(mysqli_query($conn, $sqlPesanan), MYSQLI_ASSOC);
    }
}

/* ── Ambil detail item untuk setiap pesanan ── */
foreach ($daftarPesanan as &$pesanan) {
    $idPesanan       = (int) $pesanan['id_pesanan'];
    $pesanan['items'] = mysqli_fetch_all(mysqli_query($conn,
        "SELECT dp.jumlah, dp.harga_satuan, dp.catatan, m.nama_menu
         FROM detail_pesanan dp
         JOIN menu m ON m.id_menu = dp.id_menu
         WHERE dp.id_pesanan = $idPesanan"
     ), MYSQLI_ASSOC);
}
unset($pesanan);

/* ── Hitung jumlah per status yang sesuai pencarian ── */
$sqlCount = "SELECT p.status, COUNT(*) AS total
             FROM pesanan p
             LEFT JOIN murid m2 ON m2.nisn = p.nisn_pembeli
             LEFT JOIN guru g ON g.nuptk = p.nuptk_pembeli
             WHERE p.id_toko = $idToko";

if (!empty($inboxSearch)) {
    $stmtCount = mysqli_prepare($conn, $sqlCount . " AND (m2.nama LIKE ? OR g.nama LIKE ?) GROUP BY p.status");
    $likeSearch = '%' . $inboxSearch . '%';
    mysqli_stmt_bind_param($stmtCount, 'ss', $likeSearch, $likeSearch);
    mysqli_stmt_execute($stmtCount);
    $hitungStatus = mysqli_fetch_all(mysqli_stmt_get_result($stmtCount), MYSQLI_ASSOC);
    mysqli_stmt_close($stmtCount);
} else {
    $hitungStatus = mysqli_fetch_all(mysqli_query($conn, $sqlCount . " GROUP BY p.status"), MYSQLI_ASSOC);
}

$jumlahPerStatus = [];
foreach ($hitungStatus as $row) {
    $jumlahPerStatus[$row['status']] = (int) $row['total'];
}