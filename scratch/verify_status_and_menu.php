<?php
require_once __DIR__ . '/../config/database.php';

echo "=== CHECKING MENUS ===\n";
$resMenus = mysqli_query($conn, "SELECT id_menu, nama_menu, harga, is_fleksibel FROM menu WHERE is_fleksibel = 1 LIMIT 5");
if (mysqli_num_rows($resMenus) > 0) {
    while ($row = mysqli_fetch_assoc($resMenus)) {
        echo "Menu: {$row['nama_menu']} (ID: {$row['id_menu']}), Harga: {$row['harga']}, Is_Fleksibel: {$row['is_fleksibel']}\n";
    }
} else {
    echo "No flexible menus found. Let's make one flexible for testing if needed.\n";
}

echo "\n=== CHECKING INACTIVE USERS ===\n";
// Murid
$resMurid = mysqli_query($conn, "SELECT nisn, nama, status FROM murid WHERE status != 'aktif' LIMIT 5");
echo "Murid inactive:\n";
while ($row = mysqli_fetch_assoc($resMurid)) {
    echo "- NISN: {$row['nisn']}, Name: {$row['nama']}, Status: {$row['status']}\n";
}

// Guru
$resGuru = mysqli_query($conn, "SELECT nuptk, nama, status FROM guru WHERE status != 'aktif' LIMIT 5");
echo "Guru inactive:\n";
while ($row = mysqli_fetch_assoc($resGuru)) {
    echo "- NUPTK: {$row['nuptk']}, Name: {$row['nama']}, Status: {$row['status']}\n";
}

// Penjual
$resPenjual = mysqli_query($conn, "SELECT id_penjual, nama, status FROM penjual WHERE status != 'aktif' LIMIT 5");
echo "Penjual inactive:\n";
while ($row = mysqli_fetch_assoc($resPenjual)) {
    echo "- ID: {$row['id_penjual']}, Name: {$row['nama']}, Status: {$row['status']}\n";
}

// Admin
$resAdmin = mysqli_query($conn, "SELECT id_admin, nama, status FROM admin WHERE status != 'aktif' LIMIT 5");
echo "Admin inactive:\n";
while ($row = mysqli_fetch_assoc($resAdmin)) {
    echo "- ID: {$row['id_admin']}, Name: {$row['nama']}, Status: {$row['status']}\n";
}
?>
