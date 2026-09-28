<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PromoCampaign;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoCampaignController extends Controller
{
    public function index()
    {
        $campaigns = PromoCampaign::with('creator')
            ->latest()
            ->paginate(20);

        return view('admin.promos.index', [
            'campaigns' => $campaigns,
            'whatsappReady' => NotificationService::whatsappConfigured(),
            'smsReady' => NotificationService::smsConfigured(),
        ]);
    }

    public function create()
    {
        $base = Customer::active()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $customers = (clone $base)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'loyalty_joined']);

        $loyaltyCount = (clone $base)->where('loyalty_joined', true)->count();
        $notLoyaltyCount = (clone $base)->where(function ($q) {
            $q->where('loyalty_joined', false)->orWhereNull('loyalty_joined');
        })->count();

        return view('admin.promos.create', [
            'customers' => $customers,
            'loyaltyCount' => $loyaltyCount,
            'notLoyaltyCount' => $notLoyaltyCount,
            'loyaltyEnabled' => \App\Services\LoyaltyService::enabled(),
            'whatsappReady' => NotificationService::whatsappConfigured(),
            'smsReady' => NotificationService::smsConfigured(),
            'companyName' => Setting::get('company_name', 'ResPOS'),
            'rewardLabel' => \App\Services\LoyaltyService::rewardLabel(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:1000',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:whatsapp,sms',
            'audience' => 'required|in:all,selected,loyalty,not_loyalty',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer|exists:customers,id',
        ]);

        $channels = array_values(array_unique($validated['channels']));

        if (in_array('whatsapp', $channels, true) && !NotificationService::whatsappConfigured()) {
            return back()->withInput()->with('error', 'WhatsApp is not configured. Enable it and add Meta Access Token + Phone Number ID in Settings → Notifications.');
        }

        if (in_array('sms', $channels, true) && !NotificationService::smsConfigured()) {
            return back()->withInput()->with('error', 'SMS is not configured. Enable SMS and add Notify.lk credentials in Settings → Notifications.');
        }

        if ($validated['audience'] === 'selected' && empty($validated['customer_ids'])) {
            return back()->withInput()->with('error', 'Select at least one customer.');
        }

        $recipients = $this->resolveRecipients(
            $validated['audience'],
            $validated['customer_ids'] ?? []
        );

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'No customers with phone numbers found for this audience.');
        }

        $campaign = PromoCampaign::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'channels' => $channels,
            'audience' => $validated['audience'],
            'customer_ids' => $validated['audience'] === 'selected' ? $validated['customer_ids'] : null,
            'status' => 'sending',
            'recipient_count' => $recipients->count(),
            'created_by' => auth()->id(),
        ]);

        $sent = 0;
        $failed = 0;
        $company = Setting::get('company_name', 'ResPOS');
        $body = trim($validated['message']);
        if (!str_contains($body, $company)) {
            $body .= "\n\n— " . $company;
        }

        foreach ($recipients as $customer) {
            $okAny = false;

            if (in_array('whatsapp', $channels, true)) {
                if (NotificationService::sendWhatsApp($customer->phone, $body, 'promo:' . $campaign->id)) {
                    $okAny = true;
                }
            }

            if (in_array('sms', $channels, true)) {
                if (NotificationService::sendSms($customer->phone, $body, 'promo:' . $campaign->id)) {
                    $okAny = true;
                }
            }

            if ($okAny) {
                $sent++;
            } else {
                $failed++;
            }

            // Light pacing for WhatsApp API rate limits
            usleep(150000);
        }

        $campaign->update([
            'status' => $failed === $recipients->count() ? 'failed' : 'sent',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        $msg = "Promo sent to {$sent} of {$recipients->count()} customers.";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check notification logs / API keys.";
        }

        return redirect()
            ->route('admin.promos.index')
            ->with($failed && !$sent ? 'error' : 'success', $msg);
    }

    public function show(PromoCampaign $promo)
    {
        return view('admin.promos.show', [
            'campaign' => $promo->load('creator'),
            'logs' => DB::table('notification_logs')
                ->where('reference_type', 'promo:' . $promo->id)
                ->latest()
                ->limit(200)
                ->get(),
        ]);
    }

    protected function resolveRecipients(string $audience, array $customerIds)
    {
        $query = Customer::active()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if ($audience === 'selected') {
            $query->whereIn('id', $customerIds);
        } elseif ($audience === 'loyalty') {
            $query->where('loyalty_joined', true);
        } elseif ($audience === 'not_loyalty') {
            $query->where(function ($q) {
                $q->where('loyalty_joined', false)->orWhereNull('loyalty_joined');
            });
        }

        return $query->get(['id', 'name', 'phone']);
    }
}
