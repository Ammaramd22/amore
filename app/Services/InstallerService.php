<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Support\InstallLock;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PDO;
use Throwable;

class InstallerService
{
    /** @return list<array{name:string,required:bool,ok:bool,hint:string}> */
    public function requirements(): array
    {
        $ext = fn (string $e) => extension_loaded($e);

        return [
            ['name' => 'PHP >= 8.2', 'required' => true, 'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'hint' => 'Current: '.PHP_VERSION],
            ['name' => 'PDO MySQL', 'required' => true, 'ok' => $ext('pdo_mysql'), 'hint' => 'Enable pdo_mysql in MultiPHP INI'],
            ['name' => 'OpenSSL', 'required' => true, 'ok' => $ext('openssl'), 'hint' => 'Required for encryption'],
            ['name' => 'Mbstring', 'required' => true, 'ok' => $ext('mbstring'), 'hint' => 'Required for Unicode (Sinhala/Tamil)'],
            ['name' => 'Tokenizer', 'required' => true, 'ok' => $ext('tokenizer'), 'hint' => 'Required by Laravel'],
            ['name' => 'XML', 'required' => true, 'ok' => $ext('xml'), 'hint' => 'Required by Laravel'],
            ['name' => 'Ctype', 'required' => true, 'ok' => $ext('ctype'), 'hint' => 'Required by Laravel'],
            ['name' => 'JSON', 'required' => true, 'ok' => $ext('json'), 'hint' => 'Required by Laravel'],
            ['name' => 'BCMath', 'required' => true, 'ok' => $ext('bcmath'), 'hint' => 'Money / totals'],
            ['name' => 'Fileinfo', 'required' => true, 'ok' => $ext('fileinfo'), 'hint' => 'Uploads / logos'],
            ['name' => 'GD or Imagick', 'required' => true, 'ok' => $ext('gd') || $ext('imagick'), 'hint' => 'Image processing'],
            ['name' => 'cURL', 'required' => false, 'ok' => $ext('curl'), 'hint' => 'SMS / WhatsApp / APIs'],
            ['name' => 'Zip', 'required' => false, 'ok' => $ext('zip'), 'hint' => 'Optional packages'],
            ['name' => 'storage/ writable', 'required' => true, 'ok' => is_writable(storage_path()), 'hint' => 'chmod -R 775 storage'],
            ['name' => 'bootstrap/cache writable', 'required' => true, 'ok' => is_writable(base_path('bootstrap/cache')), 'hint' => 'chmod -R 775 bootstrap/cache'],
            ['name' => '.env writable', 'required' => true, 'ok' => (! File::exists(base_path('.env'))) || is_writable(base_path('.env')), 'hint' => 'Installer must write .env'],
        ];
    }

    public function requirementsPass(): bool
    {
        foreach ($this->requirements() as $row) {
            if ($row['required'] && ! $row['ok']) {
                return false;
            }
        }

        return true;
    }

    /** @return array{ok:bool,message:string,created?:bool} */
    public function testDatabase(array $db): array
    {
        $host = $db['host'] ?? '127.0.0.1';
        $port = (int) ($db['port'] ?? 3306);
        $name = $db['database'] ?? '';
        $user = $db['username'] ?? '';
        $pass = $db['password'] ?? '';
        $create = (bool) ($db['create_database'] ?? false);

        if ($name === '' || $user === '') {
            return ['ok' => false, 'message' => 'Database name and username are required.'];
        }

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $created = false;
            if ($create) {
                $safe = str_replace('`', '``', $name);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $created = true;
            }

            $pdo->exec('USE `'.str_replace('`', '``', $name).'`');
            $pdo->query('SELECT 1');

            return [
                'ok' => true,
                'message' => $created
                    ? 'Connected and database created successfully.'
                    : 'Database connection successful.',
                'created' => $created,
            ];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains(strtolower($msg), 'unknown database') && ! $create) {
                $msg .= ' — tick “Create database” if your MySQL user has CREATE privilege, or create the DB in cPanel → MySQL Databases first.';
            }

            return ['ok' => false, 'message' => $msg];
        }
    }

    public function writeEnv(array $app, array $db): void
    {
        $example = base_path('.env.example');
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            File::copy($example, $envPath);
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        if (File::exists($envPath)) {
            $existing = File::get($envPath);
            if (preg_match('/^APP_KEY=(.+)$/m', $existing, $m) && trim($m[1]) !== '') {
                $key = trim($m[1]);
            }
        }

        $url = rtrim((string) ($app['url'] ?? ''), '/');
        if ($url === '') {
            $url = url('/');
        }

        $pairs = [
            'APP_NAME' => '"'.str_replace('"', '\\"', $app['name'] ?? 'QRPOS').'"',
            'APP_ENV' => 'production',
            'APP_KEY' => $key,
            'APP_DEBUG' => 'false',
            'APP_URL' => $url,
            'APP_TIMEZONE' => 'Asia/Colombo',
            'LOG_LEVEL' => 'error',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'] ?? '127.0.0.1',
            'DB_PORT' => (string) ($db['port'] ?? '3306'),
            'DB_DATABASE' => $db['database'] ?? '',
            'DB_USERNAME' => $db['username'] ?? '',
            'DB_PASSWORD' => $db['password'] ?? '',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
            'FILESYSTEM_DISK' => 'local',
        ];

        $content = File::get($envPath);
        foreach ($pairs as $k => $v) {
            if (preg_match("/^{$k}=.*/m", $content)) {
                $content = preg_replace("/^{$k}=.*/m", $k.'='.$v, $content);
            } else {
                $content .= PHP_EOL.$k.'='.$v;
            }
        }

        File::put($envPath, $content);
    }

    /** @return array{ok:bool,message:string} */
    public function runMigrationsAndSeed(bool $withDemo, array $admin): array
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            // Reload env into runtime
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            Artisan::call('migrate', ['--force' => true]);

            Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => SettingSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => AccountSeeder::class, '--force' => true]);

            if ($withDemo) {
                Artisan::call('db:seed', ['--class' => \Database\Seeders\DemoDataSeeder::class, '--force' => true]);
            }

            $this->createSoftwareOwner();
            $this->createAdminUser($admin);

            Setting::set('company_name', $admin['business_name'] ?? 'QRPOS', 'business');
            Setting::set('welcome_show', '1', 'system', 'Show first-use welcome', 'boolean');
            Setting::set('guide_default_lang', 'en', 'system', 'Default guide language', 'string');

            try {
                Artisan::call('storage:link');
            } catch (Throwable $e) {
                // symlink may be blocked on some hosts
            }

            return ['ok' => true, 'message' => 'Database migrated and admin created.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function createSoftwareOwner(): User
    {
        $email = strtolower(trim((string) env('SOFTWARE_OWNER_EMAIL', 'owner@avenque.io')));
        $password = (string) env('SOFTWARE_OWNER_PASSWORD', 'password');
        $pin = (string) env('SOFTWARE_OWNER_PIN', '0000');

        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'name' => 'Software Owner',
            'phone' => $user->phone ?: '0768222201',
            'password' => Hash::make($password),
            'employee_code' => $user->employee_code ?: 'OWN001',
            'login_code' => $user->login_code ?: '0000',
            'user_type' => 'software_owner',
            'is_active' => true,
        ]);
        $user->pin = $pin;
        $user->save();
        $user->syncRoles(['software_owner']);

        return $user;
    }

    public function createAdminUser(array $admin): User
    {
        $email = strtolower(trim($admin['email'] ?? 'admin@example.com'));
        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $admin['name'] ?? 'Administrator',
            'phone' => $admin['phone'] ?? null,
            'password' => Hash::make($admin['password'] ?? 'password'),
            'employee_code' => $admin['employee_code'] ?? 'ADM001',
            'login_code' => $user->login_code ?: ($admin['login_code'] ?? '1001'),
            'user_type' => 'admin',
            'is_active' => true,
        ]);
        if (! $user->hasPin()) {
            $user->pin = $admin['pin'] ?? '1111';
        }
        $user->save();
        $user->syncRoles(['admin']);

        return $user;
    }

    /**
     * Wipe restaurant/demo data for customer handover — keeps schema, roles, settings structure.
     *
     * @return array{ok:bool,message:string}
     */
    public function freshStart(array $admin): array
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tables = [
                'order_item_addons', 'order_items', 'payments', 'kitchen_order_items', 'kitchen_orders',
                'orders', 'held_orders', 'card_transactions', 'cash_registers',
                'purchase_items', 'purchases', 'supplier_payments', 'stock_movements',
                'recipe_items', 'recipes', 'product_addons', 'product_variants', 'products',
                'subcategories', 'categories', 'ingredients', 'expenses',
                'account_transactions', 'promo_campaigns', 'waiter_ratings',
                'notification_logs', 'activity_logs', 'customers', 'suppliers',
                'restaurant_tables', 'floors', 'kitchens', 'delivery_partners',
                'notifications',
            ];

            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    DB::table($table)->truncate();
                }
            }

            // Keep chart of accounts; clear balances via AccountSeeder re-run optionally
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => SettingSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => AccountSeeder::class, '--force' => true]);

            // Remove non-owner demo users except new admin
            User::query()
                ->where('user_type', '!=', 'software_owner')
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'software_owner'))
                ->delete();

            $this->createAdminUser($admin);

            Setting::set('company_name', $admin['business_name'] ?? Setting::get('company_name', 'QRPOS'), 'business');
            Setting::set('welcome_show', '1', 'system', 'Show first-use welcome', 'boolean');
            Setting::set('fresh_started_at', now()->toDateTimeString(), 'system', 'Last fresh start', 'string');

            Setting::flushCache();

            return ['ok' => true, 'message' => 'Fresh start complete. Demo data cleared and admin ready.'];
        } catch (Throwable $e) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable $ignored) {
            }

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function finish(array $meta = []): void
    {
        InstallLock::markInstalled($meta);
        try {
            Artisan::call('optimize:hosting');
        } catch (Throwable $e) {
            Artisan::call('config:clear');
        }
    }
}
