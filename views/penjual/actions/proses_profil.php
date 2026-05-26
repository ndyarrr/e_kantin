<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Ambil koneksi database
require_once __DIR__ . '/../../../config/database.php';

// Pastikan $conn tersedia
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Gunakan user_id yang ada di session index.php sebagai ID Owner
    $id_penjual = $_SESSION['user_id'] ?? 0; 

    // ==========================================================================
    // PROSES 1: EDIT DATA PROFIL & TOKO OWNER
    // ==========================================================================
    if ($action === 'edit_profil') {
        $nama      = mysqli_real_escape_string($conn, trim($_POST['nama']));
        $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
        $nama_toko = mysqli_real_escape_string($conn, trim($_POST['nama_toko']));
        
        $foto_query = ""; 
        
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto_profil']['tmp_name'];
            $fileName      = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // FORMAT NAMA FILE BARU: profil_owner_1.jpg atau profil_owner_1.png
                $newFileName = 'profil_owner_' . $id_penjual . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';
                
                if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);

                // HAPUS SEMUA FOTO LAMA OWNER INI (BAIK JPG MAUPUN PNG) BIAR GAK BENTROK
                foreach ($allowedExtensions as $ext) {
                    $file_lama_potensial = $uploadFileDir . 'profil_owner_' . $id_penjual . '.' . $ext;
                    if (file_exists($file_lama_potensial)) {
                        unlink($file_lama_potensial);
                    }
                }

                // Upload file yang baru masuk
                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    $foto_query = ", foto_profil = '$newFileName'";
                }
            }
        }

        // Update data ke tabel penjual
        $query_update = "UPDATE penjual SET nama = '$nama', username = '$username' $foto_query WHERE id_penjual = '$id_penjual'";
        mysqli_query($conn, $query_update);
        
        // Update nama toko ke tabel toko
        mysqli_query($conn, "UPDATE toko SET nama_toko = '$nama_toko' WHERE id_toko = (SELECT id_toko FROM toko_penjual WHERE id_penjual = '$id_penjual' LIMIT 1)");

        catatLog($conn, 'Update Profil', 'Owner memperbarui data profil & toko');

        // Kirim feedback banner sukses
        $_SESSION['feedback'] = [
            'type' => 'success',
            'msg'  => 'Profil & Foto Owner berhasil diperbarui!'
        ];

        echo "<script>window.location.href='../owner/index.php?section=profil';</script>";
        exit;
    } // 🌟 FIX: Kurung penutup untuk edit_profil harus ditaruh di sini!

    // ==========================================================================
    // PROSES 2: GANTI PASSWORD
    // ==========================================================================
    if ($action === 'ganti_password') {
        $password_lama    = $_POST['password_lama'];
        $password_baru    = $_POST['password_baru'];
        $password_konfirm = $_POST['password_konfirm'];

        // 1. Cek kecocokan password baru
        if ($password_baru !== $password_konfirm) {
            $_SESSION['feedback'] = [
                'type' => 'error',
                'msg'  => 'Konfirmasi password baru tidak cocok!'
            ];
            echo "<script>window.location.href='../owner/index.php?section=profil';</script>";
            exit;
        }

        // 2. Cek password lama ke database
        $res_pw = mysqli_query($conn, "SELECT password FROM penjual WHERE id_penjual = '$id_penjual'");
        if ($res_pw && $row = mysqli_fetch_assoc($res_pw)) {
            
            if (password_verify($password_lama, $row['password']) || $password_lama === $row['password']) {
                // Jika benar, lakukan hash dan update database
                $password_fix = password_hash($password_baru, PASSWORD_BCRYPT);
                mysqli_query($conn, "UPDATE penjual SET password = '$password_fix' WHERE id_penjual = '$id_penjual'");
                
                catatLog($conn, 'Update Password', 'Owner memperbarui password keamanan');

                // 🌟 FIX: Set session sukses DI SINI setelah query database berhasil jalan!
                $_SESSION['feedback'] = [
                    'type' => 'success',
                    'msg'  => 'Password keamanan owner berhasil diganti!'
                ];
            } else {
                // Jika password lama salah
                $_SESSION['feedback'] = [
                    'type' => 'error',
                    'msg'  => 'Password lama yang Anda masukkan salah!'
                ];
            }
        }

        echo "<script>window.location.href='../owner/index.php?section=profil';</script>";
        exit;
    }

    // ==========================================================================
    // PROSES 3: HAPUS FOTO PROFIL (KEMBALI KE INISIAL)
    // ==========================================================================
    if ($action === 'hapus_foto_profil') {
        $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        // 1. Cari dan hapus fisik file gambar yang ada di server
        foreach ($allowedExtensions as $ext) {
            $file_foto = $uploadFileDir . 'profil_owner_' . $id_penjual . '.' . $ext;
            if (file_exists($file_foto)) {
                unlink($file_foto); // File dihapus dari folder assets
            }
        }

        // 2. Kosongkan kolom foto_profil di database penjual
        $query_hapus_foto = "UPDATE penjual SET foto_profil = NULL WHERE id_penjual = '$id_penjual'";
        
        if (mysqli_query($conn, $query_hapus_foto)) {
            catatLog($conn, 'Hapus Foto Profil', 'Owner menghapus foto profil');
            $_SESSION['feedback'] = [
                'type' => 'success',
                'msg'  => 'Foto profil berhasil dihapus, inisial akun diaktifkan!'
            ];
        } else {
            $_SESSION['feedback'] = [
                'type' => 'error',
                'msg'  => 'Gagal menghapus foto profil di database.'
            ];
        }

        echo "<script>window.location.href='../owner/index.php?section=profil';</script>";
        exit;
    }
}
?>