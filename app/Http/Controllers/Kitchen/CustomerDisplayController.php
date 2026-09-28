<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\Setting;
use Illuminate\Http\Request;

class CustomerDisplayController extends Controller
{
    protected function brandData(): array
    {
        return [
            'companyName' => Setting::get('company_name', 'ResPOS'),
            'companyPhone' => Setting::get('company_phone', ''),
            'logoUrl' => Setting::logoUrl(),
            'currency' => Setting::get('currency_symbol', 'Rs'),
        ];
    }

    public function index()
    {
        return view('dual-display.index', $this->brandData());
    }

    public function summary()
    {
        return redirect()->route('customer.display', [], 301);
    }

    public function status()
    {
        return view('customer-display.status', $this->brandData());
    }

    public function orders()
    {
        $normalizeTable = function (?string $name): ?string {
            if (!$name) {
                return null;
            }
            $name = trim($name);
            while (preg_match('/^table\s+/i', $name)) {
                $name = trim(preg_replace('/^table\s+/i', '', $name));
            }

            return $name !== '' ? $name : null;
        };

        $prefixFor = function (string $typeRaw): string {
            return match ($typeRaw) {
                'takeaway' => 'TA',
                'delivery' => 'DEL',
                'express' => 'EXP',
                default => 'D',
            };
        };

        $displayNumber = function ($order) use ($prefixFor): string {
            $typeRaw = $order->order_type ?? 'dine_in';
            $prefix = $prefixFor($typeRaw);
            $digits = preg_replace('/\D+/', '', (string) ($order->order_number ?? ''));
            // Use last up-to-3 digits so INV-0007 / INV-20260711-0003 → 007 / 003
            $seq = $digits !== '' ? substr($digits, -3) : '000';
            $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);

            return $prefix.$seq;
        };

        $groupTickets = function ($kitchenOrders, string $elapsedField) use ($normalizeTable, $displayNumber) {
            return $kitchenOrders
                ->filter(fn ($kot) => $kot->order)
                ->groupBy('order_id')
                ->map(function ($group) use ($normalizeTable, $displayNumber, $elapsedField) {
                    $first = $group->sortBy('created_at')->first();
                    $order = $first?->order;
                    if (! $order) {
                        return null;
                    }
                    $typeRaw = $order->order_type ?? 'dine_in';
                    $types = $group->pluck('type')->unique()->values();
                    $hasKitchen = $types->contains('kitchen');
                    $hasBar = $types->contains('bar');

                    // Always invoice-based (D007 / TA001…) — never KOT-xxx / BOT-xxx
                    $display = $displayNumber($order);

                    $elapsedAt = $elapsedField === 'completed_at'
                        ? ($group->max('completed_at') ?: $first->created_at)
                        : $group->min('created_at');

                    return [
                        'order_id' => $order->id,
                        'display_number' => $display,
                        'order_number' => $order->order_number,
                        'type' => ucwords(str_replace('_', ' ', $typeRaw)),
                        'type_raw' => $typeRaw,
                        'table' => $typeRaw === 'dine_in' ? $normalizeTable($order->table?->name) : null,
                        'customer' => $order->customer?->name,
                        'has_kitchen' => $hasKitchen,
                        'has_bar' => $hasBar,
                        'elapsed' => $elapsedAt
                            ? \Illuminate\Support\Carbon::parse($elapsedAt)->diffForHumans(null, true)
                            : 'just now',
                    ];
                })
                ->filter()
                ->sortBy('display_number')
                ->values()
                ->take(36)
                ->values();
        };

        $hasActiveOrder = fn ($q) => $q->where('is_void', false)
            ->whereNotIn('status', ['cancelled', 'completed', 'refunded']);

        $preparing = $groupTickets(
            KitchenOrder::with(['order.table', 'order.customer'])
                ->whereHas('order', $hasActiveOrder)
                ->whereIn('status', ['pending', 'preparing'])
                ->orderBy('created_at', 'asc')
                ->get(),
            'created_at'
        );

        $readyHideSeconds = max(0, (int) Setting::get('customer_display_ready_seconds', 120));

        $readyQuery = KitchenOrder::with(['order.table', 'order.customer'])
            ->whereHas('order', $hasActiveOrder)
            ->where('status', 'ready')
            ->orderByDesc('completed_at');

        if ($readyHideSeconds > 0) {
            $readyQuery->where(function ($q) use ($readyHideSeconds) {
                $q->where('completed_at', '>=', now()->subSeconds($readyHideSeconds))
                    ->orWhere(function ($q2) use ($readyHideSeconds) {
                        $q2->whereNull('completed_at')
                            ->where('updated_at', '>=', now()->subSeconds($readyHideSeconds));
                    });
            });
        }

        $ready = $groupTickets($readyQuery->get(), 'completed_at');

        return response()->json([
            'preparing' => $preparing,
            'ready' => $ready,
            'ready_hide_seconds' => $readyHideSeconds,
        ]);
    }

    public function currentCart()
    {
        $cart = cache()->get('customer_display_cart');

        return response()->json([
            'active' => (bool) $cart,
            'cart' => $cart,
            'cleared' => ! $cart && cache()->has('customer_display_cleared_at'),
            'seq' => (int) cache()->get('customer_display_cart_seq', 0),
        ]);
    }

    public function dualDisplay()
    {
        return redirect()->route('customer.display', [], 301);
    }

    /** Classic LED / pole-style customer display (second screen). */
    public function led()
    {
        return view('customer-display.led', $this->brandData());
    }
}
