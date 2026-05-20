<?php
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
            if (empty($identifier))
                return "NISN atau NUPTK wajib diisi.";
            if (!ctype_digit($identifier))
                return "NISN/NUPTK hanya boleh angka.";

            $len = strlen($identifier);
            $id = mysqli_real_escape_string($conn, $identifier);

            if ($len === 10) {
                $res = mysqli_query($conn, "SELECT * FROM murid WHERE nisn = '$id' LIMIT 1");
                $user = mysqli_fetch_assoc($res);

                if (!$user)
                    return "NISN tidak ditemukan.";
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                mysqli_query($conn, "UPDATE murid SET terakhir_login = NOW() WHERE nisn = '$id'");

                $_SESSION['user_id'] = $user['nisn'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'siswa';
                $_SESSION['user_foto'] = $user['foto_profil'];

                header('Location: ../views/siswa/dashboard.php');
                exit;

            } elseif ($len === 16) {
                $res = mysqli_query($conn, "SELECT * FROM guru WHERE nuptk = '$id' LIMIT 1");
                $user = mysqli_fetch_assoc($res);

                if (!$user)
                    return "NUPTK tidak ditemukan.";
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                mysqli_query($conn, "UPDATE guru SET terakhir_login = NOW() WHERE nuptk = '$id'");

                $_SESSION['user_id'] = $user['nuptk'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'guru';
                $_SESSION['user_foto'] = $user['foto_profil'];

                header('Location: ../views/guru/dashboard.php');
                exit;

            } else {
                return "NISN harus 10 digit, NUPTK harus 16 digit.";
            }

        // ----------------------------------------
        // PENJUAL — username + id_toko + password (MD5)
        // ----------------------------------------
        case 'penjual':
            $nama = trim($_POST['username'] ?? '');
            $id_toko = (int) ($_POST['id_toko'] ?? 0);

            if (empty($nama))
                return "Nama wajib diisi.";
            if (!$id_toko)
                return "Pilih kantin terlebih dahulu.";

            $n = mysqli_real_escape_string($conn, $nama);
            $res = mysqli_query($conn, "SELECT * FROM penjual WHERE nama = '$n' AND status = 'aktif' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Nama tidak ditemukan.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $pid = (int) $user['id_penjual'];
            $cek = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT id FROM toko_penjual WHERE id_penjual=$pid AND id_toko=$id_toko AND status='aktif' LIMIT 1"
            ));
            if (!$cek)
                return "Kamu tidak terdaftar di kantin tersebut.";

            mysqli_query($conn, "UPDATE penjual SET terakhir_login = NOW() WHERE id_penjual = $pid");

            $_SESSION['user_id'] = $user['id_penjual'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'penjual';
            $_SESSION['user_foto'] = $user['foto_profil'];
            $_SESSION['id_toko'] = $id_toko;

            header('Location: ../views/penjual/dashboard.php');
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
                WHERE nama = '$u' AND kode_aktivasi = '$k' AND status = 'aktif'
                LIMIT 1
            ");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Username atau kode aktivasi tidak sesuai.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $id = (int) $user['id_admin'];
            mysqli_query($conn, "UPDATE admin SET terakhir_login = NOW() WHERE id_admin = $id");

            $_SESSION['user_id'] = $user['id_admin'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_foto'] = $user['foto_profil'];

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