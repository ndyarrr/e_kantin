<?php 
// sections/pembeli_data.php

/* ══ STATS ══ */
$totalMurid  = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid"))['c'];
$totalGuru   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM guru"))['c'];
$totalPembeli = $totalMurid + $totalGuru;

$aktifMurid = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE status='aktif'"))['c'];
$aktifGuru  = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM guru WHERE status='aktif'"))['c'];
$totalAktif  = $aktifMurid + $aktifGuru;

/* ══ SEARCH ══ */
$searchPembeli = trim($_GET['q_pembeli'] ?? '');
$filterKategori = $_GET['filter_kategori'] ?? ''; // 'murid' | 'guru' | ''

/* ══ DAFTAR MURID ══ */
$sqlMurid = "
    SELECT
        m.nisn        AS id_pembeli,
        m.nama,
        m.password,
        m.nisn        AS nisn_nuptk,
        'Murid'       AS kategori,
        m.status,
        k.nama_kelas  AS info_tambahan,
        j.nama_jurusan AS info2
    FROM murid m
    LEFT JOIN kelas k ON k.id_kelas = m.id_kelas
    LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan
";

/* ══ DAFTAR GURU ══ */
$sqlGuru = "
    SELECT
        g.nuptk       AS id_pembeli,
        g.nama,
        g.password,
        g.nuptk       AS nisn_nuptk,
        'Guru'        AS kategori,
        g.status,
        NULL          AS info_tambahan,
        NULL          AS info2
    FROM guru g
";

/* filter search */
$whereMurid = [];
$whereGuru  = [];

if ($searchPembeli !== '') {
    $s = mysqli_real_escape_string($conn, $searchPembeli);
    $whereMurid[] = "(m.nama LIKE '%$s%' OR m.nisn LIKE '%$s%')";
    $whereGuru[]  = "(g.nama LIKE '%$s%' OR g.nuptk LIKE '%$s%')";
}

if ($whereMurid) $sqlMurid .= ' WHERE ' . implode(' AND ', $whereMurid);
if ($whereGuru)  $sqlGuru  .= ' WHERE ' . implode(' AND ', $whereGuru);

/* gabung berdasarkan filter kategori */
$daftarPembeli = [];
if ($filterKategori !== 'guru') {
    $resMurid = mysqli_query($conn, $sqlMurid);
    while ($row = mysqli_fetch_assoc($resMurid)) $daftarPembeli[] = $row;
}
if ($filterKategori !== 'murid') {
    $resGuru = mysqli_query($conn, $sqlGuru);
    while ($row = mysqli_fetch_assoc($resGuru)) $daftarPembeli[] = $row;
}

/* ══ DATA DROPDOWN KELAS & JURUSAN ══ */
$semuaKelas   = mysqli_fetch_all(mysqli_query($conn,
    "SELECT k.id_kelas, k.nama_kelas, j.nama_jurusan
     FROM kelas k LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan
     ORDER BY j.nama_jurusan, k.nama_kelas"), MYSQLI_ASSOC);