<?php
// views/penjual/actions/proses_menu.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_php_s = ($_SERVER['SERVER_PORT'] == '8000' || strpos($_SERVER['HTTP_HOST'], ':') !== false);
$base_url = $is_php_s ? '' : '/e_kantin';

// ✅ MENDUKUNG MULTI-ROLE (OWNER & STAF)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['penjual', 'staf'])) {
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

// ✅ PATH SESUAI FOLDER /views/penjual/actions/
require_once __DIR__ . '/../../../config/database.php';

$penjualId = (int) ($_SESSION['user_id'] ?? 0);
$roleLabel = ($_SESSION['user_role'] === 'staf') ? 'Staf' : 'Owner';
// ✅ REDIRECT DINAMIS KEMBALI KE HALAMAN MASING-MASING
$rolePath  = ($_SESSION['user_role'] === 'staf') ? '/views/penjual/staf/index.php' : '/views/penjual/owner/index.php';

// Ambil ID Toko
$rToko = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT id_toko FROM toko_penjual WHERE id_penjual=$penjualId LIMIT 1"
));
$idToko = (int) ($rToko['id_toko'] ?? 0);

// Path upload gambar menggunakan __DIR__ agar dinamis & portabel (Bukan hardcode /opt/lampp/...)
$uploadFileDir = realpath(__DIR__ . '/../../../assets/img/menu') . DIRECTORY_SEPARATOR;

if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
$allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Upload foto menu dengan nama: menu_{id_menu}_{id_toko}.{ext}
 */
function uploadFotoMenu(array $file, int $id_menu, int $idToko, string $dir, array $allowed_ext, array $allowed_mime): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext))
        return null;

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
        $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection);
            exit;
        }

        // Insert database dulu dengan foto_menu = NULL
        $ok = mysqli_query(
            $conn,
            "INSERT INTO menu (id_toko, nama_menu, kategori, deskripsi, harga, foto_menu, stok, tersedia)
             VALUES ($idToko, '$nama_menu', '$kategori', '$deskripsi', $harga, NULL, $stok, $tersedia)"
        );

        if (!$ok) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal menambahkan menu: ' . mysqli_error($conn)];
            header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection);
            exit;
        }

        $id_menu_baru = (int) mysqli_insert_id($conn);

        $nama_foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $nama_foto = uploadFotoMenu($_FILES['foto'], $id_menu_baru, $idToko, $uploadFileDir, $allowed_ext, $allowed_mime);

            if ($nama_foto) {
                mysqli_query($conn, "UPDATE menu SET foto_menu = '$nama_foto' WHERE id_menu = $id_menu_baru");
            } else {
                $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Menu tersimpan, tapi foto gagal diunggah. Cek format/ukuran file.'];
                header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection);
                exit;
            }
        }

        if (function_exists('catatLog')) {
            catatLog($conn, 'Tambah Menu', "$roleLabel menambahkan menu baru: $nama_menu (Kategori: $kategori, Harga: Rp $harga)");
        }

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
        $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));

        if ($harga > 99999) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Gagal: Harga tidak boleh melebihi Rp 99.999!'];
            header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection);
            exit;
        }

        $resLama = mysqli_query($conn, "SELECT foto_menu FROM menu WHERE id_menu = $id_menu AND id_toko = $idToko LIMIT 1");
        $menuLama = mysqli_fetch_assoc($resLama);
        
        if (!$menuLama) {
            $_SESSION['feedback'] = ['type' => 'danger', 'msg' => 'Menu tidak ditemukan atau bukan milik toko ini.'];
            header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection);
            exit;
        }

        $nama_foto = $menuLama['foto_menu'] ?? null;

        if (!empty($_FILES['foto']['name'])) {
            $fotoBaru = uploadFotoMenu($_FILES['foto'], $id_menu, $idToko, $uploadFileDir, $allowed_ext, $allowed_mime);
            if ($fotoBaru) {
                hapusFotoLama($nama_foto, $fotoBaru, $uploadFileDir);
                $nama_foto = $fotoBaru;
            }
        }

        $fotoSql = $nama_foto ? ", foto_menu = '$nama_foto'" : '';
        
        $ok = mysqli_query(
            $conn,
            "UPDATE menu SET
                nama_menu = '$nama_menu',
                kategori  = '$kategori',
                deskripsi = '$deskripsi',
                harga     = $harga,
                stok      = $stok,
                tersedia  = $tersedia
                $fotoSql
             WHERE id_menu = $id_menu AND id_toko = $idToko"
        );

        if ($ok && function_exists('catatLog')) {
            catatLog($conn, 'Edit Menu', "$roleLabel mengubah menu ID $id_menu: $nama_menu (Kategori: $kategori, Harga: Rp $harga)");
        }

        $feedback = $ok
            ? ['type' => 'success', 'msg' => 'Menu berhasil diperbarui!']
            : ['type' => 'danger', 'msg' => 'Gagal memperbarui menu: ' . mysqli_error($conn)];
    }

    // ── 3. HAPUS MENU ────────────────────────────────────────────────────────
    if ($action === 'hapus_menu') {
        $id_menu = (int) ($_POST['id_menu'] ?? 0);

        if ($id_menu > 0) {
            // Menggunakan soft delete agar history pesanan tidak terputus
            $ok = mysqli_query(
                $conn,
                "UPDATE menu SET deleted_at = NOW() WHERE id_menu = $id_menu AND id_toko = $idToko"
            );
            if ($ok && function_exists('catatLog')) {
                $nama_menu_del = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_menu FROM menu WHERE id_menu=$id_menu"))['nama_menu'] ?? '';
                catatLog($conn, 'Hapus Menu', "$roleLabel menghapus menu: $nama_menu_del (ID: $id_menu)");
            }
            $feedback = $ok
                ? ['type' => 'success', 'msg' => 'Menu berhasil dihapus!']
                : ['type' => 'danger', 'msg' => 'Gagal menghapus menu: ' . mysqli_error($conn)];
        }
    }

    // ── 4. UPDATE STATUS PESANAN (INBOX) ─────────────────────────────────────
    if ($action === 'update_status') {
        $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
        $status_baru = $_POST['status_baru'] ?? '';
        $statusValid = ['dikonfirmasi', 'siap_diambil', 'selesai', 'dibatalkan'];

        if (!in_array($status_baru, $statusValid)) {
            $feedback = ['type' => 'danger', 'msg' => 'Status tidak valid.'];
        } else {
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
                    if (function_exists('catatLog')) {
                        catatLog($conn, 'Update Status Pesanan', "$roleLabel memperbarui status pesanan #$id_pesanan menjadi $labelStatus");
                    }
                    $feedback = ['type' => 'success', 'msg' => "Pesanan #$id_pesanan berhasil ditandai $labelStatus."];
                } else {
                    $feedback = ['type' => 'danger', 'msg' => 'Gagal mengubah status: ' . mysqli_error($conn)];
                }
            }
        }
    }

    if (isset($feedback)) {
        $_SESSION['feedback'] = $feedback;
    }

    if (in_array($action, ['tambah_menu', 'edit_menu', 'hapus_menu'])) {
        $activeSection = 'menu';
    }

    header('Location: ' . $base_url . $rolePath . '?section=' . $activeSection . '&t=' . time());
    exit;
}