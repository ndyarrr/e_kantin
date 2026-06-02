<?php
$_SESSION = [
    'user_id' => '0011000003',
    'user_nama' => 'Citra Dewi',
    'user_role' => 'siswa',
    'user_foto' => ''
];

$_SERVER['SERVER_PORT'] = '8000';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

ob_start();
include 'views/pembeli/index.php';
$html = ob_get_clean();

if (strpos($html, 'WARUNG ANJAI') !== false) {
    echo "Found WARUNG ANJAI in the rendered page!\n";
} else {
    echo "WARUNG ANJAI NOT found in the rendered page!\n";
}

if (strpos($html, 'pesanan-card') !== false) {
    echo "Found pesanan-card in the rendered page!\n";
    // count how many times pesanan-card appears
    $count = substr_count($html, 'class="pesanan-card"');
    echo "Number of pesanan-card elements: $count\n";
} else {
    echo "pesanan-card NOT found in the rendered page!\n";
}
