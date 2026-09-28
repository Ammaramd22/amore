<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\InstallerService;
use App\Support\InstallLock;
use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function __construct(protected InstallerService $installer) {}

    public function index()
    {
        if (InstallLock::isInstalled()) {
            return redirect()->route('login')->with('success', 'QRPOS is already installed.');
        }

        return view('install.wizard', [
            'requirements' => $this->installer->requirements(),
            'pass' => $this->installer->requirementsPass(),
            'suggestedUrl' => url('/'),
        ]);
    }

    public function requirements()
    {
        return response()->json([
            'items' => $this->installer->requirements(),
            'pass' => $this->installer->requirementsPass(),
        ]);
    }

    public function testDatabase(Request $request)
    {
        $data = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'database' => 'required|string|max:64',
            'username' => 'required|string|max:64',
            'password' => 'nullable|string|max:255',
            'create_database' => 'nullable|boolean',
        ]);

        return response()->json($this->installer->testDatabase($data));
    }

    public function run(Request $request)
    {
        if (InstallLock::isInstalled()) {
            return response()->json(['ok' => false, 'message' => 'Already installed.'], 400);
        }

        if (! $this->installer->requirementsPass()) {
            return response()->json(['ok' => false, 'message' => 'Fix required server extensions / permissions first.'], 422);
        }

        $data = $request->validate([
            'app.name' => 'required|string|max:100',
            'app.url' => 'required|url|max:255',
            'db.host' => 'required|string|max:255',
            'db.port' => 'nullable|integer',
            'db.database' => 'required|string|max:64',
            'db.username' => 'required|string|max:64',
            'db.password' => 'nullable|string|max:255',
            'db.create_database' => 'nullable|boolean',
            'admin.name' => 'required|string|max:100',
            'admin.email' => 'required|email|max:150',
            'admin.password' => 'required|string|min:6|max:100',
            'admin.phone' => 'nullable|string|max:30',
            'admin.pin' => 'nullable|string|min:4|max:8',
            'admin.business_name' => 'nullable|string|max:150',
            'with_demo' => 'nullable|boolean',
        ]);

        $dbTest = $this->installer->testDatabase([
            ...$data['db'],
            'create_database' => (bool) ($data['db']['create_database'] ?? false),
        ]);
        if (! $dbTest['ok']) {
            return response()->json(['ok' => false, 'message' => $dbTest['message'], 'step' => 'database'], 422);
        }

        $this->installer->writeEnv($data['app'], $data['db']);

        // Re-bootstrap DB config for this request
        config([
            'database.connections.mysql.host' => $data['db']['host'],
            'database.connections.mysql.port' => $data['db']['port'] ?? 3306,
            'database.connections.mysql.database' => $data['db']['database'],
            'database.connections.mysql.username' => $data['db']['username'],
            'database.connections.mysql.password' => $data['db']['password'] ?? '',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $migrate = $this->installer->runMigrationsAndSeed(
            (bool) ($data['with_demo'] ?? false),
            [
                ...$data['admin'],
                'business_name' => $data['admin']['business_name'] ?? $data['app']['name'],
            ]
        );

        if (! $migrate['ok']) {
            return response()->json(['ok' => false, 'message' => $migrate['message'], 'step' => 'migrate'], 500);
        }

        $this->installer->finish([
            'admin_email' => $data['admin']['email'],
            'with_demo' => (bool) ($data['with_demo'] ?? false),
            'url' => $data['app']['url'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Installation complete.',
            'login_url' => url('/login'),
            'email' => $data['admin']['email'],
        ]);
    }
}
