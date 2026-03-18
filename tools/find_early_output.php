<?php
/**
 * Find which file outputs bytes before session_start
 * This diagnostic script detects early output sources.
 */

// Start output buffering to capture any early output
ob_start();

// Try to start session (will fail if headers already sent)
$session_started = @session_start();

$early_output = ob_get_clean();

if (!empty($early_output)) {
    echo "=== EARLY OUTPUT DETECTED ===\n";
    echo "Length: " . strlen($early_output) . " bytes\n";
    echo "First 200 chars:\n";
    echo addslashes(substr($early_output, 0, 200)) . "\n";
    echo "\nFull output (hex):\n";
    echo bin2hex($early_output) . "\n";
} else {
    echo "No early output detected.\n";
}

// Check if session started successfully
if ($session_started) {
    echo "\nSession started successfully.\n";
} else {
    echo "\nSession failed to start (headers already sent).\n";
}

// Show current includes stack
echo "\nIncluded files:\n";
$includes = get_included_files();
foreach ($includes as $i => $file) {
    echo ($i + 1) . ". $file\n";
}
?>
