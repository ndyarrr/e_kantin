<?php
foreach (glob('C:/laragon/tmp/sess_*') as $f) {
    echo "=== FILE: " . basename($f) . " ===\n";
    $content = file_get_contents($f);
    echo $content . "\n\n";
}
