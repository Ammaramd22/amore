<?php
/**
 * ONE-TIME cPanel updater — open in browser, then DELETE this file.
 *
 * URL:
 *   https://your-domain.com/run-update-once.php?key=ResPOS-Update-2026-IceCream
 *
 * Fixes common cPanel issues:
 * - Tables already exist but missing from migrations table
 * - Missing users.login_code / pin columns
 * - Then runs remaining migrations + cache clears
 *
 * DELETE this file immediately after success.
 */

declare(strict_types=1);

const UPDATE_KEY = 'ResPOS-Update-2026-IceCream';

header('Content-Type: text/html; charset=utf-8');

$key = (string) ($_GET['key'] ?? '');
if (! hash_equals(UPDATE_KEY, $key)) {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Invalid or missing key.</p>';
    exit;
}

$publicDir = __DIR__;
$basePath = dirname($publicDir);

if (! is_file($basePath.'/artisan') || ! is_file($basePath.'/vendor/autoload.php')) {
    http_response_code(500);
    echo '<h1>Error</h1><p>Laravel root not found above /public.</p>';
    exit;
}

require $basePath.'/vendor/autoload.php';
$app = require $basePath.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
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

function migrationAlreadyLogged(string $name): bool
{
    try {
        return DB::table('migrations')->where('migration', $name)->exists();
    } catch (Throwable $e) {
        return false;
    }
}

function markMigrationRan(string $name, int $batch = 1): string
{
    if (migrationAlreadyLogged($name)) {
        return "already logged: {$name}";
    }

    $maxBatch = (int) (DB::table('migrations')->max('batch') ?: 0);
    DB::table('migrations')->insert([
        'migration' => $name,
        'batch' => max($batch, $maxBatch + 1),
    ]);

    return "marked as ran: {$name}";
}

$steps = [];
$notes = [];

/*
|--------------------------------------------------------------------------
| 1) Heal migration history when tables already exist
|--------------------------------------------------------------------------
| Production often has tables created earlier, but migrations table is behind.
| Mark those create-* migrations as ran so migrate can continue.
*/
$healMap = [
    // migration file basename (without .php) => table that proves it already ran
    '2026_05_18_070000_create_delivery_partners_table' => 'delivery_partners',
];

$healOut = [];
foreach ($healMap as $migration => $table) {
    try {
        if (Schema::hasTable($table) && ! migrationAlreadyLogged($migration)) {
            $healOut[] = markMigrationRan($migration);
        } elseif (Schema::hasTable($table)) {
            $healOut[] = "ok (table exists + logged): {$table}";
        } else {
            $healOut[] = "pending create: {$table}";
        }
    } catch (Throwable $e) {
        $healOut[] = "heal error {$migration}: ".$e->getMessage();
    }
}
$steps['heal-migration-history'] = [
    'ok' => true,
    'exit' => 0,
    'output' => implode("\n", $healOut) ?: '(nothing to heal)',
];

/*
|--------------------------------------------------------------------------
| 2) Ensure PIN login columns exist (critical for /login/pin)
|--------------------------------------------------------------------------
*/
try {
    $colNotes = [];
    if (Schema::hasTable('users')) {
        Schema::table('users', function ($table) use (&$colNotes) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 80)->nullable()->unique();
                $colNotes[] = 'added users.username';
            }
            if (! Schema::hasColumn('users', 'login_code')) {
                $table->string('login_code', 4)->nullable()->unique();
                $colNotes[] = 'added users.login_code';
            }
            if (! Schema::hasColumn('users', 'pin')) {
                $table->string('pin', 255)->nullable();
                $colNotes[] = 'added users.pin';
            }
        });

        // Also mark the branch/login migration as ran if columns now exist
        $loginMigration = '2026_07_19_221000_branch_products_and_login_ids';
        if (Schema::hasColumn('users', 'login_code') && ! migrationAlreadyLogged($loginMigration)) {
            // Only mark if branch_product also exists OR we only needed login cols.
            // Safer: don't mark whole migration; let migrate try the rest with hasColumn guards.
            $colNotes[] = 'login_code ready (migration may still run for branch_product)';
        }
    } else {
        $colNotes[] = 'users table missing';
    }

    $steps['ensure-login-columns'] = [
        'ok' => true,
        'exit' => 0,
        'output' => implode("\n", $colNotes) ?: 'login columns already present',
    ];
} catch (Throwable $e) {
    $steps['ensure-login-columns'] = [
        'ok' => false,
        'exit' => 1,
        'output' => $e->getMessage(),
    ];
}

/*
|--------------------------------------------------------------------------
| 3) Run remaining migrations (retry once after auto-heal on "already exists")
|--------------------------------------------------------------------------
*/
$migrate = runArtisan($kernel, 'migrate', ['--force' => true]);
if (! ($migrate['ok'] ?? false) && str_contains((string) $migrate['output'], 'already exists')) {
    // Extract table name if possible and mark matching create migration
    if (preg_match("/Table '([^']+)' already exists/", (string) $migrate['output'], $m)) {
        $table = $m[1];
        $files = glob($basePath.'/database/migrations/*create_'.$table.'_table.php') ?: [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $notes[] = markMigrationRan($name);
        }
        // Also try wildcard match create_*{$table}*
        $files2 = glob($basePath.'/database/migrations/*'.$table.'*.php') ?: [];
        foreach ($files2 as $file) {
            $base = basename($file, '.php');
            if (str_contains($base, 'create_') && Schema::hasTable($table)) {
                $notes[] = markMigrationRan($base);
            }
        }
    }
    $migrate = runArtisan($kernel, 'migrate', ['--force' => true]);
    if ($notes) {
        $migrate['output'] = implode("\n", $notes)."\n\n".$migrate['output'];
    }
}
$steps['migrate'] = $migrate;

$steps['view:clear'] = runArtisan($kernel, 'view:clear');
$steps['route:clear'] = runArtisan($kernel, 'route:clear');
$steps['config:clear'] = runArtisan($kernel, 'config:clear');

$link = $publicDir.'/storage';
$target = $basePath.'/storage/app/public';
if (is_link($link) || (file_exists($link) && ! is_dir($link))) {
    $steps['storage:link'] = [
        'ok' => true,
        'exit' => 0,
        'output' => 'public/storage already exists',
    ];
} else {
    $steps['storage:link'] = runArtisan($kernel, 'storage:link');
    if (! ($steps['storage:link']['ok'] ?? false) && ! file_exists($link) && is_dir($target)) {
        if (@symlink($target, $link)) {
            $steps['storage:link'] = [
                'ok' => true,
                'exit' => 0,
                'output' => 'Created symlink via PHP fallback',
            ];
        }
    }
}

// Final check for login_code
$loginReady = false;
try {
    $loginReady = Schema::hasTable('users') && Schema::hasColumn('users', 'login_code');
} catch (Throwable $e) {
    $loginReady = false;
}
$steps['check-login_code'] = [
    'ok' => $loginReady,
    'exit' => $loginReady ? 0 : 1,
    'output' => $loginReady
        ? 'users.login_code exists — PIN login should work'
        : 'users.login_code still missing — report this output',
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
    <title>ResPOS Update</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 860px; margin: 40px auto; padding: 0 16px; background: #0f172a; color: #e2e8f0; }
        h1 { margin-bottom: 8px; }
        .ok { color: #4ade80; }
        .bad { color: #f87171; }
        .warn { background: #7f1d1d; color: #fecaca; padding: 14px 16px; border-radius: 10px; margin: 18px 0; font-weight: 700; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #020617; padding: 10px; border-radius: 8px; font-size: 13px; }
        code { background: #020617; padding: 2px 6px; border-radius: 6px; }
    </style>
</head>
<body>
    <h1 class="<?= $allOk ? 'ok' : 'bad' ?>">
        <?= $allOk ? 'Update completed' : 'Update finished with errors' ?>
    </h1>
    <p>Ran from <code><?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?></code></p>

    <div class="warn">
        DELETE this file now from cPanel File Manager:<br>
        <code>public/run-update-once.php</code>
        <?php if ($loginReady): ?>
            <br><br>Then try PIN login again.
        <?php endif; ?>
    </div>

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
