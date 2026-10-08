<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InboxNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = $user->notifications()->latest()->limit(30)->get()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notification',
            'preview' => $n->data['preview'] ?? ($n->data['message'] ?? ''),
            'kind' => $n->data['kind'] ?? 'general',
            'icon' => $n->data['icon'] ?? 'fa-bell',
            'image' => $n->data['image'] ?? null,
            'url' => $n->data['url'] ?? route('notifications.index'),
            'read' => $n->read_at !== null,
            'urgent' => (bool) ($n->data['urgent'] ?? false),
            'created_at' => $n->created_at?->diffForHumans(),
            'created_at_full' => $n->created_at?->format('d M Y H:i'),
            'dismissible' => true,
        ]);

        $expiryItems = $this->expiryAlerts();
        $merged = $expiryItems->concat($items)->take(40)->values();
        $unread = $user->unreadNotifications()->count() + $expiryItems->where('urgent', true)->count();

        return response()->json([
            'unread' => $unread,
            'notifications' => $merged,
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        if (str_starts_with($id, 'expiry-')) {
            return response()->json([
                'success' => true,
                'unread' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['success' => true, 'unread' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'unread' => 0]);
    }

    /** Live expiry reminders for the header bell (products + ingredients). */
    protected function expiryAlerts()
    {
        $remindDays = max(0, (int) Setting::get('expiry_remind_days', 7));
        $until = Carbon::today()->addDays($remindDays)->endOfDay();
        $today = Carbon::today();

        $products = Product::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $until)
            ->orderBy('expiry_date')
            ->limit(15)
            ->get(['id', 'name', 'image', 'stock_quantity', 'expiry_date']);

        $ingredients = Ingredient::query()
            ->active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $until)
            ->orderBy('expiry_date')
            ->limit(15)
            ->get(['id', 'name', 'stock_quantity', 'unit', 'expiry_date']);

        $mapProduct = $products->map(function ($p) use ($today) {
            $daysLeft = (int) $today->diffInDays($p->expiry_date->copy()->startOfDay(), false);
            $badge = $daysLeft < 0 ? 'Expired' : ($daysLeft === 0 ? 'Expires today' : $daysLeft.' day'.($daysLeft === 1 ? '' : 's').' left');
            $urgent = $daysLeft <= 1;
            $qty = number_format((float) $p->stock_quantity, fmod((float) $p->stock_quantity, 1.0) == 0.0 ? 0 : 3);

            return [
                'id' => 'expiry-product-'.$p->id,
                'title' => $urgent ? 'Expiry alert: '.$p->name : 'Expiring soon: '.$p->name,
                'preview' => 'Product · '.$p->expiry_date->format('d M Y').' · '.$badge.' · '.$qty.' qty left',
                'kind' => 'expiry',
                'icon' => 'fa-hourglass-half',
                'image' => $p->imageUrl(),
                'url' => route('products.edit', $p),
                'read' => ! $urgent,
                'urgent' => $urgent,
                'created_at' => $badge.' · '.$qty.' left',
                'created_at_full' => $p->expiry_date->format('d M Y'),
                'dismissible' => false,
            ];
        });

        $mapIngredient = $ingredients->map(function ($i) use ($today) {
            $daysLeft = (int) $today->diffInDays($i->expiry_date->copy()->startOfDay(), false);
            $badge = $daysLeft < 0 ? 'Expired' : ($daysLeft === 0 ? 'Expires today' : $daysLeft.' day'.($daysLeft === 1 ? '' : 's').' left');
            $urgent = $daysLeft <= 1;
            $qty = number_format((float) $i->stock_quantity, fmod((float) $i->stock_quantity, 1.0) == 0.0 ? 0 : 3);
            $unit = $i->unit ?: 'qty';

            return [
                'id' => 'expiry-ingredient-'.$i->id,
                'title' => $urgent ? 'Expiry alert: '.$i->name : 'Expiring soon: '.$i->name,
                'preview' => 'Ingredient · '.$i->expiry_date->format('d M Y').' · '.$badge.' · '.$qty.' '.$unit.' left',
                'kind' => 'expiry',
                'icon' => 'fa-hourglass-half',
                'image' => '/images/product-placeholder.svg',
                'url' => route('ingredients.edit', $i),
                'read' => ! $urgent,
                'urgent' => $urgent,
                'created_at' => $badge.' · '.$qty.' '.$unit,
                'created_at_full' => $i->expiry_date->format('d M Y'),
                'dismissible' => false,
            ];
        });

        return $mapProduct
            ->concat($mapIngredient)
            ->sortBy(fn ($row) => $row['urgent'] ? 0 : 1)
            ->values();
    }
}
