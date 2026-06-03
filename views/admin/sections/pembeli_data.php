<?php
// sections/pembeli_data.php

/* ══ STATS ══ */
$totalMurid = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE deleted_at IS NULL"))['c'];
$totalGuru = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM guru WHERE deleted_at IS NULL"))['c'];
$totalPembeli = $totalMurid + $totalGuru;
$aktifMurid = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM murid WHERE status='aktif' AND deleted_at IS NULL"))['c'];
$aktifGuru = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM guru WHERE status='aktif' AND deleted_at IS NULL"))['c'];
$totalAktif = $aktifMurid + $aktifGuru;

/* ══ SEARCH & FILTER ══ */
$searchPembeli = trim($_GET['q_pembeli'] ?? '');
$filterKategori = $_GET['filter_kategori'] ?? '';

/* ══ BUILD WHERE ══ */
$whereMurid = ["m.deleted_at IS NULL"];
$whereGuru = ["g.deleted_at IS NULL"];

if ($searchPembeli !== '') {
    $s = mysqli_real_escape_string($conn, $searchPembeli);
    $whereMurid[] = "(m.nama LIKE '%$s%' OR m.nisn LIKE '%$s%')";
    $whereGuru[] = "(g.nama LIKE '%$s%' OR g.nuptk LIKE '%$s%')";
}

/* ══ QUERY MURID ══ */
$sqlMurid = "
    SELECT
        m.nisn      AS id_pembeli,
        m.nama,
        m.password,
        m.nisn      AS nisn_nuptk,
        'Murid'     AS kategori,
        m.status,
        m.terakhir_login,
        m.id_kelas,
        k.id_jurusan, -- 🔥 FIX MUTLAK: id_jurusan diambil dari tabel kelas (k), BUKAN m.id_jurusan!
        k.kelas     AS tingkat,
        k.rombel,     -- 🔥 Ambil rombel asli dari tabel kelas
        CONCAT(k.kelas, ' ', j.nama_jurusan, ' ', k.rombel) AS info_tambahan
    FROM murid m
    LEFT JOIN kelas k ON k.id_kelas = m.id_kelas
    LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan
    WHERE " . implode(' AND ', $whereMurid);

/* ══ QUERY GURU ══ */
$sqlGuru = "
    SELECT
        g.nuptk     AS id_pembeli,
        g.nama,
        g.password,
        g.nuptk     AS nisn_nuptk,
        'Guru'      AS kategori,
        g.status,
        g.terakhir_login,
        NULL        AS id_kelas,
        NULL        AS id_jurusan,
        NULL        AS tingkat,
        ''          AS rombel, 
        NULL        AS info_tambahan
    FROM guru g
    WHERE " . implode(' AND ', $whereGuru);

/* ══ GABUNG HASIL ══ */
$daftarPembeli = [];
if ($filterKategori !== 'guru') {
    $resMurid = mysqli_query($conn, $sqlMurid);
    if ($resMurid) {
        while ($row = mysqli_fetch_assoc($resMurid)) {
            $daftarPembeli[] = $row;
        }
    }
}
if ($filterKategori !== 'murid') {
    $resGuru = mysqli_query($conn, $sqlGuru);
    if ($resGuru) {
        while ($row = mysqli_fetch_assoc($resGuru)) {
            $daftarPembeli[] = $row;
        }
    }
}

/* ══ DROPDOWN DATA ══ */
$semuaJurusan = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT * FROM jurusan ORDER BY nama_jurusan ASC"
), MYSQLI_ASSOC);

$semuaKelas = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT k.id_kelas,
            k.id_jurusan, -- 🔥 BARU: Tarik id_jurusan di sini biar loop dropdown rombel dapet data aslinya
            k.kelas AS tingkat,
            k.rombel, 
            CONCAT(k.kelas, ' ', j.nama_jurusan, ' ', k.rombel) AS nama_kelas
     FROM kelas k
     JOIN jurusan j ON j.id_jurusan = k.id_jurusan
     WHERE k.deleted_at IS NULL
     ORDER BY k.kelas ASC, j.nama_jurusan ASC, k.rombel ASC"
), MYSQLI_ASSOC);

$semuaTingkat = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT DISTINCT kelas FROM kelas WHERE deleted_at IS NULL ORDER BY kelas ASC"
), MYSQLI_ASSOC);