<?php
require 'c:/laragon/www/e_kantin/config/database.php';
$query = "ALTER TABLE `toko` ADD `qris` VARCHAR(255) DEFAULT NULL";
if (mysqli_query($conn, $query)) {
    echo "Successfully added qris column to toko table!\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
