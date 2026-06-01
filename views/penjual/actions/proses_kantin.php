<?php
// views/penjual/actions/proses_kantin.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi agar yang mengeksekusi file ini murni user ber-role owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    exit('Unauthorized access!');
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/toko_foto.php';

$idToko = (int) ($_SESSION['id_toko'] ?? 0);
if ($idToko === 0) {
    $penjualId = (int) ($_SESSION['user_id'] ?? 0);
    $rToko = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId AND status='aktif' ORDER BY id DESC LIMIT 1"
    ));
    $idToko = (int) ($rToko['id_toko'] ?? 0);
    $_SESSION['id_toko'] = $idToko;
}

$action = $_POST['action'] ?? '';

// Deteksi server dinamis untuk path redirect
$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

$uploadFileDir = tokoFotoImgRoot() . '/';
$uploadBannerDir = tokoFotoImgRoot() . '/banner/';
if (!is_dir($uploadBannerDir)) {
    mkdir($uploadBannerDir, 0755, true);
}

// Ambil nama toko untuk pencatatan log secara aman
$nama_toko = '';
if ($idToko > 0) {
    $q_toko_name = mysqli_query($conn, "SELECT nama_toko FROM toko WHERE id_toko = $idToko LIMIT 1");
    if ($q_toko_name && $r_toko_name = mysqli_fetch_assoc($q_toko_name)) {
        $nama_toko = $r_toko_name['nama_toko'];
    }
}

// ════════════════════════════════════════════════════════════
// 1. UPDATE KANTIN (NAMA, STATUS, DESKRIPSI & FOTO KANTIN)
// ════════════════════════════════════════════════════════════
if ($action === 'update_kantin_full') {
    $nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko'] ?? '');
    $status_toko = $_POST['status_toko'] ?? 'buka';
    $deskripsi_singkat = mysqli_real_escape_string($conn, $_POST['deskripsi_singkat'] ?? '');
    $deskripsi_panjang = mysqli_real_escape_string($conn, $_POST['deskripsi_panjang'] ?? '');

    $qLama = mysqli_query($conn, "SELECT `foto_toko`, `qris` FROM `toko` WHERE `id_toko` = $idToko LIMIT 1");
    $dataLama = mysqli_fetch_assoc($qLama);
    $nama_foto_final = $dataLama['foto_toko'] ?? '';
    $nama_qris_final = $dataLama['qris'] ?? '';

    $upload = tokoFotoProsesUpload($idToko, $_FILES['foto_toko'] ?? []);
    if ($upload['attempted']) {
        if ($upload['error']) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => $upload['error']];
            header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
            exit;
        }
        if ($upload['filename']) {
            $nama_foto_final = $upload['filename'];
        }
    }

    // QRIS Hapus Check
    if (isset($_POST['hapus_qris']) && $_POST['hapus_qris'] == '1') {
        if (!empty($nama_qris_final)) {
            $path_qris_lama = __DIR__ . '/../../../assets/img/qris/' . $nama_qris_final;
            if (file_exists($path_qris_lama)) {
                @unlink($path_qris_lama);
            }
        }
        $nama_qris_final = '';
    }

    // QRIS Upload Check
    if (isset($_FILES['qris_toko']) && $_FILES['qris_toko']['error'] === UPLOAD_ERR_OK) {
        $qrisTmpPath = $_FILES['qris_toko']['tmp_name'];
        $qrisName = $_FILES['qris_toko']['name'];
        $qrisExt = strtolower(pathinfo($qrisName, PATHINFO_EXTENSION));
        $allowedQrisExts = ['jpg', 'jpeg', 'png', 'webp'];
        $maxQrisSize = 2097152; // 2MB

        if (in_array($qrisExt, $allowedQrisExts) && $_FILES['qris_toko']['size'] <= $maxQrisSize) {
            if (!empty($nama_qris_final)) {
                $path_qris_lama = __DIR__ . '/../../../assets/img/qris/' . $nama_qris_final;
                if (file_exists($path_qris_lama)) {
                    @unlink($path_qris_lama);
                }
            }

            $uploadQrisDir = __DIR__ . '/../../../assets/img/qris/';
            if (!is_dir($uploadQrisDir)) {
                mkdir($uploadQrisDir, 0755, true);
            }

            $newQrisName = 'qris_' . $idToko . '_' . time() . '.' . $qrisExt; // add timestamp to avoid browser caching
            if (move_uploaded_file($qrisTmpPath, $uploadQrisDir . $newQrisName)) {
                $nama_qris_final = $newQrisName;
            }
        }
    }

    $qris_db_val = ($nama_qris_final === '') ? "NULL" : "'$nama_qris_final'";

    $queryUpdate = "UPDATE `toko` SET 
                    `nama_toko` = '$nama_toko', 
                    `status` = '$status_toko', 
                    `deskripsi` = '$deskripsi_singkat', 
                    `deskripsi_panjang` = '$deskripsi_panjang', 
                    `foto_toko` = '$nama_foto_final',
                    `qris` = $qris_db_val
                    WHERE `id_toko` = $idToko";

    if (mysqli_query($conn, $queryUpdate)) {
        catatLog($conn, 'Owner', 'Kantin ' . $nama_toko . ' telah mengupdate kantin');
        $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Informasi kantin berhasil diperbarui!'];
    } else {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal memperbarui database: ' . mysqli_error($conn)];
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}

// ════════════════════════════════════════════════════════════
// 2. ADD BANNER PROMO (MENDUKUNG BERLAKU_HINGGA & SOFT DELETE CHECK)
// ════════════════════════════════════════════════════════════
if ($action === 'add_banner') {
    $berlaku_hingga = $_POST['berlaku_hingga'] ?? '';

    if (empty($berlaku_hingga)) {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! Tanggal berlaku banner wajib diisi.'];
        header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
        exit;
    }

    // ATURAN BISNIS: Hitung hanya banner yang aktif=1, belum expired, dan tidak di-soft delete
    $queryCekAktif = "SELECT COUNT(*) as total FROM `banner_promo` 
                      WHERE `id_toko` = $idToko 
                      AND `aktif` = 1 
                      AND `deleted_at` IS NULL 
                      AND `berlaku_hingga` >= CURDATE()";

    $qCek = mysqli_query($conn, $queryCekAktif);
    $dataCek = mysqli_fetch_assoc($qCek);

    if ((int) $dataCek['total'] >= 2) {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! Batas maksimal 2 banner aktif terpenuhi. Hapus atau tunggu banner lama expired.'];
        header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
        exit;
    }

    if (isset($_FILES['gambar_banner']) && $_FILES['gambar_banner']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['gambar_banner']['tmp_name'];
        $fileName = $_FILES['gambar_banner']['name'];
        $fileSize = $_FILES['gambar_banner']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $maxFileSize = 2097152; // 2MB

        if (!in_array($fileExt, $allowedExts)) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! Format gambar wajib JPG, JPEG, PNG, atau WEBP.'];
            header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
            exit;
        }

        if ($fileSize > $maxFileSize) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! Ukuran gambar maksimal adalah 2MB.'];
            header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
            exit;
        }

        // Ambil konfigurasi canvas kustom dari input hidden penjual (format baru: bgx/bgy = 0-100, 50=tengah)
        $scale = floatval($_POST['banner_scale'] ?? 1.0);
        $bgX = floatval($_POST['banner_bgx'] ?? 50.0);
        $bgY = floatval($_POST['banner_bgy'] ?? 50.0);
        
        // Amankan nilai rentang
        if ($scale < 1.0) $scale = 1.0;
        if ($scale > 3.0) $scale = 3.0;
        if ($bgX < 0) $bgX = 0;
        if ($bgX > 100) $bgX = 100;
        if ($bgY < 0) $bgY = 0;
        if ($bgY > 100) $bgY = 100;

        $canvas_config = json_encode([
            'scale' => $scale,
            'bgX' => $bgX,
            'bgY' => $bgY
        ]);
        $canvas_config_db = mysqli_real_escape_string($conn, $canvas_config);

        $kode_promo = mysqli_real_escape_string($conn, strtoupper(trim($_POST['kode_promo'] ?? '')));
        $diskon_persen = (int)($_POST['diskon_persen'] ?? 0);

        // Insert awal data banner (mengisi juga kolom berlaku_hingga, canvas_config, kode_promo, diskon_persen)
        $queryInsertBanner = "INSERT INTO `banner_promo` (`id_toko`, `gambar`, `berlaku_hingga`, `aktif`, `dibuat_pada`, `deleted_at`, `canvas_config`, `kode_promo`, `diskon_persen`) 
                              VALUES ($idToko, '', '$berlaku_hingga', 1, NOW(), NULL, '$canvas_config_db', '$kode_promo', $diskon_persen)";

        if (mysqli_query($conn, $queryInsertBanner)) {
            $id_banner_baru = mysqli_insert_id($conn);
            $newFileName = 'banner_' . $idToko . '_' . $id_banner_baru . '.' . $fileExt;

            $files = glob($uploadBannerDir . 'banner_' . $idToko . '_' . $id_banner_baru . '.*');
            if ($files) {
                foreach ($files as $file) {
                    if (file_exists($file))
                        @unlink($file);
                }
            }

            if (move_uploaded_file($fileTmpPath, $uploadBannerDir . $newFileName)) {
                mysqli_query($conn, "UPDATE `banner_promo` SET `gambar` = '$newFileName' WHERE `id_banner` = $id_banner_baru");
                catatLog($conn, 'Owner', 'Banner promo' . $nama_toko . 'telah ditambahkan');
                $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Banner promosi baru berhasil ditambahkan!'];
            } else {
                // Hard delete baris saja jika file fisik gagal di-upload ke server
                mysqli_query($conn, "DELETE FROM `banner_promo` WHERE `id_banner` = $id_banner_baru");
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal mengunggah file gambar banner ke folder server.'];
            }
        } else {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menyimpan ke database: ' . mysqli_error($conn)];
        }
    } else {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! File gambar banner tidak terdeteksi.'];
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}

// ════════════════════════════════════════════════════════════
// 3. DELETE BANNER (SISTEM SOFT DELETE SESUAI ATURAN BISNIS)
// ════════════════════════════════════════════════════════════
if ($action === 'hapus_banner_direct') {
    $id_banner = (int) ($_POST['id_banner'] ?? 0);

    if ($id_banner > 0) {
        // ATURAN BISNIS: Cukup isi deleted_at dengan waktu sekarang dan nonaktifkan banner (aktif = 0)
        // File fisik gambar TIDAK DIAPUS dari server untuk kebutuhan audit trail / riwayat data murni
        $querySoftDelete = "UPDATE `banner_promo` 
                            SET `deleted_at` = NOW(), `aktif` = 0 
                            WHERE `id_banner` = $id_banner AND `id_toko` = $idToko";

        if (mysqli_query($conn, $querySoftDelete)) {
            catatLog($conn, 'Owner', 'Banner promo '. $nama_toko .'telah dihapus');
            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Banner promosi berhasil dihapus (Soft Delete)!'];
        } else {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menghapus banner: ' . mysqli_error($conn)];
        }
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}

header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
exit;