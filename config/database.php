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

// Hanya jalankan migrasi database jika database sudah diinisialisasi (tabel utama `toko` sudah ada).
// Hal ini mencegah error saat database di-drop atau dalam kondisi kosong (belum di-import schema utama),
// karena file ini terpanggil otomatis pada setiap load PHP (termasuk background request/AJAX)
// yang akan membuat tabel `pengaturan` tersisa sendiri sementara tabel lain gagal terbuat karena constraint foreign key.
$checkTokoTable = mysqli_query($conn, "SHOW TABLES LIKE 'toko'");
if ($checkTokoTable && mysqli_num_rows($checkTokoTable) > 0) {
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

    // Migrasi tabel pengaturan jika belum ada
    $checkPengaturanTable = mysqli_query($conn, "SHOW TABLES LIKE 'pengaturan'");
    if ($checkPengaturanTable && mysqli_num_rows($checkPengaturanTable) === 0) {
        mysqli_query($conn, "CREATE TABLE `pengaturan` (
            `kunci` VARCHAR(50) PRIMARY KEY,
            `nilai` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Seed default slot kantin
        mysqli_query($conn, "INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES ('slot_kantin', '10')");
    }

    // Migrasi kolom urutan ke tabel toko jika belum ada
    $checkColTokoUrutan = mysqli_query($conn, "SHOW COLUMNS FROM `toko` LIKE 'urutan'");
    if ($checkColTokoUrutan && mysqli_num_rows($checkColTokoUrutan) === 0) {
        mysqli_query($conn, "ALTER TABLE `toko` ADD `urutan` INT NOT NULL DEFAULT 0");
        // Seed default values: set urutan = id_toko to preserve chronological order initially
        mysqli_query($conn, "UPDATE `toko` SET `urutan` = `id_toko` WHERE `deleted_at` IS NULL");
    }

    // Migrasi kolom status di tabel pesanan untuk mendukung 'tidak_diambil'
    $checkColPesananStatus = mysqli_query($conn, "SHOW COLUMNS FROM `pesanan` LIKE 'status'");
    if ($checkColPesananStatus) {
        $row = mysqli_fetch_assoc($checkColPesananStatus);
        if ($row && !str_contains($row['Type'], 'tidak_diambil')) {
            mysqli_query($conn, "ALTER TABLE `pesanan` MODIFY `status` ENUM('menunggu','dikonfirmasi','siap_diambil','selesai','dibatalkan','tidak_diambil') NOT NULL DEFAULT 'menunggu'");
        }
    }

    require_once __DIR__ . '/kantin_slot.php';
    kantinSlotMigrate($conn);
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

// Otomatisasi pembatalan dan penyesuaian pesanan yang melewati pukul 15:00 WIB (Asia/Jakarta)
if ($conn && php_sapi_name() !== 'cli') {
    $currentTime = date('H:i');
    $currentDate = date('Y-m-d');
    
    if ($currentTime >= '15:00') {
        $limitTimestamp = $currentDate . ' 23:59:59';
        
        // A. Proses pesanan yang masih 'menunggu' -> Dibatalkan (dan refund jika QRIS sudah upload bukti)
        $sqlMenunggu = "SELECT p.id_pesanan, pb.metode, pb.bukti_foto, pb.status AS status_pembayaran
                        FROM pesanan p
                        LEFT JOIN pembayaran pb ON pb.id_pesanan = p.id_pesanan
                        WHERE p.status = 'menunggu' 
                          AND p.waktu_pesan < '$limitTimestamp'";
        $resMenunggu = mysqli_query($conn, $sqlMenunggu);
        if ($resMenunggu && mysqli_num_rows($resMenunggu) > 0) {
            while ($row = mysqli_fetch_assoc($resMenunggu)) {
                $id_pes = (int)$row['id_pesanan'];
                
                mysqli_begin_transaction($conn);
                try {
                    // Kembalikan stok menu
                    $resItems = mysqli_query($conn, "SELECT id_menu, jumlah FROM detail_pesanan WHERE id_pesanan = $id_pes");
                    if ($resItems) {
                        while ($item = mysqli_fetch_assoc($resItems)) {
                            $id_menu = (int)$item['id_menu'];
                            $jumlah = (int)$item['jumlah'];
                            mysqli_query($conn, "UPDATE menu SET stok = stok + $jumlah WHERE id_menu = $id_menu");
                        }
                    }
                    
                    // Ubah status pesanan menjadi dibatalkan
                    mysqli_query($conn, "UPDATE pesanan SET status = 'dibatalkan' WHERE id_pesanan = $id_pes");
                    
                    // Jika QRIS (transfer) dan ada bukti upload atau sudah lunas -> Tandai sebagai 'dikembalikan' (Refunded)
                    $refund_info = "";
                    if ($row['metode'] === 'transfer' && (!empty($row['bukti_foto']) || $row['status_pembayaran'] === 'lunas')) {
                        mysqli_query($conn, "UPDATE pembayaran SET status = 'dikembalikan' WHERE id_pesanan = $id_pes");
                        $refund_info = " Uang QRIS akan dikembalikan (refund).";
                    }
                    
                    // Kirim pesan chat otomatis pembatalan ke pembeli
                    $q_pesanan_info = mysqli_query($conn, "SELECT nisn_pembeli, nuptk_pembeli, id_toko FROM pesanan WHERE id_pesanan = $id_pes LIMIT 1");
                    if ($q_pesanan_info && mysqli_num_rows($q_pesanan_info) > 0) {
                        $r_p = mysqli_fetch_assoc($q_pesanan_info);
                        $id_tok = (int)$r_p['id_toko'];
                        $penerima_chat = '';
                        if (!empty($r_p['nisn_pembeli'])) {
                            $penerima_chat = 'murid_' . $r_p['nisn_pembeli'];
                        } elseif (!empty($r_p['nuptk_pembeli'])) {
                            $penerima_chat = 'guru_' . $r_p['nuptk_pembeli'];
                        }

                        if (!empty($penerima_chat)) {
                            $pengirim_chat = 'toko_' . $id_tok;
                            $auto_status_msg = '[AUTO_REPLY_STATUS]
                            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
                                <div style="font-weight:800;font-size:14px;color:#f43f5e;margin-bottom:6px;">Pesanan #' . $id_pes . ' Dibatalkan Otomatis</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Pesanan Anda dibatalkan otomatis oleh sistem karena telah melewati batas waktu operasional (pukul 15:00 WIB) dan belum diproses.' . $refund_info . '</div>
                                <div style="padding:10px 12px;background:#fff1f2;border-radius:10px;border:1px solid #fecaca;display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-size:12px;font-weight:600;color:#f43f5e;">Status Terbaru</span>
                                    <span style="font-size:12px;font-weight:800;color:#e11d48;">Dibatalkan ❌</span>
                                </div>
                            </div>';

                            $msg_escaped = mysqli_real_escape_string($conn, $auto_status_msg);
                            mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                                 VALUES ('$pengirim_chat', '$penerima_chat', '$msg_escaped', NOW(), 0)");
                        }
                    }
                    
                    // Catat ke log sistem
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                                         VALUES ('admin','0','Auto-Cancel System','Batal Otomatis','Pesanan #$id_pes dibatalkan otomatis (Menunggu) karena melewati pukul 15:00 WIB.','$ip')");
                    
                    mysqli_commit($conn);
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                }
            }
        }
        
        // B. Proses pesanan yang masih 'siap_diambil' -> Tidak Diambil (No-Show, stok TIDAK dikembalikan, tidak ada refund)
        $sqlSiap = "SELECT p.id_pesanan, pb.status AS status_pembayaran 
                    FROM pesanan p
                    LEFT JOIN pembayaran pb ON pb.id_pesanan = p.id_pesanan
                    WHERE p.status = 'siap_diambil' 
                      AND p.waktu_pesan < '$limitTimestamp'";
        $resSiap = mysqli_query($conn, $sqlSiap);
        if ($resSiap && mysqli_num_rows($resSiap) > 0) {
            while ($row = mysqli_fetch_assoc($resSiap)) {
                $id_pes = (int)$row['id_pesanan'];
                $status_bayar = $row['status_pembayaran'] ?? 'belum_bayar';
                
                mysqli_begin_transaction($conn);
                try {
                    // Ubah status pesanan menjadi tidak_diambil
                    mysqli_query($conn, "UPDATE pesanan SET status = 'tidak_diambil' WHERE id_pesanan = $id_pes");
                    
                    // Kirim pesan chat otomatis pembatalan ke pembeli
                    $q_pesanan_info = mysqli_query($conn, "SELECT nisn_pembeli, nuptk_pembeli, id_toko FROM pesanan WHERE id_pesanan = $id_pes LIMIT 1");
                    if ($q_pesanan_info && mysqli_num_rows($q_pesanan_info) > 0) {
                        $r_p = mysqli_fetch_assoc($q_pesanan_info);
                        $id_tok = (int)$r_p['id_toko'];
                        $penerima_chat = '';
                        if (!empty($r_p['nisn_pembeli'])) {
                            $penerima_chat = 'murid_' . $r_p['nisn_pembeli'];
                        } elseif (!empty($r_p['nuptk_pembeli'])) {
                            $penerima_chat = 'guru_' . $r_p['nuptk_pembeli'];
                        }

                        if (!empty($penerima_chat)) {
                            $pengirim_chat = 'toko_' . $id_tok;
                            $msg_detail = ($status_bayar === 'lunas')
                                ? 'Pesanan Anda tidak diambil sampai batas operasional berakhir. Karena pembayaran Anda sudah lunas (QRIS/Transfer), Anda tidak dikenakan sanksi pembatasan metode tunai.'
                                : 'Pesanan Anda tidak diambil sampai batas operasional berakhir. Akun Anda dicatat melakukan pelanggaran no-show dan fitur pembayaran tunai akan dibatasi sampai pesanan ini dilunasi.';

                            $auto_status_msg = '[AUTO_REPLY_STATUS]
                            <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:320px;padding:4px;">
                                <div style="font-weight:800;font-size:14px;color:#e11d48;margin-bottom:6px;">Pesanan #' . $id_pes . ' Tidak Diambil!</div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:12px;">' . $msg_detail . '</div>
                                <div style="padding:10px 12px;background:#fff1f2;border-radius:10px;border:1px solid #fecaca;display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-size:12px;font-weight:600;color:#e11d48;">Status Terbaru</span>
                                    <span style="font-size:12px;font-weight:800;color:#be123c;">Tidak Diambil ⚠️</span>
                                </div>
                            </div>';

                            $msg_escaped = mysqli_real_escape_string($conn, $auto_status_msg);
                            mysqli_query($conn, "INSERT INTO pesan_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim, sudah_dibaca)
                                                 VALUES ('$pengirim_chat', '$penerima_chat', '$msg_escaped', NOW(), 0)");
                        }
                    }
                    
                    // Catat ke log sistem
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    mysqli_query($conn, "INSERT INTO log_sistem (user_role, user_id, user_nama, aksi, keterangan, ip_address)
                                         VALUES ('admin','0','Auto-Cancel System','Tidak Diambil','Pesanan #$id_pes ditandai Tidak Diambil (No-Show) karena melewati pukul 15:00 WIB.','$ip')");
                    
                    mysqli_commit($conn);
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                }
            }
        }
    }
}
