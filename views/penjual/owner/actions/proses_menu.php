<?php
// views/penjual/owner/actions/proses_menu.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🌟 DETEKSI OTOMATIS: Apakah pake php -S (:8000) atau XAMPP biasa
$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

// 🌟 1. PERBAIKAN TENDANGAN LOGIN (Paling Atas)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../../config/database.php';

$penjualId = (int)($_SESSION['user_id'] ?? 0);

// Cari id_toko
$rToko = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId LIMIT 1"
));
$idToko = (int)($rToko['id_toko'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'menu'; // Default balik ke menu biar aman
    $feedback = null;
    
    // 1. LOGIKA TAMBAH MENU
    if ($action === 'tambah_menu') {
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $kategori  = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'makanan');
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;
        $nama_foto = null;

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            // 🌟 UBAH: Pakai $base_url biar ga nyasar di php -S
            header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
            exit;
        }

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = 'menu_' . time() . '.' . $fileExtension;
            
            // Catatan: folder /opt/lampp adalah path internal Linux. 
            // Kalau kamu di windows/hosting, sesuaikan path folder upload ini ya!
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                $nama_foto = $newFileName;
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal mengunggah gambar!'];
                // 🌟 UBAH: Pakai $base_url biar ga nyasar di php -S
                header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
                exit;
            }
        }

        $queryInsert = "INSERT INTO menu (id_toko, nama_menu, kategori, deskripsi, harga, foto_menu, stok, tersedia) 
                        VALUES ($idToko, '$nama_menu', '$kategori', NULL, $harga, " . ($nama_foto ? "'$nama_foto'" : "NULL") . ", $stok, $tersedia)";
        
        if (mysqli_query($conn, $queryInsert)) {
            $feedback = ['type' => 'success', 'msg' => 'Menu baru berhasil ditambahkan!'];
        } else {
            $feedback = ['type' => 'danger', 'msg' => 'Gagal menambahkan menu: ' . mysqli_error($conn)];
        }
    }

    // 2. LOGIKA EDIT MENU
    if ($action === 'edit_menu') {
        $id_menu   = (int)($_POST['id_menu'] ?? 0);
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $kategori  = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'makanan');
        $harga     = (int)($_POST['harga'] ?? 0);
        $stok      = (int)($_POST['stok'] ?? 0);
        $tersedia  = $stok > 0 ? 1 : 0;

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            // 🌟 UBAH: Pakai $base_url biar ga nyasar di php -S
            header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
            exit;
        }

        $resLama = mysqli_query($conn, "SELECT foto_menu FROM menu WHERE id_menu = $id_menu LIMIT 1");
        $menuLama = mysqli_fetch_assoc($resLama);
        $nama_foto = $menuLama['foto_menu'] ?? null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = 'menu_' . time() . '.' . $fileExtension;
            $uploadFileDir = '/opt/lampp/htdocs/e_kantin/assets/img/menu/';
            
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                if (!empty($menuLama['foto_menu']) && file_exists($uploadFileDir . $menuLama['foto_menu'])) {
                    unlink($uploadFileDir . $menuLama['foto_menu']);
                }
                $nama_foto = $newFileName;
            }
        }

        $queryUpdate = "UPDATE menu SET 
                        nama_menu = '$nama_menu', 
                        kategori = '$kategori', 
                        harga = $harga, 
                        stok = $stok, 
                        tersedia = $tersedia" . 
                        ($nama_foto ? ", foto_menu = '$nama_foto'" : "") . " 
                        WHERE id_menu = $id_menu";
        
        if (mysqli_query($conn, $queryUpdate)) {
            $feedback = ['type' => 'success', 'msg' => 'Menu berhasil diperbarui!'];
        } else {
            $feedback = ['type' => 'danger', 'msg' => 'Gagal memperbarui menu: ' . mysqli_error($conn)];
        }
    }

    // 3. LOGIKA SOFT DELETE MENU
    if ($action === 'hapus_menu') {
        $id_menu = (int)($_POST['id_menu'] ?? 0);

        if ($id_menu > 0) {
            $querySoftDelete = "UPDATE menu SET deleted_at = NOW() WHERE id_menu = $id_menu AND id_toko = $idToko";
            
            if (mysqli_query($conn, $querySoftDelete)) {
                $feedback = ['type' => 'success', 'msg' => 'Menu berhasil dihapus!'];
            } else {
                $feedback = ['type' => 'danger', 'msg' => 'Gagal menghapus menu: ' . mysqli_error($conn)];
            }
        }
    }

    if (isset($feedback)) {
        $_SESSION['feedback'] = $feedback;
    }

    // Jalur balik dinamis: kalau XAMPP pake /e_kantin, kalau php -S langsung /views
    header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
    exit;
}