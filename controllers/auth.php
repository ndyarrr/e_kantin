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


        case 'pembeli':
            $identifier = trim($_POST['identifier'] ?? '');
            if (empty($identifier))
                return "NISN atau NUPTK wajib diisi.";

            $id = mysqli_real_escape_string($conn, $identifier);

            // Coba cari di tabel murid dulu
            $res = mysqli_query($conn, "SELECT * FROM murid WHERE nisn = '$id' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if ($user) {
                // Ketemu di murid
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                $_SESSION['user_id'] = $user['nisn'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'siswa';

                header('Location: ../views/siswa/dashboard.php');
                exit;
            }

            // Kalau tidak ketemu, coba di tabel guru
            $res = mysqli_query($conn, "SELECT * FROM guru WHERE nuptk = '$id' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if ($user) {
                // Ketemu di guru
                if ($user['password'] !== md5($pass))
                    return "Password salah.";

                $_SESSION['user_id'] = $user['nuptk'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = 'guru';

                header('Location: ../views/guru/dashboard.php');
                exit;
            }

            return "NISN atau NUPTK tidak ditemukan.";
        // ----------------------------------------
        // SISWA — nama + nisn + password (MD5)
        // ----------------------------------------
        case 'siswa':
            $nisn = trim($_POST['nisn'] ?? '');
            if (empty($nisn))
                return "NISN wajib diisi.";
            $pass = $_POST['password'] ?? '';
            if (empty($pass))
                return "Password wajib diisi.";

            $n = mysqli_real_escape_string($conn, $nisn);
            $res = mysqli_query($conn, "SELECT * FROM murid WHERE nisn = '$n' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "NISN tidak ditemukan.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $_SESSION['user_id'] = $user['nisn'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'siswa';

            header('Location: ../views/siswa/dashboard.php');
            exit;

        case 'guru':
            $nuptk = trim($_POST['nuptk'] ?? '');
            if (empty($nuptk))
                return "NUPTK wajib diisi.";
            $pass = $_POST['password'] ?? '';
            if (empty($pass))
                return "Password wajib diisi.";

            $n = mysqli_real_escape_string($conn, $nuptk);
            $res = mysqli_query($conn, "SELECT * FROM guru WHERE nuptk = '$n' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "NUPTK tidak ditemukan.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $_SESSION['user_id'] = $user['nuptk'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'guru';

            header('Location: ../views/guru/dashboard.php');
            exit;

        case 'penjual':
            $nama = trim($_POST['username'] ?? ''); // field_name di form masih 'username'
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

            // validasi toko
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

            // Update waktu login terakhir
            $id = (int) $user['id_admin'];
            mysqli_query($conn, "UPDATE admin SET terakhir_login = NOW() WHERE id_admin = $id");

            $_SESSION['user_id'] = $user['id_admin'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'admin';

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