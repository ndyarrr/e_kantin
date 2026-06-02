<?php
require 'c:/laragon/www/e_kantin/config/database.php';

echo "=== DETAILS AND MENU FOR LAST 5 ORDERS ===\n";
$res_p = mysqli_query($conn, "SELECT id_pesanan, total_harga FROM pesanan ORDER BY id_pesanan DESC LIMIT 5");
while ($p = mysqli_fetch_assoc($res_p)) {
    $pid = $p['id_pesanan'];
    echo "\nOrder #$pid (Total: {$p['total_harga']}):\n";
    $res_d = mysqli_query($conn, "SELECT dp.* FROM detail_pesanan dp WHERE dp.id_pesanan = $pid");
    while ($dp = mysqli_fetch_assoc($res_d)) {
        echo "  - Detail: menu_id: {$dp['id_menu']}, qty: {$dp['jumlah']}, price: {$dp['harga_satuan']}\n";
        // Check if menu exists
        $mid = $dp['id_menu'];
        $res_m = mysqli_query($conn, "SELECT * FROM menu WHERE id_menu = $mid");
        if (mysqli_num_rows($res_m) > 0) {
            $m = mysqli_fetch_assoc($res_m);
            echo "    -> MATCHED Menu: '{$m['nama_menu']}' (stok: {$m['stok']}, tersedia: {$m['tersedia']}, deleted_at: " . ($m['deleted_at'] ?? 'NULL') . ")\n";
        } else {
            echo "    -> ORPHANED: Menu ID $mid NOT FOUND in menu table!\n";
        }
    }
}
