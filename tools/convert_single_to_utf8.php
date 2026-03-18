<?php
// tools/convert_single_to_utf8.php
// Usage: php convert_single_to_utf8.php path/to/file.php
// Attempts to convert common mojibake encodings to UTF-8 and writes a .bak backup

if ($argc < 2) {
    echo "Usage: php convert_single_to_utf8.php <file>\n";
    exit(1);
}
$path = $argv[1];
if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit(2);
}
$raw = file_get_contents($path);
if ($raw === false) {
    echo "Failed to read file: $path\n";
    exit(3);
}

function score_text($t) {
    // Score higher when Vietnamese characters present and lower when mojibake markers present
    $viet = preg_match_all('/[àáảãạăắằẳẵặâầấẩẫậđèéẻẽẹêềếểễệìíỉĩịòóỏõọôốồổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵÀÁẢÃẠĂẮẰẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỐỒỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ]/u', $t, $m);
    $vietCount = $viet;
    $moji = preg_match_all('/Ã|Ä|Â|â|Â¡|Â¢|â€™/', $t);
    $mojiCount = $moji;
    // prefer higher Vietnamese chars and fewer mojibake sequences
    return $vietCount - $mojiCount;
}

$candidates = [];
$candidates['orig'] = $raw;

// Only try encodings that are available in this PHP build
$mbEnc = array_map('strtoupper', mb_list_encodings());
$trySources = [
    'Windows-1252',
    'CP1251',
    'ISO-8859-1',
    'UTF-8',
    'Windows-1250',
    'ISO-8859-2'
];
foreach ($trySources as $src) {
    if (in_array(strtoupper($src), $mbEnc, true)) {
        $key = strtolower(str_replace(['-', '_'], '', $src));
        $candidates[$key] = @mb_convert_encoding($raw, 'UTF-8', $src);
    }
}

// Some additional simple heuristics
if (in_array('UTF-8', $mbEnc, true)) {
    $candidates['utf8_decode_then_win1252'] = @mb_convert_encoding(utf8_decode($raw), 'UTF-8', 'Windows-1252');
    $candidates['win1252_then_utf8_decode'] = @utf8_encode(@mb_convert_encoding($raw, 'ISO-8859-1', 'UTF-8'));
}

$scores = [];
foreach ($candidates as $k => $txt) {
    $scores[$k] = score_text($txt);
}

// Choose best candidate (highest score). If tie, prefer win1252 then latin1 then orig.
arsort($scores);
$bestKey = key($scores);
$bestText = $candidates[$bestKey];

echo "Scores:\n";
foreach ($scores as $k => $s) {
    echo " - $k: $s\n";
}

echo "Best candidate: $bestKey\n";

// If best is orig and it still contains mojibake markers, still try win1252
if ($bestKey === 'orig') {
    if (preg_match('/Ã|Ä|Â/', $raw)) {
        // fallback to win1252
        $bestText = $candidates['win1252'];
        $bestKey = 'win1252 (fallback)';
        echo "Fallback to win1252 due to mojibake markers.\n";
    }
}

// If best differs from original, write backup and replace
if ($bestText !== $raw) {
    $bak = $path . '.bak_convert_' . date('YmdHis');
    if (!copy($path, $bak)) {
        echo "Failed to write backup $bak\n";
        exit(4);
    }
    // Ensure UTF-8 without BOM
    $bestTextNoBOM = preg_replace('/^\xEF\xBB\xBF/', '', $bestText);
    $w = file_put_contents($path, $bestTextNoBOM);
    if ($w === false) {
        echo "Failed to write converted file.\n";
        exit(5);
    }
    echo "Rewrote $path using candidate $bestKey and saved backup $bak\n";
} else {
    echo "No changes needed for $path (best candidate = orig).\n";
}

exit(0);
