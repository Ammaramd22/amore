<?php
/**
 * Diagnose + repair POS modifiers for a product.
 * Open: https://YOUR-DOMAIN/pos-mod-debug.php?id=10
 * DELETE after use.
 */
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');

$root = dirname(__DIR__);
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function readEnvKey(string $root): string {
    $envPath = $root.DIRECTORY_SEPARATOR.'.env';
    if (! is_file($envPath)) return '';
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), 'APP_KEY=')) {
            return trim(substr(trim($line), 8));
        }
    }
    return '';
}

$key = readEnvKey($root);
$provided = trim((string) ($_POST['app_key'] ?? $_GET['key'] ?? ''));
$unlocked = $key !== '' && $provided !== '' && hash_equals($key, $provided);
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 10);

if (! $unlocked) {
    echo '<!DOCTYPE html><html><body style="font-family:system-ui;max-width:640px;margin:40px auto;padding:0 16px">';
    echo '<h1>POS modifiers debug</h1><p>Paste APP_KEY from .env</p>';
    echo '<form method="post">';
    echo '<label>Product ID</label><input name="id" value="'.h((string)$id).'" style="width:100%;padding:10px;margin:6px 0">';
    echo '<label>APP_KEY</label><input name="app_key" style="width:100%;padding:10px;font-family:monospace" placeholder="base64:...">';
    echo '<button style="margin-top:12px;padding:10px 16px;background:#ea580c;color:#fff;border:0;border-radius:8px">Run</button></form>';
    echo '</body></html>';
    exit;
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$repair = isset($_POST['repair']);
$notes = [];

$p = \App\Models\Product::with(['variants', 'addons', 'sharedAddons', 'addonGroups.addons'])->find($id);

echo '<!DOCTYPE html><html><body style="font-family:system-ui;max-width:920px;margin:40px auto;padding:0 16px">';
echo '<h1>POS modifiers debug · product #'.h((string)$id).'</h1>';

if (! $p) {
    echo '<p style="color:#b91c1c">Product not found.</p></body></html>';
    exit;
}

if ($repair) {
    $reactivated = 0;
    foreach ($p->addonGroups as $g) {
        foreach ($g->addons as $a) {
            if (! $a->is_active) {
                $a->update(['is_active' => true]);
                $reactivated++;
            }
        }
    }
    $p->refreshHasAddonsFlag();
    $p->refresh();
    $p->load(['addonGroups.addons']);
    $notes[] = "Repair done: reactivated {$reactivated} addon(s), refreshed has_addons=" . ($p->has_addons ? '1' : '0');
}

foreach ($notes as $n) {
    echo '<p style="background:#ecfdf5;padding:10px;border-radius:8px">'.h($n).'</p>';
}

echo '<p><strong>'.h($p->name).'</strong> · has_addons='.($p->has_addons ? '1' : '0');
echo ' · variants='.$p->variants->count();
echo ' · groups='.$p->addonGroups->count().'</p>';

echo '<h2>1) Assigned modifier sets (addon_group_product)</h2><ul>';
foreach ($p->addonGroups as $g) {
    echo '<li>#'.$g->id.' <strong>'.h($g->name).'</strong>';
    echo ' active='.(($g->is_active ?? true) ? '1' : '0');
    echo ' show_in_pos='.(($g->show_in_pos ?? true) ? '1' : '0');
    echo '<ul>';
    foreach ($g->addons as $a) {
        echo '<li>#'.$a->id.' '.h($a->name).' · price='.$a->price;
        echo ' · is_active='.(($a->is_active ?? true) ? '1' : '0').'</li>';
    }
    if ($g->addons->isEmpty()) {
        echo '<li style="color:#b91c1c">No modifiers linked inside this set — open Modifiers → '.h($g->name).' and add Strong/Medium.</li>';
    }
    echo '</ul></li>';
}
if ($p->addonGroups->isEmpty()) {
    echo '<li style="color:#b91c1c"><strong>NONE assigned.</strong> Edit product → check Brew → click <em>Update product</em>.</li>';
}
echo '</ul>';

try {
    $sets = $p->posModifierSets();
    echo '<h2>2) posModifierSets() — what POS should show</h2>';
    echo '<pre style="background:#f5f5f4;padding:12px;overflow:auto;max-height:360px">'.h(json_encode($sets, JSON_PRETTY_PRINT)).'</pre>';
    if ($sets->isEmpty()) {
        echo '<p style="color:#b91c1c"><strong>EMPTY</strong> — POS cannot show Select Modifiers until this returns Brew + Strong/Medium.</p>';
    } else {
        echo '<p style="color:#15803d"><strong>OK</strong> — data is ready. Deploy POS fix ZIP, run migrate-update, Ctrl+F5 POS.</p>';
    }
} catch (Throwable $e) {
    echo '<h2 style="color:#b91c1c">2) posModifierSets() CRASHED</h2>';
    echo '<pre style="background:#fef2f2;padding:12px">'.h($e->getMessage()."\n".$e->getFile().':'.$e->getLine()).'</pre>';
}

echo '<form method="post" style="margin:20px 0;padding:16px;border:1px solid #e7e5e4;border-radius:8px">';
echo '<input type="hidden" name="app_key" value="'.h($provided).'">';
echo '<input type="hidden" name="id" value="'.h((string)$id).'">';
echo '<input type="hidden" name="repair" value="1">';
echo '<button style="padding:10px 16px;background:#0f766e;color:#fff;border:0;border-radius:8px">Repair: reactivate addons + refresh has_addons</button>';
echo '</form>';

echo '<p style="color:#64748b">Note: PosController::index FAIL in pos-debug.php is a false alarm (debug calls controller without HTTP request).<br>';
echo 'activity() error is cash-drawer only — fixed in latest ZIP.<br>';
echo 'DELETE this file when done.</p></body></html>';
