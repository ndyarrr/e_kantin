<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/KodeController.php';

function login()
{
    global $conn;
    $error = null;
    $username = trim($_POST['username']);
    $pass = $_POST['password'];

    if (empty($username) || empty($pass)) {
        $error = "Username dan password wajib diisi.";
    } else {
        $usernameEsc = mysqli_real_escape_string($conn, $username);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE nama = '$usernameEsc' LIMIT 1");
        $user = mysqli_fetch_assoc($result);

        if (!$user || !password_verify($pass, $user['password'])) {
            $error = "Username atau password salah.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect sesuai role
            switch ($user['role']) {
                case 'admin':
                    header('Location: ../views/admin/dashboard.php');
                    break;
                case 'guru':
                    header('Location: ../views/guru/dashboard.php');
                    break;
                case 'siswa':
                    header('Location: ../views/siswa/dashboard.php');
                    break;
                default:
                    header('Location: ../index.php');
                    break;
            }
            exit;
        }
    }

    return $error;
}

function register()
{
    global $conn;
    $error = null;
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $pass = $_POST['password'];
    $konfirm = $_POST['konfirm_password'];
    $role = $_POST['role'] ?? 'siswa';
    $kode = trim($_POST['kode_aktivasi'] ?? '');

    if (empty($nama) || empty($username) || empty($pass) || empty($konfirm)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter.";
    } elseif (strlen($pass) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($pass !== $konfirm) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif ($role === 'guru' && empty($kode)) {
        $error = "Kode aktivasi wajib diisi untuk guru.";
    } elseif ($role === 'guru' && validasiKode($kode) === false) {
        $error = "Kode aktivasi tidak valid atau sudah dipakai.";
    } else {
        $usernameEsc = mysqli_real_escape_string($conn, $username);
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$usernameEsc' LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            $namaEsc = mysqli_real_escape_string($conn, $nama);
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (nama, username, password, role)
                        VALUES ('$namaEsc', '$usernameEsc', '$hash', '$role')";

            if (mysqli_query($conn, $sql)) {
                if ($role === 'guru') {
                    tandaiKodeTerpakai($kode, mysqli_insert_id($conn));
                }
                header('Location: ../auth/login.php?sukses=1');
                exit;
            } else {
                $error = "Gagal mendaftar, coba lagi.";
            }
        }
    }

    return $error;
}

function logout()
{
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}