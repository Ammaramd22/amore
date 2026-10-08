<?php

namespace App\Http\Controllers\SelfOrder;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelfOrderController extends Controller
{
    public function index()
    {
        $categories = Category::active()->posVisible()->with(['products' => function ($q) {
            $q->available()->posVisible()->with('variants', 'addons');
        }])->get();
        return view('self-order.index', compact('categories'));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'customer_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $subtotal = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_number' => 'SO-' . now()->format('Ymd-His'),
                'order_type' => 'takeaway',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'order_notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);
            }

            KitchenOrder::create([
                'order_id' => $order->id,
                'kot_number' => 'KOT-' . now()->format('Ymd-His') . '-' . $order->id,
                'type' => 'kitchen',
                'status' => 'pending',
            ]);

            DB::commit();
            return response()->json(['success' => true, 'order_number' => $order->order_number]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
