<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\WaiterRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class GuestRatingController extends Controller
{
    public function show(Request $request, Order $order)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This rating link is invalid or expired.');
        }

        if ($order->is_void || $order->payment_status !== 'paid') {
            return view('guest.rate-done', [
                'companyName' => Setting::get('company_name', 'Restaurant'),
                'message' => 'This bill is not available for rating.',
                'done' => false,
            ]);
        }

        if ($order->waiterRating) {
            return view('guest.rate-done', [
                'companyName' => Setting::get('company_name', 'Restaurant'),
                'message' => 'Thanks — this bill was already rated.',
                'emoji' => $order->waiterRating->emoji,
                'done' => true,
            ]);
        }

        $order->loadMissing(['table', 'waiter']);

        return view('guest.rate', [
            'order' => $order,
            'companyName' => Setting::get('company_name', 'Restaurant'),
            'emojis' => WaiterRating::EMOJIS,
            'labels' => WaiterRating::LABELS,
            'submitUrl' => URL::temporarySignedRoute('guest.rate.submit', now()->addDays(7), ['order' => $order->id]),
        ]);
    }

    public function store(Request $request, Order $order)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This rating link is invalid or expired.');
        }

        if ($order->is_void || $order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Bill not available'], 422);
        }

        if ($order->waiterRating) {
            return response()->json(['success' => false, 'message' => 'Already rated'], 422);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (! $order->waiter_id) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter not assigned yet — ask staff to open Rate on their tablet first.',
            ], 422);
        }

        $rating = (int) $data['rating'];
        $record = WaiterRating::create([
            'order_id' => $order->id,
            'waiter_id' => $order->waiter_id,
            'rating' => $rating,
            'emoji' => WaiterRating::emojiFor($rating),
            'rated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thanks for your feedback!',
                'emoji' => $record->emoji,
            ]);
        }

        return view('guest.rate-done', [
            'companyName' => Setting::get('company_name', 'Restaurant'),
            'message' => 'Thanks for your feedback!',
            'emoji' => $record->emoji,
            'done' => true,
        ]);
    }

    public static function linkFor(Order $order, int $days = 7): string
    {
        return URL::temporarySignedRoute('guest.rate', now()->addDays($days), ['order' => $order->id]);
    }
}
