<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\KitchenOrder;
use App\Models\Kitchen;
use App\Models\Setting;
use Illuminate\Http\Request;

class KitchenDisplayController extends Controller
{
    public function index()
    {
        if (! (bool) Setting::get('kitchen_display_enabled', true)) {
            abort(403, 'Kitchen display is disabled in Settings → Kitchen.');
        }

        if (! (bool) Setting::get('kot_confirmation_enabled', true)) {
            abort(403, 'KOT confirmation is off — kitchen display is not used. Tickets print only. Enable “KOT Confirmation” in Settings → Kitchen if you need this screen.');
        }

        $kitchens = Kitchen::active()->get();
        $soundEnabled = (bool) Setting::get('kitchen_sound_alert', true);

        return view('admin.kitchen-display.index', compact('kitchens', 'soundEnabled'));
    }

    public function orders(Request $request)
    {
        if (! (bool) Setting::get('kitchen_display_enabled', true)) {
            return response()->json(['orders' => [], 'message' => 'Kitchen display disabled'], 403);
        }

        if (! (bool) Setting::get('kot_confirmation_enabled', true)) {
            return response()->json([
                'orders' => [],
                'confirmation_enabled' => false,
                'message' => 'KOT confirmation is off — print only',
            ]);
        }

        $kitchenId = $request->get('kitchen_id');

        $query = KitchenOrder::with(['order.table', 'order.waiter', 'items'])
            ->whereIn('status', ['pending', 'preparing'])
            ->onLiveBoard()
            ->orderByDesc('created_at');

        if ($kitchenId) {
            $query->where('kitchen_id', $kitchenId);
        }

        $orders = $query->get()->map(function ($kot) {
            return [
                'id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'order_number' => $kot->order?->order_number ?? 'N/A',
                'order_type' => $kot->order?->order_type ?? 'dine_in',
                'table_name' => $kot->order?->table?->name,
                'waiter_name' => $kot->order?->waiter?->name,
                'waiter_id' => $kot->order?->waiter_id,
                'status' => $kot->status,
                'items' => $kot->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'instructions' => $item->special_instructions,
                    ];
                }),
                'created_at' => $kot->created_at->format('H:i:s'),
                'elapsed_minutes' => (int) max(0, round($kot->created_at->diffInMinutes(now()))),
            ];
        });

        return response()->json(['orders' => $orders]);
    }

    public function markReady(Request $request, KitchenOrder $kitchenOrder)
    {
        $kitchenOrder->update(['status' => 'ready', 'completed_at' => now()]);

        $kitchenOrder->items()->update([
            'status' => 'ready',
            'completed_at' => now(),
        ]);

        $this->syncParentOrderStatus($kitchenOrder->order_id);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as ready — waiter & cashier notified',
            'order_id' => $kitchenOrder->order_id,
            'kot_number' => $kitchenOrder->kot_number,
            'waiter_id' => $kitchenOrder->order?->waiter_id,
        ]);
    }

    public function markStarted(Request $request, KitchenOrder $kitchenOrder)
    {
        $kitchenOrder->update(['status' => 'preparing', 'started_at' => now()]);

        $kitchenOrder->items()->update([
            'status' => 'preparing',
            'started_at' => now(),
        ]);

        if ($kitchenOrder->order && in_array($kitchenOrder->order->status, ['pending', 'confirmed'], true)) {
            $kitchenOrder->order->update(['status' => 'preparing']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order started — preparing',
        ]);
    }

    public function markServed(Request $request, KitchenOrder $kitchenOrder)
    {
        $kitchenOrder->update(['status' => 'served']);

        $kitchenOrder->items()->update([
            'status' => 'served',
            'completed_at' => now(),
        ]);

        $this->syncParentOrderStatus($kitchenOrder->order_id);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as served',
            'kot_number' => $kitchenOrder->kot_number,
        ]);
    }

    public function readyOrders(Request $request)
    {
        // End-of-day clear: leftover Ready tickets from previous service day go away
        KitchenOrder::clearPreviousDayReady();

        $kitchenId = $request->get('kitchen_id');
        $waiterId = $request->get('waiter_id');
        $dayStart = CashRegister::businessDayStart();

        $query = KitchenOrder::with(['order.table', 'order.waiter', 'items'])
            ->where('status', 'ready')
            ->where(function ($q) use ($dayStart) {
                $q->where('completed_at', '>=', $dayStart)
                    ->orWhere(function ($q2) use ($dayStart) {
                        $q2->whereNull('completed_at')->where('created_at', '>=', $dayStart);
                    });
            })
            ->whereHas('order', fn ($q) => $q->where('is_void', false)
                ->whereNotIn('status', ['cancelled', 'completed', 'refunded']))
            ->orderByDesc('completed_at');

        if ($kitchenId) {
            $query->where('kitchen_id', $kitchenId);
        }

        if ($waiterId) {
            $query->whereHas('order', fn ($q) => $q->where('waiter_id', $waiterId));
        }

        $orders = $query->limit(30)->get()
            ->map(function ($kot) {
                return [
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'order_id' => $kot->order_id,
                    'order_number' => $kot->order?->order_number ?? 'N/A',
                    'order_type' => $kot->order?->order_type ?? 'dine_in',
                    'table_name' => $kot->order?->table?->name,
                    'waiter_id' => $kot->order?->waiter_id,
                    'waiter_name' => $kot->order?->waiter?->name,
                    'items' => $kot->items->map(function ($item) {
                        return [
                            'name' => $item->product_name,
                            'quantity' => $item->quantity,
                        ];
                    }),
                    'completed_at' => $kot->completed_at?->toISOString(),
                ];
            });

        return response()->json(['orders' => $orders]);
    }

    /**
     * Roll up KOT statuses onto the parent order for POS / waiter visibility.
     */
    protected function syncParentOrderStatus(int $orderId): void
    {
        $order = \App\Models\Order::find($orderId);
        if (!$order || $order->payment_status === 'paid') {
            return;
        }

        $statuses = KitchenOrder::where('order_id', $orderId)
            ->where('status', '!=', 'cancelled')
            ->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        if ($statuses->every(fn ($s) => $s === 'served')) {
            $order->update(['status' => 'served']);
        } elseif ($statuses->contains('ready') && $statuses->every(fn ($s) => in_array($s, ['ready', 'served'], true))) {
            $order->update(['status' => 'ready']);
        } elseif ($statuses->contains('preparing') || $statuses->contains('ready')) {
            if (in_array($order->status, ['pending', 'confirmed'], true)) {
                $order->update(['status' => 'preparing']);
            }
        }
    }
}
