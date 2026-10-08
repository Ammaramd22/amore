<?php

use App\Http\Controllers\Install\InstallController;
use App\Support\InstallLock;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::post('/database', [InstallController::class, 'testDatabase'])->name('db');
    Route::post('/run', [InstallController::class, 'run'])->name('run');
});

// Convenience: if not installed, / also goes to installer (home route may still hit login)
if (! InstallLock::isInstalled()) {
    Route::redirect('/setup', '/install');
}
