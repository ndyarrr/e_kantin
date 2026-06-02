<?php
$_SESSION = [
    'user_id' => '0011000003',
    'user_nama' => 'Citra Dewi',
    'user_role' => 'siswa',
    'user_foto' => ''
];

$_SERVER['SERVER_PORT'] = '8000';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

require 'c:/laragon/www/e_kantin/config/database.php';
$koneksi = $conn;

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

echo "Variables: role=$user_role, id=$user_id\n";

$col_pembeli = ($user_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';

$sql = "SELECT p.*, t.nama_toko, t.id_toko, t.qris_image
        FROM pesanan p
        JOIN toko t ON p.id_toko = t.id_toko
        WHERE p.$col_pembeli = '$user_id'
        ORDER BY p.waktu_pesan DESC";

$q_pesanan = mysqli_query($conn, $sql);
if (!$q_pesanan) {
    echo "Query Error: " . mysqli_error($conn) . "\n";
} else {
    $num = mysqli_num_rows($q_pesanan);
    echo "Query returned $num rows.\n";
    while ($row = mysqli_fetch_assoc($q_pesanan)) {
        echo "Order ID: {$row['id_pesanan']}, Toko: {$row['nama_toko']}, Status: {$row['status']}\n";
    }
}
