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
    "SELECT p.*, t.nama_toko, t.foto_toko, tp.shift
     FROM penjual p
     LEFT JOIN toko_penjual tp ON tp.id_penjual = p.id_penjual
     LEFT JOIN toko t ON t.id_toko = tp.id_toko
     WHERE p.id_penjual = $penjualId
     LIMIT 1"
));

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
    $action        = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'dashboard';

    // ════ TAMBAH MENU ════
    if ($action === 'tambah_menu') {
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;
        $kategori  = mysqli_real_escape_string($conn, strtolower($_POST['kategori'] ?? 'makanan'));
        $nama_foto = null;

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ?section=' . $activeSection);
            exit;
        }

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = 'menu_' . time() . '.' . $fileExtension;
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                $nama_foto = $newFileName;
            } else {
                $feedback = ['type' => 'danger', 'msg' => 'Gagal mengunggah gambar. Periksa permission folder assets/img/menu/!'];
                $_SESSION['feedback'] = $feedback;
                header('Location: ?section=' . $activeSection);
                exit;
            }
        }

        $queryInsert = "INSERT INTO menu (id_toko, nama_menu, deskripsi, harga, foto_menu, stok, tersedia, kategori)
                        VALUES ($idToko, '$nama_menu', NULL, $harga, "
                        . ($nama_foto ? "'$nama_foto'" : "NULL") . ", $stok, $tersedia, '$kategori')";

        if (mysqli_query($conn, $queryInsert)) {
            $feedback = ['type' => 'success', 'msg' => 'Menu baru berhasil ditambahkan!'];
        } else {
            $feedback = ['type' => 'danger', 'msg' => 'Gagal menambah menu: ' . mysqli_error($conn)];
        }
    }

    // ════ HAPUS MENU ════  ← FIX: sudah di luar blok tambah_menu
    if ($action === 'hapus_menu') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $cek = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT foto_menu FROM menu WHERE id_menu=$id_menu AND id_toko=$idToko LIMIT 1"
        ));
        if ($cek) {
            if (!empty($cek['foto_menu'])) {
                $path = __DIR__ . '/../../../assets/img/menu/' . $cek['foto_menu'];
                if (file_exists($path)) unlink($path);
            }
            mysqli_query($conn, "DELETE FROM menu WHERE id_menu=$id_menu");
            $feedback = ['type' => 'success', 'msg' => 'Menu berhasil dihapus.'];
        } else {
            $feedback = ['type' => 'danger', 'msg' => 'Menu tidak ditemukan atau bukan milik toko ini.'];
        }
    }

    // ════ EDIT MENU ════  ← FIX: sudah di luar blok tambah_menu
    if ($action === 'edit_menu') {
        $id_menu   = (int)($_POST['id_menu'] ?? 0);
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;
        $kategori  = mysqli_real_escape_string($conn, strtolower($_POST['kategori'] ?? 'makanan'));

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ?section=' . $activeSection);
            exit;
        }

        $resLama  = mysqli_query($conn, "SELECT foto_menu FROM menu WHERE id_menu=$id_menu AND id_toko=$idToko LIMIT 1");
        $menuLama = mysqli_fetch_assoc($resLama);

        // Keamanan: pastikan menu ini milik toko yang login
        if (!$menuLama) {
            $feedback = ['type' => 'danger', 'msg' => 'Menu tidak ditemukan atau bukan milik toko ini.'];
        } else {
            $nama_foto     = $menuLama['foto_menu'] ?? null;
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['foto']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $newFileName   = 'menu_' . time() . '.' . $fileExtension;

                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    if (!empty($menuLama['foto_menu']) && file_exists($uploadFileDir . $menuLama['foto_menu'])) {
                        unlink($uploadFileDir . $menuLama['foto_menu']);
                    }
                    $nama_foto = $newFileName;
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Gagal mengunggah gambar menu. Periksa permission folder assets/img/menu/!'];
                    $_SESSION['feedback'] = $feedback;
                    header('Location: ?section=' . $activeSection);
                    exit;
                }
            }

            $queryUpdate = "UPDATE menu SET
                            nama_menu = '$nama_menu',
                            harga     = $harga,
                            stok      = $stok,
                            tersedia  = $tersedia,
                            kategori  = '$kategori',
                            foto_menu = " . ($nama_foto ? "'$nama_foto'" : "NULL") . "
                            WHERE id_menu = $id_menu AND id_toko = $idToko";

            if (mysqli_query($conn, $queryUpdate)) {
                $feedback = ['type' => 'success', 'msg' => 'Menu berhasil diperbarui!'];
            } else {
                $feedback = ['type' => 'danger', 'msg' => 'Gagal memperbarui menu: ' . mysqli_error($conn)];
            }
        }
    }

    // ════ UPDATE STATUS PESANAN (INBOX) ════
    if ($action === 'update_status') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        $status_baru = $_POST['status_baru'] ?? '';
        $statusValid = ['dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];

        if (!in_array($status_baru, $statusValid)) {
            $feedback = ['type' => 'danger', 'msg' => 'Status tidak valid.'];
        } else {
            // Pastikan pesanan milik toko ini
            $cekPesanan = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id_pesanan FROM pesanan WHERE id_pesanan=$id_pesanan AND id_toko=$idToko LIMIT 1"
            ));
            if (!$cekPesanan) {
                $feedback = ['type' => 'danger', 'msg' => 'Pesanan tidak ditemukan.'];
            } else {
                $updateFields = "status = '$status_baru'";
                if ($status_baru === 'selesai') {
                    $updateFields .= ", waktu_ambil = NOW()";
                }
                if (mysqli_query($conn, "UPDATE pesanan SET $updateFields WHERE id_pesanan=$id_pesanan")) {
                    $labelStatus = match($status_baru) {
                        'dikonfirmasi' => 'diproses',
                        'siap_diambil' => 'siap diambil',
                        'selesai'      => 'selesai',
                        'dibatalkan'   => 'dibatalkan',
                        default        => $status_baru,
                    };
                    $feedback = ['type' => 'success', 'msg' => "Pesanan #$id_pesanan berhasil ditandai $labelStatus."];
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Gagal mengubah status: ' . mysqli_error($conn)];
                }
            }
        }
    }

    // ════ EDIT PROFIL ════
    if ($action === 'edit_profil') {
        $nama     = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
        $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));

        if (empty($nama) || empty($username)) {
            $feedback = ['type' => 'danger', 'msg' => 'Nama dan username tidak boleh kosong.'];
        } else {
            /* Cek username duplikat (selain diri sendiri) */
            $cekUser = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id_penjual FROM penjual
                 WHERE username='$username' AND id_penjual != $penjualId LIMIT 1"
            ));
            if ($cekUser) {
                $feedback = ['type' => 'danger', 'msg' => 'Username sudah dipakai oleh akun lain.'];
            } else {
                $fotoField = '';
                if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                    $ext         = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                    $allowed     = ['jpg','jpeg','png','webp'];
                    if (!in_array($ext, $allowed)) {
                        $feedback = ['type' => 'danger', 'msg' => 'Format foto tidak didukung.'];
                    } else {
                        $uploadDir = '/opt/lampp/htdocs/e_kantin/assets/img/penjual/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $newFile = 'penjual_' . $penjualId . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $uploadDir . $newFile)) {
                            /* Hapus foto lama */
                            $fotoLama = $profilPenjual['foto_profil'] ?? '';
                            if ($fotoLama && file_exists($uploadDir . $fotoLama)) unlink($uploadDir . $fotoLama);
                            $fotoField = ", foto_profil = '$newFile'";
                            $_SESSION['user_foto'] = $newFile;
                        } else {
                            $feedback = ['type' => 'danger', 'msg' => 'Gagal mengunggah foto profil. Periksa permission folder assets/img/penjual/!'];
                        }
                    }
                }

                if (!isset($feedback)) {
                    mysqli_query($conn,
                        "UPDATE penjual SET nama='$nama', username='$username'$fotoField
                         WHERE id_penjual=$penjualId"
                    );
                    $_SESSION['user_nama'] = $nama;
                    $feedback = ['type' => 'success', 'msg' => 'Profil berhasil diperbarui!'];
                }
            }
        }
    }

    // ════ GANTI PASSWORD ════
    if ($action === 'ganti_password') {
        $pwLama    = $_POST['password_lama']  ?? '';
        $pwBaru    = $_POST['password_baru']  ?? '';
        $pwKonfirm = $_POST['password_konfirm'] ?? '';

        $akun = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT password FROM penjual WHERE id_penjual=$penjualId LIMIT 1"
        ));

        if (!password_verify($pwLama, $akun['password'])) {
            $feedback = ['type' => 'danger', 'msg' => 'Password lama tidak sesuai.'];
        } elseif (strlen($pwBaru) < 6) {
            $feedback = ['type' => 'danger', 'msg' => 'Password baru minimal 6 karakter.'];
        } elseif ($pwBaru !== $pwKonfirm) {
            $feedback = ['type' => 'danger', 'msg' => 'Konfirmasi password tidak cocok.'];
        } else {
            $hash = password_hash($pwBaru, PASSWORD_DEFAULT);
            $hash = mysqli_real_escape_string($conn, $hash);
            mysqli_query($conn, "UPDATE penjual SET password='$hash' WHERE id_penjual=$penjualId");
            $feedback = ['type' => 'success', 'msg' => 'Password berhasil diganti!'];
        }
    }

    // ← FIX: satu titik redirect, tidak ada duplikat
    if ($feedback) $_SESSION['feedback'] = $feedback;
    header('Location: ?section=' . $activeSection . '&t=' . time());
    exit;
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
        <button class="nav-link" data-section="chat" onclick="switchSection('chat')">
            <i class="fa-solid fa-comments"></i> Chat
        </button>
        <button class="nav-link" data-section="profil" onclick="switchSection('profil')">
            <i class="fa-solid fa-user"></i> Profil
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
                        <div class="user-role"><?= htmlspecialchars($profilPenjual['nama_toko'] ?? 'Penjual') ?></div>
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

        <!-- DASHBOARD -->
        <div class="section active" id="section-dashboard">
            <?php
                require __DIR__ . '/sections/dashboard.php';
                ?>
        </div>

        <!-- PLACEHOLDER sections lainnya -->
        <div class="section" id="section-menu">
            <?php 
                require __DIR__ . '/sections/menu.php';
                ?>
        </div>

        <div class="section" id="section-inbox">
            <?php require __DIR__ . '/sections/inbox.php'; ?>
        </div>

        <div class="section" id="section-chat">
            <div class="placeholder-box">
            <i class="fa-solid fa-comments"></i>
            <p>Fitur Chat — segera hadir</p>
            </div>
        </div>

        <div class="section" id="section-profil">
            <?php require __DIR__ . '/sections/profil.php'; ?>
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
    dashboard : { title: 'Dashboard',  sub: 'Monitor semua penjualan dan keuangan E-Kantin' },
    menu      : { title: 'Menu',       sub: 'Kelola menu dan stok kantin' },
    inbox     : { title: 'Inbox',      sub: 'Pesanan masuk dan riwayat transaksi' },
    chat      : { title: 'Chat', sub: 'Komunikasi dengan pembeli' },
    profil    : { title: 'Profil',     sub: 'Kelola data akun penjual' },
};

function switchSection(name) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));
    document.getElementById('section-' + name).classList.add('active');
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