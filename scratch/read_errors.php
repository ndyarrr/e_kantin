<?php
$file = 'C:/laragon/tmp/php_errors.log';
if (file_exists($file)) {
    $lines = file($file);
    $last_lines = array_slice($lines, -50);
    echo implode("", $last_lines);
} else {
    echo "No php_errors.log found.\n";
}
