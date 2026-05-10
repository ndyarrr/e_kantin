<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Transaksi Harian</div>
        <div class="stat-row">
            <div class="stat-value"><?= number_format($totalTransaksi) ?></div>
            <i class="fa-solid fa-cart-shopping stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Pembeli Terdaftar</div>
        <div class="stat-row">
            <div class="stat-value"><?= number_format($totalPembeli) ?></div>
            <i class="fa-solid fa-users stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Toko Buka Hari Ini</div>
        <div class="stat-row">
            <div class="stat-value"><?= $tokoAktif ?><span class="sub"> / <?= $totalToko ?></span></div>
            <i class="fa-solid fa-store stat-icon"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Menu Tersedia</div>
        <div class="stat-row">
            <div class="stat-value"><?= number_format($totalMenu) ?></div>
            <i class="fa-solid fa-burger stat-icon"></i>
        </div>
    </div>
</div>

<div class="charts-row">
    <div class="card">
        <div class="card-title">Grafik Transaksi (7 Hari Terakhir)</div>
        <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Proporsi Pesanan per Toko</div>
        <div class="donut-wrap">
            <div class="donut-canvas-wrap"><canvas id="donutChart"></canvas></div>
            <div class="legend" id="legend"></div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h2>Kendala Pesanan</h2>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th class="center">Waktu</th>
                    <th>Pelapor</th>
                    <th class="col-hide">Kategori</th>
                    <th>Status</th>
                    <th class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kendala)): ?>
                    <tr class="empty-row">
                        <td colspan="5"><i class="fa-solid fa-circle-check"
                                style="color:var(--green);font-size:22px;display:block;margin-bottom:8px"></i>Tidak
                            ada kendala saat ini</td>
                    </tr>
                <?php else:
                    foreach ($kendala as $k): ?>
                        <tr>
                            <td class="center"><?= date('H.i', strtotime($k['waktu_pesan'])) ?></td>
                            <td><?= htmlspecialchars($k['pelapor']) ?></td>
                            <td class="col-hide">
                                <?= $k['status'] === 'dibatalkan' ? 'Pesanan Dibatalkan' : 'Menunggu Konfirmasi' ?>
                            </td>
                            <td>
                                <?php if ($k['status'] === 'dibatalkan'): ?>
                                    <span class="badge badge-batal"><i class="fa-solid fa-circle-xmark"></i>
                                        Dibatalkan</span>
                                <?php else: ?>
                                    <span class="badge badge-proses"><i class="fa-solid fa-clock"></i> Proses</span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <button class="btn-aksi reset" onclick="alert('Detail pesanan #<?= $k['id_pesanan'] ?>')"><i
                                        class="fa-solid fa-eye"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>