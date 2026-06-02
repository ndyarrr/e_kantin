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

if (preg_match('/<!-- DEBUG PESANAN:.*?-->/', $html, $matches)) {
    file_put_contents('scratch/debug_result.txt', "Found debug comment: " . $matches[0] . "\n");
} else {
    file_put_contents('scratch/debug_result.txt', "Debug comment NOT found!\n");
}
