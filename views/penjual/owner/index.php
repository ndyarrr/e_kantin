<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$feedback = null;
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

// Deteksi server dinamis
$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_path = $is_php_s ? '' : '/e_kantin';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    header('Location: ' . $base_path . '/auth/login.php');
    exit;
}

// Penanganan request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'tambah_menu' || $action === 'edit_menu' || $action === 'hapus_menu') {
        require_once __DIR__ . '/../actions/proses_menu.php';
        exit;
    } elseif ($action === 'selesaikan_pesanan' || strpos($action, 'pesanan') !== false) {
        require_once __DIR__ . '/../actions/proses_pesanan.php'; 
        exit;
    } elseif ($action === 'edit_profil' || $action === 'ganti_password' || $action === 'hapus_foto_profil') {
        require_once __DIR__ . '/../actions/proses_profil.php';
        exit;
    }
}

// Lanjut ke query database...
require_once __DIR__ . '/../../../config/database.php';

$penjualNama = $_SESSION['user_nama'] ?? 'Penjual';
$penjualId   = (int)($_SESSION['user_id'] ?? 0);

$profilPenjual = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, t.nama_toko, tp.shift
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual
     LEFT JOIN toko t ON t.id_toko = tp.id_toko
     WHERE p.id_penjual = $penjualId
     LIMIT 1"
));

// 🌟 INTEGRASI PROTEKSI AKUN: Blokir jika user nekat mengakses paksa dashboard owner
if (!$profilPenjual || strtolower($profilPenjual['role'] ?? '') !== 'owner') {
    echo "<div style='padding: 30px; background: #fee2e2; color: #991b1b; text-align:center; font-family:sans-serif; margin: 50px auto; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <i class='fa-solid fa-triangle-exclamation' style='font-size: 40px; margin-bottom: 15px;'></i>
            <h2>Akses Dashboard Ditolak!</h2>
            <p>Akun Anda tidak memiliki hak akses sebagai Owner Utama.</p>
            <a href='".$base_path."/auth/logout.php' style='display:inline-block; margin-top:15px; padding:10px 20px; background:#dc2626; color:#fff; text-decoration:none; border-radius:5px;'>Log Out</a>
          </div>";
    exit;
}

// Cari id_toko
$idToko = 0;
if (!empty($profilPenjual)) {
    $rToko = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId LIMIT 1"
    ));
    $idToko = (int)($rToko['id_toko'] ?? 0);
}
// Simpan id_toko ke session supaya backend chat bisa pakai
$_SESSION['id_toko'] = $idToko;

$activeSection = $_GET['section'] ?? 'dashboard';

/* ── DATA READ (PROSES GET UNTUK VIEW TAMPILAN) ── */
require __DIR__ . '/sections/dashboard_data.php';

// Menangkap kiriman filter URL untuk digunakan di menu_data.php
$search   = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$kategori = mysqli_real_escape_string($conn, $_GET['kategori'] ?? 'semua');

require __DIR__ . '/sections/menu_data.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin — Owner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/penjual.css?v=<?= time() ?>">
</head>
<body>

<div id="overlay" onclick="closeSidebar()"></div>

<aside id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-inner">
            <img src="<?= $base_path ?>/assets/img/logo-esemkita.png" class="logo-badge" onerror="this.style.display='none'">
            <div class="logo-text">E-Kantin</div>
        </div>
        <button class="btn-close-sidebar" onclick="closeSidebar()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <button class="nav-link active" data-section="dashboard" onclick="switchSection('dashboard')">
            <i class="fa-solid fa-border-all"></i> Dashboard
        </button>
        <button class="nav-link" data-section="menu" onclick="switchSection('menu')">
            <i class="fa-solid fa-utensils"></i> Menu
        </button>
        <button class="nav-link" data-section="staf" onclick="switchSection('staf')">
            <i class="fa-solid fa-users"></i> Staf & Shift
        </button>
        <button class="nav-link" data-section="inbox" onclick="switchSection('inbox')">
            <i class="fa-solid fa-inbox"></i> Inbox
            <?php if (isset($totalPesananBaru) && $totalPesananBaru > 0): ?>
                <span class="nav-badge"><?= $totalPesananBaru ?></span>
            <?php endif; ?>
        </button>
        <button class="nav-link" data-section="pesanan" onclick="switchSection('pesanan')">
            <i class="fa-solid fa-receipt"></i> Antrean Pesanan
        </button>
        <button class="nav-link" data-section="profil" onclick="switchSection('profil')">
            <i class="fa-solid fa-user"></i> Profil
        </button>
        <button class="nav-link" data-section="chat" onclick="switchSection('chat')">
            <i class="fa-solid fa-comments"></i> Chat
        </button>
        <button class="nav-link" data-section="kas" onclick="switchSection('kas')">
            <i class="fa-solid fa-book"></i> Buku Kas
        </button>
    </nav>
    <div class="sidebar-bottom">
        <button class="nav-link"><i class="fa-solid fa-circle-info"></i> Help Centre</button>
        <a href="<?= $base_path ?>/auth/logout.php" class="nav-link logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Log out
        </a>
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
                    <h1 id="pageTitle">Dashboard</h1>
                    <p id="pageSubtitle">Monitor semua penjualan dan keuangan E-Kantin (Owner)</p>
                </div>
            </div>
            <div class="topbar-right">
                <button class="btn-notif" onclick="switchSection('inbox')">
                    <i class="fa-solid fa-bell"></i>
                    <?php if (isset($totalPesananBaru) && $totalPesananBaru > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </button>
                <div class="topbar-user" onclick="switchSection('profil')" style="cursor:pointer">
                    <div class="avatar">
                        <?php if (!empty($profilPenjual['foto_profil'])): ?>
                            <img src="<?= $base_path ?>/assets/img/penjual/<?= htmlspecialchars($profilPenjual['foto_profil']) ?>?v=<?= time() ?>"
                                style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                        <?php else: ?>
                            <?= strtoupper(substr($penjualNama, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($penjualNama) ?></div>
                        <div class="user-role"><?= htmlspecialchars($profilPenjual['nama_toko'] ?? 'Owner Kantin') ?> (Owner)</div>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($feedback): ?>
            <div class="feedback <?= $feedback['type'] ?>" id="feedbackBanner">
                <i class="fa-solid <?= $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <div><?= $feedback['msg'] ?></div>
            </div>
        <?php endif; ?>

        <div class="section active" id="section-dashboard">
            <?php require __DIR__ . '/sections/dashboard.php'; ?>
        </div>

        <div class="section" id="section-menu">
            <?php require __DIR__ . '/sections/menu.php'; ?>
        </div>

        <div class="section" id="section-staf">
            <div class="placeholder-box">
                <i class="fa-solid fa-users"></i>
                <p>Halaman Staf & Shift — segera diisi</p>
            </div>
        </div>

        <div class="section" id="section-inbox">
            <?php require __DIR__ . '/sections/inbox.php'; ?>
        </div>

        <div class="section" id="section-pesanan">
            <?php require __DIR__ . '/sections/pesanan.php'; ?>
        </div>

        <div class="section" id="section-profil">
            <?php require __DIR__ . '/sections/profil.php'; ?>
        </div>
        <div class="section" id="section-chat">
            <?php require __DIR__ . '/../../../views/chat.php'; ?>
        </div>
        
        <div class="section" id="section-kas">
            <div class="placeholder-box">
                <i class="fa-solid fa-book"></i>
                <p>Halaman Buku Kas — khusus owner</p>
            </div>
        </div>
    </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    } else {
        const hidden = sidebar.style.marginLeft === '-256px';
        sidebar.style.marginLeft = hidden ? '0' : '-256px';
        document.getElementById('main').style.width = hidden ? '' : '100%';
    }
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
}
window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

const pageMeta = {
    dashboard : { title: 'Dashboard',  sub: 'Monitor semua penjualan dan keuangan E-Kantin' },
    menu      : { title: 'Menu',       sub: 'Kelola menu dan stok kantin' },
    staf      : { title: 'Staf & Shift', sub: 'Kelola jadwal kerja dan petugas kasir' },
    inbox     : { title: 'Inbox',      sub: 'Pesanan masuk dan riwayat transaksi' },
    pesanan   : { title: 'Antrean Pesanan', sub: 'Kelola pesanan pelanggan' },
    profil    : { title: 'Profil',     sub: 'Kelola data akun penjual' },
    chat      : { title: 'Chat',       sub: 'Balas pesan pembeli atas nama kantin kamu' },
    kas       : { title: 'Buku Kas',   sub: 'Catatan pemasukan dan keuangan toko' },
};

function switchSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));
    
    // 🌟 PERBAIKAN UTAMA: Navigasi ID sekarang langsung seirama ke "section-kas"
    const targetSection = 'section-' + name;
    const targetEl = document.getElementById(targetSection);
    if(targetEl) targetEl.classList.add('active');
    
    const btn = document.querySelector('.nav-link[data-section="' + name + '"]');
    if (btn) btn.classList.add('active');
    const meta = pageMeta[name] || {};
    document.getElementById('pageTitle').textContent    = meta.title || '';
    document.getElementById('pageSubtitle').textContent = meta.sub   || '';
    if (window.innerWidth <= 768) closeSidebar();
    
    // Cegah URL menumpuk data filter pencarian menu saat berpindah ke section lain
    if (name === 'menu') {
        history.replaceState(null, '', '?section=' + name + '&search=<?=urlencode($_GET['search']??'')?>&kategori=<?=$_GET['kategori']??'semua'?>');
    } else {
        history.replaceState(null, '', '?section=' + name);
    }
}

const initSection = <?= json_encode($activeSection ?? 'dashboard') ?>;
if (initSection && initSection !== 'dashboard') {
    switchSection(initSection);
}

const feedbackEl = document.getElementById('feedbackBanner');
if (feedbackEl) {
    setTimeout(() => {
        feedbackEl.style.transition = 'opacity .5s';
        feedbackEl.style.opacity = '0';
        setTimeout(() => feedbackEl.remove(), 500);
    }, 4000);
}

// Polling Realtime Chat Notification Badge in Sidebar
function updateChatUnreadBadge() {
    const scriptPath = window.location.pathname;
    let backendUrl = '../../backend/ambil_unread_chat.php';
    if (scriptPath.includes('/owner/') || scriptPath.includes('/staf/')) {
        backendUrl = '../../../backend/ambil_unread_chat.php';
    }
    
    fetch(backendUrl)
        .then(res => res.json())
        .then(data => {
            const count = data.unread_count || 0;
            const chatBtn = document.querySelector('.nav-link[data-section="chat"]');
            if (chatBtn) {
                let badge = chatBtn.querySelector('.nav-badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'nav-badge';
                        chatBtn.appendChild(badge);
                    }
                    badge.textContent = count;
                    badge.style.display = 'inline-block';
                } else {
                    if (badge) {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(err => console.error('Error fetching unread chat:', err));
}

// Jalankan saat load pertama kali
document.addEventListener('DOMContentLoaded', () => {
    updateChatUnreadBadge();
    setInterval(updateChatUnreadBadge, 4000);
});
</script>
</body>
</html>