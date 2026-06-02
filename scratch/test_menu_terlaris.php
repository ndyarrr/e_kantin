<?php
require_once __DIR__ . '/../config/database.php';

echo "=== CURRENT MENU TERJUAL VALUES ===\n";
$res = mysqli_query($conn, "SELECT id_menu, nama_menu, terjual FROM menu ORDER BY terjual DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id_menu']} | Name: {$row['nama_menu']} | Terjual (DB column): {$row['terjual']}\n";
}

echo "\n=== ACTUAL SALES FROM COMPLETED ORDERS ===\n";
$res_actual = mysqli_query($conn, "
    SELECT dp.id_menu, m.nama_menu, SUM(dp.jumlah) AS actual_sold 
    FROM detail_pesanan dp
    JOIN pesanan p ON p.id_pesanan = dp.id_pesanan
    JOIN menu m ON m.id_menu = dp.id_menu
    WHERE p.status = 'selesai'
    GROUP BY dp.id_menu
    ORDER BY actual_sold DESC
");
if ($res_actual) {
    while ($row = mysqli_fetch_assoc($res_actual)) {
        echo "ID: {$row['id_menu']} | Name: {$row['nama_menu']} | Actual Sold: {$row['actual_sold']}\n";
        // Let's also update the column terjual to match the actual sold count!
        $id = (int)$row['id_menu'];
        $actual = (int)$row['actual_sold'];
        mysqli_query($conn, "UPDATE menu SET terjual = $actual WHERE id_menu = $id");
    }
} else {
    echo "No completed orders found or query failed.\n";
}

echo "\n=== AFTER SYNC MENU TERJUAL VALUES ===\n";
$res_new = mysqli_query($conn, "SELECT id_menu, nama_menu, terjual FROM menu ORDER BY terjual DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($res_new)) {
    echo "ID: {$row['id_menu']} | Name: {$row['nama_menu']} | Terjual (DB column): {$row['terjual']}\n";
}
