<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$feedback = null;

if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['penjual', 'staf'])) {
    header('Location: ' . $base_path . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$penjualNama = $_SESSION['user_nama'] ?? 'Penjual';
$penjualId = (int) ($_SESSION['user_id'] ?? 0);

$profilPenjual = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT p.*, t.nama_toko, t.id_toko, t.foto_toko, tp.shift
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual AND tp.status = 'aktif'
     LEFT JOIN toko t ON t.id_toko = tp.id_toko AND t.deleted_at IS NULL
     WHERE p.id_penjual = $penjualId
     ORDER BY tp.id DESC
     LIMIT 1"
));

$idToko = 0;
if (!empty($profilPenjual)) {
    $idToko = (int) ($profilPenjual['id_toko'] ?? 0);
}
// Simpan id_toko ke session agar backend chat bisa pakai
$_SESSION['id_toko'] = $idToko;

$activeSection = $_POST['_section'] ?? $_GET['section'] ?? 'dashboard';

/* ── ACTIONS (DIHUBUNGKAN KE FILE PROSES BERSAMA) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'kirim_laporan') {
        // Biarkan report.php handle sendiri — jangan intercept
    } elseif ($action === 'update_status') {
        require_once __DIR__ . '/../actions/proses_inbox.php';
        exit;
    } else {
        require_once __DIR__ . '/../actions/proses_menu.php';
    }
}

/* ── DATA ── */
require __DIR__ . '/sections/dashboard_data.php';
require __DIR__ . '/sections/menu_data.php';
require __DIR__ . '/sections/inbox_data.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin — Penjual</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../../assets/css/staf.css?v=<?= time() ?>">
</head>

<body>

    <div id="overlay" onclick="closeSidebar()"></div>

    <aside id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-inner">
                <img src="../../../assets/img/logo-esemkita.png" class="logo-badge" onerror="this.style.display='none'">
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
            <button class="nav-link" data-section="inbox" onclick="switchSection('inbox')">
                <i class="fa-solid fa-inbox"></i> Inbox
                <?php if ($totalPesananBaru > 0): ?>
                    <span class="nav-badge"><?= $totalPesananBaru ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-link" data-section="chat" onclick="switchSection('chat')">
                <i class="fa-solid fa-comments"></i> Chat
            </button>
            <button class="nav-link" data-section="profil" onclick="switchSection('profil')">
                <i class="fa-solid fa-user"></i> Profil
            </button>
        </nav>
        <div class="sidebar-bottom">
            <button class="nav-link" data-section="report" onclick="switchSection('report')">
                <i class="fa-solid fa-flag"></i> Laporan Kendala
            </button>
            <a href="../../../auth/logout.php" class="nav-link logout">
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
                        <p id="pageSubtitle">Monitor semua penjualan dan keuangan E-Kantin</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="btn-notif" onclick="switchSection('inbox')">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($totalPesananBaru > 0): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </button>
                    <div class="topbar-user" onclick="switchSection('profil')" style="cursor:pointer">
                        <div class="avatar">
                            <?php if (!empty($profilPenjual['foto_profil'])): ?>
                                <img src="../../../assets/img/penjual/<?= htmlspecialchars($profilPenjual['foto_profil']) ?>?v=<?= time() ?>"
                                    style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                            <?php else: ?>
                                <?= strtoupper(substr($penjualNama, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($penjualNama) ?></div>
                            <div class="user-role"><?= htmlspecialchars($profilPenjual['nama_toko'] ?? 'Penjual') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($feedback): ?>
                <div class="feedback <?= $feedback['type'] ?>" id="feedbackBanner">
                    <i
                        class="fa-solid <?= $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <div><?= $feedback['msg'] ?></div>
                </div>
            <?php endif; ?>

            <div class="section active" id="section-dashboard">
                <?php require __DIR__ . '/sections/dashboard.php'; ?>
            </div>

            <div class="section" id="section-menu">
                <?php require __DIR__ . '/sections/menu.php'; ?>
            </div>

            <div class="section" id="section-inbox">
                <?php require __DIR__ . '/sections/inbox.php'; ?>
            </div>

            <div class="section" id="section-chat">
                <?php require __DIR__ . '/../../../views/chat.php'; ?>
            </div>

            <div class="section" id="section-profil">
                <?php require __DIR__ . '/sections/profil.php'; ?>
            </div>

            <div class="section" id="section-report">
                <?php require __DIR__ . '/../../../views/report.php'; ?>
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
                const isHidden = sidebar.style.marginLeft === '-256px';
                sidebar.style.marginLeft = isHidden ? '0' : '-256px';
                document.getElementById('main').style.marginLeft = isHidden ? 'var(--sidebar-w)' : '0';
            }
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

        const pageMeta = {
            dashboard: { title: 'Dashboard', sub: 'Monitor semua penjualan dan keuangan E-Kantin' },
            menu: { title: 'Menu', sub: 'Kelola menu dan stok kantin' },
            inbox: { title: 'Inbox', sub: 'Pesanan masuk dan riwayat transaksi' },
            chat: { title: 'Chat', sub: 'Komunikasi dengan pembeli' },
            profil: { title: 'Profil', sub: 'Kelola data akun penjual' },
            report: { title: 'Laporan Kendala', sub: 'Kirim laporan masalah ke admin' },
        };

        function switchSection(name) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));
            document.getElementById('section-' + name).classList.add('active');
            const btn = document.querySelector('.nav-link[data-section="' + name + '"]');
            if (btn) btn.classList.add('active');
            const meta = pageMeta[name] || {};
            document.getElementById('pageTitle').textContent = meta.title || '';
            document.getElementById('pageSubtitle').textContent = meta.sub || '';
            if (window.innerWidth <= 768) closeSidebar();
            history.replaceState(null, '', '?section=' + name);
        }

        const initSection = '<?= htmlspecialchars($activeSection) ?>';
        if (initSection !== 'dashboard') switchSection(initSection);

        const feedbackEl = document.getElementById('feedbackBanner');
        if (feedbackEl) {
            setTimeout(() => {
                feedbackEl.style.transition = 'opacity .5s';
                feedbackEl.style.opacity = '0';
                setTimeout(() => feedbackEl.remove(), 500);
            }, 4000);
        }

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

        document.addEventListener('DOMContentLoaded', () => {
            updateChatUnreadBadge();
            setInterval(updateChatUnreadBadge, 4000);
        });
    </script>
</body>

</html>