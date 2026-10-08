<?php
/**
 * Clear compiled Blade views after POS fix upload (no Terminal).
 * Upload to public/clear-views.php → open once → DELETE.
 */
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$dir = $root.'/storage/framework/views';
$n = 0;
if (is_dir($dir)) {
    foreach (glob($dir.'/*.php') ?: [] as $file) {
        if (@unlink($file)) {
            $n++;
        }
    }
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Views cleared</title></head>'
    .'<body style="font-family:system-ui;max-width:640px;margin:40px auto;padding:0 16px">'
    .'<h1 style="color:#166534;">Views cleared</h1>'
    .'<p>Deleted '.$n.' compiled Blade files.</p>'
    .'<p><a href="/pos">Open POS</a></p>'
    .'<p style="color:#b91c1c;font-weight:700;">DELETE public/clear-views.php now.</p>'
    .'</body></html>';
