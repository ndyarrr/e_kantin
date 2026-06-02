<?php
$_SESSION = [
    'user_id' => '0011000003',
    'user_nama' => 'Citra Dewi',
    'user_role' => 'siswa',
    'user_foto' => ''
];

$user_role = 'siswa';
$user_id = '0011000003';

require 'c:/laragon/www/e_kantin/config/database.php';
$koneksi = $conn;

echo "=== RENDER PESANAN.PHP ===\n";
include 'views/pembeli/sections/pesanan.php';
echo "\n=== RENDER COMPLETE ===\n";
