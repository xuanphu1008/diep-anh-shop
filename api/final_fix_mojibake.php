<?php
/**
 * Fix specific files with severe mojibake by doing multi-level decoding
 */

function fixMojibake($filepath) {
    $raw = file_get_contents($filepath);
    
    // Try decoding in different ways
    $candidates = [
        'orig' => $raw,
        'win1252_1' => mb_convert_encoding($raw, 'UTF-8', 'Windows-1252'),
        'win1252_2' => html_entity_decode(mb_convert_encoding($raw, 'UTF-8', 'Windows-1252')),
    ];
    
    // If it already looks UTF-8 clean, use that
    if (mb_check_encoding($raw, 'UTF-8') && !preg_match('/Ã|Ä|â€™/', $raw)) {
        return $raw;
    }
    
    // Otherwise convert from Windows-1252
    $result = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    
    // Remove BOM if present
    if (substr($result, 0, 3) === "\xEF\xBB\xBF") {
        $result = substr($result, 3);
    }
    
    return $result;
}

$problematicFiles = [
    'C:\xampp\htdocs\diep-anh-shop\api\cart-handler.php'
];

foreach ($problematicFiles as $file) {
    if (file_exists($file)) {
        $fixed = fixMojibake($file);
        $backup = $file . '.bak_' . date('YmdHis');
        copy($file, $backup);
        file_put_contents($file, $fixed);
        echo "Fixed: " . basename($file) . "\n";
    }
}
?>
