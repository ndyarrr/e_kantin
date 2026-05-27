<?php
// Mockup data atau data dinamis dari database (nantinya bisa dihubungkan ke query SQL)
$saldo_sekarang = 5000000;
$pemasukan_hari_ini = 1000000;
$pengeluaran_hari_ini = 500000;
$total_transaksi = 48;

$riwayat_kas = [
    ['tanggal' => '20/04/2026, 10:14', 'jenis' => 'Pemasukan', 'deskripsi' => 'Penjualn Nasi Pecel', 'nominal' => 6000, 'saldo_akhir' => 5006000],
    ['tanggal' => '21/04/2026, 08:19', 'jenis' => 'Pengeluaran', 'deskripsi' => 'Pembelian Minuman Grosir', 'nominal' => -35000, 'saldo_akhir' => 4971000],
    ['tanggal' => '23/04/2026, 09:20', 'jenis' => 'Pemasukan', 'deskripsi' => 'Penjualan Ayam Geprek', 'nominal' => 6000, 'saldo_akhir' => 4977000],
];
?>

<style>
.kas-grid-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.kas-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.kas-card i {
    font-size: 28px;
    color: #1e293b;
    margin-bottom: 12px;
}
.kas-card .kas-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}
.kas-card .kas-value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin-top: 4px;
}

.kas-filter-bar {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
.kas-filter-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
}
.kas-select, .kas-input-date {
    padding: 6px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    outline: none;
}
.kas-btn-group {
    display: flex;
    gap: 8px;
}
.kas-btn-print {
    background: #fff;
    border: 1px solid #cbd5e1;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.kas-btn-add {
    background: var(--green, #22c55e);
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.kas-table-container {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.kas-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 14px;
}
.kas-table th {
    background: #cbd5e1;
    color: #334155;
    padding: 12px 16px;
    font-weight: 600;
}
.kas-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
.text-pemasukan {
    color: #16a34a !important;
    font-weight: 600;
}
.text-pengeluaran {
    color: #dc2626 !important;
    font-weight: 600;
}

.kas-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 12px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}
.kas-page-btn {
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    color: #475569;
}
.kas-page-btn.active {
    background: var(--green, #22c55e);
    color: #fff;
    border-color: var(--green, #22c55e);
    font-weight: bold;
}
</style>

<div class="kas-grid-cards">
    <div class="kas-card">
        <i class="fa-solid fa-wallet"></i>
        <div class="kas-label">Saldo saat ini</div>
        <div class="kas-value">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
    </div>
    <div class="kas-card">
        <i class="fa-solid fa-chart-line"></i>
        <div class="kas-label">Total pemasukan hari ini</div>
        <div class="kas-value">Rp <?= number_format($pemasukan_hari_ini, 0, ',', '.') ?></div>
    </div>
    <div class="kas-card">
        <i class="fa-solid fa-chart-gantt"></i>
        <div class="kas-label">Total pengeluaran hari ini</div>
        <div class="kas-value">Rp <?= number_format($pengeluaran_hari_ini, 0, ',', '.') ?></div>
    </div>
    <div class="kas-card">
        <i class="fa-solid fa-receipt"></i>
        <div class="kas-label">Total transaksi harian</div>
        <div class="kas-value"><?= $total_transaksi ?> Transaksi</div>
    </div>
</div>

<div class="kas-filter-bar">
    <div class="kas-filter-left">
        <span>Periode:</span>
        <select class="kas-select">
            <option>Bulan ini</option>
            <option>Minggu ini</option>
            <option>Hari ini</option>
        </select>
        <span>—</span>
        <input type="date" class="kas-input-date" value="2026-05-20">
    </div>
    <div class="kas-btn-group">
        <button class="kas-btn-print">
            <i class="fa-solid fa-print"></i> Cetak laporan
        </button>
        <button class="kas-btn-add">
            Tambah pemasukan manual +
        </button>
    </div>
</div>

<div class="kas-table-container">
    <table class="kas-table">
        <thead>
            <tr>
                <th style="width: 50px;">No.</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Deskripsi</th>
                <th>Nominal</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($riwayat_kas as $item): ?>
                <?php 
                    $isPemasukan = $item['jenis'] === 'Pemasukan';
                    $classWarna  = $isPemasukan ? 'text-pemasukan' : 'text-pengeluaran';
                    $prefixTanda = $isPemasukan ? '+Rp ' : '-Rp ';
                ?>
                <tr>
                    <td><?= $no++ ?>.</td>
                    <td><?= htmlspecialchars($item['tanggal']) ?></td>
                    <td class="<?= $classWarna ?>"><?= htmlspecialchars($item['jenis']) ?></td>
                    <td><?= htmlspecialchars($item['deskripsi']) ?></td>
                    <td class="<?= $classWarna ?>"><?= $prefixTanda . number_format(abs($item['nominal']), 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($item['saldo_akhir'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="kas-pagination">
        <button class="kas-page-btn"><i class="fa-solid fa-angles-left"></i></button>
        <button class="kas-page-btn"><i class="fa-solid fa-angle-left"></i></button>
        <button class="kas-page-btn active">1</button>
        <button class="kas-page-btn">2</button>
        <button class="kas-page-btn"><i class="fa-solid fa-angle-right"></i></button>
        <button class="kas-page-btn"><i class="fa-solid fa-angles-right"></i></button>
    </div>
</div>