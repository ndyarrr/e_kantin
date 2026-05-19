<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login/index.php');
    exit;
}

$error = login();

if ($error) {
    $_SESSION['login_error'] = $error;
    header('Location: ../views/login/index.php');
    exit;
}

$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($role) || empty($password)) {
    $_SESSION['login_error'] = 'Role dan password wajib diisi!';
    header('Location: ../views/login/index.php');
    exit;
}

$hashed = md5($password);

if ($role === 'admin') {
    $username = $_POST['username'] ?? '';
    $kode_aktivasi = $_POST['kode_aktivasi'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE nama = ? AND password = ? AND status = 'aktif' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $hashed);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user) {
        $_SESSION['login_error'] = 'Username atau password salah!';
        header('Location: ../views/login/index.php');
        exit;
    }

    if ($user['kode_aktivasi'] !== $kode_aktivasi) {
        $_SESSION['login_error'] = 'Kode aktivasi salah!';
        header('Location: ../views/login/index.php');
        exit;
    }

    $upd = mysqli_prepare($conn, "UPDATE admin SET terakhir_login = NOW() WHERE id_admin = ?");
    mysqli_stmt_bind_param($upd, 'i', $user['id_admin']);
    mysqli_stmt_execute($upd);

    $_SESSION['role'] = 'admin';
    $_SESSION['id'] = $user['id_admin'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['foto'] = $user['foto_profil'];
    header('Location: ../views/admin/index.php');
    exit;
}

if ($role === 'siswa') {
    $nisn = $_POST['nisn'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM murid WHERE nisn = ? AND password = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $nisn, $hashed);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user) {
        $_SESSION['login_error'] = 'NISN atau password salah!';
        header('Location: ../views/login/index.php');
        exit;
    }

    $_SESSION['role'] = 'siswa';
    $_SESSION['id'] = $user['nisn'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['foto'] = $user['foto_profil'];
    header('Location: ../views/siswa/dashboard.php');
    exit;
}

if ($role === 'guru') {
    $nuptk = $_POST['nuptk'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM guru WHERE nuptk = ? AND password = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $nuptk, $hashed);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user) {
        $_SESSION['login_error'] = 'NUPTK atau password salah!';
        header('Location: ../views/login/index.php');
        exit;
    }

    $_SESSION['role'] = 'guru';
    $_SESSION['id'] = $user['nuptk'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['foto'] = $user['foto_profil'];
    header('Location: ../views/guru/dashboard.php');
    exit;
}

if ($role === 'penjual') {
    $username = $_POST['username'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM penjual WHERE username = ? AND password = ? AND status = 'aktif' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $hashed);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user) {
        $_SESSION['login_error'] = 'Username atau password salah!';
        header('Location: ../views/login/index.php');
        exit;
    }

    $upd = mysqli_prepare($conn, "UPDATE penjual SET terakhir_login = NOW() WHERE id_penjual = ?");
    mysqli_stmt_bind_param($upd, 'i', $user['id_penjual']);
    mysqli_stmt_execute($upd);

    $_SESSION['role'] = 'penjual';
    $_SESSION['id'] = $user['id_penjual'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['foto'] = $user['foto_profil'];
    header('Location: ../views/penjual/dashboard.php');
    exit;
}

$_SESSION['login_error'] = 'Role tidak valid!';
header('Location: ../views/login/index.php');
exit;