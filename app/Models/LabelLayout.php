<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabelLayout extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'config',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'config' => 'array',
    ];

    public static function googleFonts(): array
    {
        return [
            'Roboto' => 'Roboto:wght@400;500;700',
            'Open Sans' => 'Open+Sans:wght@400;600;700',
            'Lato' => 'Lato:wght@400;700',
            'Montserrat' => 'Montserrat:wght@400;600;700',
            'Poppins' => 'Poppins:wght@400;600;700',
            'Nunito' => 'Nunito:wght@400;600;700',
            'Source Sans 3' => 'Source+Sans+3:wght@400;600;700',
            'Inter' => 'Inter:wght@400;600;700',
            'Oswald' => 'Oswald:wght@400;600;700',
            'Raleway' => 'Raleway:wght@400;600;700',
            'PT Sans' => 'PT+Sans:wght@400;700',
            'Noto Sans' => 'Noto+Sans:wght@400;600;700',
            'Ubuntu' => 'Ubuntu:wght@400;500;700',
            'Rubik' => 'Rubik:wght@400;600;700',
            'Work Sans' => 'Work+Sans:wght@400;600;700',
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'width_mm' => 38,
            'height_mm' => 25,
            'business_name' => '',
            'font_family' => 'Roboto',
            'price_type' => 'inc_tax',
            'currency_prefix' => 'Rs ',
            'barcode' => [
                'position' => 'right',
                'width' => 94,
                'height' => 25,
                'thickness' => 2,
            ],
            'fonts' => [
                'business_name' => 12,
                'item_name' => 9,
                'sku' => 8,
                'price' => 11,
                'cost_code' => 8,
            ],
            'show' => [
                'business_name' => true,
                'item_name' => true,
                'barcode' => true,
                'sku' => true,
                'price' => true,
                'cost_code' => true,
            ],
        ];
    }

    /** Merge + upgrade older designer configs into the QPOS-style schema. */
    public static function normalizeConfig(?array $config): array
    {
        $defaults = self::defaultConfig();
        if (! is_array($config) || $config === []) {
            return $defaults;
        }

        // Legacy free-form "fields" designer → map best-effort into new schema
        if (isset($config['fields']) && is_array($config['fields'])) {
            $f = $config['fields'];
            $config = [
                'width_mm' => $config['width_mm'] ?? $defaults['width_mm'],
                'height_mm' => $config['height_mm'] ?? $defaults['height_mm'],
                'business_name' => $config['business_name'] ?? '',
                'font_family' => $config['font_family'] ?? $defaults['font_family'],
                'price_type' => $config['price_type'] ?? 'inc_tax',
                'currency_prefix' => $config['currency_prefix'] ?? $defaults['currency_prefix'],
                'barcode' => [
                    'position' => 'right',
                    'width' => (int) ($f['barcode']['width_pct'] ?? 94),
                    'height' => (int) round(((float) ($f['barcode']['height_mm'] ?? 9)) * 2.8),
                    'thickness' => 2,
                ],
                'fonts' => [
                    'business_name' => 12,
                    'item_name' => (float) ($f['name']['font_size'] ?? 9),
                    'sku' => (float) ($f['barcode_text']['font_size'] ?? $f['sku']['font_size'] ?? 8),
                    'price' => (float) ($f['price']['font_size'] ?? 11),
                    'cost_code' => 8,
                ],
                'show' => [
                    'business_name' => true,
                    'item_name' => (bool) ($f['name']['visible'] ?? true),
                    'barcode' => (bool) ($f['barcode']['visible'] ?? true),
                    'sku' => (bool) (($f['barcode_text']['visible'] ?? false) || ($f['sku']['visible'] ?? false)),
                    'price' => (bool) ($f['price']['visible'] ?? true),
                    'cost_code' => false,
                ],
            ];
        }

        $merged = array_replace_recursive($defaults, $config);

        // Horizontal / landscape sticker: longer edge is width
        $w = (float) ($merged['width_mm'] ?? $defaults['width_mm']);
        $h = (float) ($merged['height_mm'] ?? $defaults['height_mm']);
        if ($h > $w) {
            $merged['width_mm'] = $h;
            $merged['height_mm'] = $w;
        }

        // Migrate legacy vertical barcode positions → horizontal left/right
        $pos = $merged['barcode']['position'] ?? 'right';
        if (in_array($pos, ['top', 'left'], true)) {
            $merged['barcode']['position'] = 'left';
        } else {
            $merged['barcode']['position'] = 'right';
        }

        return $merged;
    }

    public function normalizedConfig(): array
    {
        return self::normalizeConfig($this->config);
    }

    public function fontCssUrl(?array $config = null): string
    {
        $cfg = $config ?? $this->normalizedConfig();
        $family = $cfg['font_family'] ?? 'Roboto';
        $spec = self::googleFonts()[$family] ?? 'Roboto:wght@400;700';

        return 'https://fonts.googleapis.com/css2?family='.$spec.'&display=swap';
    }

    /** Retail-style cost code from a number (digits → letters). */
    public static function costCodeFromAmount(float $amount): string
    {
        $map = ['0' => 'X', '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E', '6' => 'F', '7' => 'G', '8' => 'H', '9' => 'I'];
        $digits = preg_replace('/\D+/', '', number_format($amount, 0, '', ''));
        if ($digits === '' || $digits === null) {
            return '';
        }
        // Keep last 3 digits for compact code
        $digits = substr($digits, -3);

        return collect(str_split($digits))->map(fn ($d) => $map[$d] ?? 'X')->implode('');
    }

    public static function ensureDefault(): self
    {
        $existing = static::query()->where('is_default', true)->first();
        if ($existing) {
            $normalized = self::normalizeConfig($existing->config);
            if ($existing->config !== $normalized) {
                $existing->update(['config' => $normalized]);
            }

            return $existing->fresh();
        }

        $any = static::query()->orderBy('id')->first();
        if ($any) {
            $any->update([
                'is_default' => true,
                'config' => self::normalizeConfig($any->config),
            ]);

            return $any->fresh();
        }

        return static::create([
            'name' => 'Default 38×25mm',
            'is_default' => true,
            'config' => self::defaultConfig(),
        ]);
    }
}
