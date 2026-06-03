<?php
// views/penjual/actions/proses_latar_belakang.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi agar yang mengeksekusi file ini murni user ber-role owner (penjual) atau staf
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['penjual', 'staf'])) {
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$base_url = '';
if (preg_match('#^(.*)/(views|auth|backend|controllers|config|assets|scratch)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $base_url = $m[1];
} elseif (preg_match('#^(.*)/index\.php#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $base_url = $m[1];
}

$uploadLatarDir = tokoFotoImgRoot() . '/latar_belakang/';
if (!is_dir($uploadLatarDir)) {
    mkdir($uploadLatarDir, 0755, true);
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
// 1. ADD FOTO LATAR BELAKANG
// ════════════════════════════════════════════════════════════
if ($action === 'add_latar_belakang') {
    // Cek jumlah foto latar belakang yang sudah ada untuk toko ini
    $q_cek = mysqli_query($conn, "SELECT COUNT(*) as total FROM `foto_latar_belakang` WHERE `id_toko` = $idToko");
    $data_cek = mysqli_fetch_assoc($q_cek);
    if ((int)$data_cek['total'] >= 5) {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! Batas maksimal 5 foto latar belakang telah tercapai.'];
        header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin");
        exit;
    }

    if (isset($_FILES['gambar_latar']) && $_FILES['gambar_latar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['gambar_latar']['tmp_name'];
        $fileName = $_FILES['gambar_latar']['name'];
        $fileSize = $_FILES['gambar_latar']['size'];
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

        // Konfigurasi canvas kustom
        $scale = floatval($_POST['latar_scale'] ?? 1.0);
        $panNormX = floatval($_POST['latar_pan_norm_x'] ?? 0.0);
        $panNormY = floatval($_POST['latar_pan_norm_y'] ?? 0.0);

        if ($scale < 1.0) $scale = 1.0;
        if ($scale > 4.0) $scale = 4.0;
        if ($panNormX < -1) $panNormX = -1;
        if ($panNormX > 1) $panNormX = 1;
        if ($panNormY < -1) $panNormY = -1;
        if ($panNormY > 1) $panNormY = 1;

        $bgX = 50 + ($panNormX * 50);
        $bgY = 50 + ($panNormY * 50);

        $canvas_config = json_encode([
            'scale' => $scale,
            'panNormX' => $panNormX,
            'panNormY' => $panNormY,
            'bgX' => $bgX,
            'bgY' => $bgY,
            'version' => 2
        ]);
        $canvas_config_db = mysqli_real_escape_string($conn, $canvas_config);

        // Cari urutan terbesar
        $q_urutan = mysqli_query($conn, "SELECT COALESCE(MAX(urutan), -1) + 1 as next_urutan FROM `foto_latar_belakang` WHERE `id_toko` = $idToko");
        $r_urutan = mysqli_fetch_assoc($q_urutan);
        $next_urutan = (int)$r_urutan['next_urutan'];

        // Insert awal data foto latar belakang
        $queryInsert = "INSERT INTO `foto_latar_belakang` (`id_toko`, `gambar`, `canvas_config`, `urutan`) 
                        VALUES ($idToko, '', '$canvas_config_db', $next_urutan)";

        if (mysqli_query($conn, $queryInsert)) {
            $id_latar_baru = mysqli_insert_id($conn);
            $newFileName = 'latar_' . $idToko . '_' . $id_latar_baru . '.' . $fileExt;

            // Hapus file lama jika ada (berjaga-jaga)
            $files = glob($uploadLatarDir . 'latar_' . $idToko . '_' . $id_latar_baru . '.*');
            if ($files) {
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
            }

            if (move_uploaded_file($fileTmpPath, $uploadLatarDir . $newFileName)) {
                mysqli_query($conn, "UPDATE `foto_latar_belakang` SET `gambar` = '$newFileName' WHERE `id` = $id_latar_baru");
                catatLog($conn, 'Owner', 'Menambahkan foto latar belakang baru untuk kantin ' . $nama_toko);
                $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Foto latar belakang baru berhasil ditambahkan!'];
            } else {
                mysqli_query($conn, "DELETE FROM `foto_latar_belakang` WHERE `id` = $id_latar_baru");
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal mengunggah file gambar ke folder server.'];
            }
        } else {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menyimpan ke database: ' . mysqli_error($conn)];
        }
    } else {
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal! File gambar tidak terdeteksi atau error upload.'];
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}

// ════════════════════════════════════════════════════════════
// 2. HAPUS FOTO LATAR BELAKANG
// ════════════════════════════════════════════════════════════
if ($action === 'hapus_latar_belakang') {
    $id_latar = (int)($_POST['id_latar'] ?? $_GET['id_latar'] ?? 0);

    if ($id_latar > 0) {
        // Ambil info file gambar
        $q_gambar = mysqli_query($conn, "SELECT gambar FROM `foto_latar_belakang` WHERE `id` = $id_latar AND `id_toko` = $idToko LIMIT 1");
        if ($q_gambar && mysqli_num_rows($q_gambar) > 0) {
            $data_latar = mysqli_fetch_assoc($q_gambar);
            $nama_file = $data_latar['gambar'];

            // Hapus file fisik
            if (!empty($nama_file) && file_exists($uploadLatarDir . $nama_file)) {
                @unlink($uploadLatarDir . $nama_file);
            }

            // Hapus dari DB
            $queryDelete = "DELETE FROM `foto_latar_belakang` WHERE `id` = $id_latar AND `id_toko` = $idToko";
            if (mysqli_query($conn, $queryDelete)) {
                // Re-sequence urutan sisa agar berurutan dari 0
                $q_sisa = mysqli_query($conn, "SELECT id FROM `foto_latar_belakang` WHERE `id_toko` = $idToko ORDER BY urutan ASC");
                $i = 0;
                while ($row = mysqli_fetch_assoc($q_sisa)) {
                    $curr_id = $row['id'];
                    mysqli_query($conn, "UPDATE `foto_latar_belakang` SET `urutan` = $i WHERE `id` = $curr_id");
                    $i++;
                }

                catatLog($conn, 'Owner', 'Menghapus foto latar belakang untuk kantin ' . $nama_toko);
                $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Foto latar belakang berhasil dihapus!'];
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menghapus dari database: ' . mysqli_error($conn)];
            }
        } else {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Data foto tidak ditemukan.'];
        }
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}

// ════════════════════════════════════════════════════════════
// 3. UPDATE URUTAN LATAR BELAKANG (BISA AJAX MAUPUN POST BIASA)
// ════════════════════════════════════════════════════════════
if ($action === 'update_urutan_latar') {
    // Kita terima payload json atau array id
    $ids = $_POST['ids'] ?? [];
    if (empty($ids) && isset($_POST['ids_json'])) {
        $ids = json_decode($_POST['ids_json'], true) ?: [];
    }

    if (!empty($ids) && is_array($ids)) {
        $success = true;
        foreach ($ids as $index => $id) {
            $id = (int)$id;
            $index = (int)$index;
            $update = mysqli_query($conn, "UPDATE `foto_latar_belakang` SET `urutan` = $index WHERE `id` = $id AND `id_toko` = $idToko");
            if (!$update) {
                $success = false;
            }
        }

        if ($success) {
            catatLog($conn, 'Owner', 'Mengatur ulang urutan foto latar belakang untuk kantin ' . $nama_toko);
            if (isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Urutan berhasil diperbarui!']);
                exit;
            }
            $_SESSION['feedback'] = ['type' => 'success', 'msg' => 'Urutan foto latar belakang berhasil diperbarui!'];
        } else {
            if (isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Sebagian atau seluruh urutan gagal diperbarui.']);
                exit;
            }
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal memperbarui urutan foto latar belakang.'];
        }
    } else {
        if (isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Data urutan kosong atau tidak valid.']);
            exit;
        }
        $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Data urutan tidak valid.'];
    }

    header("Location: " . $base_url . "/views/penjual/owner/index.php?section=kantin&t=" . time());
    exit;
}
