<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kitchen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'printer_name',
        'printer_ip',
        'printer_port',
        'print_mode',
        'auto_print',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_print' => 'boolean',
        'printer_port' => 'integer',
    ];

    /** Resolved print mode: direct (Print Bridge) or preview (browser). */
    public function resolvedPrintMode(): string
    {
        $mode = strtolower(trim((string) ($this->print_mode ?? 'preview')));

        return in_array($mode, ['direct', 'preview'], true) ? $mode : 'preview';
    }

    public function resolvedPrinterPort(): int
    {
        $port = (int) ($this->printer_port ?: 9100);

        return ($port >= 1 && $port <= 65535) ? $port : 9100;
    }

    public function resolvedPrinterName(): string
    {
        $name = trim((string) ($this->printer_name ?? ''));

        return $name !== '' ? $name : '';
    }

    /**
     * Resolve kitchen printer settings for a kitchen order (including null kitchen_id).
     * Prefers the ticket's kitchen, then first active matching KOT/BOT kitchen with a printer.
     * BOT without a bar printer configured must NOT fall back to the KOT printer.
     *
     * @return array{printer_ip:string,printer_port:int,printer_name:string,print_mode:string,has_printer:bool}
     */
    public static function resolvePrinterForKitchenOrder(?self $kitchen, string $ticketType = 'kitchen'): array
    {
        $isBar = in_array($ticketType, ['bar', 'bot'], true);

        if ($kitchen) {
            $ip = trim((string) ($kitchen->printer_ip ?? ''));
            $name = trim((string) ($kitchen->printer_name ?? ''));
            $has = $ip !== '' || $name !== '';

            return [
                'printer_ip' => $ip,
                'printer_port' => $kitchen->resolvedPrinterPort(),
                'printer_name' => $has ? ($name !== '' ? $name : ($isBar ? 'Bar Printer' : 'XP-80Kitchen KOT')) : '',
                'print_mode' => $has ? $kitchen->resolvedPrintMode() : 'preview',
                'has_printer' => $has,
            ];
        }

        $type = $isBar ? 'bot' : 'kot';
        $fallback = static::query()
            ->where('is_active', true)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNotNull('printer_name')->where('printer_name', '!=', '')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('printer_ip')->where('printer_ip', '!=', '');
                    });
            })
            ->orderBy('id')
            ->first();

        if ($fallback) {
            $ip = trim((string) ($fallback->printer_ip ?? ''));
            $name = trim((string) ($fallback->printer_name ?? ''));

            return [
                'printer_ip' => $ip,
                'printer_port' => $fallback->resolvedPrinterPort(),
                'printer_name' => $name !== '' ? $name : ($isBar ? 'Bar Printer' : 'XP-80Kitchen KOT'),
                'print_mode' => $fallback->resolvedPrintMode(),
                'has_printer' => true,
            ];
        }

        // No printer for this ticket type — do not point BOT at KOT
        return [
            'printer_ip' => '',
            'printer_port' => 9100,
            'printer_name' => '',
            'print_mode' => 'preview',
            'has_printer' => false,
        ];
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function kitchenOrders()
    {
        return $this->hasMany(KitchenOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
