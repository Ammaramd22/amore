<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\OrderItemOption;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\WaiterRating;
use App\Services\KitchenTicketService;
use App\Services\OrderNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaiterController extends Controller
{
    public function dashboard()
    {
        $waiter = auth()->user();
        $stats = $this->waiterStats($waiter->id);

        // Ensure sound settings exist
        if (!Setting::where('key', 'waiter_sound_alert')->exists()) {
            Setting::set('waiter_sound_alert', true, 'kitchen', null, 'boolean');
        }

        return view('waiter.dashboard', [
            'waiter' => $waiter,
            'stats' => $stats,
            'emojis' => WaiterRating::EMOJIS,
            'labels' => WaiterRating::LABELS,
            'soundEnabled' => (bool) Setting::get('waiter_sound_alert', true),
            'bringBillEnabled' => (bool) Setting::get('bring_bill_enabled', true),
            'kotConfirmationEnabled' => (bool) Setting::get('kot_confirmation_enabled', true),
            'ratingEnabled' => (bool) Setting::get('waiter_rating_enabled', true),
        ]);
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'stats' => $this->waiterStats(auth()->id()),
        ]);
    }

    public function myOrders(Request $request)
    {
        $tab = $request->get('tab', 'open'); // open | completed | rate
        $waiterId = auth()->id();

        if ($tab === 'open') {
            // Floor open bills — not only ones already tagged to this waiter
            $orders = Order::with(['table:id,name', 'waiterRating', 'waiter:id,name', 'kitchenOrders'])
                ->withCount(['items as items_count' => fn ($q) => $q->where('is_void', false)])
                ->notVoid()
                ->where('payment_status', 'unpaid')
                ->latest('id')
                ->limit(50)
                ->get();
        } elseif ($tab === 'completed') {
            $orders = Order::with(['table:id,name', 'waiterRating'])
                ->withCount(['items as items_count' => fn ($q) => $q->where('is_void', false)])
                ->notVoid()
                ->where('waiter_id', $waiterId)
                ->where('payment_status', 'paid')
                ->latest('completed_at')
                ->latest('id')
                ->limit(50)
                ->get();
        } else {
            // Rate: my paid unrated + unassigned paid (claimable)
            $orders = Order::with(['table:id,name', 'waiterRating', 'customer:id,name,phone'])
                ->withCount(['items as items_count' => fn ($q) => $q->where('is_void', false)])
                ->notVoid()
                ->where('payment_status', 'paid')
                ->whereDoesntHave('waiterRating')
                ->where(function ($q) use ($waiterId) {
                    $q->where('waiter_id', $waiterId)
                        ->orWhereNull('waiter_id');
                })
                ->where(function ($q) {
                    $q->where('completed_at', '>=', now()->subDays(14))
                        ->orWhere(function ($q2) {
                            $q2->whereNull('completed_at')
                                ->where('updated_at', '>=', now()->subDays(14));
                        });
                })
                ->latest('completed_at')
                ->latest('id')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'success' => true,
            'tab' => $tab,
            'stats' => $this->waiterStats($waiterId),
            'bring_bill_enabled' => (bool) Setting::get('bring_bill_enabled', true),
            'kot_confirmation_enabled' => (bool) Setting::get('kot_confirmation_enabled', true),
            'rating_enabled' => (bool) Setting::get('waiter_rating_enabled', true),
            'orders' => $orders->map(function (Order $o) use ($waiterId, $tab) {
                $rateUrl = null;
                $ratingOn = (bool) Setting::get('waiter_rating_enabled', true);
                if ($tab === 'rate' && $ratingOn) {
                    $rateUrl = \App\Http\Controllers\GuestRatingController::linkFor($o);
                }

                $bringBillOn = (bool) Setting::get('bring_bill_enabled', true);
                // Independent of KOT confirmation — available after order/KOT exists (open unpaid bill)
                $hasItems = (int) ($o->items_count ?? 0) > 0
                    || ($o->relationLoaded('items') ? $o->items->where('is_void', false)->isNotEmpty() : false);
                $canRequestBill = $bringBillOn
                    && $o->payment_status === 'unpaid'
                    && ! $o->bill_requested_at
                    && ($o->relationLoaded('kitchenOrders')
                        ? ($o->kitchenOrders->where('status', '!=', 'cancelled')->isNotEmpty() || $hasItems)
                        : ($o->kitchenOrders()->where('status', '!=', 'cancelled')->exists() || $hasItems));

                return [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'table' => $o->table?->name,
                    'payment_status' => $o->payment_status,
                    'status' => $o->status,
                    'items_count' => (int) ($o->items_count ?? $o->items()->where('is_void', false)->count()),
                    'completed_at' => $o->completed_at
                        ? Setting::formatDateTime($o->completed_at, 'Y-m-d H:i')
                        : Setting::formatDateTime($o->updated_at, 'Y-m-d H:i'),
                    'created_at' => Setting::formatDateTime($o->created_at, 'Y-m-d H:i'),
                    'bill_requested' => (bool) $o->bill_requested_at,
                    'bill_requested_at' => $o->bill_requested_at
                        ? Setting::formatDateTime($o->bill_requested_at, 'H:i')
                        : null,
                    'assigned_waiter' => $o->waiter?->name,
                    'is_mine' => (int) $o->waiter_id === (int) $waiterId,
                    'rating' => $o->waiterRating?->rating,
                    'emoji' => $o->waiterRating?->emoji,
                    'can_rate' => $ratingOn
                        && $o->payment_status === 'paid'
                        && ! $o->waiterRating
                        && ((int) $o->waiter_id === (int) $waiterId || $o->waiter_id === null),
                    'can_request_bill' => $canRequestBill,
                    'rate_url' => $rateUrl,
                    'customer_phone' => $o->customer?->phone,
                    'qr_image' => $rateUrl
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=6&data='.urlencode($rateUrl)
                        : null,
                ];
            }),
        ]);
    }

    /**
     * Customer asked for the bill — mark ready for cashier Pay Bills queue.
     */
    public function requestBill(Order $order)
    {
        if (! (bool) Setting::get('bring_bill_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Bring Bill is disabled in Settings'], 422);
        }

        if ($order->is_void) {
            return response()->json(['success' => false, 'message' => 'Order is voided'], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Order is already paid'], 422);
        }

        // Claim the bill for this waiter so rating/report attach correctly
        // Works with KOT confirmation ON or OFF — sends to POS Pay Bills queue
        $order->update([
            'bill_requested_at' => now(),
            'status' => 'served',
            'waiter_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bill sent to cashier — Pay Bills alert updated',
            'order_id' => $order->id,
            'stats' => $this->waiterStats(auth()->id()),
        ]);
    }

    /**
     * Kitchen tickets for this waiter's open floor orders (status tracking + serve).
     */
    public function kitchenOrders(Request $request)
    {
        $confirmation = (bool) Setting::get('kot_confirmation_enabled', true);
        if (! $confirmation) {
            return response()->json([
                'success' => true,
                'orders' => [],
                'ready_count' => 0,
                'preparing_count' => 0,
                'active_count' => 0,
                'confirmation_enabled' => false,
                'kitchen_display_enabled' => (bool) Setting::get('kitchen_display_enabled', true),
                'sound_enabled' => (bool) Setting::get('waiter_sound_alert', true),
                'message' => 'KOT confirmation is off — tickets print only',
            ]);
        }

        $waiterId = auth()->id();
        $status = $request->get('status'); // optional filter

        // Drop previous-day Ready leftovers (same end-of-day clear as POS)
        \App\Models\KitchenOrder::clearPreviousDayReady();

        $dayStart = \App\Models\CashRegister::businessDayStart();

        // All waiters see open kitchen tickets for unpaid floor orders (today's service day)
        $query = \App\Models\KitchenOrder::with(['order.table', 'items'])
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'unpaid')
                    ->where('is_void', false)
                    ->whereNull('completed_at');
            })
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->where('created_at', '>=', $dayStart)
            ->orderByRaw("FIELD(status, 'ready', 'preparing', 'pending')")
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'preparing', 'ready'], true)) {
            $query->where('status', $status);
        }

        $kots = $query->limit(40)->get()->map(function ($kot) use ($waiterId) {
            return [
                'id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'order_id' => $kot->order_id,
                'order_number' => $kot->order->order_number,
                'order_type' => $kot->order->order_type,
                'table_name' => $kot->order->table?->name,
                'status' => $kot->status,
                'is_mine' => (int) $kot->order->waiter_id === (int) $waiterId,
                'can_serve' => $kot->status === 'ready',
                'items' => $kot->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'quantity' => (float) $i->quantity,
                    'special_instructions' => $i->special_instructions,
                ]),
                'started_at' => $kot->started_at ? \App\Models\Setting::formatDateTime($kot->started_at, 'H:i') : null,
                'ready_at' => $kot->completed_at ? \App\Models\Setting::formatDateTime($kot->completed_at, 'H:i') : null,
                'elapsed' => $kot->created_at->diffForHumans(null, true),
            ];
        });

        $readyCount = $kots->where('status', 'ready')->count();
        $preparingCount = $kots->whereIn('status', ['pending', 'preparing'])->count();
        $activeCount = $readyCount + $preparingCount;

        return response()->json([
            'success' => true,
            'orders' => $kots,
            'ready_count' => $readyCount,
            'preparing_count' => $preparingCount,
            'active_count' => $activeCount,
            'confirmation_enabled' => true,
            'kitchen_display_enabled' => (bool) Setting::get('kitchen_display_enabled', true),
            'sound_enabled' => (bool) Setting::get('waiter_sound_alert', true),
        ]);
    }

    /**
     * Waiter confirms food has been given to the customer.
     */
    public function markKotServed(\App\Models\KitchenOrder $kitchenOrder)
    {
        $order = $kitchenOrder->order;
        if (!$order || $order->is_void || $order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Order unavailable'], 422);
        }

        if ($kitchenOrder->status !== 'ready') {
            return response()->json(['success' => false, 'message' => 'Only ready tickets can be served'], 422);
        }

        // Prefer assigned waiter; allow any waiter to serve and claim unassigned / help floor
        if ($order->waiter_id && (int) $order->waiter_id !== (int) auth()->id()) {
            // Still allow serve — floor help — but keep original waiter
        } elseif (! $order->waiter_id) {
            $order->update(['waiter_id' => auth()->id()]);
        }

        $kitchenOrder->update(['status' => 'served']);
        $kitchenOrder->items()->update(['status' => 'served', 'completed_at' => now()]);

        if (!$order->waiter_id) {
            $order->update(['waiter_id' => auth()->id()]);
        }

        $remaining = \App\Models\KitchenOrder::where('order_id', $order->id)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'served')
            ->exists();

        if (!$remaining) {
            $order->update(['status' => 'served']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked served — food given to customer',
            'kot_number' => $kitchenOrder->kot_number,
        ]);
    }

    public function rateOrder(Request $request, Order $order)
    {
        if (! (bool) Setting::get('waiter_rating_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Guest rating is disabled in Settings'], 422);
        }

        if ($order->is_void) {
            return response()->json(['success' => false, 'message' => 'Order is voided'], 422);
        }

        // Claim unassigned paid bills so rating attaches to this waiter
        if (! $order->waiter_id) {
            $order->update(['waiter_id' => auth()->id()]);
            $order->refresh();
        }

        if ((int) $order->waiter_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'This is not your order'], 403);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Rate after the order is completed / paid'], 422);
        }

        if ($order->waiterRating) {
            return response()->json(['success' => false, 'message' => 'Already rated'], 422);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $rating = (int) $data['rating'];
        $record = WaiterRating::create([
            'order_id' => $order->id,
            'waiter_id' => $order->waiter_id,
            'rating' => $rating,
            'emoji' => WaiterRating::emojiFor($rating),
            'rated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thanks for the rating!',
            'rating' => $record->rating,
            'emoji' => $record->emoji,
            'stats' => $this->waiterStats(auth()->id()),
        ]);
    }

    /**
     * Claim bill (if needed) and return guest rating QR / share link.
     */
    public function rateLink(Order $order)
    {
        if (! (bool) Setting::get('waiter_rating_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Guest rating is disabled in Settings'], 422);
        }

        if ($order->is_void || $order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Only paid bills can be rated'], 422);
        }

        if ($order->waiterRating) {
            return response()->json(['success' => false, 'message' => 'Already rated'], 422);
        }

        if (! $order->waiter_id) {
            $order->update(['waiter_id' => auth()->id()]);
            $order->refresh();
        } elseif ((int) $order->waiter_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'message' => 'This bill belongs to another waiter'], 403);
        }

        $url = \App\Http\Controllers\GuestRatingController::linkFor($order);

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'rate_url' => $url,
            'qr_image' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&data='.urlencode($url),
            'whatsapp_url' => 'https://wa.me/?text='.rawurlencode(
                'Thanks for dining with us! Please rate our service: '.$url
            ),
            'stats' => $this->waiterStats(auth()->id()),
        ]);
    }

    public function sendRateLink(Request $request, Order $order)
    {
        $data = $request->validate([
            'phone' => 'required|string|min:9|max:20',
            'channel' => 'nullable|in:whatsapp,sms,both',
        ]);

        $link = $this->rateLink($order);
        $payload = $link->getData(true);
        if (! ($payload['success'] ?? false)) {
            return $link;
        }

        $channel = $data['channel'] ?? 'whatsapp';
        $msg = 'Thanks for dining with us! Rate your service: '.$payload['rate_url'];
        $sent = false;
        $errors = [];

        if (in_array($channel, ['whatsapp', 'both'], true)) {
            if (\App\Services\NotificationService::whatsappConfigured()) {
                $sent = \App\Services\NotificationService::sendWhatsApp($data['phone'], $msg, 'rating:'.$order->id) || $sent;
            } else {
                $errors[] = 'WhatsApp not configured';
            }
        }

        if (in_array($channel, ['sms', 'both'], true)) {
            if (\App\Services\NotificationService::smsConfigured()) {
                $sent = \App\Services\NotificationService::sendSms($data['phone'], $msg, 'rating:'.$order->id) || $sent;
            } else {
                $errors[] = 'SMS not configured';
            }
        }

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'Rating link sent' : ('Could not send'.($errors ? ': '.implode(', ', $errors) : '')),
            'rate_url' => $payload['rate_url'],
            'whatsapp_url' => $payload['whatsapp_url'],
        ], $sent ? 200 : 422);
    }

    protected function waiterStats(int $waiterId): array
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $base = Order::query()->notVoid()->where('waiter_id', $waiterId);
        $openFloor = Order::query()->notVoid()->where('payment_status', 'unpaid');

        $openToday = (clone $openFloor)->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $completedToday = (clone $base)->where('payment_status', 'paid')
            ->where(function ($q) use ($todayStart, $todayEnd) {
                $q->whereBetween('completed_at', [$todayStart, $todayEnd])
                    ->orWhere(function ($q2) use ($todayStart, $todayEnd) {
                        $q2->whereNull('completed_at')->whereBetween('updated_at', [$todayStart, $todayEnd]);
                    });
            })
            ->count();
        $completedMonth = (clone $base)->where('payment_status', 'paid')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('completed_at', [$monthStart, $monthEnd])
                    ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                        $q2->whereNull('completed_at')->whereBetween('updated_at', [$monthStart, $monthEnd]);
                    });
            })
            ->count();

        $awaitingRating = 0;
        $ratePrompt = null;
        if ((bool) Setting::get('waiter_rating_enabled', true)) {
            $awaitingRating = Order::query()->notVoid()
                ->where('payment_status', 'paid')
                ->whereDoesntHave('waiterRating')
                ->where(function ($q) use ($waiterId) {
                    $q->where('waiter_id', $waiterId)->orWhereNull('waiter_id');
                })
                ->where(function ($q) {
                    $q->where('completed_at', '>=', now()->subDays(14))
                        ->orWhere(function ($q2) {
                            $q2->whereNull('completed_at')
                                ->where('updated_at', '>=', now()->subDays(14));
                        });
                })
                ->count();

            $ratePrompt = Order::with(['table:id,name'])
                ->notVoid()
                ->where(function ($q) use ($waiterId) {
                    $q->where('waiter_id', $waiterId)->orWhereNull('waiter_id');
                })
                ->where('payment_status', 'paid')
                ->whereDoesntHave('waiterRating')
                ->where(function ($q) {
                    $q->where('completed_at', '>=', now()->subDays(14))
                        ->orWhere(function ($q2) {
                            $q2->whereNull('completed_at')
                                ->where('updated_at', '>=', now()->subDays(14));
                        });
                })
                ->latest('completed_at')
                ->latest('id')
                ->first();
        }

        $billsReady = (clone $openFloor)
            ->whereNotNull('bill_requested_at')
            ->count();

        $ratings = WaiterRating::where('waiter_id', $waiterId);
        $ratingCount = (clone $ratings)->count();
        $avgRating = $ratingCount ? round((float) (clone $ratings)->avg('rating'), 1) : null;
        $ratingsToday = WaiterRating::where('waiter_id', $waiterId)
            ->whereBetween('rated_at', [$todayStart, $todayEnd])
            ->count();

        return [
            'open_today' => $openToday,
            'completed_today' => $completedToday,
            'completed_month' => $completedMonth,
            'awaiting_rating' => $awaitingRating,
            'bills_ready' => $billsReady,
            'ratings_count' => $ratingCount,
            'ratings_today' => $ratingsToday,
            'avg_rating' => $avgRating,
            'rate_prompt' => $ratePrompt ? [
                'id' => $ratePrompt->id,
                'order_number' => $ratePrompt->order_number,
                'table' => $ratePrompt->table?->name,
            ] : null,
        ];
    }

    public function index()
    {
        $settings = [
            'currency_symbol' => Setting::get('currency_symbol', 'LKR'),
            'print_ask_before' => (bool) Setting::get('print_ask_before', true),
            'auto_print_kot' => (bool) Setting::get('auto_print_kot', false),
            'tax_enabled' => (bool) Setting::get('tax_enabled', false),
            'tax_rate' => (float) Setting::get('tax_rate', 0),
            'service_charge_enabled' => (bool) Setting::get('service_charge_enabled', false),
            'service_charge_rate' => (float) Setting::get('service_charge_rate', 0),
        ];

        if (!$settings['tax_enabled']) {
            $settings['tax_rate'] = 0;
        }

        return view('waiter.index', [
            'waiter' => auth()->user(),
            'settings' => $settings,
        ]);
    }

    public function tables()
    {
        $pendingQrByTable = Order::awaitingQrApproval()
            ->selectRaw('table_id, COUNT(*) as c')
            ->whereNotNull('table_id')
            ->groupBy('table_id')
            ->pluck('c', 'table_id');

        $tables = RestaurantTable::with([
                'floor',
                'activeOrder' => fn ($q) => $q->withCount(['items as items_count' => fn ($iq) => $iq->where('is_void', false)]),
            ])
            ->active()
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'floor' => $t->floor?->name ?? 'Main',
                'status' => $t->activeOrder ? 'occupied' : 'available',
                'capacity' => $t->capacity,
                'qr_code' => $t->qr_code,
                'pending_qr' => (int) ($pendingQrByTable[$t->id] ?? 0),
                'active_order' => $t->activeOrder ? [
                    'id' => $t->activeOrder->id,
                    'order_number' => $t->activeOrder->order_number,
                    'total' => (float) $t->activeOrder->total_amount,
                    'items_count' => (int) ($t->activeOrder->items_count ?? 0),
                    'guest_count' => $t->activeOrder->guest_count ? (int) $t->activeOrder->guest_count : null,
                    'started_at' => $t->activeOrder->created_at?->toIso8601String(),
                ] : null,
            ]);

        return response()->json(['tables' => $tables]);
    }

    public function menu()
    {
        $showDirect = (bool) Setting::get('waiter_show_direct_items', true);

        $categories = Category::active()
            ->posVisible()
            ->when(!$showDirect, fn ($q) => $q->where('type', '!=', 'direct'))
            ->with(['products' => fn ($q) => $q->available()->posVisible()->with([
                'variants' => fn ($vq) => $vq->active()->orderBy('name'),
                'sharedAddons' => fn ($aq) => $aq->active()->ordered(),
                'addons' => fn ($aq) => $aq->active()->orderBy('name'),
                'addonGroups' => fn ($gq) => $gq->active()->ordered()->with(['addons' => fn ($aq) => $aq->active()->ordered()]),
                'optionSets' => fn ($oq) => $oq->active()->ordered()->with(['options' => fn ($opt) => $opt->active()->ordered()]),
            ])])
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type ?? 'kot',
                'products' => $c->products->map(function ($p) {
                    $basePrice = (float) ($p->final_price ?? $p->selling_price ?? $p->price ?? 0);
                    $variants = $p->variants->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'price_adjustment' => (float) $v->price_adjustment,
                        'final_price' => $basePrice + (float) $v->price_adjustment,
                    ])->values();
                    $resolvedAddons = $p->posAddons();
                    $addons = $resolvedAddons->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'price' => (float) $a->price,
                        'shared' => $a instanceof \App\Models\Addon,
                    ])->values();
                    $modifierSets = $p->posModifierSets()->map(fn ($set) => [
                        'id' => $set['id'],
                        'name' => $set['name'],
                        'display_name' => $set['display_name'],
                        'require_selection' => $set['require_selection'],
                        'allow_multiple' => $set['allow_multiple'],
                        'hide_on_receipt' => $set['hide_on_receipt'],
                        'addons' => $set['addons']->map(fn ($a) => [
                            'id' => $a->id,
                            'name' => $a->name,
                            'price' => (float) $a->price,
                            'shared' => true,
                            'group_id' => $set['id'],
                            'is_preselected' => (bool) ($a->pivot->is_preselected ?? false),
                            'hide_on_receipt' => $set['hide_on_receipt'],
                        ])->values(),
                    ])->values();
                    $optionSets = $p->posOptionSets()->map(fn ($set) => [
                        'id' => $set['id'],
                        'name' => $set['name'],
                        'display_name' => $set['display_name'],
                        'type' => $set['type'],
                        'require_selection' => $set['require_selection'],
                        'options' => $set['options']->map(fn ($o) => [
                            'id' => $o->id,
                            'name' => $o->name,
                            'color' => $o->color,
                            'option_set_id' => $set['id'],
                            'option_set_name' => $set['display_name'],
                        ])->values(),
                    ])->values();

                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => $basePrice,
                        'has_variants' => $variants->isNotEmpty() || (bool) $p->has_variants,
                        'has_addons' => $addons->isNotEmpty() || $optionSets->isNotEmpty() || (bool) $p->has_addons,
                        'variants' => $variants,
                        'addons' => $addons,
                        'modifier_sets' => $modifierSets,
                        'option_sets' => $optionSets,
                    ];
                }),
            ])
            ->filter(fn ($c) => count($c['products']) > 0)
            ->values();

        return response()->json([
            'categories' => $categories,
            'show_direct_items' => $showDirect,
        ]);
    }

    public function lookupCustomer(Request $request)
    {
        $phone = $this->normalizePhone($request->get('phone', ''));
        if (strlen($phone) < 7) {
            return response()->json(['success' => false, 'message' => 'Enter a valid phone number'], 422);
        }

        $customer = Customer::active()
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)
                    ->orWhere('phone', 'like', '%' . ltrim($phone, '0') . '%')
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . preg_replace('/\D+/', '', $phone) . '%']);
            })
            ->first();

        if ($customer) {
            return response()->json([
                'success' => true,
                'found' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => false,
            'phone' => $phone,
            'message' => 'New customer — ask for name',
        ]);
    }

    public function saveCustomer(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'name' => 'nullable|string|max:255',
        ]);

        $phone = $this->normalizePhone($data['phone']);
        if (strlen(preg_replace('/\D+/', '', $phone)) < 7) {
            return response()->json(['success' => false, 'message' => 'Enter a valid phone number'], 422);
        }

        $digits = preg_replace('/\D+/', '', $phone);
        $customer = Customer::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?", [$digits])
            ->first();

        if ($customer) {
            if (!empty($data['name']) && $customer->name !== $data['name']) {
                // keep existing name unless empty-ish
                if (!$customer->name || strcasecmp($customer->name, 'Walk-in') === 0) {
                    $customer->update(['name' => $data['name'], 'is_active' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'created' => false,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ],
            ]);
        }

        if (empty(trim($data['name'] ?? ''))) {
            return response()->json([
                'success' => false,
                'needs_name' => true,
                'message' => 'New number — please enter customer name',
            ], 422);
        }

        $customer = Customer::create([
            'name' => trim($data['name']),
            'phone' => $phone,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'created' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'message' => 'Customer saved',
        ]);
    }

    /**
     * Attach / update customer on an open bill using phone only (or phone+name).
     */
    public function attachCustomer(Request $request, Order $order)
    {
        if ($order->is_void || $order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot update this order'], 422);
        }

        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'name' => 'nullable|string|max:255',
        ]);

        $save = $this->saveCustomer(new Request($data));
        $payload = $save->getData(true);
        if (!($payload['success'] ?? false)) {
            return $save;
        }

        $order->update(['customer_id' => $payload['customer']['id']]);

        return response()->json([
            'success' => true,
            'message' => 'Customer linked to bill',
            'customer' => $payload['customer'],
            'order_id' => $order->id,
        ]);
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^\d+]/', '', $phone) ?: '';

        return $phone;
    }

    protected function resolveCustomerId(?int $customerId, ?string $phone, ?string $name): ?int
    {
        if ($customerId) {
            return Customer::where('id', $customerId)->value('id');
        }

        if (!$phone) {
            return null;
        }

        $response = $this->saveCustomer(new Request([
            'phone' => $phone,
            'name' => $name,
        ]));
        $payload = $response->getData(true);
        if (!($payload['success'] ?? false)) {
            return null;
        }

        return $payload['customer']['id'] ?? null;
    }

    public function placeOrder(Request $request, KitchenTicketService $tickets)
    {
        $data = $request->validate([
            'table_id' => 'required|integer|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric',
            'items.*.name' => 'nullable|string',
            'items.*.addons' => 'nullable|array',
            'items.*.options' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.variant_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_phone' => 'nullable|string|max:30',
            'customer_name' => 'nullable|string|max:255',
            'guest_count' => 'nullable|integer|min:1|max:99',
        ]);

        if (!empty($data['customer_phone']) && empty($data['customer_id'])) {
            $lookup = $this->saveCustomer(new Request([
                'phone' => $data['customer_phone'],
                'name' => $data['customer_name'] ?? null,
            ]));
            $payload = $lookup->getData(true);
            if (!($payload['success'] ?? false)) {
                return $lookup;
            }
            $data['customer_id'] = $payload['customer']['id'];
        }

        DB::beginTransaction();
        try {
            $table = RestaurantTable::findOrFail($data['table_id']);

            $order = Order::query()
                ->openBill()
                ->where('table_id', $table->id)
                ->where('order_type', 'dine_in')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $taxRate = (bool) Setting::get('tax_enabled', false) ? (float) Setting::get('tax_rate', 0) : 0;
            $serviceRate = (bool) Setting::get('service_charge_enabled', false)
                ? (float) Setting::get('service_charge_rate', 0)
                : 0;

            $isNew = false;
            if (!$order) {
                $isNew = true;
                $order = Order::create([
                    'order_number' => OrderNumberService::generate(
                        Setting::get('invoice_prefix', 'INV-'),
                        'orders',
                        (bool) Setting::get('invoice_reset_daily', true)
                    ),
                    'table_id' => $table->id,
                    'guest_count' => $data['guest_count'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'waiter_id' => auth()->id(),
                    'cashier_id' => null,
                    'branch_id' => \App\Services\BranchService::currentId(),
                    'order_type' => 'dine_in',
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'service_charge' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'order_notes' => $data['notes'] ?? null,
                ]);
            } else {
                $updates = [
                    'waiter_id' => auth()->id(),
                    'order_notes' => $data['notes'] ?? $order->order_notes,
                ];
                if (empty($order->branch_id) && \App\Services\BranchService::currentId()) {
                    $updates['branch_id'] = \App\Services\BranchService::currentId();
                }
                if (!empty($data['customer_id'])) {
                    $updates['customer_id'] = $data['customer_id'];
                }
                if (!empty($data['guest_count']) && !$order->guest_count) {
                    $updates['guest_count'] = $data['guest_count'];
                }
                $order->update($updates);
            }

            $newItems = collect();
            foreach ($data['items'] as $item) {
                $product = Product::with('category')->findOrFail($item['product_id']);
                $unitPrice = (float) ($item['price'] ?? $product->final_price ?? $product->selling_price);
                $lineTotal = ($unitPrice + collect($item['addons'] ?? [])->sum('price')) * $item['quantity'];
                $displayName = $item['name'] ?? $product->name;
                if (! empty($item['variant_name']) && ! str_contains($displayName, $item['variant_name'])) {
                    $displayName .= ' ('.$item['variant_name'].')';
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $displayName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'routed_to' => match ($product->category?->type ?? 'kot') {
                        'bot' => 'bar',
                        'direct' => 'direct',
                        default => 'kitchen',
                    },
                ]);

                foreach ($item['addons'] ?? [] as $addon) {
                    $addonId = isset($addon['id']) ? (int) $addon['id'] : null;
                    $useShared = array_key_exists('shared', $addon)
                        ? (bool) $addon['shared']
                        : ($addonId > 0 && \App\Models\Addon::whereKey($addonId)->exists());
                    OrderItemAddon::create([
                        'order_item_id' => $orderItem->id,
                        'product_addon_id' => $useShared ? null : $addonId,
                        'addon_id' => $useShared ? $addonId : null,
                        'addon_name' => $addon['name'] ?? 'Addon',
                        'price' => $addon['price'] ?? 0,
                        'hide_on_receipt' => ! empty($addon['hide_on_receipt']),
                    ]);
                }

                foreach ($item['options'] ?? [] as $opt) {
                    $optName = trim((string) ($opt['name'] ?? $opt['option_name'] ?? ''));
                    if ($optName === '') {
                        continue;
                    }
                    OrderItemOption::create([
                        'order_item_id' => $orderItem->id,
                        'option_set_id' => isset($opt['option_set_id']) ? (int) $opt['option_set_id'] : null,
                        'option_id' => isset($opt['id']) ? (int) $opt['id'] : (isset($opt['option_id']) ? (int) $opt['option_id'] : null),
                        'option_set_name' => trim((string) ($opt['option_set_name'] ?? $opt['set_name'] ?? 'Option')),
                        'option_name' => $optName,
                    ]);
                }

                $newItems->push($orderItem);
            }

            $order->refresh();
            $subtotal = $order->items()->where('is_void', false)->sum('total_price');
            $afterDiscount = max(0, $subtotal - (float) $order->discount_amount);
            $tax = $afterDiscount * ($taxRate / 100);
            $service = $afterDiscount * ($serviceRate / 100);
            $total = $afterDiscount + $tax + $service;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_charge' => $service,
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            $table->update(['status' => 'occupied']);

            $newItems = $order->items()->with(['product.category.kitchen'])->whereIn('id', $newItems->pluck('id'))->get();
            $printJobs = $tickets->createKitchenOrders($order, $newItems, [], ! $isNew);
            $tickets->deductStock($order, $newItems);

            DB::commit();

            $modified = ! $isNew;

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_order' => $isNew,
                'modified' => $modified,
                'new_total' => (float) $total,
                'print_jobs' => $printJobs,
                'message' => $isNew
                    ? 'Order sent to kitchen'
                    : 'Order modified — updated KOT sent to kitchen',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Waiter place order failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateOrder(Request $request, Order $order, KitchenTicketService $tickets)
    {
        $data = $request->validate([
            'table_id' => 'nullable|integer|exists:tables,id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.price' => 'required_with:items|numeric',
            'items.*.name' => 'nullable|string',
            'items.*.addons' => 'nullable|array',
            'items.*.options' => 'nullable|array',
            'items.*.special_instructions' => 'nullable|string',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.variant_name' => 'nullable|string',
            'existing_items' => 'nullable|array',
            'existing_items.*.id' => 'required_with:existing_items|integer',
            'existing_items.*.quantity' => 'required_with:existing_items|numeric|min:0.001',
            'existing_items.*.special_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_phone' => 'nullable|string|max:30',
            'customer_name' => 'nullable|string|max:255',
        ]);

        if ($order->is_void) {
            return response()->json(['success' => false, 'message' => 'Cannot edit a voided order'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot edit a paid bill'], 422);
        }

        $newLines = $data['items'] ?? [];
        $keepLines = collect($data['existing_items'] ?? [])->keyBy('id');

        if (! empty($data['customer_phone']) && empty($data['customer_id'])) {
            $lookup = $this->saveCustomer(new Request([
                'phone' => $data['customer_phone'],
                'name' => $data['customer_name'] ?? null,
            ]));
            $payload = $lookup->getData(true);
            if (! ($payload['success'] ?? false)) {
                return $lookup;
            }
            $data['customer_id'] = $payload['customer']['id'];
        }

        DB::beginTransaction();
        try {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->is_void || $order->payment_status === 'paid') {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'This order can no longer be edited'], 422);
            }

            $updates = [
                'waiter_id' => auth()->id(),
                'order_notes' => array_key_exists('notes', $data) ? ($data['notes'] ?? $order->order_notes) : $order->order_notes,
            ];
            if (! empty($data['customer_id'])) {
                $updates['customer_id'] = $data['customer_id'];
            }
            if (empty($order->branch_id) && \App\Services\BranchService::currentId()) {
                $updates['branch_id'] = \App\Services\BranchService::currentId();
            }
            $order->update($updates);

            $printJobs = [];
            $increaseOverrides = [];
            $increaseItems = collect();
            $restoreItems = collect();
            $noteChangedItems = collect();

            $currentItems = $order->items()->with(['addons', 'product.category.kitchen'])->where('is_void', false)->get();
            foreach ($currentItems as $item) {
                $keep = $keepLines->get($item->id) ?? $keepLines->get((string) $item->id);
                if (! $keep) {
                    $restore = $item->replicate();
                    $restore->id = $item->id;
                    $restore->setRelation('product', $item->product);
                    $restoreItems->push($restore);
                    $tickets->reduceKitchenQuantity($item, (float) $item->quantity);
                    $item->update([
                        'is_void' => true,
                        'void_reason' => 'Removed while editing order',
                        'total_price' => 0,
                    ]);
                    continue;
                }

                $newQty = (float) $keep['quantity'];
                $oldQty = (float) $item->quantity;
                $oldNote = $item->special_instructions;
                $newNote = array_key_exists('special_instructions', $keep)
                    ? ($keep['special_instructions'] ?: null)
                    : $item->special_instructions;
                $addonSum = (float) $item->addons->sum('price');
                $lineTotal = ((float) $item->unit_price + $addonSum) * $newQty;
                $noteChanged = (string) ($newNote ?? '') !== (string) ($oldNote ?? '');

                if (abs($newQty - $oldQty) > 0.0001 || $noteChanged) {
                    $item->update([
                        'quantity' => $newQty,
                        'total_price' => $lineTotal,
                        'special_instructions' => $newNote,
                    ]);
                }

                if ($newQty > $oldQty + 0.0001) {
                    $delta = $newQty - $oldQty;
                    $increaseOverrides[$item->id] = $delta;
                    $increaseItems->push($item);
                } elseif ($oldQty > $newQty + 0.0001) {
                    $delta = $oldQty - $newQty;
                    $restore = $item->replicate();
                    $restore->id = $item->id;
                    $restore->quantity = $delta;
                    $restore->setRelation('product', $item->product);
                    $restoreItems->push($restore);
                    $tickets->reduceKitchenQuantity($item, $delta);
                } elseif ($noteChanged && $newQty > 0) {
                    // Note-only change → reprint KOT line so kitchen sees the note
                    $noteChangedItems->push($item);
                }
            }

            $newItems = collect();
            foreach ($newLines as $item) {
                $product = Product::with('category')->findOrFail($item['product_id']);
                $unitPrice = (float) ($item['price'] ?? $product->final_price ?? $product->selling_price);
                $lineTotal = ($unitPrice + collect($item['addons'] ?? [])->sum('price')) * $item['quantity'];
                $displayName = $item['name'] ?? $product->name;
                if (! empty($item['variant_name']) && ! str_contains($displayName, $item['variant_name'])) {
                    $displayName .= ' ('.$item['variant_name'].')';
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $displayName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'routed_to' => match ($product->category?->type ?? 'kot') {
                        'bot' => 'bar',
                        'direct' => 'direct',
                        default => 'kitchen',
                    },
                ]);

                foreach ($item['addons'] ?? [] as $addon) {
                    $addonId = isset($addon['id']) ? (int) $addon['id'] : null;
                    $useShared = array_key_exists('shared', $addon)
                        ? (bool) $addon['shared']
                        : ($addonId > 0 && \App\Models\Addon::whereKey($addonId)->exists());
                    OrderItemAddon::create([
                        'order_item_id' => $orderItem->id,
                        'product_addon_id' => $useShared ? null : $addonId,
                        'addon_id' => $useShared ? $addonId : null,
                        'addon_name' => $addon['name'] ?? 'Addon',
                        'price' => $addon['price'] ?? 0,
                        'hide_on_receipt' => ! empty($addon['hide_on_receipt']),
                    ]);
                }

                foreach ($item['options'] ?? [] as $opt) {
                    $optName = trim((string) ($opt['name'] ?? $opt['option_name'] ?? ''));
                    if ($optName === '') {
                        continue;
                    }
                    OrderItemOption::create([
                        'order_item_id' => $orderItem->id,
                        'option_set_id' => isset($opt['option_set_id']) ? (int) $opt['option_set_id'] : null,
                        'option_id' => isset($opt['id']) ? (int) $opt['id'] : (isset($opt['option_id']) ? (int) $opt['option_id'] : null),
                        'option_set_name' => trim((string) ($opt['option_set_name'] ?? $opt['set_name'] ?? 'Option')),
                        'option_name' => $optName,
                    ]);
                }

                $newItems->push($orderItem);
            }

            if ($restoreItems->isNotEmpty()) {
                $tickets->restoreStock($order, $restoreItems);
            }

            $kotItems = collect();
            $qtyOverrides = $increaseOverrides;
            if ($increaseItems->isNotEmpty()) {
                $kotItems = $kotItems->concat($increaseItems);
            }
            if ($newItems->isNotEmpty()) {
                $kotItems = $kotItems->concat($newItems);
            }
            if ($noteChangedItems->isNotEmpty()) {
                $kotItems = $kotItems->concat($noteChangedItems);
            }

            if ($kotItems->isNotEmpty()) {
                $freshKot = $order->items()->with(['product.category.kitchen'])->whereIn('id', $kotItems->pluck('id')->unique())->get();
                $printJobs = $tickets->createKitchenOrders($order, $freshKot, $qtyOverrides, true);
                $stockItems = $freshKot->map(function ($item) use ($qtyOverrides) {
                    if (! array_key_exists($item->id, $qtyOverrides)) {
                        return $item;
                    }
                    $proxy = $item->replicate();
                    $proxy->id = $item->id;
                    $proxy->quantity = $qtyOverrides[$item->id];
                    $proxy->setRelation('product', $item->product);

                    return $proxy;
                });
                $tickets->deductStock($order, $stockItems);
            }

            $remaining = $order->items()->where('is_void', false)->count();
            if ($remaining === 0) {
                $tickets->cancelActiveTickets($order);
                $order->update([
                    'is_void' => true,
                    'status' => 'cancelled',
                    'void_reason' => 'All items removed by waiter',
                    'voided_by' => auth()->id(),
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'service_charge' => 0,
                    'total_amount' => 0,
                ]);
                RestaurantTable::syncOccupancy($order->table_id);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'cancelled' => true,
                    'order_id' => null,
                    'print_jobs' => [],
                    'message' => 'Order cancelled — all items removed',
                ]);
            }

            $this->recalcOrderTotals($order);
            if ($kotItems->isNotEmpty()) {
                $order->update(['status' => 'pending']);
            }

            DB::commit();

            $order->refresh();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_order' => false,
                'modified' => true,
                'new_total' => (float) $order->total_amount,
                'print_jobs' => $printJobs,
                'message' => count($printJobs)
                    ? 'Order modified by waiter — updated KOT sent to kitchen'
                    : 'Order updated',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Waiter update order failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function orderDetails(Order $order)
    {
        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Order is cancelled'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Order is already paid'], 422);
        }

        $order->load(['customer', 'table.floor', 'waiter']);
        $items = $order->items()
            ->with('addons')
            ->where('is_void', false)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'guest_count' => $order->guest_count,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->name,
                'customer_phone' => $order->customer?->phone,
                'table_id' => $order->table_id,
                'table_name' => $order->table?->name,
                'order_notes' => $order->order_notes,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'service_charge' => (float) $order->service_charge,
                'discount_amount' => (float) $order->discount_amount,
                'total_amount' => (float) $order->total_amount,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'name' => $item->product_name,
                        'quantity' => (float) $item->quantity,
                        'price' => (float) $item->unit_price,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'special_instructions' => $item->special_instructions,
                        'is_void' => (bool) $item->is_void,
                        'addons' => $item->addons->map(function ($addon) {
                            return [
                                'id' => $addon->product_addon_id ?? $addon->id,
                                'name' => $addon->addon_name,
                                'price' => (float) $addon->price,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }

    public function transferOrder(Request $request, Order $order)
    {
        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Order is cancelled'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot transfer a paid bill'], 422);
        }

        $validated = $request->validate([
            'table_id' => 'required|integer|exists:tables,id',
        ]);

        $oldTableId = $order->table_id;
        $newTableId = (int) $validated['table_id'];

        if ($oldTableId && (int) $oldTableId === $newTableId) {
            return response()->json([
                'success' => true,
                'message' => 'Already on this table',
                'order_number' => $order->order_number,
                'table_name' => $order->table?->name,
            ]);
        }

        $busy = Order::findOpenBillForTable($newTableId, $order->id);
        if ($busy) {
            return response()->json([
                'success' => false,
                'message' => 'Table already has open bill '.$busy->order_number,
                'existing_order_id' => $busy->id,
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($oldTableId) {
                $stillOpen = Order::findOpenBillForTable((int) $oldTableId, $order->id);
                if (! $stillOpen) {
                    RestaurantTable::where('id', $oldTableId)->update(['status' => 'available']);
                }
            }

            $order->update(['table_id' => $newTableId]);
            RestaurantTable::where('id', $newTableId)->update(['status' => 'occupied']);

            DB::commit();

            $newTable = RestaurantTable::find($newTableId);

            return response()->json([
                'success' => true,
                'message' => 'Moved to '.$newTable?->name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'table_id' => $newTableId,
                'table_name' => $newTable?->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Waiter transfer order failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request, Order $order, KitchenTicketService $tickets)
    {
        if ($order->is_void || $order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Already cancelled'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel a paid bill'], 422);
        }

        $data = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        DB::beginTransaction();
        try {
            $order->update([
                'is_void' => true,
                'void_type' => 'cancel',
                'void_reason' => trim($data['reason']),
                'voided_by' => auth()->id(),
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ]);

            $order->items()->where('is_void', false)->update([
                'is_void' => true,
                'void_reason' => 'Bill cancelled',
            ]);

            $tickets->cancelActiveTickets($order);
            RestaurantTable::syncOccupancy($order->table_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Waiter cancel order failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function pendingQrOrders()
    {
        $orders = Order::awaitingQrApproval()
            ->with(['table.floor', 'items'])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'table_id' => $o->table_id,
                'table' => $o->table?->name,
                'floor' => $o->table?->floor?->name,
                'qr_code' => $o->table?->qr_code,
                'total' => (float) $o->total_amount,
                'notes' => $o->order_notes,
                'created_at' => $o->created_at?->diffForHumans(null, true),
                'items' => $o->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'quantity' => (float) $i->quantity,
                ]),
            ]);

        return response()->json(['orders' => $orders, 'count' => $orders->count()]);
    }

    public function acceptQrOrder(Order $order, KitchenTicketService $tickets)
    {
        if (! $order->isAwaitingQrApproval()) {
            return response()->json(['success' => false, 'message' => 'Not a pending QR order'], 422);
        }

        DB::beginTransaction();
        try {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $order || ! $order->isAwaitingQrApproval()) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Already taken by another waiter'], 422);
            }

            $table = RestaurantTable::findOrFail($order->table_id);
            $qrItems = $order->items()->with(['product.category.kitchen'])->get();

            if ($qrItems->isEmpty()) {
                throw new \RuntimeException('Order has no items');
            }

            $open = Order::query()
                ->openBill()
                ->where('table_id', $table->id)
                ->where('order_type', 'dine_in')
                ->where('id', '!=', $order->id)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $target = $open ?: $order;
            $itemIdsForKot = $qrItems->pluck('id');

            if ($open) {
                foreach ($qrItems as $item) {
                    $item->update(['order_id' => $open->id]);
                }
                if ($order->order_notes) {
                    $open->update([
                        'order_notes' => trim(($open->order_notes ? $open->order_notes.' · ' : '').$order->order_notes),
                        'waiter_id' => auth()->id(),
                    ]);
                } else {
                    $open->update(['waiter_id' => auth()->id()]);
                }

                $open->refresh();
                $this->recalcOrderTotals($open);
                $order->update([
                    'status' => 'cancelled',
                    'approval_status' => 'approved',
                    'is_void' => true,
                    'void_reason' => 'Merged into '.$open->order_number.' after QR approval',
                    'voided_by' => auth()->id(),
                ]);
                $target = $open;
            } else {
                $order->update([
                    'status' => 'pending',
                    'approval_status' => 'approved',
                    'waiter_id' => auth()->id(),
                    // Cashier is who takes payment — set at settle / POS pay
                    'cashier_id' => null,
                ]);
                $this->recalcOrderTotals($order);
                $target = $order->fresh();
            }

            $table->update(['status' => 'occupied']);

            $kotItems = $target->items()->with(['product.category.kitchen'])->whereIn('id', $itemIdsForKot)->get();
            $printJobs = $tickets->createKitchenOrders($target, $kotItems);
            $tickets->deductStock($target, $kotItems);

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $target->id,
                'order_number' => $target->order_number,
                'print_jobs' => $printJobs,
                'message' => 'Accepted — sent to kitchen',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QR accept failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rejectQrOrder(Order $order)
    {
        if (! $order->isAwaitingQrApproval()) {
            return response()->json(['success' => false, 'message' => 'Not a pending QR order'], 422);
        }

        $order->update([
            'status' => 'cancelled',
            'approval_status' => 'rejected',
            'is_void' => true,
            'void_reason' => 'Rejected by waiter',
            'voided_by' => auth()->id(),
            'waiter_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Order rejected']);
    }

    protected function recalcOrderTotals(Order $order): void
    {
        $taxRate = (bool) Setting::get('tax_enabled', false) ? (float) Setting::get('tax_rate', 0) : 0;
        $serviceRate = (bool) Setting::get('service_charge_enabled', false)
            ? (float) Setting::get('service_charge_rate', 0)
            : 0;

        $subtotal = $order->items()->where('is_void', false)->sum('total_price');
        $afterDiscount = max(0, $subtotal - (float) $order->discount_amount);
        $tax = $afterDiscount * ($taxRate / 100);
        $service = $afterDiscount * ($serviceRate / 100);

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'service_charge' => $service,
            'total_amount' => $afterDiscount + $tax + $service,
        ]);
    }
}
