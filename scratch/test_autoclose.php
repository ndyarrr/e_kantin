<?php
require 'c:/laragon/www/e_kantin/config/database.php';

echo "=== AUTO-CLOSE SYSTEM DIAGNOSTIC ===\n";
echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current Date/Time: " . date('Y-m-d H:i:s') . "\n";
echo "Current Hour: " . date('H') . "\n";

$today = date('Y-m-d');
$q = mysqli_query($conn, "SELECT dibuat_pada, aksi, keterangan FROM log_sistem WHERE aksi = 'Auto-Close Kantin' AND dibuat_pada >= '$today 00:00:00' ORDER BY dibuat_pada DESC LIMIT 3");

if ($q && mysqli_num_rows($q) > 0) {
    echo "\nLogs found for today's Auto-Close:\n";
    while ($row = mysqli_fetch_assoc($q)) {
        echo "  - [{$row['dibuat_pada']}] Aksi: {$row['aksi']} | Ket: {$row['keterangan']}\n";
    }
} else {
    echo "\nNo logs found for today's Auto-Close yet.\n";
}

// Check open shops
$q_toko = mysqli_query($conn, "SELECT id_toko, nama_toko, status FROM toko");
echo "\nCurrent Toko statuses:\n";
while ($t = mysqli_fetch_assoc($q_toko)) {
    echo "  - Canteen: '{$t['nama_toko']}' (ID: {$t['id_toko']}) | Status: {$t['status']}\n";
}
