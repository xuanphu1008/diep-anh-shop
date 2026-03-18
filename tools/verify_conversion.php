<?php
// Quick verification of key files
$files = [
    'contact.php',
    'news.php',
    'products.php',
    'product-detail.php',
    'includes/header.php',
    'includes/footer.php',
    'api/cart-handler.php'
];

$basePath = dirname(__DIR__);
echo "=== UTF-8 Verification ===\n\n";

foreach ($files as $f) {
    $path = $basePath . '/' . $f;
    if (!file_exists($path)) {
        echo "[SKIP] $f (not found)\n";
        continue;
    }

    $raw = file_get_contents($path);
    
    // Check BOM
    $hasBom = (substr($raw, 0, 3) === "\xEF\xBB\xBF");
    
    // Check mojibake patterns (these shouldn't appear in clean UTF-8)
    $mojibakePattern = '/(\xC3\x83|\xC3\x84|\xC3\x82|\xE2\x80\x99|\xE2\x80\x98|\xC2\xA1|\xC2\xAB)/';
    $hasMojibake = (bool)preg_match($mojibakePattern, $raw);
    
    // Extract status
    $status = [];
    if ($hasBom) $status[] = "HAS_BOM";
    if ($hasMojibake) $status[] = "HAS_MOJIBAKE";
    if (empty($status)) $status[] = "CLEAN";
    
    $statusStr = implode(", ", $status);
    echo "[$statusStr] $f\n";
}

echo "\n✓ Clean files can be deployed\n";
?>
