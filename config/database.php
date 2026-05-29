<?php
/**
 * Koneksi MySQL — kompatibel XAMPP (Linux) & PHP 8+
 */

mysqli_report(MYSQLI_REPORT_OFF);

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$db   = 'e_kantin';
$socket = null;

// XAMPP di Linux: socket bawaan (jika TCP gagal)
if (file_exists('/opt/lampp/var/mysql/mysql.sock')) {
    $socket = '/opt/lampp/var/mysql/mysql.sock';
}

// Override lokal (opsional): salin database.local.example.php → database.local.php
if (file_exists(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
}

$conn = @mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn && $socket) {
    $conn = @mysqli_connect($host, $user, $pass, $db, $port, $socket);
}

if (!$conn) {
    $detail = mysqli_connect_error();
    http_response_code(503);

    $pesan = 'Gagal menghubungkan ke server database. ';
    $pesan .= 'Pastikan MySQL sudah aktif (XAMPP: jalankan <code>sudo /opt/lampp/lampp start</code>).';

    if (ini_get('display_errors') && $detail) {
        $pesan .= '<br><small style="opacity:.85">Detail: ' . htmlspecialchars($detail) . '</small>';
    }

    if (php_sapi_name() === 'cli') {
        die(strip_tags($pesan) . ($detail ? " ($detail)" : ''));
    }

    die('<div style="font-family:sans-serif;max-width:520px;margin:40px auto;padding:24px;background:#fee2e2;color:#991b1b;border-radius:10px;line-height:1.5;">'
        . '<strong>Database tidak tersambung</strong><p>' . $pesan . '</p></div>');
}

mysqli_set_charset($conn, 'utf8mb4');

/** Alias lama — beberapa file memakai $koneksi */
$koneksi = $conn;

if (!function_exists('catatLog')) {
    function catatLog($conn, $aksi, $keterangan = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = mysqli_real_escape_string($conn, $_SESSION['user_role'] ?? '');
        $uid = mysqli_real_escape_string($conn, $_SESSION['user_id'] ?? '');
        $nama = mysqli_real_escape_string($conn, $_SESSION['user_nama'] ?? '');
        $aksi = mysqli_real_escape_string($conn, $aksi);
        $ket = mysqli_real_escape_string($conn, $keterangan);
        $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
        @mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                             VALUES ('$role','$uid','$nama','$aksi','$ket','$ip')");
    }
}
