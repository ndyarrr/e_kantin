<?php
// views/penjual/owner/sections/keuangan.php

// Pastikan session id_toko tersedia
if (!isset($idToko)) {
    die("Akses ditolak: ID Toko tidak ditemukan.");
}

// =========================================================================
// FILTER TANGGAL REAL-TIME
// =========================================================================
$filter_tanggal = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// =========================================================================
// LOGIC BACKEND HITUNG KARTU RINGKASAN (REAL-TIME DATABASE)
// =========================================================================

// 1. Hitung Saldo Saat Ini (Total Semua Masuk dikurangi Total Semua Keluar dari data aktif)
$query_saldo = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN `tipe` = 'masuk' THEN `jumlah` ELSE 0 END) - 
    SUM(CASE WHEN `tipe` = 'keluar' THEN `jumlah` ELSE 0 END) AS `saldo_sekarang`
    FROM `keuangan` WHERE `id_toko` = $idToko AND `deleted_at` IS NULL");
$data_saldo = mysqli_fetch_assoc($query_saldo);
$saldo_sekarang = (float)($data_saldo['saldo_sekarang'] ?? 0);

// 2. Total Pemasukan Hari Ini / Tanggal Terpilih
$query_masuk_hari_ini = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
    WHERE `id_toko` = $idToko AND `tipe` = 'masuk' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");
$data_masuk = mysqli_fetch_assoc($query_masuk_hari_ini);
$pemasukan_hari_ini = (float)($data_masuk['total'] ?? 0);

// 3. Total Pengeluaran Hari Ini / Tanggal Terpilih
$query_keluar_hari_ini = mysqli_query($conn, "SELECT SUM(`jumlah`) AS `total` FROM `keuangan` 
    WHERE `id_toko` = $idToko AND `tipe` = 'keluar' AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");
$data_keluar = mysqli_fetch_assoc($query_keluar_hari_ini);
$pengeluaran_hari_ini = (float)($data_keluar['total'] ?? 0);

// 4. Hitung Total Transaksi Hari Ini / Tanggal Terpilih
$query_transaksi = mysqli_query($conn, "SELECT COUNT(*) AS `total_trx` FROM `keuangan` 
    WHERE `id_toko` = $idToko AND `tanggal` = '$filter_tanggal' AND `deleted_at` IS NULL");
$data_trx = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = (int)($data_trx['total_trx'] ?? 0);


// =========================================================================
// QUERY AMBIL DATA RIWAYAT TABEL KEUANGAN (MENYARING SOFT DELETE)
// =========================================================================
$query_riwayat = mysqli_query($conn, "SELECT * FROM `keuangan` 
    WHERE `id_toko` = $idToko 
    AND `deleted_at` IS NULL 
    ORDER BY `tanggal` DESC, `id_keuangan` DESC");
?>

<div class="kantin-container">

    <div class="kas-grid-cards">
        <div class="kas-card">
            <i class="fa-solid fa-wallet" style="color: #22c55e;"></i>
            <div class="kas-label">Saldo Saat Ini</div>
            <div class="kas-value">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i>
            <div class="kas-label">Pemasukan Tanggal Terpilih</div>
            <div class="kas-value">Rp <?= number_format($pemasukan_hari_ini, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i>
            <div class="kas-label">Pengeluaran Tanggal Terpilih</div>
            <div class="kas-value">Rp <?= number_format($pengeluaran_hari_ini, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-receipt" style="color: #3498db;"></i>
            <div class="kas-label">Jumlah Transaksi Tanggal</div>
            <div class="kas-value"><?= $total_transaksi ?> Catatan</div>
        </div>
    </div>

    <div class="kas-filter-bar">
        <div class="kas-filter-left">
            <i class="fa-solid fa-filter"></i>
            <span>Pilih Tanggal Data:</span>
            <input type="date" class="kas-input-date" id="filterKasTanggal" value="<?= $filter_tanggal ?>" onchange="pindahTanggal(this.value)">
        </div>
        <div class="kas-btn-group">
            <button class="kas-btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
            <button class="kas-btn-add" style="background-color: #3498db;" onclick="alert('Buka modal tambah pemasukan/pengeluaran manual!')">
                <i class="fa-solid fa-circle-plus"></i> Tambah Log Keuangan
            </button>
        </div>
    </div>

    <div class="kas-table-container">
        <table class="kas-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No.</th>
                    <th>Tanggal Log</th>
                    <th>Jenis</th>
                    <th>Keterangan / Catatan</th>
                    <th>Nominal</th>
                    <th style="text-align: center; width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query_riwayat) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 500;">Belum ada catatan keuangan yang tersimpan di database toko Anda.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    while ($row = mysqli_fetch_assoc($query_riwayat)): 
                        $isPemasukan = $row['tipe'] === 'masuk';
                        $classWarna  = $isPemasukan ? 'text-pemasukan' : 'text-pengeluaran';
                        $prefixTanda = $isPemasukan ? '+ Rp ' : '- Rp ';
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?>.</td>
                            <td>
                                <strong><?= date('d M Y', strtotime($row['tanggal'])) ?></strong>
                                <br><small style="color:#94a3b8; font-size:11px;">Input: <?= date('d/m/y H:i', strtotime($row['dibuat_pada'])) ?></small>
                            </td>
                            <td>
                                <span class="<?= $classWarna ?>" style="text-transform: uppercase; font-size: 12px; background: <?= $isPemasukan ? '#e6f4ea':'#fce8e6' ?>; padding: 4px 8px; border-radius:4px;">
                                    <?= $isPemasukan ? '🟢 Pemasukan' : '🔴 Pengeluaran' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td class="<?= $classWarna ?>" style="font-size: 15px;">
                                <?= $prefixTanda . number_format($row['jumlah'], 0, ',', '.') ?>
                            </td>
                            <td style="text-align: center;">
                                <form action="index.php?section=keuangan" method="POST" onsubmit="return confirm('Yakin ingin membuang data keuangan ini ke sampah?');" style="display:inline;">
                                    <input type="hidden" name="_current_section" value="keuangan">
                                    <input type="hidden" name="action" value="soft_delete_keuangan">
                                    <input type="hidden" name="id_keuangan" value="<?= $row['id_keuangan'] ?>">
                                    <button type="submit" class="btn-delete-kas">
                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function pindahTanggal(val) {
    window.location.href = "index.php?section=keuangan&filter_date=" + val;
}
</script>