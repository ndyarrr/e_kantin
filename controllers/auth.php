<?php
// controllers/auth.php

require_once __DIR__ . '/../config/database.php';

function login()
{
    global $conn;

    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($pass) || empty($role)) {
        return "Password dan role wajib diisi.";
    }

    switch ($role) {

        // ----------------------------------------
        // PEMBELI — siswa (NISN 10 digit) atau guru (NUPTK 16 digit)
        // ----------------------------------------
        case 'pembeli':
            $identifier = trim($_POST['identifier'] ?? '');
            $tipe_pembeli = $_POST['tipe_pembeli'] ?? 'siswa';

            $_SESSION['last_tipe_pembeli'] = $tipe_pembeli;

            if (empty($identifier)) {
                return $tipe_pembeli === 'siswa' ? "NISN wajib diisi." : "NUPTK atau Nama Guru wajib diisi.";
            }

            $id = mysqli_real_escape_string($conn, $identifier);

            // ════ ALUR LOGIN SISWA ════
            if ($tipe_pembeli === 'siswa') {
                if (!ctype_digit($identifier) || strlen($identifier) !== 10) {
                    return "NISN siswa harus berupa 10 digit angka.";
                }

                $res = mysqli_query($conn, "SELECT * FROM murid WHERE nisn = '$id' AND deleted_at IS NULL LIMIT 1");
                $user = mysqli_fetch_assoc($res);

                if (!$user)
                    return "NISN tidak ditemukan.";
                if ($user['status'] !== 'aktif')
                    return "akun dinonaktifkan";
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                mysqli_query($conn, "UPDATE murid SET terakhir_login = NOW() WHERE nisn = '$id'");

                                $_SESSION['user_id'] = $user['nisn'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'siswa';
                $_SESSION['user_foto'] = $user['foto_profil'];

                catatLog($conn, 'Login', 'Siswa berhasil login');

                // Redirect ke dashboard siswa
                header('Location: ../views/pembeli/index.php');
                exit;

                // ════ ALUR LOGIN GURU ════
            } else {
                if (ctype_digit($identifier)) {
                    if (strlen($identifier) !== 16) {
                        return "NUPTK guru harus tepat 16 digit angka.";
                    }
                    $query = "SELECT * FROM guru WHERE nuptk = '$id' AND deleted_at IS NULL LIMIT 1";
                } else {
                    $query = "SELECT * FROM guru WHERE nama = '$id' AND deleted_at IS NULL LIMIT 1";
                }

                $res = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($res);

                if (!$user) {
                    return ctype_digit($identifier) ? "NUPTK tidak ditemukan." : "Nama Guru tidak ditemukan.";
                }
                if ($user['status'] !== 'aktif')
                    return "akun dinonaktifkan";
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                $nuptk_guru = $user['nuptk'];
                mysqli_query($conn, "UPDATE guru SET terakhir_login = NOW() WHERE nuptk = '$nuptk_guru'");

                                $_SESSION['user_id'] = $user['nuptk'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'guru';
                $_SESSION['user_foto'] = $user['foto_profil'];

                catatLog($conn, 'Login', 'Guru berhasil login');

                // REDIRECT DISAMAKAN: Ikut masuk ke folder siswa
                header('Location: ../views/pembeli/index.php');
                exit;
            }
            break;

        // ----------------------------------------
        // PENJUAL — username + id_toko + password (MD5) + sub-role (Owner/Staf)
        // ----------------------------------------
        case 'penjual':
            $username = trim($_POST['username'] ?? '');
            $id_toko = (int) ($_POST['id_toko'] ?? 0);
            $tipe_penjual = trim($_POST['tipe_penjual'] ?? 'staf'); // 'owner' atau 'staf'

            if (empty($username))
                return "Username wajib diisi.";
            if (!$id_toko)
                return "Pilih kantin terlebih dahulu.";

            $u = mysqli_real_escape_string($conn, $username);

            // 1. CARI USER BERDASARKAN USERNAME DAN ROLE (OWNER/STAF)
            $res = mysqli_query($conn, "
                SELECT * FROM penjual 
                WHERE username = '$u' 
                  AND role = '$tipe_penjual' 
                LIMIT 1
            ");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Username atau sub-role tidak cocok.";
            if ($user['status'] !== 'aktif')
                return "akun dinonaktifkan";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $pid = (int) $user['id_penjual'];

            // 2. CEK APAKAH USER TERDAFTAR DI KANTIN TERSEBUT DAN KANTIN TIDAK DIHAPUS
            $cek = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT tp.id FROM toko_penjual tp 
                 JOIN toko t ON tp.id_toko = t.id_toko 
                 WHERE tp.id_penjual=$pid 
                   AND tp.id_toko=$id_toko 
                   AND tp.status='aktif' 
                   AND t.deleted_at IS NULL 
                 LIMIT 1"
            ));
            if (!$cek)
                return "Kamu tidak terdaftar di kantin tersebut atau kantin sudah dinonaktifkan.";

            mysqli_query($conn, "UPDATE penjual SET terakhir_login = NOW() WHERE id_penjual = $pid");

            // 3. SET DATA KE SESSION
            $_SESSION['user_id'] = $user['id_penjual'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'penjual';
            $_SESSION['user_sub_role'] = $user['role']; // Simpan 'owner' atau 'staf'
            $_SESSION['user_foto'] = $user['foto_profil'];
            $_SESSION['id_toko'] = $id_toko;

            catatLog($conn, 'Login', "Penjual ({$user['role']}) berhasil login");

            // 4. REDIRECT BERDASARKAN SUB-ROLE
            if ($user['role'] === 'owner') {
                // Diarahkan masuk ke views/penjual/owner/index.php
                header('Location: ../views/penjual/owner/index.php');
            } else {
                // Diarahkan masuk ke views/penjual/staf/index.php
                header('Location: ../views/penjual/staf/index.php');
            }
            exit;

        // ----------------------------------------
        // ADMIN — nama + kode_aktivasi + password (MD5)
        // ----------------------------------------
        case 'admin':
            $username = trim($_POST['username'] ?? '');
            $kode = trim($_POST['kode_aktivasi'] ?? '');

            if (empty($username))
                return "Username wajib diisi.";
            if (empty($kode))
                return "Kode aktivasi wajib diisi.";

            $u = mysqli_real_escape_string($conn, $username);
            $k = mysqli_real_escape_string($conn, $kode);
            $res = mysqli_query($conn, "
                SELECT * FROM admin
                WHERE nama = '$u' AND kode_aktivasi = '$k'
                LIMIT 1
            ");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Username atau kode aktivasi tidak sesuai.";
            if ($user['status'] !== 'aktif')
                return "akun dinonaktifkan";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $id = (int) $user['id_admin'];
            mysqli_query($conn, "UPDATE admin SET terakhir_login = NOW() WHERE id_admin = $id");

            $_SESSION['user_id'] = $user['id_admin'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_foto'] = $user['foto_profil'];

            // 🔥 FIX UTAMA: Daftarkan role_level murni database ke dalam Session browser!
            $_SESSION['role_level'] = (int) ($user['role_level'] ?? 1);

            catatLog($conn, 'Login', 'Admin berhasil login');

            header('Location: ../views/admin/?section=dashboard');
            exit;

        default:
            return "Role tidak valid.";
    }
}

function logout()
{
    session_destroy();
    header('Location: ../views/login/index.php');
    exit;
}