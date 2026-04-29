<?php
require_once __DIR__ . '/../config/database.php';

function login()
{
    global $conn;
    $error = null;
    $username = trim($_POST['username']);
    $pass = $_POST['password'];
    $role = $_POST['role'] ?? 'siswa';

    if (empty($username) || empty($pass)) {
        $error = "Username dan password wajib diisi.";
        return $error;
    }

    $usernameEsc = mysqli_real_escape_string($conn, $username);
    $roleEsc = mysqli_real_escape_string($conn, $role);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE nama = '$usernameEsc' AND role = '$roleEsc' LIMIT 1");
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($pass, $user['password'])) {
        $error = "Username atau password salah.";
        return $error;
    }

    // Verifikasi tambahan per role
    switch ($role) {
        case 'siswa':
            $nisn = trim($_POST['nisn'] ?? '');
            if (empty($nisn)) {
                return "NISN wajib diisi.";
            }
            $nisnEsc = mysqli_real_escape_string($conn, $nisn);
            $cek = mysqli_query($conn, "SELECT id FROM siswa WHERE user_id = '{$user['id']}' AND nisn = '$nisnEsc' LIMIT 1");
            if (mysqli_num_rows($cek) === 0) {
                return "NISN tidak sesuai.";
            }
            break;

        case 'guru':
            $nuptk = trim($_POST['nuptk'] ?? '');
            if (empty($nuptk)) {
                return "NUPTK wajib diisi.";
            }
            $nuptkEsc = mysqli_real_escape_string($conn, $nuptk);
            $cek = mysqli_query($conn, "SELECT id FROM users WHERE id = '{$user['id']}' AND nuptk = '$nuptkEsc' LIMIT 1");
            if (mysqli_num_rows($cek) === 0) {
                return "NUPTK tidak sesuai.";
            }
            break;

        case 'kantin':
            $lapak = trim($_POST['nomor_lapak'] ?? '');
            if (empty($lapak)) {
                return "Nomor lapak wajib diisi.";
            }
            $lapakEsc = mysqli_real_escape_string($conn, $lapak);
            $cek = mysqli_query($conn, "SELECT id FROM users WHERE id = '{$user['id']}' AND nomor_lapak = '$lapakEsc' LIMIT 1");
            if (mysqli_num_rows($cek) === 0) {
                return "Nomor lapak tidak sesuai.";
            }
            break;

        case 'admin':
            $kode = trim($_POST['kode_aktivasi'] ?? '');
            if (empty($kode)) {
                return "Kode aktivasi wajib diisi.";
            }
            $kodeEsc = mysqli_real_escape_string($conn, $kode);
            $cek = mysqli_query($conn, "SELECT id FROM users WHERE id = '{$user['id']}' AND kode_login = '$kodeEsc' LIMIT 1");
            if (mysqli_num_rows($cek) === 0) {
                return "Kode aktivasi tidak sesuai.";
            }
            break;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_role'] = $user['role'];

    // Redirect sesuai role
    switch ($role) {
        case 'admin':
            header('Location: ../views/admin/dashboard.php');
            break;
        case 'guru':
            header('Location: ../views/guru/dashboard.php');
            break;
        case 'siswa':
            header('Location: ../views/siswa/dashboard.php');
            break;
        case 'kantin':
            header('Location: ../views/kantin/dashboard.php');
            break;
        default:
            header('Location: ../index.php');
            break;
    }
    exit;
}

function logout()
{
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}