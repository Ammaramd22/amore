<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class InstallLock
{
    public static function path(): string
    {
        return storage_path('app/installed');
    }

    public static function isInstalled(): bool
    {
        return File::exists(self::path());
    }

    public static function markInstalled(array $meta = []): void
    {
        $payload = array_merge([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'app' => 'QRPOS',
        ], $meta);

        File::ensureDirectoryExists(dirname(self::path()));
        File::put(self::path(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function clear(): void
    {
        if (File::exists(self::path())) {
            File::delete(self::path());
        }
    }

    /** @return array<string, mixed>|null */
    public static function meta(): ?array
    {
        if (! self::isInstalled()) {
            return null;
        }

        $raw = File::get(self::path());
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}
