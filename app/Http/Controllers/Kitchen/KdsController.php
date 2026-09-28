<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    public function index()
    {
        return view('kitchen.kds');
    }

    public function orders()
    {
        $orders = KitchenOrder::with(['order.table', 'items'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->onLiveBoard()
            ->kitchen()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($kot) => [
                'id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'order_number' => $kot->order->order_number ?? 'N/A',
                'table' => $kot->order->table?->name ?? 'Takeaway',
                'order_type' => $kot->order->order_type,
                'status' => $kot->status,
                'elapsed_seconds' => (int) max(0, round(now()->diffInSeconds($kot->created_at))),
                'items' => $kot->items->map(fn($i) => [
                    'name' => $i->product_name,
                    'quantity' => (float) $i->quantity,
                    'instructions' => $i->special_instructions,
                    'status' => $i->status,
                ]),
            ]);
        return response()->json($orders);
    }

    public function updateStatus(Request $request)
    {
        $kot = KitchenOrder::findOrFail($request->kot_id);
        $kot->update(['status' => $request->status]);
        if ($request->status === 'preparing' && !$kot->started_at) {
            $kot->update(['started_at' => now()]);
        }
        if ($request->status === 'ready') {
            $kot->update(['completed_at' => now()]);
        }
        return response()->json(['success' => true]);
    }
}
