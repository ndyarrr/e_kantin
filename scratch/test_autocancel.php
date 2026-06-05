<?php
require_once __DIR__ . '/../config/database.php';

// 1. Get initial stock of menu 1
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM menu WHERE id_menu = 1"));
$init_stok = (int)$r['stok'];
echo "Initial stock of menu 1: $init_stok\n";

// 2. Create yesterday's order (should be auto-cancelled)
$yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
mysqli_query($conn, "INSERT INTO pesanan (id_toko, nisn_pembeli, waktu_pesan, status, total_harga) 
                     VALUES (1, '1234567890', '$yesterday', 'menunggu', 5000)");
$id_pesanan = mysqli_insert_id($conn);
echo "Created dummy order #$id_pesanan with waktu_pesan = $yesterday\n";

mysqli_query($conn, "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan) 
                     VALUES ($id_pesanan, 1, 2, 2500)");

// Subtract stock to simulate order placement
mysqli_query($conn, "UPDATE menu SET stok = stok - 2 WHERE id_menu = 1");

$r_stok_after = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM menu WHERE id_menu = 1"));
echo "Stock after placement: {$r_stok_after['stok']} (expected " . ($init_stok - 2) . ")\n";

// 3. Trigger database check via subprocess
echo "Triggering database.php via subprocess...\n";
shell_exec("php scratch/run_db_inc.php");

// 4. Verify order status and stock recovery
$r_pesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM pesanan WHERE id_pesanan = $id_pesanan"));
$r_stok_final = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM menu WHERE id_menu = 1"));

echo "Final order status: {$r_pesanan['status']}\n";
echo "Final stock of menu 1: {$r_stok_final['stok']}\n";

if ($r_pesanan['status'] === 'dibatalkan' && (int)$r_stok_final['stok'] === $init_stok) {
    echo "SUCCESS: Order was auto-cancelled and stock restored!\n";
} else {
    echo "FAILED: Check logic.\n";
}

// 5. Clean up
mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_pesanan = $id_pesanan");
mysqli_query($conn, "DELETE FROM pesanan WHERE id_pesanan = $id_pesanan");
mysqli_query($conn, "UPDATE menu SET stok = $init_stok WHERE id_menu = 1");
echo "Cleaned up test data.\n";
