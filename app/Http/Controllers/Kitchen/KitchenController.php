<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        return view('kitchen.index');
    }

    public function orders()
    {
        $orders = KitchenOrder::with(['order.table', 'order.customer', 'order.waiter', 'items'])
            ->whereIn('status', ['pending', 'preparing'])
            ->onLiveBoard()
            ->kitchen()
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($kot) {
                return [
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'order_id' => $kot->order_id,
                    'order_number' => $kot->order->order_number ?? 'N/A',
                    'table' => $kot->order->table?->name ?? 'Takeaway',
                    'order_type' => $kot->order->order_type,
                    'waiter' => $kot->order->waiter?->name ?? '',
                    'customer' => $kot->order->customer?->name ?? 'Walk-in',
                    'notes' => $kot->notes ?? $kot->order->order_notes,
                    'status' => $kot->status,
                    'created_at' => $kot->created_at->diffForHumans(),
                    'elapsed_seconds' => (int) max(0, round(now()->diffInSeconds($kot->created_at))),
                    'items' => $kot->items->map(fn($i) => [
                        'id' => $i->id,
                        'name' => $i->product_name,
                        'quantity' => (float) $i->quantity,
                        'instructions' => $i->special_instructions,
                        'status' => $i->status,
                    ]),
                ];
            });
        return response()->json($orders);
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['kot_id' => 'required|integer', 'status' => 'required|in:pending,preparing,ready,served']);

        $kot = KitchenOrder::findOrFail($request->kot_id);
        $kot->update(['status' => $request->status]);

        if ($request->status === 'preparing' && !$kot->started_at) {
            $kot->update(['started_at' => now()]);
        }
        if ($request->status === 'ready') {
            $kot->update(['completed_at' => now()]);
            KitchenOrderItem::where('kitchen_order_id', $kot->id)->update(['status' => 'ready', 'completed_at' => now()]);
        }

        if (in_array($request->status, ['ready', 'served'])) {
            $kot->order->update(['status' => $request->status === 'served' ? 'served' : 'ready']);
        }

        return response()->json(['success' => true]);
    }

    public function printKot(KitchenOrder $kot)
    {
        $kot->load('items', 'order.table', 'order.customer');
        return view('pos.kot_print', compact('kot'));
    }
}
