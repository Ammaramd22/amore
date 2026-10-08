<?php
/**
 * Show the REAL error for /pos 500 (cPanel, no Terminal).
 * Upload to public/pos-debug.php then open:
 *   https://YOUR-DOMAIN/pos-debug.php
 * Paste APP_KEY from .env, then DELETE this file.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');

$root = dirname(__DIR__);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function readEnvKey(string $root): string
{
    $envPath = $root.DIRECTORY_SEPARATOR.'.env';
    if (! is_file($envPath)) {
        return '';
    }
    foreach (@file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if (preg_match('/^APP_KEY\s*=\s*(.*)$/', $line, $m)) {
            return trim($m[1], " \t\"'");
        }
    }

    return '';
}

$appKey = readEnvKey($root);
$provided = '';
if (isset($_POST['app_key'])) {
    $provided = trim((string) $_POST['app_key'], " \t\"'");
} elseif (isset($_GET['key'])) {
    $provided = trim((string) $_GET['key'], " \t\"'");
}

if ($appKey === '' || $provided === '' || ! hash_equals($appKey, $provided)) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>POS Debug</title></head><body style="font-family:system-ui;max-width:720px;margin:40px auto;padding:0 16px">'
        .'<h1>POS error debugger</h1>'
        .'<p>Paste <code>APP_KEY</code> from <code>.env</code> to see the real /pos error.</p>'
        .'<form method="post"><input type="password" name="app_key" required style="width:100%;padding:12px;border:2px solid #000;border-radius:8px;font-family:monospace" placeholder="base64:...">'
        .'<button type="submit" style="margin-top:12px;padding:12px 18px;background:#ea580c;color:#fff;border:0;border-radius:8px;font-weight:700">Show error</button></form>'
        .'<p style="color:#64748b;font-size:13px;margin-top:16px;">Delete <code>public/pos-debug.php</code> after use.</p></body></html>';
    exit;
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$checks = [];

// 1) .env sanity
$checks[] = ['label' => 'APP_KEY length', 'ok' => strlen($appKey) > 20, 'detail' => strlen($appKey).' chars'];

// 2) DB
try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    $checks[] = ['label' => 'Database', 'ok' => true, 'detail' => 'Connected'];
} catch (Throwable $e) {
    $checks[] = ['label' => 'Database', 'ok' => false, 'detail' => $e->getMessage()];
}

// 3) Critical tables/columns for update
$tables = ['addons', 'addon_product', 'subcategories'];
foreach ($tables as $t) {
    $exists = false;
    try {
        $exists = Illuminate\Support\Facades\Schema::hasTable($t);
    } catch (Throwable $e) {
        $checks[] = ['label' => "Table $t", 'ok' => false, 'detail' => $e->getMessage()];
        continue;
    }
    $checks[] = ['label' => "Table $t", 'ok' => $exists, 'detail' => $exists ? 'exists' : 'MISSING — run migrate-update.php'];
}

foreach (['rounding_amount', 'card_surcharge_amount', 'is_comp'] as $col) {
    $has = false;
    try {
        $has = Illuminate\Support\Facades\Schema::hasColumn('orders', $col);
    } catch (Throwable $e) {
        $checks[] = ['label' => "orders.$col", 'ok' => false, 'detail' => $e->getMessage()];
        continue;
    }
    $checks[] = ['label' => "orders.$col", 'ok' => $has, 'detail' => $has ? 'exists' : 'MISSING — run migrate-update.php'];
}

// 4) Try PosController::index
$posError = null;
try {
    auth()->loginUsingId(
        optional(
            \App\Models\User::query()
                ->where('user_type', 'software_owner')
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'software_owner'))
                ->first()
        )->id
    );
} catch (Throwable $e) {
    // ignore login helper failure
}

try {
    $request = Illuminate\Http\Request::create('/pos', 'GET');
    $request->setLaravelSession($app['session.store']);
    $controller = $app->make(\App\Http\Controllers\Pos\PosController::class);
    $response = $controller->index($request);
    $html = $response->render();
    $checks[] = ['label' => 'PosController::index', 'ok' => true, 'detail' => 'Rendered OK ('.strlen($html).' bytes)'];
} catch (Throwable $e) {
    $posError = $e;
    $checks[] = [
        'label' => 'PosController::index',
        'ok' => false,
        'detail' => $e->getMessage()."\n\n".$e->getFile().':'.$e->getLine()."\n\n".$e->getTraceAsString(),
    ];
}

// 5) Tail laravel.log
$logTail = '';
$logFile = $root.'/storage/logs/laravel.log';
if (is_file($logFile)) {
    $size = filesize($logFile);
    $fp = fopen($logFile, 'r');
    if ($fp) {
        fseek($fp, max(0, $size - 12000));
        $logTail = stream_get_contents($fp) ?: '';
        fclose($fp);
    }
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>POS Debug</title></head>'
    .'<body style="font-family:system-ui;max-width:900px;margin:40px auto;padding:0 16px;color:#1c1917">'
    .'<h1>'.($posError ? 'POS is broken — real error below' : 'POS controller rendered OK').'</h1>'
    .'<table style="width:100%;border-collapse:collapse">';

foreach ($checks as $c) {
    $color = $c['ok'] ? '#166534' : '#b91c1c';
    echo '<tr><td style="padding:8px;border-bottom:1px solid #e5e7eb;vertical-align:top;"><strong style="color:'.$color.'">'.($c['ok'] ? 'OK' : 'FAIL').'</strong></td>'
        .'<td style="padding:8px;border-bottom:1px solid #e5e7eb;"><strong>'.h($c['label']).'</strong>'
        .'<pre style="white-space:pre-wrap;margin:6px 0 0;font-size:12px;background:#f8fafc;padding:8px;border-radius:8px;max-height:320px;overflow:auto;">'.h($c['detail']).'</pre></td></tr>';
}

echo '</table>';

if ($logTail !== '') {
    echo '<h2>laravel.log (tail)</h2><pre style="white-space:pre-wrap;font-size:11px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;max-height:420px;overflow:auto;">'.h($logTail).'</pre>';
}

echo '<p style="margin-top:20px;color:#b91c1c;font-weight:700;">DELETE public/pos-debug.php after copying the error text.</p>'
    .'<p><a href="/dashboard">Dashboard</a> · <a href="/pos">POS</a> · <a href="/migrate-update.php">Migrate</a></p>'
    .'</body></html>';
