<?php // sections/dashboard.php ?>

<style>
    .status-kantin-banner {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
        gap: 16px;
    }
    
    .status-kantin-btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-weight: 800;
        font-size: 13px;
        cursor: pointer;
        color: #ffffff;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    @media (max-width: 768px) {
        .status-kantin-banner {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            padding: 16px;
        }
        .status-kantin-banner > div:first-child {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
            gap: 10px !important;
        }
        .status-kantin-btn {
            width: 100%;
            justify-content: center;
            padding: 12px;
        }
    }
</style>

<!-- STATUS BANNER TOKO/KANTIN -->
<div class="status-kantin-banner" id="bannerStatusKantin">
    <div style="display: flex; align-items: center; gap: 12px;" id="statusBannerFlexWrapper">
        <div id="statusIndikatorDot" style="width: 12px; height: 12px; border-radius: 50%; background: <?= $statusTokoAktif === 'buka' ? '#22c55e' : '#dc2626' ?>; box-shadow: 0 0 8px <?= $statusTokoAktif === 'buka' ? 'rgba(34,197,94,0.4)' : 'rgba(220,38,38,0.4)' ?>; flex-shrink: 0;"></div>
        <div>
            <div style="font-size: 13.5px; font-weight: 800; color: #1e293b; line-height: 1.2;">
                Status Kantin Saat Ini: <span id="statusTeksLabel" style="text-transform: uppercase; color: <?= $statusTokoAktif === 'buka' ? '#16a34a' : '#dc2626' ?>;"><?= $statusTokoAktif === 'buka' ? 'Buka' : 'Tutup' ?></span>
            </div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Jika status tutup, pembeli tidak dapat memesan makanan dari kantin ini.
            </div>
        </div>
    </div>
    <div>
        <button id="btnToggleStatusKantin" class="status-kantin-btn" onclick="toggleStatusKantin()"
                data-status="<?= htmlspecialchars($statusTokoAktif) ?>"
                style="background: <?= $statusTokoAktif === 'buka' ? '#dc2626' : '#22c55e' ?>; box-shadow: 0 2px 6px <?= $statusTokoAktif === 'buka' ? 'rgba(220,38,38,0.2)' : 'rgba(34,197,94,0.2)' ?>;">
            <i class="fa-solid fa-power-off"></i> <span><?= $statusTokoAktif === 'buka' ? 'Tutup Kantin' : 'Buka Kantin' ?></span>
        </button>
    </div>
</div>

<!-- STATS -->
<div class="stats-grid col4">
    <div class="stat-card">
        <div class="stat-label">Ringkasan Pendapatan Hari ini</div>
        <div class="stat-row">
            <div class="stat-value" style="font-size:20px">
                Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?>
            </div>
            <i class="fa-solid fa-hand-holding-dollar stat-icon"></i>
        </div>
        <div class="stat-desc <?= $trendPendapatan < 0 ? 'neg' : '' ?>">
            <i class="fa-solid fa-arrow-<?= $trendPendapatan >= 0 ? 'trend-up' : 'trend-down' ?>"></i>
            <?= ($trendPendapatan >= 0 ? '+' : '') . $trendPendapatan ?>% dari kemarin
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Pesanan Selesai Hari ini</div>
        <div class="stat-row">
            <div class="stat-value"><?= $pesananSelesai ?></div>
            <i class="fa-solid fa-cart-shopping stat-icon"></i>
        </div>
        <div class="stat-desc <?= $trendPesanan < 0 ? 'neg' : '' ?>">
            <i class="fa-solid fa-arrow-<?= $trendPesanan >= 0 ? 'trend-up' : 'trend-down' ?>"></i>
            <?= ($trendPesanan >= 0 ? '+' : '') . $trendPesanan ?>% dari kemarin
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Item Terlaris Harian</div>
        <div class="stat-row">
            <?php if ($itemTerlaris): ?>
                <div>
                    <div class="stat-value" style="font-size:15px;line-height:1.3">
                        <?= htmlspecialchars($itemTerlaris['nama_menu']) ?>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
                        <?= $itemTerlaris['total_jual'] ?> Porsi Terjual
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-value" style="font-size:14px;color:var(--text-muted)">Belum ada</div>
            <?php endif; ?>
            <i class="fa-solid fa-star stat-icon"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Saldo Buku Kas (Dompet)</div>
        <div class="stat-row">
            <div class="stat-value" style="font-size:18px">
                Rp <?= number_format($saldoKas, 0, ',', '.') ?>
            </div>
            <i class="fa-solid fa-wallet stat-icon"></i>
        </div>
    </div>
</div>

<!-- CHARTS -->
<div class="chart-grid-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
    <span style="font-size:13px;color:var(--text-muted);font-weight:500;">
        <i class="fa-solid fa-chart-line" style="color:var(--green);margin-right:5px;"></i>
        Ringkasan Visual Minggu Ini
    </span>
    <button id="btnCetakCSV" onclick="exportDashboardCSV()"
        title="Unduh laporan CSV: tren penjualan & distribusi produk"
        style="display:inline-flex;align-items:center;gap:8px;
               padding:8px 18px;border-radius:10px;
               background:linear-gradient(135deg,#6abf65,#4a9e45);
               color:#fff;font-size:13px;font-weight:700;
               border:none;cursor:pointer;box-shadow:0 2px 8px rgba(90,171,85,.3);
               transition:all .2s ease;">
        <i class="fa-solid fa-file-csv" style="font-size:15px;"></i>
        Cetak
    </button>
</div>

<div class="chart-grid">
    <div class="chart-card">
        <h3>Tren Penjualan Minggu Ini</h3>
        <div class="chart-wrap">
            <canvas id="lineChartPenjual"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3>Distribusi Pesanan Produk</h3>
        <div class="chart-wrap" style="height:150px">
            <canvas id="donutChartPenjual"></canvas>
        </div>
        <div class="legend" id="legendPenjual"></div>
    </div>
</div>

<!-- TABEL PESANAN TERBARU -->
<div class="table-card">
    <div class="table-card-header">
        <h2>Pesanan Terbaru</h2>
        <button onclick="switchSection('inbox')"
            style="display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1.5px solid var(--green);background:transparent;color:var(--green);font-size:12px;font-weight:700;cursor:pointer">
            <i class="fa-solid fa-receipt"></i> Lihat Pesanan
        </button>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pembeli</th>
                    <th class="col-hide">Total</th>
                    <th>Status</th>
                    <th class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pesananTerbaru)): ?>
                    <tr class="empty-row">
                        <td colspan="5">
                            <i class="fa-solid fa-inbox" style="color:var(--green-muted);font-size:22px;display:block;margin-bottom:8px"></i>
                            Belum ada pesanan
                        </td>
                    </tr>
                <?php else: foreach ($pesananTerbaru as $ps):
                    $statusClass = match($ps['status']) {
                        'selesai'    => 'badge-selesai',
                        'menunggu'   => 'badge-proses',
                        'dibatalkan' => 'badge-batal',
                        default      => 'badge-proses',
                    };
                    $statusLabel = match($ps['status']) {
                        'selesai'    => 'Selesai',
                        'menunggu'   => 'Proses',
                        'dibatalkan' => 'Batal',
                        default      => ucfirst($ps['status']),
                    };
                    $statusIcon = match($ps['status']) {
                        'selesai'    => 'fa-circle-check',
                        'dibatalkan' => 'fa-circle-xmark',
                        default      => 'fa-clock',
                    };
                ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-muted)">
                        <?= date('H:i', strtotime($ps['waktu_pesan'])) ?>
                    </td>
                    <td style="font-weight:600"><?= htmlspecialchars($ps['nama_pembeli']) ?></td>
                    <td class="col-hide" style="font-weight:700;color:var(--green-dark)">
                        Rp <?= number_format($ps['total_harga'], 0, ',', '.') ?>
                    </td>
                    <td>
                        <span class="badge <?= $statusClass ?>">
                            <i class="fa-solid <?= $statusIcon ?>"></i>
                            <?= $statusLabel ?>
                        </span>
                    </td>
                    <td class="center">
                        <button class="btn-aksi" title="Pesanan" onclick="switchSection('inbox')">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    const labels    = <?= json_encode($grafikLabels) ?>;
    const values    = <?= json_encode($grafikValues) ?>;
    const distribusi = <?= json_encode($distribusi) ?>;

    /* Line chart */
    const ctxL = document.getElementById('lineChartPenjual').getContext('2d');
    const grad = ctxL.createLinearGradient(0, 0, 0, 180);
    grad.addColorStop(0, 'rgba(90,171,85,.35)');
    grad.addColorStop(1, 'rgba(90,171,85,0)');
    new Chart(ctxL, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: '#5aab55',
                borderWidth: 2.5,
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#5aab55',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    grid: { color: '#e5e7eb' },
                    ticks: {
                        font: { size: 10 },
                        callback: v => v >= 1000 ? 'Rp' + (v/1000).toFixed(0) + 'k' : v
                    },
                    beginAtZero: true,
                    border: { display: false }
                }
            }
        }
    });

    /* Donut chart */
    const colors = ['#3b82f6','#06b6d4','#f97316','#5aab55','#a78bfa'];
    const noData = distribusi.length === 0;
    const distLabels = noData ? ['Belum ada data'] : distribusi.map(d => d.kategori);
    const distValues = noData ? [1] : distribusi.map(d => parseInt(d.total));

    new Chart(document.getElementById('donutChartPenjual'), {
        type: 'doughnut',
        data: {
            labels: distLabels,
            datasets: [{
                data: distValues,
                backgroundColor: noData ? ['#e5e7eb'] : colors.slice(0, distLabels.length),
                borderWidth: 3,
                borderColor: '#f8f9fa'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '55%',
            plugins: { legend: { display: false }, tooltip: { enabled: !noData } }
        }
    });

    if (!noData) {
        const legend = document.getElementById('legendPenjual');
        distLabels.forEach((label, i) => {
            legend.innerHTML += `<div class="legend-item"><span class="legend-dot" style="background:${colors[i]}"></span>${label}</div>`;
        });
    }
})();

/* ── Fungsi ekspor CSV (Mengunduh 3 File Sekaligus) ─────────────────────────────── */
function exportDashboardCSV() {
    const btn = document.getElementById('btnCetakCSV');
    const originalHTML = btn.innerHTML;

    // Animasi loading pada tombol
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:15px;"></i> Menyiapkan...';
    btn.style.opacity = '0.8';

    const baseUrl = '<?= $base_path ?>/views/penjual/owner/sections/export_csv.php';
    const reportTypes = ['dashboard', 'tren', 'distribusi'];

    // Unduh 3 file dengan jeda waktu singkat untuk mencegah pemblokiran unduhan oleh browser
    reportTypes.forEach((type, index) => {
        setTimeout(() => {
            const link = document.createElement('a');
            link.href = baseUrl + '?type=' + type;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, index * 400);
    });

    // Kembalikan tombol ke semula setelah proses unduh dimulai
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        btn.style.opacity = '1';
    }, 1500);
}

// Dynamic routing paths resolved directly from the backend's base path
const prosesKantinUrl = '<?= $base_path ?>/views/penjual/actions/proses_kantin.php';

/* ── Fungsi ganti status kantin via AJAX dengan Konfirmasi ─────────────────────────────── */
function toggleStatusKantin() {
    const btn = document.getElementById('btnToggleStatusKantin');
    const currentStatus = btn.getAttribute('data-status');
    const nextStatus = currentStatus === 'buka' ? 'tutup' : 'buka';
    
    // Tampilkan modal konfirmasi kustom
    let existingModal = document.getElementById('statusConfirmModal');
    if (existingModal) existingModal.remove();
    
    const modal = document.createElement('div');
    modal.id = 'statusConfirmModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    
    const card = document.createElement('div');
    card.style.cssText = `
        background: #ffffff;
        padding: 30px 24px;
        border-radius: 24px;
        width: 90%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    
    const isClosing = currentStatus === 'buka';
    const accentColor = isClosing ? '#ef4444' : '#22c55e';
    const bgAccentColor = isClosing ? '#fee2e2' : '#dcfce7';
    const titleText = isClosing ? 'Tutup Kantin?' : 'Buka Kantin?';
    const descText = isClosing 
        ? 'Apakah Anda yakin ingin menutup kantin? Pembeli tidak akan dapat memesan menu dari kantin ini sampai dibuka kembali.'
        : 'Apakah Anda yakin ingin membuka kantin? Pembeli akan dapat memesan menu kembali.';
    const confirmBtnText = isClosing ? 'Tutup Kantin' : 'Buka Kantin';
    const confirmBtnShadow = isClosing ? 'rgba(239, 68, 68, 0.25)' : 'rgba(34, 197, 94, 0.25)';
    
    card.innerHTML = `
        <div style="width: 56px; height: 56px; background: ${bgAccentColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="fa-solid fa-power-off" style="font-size: 24px; color: ${accentColor};"></i>
        </div>
        <h3 style="margin: 0 0 8px; font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 750; color: #1e293b;">${titleText}</h3>
        <p style="margin: 0 0 24px; font-family: 'Poppins', sans-serif; font-size: 13.5px; color: #64748b; line-height: 1.5;">${descText}</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button id="statusCancelBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Batal</button>
            <button id="statusConfirmBtn" style="flex: 1; padding: 11px; border-radius: 12px; border: none; background: ${accentColor}; color: #ffffff; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px ${confirmBtnShadow};">${confirmBtnText}</button>
        </div>
    `;
    
    modal.appendChild(card);
    document.body.appendChild(modal);
    
    setTimeout(() => {
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }, 10);
    
    function closeModal() {
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => modal.remove(), 300);
    }
    
    document.getElementById('statusCancelBtn').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    
    document.getElementById('statusConfirmBtn').addEventListener('click', () => {
        closeModal();
        
        // Jalankan AJAX toggle status
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengubah...';
        
        const formData = new FormData();
        formData.append('action', 'toggle_status_ajax');
        formData.append('status', nextStatus);
        
        fetch(prosesKantinUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                // Update button data-status and text
                btn.setAttribute('data-status', data.status);
                btn.style.background = data.status === 'buka' ? '#dc2626' : '#22c55e';
                btn.style.boxShadow = `0 2px 6px ${data.status === 'buka' ? 'rgba(220,38,38,0.2)' : 'rgba(34,197,94,0.2)'}`;
                btn.innerHTML = `<i class="fa-solid fa-power-off"></i> <span>${data.status === 'buka' ? 'Tutup Kantin' : 'Buka Kantin'}</span>`;
                
                // Update label and dot indicator
                const dot = document.getElementById('statusIndikatorDot');
                const label = document.getElementById('statusTeksLabel');
                
                dot.style.background = data.status === 'buka' ? '#22c55e' : '#dc2626';
                dot.style.boxShadow = `0 0 8px ${data.status === 'buka' ? 'rgba(34,197,94,0.4)' : 'rgba(220,38,38,0.4)'}`;
                
                label.textContent = data.status === 'buka' ? 'Buka' : 'Tutup';
                label.style.color = data.status === 'buka' ? '#16a34a' : '#dc2626';
            } else {
                alert('Gagal mengubah status kantin: ' + (data.message || 'Error tidak diketahui'));
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error(err);
            alert('Koneksi gagal atau sesi habis!');
        });
    });
}
</script>