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

// Migrasi kolom deleted_at ke tabel kelas jika belum ada
$checkColKelasDel = mysqli_query($conn, "SHOW COLUMNS FROM `kelas` LIKE 'deleted_at'");
if ($checkColKelasDel && mysqli_num_rows($checkColKelasDel) === 0) {
    mysqli_query($conn, "ALTER TABLE `kelas` ADD `deleted_at` DATETIME NULL DEFAULT NULL");
}

// Migrasi kolom shift di toko_penjual dari ENUM ke VARCHAR agar lebih fleksibel
$checkShiftType = mysqli_query($conn, "SHOW COLUMNS FROM `toko_penjual` LIKE 'shift'");
if ($checkShiftType) {
    $row = mysqli_fetch_assoc($checkShiftType);
    if ($row && str_contains(strtolower($row['Type']), 'enum')) {
        mysqli_query($conn, "ALTER TABLE `toko_penjual` MODIFY `shift` VARCHAR(50) NULL DEFAULT NULL");
    }
}

// Migrasi tabel foto_latar_belakang jika belum ada
$checkLatarTable = mysqli_query($conn, "SHOW TABLES LIKE 'foto_latar_belakang'");
if ($checkLatarTable && mysqli_num_rows($checkLatarTable) === 0) {
    mysqli_query($conn, "CREATE TABLE `foto_latar_belakang` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_toko` INT NOT NULL,
        `gambar` VARCHAR(255) NOT NULL,
        `canvas_config` TEXT DEFAULT NULL,
        `urutan` INT NOT NULL DEFAULT 0,
        `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`id_toko`) REFERENCES `toko`(`id_toko`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** Alias lama — beberapa file memakai $koneksi */
$koneksi = $conn;

/** Konfigurasi Global Logging: Ubah ke false untuk mematikan pencatatan log sistem secara global */
if (!defined('SYSTEM_LOGGING_ACTIVE')) {
    define('SYSTEM_LOGGING_ACTIVE', true);
}

if (!function_exists('catatLog')) {
    function catatLog($conn, $aksi, $keterangan = '')
    {
        if (defined('SYSTEM_LOGGING_ACTIVE') && !SYSTEM_LOGGING_ACTIVE) {
            return;
        }
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
    $currentHour = (int)date('H');
    if ($currentHour >= 15) {
        $currentDate = date('Y-m-d');
        // Cek apakah hari ini sistem sudah melakukan auto-close secara global (di database)
        $qCheck = mysqli_query($conn, "SELECT 1 FROM log_sistem WHERE aksi = 'Auto-Close Kantin' AND dibuat_pada >= '$currentDate 00:00:00' LIMIT 1");
        if ($qCheck && mysqli_num_rows($qCheck) === 0) {
            // Tutup semua kantin yang masih buka
            mysqli_query($conn, "UPDATE `toko` SET `status` = 'tutup' WHERE `status` = 'buka'");

            // Catat ke log sistem - HARUS pakai 'admin' karena user_role adalah ENUM('admin','penjual','siswa','guru')
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                                 VALUES ('admin','0','Auto-Close System','Auto-Close Kantin','Menutup semua kantin secara otomatis pada pukul 15:00 WIB.','$ip')");
        }
    }
}
