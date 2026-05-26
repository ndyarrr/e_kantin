<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Ambil koneksi database
require_once __DIR__ . '/../../../config/database.php';

// Pastikan $conn tersedia
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Gunakan user_id yang ada di session index.php
    $id_penjual = $_SESSION['user_id'] ?? 0; 
    
    // 🌟 DETEKSI ROLE & URL TUJUAN (Biar Staf gak nyasar ke Owner)
    $user_role   = $_SESSION['user_role'] ?? 'penjual';
    $folder_dest = ($user_role === 'staf') ? 'staf' : 'owner';
    $prefix_foto = ($user_role === 'staf') ? 'profil_staf_' : 'profil_owner_';
    $label_log   = ($user_role === 'staf') ? 'Staf' : 'Owner';

    // ==========================================================================
    // PROSES 1: EDIT DATA PROFIL & TOKO
    // ==========================================================================
    if ($action === 'edit_profil') {
        $nama      = mysqli_real_escape_string($conn, trim($_POST['nama']));
        $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
        
        $foto_query = ""; 
        
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto_profil']['tmp_name'];
            $fileName      = $_FILES['foto_profil']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // 🌟 FORMAT NAMA FILE DINAMIS: profil_staf_X.jpg atau profil_owner_X.jpg
                $newFileName = $prefix_foto . $id_penjual . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';
                
                if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);

                // HAPUS SEMUA FOTO LAMA USER INI AGAR TIDAK BENTROK
                foreach ($allowedExtensions as $ext) {
                    $file_lama_potensial = $uploadFileDir . $newFileName;
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
        
        // 🌟 UPDATE NAMA TOKO HANYA JIKA INPUTNYA DIKIRIM (Khusus Owner, Staf gak akan ngirim input ini)
        if (isset($_POST['nama_toko'])) {
            $nama_toko = mysqli_real_escape_string($conn, trim($_POST['nama_toko']));
            mysqli_query($conn, "UPDATE toko SET nama_toko = '$nama_toko' WHERE id_toko = (SELECT id_toko FROM toko_penjual WHERE id_penjual = '$id_penjual' LIMIT 1)");
        }

        catatLog($conn, 'Update Profil', "$label_log memperbarui data profil");

        // Kirim feedback banner sukses
        $_SESSION['feedback'] = [
            'type' => 'success',
            'msg'  => 'Profil & Foto berhasil diperbarui!'
        ];

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }

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
            echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
            exit;
        }

        // 2. Cek password lama ke database
        $res_pw = mysqli_query($conn, "SELECT password FROM penjual WHERE id_penjual = '$id_penjual'");
        if ($res_pw && $row = mysqli_fetch_assoc($res_pw)) {
            
            if (password_verify($password_lama, $row['password']) || $password_lama === $row['password']) {
                // Jika benar, lakukan hash dan update database
                $password_fix = password_hash($password_baru, PASSWORD_BCRYPT);
                mysqli_query($conn, "UPDATE penjual SET password = '$password_fix' WHERE id_penjual = '$id_penjual'");
                
                catatLog($conn, 'Update Password', "$label_log memperbarui password keamanan");

                $_SESSION['feedback'] = [
                    'type' => 'success',
                    'msg'  => 'Password keamanan berhasil diganti!'
                ];
            } else {
                // Jika password lama salah
                $_SESSION['feedback'] = [
                    'type' => 'error',
                    'msg'  => 'Password lama yang Anda masukkan salah!'
                ];
            }
        }

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }

    // ==========================================================================
    // PROSES 3: HAPUS FOTO PROFIL (KEMBALI KE INISIAL)
    // ==========================================================================
    if ($action === 'hapus_foto_profil') {
        $uploadFileDir = __DIR__ . '/../../../assets/img/penjual/';
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        // 1. Cari dan hapus fisik file gambar yang ada di server sesuai prefix user
        foreach ($allowedExtensions as $ext) {
            $file_foto = $uploadFileDir . $prefix_foto . $id_penjual . '.' . $ext;
            if (file_exists($file_foto)) {
                unlink($file_foto); 
            }
        }

        // 2. Kosongkan kolom foto_profil di database penjual
        $query_hapus_foto = "UPDATE penjual SET foto_profil = NULL WHERE id_penjual = '$id_penjual'";
        
        if (mysqli_query($conn, $query_hapus_foto)) {
            catatLog($conn, 'Hapus Foto Profil', "$label_log menghapus foto profil");
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

        echo "<script>window.location.href='../" . $folder_dest . "/index.php?section=profil';</script>";
        exit;
    }
}
?>