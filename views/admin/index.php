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

// Ambil status tingkatan level admin dari session (1 = Super Admin, 2 = Admin Biasa)
$isAdminSuper = (isset($_SESSION['role_level']) && (int) $_SESSION['role_level'] === 1);

// KUNCI UTAMA: Mengambil data laporan kendala sistem/web yang berstatus menunggu atau proses
$filterKendala = $_GET['filter_kendala'] ?? 'aktif';

$whereKendala = match ($filterKendala) {
    'menunggu' => "WHERE status = 'menunggu'",
    'proses' => "WHERE status = 'proses'",
    'selesai' => "WHERE status = 'selesai'",
    default => "WHERE status IN ('menunggu', 'proses')", // aktif
};

$kendala = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT * FROM laporan_kendala $whereKendala ORDER BY dibuat_pada DESC LIMIT 10"
), MYSQLI_ASSOC);

// Untuk notif dropdown — selalu ambil yang aktif (menunggu + proses)
$kendalaNotif = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT * FROM laporan_kendala WHERE status IN ('menunggu','proses') ORDER BY dibuat_pada DESC LIMIT 10"
), MYSQLI_ASSOC);

/* ── helpers ── */

if (!function_exists('catatLog')) {
    function catatLog($conn, $aksi, $keterangan = '')
    {
        if (defined('SYSTEM_LOGGING_ACTIVE') && !SYSTEM_LOGGING_ACTIVE) {
            return;
        }
        $role = mysqli_real_escape_string($conn, $_SESSION['user_role'] ?? '');
        $uid = mysqli_real_escape_string($conn, $_SESSION['user_id'] ?? '');
        $nama = mysqli_real_escape_string($conn, $_SESSION['user_nama'] ?? '');
        $aksi = mysqli_real_escape_string($conn, $aksi);
        $ket = mysqli_real_escape_string($conn, $keterangan);
        $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
        mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                             VALUES ('$role','$uid','$nama','$aksi','$ket','$ip')");
    }
}
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
if ($activeSection === 'admin' && !$isAdminSuper) {
    $activeSection = 'dashboard';
}

/* ══ AJAX ENDPOINTS (return JSON and exit) ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    if ($action === 'kantin_ubah_slot_ajax') {
        if (!$isAdminSuper) {
            echo json_encode(['status' => 'error', 'msg' => 'Akses Ilegal: Hanya Super Admin yang berhak mengubah slot kantin!']);
            exit;
        }
        $nilaiBaru = (int) ($_POST['nilai'] ?? 0);
        $currentTokoCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE deleted_at IS NULL"))['c'];

        if ($nilaiBaru < 1) {
            echo json_encode(['status' => 'error', 'msg' => 'Slot minimal harus 1.']);
            exit;
        }
        if ($nilaiBaru < $currentTokoCount) {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal: Slot tidak boleh kurang dari jumlah stand aktif (' . $currentTokoCount . ').']);
            exit;
        }
        $nilaiBaru = (int) $nilaiBaru;
        mysqli_query($conn, "UPDATE pengaturan SET nilai = '$nilaiBaru' WHERE kunci = 'slot_kantin'");
        catatLog($conn, 'Ubah Slot Kantin', 'Super Admin mengubah slot kantin menjadi: ' . $nilaiBaru);
        echo json_encode(['status' => 'success', 'msg' => 'Slot kantin berhasil diubah menjadi ' . $nilaiBaru . '.', 'slot' => $nilaiBaru, 'totalToko' => $currentTokoCount]);
        exit;
    }

    if ($action === 'kantin_geser_urutan_ajax') {
        if (!$isAdminSuper) {
            echo json_encode(['status' => 'error', 'msg' => 'Akses Ilegal!']);
            exit;
        }
        $id = (int) ($_POST['id_toko'] ?? 0);
        $arah = $_POST['arah'] ?? '';

        if ($id && ($arah === 'up' || $arah === 'down')) {
            $res = mysqli_query($conn, "SELECT id_toko, urutan FROM toko WHERE deleted_at IS NULL ORDER BY urutan ASC, id_toko ASC");
            $canteens = mysqli_fetch_all($res, MYSQLI_ASSOC);

            foreach ($canteens as $idx => $c) {
                $canteens[$idx]['urutan'] = $idx;
                mysqli_query($conn, "UPDATE toko SET urutan = $idx WHERE id_toko = " . $c['id_toko']);
            }

            $targetIdx = -1;
            foreach ($canteens as $idx => $c) {
                if ((int) $c['id_toko'] === $id) {
                    $targetIdx = $idx;
                    break;
                }
            }

            if ($targetIdx !== -1) {
                $swapIdx = ($arah === 'up') ? $targetIdx - 1 : $targetIdx + 1;
                if ($swapIdx >= 0 && $swapIdx < count($canteens)) {
                    $targetId = $canteens[$targetIdx]['id_toko'];
                    $swapId = $canteens[$swapIdx]['id_toko'];

                    mysqli_query($conn, "UPDATE toko SET urutan = $swapIdx WHERE id_toko = $targetId");
                    mysqli_query($conn, "UPDATE toko SET urutan = $targetIdx WHERE id_toko = $swapId");

                    catatLog($conn, 'Geser Urutan Kantin', 'Menggeser kantin ID ' . $targetId . ' ke arah ' . $arah);
                    echo json_encode(['status' => 'success', 'msg' => 'Urutan kantin berhasil diperbarui.', 'swappedWith' => (int) $swapId]);
                    exit;
                }
            }
            echo json_encode(['status' => 'error', 'msg' => 'Gagal memindahkan urutan.']);
            exit;
        }
        echo json_encode(['status' => 'error', 'msg' => 'Parameter tidak valid.']);
        exit;
    }
}


/* ══ ACTIONS ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'dashboard';

    if ($action === 'admin_profil') {
        $nama_baru = trim($_POST['nama_baru'] ?? '');
        $foto_baru = null;

        catatLog($conn, 'Update Profil', 'Admin mengubah nama profil menjadi: ' . $nama_baru);
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
            catatLog($conn, 'Update Profil', 'Mengubah nama profil / foto admin');
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
                catatLog($conn, 'Update Password', 'Admin mengubah password');
                $feedback = ['type' => 'success', 'msg' => 'Password berhasil diubah.'];
            }
        }

        $activeSection = $_POST['_section'] ?? 'dashboard';
    }

    /* Tambah admin - PROTEKSI SUPER ADMIN ONLY */
    if ($action === 'admin_tambah') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang berhak menambahkan Admin baru!");
        }

        $nama = trim($_POST['nama'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $kode = trim($_POST['kode_aktivasi'] ?? '');
        if ($kode === '') {
            $kode = generateKodeAktivasi();
        }

        if ($nama === '' || $pass === '' || $kode === '') {
            $feedback = ['type' => 'error', 'msg' => 'Nama, password, dan kode aktivasi wajib diisi.'];
        } else {
            $n = mysqli_real_escape_string($conn, $nama);
            $h = md5($pass);
            $k = mysqli_real_escape_string($conn, $kode);

            // Cek apakah kode aktivasi sudah pernah dipakai
            $cek_kode = mysqli_query($conn, "SELECT id_admin FROM admin WHERE kode_aktivasi = '$k' AND deleted_at IS NULL LIMIT 1");
            if (mysqli_num_rows($cek_kode) > 0) {
                $feedback = ['type' => 'error', 'msg' => 'Kode aktivasi sudah digunakan oleh administrator lain!'];
            } else {
                // Kolom role_level dipaksa bernilai 2 (Admin Biasa)
                if (mysqli_query($conn, "INSERT INTO admin (nama, password, role_level, kode_aktivasi) VALUES ('$n', '$h', 2, '$k')")) {
                    catatLog($conn, 'Tambah Admin', 'Menambahkan admin baru bernama: ' . $nama);
                    $feedback = ['type' => 'success', 'msg' => "Admin <strong>" . htmlspecialchars($nama) . "</strong> ditambahkan.", "extra" => "Kode Aktivasi: <code>$kode</code>"];
                } else {
                    $feedback = ['type' => 'error', 'msg' => 'Gagal menambahkan admin.'];
                }
            }
        }
    }

    /* Toggle status admin - PROTEKSI SUPER ADMIN ONLY & ANTI SENGGOL SUPER ADMIN */
    if ($action === 'admin_toggle') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang bisa mengaktifkan/menonaktifkan Admin!");
        }

        $id = (int) ($_POST['id'] ?? 0);

        // 🔥 PAGAR UTAMA: Cek level target di database sebelum eksekusi
        $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_level FROM admin WHERE id_admin=$id"));
        if ((int) ($target['role_level'] ?? 2) === 1) {
            die("Akses Ditolak: Akun sesama Super Admin tidak boleh dinonaktifkan!");
        }

        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['aktif', 'tidak_digunakan'])) {
            $new = $status === 'aktif' ? 'tidak_digunakan' : 'aktif';
            mysqli_query($conn, "UPDATE admin SET status='$new' WHERE id_admin=$id");
            catatLog($conn, 'Toggle Status Admin', 'Mengubah status ID Admin ' . $id . ' menjadi ' . $new);
        }
        header("Location: ?section=admin");
        exit;
    }

    /* Reset password admin - PROTEKSI ANTI SENGGOL SUPER ADMIN */
    if ($action === 'admin_reset') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang diizinkan mereset password Admin!");
        }

        $id = (int) ($_POST['id'] ?? 0);

        // 🔥 PAGAR UTAMA: Cek level target sebelum reset
        $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_level FROM admin WHERE id_admin=$id"));
        if ((int) ($target['role_level'] ?? 2) === 1) {
            die("Akses Ditolak: Anda tidak berhak mereset password sesama Super Admin!");
        }

        $pw_reset = trim($_POST['pw_reset'] ?? '');
        if ($id && $pw_reset !== '') {
            $hash = md5($pw_reset);
            $nama_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM admin WHERE id_admin=$id"))['nama'] ?? '';
            mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id_admin=$id");
            catatLog($conn, 'Reset Password Admin', 'Mereset paksa password untuk admin: ' . $nama_admin);
            $feedback = ['type' => 'success', 'msg' => "Password <strong>" . htmlspecialchars($nama_admin) . "</strong> berhasil direset."];
        } elseif ($id && $pw_reset === '') {
            $feedback = ['type' => 'error', 'msg' => 'Password baru wajib diisi.'];
        }
    }

    /* Soft Delete Admin - PROTEKSI SUPER ADMIN ONLY */
    /* Soft Delete Admin - PROTEKSI ANTI SENGGOL SUPER ADMIN */
    if ($action === 'admin_soft_delete') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang berhak menambahkan/menghapus data Admin!");
        }

        $id = (int) ($_POST['id'] ?? 0);

        // 🔥 PAGAR UTAMA: Cek level target sebelum delete
        $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_level FROM admin WHERE id_admin=$id"));
        if ((int) ($target['role_level'] ?? 2) === 1) {
            die("Akses Ditolak: Struktur sistem melarang penghapusan sesama Super Admin!");
        }

        if ($id) {
            $nama_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM admin WHERE id_admin=$id"))['nama'] ?? '';
            mysqli_query($conn, "UPDATE admin SET deleted_at = NOW() WHERE id_admin=$id");
            catatLog($conn, 'Soft Delete Admin', 'Menghapus sementara akun admin: ' . $nama_admin);
            $feedback = ['type' => 'success', 'msg' => "Admin <strong>" . htmlspecialchars($nama_admin) . "</strong> berhasil dipindahkan ke arsip sampah."];
        }
        header("Location: ?section=admin");
        exit;
    }

    if ($action === 'kantin_ubah_slot') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang berhak mengubah slot kantin!");
        }
        $tipe = $_POST['tipe'] ?? '';
        if ($tipe === 'tambah') {
            mysqli_query($conn, "UPDATE pengaturan SET nilai = nilai + 1 WHERE kunci = 'slot_kantin'");
            catatLog($conn, 'Ubah Slot Kantin', 'Super Admin menambah slot kantin');
            $feedback = ['type' => 'success', 'msg' => 'Slot kantin berhasil ditambah.'];
        } elseif ($tipe === 'kurang') {
            $currSlot = 10;
            $qSlot = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'slot_kantin' LIMIT 1");
            if ($qSlot && mysqli_num_rows($qSlot) > 0) {
                $currSlot = (int) mysqli_fetch_assoc($qSlot)['nilai'];
            }
            $currentTokoCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE deleted_at IS NULL"))['c'];
            if ($currSlot > $currentTokoCount) {
                mysqli_query($conn, "UPDATE pengaturan SET nilai = nilai - 1 WHERE kunci = 'slot_kantin'");
                catatLog($conn, 'Ubah Slot Kantin', 'Super Admin mengurangi slot kantin');
                $feedback = ['type' => 'success', 'msg' => 'Slot kantin berhasil dikurangi.'];
            } else {
                $feedback = ['type' => 'error', 'msg' => 'Gagal: Jumlah slot tidak boleh kurang dari jumlah stand kantin yang tersedia (' . $currentTokoCount . ').'];
            }
        }
        if ($feedback) {
            $_SESSION['feedback'] = $feedback;
        }
        header("Location: ?section=kantin");
        exit;
    }

    if ($action === 'kantin_geser_urutan') {
        if (!$isAdminSuper) {
            die("Akses Ilegal: Hanya Super Admin yang berhak memindahkan urutan kantin!");
        }
        $id = (int) ($_POST['id_toko'] ?? 0);
        $arah = $_POST['arah'] ?? '';
        
        if ($id && ($arah === 'up' || $arah === 'down')) {
            $res = mysqli_query($conn, "SELECT id_toko, urutan FROM toko WHERE deleted_at IS NULL ORDER BY urutan ASC, id_toko ASC");
            $canteens = mysqli_fetch_all($res, MYSQLI_ASSOC);
            
            // Normalize/re-index urutan to be 0, 1, 2, ...
            foreach ($canteens as $idx => $c) {
                $canteens[$idx]['urutan'] = $idx;
                mysqli_query($conn, "UPDATE toko SET urutan = $idx WHERE id_toko = " . $c['id_toko']);
            }
            
            $targetIdx = -1;
            foreach ($canteens as $idx => $c) {
                if ((int)$c['id_toko'] === $id) {
                    $targetIdx = $idx;
                    break;
                }
            }
            
            if ($targetIdx !== -1) {
                $swapIdx = ($arah === 'up') ? $targetIdx - 1 : $targetIdx + 1;
                if ($swapIdx >= 0 && $swapIdx < count($canteens)) {
                    $targetId = $canteens[$targetIdx]['id_toko'];
                    $swapId = $canteens[$swapIdx]['id_toko'];
                    
                    mysqli_query($conn, "UPDATE toko SET urutan = $swapIdx WHERE id_toko = $targetId");
                    mysqli_query($conn, "UPDATE toko SET urutan = $targetIdx WHERE id_toko = $swapId");
                    
                    catatLog($conn, 'Geser Urutan Kantin', 'Menggeser kantin ID ' . $targetId . ' ke arah ' . $arah);
                    $feedback = ['type' => 'success', 'msg' => 'Urutan kantin berhasil diperbarui.'];
                }
            }
        }
        if ($feedback) {
            $_SESSION['feedback'] = $feedback;
        }
        header("Location: ?section=kantin");
        exit;
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
        $backSection = $_POST['_section'] ?? 'kantin';
        if (!in_array($backSection, ['kantin', 'tambah_akun'], true)) {
            $backSection = 'kantin';
        }
        $selToko = (int) ($_POST['_selected_toko'] ?? 0);
        if ($backSection === 'tambah_akun') {
            header('Location: ?section=tambah_akun');
        } else {
            header('Location: ?section=kantin' . ($selToko ? "&toko=$selToko" : ''));
        }
        exit;
    }

    // ── ACTION PEMBELI ──
    // ── ACTION PEMBELI ──
    if (str_starts_with($action, 'pembeli_')) {
        require __DIR__ . '/actions/pembeli.php';
        if ($feedback)
            $_SESSION['feedback'] = $feedback;
        $backSection = $_POST['_section'] ?? 'pembeli';
        // pastikan hanya section yang valid
        $allowedSections = ['pembeli', 'tambah_akun'];
        if (!in_array($backSection, $allowedSections))
            $backSection = 'pembeli';
        header("Location: ?section=$backSection");
        exit;
    }

    // ── ACTION TOOLS (Soft/Hard Delete, Restore) - PROTEKSI BACKEND ──
    if (str_starts_with($action, 'tools_')) {
        // Blokir Admin Biasa jika mencoba melakukan Soft/Hard Delete atau Restore di file tools.php
        if (!$isAdminSuper) {
            die("Akses Ditolak: Anda tidak memiliki wewenang Super Admin untuk melakukan operasi data ini.");
        }
        require __DIR__ . '/actions/tools.php';
        if ($feedback)
            $_SESSION['feedback'] = $feedback;
        header("Location: ?section=tools");
        exit;
    }

    if (str_starts_with($action, 'kendala_')) {
        $id = (int) ($_POST['id_laporan'] ?? 0);
        if ($id) {
            if ($action === 'kendala_proses') {
                mysqli_query($conn, "UPDATE laporan_kendala SET status='proses' WHERE id_laporan=$id");
                catatLog($conn, 'Update Laporan Kendala', 'Laporan ID ' . $id . ' ditandai sedang diproses');
                $feedback = ['type' => 'success', 'msg' => 'Laporan ditandai sedang diproses.'];
            }
            if ($action === 'kendala_selesai') {
                mysqli_query($conn, "UPDATE laporan_kendala SET status='selesai' WHERE id_laporan=$id");
                catatLog($conn, 'Update Laporan Kendala', 'Laporan ID ' . $id . ' ditandai selesai');
                $feedback = ['type' => 'success', 'msg' => 'Laporan ditandai selesai.'];
            }
            if ($action === 'kendala_hapus') {
                mysqli_query($conn, "DELETE FROM laporan_kendala WHERE id_laporan=$id");
                catatLog($conn, 'Delete Laporan Kendala', 'Laporan ID ' . $id . ' dihapus');
                $feedback = ['type' => 'success', 'msg' => 'Laporan berhasil dihapus.'];
            }
        }
        if ($feedback)
            $_SESSION['feedback'] = $feedback;

        // Bawa filter_kendala dari POST biar tidak reset
        $filterKembali = $_POST['filter_kendala'] ?? 'aktif';
        header("Location: ?section=dashboard&filter_kendala=$filterKembali");
        exit;
    }
}

/* ══ DATA ══ */
// Dashboard
$totalTransaksi = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan)=CURDATE()"))['c'];
$totalPembeli = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT (SELECT COUNT(*) FROM murid WHERE deleted_at IS NULL)+(SELECT COUNT(*) FROM guru WHERE deleted_at IS NULL) as c"))['c'];
$tokoAktif = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE status='buka' AND deleted_at IS NULL"))['c'];
$totalToko = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM toko WHERE deleted_at IS NULL"))['c'];
$totalMenu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM menu WHERE tersedia=1 AND deleted_at IS NULL"))['c'];

$slotKantin = 10;
$qSlot = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'slot_kantin' LIMIT 1");
if ($qSlot && mysqli_num_rows($qSlot) > 0) {
    $slotKantin = (int) mysqli_fetch_assoc($qSlot)['nilai'];
}

$grafikLabels = [];
$grafikValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $grafikLabels[] = date('d/m', strtotime("-{$i} days"));
    $grafikValues[] = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pesanan WHERE DATE(waktu_pesan)='$date'"))['c'];
}
$proporsiRaw = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT t.nama_toko, COUNT(p.id_pesanan) as total 
     FROM toko t 
     LEFT JOIN pesanan p ON p.id_toko=t.id_toko 
     WHERE t.deleted_at IS NULL
     GROUP BY t.id_toko, t.nama_toko 
     ORDER BY total DESC LIMIT 5"
), MYSQLI_ASSOC);
$proporsiTotal = array_sum(array_column($proporsiRaw, 'total'));
$proporsiLabels = array_column($proporsiRaw, 'nama_toko');
$proporsiValues = $proporsiTotal > 0 ? array_map('intval', array_column($proporsiRaw, 'total')) : array_fill(0, count($proporsiRaw), 1);

// Admin
$admins = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM admin WHERE deleted_at IS NULL ORDER BY id_admin ASC"), MYSQLI_ASSOC);
$totalAdmin = count($admins);
$aktifCount = count(array_filter($admins, fn($a) => $a['status'] === 'aktif'));
// Tools
require __DIR__ . '/sections/tools_data.php';
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

    <script>
        const pageMeta = {
            dashboard: { title: 'Dashboard', sub: 'Monitor semua transaksi dan keuangan E-Kantin' },
            admin: { title: 'Admin Management', sub: 'Kelola akun administrator E-Kantin' },
            kantin: { title: 'Kelola Kantin', sub: 'Manajemen toko dan menu kantin' },
            penjual: { title: 'Kelola Penjual', sub: 'Manajemen akun penjual' },
            pembeli: { title: 'Kelola Pembeli', sub: 'Data murid dan guru terdaftar' },
            tambah_akun: { title: 'Tambah Akun Pembeli', sub: 'Tambah akun murid atau guru baru' },
            tools: { title: 'Tools & Log', sub: 'Import data dan log aktivitas sistem' },
            chat: { title: 'Chat', sub: 'Hubungi dan balas pesan dari murid atau toko kantin' },
        };

        function switchSection(name) {
            if (!name) name = 'dashboard';

            // Ambil element secara dinamis, jika belum ke-render di HTML kita skip dulu sementara
            const targetSec = document.getElementById('section-' + name);
            if (!targetSec) return;

            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-link[data-section]').forEach(l => l.classList.remove('active'));

            targetSec.classList.add('active');

            const targetLink = document.querySelector('.nav-link[data-section="' + name + '"]');
            if (targetLink) targetLink.classList.add('active');

            const meta = pageMeta[name] || pageMeta['dashboard'];
            const titleEl = document.getElementById('pageTitle');
            const subEl = document.getElementById('pageSubtitle');
            if (titleEl) titleEl.textContent = meta.title;
            if (subEl) subEl.textContent = meta.sub;

            // Update URL browser
            try {
                const currentParams = new URLSearchParams(window.location.search);
                currentParams.set('section', name);
                if (name !== 'chat') currentParams.delete('lawan');
                history.replaceState(null, '', '?' + currentParams.toString());
            } catch (e) { }
        }
    </script>

    <style>
        /* CSS Tambahan untuk Animasi & Desain Dropdown Notifikasi Kendala */
        @keyframes fadeInNotif {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notif-item:hover {
            background-color: rgba(121, 183, 117, 0.06) !important;
        }

        .notif-list::-webkit-scrollbar {
            width: 5px;
        }

        .notif-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notif-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* ── RESPONSIVE DROPDOWN NOTIFIKASI (HP) ── */
        @media (max-width: 768px) {
            .topbar {
                position: relative;
                /* Menjadikan kotak hijau (topbar) sebagai patokan ukuran */
            }

            .notif-wrapper {
                position: static !important;
                /* Melepas paksa ikatan dari ikon lonceng */
            }

            .notif-dropdown {
                position: absolute !important;
                top: calc(100% + 5px) !important;
                /* Muncul tepat 5px di bawah kotak hijau */
                left: 0 !important;
                /* Sejajar persis dengan ujung kiri topbar */
                right: 0 !important;
                /* Sejajar persis dengan ujung kanan topbar */
                width: 100% !important;
                /* Memenuhi lebar topbar dengan sempurna */
                z-index: 9999 !important;
            }
        }
    </style>
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
                <img src="../../assets/img/logo_ekantin_hijau.png" class="logo-badge" onerror="this.style.display='none'">
                <div class="logo-text">E-Kantin</div>
            </div>
            <button class="btn-close-sidebar" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-link active" data-section="dashboard" onclick="switchSection('dashboard')"><i
                    class="fa-solid fa-border-all"></i> Dashboard</button>
            <?php if ($isAdminSuper): ?>
            <button class="nav-link" data-section="admin" onclick="switchSection('admin')"><i
                    class="fa-solid fa-user"></i> Admin</button>
            <?php endif; ?>
            <button class="nav-link" data-section="kantin" onclick="switchSection('kantin')"><i
                    class="fa-solid fa-store"></i> Kantin</button>
            <button class="nav-link" data-section="penjual" onclick="switchSection('penjual')"><i
                    class="fa-solid fa-user-tag"></i> Penjual</button>
            <button class="nav-link" data-section="pembeli" onclick="switchSection('pembeli')"><i
                    class="fa-solid fa-users"></i> Pembeli</button>
            <button class="nav-link" data-section="tambah_akun" onclick="switchSection('tambah_akun')">
                <i class="fa-solid fa-user-plus"></i> Tambah Akun
            </button>
            <button class="nav-link" data-section="chat" onclick="switchSection('chat')"><i
                    class="fa-solid fa-comments"></i> Chat
            </button>
        </nav>
        <div class="sidebar-bottom">
            <button class="nav-link" data-section="tools" onclick="switchSection('tools')">
                <i class="fa-solid fa-wrench"></i> Tools & Log
            </button>
            <button class="nav-link logout" onclick="document.getElementById('modalLogout').style.display='flex'">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Log out
            </button>
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
                    <div class="notif-wrapper" style="position: relative; display: inline-block;">
                        <button type="button" class="btn-notif" onclick="toggleNotifDropdown(event)"
                            style="position: relative;">
                            <i class="fa-solid fa-bell"></i>
                            <?php if (!empty($kendalaNotif)): ?>
                                <span class="notif-dot" id="notifDot"></span>
                            <?php endif; ?>
                        </button>

                        <div class="notif-dropdown" id="notifDropdown"
                            style="display: none; position: absolute; right: 0; top: 45px; width: 340px; background: var(--card-bg, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; overflow: hidden;">
                            <div
                                style="padding: 12px 16px; border-bottom: 1px solid var(--border, #e5e7eb); display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text);">Laporan
                                    Kendala Web</h3>
                                <span
                                    style="font-size: 11px; background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                    <?= count($kendalaNotif) ?> Laporan
                                </span>
                            </div>

                            <div class="notif-list" style="max-height: 320px; overflow-y: auto;">
                                <?php if (empty($kendala)): ?>
                                    <div
                                        style="padding: 24px; text-align: center; color: var(--text-muted, #9ca3af); font-size: 12px;">
                                        <i class="fa-solid fa-circle-check"
                                            style="font-size: 22px; display: block; margin-bottom: 8px; color: #79b775;"></i>
                                        Sistem aman! Belum ada laporan kendala masuk.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($kendalaNotif as $knd):
                                        $role = strtolower($knd['user_role'] ?? '');
                                        $badgeBg = ($role === 'penjual') ? '#e0f2fe' : (($role === 'guru') ? '#e8f0fe' : '#e2f5e1');
                                        $badgeColor = ($role === 'penjual') ? '#0369a1' : (($role === 'guru') ? '#1a56db' : '#2d7a2d');
                                        ?>
                                        <div class="notif-item"
                                            style="padding: 14px 16px; border-bottom: 1px solid var(--border, #e5e7eb); display: flex; gap: 12px; cursor: pointer; transition: background 0.2s;"
                                            onclick="switchSection('dashboard')">
                                            <div style="width: 8px; height: 8px; background: <?= ($knd['status'] ?? '') === 'menunggu' ? '#ef4444' : '#f59e0b' ?>; border-radius: 50%; margin-top: 5px; flex-shrink: 0;"
                                                title="Status: <?= $knd['status'] ?>"></div>

                                            <div style="flex: 1;">
                                                <div
                                                    style="font-size: 12px; font-weight: 700; color: var(--text); line-height: 1.4; margin-bottom: 4px;">
                                                    <?= htmlspecialchars($knd['judul_kendala'] ?? '') ?>
                                                </div>
                                                <div
                                                    style="font-size: 11px; color: var(--text-muted, #6b7280); display: flex; align-items: center; gap: 6px;">
                                                    <span><?= htmlspecialchars($knd['user_nama'] ?? '') ?></span>
                                                    <span
                                                        style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-size: 9px; padding: 1px 6px; border-radius: 10px; font-weight: 600; text-transform: uppercase;">
                                                        <?= $role ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 10px; color: #9ca3af; margin-top: 6px;">
                                                    <i class="fa-regular fa-clock"></i>
                                                    <?= date('d M, H:i', strtotime($knd['dibuat_pada'] ?? 'now')) ?> WIB
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <a href="#"
                                onclick="switchSection('dashboard'); document.getElementById('notifDropdown').style.display='none'; return false;"
                                style="display: block; text-align: center; padding: 11px; font-size: 12px; color: #79b775; font-weight: 600; text-decoration: none; border-top: 1px solid var(--border, #e5e7eb); background: #f9fafb;">
                                Lihat Laporan
                            </a>
                        </div>
                    </div>

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
                            <div class="user-role"><?= $isAdminSuper ? 'Super Admin' : 'Admin' ?></div>
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

            <div class="section active" id="section-dashboard">
                <?php require __DIR__ . '/sections/dashboard.php'; ?>
            </div>

            <?php if ($isAdminSuper): ?>
            <div class="section" id="section-admin">
                <?php require __DIR__ . '/sections/admin.php'; ?>
            </div>
            <?php endif; ?>

            <div class="section" id="section-kantin">

                <?php
                require __DIR__ . '/sections/kantin_data.php';
                require __DIR__ . '/sections/kantin.php';
                ?>
            </div>

            <div class="section" id="section-penjual">
                <?php
                require __DIR__ . '/sections/penjual_data.php';
                require __DIR__ . '/sections/penjual.php';
                ?>
            </div>

            <div class="section" id="section-pembeli">
                <?php
                require __DIR__ . '/sections/pembeli_data.php';
                require __DIR__ . '/sections/pembeli.php';
                ?>
            </div>

            <div class="section" id="section-tambah_akun">
                <?php
                require __DIR__ . '/sections/pembeli_data.php'; // butuh $semuaKelas
                require __DIR__ . '/sections/penjual_data.php'; // butuh $semuaKelas
                require __DIR__ . '/sections/tambah_akun.php';
                ?>
            </div>

            <div class="section" id="section-chat">
                <?php require __DIR__ . '/sections/chat.php'; ?>
            </div>

            <div class="section" id="section-tools">
                <?php require __DIR__ . '/sections/tools.php'; ?>
            </div>
        </div>

    </div>
    </div>

<div id="modalLogout" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div style="background:#fff; border-radius:16px; padding:32px 28px; max-width:340px; width:90%; text-align:center; font-family:'Poppins',sans-serif; animation:muncul 0.3s ease-out;">
        <h3 style="font-size:18px; font-weight:700; margin-bottom:8px; color:#111;">Apakah anda yakin ingin logout?</h3>
        <p style="font-size:13px; color:#888; margin-bottom:24px;">Kamu akan di arahkan keluar dan harus login kembali.</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="document.getElementById('modalLogout').style.display='none'"
                style="padding:10px 24px; border-radius:10px; border:1.5px solid #ddd; background:#fff; font-family:'Poppins',sans-serif; font-size:14px; cursor:pointer; color:#555;">
                Batal
            </button>
            <a href="../../auth/logout.php"
                style="padding:10px 24px; border-radius:10px; background:#e45c5cff; color:#fff; font-family:'Poppins',sans-serif; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;">
                Keluar
            </a>
        </div>
    </div>
</div>
</body>

<script>
    /* ── sidebar ── */
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('show');
        } else {
            if (sidebar) {
                const hidden = sidebar.style.marginLeft === '-256px';
                sidebar.style.marginLeft = hidden ? '0' : '-256px';
                const mainEl = document.getElementById('main');
                if (mainEl) mainEl.style.width = hidden ? '' : '100%';
            }
        }
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }
    window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

    /* ── section switcher ── */


    /* Inisialisasi awal saat halaman pertama kali di-load */
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        let initSection = urlParams.get('section') || '<?= htmlspecialchars($activeSection ?? "dashboard") ?>';

        // Bersihkan string jikalau ada sisa parameter nempel
        initSection = initSection.split('&')[0];

        // Jalankan switch otomatis ke halaman terakhir yang dibuka
        switchSection(initSection);

        // Auto-generate activation code on load
        if (typeof generateAutoKode === 'function') {
            generateAutoKode();
        }
    });

    // MODIFIKASI: Filter redirect toko agar tidak mengganggu section chat
    if (window.location.search.includes('toko=') && !window.location.search.includes('section=')) {
        history.replaceState(null, '', '?section=kantin');
    }

    /* aktifkan section dari URL / POST */
    const initSection = '<?= htmlspecialchars($activeSection ?? "dashboard") ?>';
    if (initSection !== 'dashboard' && document.getElementById('section-' + initSection)) {
        switchSection(initSection);
    }

    /* ── charts ── */
    const grafikLabels = <?= json_encode($grafikLabels ?? []) ?>;
    const grafikValues = <?= json_encode($grafikValues ?? []) ?>;
    const proporsiLabels = <?= json_encode($proporsiLabels ?? []) ?>;
    const proporsiValues = <?= json_encode($proporsiValues ?? []) ?>;
    const greens = ['#79b775', '#8cd48a', '#b5d7b4', '#4a9e4a', '#2d7a2d'];

    const lineChartEl = document.getElementById('lineChart');
    if (lineChartEl) {
        const ctxL = lineChartEl.getContext('2d');
        const grad = ctxL.createLinearGradient(0, 0, 0, 180);
        grad.addColorStop(0, 'rgba(121,183,117,.35)');
        grad.addColorStop(1, 'rgba(121,183,117,0)');
        new Chart(ctxL, { type: 'line', data: { labels: grafikLabels, datasets: [{ data: grafikValues, borderColor: '#79b775', borderWidth: 2.5, backgroundColor: grad, fill: true, tension: 0.4, pointBackgroundColor: '#79b775', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } }, y: { grid: { color: '#e5e7eb' }, ticks: { display: false }, beginAtZero: true, border: { display: false } } } } });
    }

    const ctxD = document.getElementById('donutChart');
    if (ctxD) {
        new Chart(ctxD, {
            type: 'doughnut',
            data: { labels: proporsiLabels, datasets: [{ data: proporsiValues, backgroundColor: greens.slice(0, proporsiLabels.length), borderWidth: 3, borderColor: '#f8f9fa' }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { display: false } } }
        });
        const legend = document.getElementById('legend');
        if (legend) {
            legend.innerHTML = ''; // reset container
            proporsiLabels.forEach((label, i) => {
                legend.innerHTML += `<div class="legend-item"><span class="legend-dot" style="background:${greens[i]}"></span>${label}</div>`;
            });
        }
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
        const preview = document.getElementById('kodePreview');
        const hidden = document.getElementById('kodeHidden');
        if (preview) preview.textContent = k;
        if (hidden) hidden.value = k;
    }
    function generateAutoKode() {
        const k = randomKode();
        const inp = document.getElementById('inputKode');
        if (inp) inp.value = k;
    }

    /* ── toggle password (sidebar/tabel bawaan) ── */
    function togglePass() {
        const inp = document.getElementById('inputPass');
        const ico = document.getElementById('eyeIcon');
        if (inp && ico) {
            inp.type = inp.type === 'password' ? 'text' : 'password';
            ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }
    }

    /* ── reveal kode di tabel ── */
    function revealKode(id) {
        const el = document.getElementById('kode-' + id);
        const eye = document.getElementById('eye-' + id);
        if (!el || !eye) return;
        if (el.dataset.hidden === '1') {
            el.textContent = el.dataset.plain;
            el.dataset.hidden = '0';
            eye.className = 'fa-solid fa-eye-slash';
        } else {
            const p = el.dataset.plain || '';
            el.textContent = p.substring(0, 2) + '•'.repeat(Math.max(0, p.length - 2));
            el.dataset.hidden = '1';
            eye.className = 'fa-solid fa-eye';
        }
    }

    /* ── modal profil ── */
    function bukaProfil() {
        const modal = document.getElementById('modalProfil');
        if (modal) modal.style.display = 'flex';

        const activeLink = document.querySelector('.nav-link.active[data-section]');
        const inputSection = document.getElementById('profilSection');
        if (inputSection) {
            inputSection.value = activeLink?.dataset?.section || 'dashboard';
        }
    }
    function tutupProfil() {
        const modal = document.getElementById('modalProfil');
        if (modal) modal.style.display = 'none';
    }
    function revealModalKode() {
        const el = document.getElementById('modalKode');
        const eyeBtn = document.getElementById('modalKodeEye');
        if (!el || !eyeBtn) return;
        const eye = eyeBtn.querySelector('i');
        if (!eye) return;

        if (el.dataset.hidden === '1') {
            el.textContent = el.dataset.plain;
            el.dataset.hidden = '0';
            eye.className = 'fa-solid fa-eye-slash';
        } else {
            const p = el.dataset.plain || '';
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
            setTimeout(() => { if (feedbackEl.parentNode) feedbackEl.remove(); }, 500);
        }, 4000);
    }

    /* ── toggle password input dinamis ── */
    function togglePw(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (!inp || !ico) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
        ico.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    }

    /* ── modal foto profil admin ── */
    function bukaFotoAdmin(src) {
        const img = document.getElementById('modalFotoAdminImg');
        const modal = document.getElementById('modalFotoAdmin');
        if (img) img.src = src;
        if (modal) modal.classList.add('show');
    }
    function tutupFotoAdmin() {
        const modal = document.getElementById('modalFotoAdmin');
        if (modal) modal.classList.remove('show');
    }

    /* ── JavaScript Operasional Dropdown Notifikasi Kendala ── */
    const notifDropdown = document.getElementById('notifDropdown');
    const notifDot = document.getElementById('notifDot');

    function toggleNotifDropdown(event) {
        if (event) event.stopPropagation();
        if (!notifDropdown) return;

        if (notifDropdown.style.display === 'none' || notifDropdown.style.display === '') {
            notifDropdown.style.display = 'block';
            notifDropdown.style.animation = 'fadeInNotif 0.2s ease-out';
        } else {
            notifDropdown.style.display = 'none';
        }
    }

    // Tutup dropdown otomatis jika klik di luar area komponen notifikasi
    document.addEventListener('click', function (event) {
        if (notifDropdown && !event.target.closest('.notif-wrapper')) {
            notifDropdown.style.display = 'none';
        }
    });

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

</html>