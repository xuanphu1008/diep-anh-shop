<?php
// Try to fix mojibake by detecting common garbled sequences and converting from Windows-1252 -> UTF-8
$root = __DIR__ . '/../';
$exts = ['php','html','htm'];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, $exts)) continue;
    $path = $f->getPathname();
    $s = file_get_contents($path);
    if ($s === false) continue;
    // detect common mojibake markers
    if (preg_match('/ÃƒÆ’Ã†â€™|ÃƒÆ’Ã¢â‚¬Å¡|ÃƒÆ’Ã¢â‚¬Å¾|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¨|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âª|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµ|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´|ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€šÃ‚Â©|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´|ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â®|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âº|ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³/', $s)) {
        $bak = $path . '.bak2';
        if (!file_exists($bak)) copy($path, $bak);
        $conv = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        file_put_contents($path, $conv);
        $changed[] = $path;
    }
}
if (empty($changed)) {
    echo "No mojibake matches found.\n";
} else {
    echo "Fixed files:\n";
    foreach ($changed as $c) echo " - $c\n";
}
?>