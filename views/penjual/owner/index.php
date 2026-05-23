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

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    header('Location: ../../auth/login.php');
    exit;
}

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

// Cari id_toko
$idToko = 0;
if (!empty($profilPenjual)) {
    $rToko = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId LIMIT 1"
    ));
    $idToko = (int)($rToko['id_toko'] ?? 0);
}

$activeSection = $_POST['_section'] ?? $_GET['section'] ?? 'dashboard';

/* ── ACTIONS ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'dashboard';
    
    // LOGIKA TAMBAH MENU
    if ($action === 'tambah_menu') {
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;
        $nama_foto = null;

        // ── VALIDASI PHP: Benteng Pertahanan Terakhir ──
        if ($harga > 99999) {
            $feedback = [
                'type' => 'danger', 
                'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999 demi keamanan database!'
            ];
            $_SESSION['feedback'] = $feedback;
            header('Location: ?section=' . $activeSection);
            exit;
        }

        // ── PERBAIKAN PROSES UPLOAD FOTO (GANTI BLOK BARIS 56-72) ──
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = 'menu_' . time() . '.' . $fileExtension;
            
            // Menggunakan jalur absolut Linux XAMPP agar pasti ketemu foldernya
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';
            
            // Membuat folder otomatis jika belum ada di direktori assets
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                $nama_foto = $newFileName;
            } else {
                $feedback = [
                    'type' => 'danger', 
                    'msg' => 'Gagal mengunggah gambar. Silakan cek permission folder assets kamu!'
                ];
                $_SESSION['feedback'] = $feedback;
                header('Location: ?section=' . $activeSection);
                exit;
            }
        }

        $queryInsert = "INSERT INTO menu (id_toko, nama_menu, deskripsi, harga, foto_menu, stok, tersedia) 
                VALUES ($idToko, '$nama_menu', NULL, $harga, " . ($nama_foto ? "'$nama_foto'" : "NULL") . ", $stok, $tersedia)";
        
        if (mysqli_query($conn, $queryInsert)) {
            $feedback = ['type' => 'success', 'msg' => 'Menu baru berhasil ditambahkan!'];
        } else {
            // PERBAIKAN: Membersihkan potongan error teks nyasar yang bikin crash
            $feedback = ['type' => 'danger', 'msg' => 'Gagal menambah menu: ' . mysqli_error($conn)];
        }
    }

    // ════ BARU: LOGIKA EDIT MENU ════
    if ($action === 'edit_menu') {
        $id_menu   = (int)($_POST['id_menu'] ?? 0);
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;

        // Validasi harga maksimal
        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ?section=' . $activeSection);
            exit;
        }

        // Ambil nama foto lama terlebih dahulu dari database
        $resLama = mysqli_query($conn, "SELECT foto_menu FROM menu WHERE id_menu = $id_menu LIMIT 1");
        $menuLama = mysqli_fetch_assoc($resLama);
        $nama_foto = $menuLama['foto_menu'] ?? null;

        // Jika user mengunggah foto baru, proses file-nya
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = 'menu_' . time() . '.' . $fileExtension;
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';
            
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                // Hapus foto lama di folder jika ada, biar tidak menimbun sampah gambar
                if (!empty($menuLama['foto_menu']) && file_exists($uploadFileDir . $menuLama['foto_menu'])) {
                    unlink($uploadFileDir . $menuLama['foto_menu']);
                }
                $nama_foto = $newFileName;
            }
        }

        // Jalankan perintah UPDATE ke database
        $queryUpdate = "UPDATE menu SET 
                        nama_menu = '$nama_menu', 
                        harga = $harga, 
                        stok = $stok, 
                        tersedia = $tersedia, 
                        foto_menu = " . ($nama_foto ? "'$nama_foto'" : "NULL") . " 
                        WHERE id_menu = $id_menu";
        
        if (mysqli_query($conn, $queryUpdate)) {
            $feedback = ['type' => 'success', 'msg' => 'Menu berhasil diperbarui!'];
        } else {
            $feedback = ['type' => 'danger', 'msg' => 'Gagal memperbarui menu: ' . mysqli_error($conn)];
        }
    }

    if ($feedback) $_SESSION['feedback'] = $feedback;
    header('Location: ?section=' . $activeSection);
    exit;
}

/* ── DATA ── */
require __DIR__ . '/sections/dashboard_data.php';
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
    <link rel="stylesheet" href="../../../assets/css/penjual.css?v=<?= time() ?>">
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
        <button class="nav-link" data-section="profil" onclick="switchSection('profil')">
            <i class="fa-solid fa-user"></i> Profil
        </button>
        <button class="nav-link" data-section="kas" onclick="switchSection('kas')">
            <i class="fa-solid fa-book"></i> Buku Kas
        </button>
    </nav>
    <div class="sidebar-bottom">
        <button class="nav-link"><i class="fa-solid fa-circle-info"></i> Help Centre</button>
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
                    <p id="pageSubtitle">Monitor semua penjualan dan keuangan E-Kantin (Owner)</p>
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

        <div class="section" id="section-inbox">
            <div class="placeholder-box">
                <i class="fa-solid fa-inbox"></i>
                <p>Halaman Inbox — segera diisi</p>
            </div>
        </div>
        <div class="section" id="section-profil">
            <div class="placeholder-box">
                <i class="fa-solid fa-user"></i>
                <p>Halaman Profil — segera diisi</p>
            </div>
        </div>
        <div class="section" id="section-grid-buku-kas">
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
    inbox     : { title: 'Inbox',      sub: 'Pesanan masuk dan riwayat transaksi' },
    profil    : { title: 'Profil',     sub: 'Kelola data akun penjual' },
    kas       : { title: 'Buku Kas',   sub: 'Catatan pemasukan dan keuangan toko' },
};

function switchSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));
    
    // Perbaikan selektor penamaan section kas agar sinkron
    const targetSection = name === 'kas' ? 'section-grid-buku-kas' : 'section-' + name;
    const targetEl = document.getElementById(targetSection);
    if(targetEl) targetEl.classList.add('active');
    
    const btn = document.querySelector('.nav-link[data-section="' + name + '"]');
    if (btn) btn.classList.add('active');
    const meta = pageMeta[name] || {};
    document.getElementById('pageTitle').textContent    = meta.title || '';
    document.getElementById('pageSubtitle').textContent = meta.sub   || '';
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
</script>
</body>
</html>