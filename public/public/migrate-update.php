<?php
/**
 * cPanel migrate + cache clear (NO Terminal).
 *
 * Place in: public/migrate-update.php
 * Open:    https://YOUR-DOMAIN/migrate-update.php
 * DELETE after success.
 *
 * Unlock with APP_KEY from .env (File Manager → .env → copy APP_KEY=... value).
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$root = dirname(__DIR__);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page(string $title, string $bodyHtml, int $code = 200): never
{
    http_response_code($code);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex">'
        .'<title>'.h($title).'</title></head>'
        .'<body style="font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#1c1917">'
        .$bodyHtml
        .'</body></html>';
    exit;
}

function readEnvKey(string $root): string
{
    $envPath = $root.DIRECTORY_SEPARATOR.'.env';
    if (! is_file($envPath)) {
        return '';
    }
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^APP_KEY\s*=\s*(.*)$/', $line, $m)) {
            $val = trim($m[1]);
            $val = trim($val, "\"'");

            return $val;
        }
    }

    return '';
}

function expectedToken(string $appKey): string
{
    return substr(hash('sha256', $appKey.'|migrate-update'), 0, 32);
}

if (! is_file($root.'/vendor/autoload.php') || ! is_file($root.'/bootstrap/app.php')) {
    page('Failed', '<h1 style="color:#b91c1c;">Failed</h1><p>Laravel not found. Put this file in the <code>public</code> folder (next to <code>index.php</code>).</p>', 500);
}

$appKey = readEnvKey($root);
$tokenExpected = $appKey !== '' ? expectedToken($appKey) : '';

$provided = '';
if (isset($_POST['app_key'])) {
    $provided = trim((string) $_POST['app_key']);
    $provided = trim($provided, "\"'");
} elseif (isset($_GET['token'])) {
    $provided = trim((string) $_GET['token']);
} elseif (isset($_GET['key'])) {
    $provided = trim((string) $_GET['key']);
    $provided = trim($provided, "\"'");
}

$unlocked = false;
if ($appKey !== '' && $provided !== '') {
    // Accept full APP_KEY or the derived token
    if (hash_equals($appKey, $provided) || ($tokenExpected !== '' && hash_equals($tokenExpected, $provided))) {
        $unlocked = true;
    }
}

// Also accept SETUP_STORAGE_TOKEN / SETUP_MIGRATE_TOKEN from .env if present
if (! $unlocked && $provided !== '') {
    $envPath = $root.DIRECTORY_SEPARATOR.'.env';
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($lines as $line) {
        if (preg_match('/^(SETUP_MIGRATE_TOKEN|SETUP_STORAGE_TOKEN)\s*=\s*(.*)$/', trim($line), $m)) {
            $val = trim($m[2], " \t\"'");
            if ($val !== '' && hash_equals($val, $provided)) {
                $unlocked = true;
                break;
            }
        }
    }
}

if (! $unlocked) {
    $err = $provided !== '' ? '<p style="color:#b91c1c;"><strong>Wrong key.</strong> Copy APP_KEY exactly from .env (starts with <code>base64:</code>).</p>' : '';
    page('Unlock migrate', '
        <h1>Run database update</h1>
        <p>No Terminal needed. Unlock with your live <code>APP_KEY</code> from File Manager → <code>.env</code>.</p>
        '.$err.'
        <ol>
            <li>cPanel → File Manager → open project root <code>.env</code></li>
            <li>Find the line <code>APP_KEY=base64:....</code></li>
            <li>Copy everything after <code>APP_KEY=</code> (include <code>base64:</code>)</li>
            <li>Paste below and click Run</li>
        </ol>
        <form method="post" action="" style="margin-top:20px;">
            <label style="display:block;font-weight:600;margin-bottom:6px;">APP_KEY from .env</label>
            <input type="password" name="app_key" required autocomplete="off"
                style="width:100%;padding:12px;border:2px solid #000;border-radius:8px;font-family:monospace;"
                placeholder="base64:xxxxxxxx">
            <button type="submit" style="margin-top:14px;padding:12px 20px;background:#ea580c;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer;">
                Run migrate + clear cache
            </button>
        </form>
        <p style="margin-top:24px;color:#64748b;font-size:13px;">Delete this file after a successful run.</p>
    ', 403);
}

// --- Authorized: bootstrap Laravel and run migrate ---
require $root.'/vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$steps = [];
$ok = true;

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    $steps[] = ['ok' => true, 'label' => 'Database', 'detail' => 'Connected'];
} catch (Throwable $e) {
    page('Failed', '<h1 style="color:#b91c1c;">Database failed</h1><pre>'.h($e->getMessage()).'</pre>', 500);
}

try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $out = trim(Illuminate\Support\Facades\Artisan::output());
    $steps[] = ['ok' => true, 'label' => 'migrate --force', 'detail' => $out !== '' ? $out : 'Already up to date'];
} catch (Throwable $e) {
    $ok = false;
    $steps[] = ['ok' => false, 'label' => 'migrate --force', 'detail' => $e->getMessage()];
}

try {
    Illuminate\Support\Facades\Artisan::call('optimize:hosting', ['--clear' => true]);
    $out = trim(Illuminate\Support\Facades\Artisan::output());
    $steps[] = ['ok' => true, 'label' => 'optimize:hosting --clear', 'detail' => $out !== '' ? $out : 'Cleared'];
} catch (Throwable $e) {
    try {
        Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $out = trim(Illuminate\Support\Facades\Artisan::output());
        if (class_exists(\App\Models\Setting::class)) {
            \App\Models\Setting::flushCache();
        }
        $steps[] = ['ok' => true, 'label' => 'optimize:clear', 'detail' => $out !== '' ? $out : 'Cleared'];
    } catch (Throwable $e2) {
        $ok = false;
        $steps[] = ['ok' => false, 'label' => 'cache clear', 'detail' => $e2->getMessage()];
    }
}

if ($ok) {
    try {
        Illuminate\Support\Facades\Artisan::call('optimize:hosting');
        $out = trim(Illuminate\Support\Facades\Artisan::output());
        $steps[] = ['ok' => true, 'label' => 'optimize:hosting', 'detail' => $out !== '' ? $out : 'Rebuilt'];
    } catch (Throwable $e) {
        $steps[] = ['ok' => true, 'label' => 'optimize:hosting', 'detail' => 'Skipped: '.$e->getMessage()];
    }
}

$rows = '';
foreach ($steps as $step) {
    $color = $step['ok'] ? '#166534' : '#b91c1c';
    $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb;vertical-align:top;"><strong style="color:'.$color.';">'.($step['ok'] ? 'OK' : 'FAIL').'</strong></td>'
        .'<td style="padding:8px;border-bottom:1px solid #e5e7eb;"><strong>'.h($step['label']).'</strong>'
        .'<pre style="white-space:pre-wrap;margin:6px 0 0;font-size:12px;background:#f8fafc;padding:8px;border-radius:8px;">'.h($step['detail']).'</pre></td></tr>';
}

page(
    $ok ? 'Update complete' : 'Update incomplete',
    '<h1 style="color:'.($ok ? '#166534' : '#b91c1c').';">'.($ok ? 'Update complete' : 'Update incomplete').'</h1>'
    .'<p>'.($ok
        ? 'Migrations finished. Hard-refresh Dashboard (Ctrl+F5). Then <strong>DELETE</strong> <code>public/migrate-update.php</code> from File Manager.'
        : 'See errors below, fix, then run again.').'</p>'
    .'<table style="width:100%;border-collapse:collapse;">'.$rows.'</table>'
    .'<p style="margin-top:20px;"><a href="/dashboard">← Dashboard</a></p>',
    $ok ? 200 : 500
);
