<?php

namespace App\Support;

use App\Models\KitchenOrder;
use App\Models\Order;

class KotPrint
{
    public static function orderTypeLabel(?string $type): string
    {
        return match ($type) {
            'dine_in' => 'Dine In',
            'takeaway' => 'Take Away',
            'delivery' => 'Delivery',
            'express' => 'Express',
            default => ucfirst(str_replace('_', ' ', (string) $type)),
        };
    }

    /** True when item/order notes mention take away (pack with dine-in). */
    public static function hasTakeAwayNote(?Order $order, ?KitchenOrder $kot): bool
    {
        $chunks = [];
        if ($order && ! empty($order->order_notes)) {
            $chunks[] = (string) $order->order_notes;
        }
        if ($kot) {
            foreach ($kot->items as $item) {
                if (! empty($item->special_instructions)) {
                    $chunks[] = (string) $item->special_instructions;
                }
            }
        }
        if ($chunks === []) {
            return false;
        }

        $hay = strtolower(implode(' ', $chunks));

        return (bool) preg_match('/take\s*-?\s*away|takeaway/', $hay);
    }

    public static function tableLabel(?Order $order): string
    {
        // Only dine-in may show a physical table name (prevents takeaway KOT "Table: TABLE 3")
        if (($order?->order_type ?? null) === 'dine_in') {
            $table = trim((string) ($order?->table?->name ?? ''));
            if ($table !== '') {
                return $table;
            }

            return '-';
        }

        return match ($order?->order_type) {
            'takeaway' => 'Takeaway',
            'delivery' => $order->deliveryPartner?->name ?: 'Delivery',
            'express' => 'Express',
            default => '-',
        };
    }

    public static function waiterLabel(?Order $order): string
    {
        $name = trim((string) ($order?->waiter?->name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $name = trim((string) ($order?->cashier?->name ?? ''));

        return $name !== '' ? $name : '—';
    }
}
