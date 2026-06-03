<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE)
    session_start();

// 1. Ambil koneksi database
require_once __DIR__ . '/../../../config/database.php';

// Pastikan $conn tersedia
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Gunakan user_id yang ada di session
    $id_penjual = $_SESSION['user_id'] ?? 0;

    // 🌟 AMBIL DATA ROLE LANGSUNG DARI DATABASE (Anti Salah Alamat)
    $user_role = 'owner'; // Default fallback
    if ($id_penjual > 0) {
        $query_role = mysqli_query($conn, "SELECT role FROM penjual WHERE id_penjual = '$id_penjual' LIMIT 1");
        if ($query_role && $data_user = mysqli_fetch_assoc($query_role)) {
            $user_role = strtolower($data_user['role']);
        }
    }

    // 🌟 SETTING VARIABEL DINAMIS BERDASARKAN REAL DATABASE ROLE
    $folder_dest = ($user_role === 'staf') ? 'staf' : 'owner';
    $prefix_foto = ($user_role === 'staf') ? 'profil_staf_' : 'profil_owner_';
    $label_log = ($user_role === 'staf') ? 'Staf' : 'Owner';

    // ==========================================================================
    // PROSES 1: EDIT DATA PROFIL & TOKO
    // ==========================================================================
    if ($action === 'edit_profil') {
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));

        // Ambil nama_toko jika dikirim (hanya dari form owner)
        $nama_toko = isset($_POST['nama_toko']) ? mysqli_real_escape_string($conn, trim($_POST['nama_toko'])) : null;

        $foto_query = "";
        $error_feedback = null;

        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_profil']['tmp_name'];
            $fileName = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            // Validasi format file
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error_feedback = [
                    'type' => 'error',
                    'msg' => 'Format foto tidak didukung! Gunakan JPG, JPEG, PNG, atau WEBP.'
                ];
            } else {
                $newFileName = $prefix_foto . $id_penjual . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';

                if (!is_dir($uploadFileDir))
                    mkdir($uploadFileDir, 0755, true);

                // Hapus semua format file lama dengan prefix role ini
                foreach ($allowedExtensions as $ext) {
                    $file_lama_potensial = $uploadFileDir . $prefix_foto . $id_penjual . '.' . $ext;
                    if (file_exists($file_lama_potensial)) {
                        unlink($file_lama_potensial);
                    }
                }

                // Upload file baru
                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    $foto_query = ", foto_profil = '$newFileName'";

                    // Update session foto jika sistemmu memakainya di layout
                    if (isset($_SESSION['user_foto'])) {
                        $_SESSION['user_foto'] = $newFileName;
                    }
                } else {
                    $error_feedback = [
                        'type' => 'error',
                        'msg' => 'Gagal mengunggah foto profil. Periksa permission folder!'
                    ];
                }
            }
        }

        // Eksekusi jika tidak ada error format file
        if ($error_feedback !== null) {
            $_SESSION['feedback'] = $error_feedback;
        } else {
            // Update data utama
            $query_update = "UPDATE penjual SET nama = '$nama', username = '$username' $foto_query WHERE id_penjual = '$id_penjual'";
            mysqli_query($conn, $query_update);

            // Update nama toko (Hanya jika login sebagai Owner dan data dikirim)
            if ($user_role !== 'staf' && $nama_toko !== null) {
                mysqli_query($conn, "UPDATE toko SET nama_toko = '$nama_toko' WHERE id_toko = (SELECT id_toko FROM toko_penjual WHERE id_penjual = '$id_penjual' AND status = 'aktif' ORDER BY id DESC LIMIT 1)");
            }

            // Sync session nama biar di navbar langsung berubah tanpa relog
            if (isset($_SESSION['user_nama'])) {
                $_SESSION['user_nama'] = $nama;
            }

            catatLog($conn, 'Update Profil', "$label_log memperbarui data profil");

            $_SESSION['feedback'] = [
                'type' => 'success',
                'msg' => "Profil & Foto $label_log berhasil diperbarui!"
            ];
        }

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }

    // ==========================================================================
    // PROSES 2: GANTI PASSWORD
    // ==========================================================================
    if ($action === 'ganti_password') {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $password_konfirm = $_POST['password_konfirm'];

        if ($password_baru !== $password_konfirm) {
            $_SESSION['feedback'] = [
                'type' => 'error',
                'msg' => 'Konfirmasi password baru tidak cocok!'
            ];
            echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
            exit;
        }

        $res_pw = mysqli_query($conn, "SELECT password FROM penjual WHERE id_penjual = '$id_penjual'");
        if ($res_pw && $row = mysqli_fetch_assoc($res_pw)) {

            // MD5 comparison
            if (md5($password_lama) === $row['password']) {
                $password_fix = md5($password_baru); // konsisten pakai MD5
                mysqli_query($conn, "UPDATE penjual SET password = '$password_fix' WHERE id_penjual = '$id_penjual'");

                catatLog($conn, 'Update Password', "$label_log memperbarui password keamanan");

                $_SESSION['feedback'] = [
                    'type' => 'success',
                    'msg' => 'Password keamanan berhasil diganti!'
                ];
            } else {
                $_SESSION['feedback'] = [
                    'type' => 'error',
                    'msg' => 'Password lama yang Anda masukkan salah!'
                ];
            }
        }

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }

    // ==========================================================================
    // PROSES 3: HAPUS FOTO PROFIL
    // ==========================================================================
    if ($action === 'hapus_foto_profil') {
        $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($allowedExtensions as $ext) {
            $file_foto = $uploadFileDir . $prefix_foto . $id_penjual . '.' . $ext;
            if (file_exists($file_foto)) {
                unlink($file_foto);
            }
        }

        $query_hapus_foto = "UPDATE penjual SET foto_profil = NULL WHERE id_penjual = '$id_penjual'";

        if (mysqli_query($conn, $query_hapus_foto)) {
            if (isset($_SESSION['user_foto'])) {
                $_SESSION['user_foto'] = null;
            }
            catatLog($conn, 'Hapus Foto Profil', "$label_log menghapus foto profil");
            $_SESSION['feedback'] = [
                'type' => 'success',
                'msg' => 'Foto profil berhasil dihapus, inisial akun diaktifkan!'
            ];
        } else {
            $_SESSION['feedback'] = [
                'type' => 'error',
                'msg' => 'Gagal menghapus foto profil di database.'
            ];
        }

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }
}
?>