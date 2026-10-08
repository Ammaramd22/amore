<?php
/**
 * One-time cache clear for cPanel (no SSH/terminal).
 * 1) Upload this file into the public/ folder (same place as index.php).
 * 2) Open: https://YOUR-DOMAIN/clear-kds-cache.php
 * 3) DELETE this file immediately after it shows OK.
 */

declare(strict_types=1);

$base = dirname(__DIR__);

$deleted = [];
$errors = [];

$targets = [
    $base . '/bootstrap/cache/routes-v7.php',
    $base . '/bootstrap/cache/routes.php',
    $base . '/bootstrap/cache/config.php',
];

foreach ($targets as $file) {
    if (is_file($file)) {
        if (@unlink($file)) {
            $deleted[] = basename($file);
        } else {
            $errors[] = 'Could not delete ' . basename($file);
        }
    }
}

$viewDir = $base . '/storage/framework/views';
$viewCount = 0;
if (is_dir($viewDir)) {
    foreach (glob($viewDir . '/*.php') ?: [] as $viewFile) {
        if (@unlink($viewFile)) {
            $viewCount++;
        }
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "KDS cache clear\n";
echo "===============\n";
echo 'Route/config cache removed: ' . (count($deleted) ? implode(', ', $deleted) : '(none found)') . "\n";
echo "Compiled views deleted: {$viewCount}\n";
if ($errors) {
    echo 'Errors: ' . implode('; ', $errors) . "\n";
}
echo "\nDELETE this file (clear-kds-cache.php) from public/ now.\n";
echo "Then hard-refresh Kitchen Display (Ctrl+F5).\n";
