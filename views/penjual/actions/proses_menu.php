<?php
// views/penjual/owner/actions/proses_menu.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'penjual') {
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$penjualId = (int) ($_SESSION['user_id'] ?? 0);

$rToko = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId LIMIT 1"
));
$idToko = (int) ($rToko['id_toko'] ?? 0);


$uploadFileDir = realpath(__DIR__ . '/../../../assets/img/menu') . DIRECTORY_SEPARATOR;


if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
$allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Upload foto menu dengan nama: menu_{id_menu}_{id_toko}.{ext}
 * Return nama file jika sukses, null jika gagal / tidak ada file.
 */
function uploadFotoMenu(array $file, int $id_menu, int $idToko, string $dir, array $allowed_ext, array $allowed_mime): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext))
        return null;

    // ✅ Pakai OOP style — tidak deprecated di PHP 8.5
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime))
        return null;

    $namaFile = "menu_{$id_menu}_{$idToko}.{$ext}";
    if (move_uploaded_file($file['tmp_name'], $dir . $namaFile)) {
        return $namaFile;
    }
    return null;
}

/**
 * Hapus file foto lama jika ada dan berbeda dengan yang baru.
 */
function hapusFotoLama(?string $fotoLama, ?string $fotoBaru, string $dir): void
{
    if (!empty($fotoLama) && $fotoLama !== $fotoBaru) {
        $path = $dir . $fotoLama;
        if (file_exists($path))
            unlink($path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activeSection = $_POST['_section'] ?? 'menu';
    $feedback = null;

    // ── 1. TAMBAH MENU ───────────────────────────────────────────────────────
    if ($action === 'tambah_menu') {
        $nama_menu = mysqli_real_escape_string($conn, trim($_POST['nama_menu']));
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'makanan');
        $harga = (int) ($_POST['harga'] ?? 0);
        $stok = (int) ($_POST['stok'] ?? 0);
        $tersedia = $stok > 0 ? 1 : 0;

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
            exit;
        }

        // Insert dulu tanpa foto untuk dapat id_menu
        $ok = mysqli_query(
            $conn,
            "INSERT INTO menu (id_toko, nama_menu, kategori, deskripsi, harga, foto_menu, stok, tersedia)
             VALUES ($idToko, '$nama_menu', '$kategori', NULL, $harga, NULL, $stok, $tersedia)"
        );

        if (!$ok) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menambahkan menu: ' . mysqli_error($conn)];
            header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
            exit;
        }

        $id_menu_baru = (int) mysqli_insert_id($conn);

        // Upload foto setelah dapat id_menu
        $nama_foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $nama_foto = uploadFotoMenu($_FILES['foto'], $id_menu_baru, $idToko, $uploadFileDir, $allowed_ext, $allowed_mime);

            if ($nama_foto) {
                mysqli_query($conn, "UPDATE menu SET foto_menu = '$nama_foto' WHERE id_menu = $id_menu_baru");
            } else {
                // File ada tapi gagal upload
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Menu tersimpan, tapi foto gagal diunggah. Cek format/ukuran file.'];
                header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
                exit;
            }
        }

        catatLog($conn, 'Tambah Menu', "Owner menambahkan menu baru: $nama_menu (Kategori: $kategori, Harga: Rp $harga)");

        $feedback = ['type' => 'success', 'msg' => 'Menu baru berhasil ditambahkan!'];
    }

    // ── 2. EDIT MENU ─────────────────────────────────────────────────────────
    if ($action === 'edit_menu') {
        $id_menu = (int) ($_POST['id_menu'] ?? 0);
        $nama_menu = mysqli_real_escape_string($conn, trim($_POST['nama_menu']));
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'makanan');
        $harga = (int) ($_POST['harga'] ?? 0);
        $stok = (int) ($_POST['stok'] ?? 0);
        $tersedia = $stok > 0 ? 1 : 0;

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
            exit;
        }

        $resLama = mysqli_query($conn, "SELECT foto_menu FROM menu WHERE id_menu = $id_menu LIMIT 1");
        $menuLama = mysqli_fetch_assoc($resLama);
        $nama_foto = $menuLama['foto_menu'] ?? null; // default: tetap pakai foto lama

        if (!empty($_FILES['foto']['name'])) {
            $fotoBaru = uploadFotoMenu($_FILES['foto'], $id_menu, $idToko, $uploadFileDir, $allowed_ext, $allowed_mime);
            if ($fotoBaru) {
                hapusFotoLama($nama_foto, $fotoBaru, $uploadFileDir); // hapus lama jika nama beda (ganti ekstensi)
                $nama_foto = $fotoBaru;
            }
        }

        $fotoSql = $nama_foto ? ", foto_menu = '$nama_foto'" : '';
        $ok = mysqli_query(
            $conn,
            "UPDATE menu SET
                nama_menu = '$nama_menu',
                kategori  = '$kategori',
                harga     = $harga,
                stok      = $stok,
                tersedia  = $tersedia
                $fotoSql
             WHERE id_menu = $id_menu AND id_toko = $idToko"
        );

        if ($ok) {
            catatLog($conn, 'Edit Menu', "Owner mengubah menu ID $id_menu: $nama_menu (Kategori: $kategori, Harga: Rp $harga)");
        }

        $feedback = $ok
            ? ['type' => 'success', 'msg' => 'Menu berhasil diperbarui!']
            : ['type' => 'danger', 'msg' => 'Gagal memperbarui menu: ' . mysqli_error($conn)];
    }

    // ── 3. HAPUS MENU (soft delete) ──────────────────────────────────────────
    if ($action === 'hapus_menu') {
        $id_menu = (int) ($_POST['id_menu'] ?? 0);

        if ($id_menu > 0) {
            $ok = mysqli_query(
                $conn,
                "UPDATE menu SET deleted_at = NOW() WHERE id_menu = $id_menu AND id_toko = $idToko"
            );
            if ($ok) {
                $nama_menu_del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_menu FROM menu WHERE id_menu=$id_menu"))['nama_menu'] ?? '';
                catatLog($conn, 'Hapus Menu', "Owner menghapus menu: $nama_menu_del (ID: $id_menu)");
            }
            $feedback = $ok
                ? ['type' => 'success', 'msg' => 'Menu berhasil dihapus!']
                : ['type' => 'danger', 'msg' => 'Gagal menghapus menu: ' . mysqli_error($conn)];
        }
    }

    if (isset($feedback)) {
        $_SESSION['feedback'] = $feedback;
    }

    header('Location: ' . $base_url . '/views/penjual/owner/index.php?section=' . $activeSection);
    exit;
}