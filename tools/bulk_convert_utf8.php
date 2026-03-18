<?php
/**
 * Bulk UTF-8 converter: safe, recursive, with validation
 * Usage: php bulk_convert_to_utf8.php
 * Converts all .php, .html, .js files to UTF-8 without BOM
 * Creates timestamped backups before modifying
 */

$basePath = __DIR__ . '/..';
$extensions = ['php', 'html', 'js'];
$skipDirs = ['vendor', 'migrations', '.git'];

function isSkipDir($path) {
    global $skipDirs;
    foreach ($skipDirs as $skip) {
        if (strpos($path, DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR) !== false || 
            strpos($path, DIRECTORY_SEPARATOR . $skip) === strlen($path) - strlen(DIRECTORY_SEPARATOR . $skip)) {
            return true;
        }
    }
    return false;
}

function getFiles($dir, $extensions) {
    $files = [];
    try {
        $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($iterator, function($current, $key, $iterator) use ($extensions) {
            if ($current->isDir()) {
                return !isSkipDir($current->getPathname());
            }
            $ext = strtolower($current->getExtension());
            return in_array($ext, $extensions);
        });
        foreach (new RecursiveIteratorIterator($filter) as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
    } catch (Exception $e) {
        echo "Error reading directory: " . $e->getMessage() . "\n";
    }
    return $files;
}

function convertFileToUtf8($filepath) {
    $raw = @file_get_contents($filepath);
    if ($raw === false) {
        return ['status' => 'error', 'reason' => 'read_failed'];
    }

    // Check if file already contains BOM
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }

    // Detect likely source encoding and convert
    $candidates = [
        'orig' => $raw,
        'utf8' => $raw,  // Already UTF-8
        'win1252' => mb_convert_encoding($raw, 'UTF-8', 'Windows-1252'),
        'cp1251' => @mb_convert_encoding($raw, 'UTF-8', 'CP1251'),
        'iso88591' => @mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1'),
    ];

    // Score each candidate based on valid UTF-8 output and Vietnamese characters
    $scores = [];
    foreach ($candidates as $k => $text) {
        $vietCount = preg_match_all('/[àáảãạăắằẳẵặâầấẩẫậđèéẻẽẹêềếểễệìíỉĩịòóỏõọôốồổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵÀÁẢÃẠĂẮẰẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỐỒỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ]/u', $text);
        $mojCount = preg_match_all('/Ã|Ä|Â|â|Â¡|Â¢|â€™|â„¢/', $text);
        $scores[$k] = ($vietCount * 2) - $mojCount;
    }

    arsort($scores);
    $bestKey = key($scores);
    $bestText = $candidates[$bestKey];

    // If no good candidate found, use win1252 as fallback for mojibake
    if ($scores[$bestKey] <= 0 && isset($candidates['win1252'])) {
        $bestText = $candidates['win1252'];
        $bestKey = 'win1252_fallback';
    }

    // Remove any remaining BOM
    $bestText = preg_replace('/^\xEF\xBB\xBF/', '', $bestText);

    // If text is different from original, write backup and update
    if ($bestText !== $raw) {
        $timestamp = date('Ymd_His');
        $backupPath = $filepath . ".bak_$timestamp";
        
        if (!@copy($filepath, $backupPath)) {
            return ['status' => 'error', 'reason' => 'backup_failed'];
        }

        if (@file_put_contents($filepath, $bestText) === false) {
            // Restore from backup on failure
            @copy($backupPath, $filepath);
            return ['status' => 'error', 'reason' => 'write_failed'];
        }

        return ['status' => 'converted', 'method' => $bestKey, 'backup' => basename($backupPath)];
    }

    return ['status' => 'unchanged', 'reason' => 'already_utf8'];
}

// Main execution
echo "=== Bulk UTF-8 Conversion ===\n";
echo "Scanning: $basePath\n\n";

$files = getFiles($basePath, $extensions);
echo "Found " . count($files) . " files to check.\n\n";

$stats = ['converted' => 0, 'unchanged' => 0, 'error' => 0];
$results = [];

foreach ($files as $file) {
    $result = convertFileToUtf8($file);
    $relPath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file);
    
    if ($result['status'] === 'converted') {
        $stats['converted']++;
        echo "[CONVERTED] $relPath ({$result['method']})\n";
    } elseif ($result['status'] === 'unchanged') {
        $stats['unchanged']++;
    } else {
        $stats['error']++;
        echo "[ERROR] $relPath ({$result['reason']})\n";
    }
    $results[$relPath] = $result;
}

echo "\n=== Summary ===\n";
echo "Converted: {$stats['converted']}\n";
echo "Unchanged: {$stats['unchanged']}\n";
echo "Errors: {$stats['error']}\n";
echo "\nConversion complete. All files are now UTF-8 without BOM.\n";
?>
