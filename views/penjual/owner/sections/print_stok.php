<?php
// views/penjual/owner/sections/print_stok.php

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

// Hitung statistik keseluruhan (sebelum filter tabel diterapkan)
$allMenusQuery = mysqli_query($conn, "SELECT * FROM menu WHERE id_toko = $idToko AND deleted_at IS NULL");
$allMenus = mysqli_fetch_all($allMenusQuery, MYSQLI_ASSOC);

$totalItems = count($allMenus);
$stokAman = 0;
$stokMenipis = 0;
$stokHabis = 0;

foreach ($allMenus as $m) {
    $stk = (int) $m['stok'];
    if ($stk == 0) {
        $stokHabis++;
    } elseif ($stk <= 10) {
        $stokMenipis++;
    } else {
        $stokAman++;
    }
}

// Tangkap filter dari URL/GET request
$searchStok = trim($_GET['search_stok'] ?? '');
$kategoriStok = $_GET['kategori_stok'] ?? 'semua';
$statusStok = $_GET['status_stok'] ?? 'semua';

// Query filter untuk tabel
$sqlFilter = "SELECT * FROM menu WHERE id_toko = $idToko AND deleted_at IS NULL";

if ($searchStok !== '') {
    $searchEscaped = mysqli_real_escape_string($conn, $searchStok);
    $sqlFilter .= " AND nama_menu LIKE '%$searchEscaped%'";
}

if ($kategoriStok !== 'semua') {
    $katEscaped = mysqli_real_escape_string($conn, $kategoriStok);
    $sqlFilter .= " AND kategori = '$katEscaped'";
}

if ($statusStok === 'aman') {
    $sqlFilter .= " AND stok > 10";
} elseif ($statusStok === 'menipis') {
    $sqlFilter .= " AND stok <= 10 AND stok > 0";
} elseif ($statusStok === 'habis') {
    $sqlFilter .= " AND stok = 0";
}

$sqlFilter .= " ORDER BY nama_menu ASC";
$menusTabel = mysqli_fetch_all(mysqli_query($conn, $sqlFilter), MYSQLI_ASSOC);

// Text deskripsi filter
$filterDescParts = [];
if ($searchStok !== '') $filterDescParts[] = "Pencarian: \"$searchStok\"";
if ($kategoriStok !== 'semua') $filterDescParts[] = "Kategori: " . ucfirst($kategoriStok);
if ($statusStok !== 'semua') $filterDescParts[] = "Status: Stok " . ucfirst($statusStok);
$filterText = empty($filterDescParts) ? 'Semua Menu' : implode(', ', $filterDescParts);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok - <?= htmlspecialchars($namaToko) ?></title>
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
            color: #5aab55;
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
            font-size: 16px;
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

        .badge-stock {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .badge-aman {
            background: #e6f4ea;
            color: #137333;
        }

        .badge-menipis {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-habis {
            background: #fee2e2;
            color: #dc2626;
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
            background: #5aab55;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 4px 12px rgba(90, 171, 85, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            z-index: 9999;
        }

        .btn-print-floating:hover {
            background: #4a9146;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(90, 171, 85, 0.4);
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
        <i class="fa-solid fa-print"></i> Cetak Laporan
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
                <p style="font-weight: 600;">Pemilik/Kasir Utama: <?= htmlspecialchars($penjualNama) ?></p>
            </div>
        </div>
        <div class="report-title">
            <h1>LAPORAN STOK MENU</h1>
            <p>Filter: <?= htmlspecialchars($filterText) ?></p>
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
            <i class="fa-solid fa-utensils" style="color: #3b82f6;"></i>
            <div class="summary-label">Total Produk</div>
            <div class="summary-value"><?= $totalItems ?> Menu</div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
            <div class="summary-label">Stok Aman (>10)</div>
            <div class="summary-value" style="color: #137333;"><?= $stokAman ?> Menu</div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
            <div class="summary-label">Stok Menipis (1-10)</div>
            <div class="summary-value" style="color: #d97706;"><?= $stokMenipis ?> Menu</div>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i>
            <div class="summary-label">Stok Habis (0)</div>
            <div class="summary-value" style="color: #dc2626;"><?= $stokHabis ?> Menu</div>
        </div>
    </div>

    <div class="table-container">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th style="width: 100px; text-align: center;">Jumlah Stok</th>
                    <th style="width: 120px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menusTabel)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">
                            Tidak ada produk menu yang sesuai dengan kriteria cetak.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($menusTabel as $row): 
                        $stk = (int) $row['stok'];
                        $badgeClass = 'badge-aman';
                        $statusText = 'Aman';
                        if ($stk == 0) {
                            $badgeClass = 'badge-habis';
                            $statusText = 'Habis';
                        } elseif ($stk <= 10) {
                            $badgeClass = 'badge-menipis';
                            $statusText = 'Menipis';
                        }
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_menu']) ?></strong></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($row['kategori']) ?></td>
                            <td>
                                <?php if (isset($row['is_fleksibel']) && $row['is_fleksibel'] == 1): ?>
                                    Harga Fleksibel
                                <?php else: ?>
                                    Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: 700;"><?= $stk ?></td>
                            <td style="text-align: center;">
                                <span class="badge-stock <?= $badgeClass ?>"><?= $statusText ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
