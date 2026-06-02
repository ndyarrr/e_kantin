<?php
require_once __DIR__ . '/../config/database.php';
$res = mysqli_query($conn, "SELECT id_menu, nama_menu, harga, tersedia, stok, terjual, deleted_at FROM menu");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
