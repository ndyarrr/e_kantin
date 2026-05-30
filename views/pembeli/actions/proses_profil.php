<?php
// views/pembeli/actions/proses_profil.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

// Proteksi agar pembeli yang login saja yang bisa akses
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role']; // 'siswa' or 'guru'
$table = ($user_role === 'siswa') ? 'murid' : 'guru';
$pk_col = ($user_role === 'siswa') ? 'nisn' : 'nuptk';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ──────────────────────────────────────────────
    // ACTION: GANTI NAMA
    // ──────────────────────────────────────────────
    if ($action === 'ganti_nama') {
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
        if (empty($nama)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama tidak boleh kosong.']);
            exit;
        }

        $query = "UPDATE $table SET nama = '$nama' WHERE $pk_col = '$user_id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['user_nama'] = $nama;
            if (function_exists('catatLog')) {
                catatLog($conn, 'Update Profil', "Pembeli mengganti nama menjadi $nama");
            }
            echo json_encode(['status' => 'success', 'message' => 'Nama berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui nama di database.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // ACTION: GANTI PASSWORD
    // ──────────────────────────────────────────────
    if ($action === 'ganti_password') {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $password_konfirm = $_POST['password_konfirm'] ?? '';

        if (empty($password_lama) || empty($password_baru) || empty($password_konfirm)) {
            echo json_encode(['status' => 'error', 'message' => 'Semua kolom password wajib diisi.']);
            exit;
        }

        if ($password_baru !== $password_konfirm) {
            echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password baru tidak cocok.']);
            exit;
        }

        if (strlen($password_baru) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 6 karakter.']);
            exit;
        }

        // Ambil password lama dari DB
        $res = mysqli_query($conn, "SELECT password FROM $table WHERE $pk_col = '$user_id' LIMIT 1");
        if ($res && $user = mysqli_fetch_assoc($res)) {
            $old_hash = md5($password_lama);
            if ($user['password'] !== $old_hash) {
                echo json_encode(['status' => 'error', 'message' => 'Password lama Anda salah.']);
                exit;
            }

            $new_hash = md5($password_baru);
            $query = "UPDATE $table SET password = '$new_hash' WHERE $pk_col = '$user_id'";
            if (mysqli_query($conn, $query)) {
                if (function_exists('catatLog')) {
                    catatLog($conn, 'Update Password', 'Pembeli berhasil memperbarui password');
                }
                echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui password di database.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }
        exit;
    }

    // ──────────────────────────────────────────────
    // ACTION: GANTI FOTO PROFIL
    // ──────────────────────────────────────────────
    if ($action === 'ganti_foto') {
        if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih foto profil terlebih dahulu.']);
            exit;
        }

        $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
        $fileName = $_FILES['foto_profil']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung! Gunakan JPG, JPEG, PNG, atau WEBP.']);
            exit;
        }

        $newFileName = 'profil_' . $table . '_' . $user_id . '.' . $fileExtension;
        $uploadFileDir = __DIR__ . '/../../../assets/img/';

        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        // Hapus semua format file lama dengan prefix nama file ini
        foreach ($allowedExtensions as $ext) {
            $oldFile = $uploadFileDir . 'profil_' . $table . '_' . $user_id . '.' . $ext;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Upload file baru
        if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
            $query = "UPDATE $table SET foto_profil = '$newFileName' WHERE $pk_col = '$user_id'";
            if (mysqli_query($conn, $query)) {
                $_SESSION['user_foto'] = $newFileName;
                if (function_exists('catatLog')) {
                    catatLog($conn, 'Update Foto Profil', 'Pembeli berhasil memperbarui foto profil');
                }
                echo json_encode(['status' => 'success', 'message' => 'Foto profil berhasil diperbarui!', 'foto_path' => '../../assets/img/' . $newFileName . '?v=' . time()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file. Periksa izin folder.']);
        }
        exit;
    }
}
