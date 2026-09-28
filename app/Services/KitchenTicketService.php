<?php

namespace App\Services;

use App\Models\Kitchen;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Schema;

class KitchenTicketService
{
    /**
     * $isReorder marks tickets raised against an order that was already placed,
     * so the kitchen sees REORDER plus the original order number.
     */
    public function createKitchenOrders(Order $order, $items = null, array $qtyOverrides = [], bool $isReorder = false): array
    {
        $printJobs = [];
        $qtyOf = function ($item) use ($qtyOverrides) {
            $id = $item->id ?? null;
            if ($id && array_key_exists($id, $qtyOverrides)) {
                return (float) $qtyOverrides[$id];
            }

            return (float) $item->quantity;
        };
        $confirmation = (bool) Setting::get('kot_confirmation_enabled', true);
        $autoAccept = (bool) Setting::get('kitchen_auto_accept', false);
        $bakeryDirectOnly = Setting::get('pos_ui_mode', 'restaurant') === 'bakery'
            && (
                (bool) Setting::get('bakery_direct_billing', false)
                || (bool) Setting::get('bakery_disable_kot', false)
            );

        // Print-only mode: still create tickets for reprint/audit, but skip Accept/Ready/Serve boards
        if (! $confirmation) {
            $initialStatus = 'served';
        } elseif ($autoAccept) {
            $initialStatus = 'preparing';
        } else {
            $initialStatus = 'pending';
        }

        if ($items === null) {
            $items = $order->items()->with(['product.category.kitchen'])->get();
        } else {
            $itemIds = collect($items)->pluck('id')->filter()->all();
            $items = $order->items()
                ->with(['product.category.kitchen'])
                ->whereIn('id', $itemIds)
                ->get();
        }

        $items->each(function ($item) {
            // Custom items have no product — keep their routed_to as-is (defaults to 'kitchen')
            if (! empty($item->is_custom_item) || empty($item->product_id)) {
                return;
            }
            $categoryType = $item->product?->category?->type ?? 'kot';
            $routedTo = match ($categoryType) {
                'bot' => 'bar',
                'direct' => 'direct',
                default => 'kitchen',
            };
            if ($item->routed_to !== $routedTo) {
                $item->routed_to = $routedTo;
                $item->save();
            }
        });

        $kitchenItems = $items->where('routed_to', 'kitchen');
        $barItems = $items->where('routed_to', 'bar');
        $directItems = $items->where('routed_to', 'direct');

        foreach ($directItems as $item) {
            $product = $item->product;
            if ($product && $product->track_stock) {
                $product->decrement('stock_quantity', $qtyOf($item));
            }
        }

        // Bakery UI + Direct Billing Only: receipt sale only — no KOT/BOT tickets
        if ($bakeryDirectOnly) {
            return [];
        }

        $groupedByKitchen = $kitchenItems->groupBy(function ($item) {
            return $item->product?->category?->kitchen_id ?? 'general';
        });

        foreach ($groupedByKitchen as $kitchenId => $groupItems) {
            if ($groupItems->isEmpty()) {
                continue;
            }

            $kot = KitchenOrder::create([
                'order_id' => $order->id,
                'kitchen_id' => $kitchenId === 'general' ? null : $kitchenId,
                'kot_number' => OrderNumberService::generate(Setting::get('kot_prefix', 'KOT-'), 'kitchen_orders', (bool) Setting::get('kot_reset_daily', true), 'kitchen', 'kot_number'),
                'type' => 'kitchen',
                'status' => $initialStatus,
                'started_at' => in_array($initialStatus, ['preparing', 'served'], true) ? now() : null,
                'completed_at' => $initialStatus === 'served' ? now() : null,
            ] + $this->reorderAttribute($isReorder));

            foreach ($groupItems as $item) {
                $qty = $qtyOf($item);
                if ($qty <= 0) {
                    continue;
                }
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kot->id,
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $qty,
                    'special_instructions' => $item->special_instructions,
                    'status' => $initialStatus,
                    'started_at' => in_array($initialStatus, ['preparing', 'served'], true) ? now() : null,
                    'completed_at' => $initialStatus === 'served' ? now() : null,
                ]);
            }

            $kitchen = $kitchenId !== 'general' ? Kitchen::find($kitchenId) : null;
            $resolved = Kitchen::resolvePrinterForKitchenOrder($kitchen, 'kitchen');
            $autoPrint = (bool) ($kitchen?->auto_print ?? Setting::get('auto_print_kot', false));

            $printJobs[] = [
                'type' => 'kot',
                'kitchen_order_id' => $kot->id,
                'url' => route('pos.print-kot', ['order' => $order, 'kitchen_order_id' => $kot->id]),
                'auto_print' => $autoPrint,
                'kitchen_name' => $kitchen?->name ?? 'General Kitchen',
                'printer_ip' => $resolved['printer_ip'] !== '' ? $resolved['printer_ip'] : null,
                'printer_port' => $resolved['printer_port'],
                'printer_name' => $resolved['printer_name'],
                'print_mode' => 'direct',
            ];
        }

        if ($barItems->isNotEmpty()) {
            $barKitchen = $barItems->first()?->product?->category?->kitchen;
            $bot = KitchenOrder::create([
                'order_id' => $order->id,
                'kitchen_id' => $barKitchen?->id,
                'kot_number' => OrderNumberService::generate(Setting::get('bot_prefix', 'BOT-'), 'kitchen_orders', (bool) Setting::get('kot_reset_daily', true), 'bar', 'kot_number'),
                'type' => 'bar',
                'status' => $initialStatus,
                'started_at' => in_array($initialStatus, ['preparing', 'served'], true) ? now() : null,
                'completed_at' => $initialStatus === 'served' ? now() : null,
            ] + $this->reorderAttribute($isReorder));

            foreach ($barItems as $item) {
                $qty = $qtyOf($item);
                if ($qty <= 0) {
                    continue;
                }
                KitchenOrderItem::create([
                    'kitchen_order_id' => $bot->id,
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $qty,
                    'special_instructions' => $item->special_instructions,
                    'status' => $initialStatus,
                    'started_at' => in_array($initialStatus, ['preparing', 'served'], true) ? now() : null,
                    'completed_at' => $initialStatus === 'served' ? now() : null,
                ]);
            }

            $printJobs[] = [
                'type' => 'bot',
                'kitchen_order_id' => $bot->id,
                'url' => route('pos.print-bot', ['order' => $order, 'kitchen_order_id' => $bot->id]),
                'auto_print' => $barKitchen?->auto_print ?? (bool) Setting::get('auto_print_kot', false),
                'kitchen_name' => $barKitchen?->name ?? 'Bar',
                'printer_ip' => $barKitchen?->printer_ip,
                'printer_port' => $barKitchen ? $barKitchen->resolvedPrinterPort() : 9100,
                'printer_name' => $barKitchen?->printer_name,
                'print_mode' => $barKitchen ? $barKitchen->resolvedPrintMode() : 'preview',
            ];
        }

        // Print-only: do not force parent order to "served" (bill stays open until paid)
        if ($confirmation && $autoAccept && $order->status === 'pending') {
            $order->update(['status' => 'preparing']);
        }

        return $printJobs;
    }

    /** Skipped until the is_reorder migration has run, so ticket creation never hard-fails. */
    protected function reorderAttribute(bool $isReorder): array
    {
        static $supported = null;
        if ($supported === null) {
            $supported = Schema::hasColumn('kitchen_orders', 'is_reorder');
        }

        return $supported ? ['is_reorder' => $isReorder] : [];
    }

    public function deductStock(Order $order, $items = null): void
    {
        $items = $items ? collect($items) : $order->items;

        foreach ($items as $orderItem) {
            if ($orderItem->routed_to === 'direct') {
                continue;
            }

            if (empty($orderItem->product_id) || ! empty($orderItem->is_custom_item)) {
                continue;
            }

            $recipe = Recipe::with('items.ingredient')->where('product_id', $orderItem->product_id)->first();
            if (!$recipe) {
                continue;
            }

            foreach ($recipe->items as $recipeItem) {
                $ingredient = $recipeItem->ingredient;
                if (!$ingredient) {
                    continue;
                }

                $deductQty = $recipeItem->quantity * $orderItem->quantity;
                $before = $ingredient->stock_quantity;
                $ingredient->decrement('stock_quantity', $deductQty);

                StockMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'sale',
                    'quantity' => -$deductQty,
                    'stock_before' => $before,
                    'stock_after' => $before - $deductQty,
                    'unit' => $recipeItem->unit,
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    public function restoreStock(Order $order, $items): void
    {
        foreach (collect($items) as $orderItem) {
            $qty = (float) $orderItem->quantity;
            if ($qty <= 0) {
                continue;
            }

            if ($orderItem->routed_to === 'direct') {
                $product = $orderItem->product;
                if ($product && $product->track_stock) {
                    $product->increment('stock_quantity', $qty);
                }
                continue;
            }

            if (empty($orderItem->product_id) || ! empty($orderItem->is_custom_item)) {
                continue;
            }

            $recipe = Recipe::with('items.ingredient')->where('product_id', $orderItem->product_id)->first();
            if (! $recipe) {
                continue;
            }

            foreach ($recipe->items as $recipeItem) {
                $ingredient = $recipeItem->ingredient;
                if (! $ingredient) {
                    continue;
                }

                $restoreQty = $recipeItem->quantity * $qty;
                $before = $ingredient->stock_quantity;
                $ingredient->increment('stock_quantity', $restoreQty);

                StockMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'return',
                    'quantity' => $restoreQty,
                    'stock_before' => $before,
                    'stock_after' => $before + $restoreQty,
                    'unit' => $recipeItem->unit,
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    public function reduceKitchenQuantity(OrderItem $item, float $reduceBy): void
    {
        if ($reduceBy <= 0) {
            return;
        }

        $remaining = $reduceBy;
        $kotItems = KitchenOrderItem::with('kitchenOrder')
            ->where('order_item_id', $item->id)
            ->whereHas('kitchenOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'preparing' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->get();

        foreach ($kotItems as $kotItem) {
            if ($remaining <= 0) {
                break;
            }
            $qty = (float) $kotItem->quantity;
            $kotId = $kotItem->kitchen_order_id;
            if ($qty <= $remaining + 0.0001) {
                $remaining -= $qty;
                $kotItem->delete();
                $this->cancelKitchenOrderIfEmpty($kotId);
            } else {
                $kotItem->update(['quantity' => $qty - $remaining]);
                $remaining = 0;
            }
        }
    }

    protected function cancelKitchenOrderIfEmpty(int $kitchenOrderId): void
    {
        $kot = KitchenOrder::withCount('items')->find($kitchenOrderId);
        if ($kot && (int) $kot->items_count === 0) {
            $kot->update(['status' => 'cancelled']);
        }
    }

    /**
     * After a bill is fully paid, archive remaining kitchen/bar tickets as served.
     * Does not delete history. Already-cancelled tickets are left alone.
     */
    public function finalizeActiveTickets(Order $order): int
    {
        $tickets = KitchenOrder::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $ids = $tickets->pluck('id');
        $now = now();

        KitchenOrder::whereIn('id', $ids)
            ->whereNull('completed_at')
            ->update(['completed_at' => $now]);

        KitchenOrder::whereIn('id', $ids)->update(['status' => 'served']);

        KitchenOrderItem::whereIn('kitchen_order_id', $ids)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereNull('completed_at')
            ->update(['completed_at' => $now]);

        KitchenOrderItem::whereIn('kitchen_order_id', $ids)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->update(['status' => 'served']);

        return $ids->count();
    }

    /** Void / cancel: remove tickets from live boards without deleting rows. */
    public function cancelActiveTickets(Order $order): int
    {
        $tickets = KitchenOrder::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $ids = $tickets->pluck('id');

        KitchenOrder::whereIn('id', $ids)->update(['status' => 'cancelled']);

        return $ids->count();
    }
}
