<?php
require_once __DIR__ . '/../config/database.php';

function login()
{
    global $conn;

    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'siswa';

    if (empty($username) || empty($pass)) {
        return "Username dan password wajib diisi.";
    }

    switch ($role) {

        // ----------------------------------------
        // SISWA — nama + nisn + password (MD5)
        // ----------------------------------------
        case 'siswa':
            $nisn = trim($_POST['nisn'] ?? '');
            if (empty($nisn))
                return "NISN wajib diisi.";

            $u = mysqli_real_escape_string($conn, $username);
            $n = mysqli_real_escape_string($conn, $nisn);

            $res = mysqli_query($conn, "SELECT * FROM murid WHERE nama = '$u' AND nisn = '$n' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Username atau NISN tidak sesuai.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $_SESSION['user_id'] = $user['nisn'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'siswa';

            header('Location: ../views/siswa/dashboard.php');
            exit;

        // ----------------------------------------
        // GURU — nama + nuptk + password (MD5)
        // ----------------------------------------
        case 'guru':
            $nuptk = trim($_POST['nuptk'] ?? '');
            if (empty($nuptk))
                return "NUPTK wajib diisi.";

            $u = mysqli_real_escape_string($conn, $username);
            $n = mysqli_real_escape_string($conn, $nuptk);

            $res = mysqli_query($conn, "SELECT * FROM guru WHERE nama = '$u' AND nuptk = '$n' LIMIT 1");
            $user = mysqli_fetch_assoc($res);

            if (!$user)
                return "Username atau NUPTK tidak sesuai.";
            if ($user['password'] !== md5($pass))
                return "Password salah.";

            $_SESSION['user_id'] = $user['nuptk'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = 'guru';

            header('Location: ../views/guru/dashboard.php');
            exit;

        // ----------------------------------------
        // KANTIN — nama penjual + id_toko + password (MD5)
        // Catatan: tabel penjual belum punya kolom password,
        // sementara pakai password = md5(id_penjual) sebagai default
        // ----------------------------------------
        case 'kantin':
            $nomor_lapak = trim($_POST['nomor_lapak'] ?? '');
            if (empty($nomor_lapak))
                return "Nomor lapak wajib diisi.";

            $u = mysqli_real_escape_string($conn, $username);
            $l = (int) $nomor_lapak;

            // Cari penjual berdasarkan nama + verifikasi toko miliknya
            $res = mysqli_query($conn, "
                SELECT p.*, t.id_toko FROM penjual p
                JOIN toko t ON t.id_penjual = p.id_penjual
                WHERE p.nama = '$u' AND t.id_toko = $l
                LIMIT 1
            ");
            $penjual = mysqli_fetch_assoc($res);

            if (!$penjual)
                return "Nama atau nomor lapak tidak sesuai.";

            // Sementara password default = md5(id_penjual) karena kolom password belum ada
            $defaultPass = md5($penjual['id_penjual']);
            if (md5($pass) !== $defaultPass)
                return "Password salah.";

            $_SESSION['user_id'] = $penjual['id_penjual'];
            $_SESSION['user_nama'] = $penjual['nama'];
            $_SESSION['user_role'] = 'kantin';
            $_SESSION['id_toko'] = $penjual['id_toko'];

            header('Location: ../views/kantin/dashboard.php');
            exit;

        // ----------------------------------------
        // ADMIN — nama + kode_aktivasi + password (MD5)
        // ----------------------------------------
        case 'admin':
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

            header('Location: ../views/admin/dashboard.php');
            exit;

        default:
            return "Role tidak valid.";
    }
}

function logout()
{
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}