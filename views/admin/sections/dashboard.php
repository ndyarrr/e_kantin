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
        <?php if ($proporsiTotal == 0): ?>
            <div style="text-align:center;padding:40px 20px;color:var(--text-light);font-size:13px">
                <i class="fa-solid fa-chart-pie"
                    style="font-size:28px;display:block;margin-bottom:8px;color:var(--green-muted)"></i>
                Belum ada pesanan
            </div>
        <?php else: ?>
            <div class="donut-wrap">
                <div class="donut-canvas-wrap"><canvas id="donutChart"></canvas></div>
                <div class="legend" id="legend"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h2><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; margin-right: 8px;"></i> Report Kendala
        </h2>
        <!-- Filter pills -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
            <?php
            $filters = [
                'aktif' => ['label' => 'Aktif', 'color' => '#f59e0b'],
                'menunggu' => ['label' => 'Menunggu', 'color' => '#ef4444'],
                'proses' => ['label' => 'Proses', 'color' => '#3b82f6'],
                'selesai' => ['label' => 'Selesai', 'color' => '#22c55e'],
            ];
            foreach ($filters as $key => $f):
                $isActive = $filterKendala === $key;
                ?>
                <a href="?section=dashboard&filter_kendala=<?= $key ?>" style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;
        border:1.5px solid <?= $f['color'] ?>;
        background:<?= $isActive ? $f['color'] : 'transparent' ?>;
        color:<?= $isActive ? '#fff' : $f['color'] ?>">
                    <?= $f['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th class="center">Waktu</th>
                    <th>Pelapor</th>
                    <th>Judul Kendala</th>
                    <th>Deskripsi</th> <!-- ← tambah ini -->
                    <th>Status</th>
                    <th class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kendala)): ?>
                    <tr class="empty-row">
                        <td colspan="6">
                            <i class="fa-solid fa-circle-check"
                                style="color:var(--green);font-size:22px;display:block;margin-bottom:8px"></i>
                            Sistem aman! Tidak ada report kendala saat ini.
                        </td>
                    </tr>
                <?php else:
                    foreach ($kendala as $k):
                        $role = strtolower($k['user_role'] ?? '');
                        // Penyesuaian warna badge: penjual (biru), guru (ungu), murid (hijau)
                        $badgeBg = ($role === 'penjual') ? '#e0f2fe' : (($role === 'guru') ? '#e8f0fe' : '#e2f5e1');
                        $badgeColor = ($role === 'penjual') ? '#0369a1' : (($role === 'guru') ? '#1a56db' : '#2d7a2d');
                        ?>
                        <tr>
                            <td class="center" style="font-size: 11px; color: #6b7280;">
                                <?= date('d M, H:i', strtotime($k['dibuat_pada'])) ?>
                            </td>
                            <td>
                                <span style="font-weight: 600;">
                                    <?= htmlspecialchars($k['user_nama']) ?>
                                </span>
                                <span
                                    style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-size: 9px; padding: 1px 6px; border-radius: 10px; font-weight: 600; text-transform: uppercase; margin-left: 5px;">
                                    <?= $role ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text);">
                                    <?= htmlspecialchars($k['judul_kendala']) ?>
                                </strong>
                            </td>

                            
                            <td style="font-size:12px;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer"
                                onmouseenter="showTooltip(event, this.dataset.full)" onmouseleave="hideTooltip()"
                                data-full="<?= htmlspecialchars($k['deskripsi'] ?? '-') ?>">
                                <?= htmlspecialchars($k['deskripsi'] ?? '-') ?>
                            </td>

                            <td>
                                <?php if ($k['status'] === 'menunggu'): ?>
                                    <span class="badge badge-batal" style="background:#fee2e2;color:#ef4444;">
                                        <i class="fa-solid fa-clock"></i> Menunggu
                                    </span>
                                <?php elseif ($k['status'] === 'proses'): ?>
                                    <span class="badge badge-proses" style="background:#fef3c7;color:#d97706;">
                                        <i class="fa-solid fa-spinner fa-spin"></i> Proses
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#dcfce7;color:#16a34a;">
                                        <i class="fa-solid fa-circle-check"></i> Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="center" style="white-space:nowrap">
                                <!-- Lihat detail -->
                                <button class="btn-aksi reset" title="Lihat Detail"
                                    data-judul="<?= htmlspecialchars($k['judul_kendala'], ENT_QUOTES) ?>"
                                    data-deskripsi="<?= htmlspecialchars($k['deskripsi'], ENT_QUOTES) ?>"
                                    onclick="bukaDetailKendala(this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                <!-- Tandai Proses -->
                                <?php if ($k['status'] === 'menunggu'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="kendala_proses">
                                        <input type="hidden" name="id_laporan" value="<?= $k['id_laporan'] ?>">
                                        <input type="hidden" name="_section" value="dashboard">
                                        <!-- Tambah di setiap form kendala -->
                                        <input type="hidden" name="filter_kendala" value="<?= htmlspecialchars($filterKendala) ?>">
                                        <button type="submit" class="btn-aksi yellow" title="Tandai Proses">

                                            <i class="fa-solid fa-spinner"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Tandai Selesai -->
                                <?php if (in_array($k['status'], ['menunggu', 'proses'])): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="kendala_selesai">
                                        <input type="hidden" name="id_laporan" value="<?= $k['id_laporan'] ?>">
                                        <input type="hidden" name="_section" value="dashboard">
                                        <!-- Tambah di setiap form kendala -->
                                        <input type="hidden" name="filter_kendala" value="<?= htmlspecialchars($filterKendala) ?>">
                                        <button type="submit" class="btn-aksi toggle-on" title="Tandai Selesai">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Hapus -->
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus laporan ini?')">
                                    <input type="hidden" name="action" value="kendala_hapus">
                                    <input type="hidden" name="id_laporan" value="<?= $k['id_laporan'] ?>">
                                    <input type="hidden" name="_section" value="dashboard">
                                    <!-- Tambah di setiap form kendala -->
                                    <input type="hidden" name="filter_kendala" value="<?= htmlspecialchars($filterKendala) ?>">
                                    <button type="submit" class="btn-aksi danger" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
        <div id="logTooltip"
            style="display:none;position:fixed;background:#1f2937;color:#fff;padding:8px 12px;border-radius:8px;font-size:12px;max-width:300px;word-break:break-word;z-index:9999;pointer-events:none;box-shadow:0 4px 12px rgba(0,0,0,.3);">
        </div>
    </div>
</div>

<div id="modalDetailKendala"
    style="display:none;position:fixed;inset:0;z-index:100;align-items:center;justify-content:center">
    <div onclick="tutupDetailKendala()"
        style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px)"></div>
    <div
        style="position:relative;background:#fff;border-radius:16px;padding:24px;width:90%;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.15)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h2 style="font-size:15px;font-weight:700" id="modalKendalaJudul"></h2>
            <button onclick="tutupDetailKendala()"
                style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <p style="font-size:13px;color:#6b7280;line-height:1.6" id="modalKendalaDesc"></p>
    </div>
</div>
<script>
    function bukaDetailKendala(btn) {
        document.getElementById('modalKendalaJudul').textContent = btn.getAttribute('data-judul');
        document.getElementById('modalKendalaDesc').textContent = btn.getAttribute('data-deskripsi');
        document.getElementById('modalDetailKendala').style.display = 'flex';
    }
    function tutupDetailKendala() {
        document.getElementById('modalDetailKendala').style.display = 'none';
    }

    const tooltipEl = document.getElementById('logTooltip');
    function showTooltip(e, text) {
        if (!tooltipEl) return;
        tooltipEl.textContent = text;
        tooltipEl.style.display = 'block';
        tooltipEl.style.left = (e.clientX + 12) + 'px';
        tooltipEl.style.top = (e.clientY + 12) + 'px';
    }
    function hideTooltip() {
        if (tooltipEl) tooltipEl.style.display = 'none';
    }
</script>