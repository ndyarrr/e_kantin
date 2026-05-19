<?php
session_start();
$feedback = null;

if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../config/database.php';

$adminNama = $_SESSION['user_nama'] ?? 'Super Admin';
$adminId = (int) ($_SESSION['user_id'] ?? 0);
$profilAdmin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id_admin=$adminId"));
/* ── helpers ── */
function generateKodeAktivasi(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = 'EK';
    for ($i = 0; $i < 6; $i++)
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    return $code;
}
function generatePassword(int $len = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#';
    $pass = '';
    for ($i = 0; $i < $len; $i++)
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    return $pass;
}

$activeSection = $_POST['_section'] ?? $_GET['section'] ?? 'dashboard';


/* ══ ACTIONS ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'dashboard';

    if ($action === 'admin_profil') {
        $nama_baru = trim($_POST['nama_baru'] ?? '');
        $foto_baru = null;

        // hapus foto
        if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
            $fotoLama = $profilAdmin['foto_profil'] ?? '';
            if ($fotoLama) {
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
                    $lama = __DIR__ . '/../../assets/img/admin/admin_' . $adminId . '.' . $e;
                    if (file_exists($lama))
                        unlink($lama);
                }
                mysqli_query($conn, "UPDATE admin SET foto_profil=NULL WHERE id_admin=$adminId");
            }
            $foto_baru = null;
        }
        // upload foto baru
        elseif (!empty($_FILES['foto_profil']['name'])) {
            $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $namaFile = 'admin_' . $adminId . '.' . strtolower($ext);
                $tujuan = __DIR__ . '/../../assets/img/admin/' . $namaFile;
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
                    $lama = __DIR__ . '/../../assets/img/admin/admin_' . $adminId . '.' . $e;
                    if (file_exists($lama))
                        unlink($lama);
                }
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $tujuan)) {
                    $foto_baru = $namaFile;
                } else {
                    $feedback = ['type' => 'error', 'msg' => 'Gagal upload foto.'];
                }
            }
        }

        if ($nama_baru !== '' && $adminId) {
            $n = mysqli_real_escape_string($conn, $nama_baru);
            if ($foto_baru !== null) {
                $f = mysqli_real_escape_string($conn, $foto_baru);
                mysqli_query($conn, "UPDATE admin SET nama='$n', foto_profil='$f' WHERE id_admin=$adminId");
            } else {
                mysqli_query($conn, "UPDATE admin SET nama='$n' WHERE id_admin=$adminId");
            }
            $_SESSION['user_nama'] = $nama_baru;
            $adminNama = $nama_baru;
            // refresh profilAdmin biar foto baru ke-load
            $profilAdmin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id_admin=$adminId"));
            $feedback = ['type' => 'success', 'msg' => 'Profil berhasil diperbarui.'];
        }

        // ubah password
        $pw_lama = $_POST['pw_lama'] ?? '';
        $pw_baru = $_POST['pw_baru'] ?? '';
        $pw_konfirmasi = $_POST['pw_konfirmasi'] ?? '';

        if ($pw_lama !== '' || $pw_baru !== '' || $pw_konfirmasi !== '') {
            if ($pw_lama === '') {
                $feedback = ['type' => 'error', 'msg' => 'Password lama wajib diisi untuk mengubah password.'];
            } elseif (md5($pw_lama) !== $profilAdmin['password']) {
                $feedback = ['type' => 'error', 'msg' => 'Password lama salah.'];
            } elseif (strlen($pw_baru) < 8) {
                $feedback = ['type' => 'error', 'msg' => 'Password baru minimal 8 karakter.'];
            } elseif ($pw_baru !== $pw_konfirmasi) {
                $feedback = ['type' => 'error', 'msg' => 'Konfirmasi password tidak cocok.'];
            } else {
                $hash_baru = md5($pw_baru);
                mysqli_query($conn, "UPDATE admin SET password='$hash_baru' WHERE id_admin=$adminId");
                $feedback = ['type' => 'success', 'msg' => 'Password berhasil diubah.'];
            }
        }

        $activeSection = $_POST['_section'] ?? 'dashboard';
    }

    /* Tambah admin */
    if ($action === 'admin_tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $kode = generateKodeAktivasi();
        if ($nama === '' || $pass === '') {
            $feedback = ['type' => 'error', 'msg' => 'Nama dan password wajib diisi.'];
        } else {
            $n = mysqli_real_escape_string($conn, $nama);
            $h = md5($pass);
            $k = mysqli_real_escape_string($conn, $kode);
            if (mysqli_query($conn, "INSERT INTO admin (nama,password,kode_aktivasi) VALUES ('$n','$h','$k')")) {
                $feedback = ['type' => 'success', 'msg' => "Admin <strong>" . htmlspecialchars($nama) . "</strong> ditambahkan.", "extra" => "Kode Aktivasi: <code>$kode</code>"];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan admin.'];
            }
        }
    }

    /* Toggle status admin */
    if ($action === 'admin_toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['aktif', 'tidak_digunakan'])) {
            $new = $status === 'aktif' ? 'tidak_digunakan' : 'aktif';
            mysqli_query($conn, "UPDATE admin SET status='$new' WHERE id_admin=$id");
        }
        header("Location: ?section=admin");
        exit;
    }

    /* Reset password admin */
    if ($action === 'admin_reset') {
        $id = (int) ($_POST['id'] ?? 0);
        $pw_reset = trim($_POST['pw_reset'] ?? '');
        if ($id && $pw_reset !== '') {
            $hash = md5($pw_reset);
            $nama_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM admin WHERE id_admin=$id"))['nama'] ?? '';
            mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id_admin=$id");
            $feedback = ['type' => 'success', 'msg' => "Password <strong>" . htmlspecialchars($nama_admin) . "</strong> berhasil direset."];
        } elseif ($id && $pw_reset === '') {
            $feedback = ['type' => 'error', 'msg' => 'Password baru wajib diisi.'];
        }
    }

    if (
        str_starts_with($action, 'penjual_')
        || $action === 'kantin_assign_penjual'
        || $action === 'kantin_lepas_penjual'
    ) {
        require __DIR__ . '/actions/penjual.php';
    }

    if (
        (str_starts_with($action, 'kantin_') || str_starts_with($action, 'menu_'))
        && $action !== 'kantin_assign_penjual'
        && $action !== 'kantin_lepas_penjual'
    ) {
        require __DIR__ . '/actions/kantin.php';
    }

    if (str_starts_with($action, 'kantin_') || str_starts_with($action, 'menu_')) {
        if ($feedback)
            $_SESSION['feedback'] = $feedback;
        $selToko = (int) ($_POST['_selected_toko'] ?? 0);
        header("Location: ?section=kantin" . ($selToko ? "&toko=$selToko" : ""));
        exit;
    }

}


/* ══ DATA ══ */
// Dashboard
$totalTransaksi = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan)=CURDATE()"))['c'];
$totalPembeli = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT (SELECT COUNT(*) FROM murid)+(SELECT COUNT(*) FROM guru) as c"))['c'];
$tokoAktif = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE status='buka'"))['c'];
$totalToko = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko"))['c'];
$totalMenu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE tersedia=1"))['c'];

$grafikLabels = [];
$grafikValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $grafikLabels[] = date('d/m', strtotime("-{$i} days"));
    $grafikValues[] = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan)='$date'"))['c'];
}
$proporsiRaw = mysqli_fetch_all(mysqli_query($conn, "SELECT t.nama_toko, COUNT(p.id_pesanan) as total FROM toko t LEFT JOIN pesanan p ON p.id_toko=t.id_toko GROUP BY t.id_toko,t.nama_toko ORDER BY total DESC LIMIT 5"), MYSQLI_ASSOC);
$proporsiTotal = array_sum(array_column($proporsiRaw, 'total'));
$proporsiLabels = array_column($proporsiRaw, 'nama_toko');
$proporsiValues = $proporsiTotal > 0 ? array_map('intval', array_column($proporsiRaw, 'total')) : array_fill(0, count($proporsiRaw), 1);
$kendala = mysqli_fetch_all(mysqli_query($conn, "SELECT p.id_pesanan,p.waktu_pesan,p.status,COALESCE(m.nama,g.nama,'Unknown') AS pelapor FROM pesanan p LEFT JOIN murid m ON m.nisn=p.nisn_pembeli LEFT JOIN guru g ON g.nuptk=p.nuptk_pembeli WHERE p.status IN ('dibatalkan','menunggu') ORDER BY p.waktu_pesan DESC LIMIT 10"), MYSQLI_ASSOC);

// Admin
$admins = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM admin ORDER BY id_admin ASC"), MYSQLI_ASSOC);
$totalAdmin = count($admins);
$aktifCount = count(array_filter($admins, fn($a) => $a['status'] === 'aktif'));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin — Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/admin_kantin.css">
</head>



<body>
    <div id="modalFotoAdmin" class="modal-foto" onclick="tutupFotoAdmin()">
        <img id="modalFotoAdminImg" src="" onclick="event.stopPropagation()">
    </div>
    <div id="overlay" onclick="closeSidebar()">
    </div>

    <aside id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-inner">
                <img src="../../assets/img/logo-esemkita.png" class="logo-badge" onerror="this.style.display='none'">
                <div class="logo-text">E-Kantin</div>
            </div>
            <button class="btn-close-sidebar" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="sidebar-nav">
            <button class="nav-link active" data-section="dashboard" onclick="switchSection('dashboard')"><i
                    class="fa-solid fa-border-all"></i> Dashboard</button>
            <button class="nav-link" data-section="admin" onclick="switchSection('admin')"><i
                    class="fa-solid fa-user"></i> Admin</button>
            <button class="nav-link" data-section="kantin" onclick="switchSection('kantin')"><i
                    class="fa-solid fa-store"></i> Kantin</button>
            <button class="nav-link" data-section="penjual" onclick="switchSection('penjual')"><i
                    class="fa-solid fa-user-tag"></i> Penjual</button>
            <button class="nav-link" data-section="pembeli" onclick="switchSection('pembeli')"><i
                    class="fa-solid fa-users"></i> Pembeli</button>
        </nav>
        <div class="sidebar-bottom">
            <button class="nav-link"><i class="fa-solid fa-circle-info"></i> Help Centre</button>
            <a href="../../auth/logout.php" class="nav-link logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>
                Log out</a>
        </div>
    </aside>

    <div id="main">
        <div class="content">

            <header class="topbar">
                <div class="topbar-left">
                    <button class="btn-hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                    <div class="topbar-title">
                        <h1 id="pageTitle">Dashboard</h1>
                        <p id="pageSubtitle">Monitor semua transaksi dan keuangan E-Kantin</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="btn-notif"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
                    <div class="topbar-user" onclick="bukaProfil()" style="cursor:pointer">
                        <div class="avatar">
                            <?php if (!empty($profilAdmin['foto_profil'])): ?>
                                <img src="../../assets/img/admin/<?= htmlspecialchars($profilAdmin['foto_profil']) ?>?v=<?= time() ?>"
                                    style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                            <?php else: ?>
                                <?= strtoupper(substr($adminNama, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="user-name"><?= htmlspecialchars($adminNama) ?></div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($feedback): ?>
                <div class="feedback <?= $feedback['type'] ?>" id="feedbackBanner">
                    <i
                        class="fa-solid <?= $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <div>
                        <div><?= $feedback['msg'] ?></div>
                        <?php if (!empty($feedback['extra'])): ?>
                            <div style="margin-top:6px"><?= $feedback['extra'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php require __DIR__ . '/sections/profile.php'; ?>

            <!-- ══════════════ DASHBOARD ══════════════ -->
            <div class="section active" id="section-dashboard">
                <?php require __DIR__ . '/sections/dashboard.php'; ?>
            </div>

            <!-- ══════════════ ADMIN ══════════════ -->
            <div class="section" id="section-admin">
                <?php require __DIR__ . '/sections/admin.php'; ?>
            </div>

            <!-- ══════════════ KANTIN ══════════════ -->
            <div class="section" id="section-kantin">

                <?php
                require __DIR__ . '/sections/kantin_data.php'; // ← TAMBAH INI
                require __DIR__ . '/sections/kantin.php';
                ?>
            </div>

            <!-- ══════════════ PENJUAL ══════════════ -->
            <div class="section" id="section-penjual">
                <?php
                require __DIR__ . '/sections/penjual_data.php';
                require __DIR__ . '/sections/penjual.php';
                ?>
            </div>

            <!-- ══════════════ PEMBELI ══════════════ -->
            <div class="section" id="section-pembeli">
                <div class="placeholder-box">
                    <i class="fa-solid fa-users"></i>
                    <p>Halaman Pembeli — segera diisi</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        /* ── sidebar ── */
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
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); }
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

        /* ── section switcher ── */
        const pageMeta = {
            dashboard: { title: 'Dashboard', sub: 'Monitor semua transaksi dan keuangan E-Kantin' },
            admin: { title: 'Admin Management', sub: 'Kelola akun administrator E-Kantin' },
            kantin: { title: 'Kelola Kantin', sub: 'Manajemen toko dan menu kantin' },
            penjual: { title: 'Kelola Penjual', sub: 'Manajemen akun penjual' },
            pembeli: { title: 'Kelola Pembeli', sub: 'Data murid dan guru terdaftar' },
        };

        function switchSection(name) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));
            document.getElementById('section-' + name).classList.add('active');
            document.querySelector('.nav-link[data-section="' + name + '"]').classList.add('active');
            const meta = pageMeta[name] || {};
            document.getElementById('pageTitle').textContent = meta.title || '';
            document.getElementById('pageSubtitle').textContent = meta.sub || '';
            if (window.innerWidth <= 768) closeSidebar();
            history.replaceState(null, '', '?section=' + name);
            if (name === 'admin') regenKode();
        }

        if (window.location.search.includes('toko=')) {
            history.replaceState(null, '', '?section=kantin');
        }

        /* aktifkan section dari URL / POST */
        const initSection = '<?= htmlspecialchars($activeSection) ?>';
        if (initSection !== 'dashboard') switchSection(initSection);

        /* ── charts ── */
        const grafikLabels = <?= json_encode($grafikLabels) ?>;
        const grafikValues = <?= json_encode($grafikValues) ?>;
        const proporsiLabels = <?= json_encode($proporsiLabels) ?>;
        const proporsiValues = <?= json_encode($proporsiValues) ?>;
        const greens = ['#79b775', '#8cd48a', '#b5d7b4', '#4a9e4a', '#2d7a2d'];

        const ctxL = document.getElementById('lineChart').getContext('2d');
        const grad = ctxL.createLinearGradient(0, 0, 0, 180);
        grad.addColorStop(0, 'rgba(121,183,117,.35)');
        grad.addColorStop(1, 'rgba(121,183,117,0)');
        new Chart(ctxL, { type: 'line', data: { labels: grafikLabels, datasets: [{ data: grafikValues, borderColor: '#79b775', borderWidth: 2.5, backgroundColor: grad, fill: true, tension: 0.4, pointBackgroundColor: '#79b775', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } }, y: { grid: { color: '#e5e7eb' }, ticks: { display: false }, beginAtZero: true, border: { display: false } } } } });

        const ctxD = document.getElementById('donutChart');
        if (ctxD) {
            const greens = ['#79b775', '#8cd48a', '#b5d7b4', '#4a9e4a', '#2d7a2d'];
            new Chart(ctxD, {
                type: 'doughnut',
                data: { labels: proporsiLabels, datasets: [{ data: proporsiValues, backgroundColor: greens.slice(0, proporsiLabels.length), borderWidth: 3, borderColor: '#f8f9fa' }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { display: false } } }
            });
            const legend = document.getElementById('legend');
            proporsiLabels.forEach((label, i) => {
                legend.innerHTML += `<div class="legend-item"><span class="legend-dot" style="background:${greens[i]}"></span>${label}</div>`;
            });
        }
        /* ── kode aktivasi ── */
        function randomKode() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let c = 'EK';
            for (let i = 0; i < 6; i++) c += chars[Math.floor(Math.random() * chars.length)];
            return c;
        }
        function regenKode() {
            const k = randomKode();
            document.getElementById('kodePreview').textContent = k;
            document.getElementById('kodeHidden').value = k;
        }

        /* ── toggle password ── */
        function togglePass() {
            const inp = document.getElementById('inputPass');
            const ico = document.getElementById('eyeIcon');
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }

        /* ── reveal kode di tabel ── */
        function revealKode(id) {
            const el = document.getElementById('kode-' + id);
            const eye = document.getElementById('eye-' + id);
            if (el.dataset.hidden === '1') {
                el.textContent = el.dataset.plain;
                el.dataset.hidden = '0';
                eye.className = 'fa-solid fa-eye-slash';
            } else {
                const p = el.dataset.plain;
                el.textContent = p.substring(0, 2) + '•'.repeat(Math.max(0, p.length - 2));
                el.dataset.hidden = '1';
                eye.className = 'fa-solid fa-eye';
            }
        }

        /* ── modal profil ── */
        function bukaProfil() {
            const modal = document.getElementById('modalProfil');
            modal.style.display = 'flex';
            document.getElementById('profilSection').value =
                document.querySelector('.nav-link.active[data-section]')?.dataset.section || 'dashboard';
        }
        function tutupProfil() {
            document.getElementById('modalProfil').style.display = 'none';
        }
        function revealModalKode() {
            const el = document.getElementById('modalKode');
            const eye = document.getElementById('modalKodeEye').querySelector('i');
            if (el.dataset.hidden === '1') {
                el.textContent = el.dataset.plain;
                el.dataset.hidden = '0';
                eye.className = 'fa-solid fa-eye-slash';
            } else {
                const p = el.dataset.plain;
                el.textContent = p.substring(0, 2) + '•'.repeat(Math.max(0, p.length - 2));
                el.dataset.hidden = '1';
                eye.className = 'fa-solid fa-eye';
            }
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupProfil(); });

        /* ── auto dismiss feedback ── */
        const feedbackEl = document.getElementById('feedbackBanner');
        if (feedbackEl) {
            setTimeout(() => {
                feedbackEl.style.transition = 'opacity .5s';
                feedbackEl.style.opacity = '0';
                setTimeout(() => feedbackEl.remove(), 500);
            }, 4000); // hilang setelah 4 detik
        }

        function togglePw(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (!inp) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }

        // HAPUS fungsi selectToko dan tutupDetailToko yang lama (yang pertama)
        // Pakai ini saja:


        function bukaFotoAdmin(src) {
            document.getElementById('modalFotoAdminImg').src = src;
            document.getElementById('modalFotoAdmin').classList.add('show');
        }
        function tutupFotoAdmin() {
            document.getElementById('modalFotoAdmin').classList.remove('show');
        }

        // console.log('lineChart el:', document.getElementById('lineChart'));
        // console.log('donutChart el:', document.getElementById('donutChart'));
        // console.log('Chart.js loaded:', typeof Chart);
        // console.log('grafikLabels:', grafikLabels);
        // console.log('grafikValues:', grafikValues);
        // console.log('proporsiLabels:', proporsiLabels);
        // console.log('proporsiValues:', proporsiValues);
    </script>
</body>

</html>