<?php
// tools/convert_to_utf8.php
// Scans .php and .html files (recursively) and converts to UTF-8 (no BOM).
// Creates a .bak backup for each modified file.

$root = __DIR__ . '/../';
$exts = ['php','html','htm'];
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (in_array($ext, $exts)) $files[] = $f->getPathname();
}

function startsWithBOM($bytes) {
    return strlen($bytes) >= 3 && ord($bytes[0])==0xEF && ord($bytes[1])==0xBB && ord($bytes[2])==0xBF;
}

$changed = [];
foreach ($files as $file) {
    $bytes = file_get_contents($file);
    if ($bytes === false) continue;

    $orig = $bytes;
    $modified = false;

    // Remove BOM if present
    if (startsWithBOM($bytes)) {
        $bytes = substr($bytes,3);
        $modified = true;
    }

    // If already valid UTF-8, skip conversion (but still may have been BOM removed)
    if (!mb_check_encoding($bytes, 'UTF-8')) {
        // Try common encodings to convert
        $enc = mb_detect_encoding($bytes, ['UTF-8','Windows-1252','ISO-8859-1','CP1251','GBK'], true);
        if ($enc === false) {
            $enc = 'Windows-1252';
        }
        $bytes = mb_convert_encoding($bytes, 'UTF-8', $enc);
        $modified = true;
    }

    if ($modified) {
        // Backup
        $bak = $file . '.bak';
        if (!file_exists($bak)) copy($file, $bak);
        file_put_contents($file, $bytes);
        $changed[] = $file;
    }
}

if (empty($changed)) {
    echo "No files changed.\n";
} else {
    echo "Converted files:\n";
    foreach ($changed as $c) echo " - $c\n";
}

?>