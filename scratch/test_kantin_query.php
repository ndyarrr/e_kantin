<?php
require_once __DIR__ . '/../config/database.php';

$query = "SELECT t.id_toko, t.nama_toko, t.deskripsi, t.foto_toko, t.urutan,
            MIN(s.nomor) AS nomor_lapak,
            COUNT(DISTINCT tp.id_penjual) as jumlah_penjual
    FROM toko t
    LEFT JOIN slot_stand_kantin s ON s.id_toko = t.id_toko
    LEFT JOIN toko_penjual tp ON tp.id_toko = t.id_toko AND tp.status = 'aktif'
    WHERE t.deleted_at IS NULL
    GROUP BY t.id_toko
    ORDER BY COALESCE(MIN(s.nomor), t.urutan + 1) ASC, t.dibuat_pada ASC";

$res = mysqli_query($conn, $query);
if ($res) {
    echo "Success!\n";
} else {
    echo "Query Error: " . mysqli_error($conn) . "\n";
}
