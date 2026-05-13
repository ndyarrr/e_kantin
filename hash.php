<?php
$path = __DIR__ . '/assets/img/kantin/';
echo "Folder ada: " . (is_dir($path) ? 'YA' : 'TIDAK') . "<br>";
echo "Bisa ditulis: " . (is_writable($path) ? 'YA' : 'TIDAK') . "<br>";