<?php
// views/penjual/owner/sections/print_keuangan.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guard: hanya owner yang boleh akses
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    http_response_code(403);
    exit('Akses ditolak.');
}

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../config/toko_foto.php';

$penjualNama = $_SESSION['user_nama'] ?? 'Penjual';
$penjualId = (int) ($_SESSION['user_id'] ?? 0);
$idToko = (int) ($_SESSION['id_toko'] ?? 0);

// Ambil data toko
$tokoRow = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT nama_toko, foto_toko FROM toko WHERE id_toko = $idToko AND deleted_at IS NULL LIMIT 1"
));
$namaToko = $tokoRow['nama_toko'] ?? 'E-Kantin';
$fotoToko = $tokoRow['foto_toko'] ?? '';

// Ambil filter tanggal
$filter_tanggal = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// 1. Saldo Berjalan Kumulatif
$query_saldo = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN `tipe` = 'masuk' THEN `jumlah` ELSE 0 END) - 
    SUM(CASE WHEN `tipe` = 'keluar' THEN `jumlah` ELSE 0 END) AS `saldo_sekarang`
    FROM `keuangan` WHERE `id_toko` = $idToko AND `deleted_at` IS NULL");
$data_saldo = mysqli_fetch_assoc($query_saldo);
$saldo_sekarang = (float)($data_saldo['saldo_sekarang'] ?? 0);

if ($filter_tanggal === 'semua') {
    $query_masuk = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'masuk' AND `deleted_at` IS NULL");
    
    $query_keluar = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'keluar' AND `deleted_at` IS NULL");

    $query_trx = mysqli_query($conn, "SELECT COUNT(*) AS `total_trx` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `deleted_at` IS NULL");

    $query_riwayat = mysqli_query($conn, "SELECT * FROM `keuangan` 
        WHERE `id_toko` = $idToko 
        AND `deleted_at` IS NULL 
        ORDER BY `tanggal` ASC, `id_keuangan` ASC");
} else {
    $query_masuk = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'masuk' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    $query_keluar = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tipe` = 'keluar' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    $query_trx = mysqli_query($conn, "SELECT COUNT(*) AS `total_trx` FROM `keuangan` 
        WHERE `id_toko` = $idToko AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");

    $query_riwayat = mysqli_query($conn, "SELECT * FROM `keuangan` 
        WHERE `id_toko` = $idToko 
        AND `tanggal` = '$filter_tanggal'
        AND `deleted_at` IS NULL 
        ORDER BY `id_keuangan` ASC");
}

$data_masuk = mysqli_fetch_assoc($query_masuk);
$pemasukan = (float)($data_masuk['total'] ?? 0);

$data_keluar = mysqli_fetch_assoc($query_keluar);
$pengeluaran = (float)($data_keluar['total'] ?? 0);

$data_trx = mysqli_fetch_assoc($query_trx);
$total_transaksi = (int)($data_trx['total_trx'] ?? 0);

// Format tampilan periode
if ($filter_tanggal === 'semua') {
    $periodeText = 'Semua Periode';
} else {
    $periodeText = date('d F Y', strtotime($filter_tanggal));
    // Terjemahkan nama bulan ke Indonesia secara sederhana
    $bln_en = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $bln_id = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periodeText = str_replace($bln_en, $bln_id, $periodeText);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - <?= htmlspecialchars($namaToko) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #ffffff;
            color: #1e293b;
            margin: 0;
            padding: 40px;
            font-size: 13px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .report-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .toko-logo {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .toko-logo-placeholder {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            background: #e8f5e9;
            color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border: 2px solid #c8e6c9;
        }

        .toko-info h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .toko-info p {
            margin: 0;
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }

        .report-title {
            text-align: right;
        }

        .report-title h1 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 800;
            color: #16a34a;
            letter-spacing: -0.5px;
        }

        .report-title p {
            margin: 0;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 25px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 20px;
        }

        .meta-item {
            font-size: 12.5px;
        }

        .meta-item strong {
            color: #0f172a;
        }

        .meta-item span {
            color: #64748b;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 30px;
        }

        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        }

        .summary-card i {
            font-size: 18px;
            margin-bottom: 6px;
            display: block;
        }

        .summary-label {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .summary-value {
            font-size: 15px;
            font-weight: 750;
            color: #0f172a;
            margin-top: 4px;
        }

        .table-container {
            margin-bottom: 35px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .report-table th {
            background: #f8fafc;
            color: #475569;
            padding: 10px 12px;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e1;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .report-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 12px;
            vertical-align: middle;
        }

        .type-badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 6px;
            border-radius: 4px;
            display: inline-block;
        }

        .badge-masuk {
            background: #e6f4ea;
            color: #137333;
        }

        .badge-keluar {
            background: #fce8e6;
            color: #c5221f;
        }

        .text-masuk {
            color: #16a34a;
            font-weight: 600;
        }

        .text-keluar {
            color: #dc2626;
            font-weight: 600;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            color: #0f172a;
            font-size: 13px;
        }

        .signature-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-box p {
            margin: 0;
        }

        .signature-space {
            height: 60px;
        }

        .btn-print-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #16a34a;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            z-index: 9999;
        }

        .btn-print-floating:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.4);
        }

        @media print {
            body {
                padding: 10px;
            }
            .btn-print-floating {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <button class="btn-print-floating" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Dokumen
    </button>

    <div class="report-header">
        <div class="report-header-left">
            <?php 
            $fotoPath = !empty($fotoToko) ? tokoFotoUrl($fotoToko, '../../../../') : '';
            if ($fotoPath): 
            ?>
                <img src="<?= $fotoPath ?>" alt="Logo Toko" class="toko-logo">
            <?php else: ?>
                <div class="toko-logo-placeholder">
                    <i class="fa-solid fa-store"></i>
                </div>
            <?php endif; ?>
            <div class="toko-info">
                <h2><?= htmlspecialchars($namaToko) ?></h2>
                <p>Unit Usaha E-Kantin SMKN 1</p>
                <p>Pemilik/Kasir Utama: <?= htmlspecialchars($penjualNama) ?></p>
            </div>
        </div>
        <div class="report-title">
            <h1>LAPORAN KEUANGAN</h1>
            <p>Periode: <?= htmlspecialchars($periodeText) ?></p>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <span>Tanggal Cetak:</span> <strong style="float: right;"><?= date('d-m-Y H:i') ?> WIB</strong>
        </div>
        <div class="meta-item">
            <span>Petugas Cetak:</span> <strong style="float: right;"><?= htmlspecialchars($penjualNama) ?> (Owner)</strong>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <i class="fa-solid fa-wallet" style="color: #22c55e;"></i>
            <div class="summary-label">Saldo Berjalan</div>
            <div class="summary-value">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i>
            <div class="summary-label">Pemasukan Periode</div>
            <div class="summary-value">Rp <?= number_format($pemasukan, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i>
            <div class="summary-label">Pengeluaran Periode</div>
            <div class="summary-value">Rp <?= number_format($pengeluaran, 0, ',', '.') ?></div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-receipt" style="color: #3498db;"></i>
            <div class="summary-label">Jumlah Aktivitas</div>
            <div class="summary-value"><?= $total_transaksi ?> Log</div>
        </div>
    </div>

    <div class="table-container">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 100px;">Tanggal</th>
                    <th style="width: 90px;">Tipe</th>
                    <th>Keterangan / Catatan</th>
                    <th style="width: 150px; text-align: right;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query_riwayat) === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">
                            Tidak ada aktivitas transaksi pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $netIncome = 0;
                    while ($row = mysqli_fetch_assoc($query_riwayat)): 
                        $isPemasukan = $row['tipe'] === 'masuk';
                        $badgeClass = $isPemasukan ? 'badge-masuk' : 'badge-keluar';
                        $badgeText  = $isPemasukan ? 'Pemasukan' : 'Pengeluaran';
                        $valClass   = $isPemasukan ? 'text-masuk' : 'text-keluar';
                        $tanda      = $isPemasukan ? '+' : '-';
                        
                        if ($isPemasukan) {
                            $netIncome += $row['jumlah'];
                        } else {
                            $netIncome -= $row['jumlah'];
                        }
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="type-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td class="<?= $valClass ?>" style="text-align: right;">
                                <?= $tanda ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right; padding-right: 15px;">NET SURPLUS / DEFISIT PERIODE:</td>
                        <td style="text-align: right;" class="<?= $netIncome >= 0 ? 'text-masuk' : 'text-keluar' ?>">
                            <?= $netIncome >= 0 ? '+' : '-' ?> Rp <?= number_format(abs($netIncome), 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>SMKN 1, <?= date('d M Y') ?></p>
            <p style="font-weight: 600;">Mengetahui, Owner Kantin</p>
            <div class="signature-space"></div>
            <p style="font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($penjualNama) ?></p>
        </div>
    </div>

    <script>
        // Trigger browser print dialog automatically
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
