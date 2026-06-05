<?php
// views/penjual/owner/sections/keuangan_data.php

if (!isset($conn) || !isset($idToko)) {
    die("Akses langsung ditolak.");
}

// =========================================================================
// AKSI POST 1: Simpan Transaksi Manual Pengeluaran
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_keuangan') {
    // Validasi tipe — hanya 'masuk' atau 'keluar' yang diizinkan
    $tipe_post     = $_POST['tipe'] ?? 'keluar';
    $tipe_input    = in_array($tipe_post, ['masuk', 'keluar']) ? $tipe_post : 'keluar';
    // Tanggal otomatis dari server (hari ini)
    $tanggal_input = date('Y-m-d');
    $jumlah_input  = floatval($_POST['jumlah']);
    $keterangan    = mysqli_real_escape_string($conn, $_POST['keterangan']);

    if ($jumlah_input > 0 && !empty($keterangan)) {
        $query_insert = "INSERT INTO `keuangan` (`id_toko`, `tipe`, `jumlah`, `keterangan`, `tanggal`) 
                         VALUES ($idToko, '$tipe_input', $jumlah_input, '$keterangan', '$tanggal_input')";
        if (mysqli_query($conn, $query_insert)) {
            $label = $tipe_input === 'masuk' ? 'pemasukan' : 'pengeluaran';
            $_SESSION['feedback_kas'] = ['type' => 'success', 'msg' => "Catatan $label berhasil disimpan!"];
        } else {
            $_SESSION['feedback_kas'] = ['type' => 'danger', 'msg' => 'Gagal menyimpan data ke database.'];
        }
    } else {
        $_SESSION['feedback_kas'] = ['type' => 'danger', 'msg' => 'Formulir tidak valid. Nominal dan keterangan wajib diisi.'];
    }
    header("Location: index.php?section=keuangan&filter_date=" . $tanggal_input);
    exit;
}

// =========================================================================
// AKSI POST 2: Jalankan Soft Delete Keuangan
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'soft_delete_keuangan') {
    $id_keuangan = intval($_POST['id_keuangan']);
    $tgl_kembali = isset($_POST['current_filter']) ? $_POST['current_filter'] : date('Y-m-d');

    $query_soft_delete = "UPDATE `keuangan` SET `deleted_at` = NOW() 
                          WHERE `id_keuangan` = $id_keuangan AND `id_toko` = $idToko";
    if (mysqli_query($conn, $query_soft_delete)) {
        $_SESSION['feedback_kas'] = ['type' => 'success', 'msg' => 'Data transaksi berhasil dibuang ke kotak sampah.'];
    } else {
        $_SESSION['feedback_kas'] = ['type' => 'danger', 'msg' => 'Gagal menghapus data dari sistem.'];
    }
    header("Location: index.php?section=keuangan&filter_date=" . $tgl_kembali);
    exit;
}

// =========================================================================
// LOGIC GET: Hitung Angka Ringkasan Kartu (Dinamis Real-Time)
// =========================================================================
$filter_tanggal = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// 1. Saldo Berjalan Kumulatif (Semua Masuk - Semua Keluar yang Aktif)
$query_saldo = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN `tipe` = 'masuk' THEN `jumlah` ELSE 0 END) - 
    SUM(CASE WHEN `tipe` = 'keluar' THEN `jumlah` ELSE 0 END) AS `saldo_sekarang`
    FROM `keuangan` WHERE `id_toko` = $idToko AND `deleted_at` IS NULL");
$data_saldo = mysqli_fetch_assoc($query_saldo);
$saldo_sekarang = (float)($data_saldo['saldo_sekarang'] ?? 0);

if ($filter_tanggal === 'semua') {
    // 2. Total Pemasukan Kumulatif Semua Tanggal
    $query_masuk = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'masuk' AND `deleted_at` IS NULL");
    
    // 3. Total Pengeluaran Kumulatif Semua Tanggal
    $query_keluar = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'keluar' AND `deleted_at` IS NULL");

    // 4. Jumlah Aktivitas Log Semua Tanggal
    $query_trx = mysqli_query($conn, "SELECT COUNT(*) AS `total_trx` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `deleted_at` IS NULL");

    // 5. Riwayat Penuh Semua Tanggal
    $query_riwayat = mysqli_query($conn, "SELECT * FROM `keuangan` 
        WHERE `id_toko` = $idToko 
        AND `deleted_at` IS NULL 
        ORDER BY `tanggal` DESC, `id_keuangan` DESC");
} else {
    // 2. Total Pemasukan pada Tanggal Terpilih (Otomatis Pesanan Selesai + Manual)
    $query_masuk = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'masuk' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    // 3. Total Pengeluaran pada Tanggal Terpilih
    $query_keluar = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'keluar' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    // 4. Jumlah Aktivitas Log/Catatan Transaksi pada Tanggal Terpilih
    $query_trx = mysqli_query($conn, "SELECT COUNT(*) AS `total_trx` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    // 5. Riwayat Penuh untuk Di-looping ke Tabel Utama (difilter per tanggal terpilih)
    $query_riwayat = mysqli_query($conn, "SELECT * FROM `keuangan` 
        WHERE `id_toko` = $idToko 
        AND `tanggal` = '$filter_tanggal'
        AND `deleted_at` IS NULL 
        ORDER BY `id_keuangan` DESC");
}

$data_masuk = mysqli_fetch_assoc($query_masuk);
$pemasukan_hari_ini = (float)($data_masuk['total'] ?? 0);

$data_keluar = mysqli_fetch_assoc($query_keluar);
$pengeluaran_hari_ini = (float)($data_keluar['total'] ?? 0);

$data_trx = mysqli_fetch_assoc($query_trx);
$total_transaksi = (int)($data_trx['total_trx'] ?? 0);