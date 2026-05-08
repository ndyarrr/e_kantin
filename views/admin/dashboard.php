<?php
session_start();
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: ../../auth/login.php'); exit;
// }
require_once __DIR__ . '/../../config/database.php';

$totalTransaksi = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan) = CURDATE()"))['c'];
$totalPembeli = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT (SELECT COUNT(*) FROM murid) + (SELECT COUNT(*) FROM guru) as c"))['c'];
$penjualAktif = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE status = 'buka'"))['c'];
$totalToko = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko"))['c'];
$totalMenu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE tersedia = 1"))['c'];

$grafikLabels = [];
$grafikValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $grafikLabels[] = date('d/m', strtotime("-{$i} days"));
    $grafikValues[] = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan) = '$date'"))['c'];
}

$proporsiRaw = mysqli_fetch_all(mysqli_query($conn, "SELECT t.nama_toko, COUNT(p.id_pesanan) as total FROM toko t LEFT JOIN pesanan p ON p.id_toko = t.id_toko GROUP BY t.id_toko, t.nama_toko ORDER BY total DESC LIMIT 5"), MYSQLI_ASSOC);
$proporsiTotal = array_sum(array_column($proporsiRaw, 'total'));
$proporsiLabels = array_column($proporsiRaw, 'nama_toko');
$proporsiValues = $proporsiTotal > 0 ? array_map('intval', array_column($proporsiRaw, 'total')) : array_fill(0, count($proporsiRaw), 1);

$kendala = mysqli_fetch_all(mysqli_query($conn, "SELECT p.id_pesanan, p.waktu_pesan, p.status, COALESCE(m.nama, g.nama, 'Unknown') AS pelapor FROM pesanan p LEFT JOIN murid m ON m.nisn = p.nisn_pembeli LEFT JOIN guru g ON g.nuptk = p.nuptk_pembeli WHERE p.status IN ('dibatalkan','menunggu') ORDER BY p.waktu_pesan DESC LIMIT 10"), MYSQLI_ASSOC);

$adminNama = $_SESSION['user_nama'] ?? 'Super Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — E-Kantin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --green:        #79b775;
    --green-dark:   #5a9657;
    --green-light:  #e6ebe9;
    --green-pale:   #f0f7f0;
    --green-muted:  #b5d7b4;
    --white:        #ffffff;
    --bg:           #f4f7f6;
    --card-bg:      #f8f9fa;
    --border:       #e2e8e4;
    --text:         #1f2937;
    --text-muted:   #6b7280;
    --text-light:   #9ca3af;
    --red:          #ef4444;
    --radius:       12px;
    --shadow:       0 2px 12px rgba(0,0,0,.07);
    --shadow-lg:    0 8px 24px rgba(0,0,0,.11);
    --sidebar-w:    256px;
    --transition:   .28s cubic-bezier(.4,0,.2,1);
}

body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    height: 100vh;
    overflow: hidden;
}

#overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 30;
    backdrop-filter: blur(2px);
}
#overlay.show { display: block; }

/* ── SIDEBAR ── */
#sidebar {
    width: var(--sidebar-w);
    height: 100vh;
    background: var(--green-light);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: margin-left var(--transition), transform var(--transition);
    overflow-y: auto;
    position: relative;
    z-index: 40;
}

.sidebar-logo {
    padding: 24px 20px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border);
}
.sidebar-logo-inner { display: flex; align-items: center; gap: 10px; }
.logo-badge {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--white);
    border: 2px solid var(--green);
    display: flex; align-items: center; justify-content: center;
    font-size: 8px; font-weight: 800;
    color: var(--green-dark);
    text-align: center; line-height: 1.2;
    flex-shrink: 0;
}
.logo-text { font-size: 22px; font-weight: 800; color: var(--green-dark); letter-spacing: -.3px; }

.btn-close-sidebar {
    display: none;
    background: none; border: none;
    font-size: 18px; color: var(--text-muted);
    cursor: pointer; padding: 4px;
    border-radius: 6px;
    transition: background var(--transition);
}
.btn-close-sidebar:hover { background: var(--border); }

.sidebar-nav {
    padding: 16px 12px;
    display: flex; flex-direction: column;
    gap: 4px; flex: 1;
}
.nav-link {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    font-size: 14px; font-weight: 500;
    color: var(--text-muted); text-decoration: none;
    transition: background var(--transition), color var(--transition);
}
.nav-link:hover { background: rgba(0,0,0,.06); color: var(--text); }
.nav-link.active { background: var(--green); color: #fff; font-weight: 600; }
.nav-link i { width: 18px; text-align: center; font-size: 15px; }

.sidebar-bottom {
    padding: 12px 12px 24px;
    border-top: 1px solid var(--border);
    display: flex; flex-direction: column; gap: 4px;
}
.nav-link.logout { color: var(--red); }
.nav-link.logout:hover { background: #fee2e2; }

/* ── MAIN ── */
#main {
    flex: 1;
    display: flex; flex-direction: column;
    overflow-y: auto;
    min-width: 0;
    transition: margin-left var(--transition);
}
.content { padding: 20px 24px 32px; }

/* ── TOPBAR ── */
.topbar {
    background: var(--green);
    padding: 16px 24px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
    border-radius: var(--radius);
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.btn-hamburger {
    background: rgba(255,255,255,.2); border: none; color: #fff;
    width: 38px; height: 38px; border-radius: 8px; font-size: 16px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background var(--transition); flex-shrink: 0;
}
.btn-hamburger:hover { background: rgba(255,255,255,.35); }
.topbar-title h1 { font-size: 24px; font-weight: 800; color: #fff; line-height: 1.1; }
.topbar-title p  { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.btn-notif {
    position: relative;
    background: rgba(255,255,255,.2); border: none; color: #fff;
    width: 38px; height: 38px; border-radius: 8px; font-size: 16px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background var(--transition);
}
.btn-notif:hover { background: rgba(255,255,255,.35); }
.notif-dot {
    position: absolute; top: 7px; right: 7px;
    width: 8px; height: 8px;
    background: var(--red); border-radius: 50%;
    border: 2px solid var(--green);
}
.topbar-user {
    display: flex; align-items: center; gap: 10px;
    border-left: 1px solid rgba(255,255,255,.3);
    padding-left: 14px;
}
.avatar {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--green-dark); color: #fff;
    font-weight: 800; font-size: 17px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; border: 2px solid rgba(255,255,255,.3);
}
.user-name { font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2; }
.user-role { font-size: 11px; color: rgba(255,255,255,.7); }

/* ── STAT CARDS ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px; margin-bottom: 20px;
}
.stat-card {
    background: var(--white); border-radius: var(--radius);
    padding: 18px 20px; border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.stat-label { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 10px; }
.stat-row { display: flex; align-items: flex-end; justify-content: space-between; }
.stat-value { font-size: 32px; font-weight: 800; color: var(--text); line-height: 1; letter-spacing: -1px; }
.stat-value .sub { font-size: 18px; color: var(--text-light); font-weight: 500; }
.stat-icon { font-size: 26px; color: var(--green-muted); }

/* ── CHARTS ── */
.charts-row {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 16px; margin-bottom: 20px;
    min-width: 0;
}
.charts-row > * { min-width: 0; }

.card {
    background: var(--card-bg); border-radius: var(--radius);
    padding: 20px; border: 1px solid var(--border);
    box-shadow: var(--shadow); min-width: 0;
}
.card-title { font-size: 14px; font-weight: 700; color: var(--text); text-align: center; margin-bottom: 16px; }
.chart-wrap { position: relative; height: 180px; }

/* Donut: samping dulu, kalau ga muat wrap ke bawah */
.donut-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;        /* <-- ini kuncinya, legend otomatis turun kalau ga muat */
    gap: 16px;
    padding: 4px 0;
}
.donut-canvas-wrap {
    position: relative;
    width: 130px; height: 130px;
    flex-shrink: 0;
}
.legend { display: flex; flex-direction: column; gap: 8px; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text); }
.legend-dot { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }

/* ── TABLE ── */
.table-card {
    background: var(--white); border-radius: var(--radius);
    border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden;
}
.table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; min-width: 480px; }
thead th {
    padding: 14px 16px; font-size: 12px; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid var(--border); background: var(--white); white-space: nowrap;
}
thead th.center { text-align: center; }
tbody td { padding: 13px 16px; font-size: 13px; color: var(--text); border-bottom: 1px solid #f3f4f6; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--green-pale); }
tbody td.center { text-align: center; }
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px;
    font-size: 12px; font-weight: 600; white-space: nowrap;
}
.badge-proses  { background: #fef3c7; color: #92400e; }
.badge-batal   { background: #fee2e2; color: #991b1b; }
.badge-selesai { background: #dcfce7; color: #166534; }
.btn-aksi {
    background: none; border: none; color: var(--green-dark);
    font-size: 16px; cursor: pointer; padding: 4px 8px;
    border-radius: 6px; transition: background var(--transition);
}
.btn-aksi:hover { background: var(--green-pale); }
.empty-row td { text-align: center; padding: 40px; color: var(--text-light); font-size: 14px; }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    body { overflow: hidden; }
    #sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0; height: 100%;
        transform: translateX(-100%);
        z-index: 40; margin-left: 0 !important;
    }
    #sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
    .btn-close-sidebar { display: flex; }
    #main { width: 100%; }
    .content { padding: 14px 14px 28px; }
    .topbar { padding: 12px 14px; margin-bottom: 16px; }
    .topbar-title h1 { font-size: 18px; }
    .topbar-title p { display: none; }
    .user-name, .user-role { display: none; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px; }
    .stat-card { padding: 14px; }
    .stat-value { font-size: 26px; }
    .stat-icon { font-size: 20px; }
    .charts-row { grid-template-columns: 1fr; gap: 12px; margin-bottom: 14px; }
    .chart-wrap { height: 150px; }
    .donut-canvas-wrap { width: 110px; height: 110px; }
}

@media (max-width: 480px) {
    .col-hide { display: none; }
}
</style>
</head>
<body>

<div id="overlay" onclick="closeSidebar()"></div>

<aside id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-inner">
            <div class="logo-badge">ESEMKITA</div>
            <span class="logo-text">E-Kantin</span>
        </div>
        <button class="btn-close-sidebar" onclick="closeSidebar()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <a href="#"           class="nav-link active"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="admin.php"   class="nav-link"><i class="fa-solid fa-user"></i> Admin</a>
        <a href="kantin.php"  class="nav-link"><i class="fa-solid fa-store"></i> Kantin</a>
        <a href="penjual.php" class="nav-link"><i class="fa-solid fa-user-tag"></i> Penjual</a>
        <a href="pembeli.php" class="nav-link"><i class="fa-solid fa-users"></i> Pembeli</a>
    </nav>
    <div class="sidebar-bottom">
        <a href="#"                     class="nav-link"><i class="fa-solid fa-circle-info"></i> Help Centre</a>
        <a href="../../auth/logout.php" class="nav-link logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</a>
    </div>
</aside>

<div id="main">
<div class="content">

    <header class="topbar">
        <div class="topbar-left">
            <button class="btn-hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="topbar-title">
                <h1>Dashboard</h1>
                <p>Monitor all sales and finances with E-Kantin</p>
            </div>
        </div>
        <div class="topbar-right">
            <button class="btn-notif">
                <i class="fa-solid fa-bell"></i>
                <span class="notif-dot"></span>
            </button>
            <div class="topbar-user">
                <div class="avatar"><?= strtoupper(substr($adminNama, 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($adminNama) ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </div>
    </header>

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
            <div class="stat-label">Penjual Aktif Hari ini</div>
            <div class="stat-row">
                <div class="stat-value"><?= $penjualAktif ?><span class="sub"> / <?= $totalToko ?></span></div>
                <i class="fa-solid fa-user-tie stat-icon"></i>
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
            <div class="chart-wrap">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-title">Proporsi Penjual (Transaksi)</div>
            <div class="donut-wrap">
                <div class="donut-canvas-wrap">
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="legend" id="legend"></div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="center">Waktu</th>
                        <th>Pelapor</th>
                        <th class="col-hide">Kategori Kendala</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kendala)): ?>
                        <tr class="empty-row">
                            <td colspan="5">
                                <i class="fa-solid fa-circle-check" style="color:var(--green);font-size:22px;display:block;margin-bottom:8px"></i>
                                Tidak ada kendala saat ini
                            </td>
                        </tr>
                    <?php else:
                        foreach ($kendala as $k): ?>
                            <tr>
                                <td class="center"><?= date('H.i', strtotime($k['waktu_pesan'])) ?></td>
                                <td><?= htmlspecialchars($k['pelapor']) ?></td>
                                <td class="col-hide"><?= $k['status'] === 'dibatalkan' ? 'Pesanan Dibatalkan' : 'Menunggu Konfirmasi' ?></td>
                                <td>
                                    <?php if ($k['status'] === 'dibatalkan'): ?>
                                            <span class="badge badge-batal"><i class="fa-solid fa-circle-xmark"></i> Dibatalkan</span>
                                    <?php else: ?>
                                            <span class="badge badge-proses"><i class="fa-solid fa-clock"></i> Proses</span>
                                    <?php endif; ?>
                                </td>
                                <td class="center">
                                    <a href="detail_pesanan.php?id=<?= $k['id_pesanan'] ?>">
                                        <button class="btn-aksi"><i class="fa-solid fa-eye"></i></button>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

function toggleSidebar() {
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    } else {
        const hidden = sidebar.style.marginLeft === '-256px';
        sidebar.style.marginLeft = hidden ? '0' : '-256px';
    }
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeSidebar();
});

const grafikLabels   = <?= json_encode($grafikLabels) ?>;
const grafikValues   = <?= json_encode($grafikValues) ?>;
const proporsiLabels = <?= json_encode($proporsiLabels) ?>;
const proporsiValues = <?= json_encode($proporsiValues) ?>;
const greens = ['#79b775','#8cd48a','#b5d7b4','#4a9e4a','#2d7a2d'];

const ctxL = document.getElementById('lineChart').getContext('2d');
const grad = ctxL.createLinearGradient(0, 0, 0, 180);
grad.addColorStop(0, 'rgba(121,183,117,.35)');
grad.addColorStop(1, 'rgba(121,183,117,0)');
new Chart(ctxL, {
    type: 'line',
    data: {
        labels: grafikLabels,
        datasets: [{ data: grafikValues, borderColor: '#79b775', borderWidth: 2.5, backgroundColor: grad, fill: true, tension: 0.4, pointBackgroundColor: '#79b775', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { grid: { color: '#e5e7eb' }, ticks: { display: false }, beginAtZero: true, border: { display: false } }
        }
    }
});

const ctxD = document.getElementById('donutChart').getContext('2d');
new Chart(ctxD, {
    type: 'doughnut',
    data: {
        labels: proporsiLabels,
        datasets: [{ data: proporsiValues, backgroundColor: greens.slice(0, proporsiLabels.length), borderWidth: 3, borderColor: '#f8f9fa' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { display: false } } }
});

const legend = document.getElementById('legend');
proporsiLabels.forEach((label, i) => {
    legend.innerHTML += `<div class="legend-item"><span class="legend-dot" style="background:${greens[i]}"></span>${label}</div>`;
});
</script>
</body>
</html>