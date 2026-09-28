<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'respos.settings.all';

    public const CACHE_TTL = 86400; // 24h — flushed on every write

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    protected $casts = [
        'value' => 'string',
    ];

    /** @var EloquentCollection<string, static>|null */
    protected static ?EloquentCollection $runtimeCache = null;

    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['type'] = 'json';
        } elseif (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';
            $this->attributes['type'] = 'boolean';
        } elseif (is_int($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'integer';
        } elseif (is_float($value)) {
            $this->attributes['value'] = (string) $value;
            $this->attributes['type'] = 'float';
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /** All settings keyed by `key` (request memory + file/redis cache). */
    public static function allCached(): EloquentCollection
    {
        if (static::$runtimeCache instanceof EloquentCollection) {
            return static::$runtimeCache;
        }

        static::$runtimeCache = Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return static::query()->get()->keyBy('key');
        });

        return static::$runtimeCache;
    }

    public static function flushCache(): void
    {
        static::$runtimeCache = null;
        Cache::forget(static::CACHE_KEY);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::allCached()->get($key);

        return $setting ? $setting->value : $default;
    }

    /**
     * Configured system timezone (source of truth for POS print / business clock).
     * Falls back to APP_TIMEZONE / config, then Asia/Colombo for this product default.
     */
    public static function timezone(): string
    {
        $tz = trim((string) static::get('timezone', ''));
        if ($tz === '') {
            $tz = trim((string) config('app.timezone', 'UTC'));
        }

        if ($tz === '' || ! in_array($tz, timezone_identifiers_list(), true)) {
            $tz = 'Asia/Colombo';
        }

        return $tz;
    }

    /** Format a datetime in the configured system timezone. */
    public static function formatDateTime(mixed $value, string $format = 'Y-m-d H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            if ($value instanceof CarbonInterface) {
                $dt = $value->copy();
            } elseif ($value instanceof DateTimeInterface) {
                $dt = Carbon::instance($value);
            } else {
                $dt = Carbon::parse((string) $value);
            }

            return $dt->timezone(static::timezone())->format($format);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Batch-read many keys with defaults: ['tax_rate' => 0, ...] */
    public static function many(array $keysWithDefaults): array
    {
        $out = [];
        foreach ($keysWithDefaults as $key => $default) {
            $out[$key] = static::get($key, $default);
        }

        return $out;
    }

    public static function set(string $key, mixed $value, ?string $group = null, ?string $description = null, ?string $type = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        if ($type) {
            $setting->type = $type;
        }
        $setting->value = $value;
        if ($group !== null) {
            $setting->group = $group;
        } elseif (! $setting->exists) {
            $setting->group = 'general';
        }
        if ($description !== null) {
            $setting->description = $description;
        }
        $setting->save();
        static::flushCache();

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return static::allCached()
            ->where('group', $group)
            ->mapWithKeys(fn ($item) => [$item->key => $item->value])
            ->all();
    }

    /** Public URL for company/software logo, or null if unset. */
    public static function logoUrl(?string $fallback = null): ?string
    {
        return static::logoUrlFor('company_logo', $fallback);
    }

    /** Public URL for invoice logo (falls back to company logo). */
    public static function invoiceLogoUrl(?string $fallback = null): ?string
    {
        return static::logoUrlFor('invoice_logo')
            ?? static::logoUrlFor('company_logo', $fallback);
    }

    public static function logoUrlFor(string $key, ?string $fallback = null): ?string
    {
        $logo = static::get($key);
        if (! $logo) {
            return $fallback;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '//')) {
            return $logo;
        }

        if (str_starts_with($logo, '/storage/') || str_starts_with($logo, 'storage/')) {
            return asset(ltrim($logo, '/'));
        }

        if (str_starts_with($logo, '/')) {
            return asset(ltrim($logo, '/'));
        }

        return asset('storage/' . ltrim($logo, '/'));
    }

    /** Absolute filesystem path for a stored logo. */
    public static function logoPathFor(string $key): ?string
    {
        $logo = static::get($key);
        if (! $logo || str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '//')) {
            return null;
        }

        $relative = ltrim($logo, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $path = storage_path('app/public/' . $relative);

        return is_file($path) ? $path : null;
    }

    /** Public URL for PWA app logo (falls back to software logo). */
    public static function pwaLogoUrl(?string $fallback = null): ?string
    {
        return static::logoUrlFor('pwa_app_logo')
            ?? static::logoUrlFor('company_logo', $fallback)
            ?? asset('pwa/waiter/icon-192.png');
    }

    /** Public URL for PWA splash image (optional). */
    public static function pwaSplashUrl(): ?string
    {
        return static::logoUrlFor('pwa_splash_image');
    }

    /** @return array{name:string,short_name:string,theme_color:string,background_color:string,logo:?string,splash:?string} */
    public static function pwaConfig(): array
    {
        $name = trim((string) static::get('pwa_app_name', 'QRPOS Waiter Panel')) ?: 'QRPOS Waiter Panel';
        $short = trim((string) static::get('pwa_app_short_name', 'QRPOS Waiter')) ?: 'QRPOS Waiter';
        $theme = trim((string) static::get('pwa_theme_color', '#1c1410')) ?: '#1c1410';
        $bg = trim((string) static::get('pwa_background_color', '#1c1410')) ?: '#1c1410';

        return [
            'name' => $name,
            'short_name' => $short,
            'theme_color' => $theme,
            'background_color' => $bg,
            'logo' => static::pwaLogoUrl(),
            'splash' => static::pwaSplashUrl(),
        ];
    }

    /** Data URI for invoice/PDF printing (invoice logo, else company logo). */
    public static function invoiceLogoDataUri(int $maxWidth = 240): ?string
    {
        $path = static::logoPathFor('invoice_logo') ?? static::logoPathFor('company_logo');
        if (! $path) {
            return null;
        }

        $resized = static::resizeLogoForPrint($path, $maxWidth);
        if ($resized) {
            return $resized;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** Downscale large logos so DomPDF / browser 80mm print stay reliable. */
    protected static function resizeLogoForPrint(string $path, int $maxWidth): ?string
    {
        if ($maxWidth < 32 || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info || empty($info[0]) || empty($info[1])) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? (@mime_content_type($path) ?: 'image/png');

        $src = match (true) {
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            default => null,
        };

        if (! $src) {
            return null;
        }

        if ($width <= $maxWidth) {
            ob_start();
            imagepng($src, null, 6);
            $data = ob_get_clean();
            imagedestroy($src);

            return $data ? 'data:image/png;base64,' . base64_encode($data) : null;
        }

        $newW = $maxWidth;
        $newH = max(1, (int) round($height * ($maxWidth / $width)));
        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($src);

        ob_start();
        imagepng($dst, null, 6);
        $data = ob_get_clean();
        imagedestroy($dst);

        return $data ? 'data:image/png;base64,' . base64_encode($data) : null;
    }
}
