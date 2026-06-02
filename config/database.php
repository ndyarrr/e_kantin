<?php
// Atur timezone default PHP agar sinkron dengan Waktu Indonesia Barat (WIB / GMT+7)
date_default_timezone_set('Asia/Jakarta');

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
// Atur timezone session MySQL agar sinkron dengan PHP (+07:00 / Asia/Jakarta)
mysqli_query($conn, "SET time_zone = '+07:00'");

// Migrasi kolom is_fleksibel ke tabel menu jika belum ada
$checkColMenu = mysqli_query($conn, "SHOW COLUMNS FROM `menu` LIKE 'is_fleksibel'");
if ($checkColMenu && mysqli_num_rows($checkColMenu) === 0) {
    mysqli_query($conn, "ALTER TABLE `menu` ADD `is_fleksibel` TINYINT(1) NOT NULL DEFAULT 0");
}

// Migrasi kolom harga ke tabel keranjang jika belum ada
$checkColKeranjang = mysqli_query($conn, "SHOW COLUMNS FROM `keranjang` LIKE 'harga'");
if ($checkColKeranjang && mysqli_num_rows($checkColKeranjang) === 0) {
    mysqli_query($conn, "ALTER TABLE `keranjang` ADD `harga` INT NOT NULL DEFAULT 0");
    mysqli_query($conn, "UPDATE `keranjang` k JOIN `menu` m ON k.id_menu = m.id_menu SET k.harga = m.harga");
    $checkIndex = mysqli_query($conn, "SHOW INDEX FROM `keranjang` WHERE Key_name = 'unique_user_menu'");
    if ($checkIndex && mysqli_num_rows($checkIndex) > 0) {
        mysqli_query($conn, "ALTER TABLE `keranjang` DROP INDEX `unique_user_menu`");
    }
    mysqli_query($conn, "ALTER TABLE `keranjang` ADD UNIQUE KEY `unique_user_menu_harga` (user_id, user_role, id_menu, harga)");
}

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

// Otomatisasi penutupan kantin jika sudah melewati pukul 15:00 WIB (Asia/Jakarta)
if ($conn && php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $currentDate = date('Y-m-d');
    if (($_SESSION['last_autoclose_checked'] ?? '') !== $currentDate) {
        $currentHour = (int)date('H');
        if ($currentHour >= 15) {
            // Cek apakah hari ini sistem sudah melakukan auto-close
            $qCheck = mysqli_query($conn, "SELECT 1 FROM log_sistem WHERE aksi = 'Auto-Close Kantin' AND dibuat_pada >= '$currentDate 00:00:00' LIMIT 1");
            if ($qCheck && mysqli_num_rows($qCheck) === 0) {
                // Tutup semua kantin yang masih buka
                $update = mysqli_query($conn, "UPDATE `toko` SET `status` = 'tutup' WHERE `status` = 'buka'");
                if ($update) {
                    // Catat ke log sistem
                    $role = 'sistem';
                    $uid = '0';
                    $nama = 'Auto-Close System';
                    $aksi = 'Auto-Close Kantin';
                    $ket = 'Menutup semua kantin secara otomatis pada pukul 15:00 WIB untuk mencegah kantin tetap terbuka jika staff/owner lupa.';
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    @mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                                         VALUES ('$role','$uid','$nama','$aksi','$ket','$ip')");
                }
            }
            $_SESSION['last_autoclose_checked'] = $currentDate;
        }
    }
}
