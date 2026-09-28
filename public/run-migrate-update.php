<?php
/**
 * ONE-TIME cPanel DB updater for Zingers QRPOS update.
 *
 * Migrate (forward):
 *   https://YOUR-DOMAIN/run-migrate-update.php?key=Zingers-Update-20260905
 *
 * Rollback last migration batch (only if needed):
 *   https://YOUR-DOMAIN/run-migrate-update.php?key=Zingers-Update-20260905&action=rollback
 *
 * DELETE this file from public/ immediately after success.
 */

declare(strict_types=1);

const MIGRATE_KEY = 'Zingers-Update-20260905';

header('Content-Type: text/html; charset=utf-8');

$key = (string) ($_GET['key'] ?? '');
if (! hash_equals(MIGRATE_KEY, $key)) {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Invalid or missing key.</p>';
    echo '<p>Use: <code>?key=Zingers-Update-20260905</code></p>';
    exit;
}

$action = strtolower((string) ($_GET['action'] ?? 'migrate'));
if (! in_array($action, ['migrate', 'rollback'], true)) {
    http_response_code(400);
    echo '<h1>Bad request</h1><p>action must be migrate or rollback.</p>';
    exit;
}

$publicDir = __DIR__;
$basePath = dirname($publicDir);

if (! is_file($basePath.'/artisan') || ! is_file($basePath.'/vendor/autoload.php')) {
    http_response_code(500);
    echo '<h1>Error</h1><p>Laravel root not found above /public (need artisan + vendor).</p>';
    exit;
}

require $basePath.'/vendor/autoload.php';
$app = require $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

function runArtisan($kernel, string $command, array $params = []): array
{
    try {
        $exit = $kernel->call($command, $params);
        $output = trim((string) $kernel->output());

        return [
            'ok' => $exit === 0,
            'exit' => $exit,
            'output' => $output !== '' ? $output : '(no output)',
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'exit' => 1,
            'output' => $e->getMessage(),
        ];
    }
}

$steps = [];

if ($action === 'rollback') {
    $steps['migrate:rollback'] = runArtisan($kernel, 'migrate:rollback', [
        '--force' => true,
        '--step' => 1,
    ]);
} else {
    // Ensure this update's column even if migrate history is messy
    try {
        $notes = [];
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'void_type')) {
            Schema::table('orders', function ($table) {
                $table->string('void_type', 20)->nullable()->after('is_void');
            });
            $notes[] = 'Added orders.void_type manually';
        } elseif (Schema::hasColumn('orders', 'void_type')) {
            $notes[] = 'orders.void_type already exists';
        } else {
            $notes[] = 'orders table missing';
        }
        $steps['ensure-void_type'] = [
            'ok' => true,
            'exit' => 0,
            'output' => implode("\n", $notes),
        ];
    } catch (Throwable $e) {
        $steps['ensure-void_type'] = [
            'ok' => false,
            'exit' => 1,
            'output' => $e->getMessage(),
        ];
    }

    // Mark old kitchen tickets as already printed so pending-KOT queue only gets NEW waiter tickets
    try {
        $kotNotes = [];
        if (Schema::hasTable('kitchen_orders') && Schema::hasColumn('kitchen_orders', 'printed_at')) {
            $n = \Illuminate\Support\Facades\DB::table('kitchen_orders')
                ->whereNull('printed_at')
                ->update(['printed_at' => \Illuminate\Support\Facades\DB::raw('COALESCE(created_at, NOW())')]);
            $kotNotes[] = "Backfilled printed_at on {$n} existing kitchen_orders";
        } else {
            $kotNotes[] = 'kitchen_orders.printed_at not available';
        }
        \App\Models\Setting::firstOrCreate(
            ['key' => 'pos_bridge_print_pending_kot'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'POS Print Bridge auto-prints waiter KOTs that never printed',
            ]
        );
        $kotNotes[] = 'Setting pos_bridge_print_pending_kot ready';
        $steps['ensure-pending-kot'] = [
            'ok' => true,
            'exit' => 0,
            'output' => implode("\n", $kotNotes),
        ];
    } catch (Throwable $e) {
        $steps['ensure-pending-kot'] = [
            'ok' => false,
            'exit' => 1,
            'output' => $e->getMessage(),
        ];
    }

    $steps['migrate'] = runArtisan($kernel, 'migrate', ['--force' => true]);
}

$steps['view:clear'] = runArtisan($kernel, 'view:clear');
$steps['route:clear'] = runArtisan($kernel, 'route:clear');
$steps['config:clear'] = runArtisan($kernel, 'config:clear');
$steps['cache:clear'] = runArtisan($kernel, 'cache:clear');

$voidReady = false;
try {
    $voidReady = Schema::hasTable('orders') && Schema::hasColumn('orders', 'void_type');
} catch (Throwable $e) {
    $voidReady = false;
}

$steps['check-void_type'] = [
    'ok' => $action === 'rollback' ? true : $voidReady,
    'exit' => ($action === 'rollback' || $voidReady) ? 0 : 1,
    'output' => $action === 'rollback'
        ? 'Rollback requested — verify site still works, then restore files if needed'
        : ($voidReady
            ? 'orders.void_type ready — Cancelled Bills / Cancel bill OK'
            : 'orders.void_type still missing'),
];

$allOk = true;
foreach ($steps as $result) {
    if (! ($result['ok'] ?? false)) {
        $allOk = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zingers DB Update</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 860px; margin: 40px auto; padding: 0 16px; background: #0f172a; color: #e2e8f0; }
        h1 { margin-bottom: 8px; }
        .ok { color: #4ade80; }
        .bad { color: #f87171; }
        .warn { background: #7f1d1d; color: #fecaca; padding: 14px 16px; border-radius: 10px; margin: 18px 0; font-weight: 700; }
        .info { background: #1e3a5f; color: #bfdbfe; padding: 12px 14px; border-radius: 10px; margin: 12px 0; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #020617; padding: 10px; border-radius: 8px; font-size: 13px; }
        code { background: #020617; padding: 2px 6px; border-radius: 6px; }
        a { color: #93c5fd; }
    </style>
</head>
<body>
    <h1 class="<?= $allOk ? 'ok' : 'bad' ?>">
        <?= $allOk
            ? ($action === 'rollback' ? 'Rollback completed' : 'Migration completed')
            : 'Finished with errors' ?>
    </h1>
    <p>Action: <code><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?></code>
       · Path: <code><?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?></code></p>

    <div class="warn">
        DELETE this file now from cPanel File Manager:<br>
        <code>public/run-migrate-update.php</code>
    </div>

    <?php if ($action === 'migrate'): ?>
    <div class="info">
        If something broke and you need DB rollback (last migration only):<br>
        <code>?key=Zingers-Update-20260905&amp;action=rollback</code>
    </div>
    <?php endif; ?>

    <?php foreach ($steps as $name => $result): ?>
        <div class="card">
            <strong class="<?= ($result['ok'] ?? false) ? 'ok' : 'bad' ?>">
                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                — <?= ($result['ok'] ?? false) ? 'OK' : 'FAILED' ?>
                (exit <?= (int) ($result['exit'] ?? 1) ?>)
            </strong>
            <pre><?= htmlspecialchars((string) ($result['output'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php endforeach; ?>
</body>
</html>
