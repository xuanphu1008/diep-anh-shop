<?php
$files = [
    __DIR__ . '/../index.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../product-detail.php',
    __DIR__ . '/../includes/functions.php',
    __DIR__ . '/../includes/rating-widget.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) { echo "$f: (missing)\n"; continue; }
    $b = file_get_contents($f);
    $ok = mb_check_encoding($b, 'UTF-8') ? 'UTF-8-valid' : 'NOT-UTF8';
    $det = mb_detect_encoding($b, ['UTF-8','Windows-1252','ISO-8859-1','CP1251','GBK','ASCII'], true);
    echo basename($f) . ": $ok detected=$det size=" . strlen($b) . "\n";
}
?>