<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'table', 'cashier', 'waiter'])->notVoid()->latest();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($qb) use ($q) {
                $qb->where('order_number', 'like', "%{$q}%")
                    ->orWhere('order_notes', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->orWhereHas('table', function ($t) use ($q) {
                        $t->where('name', 'like', "%{$q}%")
                            ->orWhere('number', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $order->load([
            'items.product',
            'items.addons',
            'items.options',
            'payments',
            'customer',
            'table',
            'waiter',
            'cashier',
            'kitchenOrders.items',
        ]);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            $currency = \App\Models\Setting::get('currency_symbol', 'LKR');

            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'order_type_label' => ucfirst(str_replace('_', ' ', $order->order_type ?? '')),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'delivery_status' => $order->delivery_status,
                'table' => $order->table?->name,
                'customer' => $order->customer?->name ?? 'Walk-in',
                'waiter' => $order->waiter?->name,
                'cashier' => $order->cashier?->name,
                'notes' => $order->order_notes,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'tax_amount' => (float) $order->tax_amount,
                'tax_name' => \App\Models\Setting::get('tax_name', 'Tax'),
                'tax_enabled' => (bool) \App\Models\Setting::get('tax_enabled', false),
                'service_charge' => (float) $order->service_charge,
                'delivery_charge' => (float) $order->delivery_charge,
                'total_amount' => (float) $order->total_amount,
                'paid_amount' => (float) $order->paid_amount,
                'change_amount' => (float) $order->change_amount,
                'currency' => $currency,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'edit_url' => route('orders.edit', $order),
                'print_receipt_url' => route('pos.print-receipt', $order),
                'print_kot_url' => route('pos.print-kot', $order),
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'status' => $item->status,
                ]),
                'payments' => $order->payments->map(fn ($p) => [
                    'method' => ucfirst($p->method),
                    'amount' => (float) $p->amount,
                    'reference' => $p->reference_number,
                    'created_at' => $p->created_at?->format('Y-m-d H:i'),
                ]),
            ]);
        }

        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        return view('admin.orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->only(['status', 'delivery_status', 'payment_status']));
        if ($request->status === 'completed') {
            $order->update(['completed_at' => $order->completed_at ?? now()]);
            app(\App\Services\KitchenTicketService::class)->finalizeActiveTickets($order);
            RestaurantTable::syncOccupancy($order->table_id);
        }
        return redirect()->route('orders.show', $order)->with('success', 'Order updated.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }
}
