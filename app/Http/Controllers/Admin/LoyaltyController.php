<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(protected LoyaltyService $loyalty) {}

    protected function ensureEnabled(): void
    {
        if (! LoyaltyService::enabled()) {
            abort(403, 'Loyalty stamp cards are disabled. Ask the software owner to enable them in Settings.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();

        $query = Customer::query()->loyaltyJoined()->latest('loyalty_joined_at');
        if ($request->filled('q')) {
            $q = '%'.$request->q.'%';
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', $q)->orWhere('phone', 'like', $q);
            });
        }

        $members = $query->paginate(20)->withQueryString();
        $config = LoyaltyService::config();
        $currency = Setting::get('currency_symbol', 'LKR');
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.loyalty.index', compact('members', 'config', 'currency', 'categories'));
    }

    public function enroll(Request $request, Customer $customer)
    {
        $this->ensureEnabled();
        $customer = $this->loyalty->enroll($customer);

        return back()->with('success', $customer->name.' joined the loyalty stamp card.');
    }

    public function show(Customer $customer)
    {
        $this->ensureEnabled();
        if (! $customer->loyalty_joined) {
            return redirect()->route('loyalty.index')->with('error', 'Customer is not enrolled.');
        }
        $customer->load(['loyaltyLogs' => fn ($q) => $q->latest()->limit(30)]);
        $payload = $this->loyalty->customerPayload($customer);
        $cardUrl = $this->loyalty->cardUrl($customer);
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=10&data='.urlencode($this->loyalty->qrPayload($customer));

        return view('admin.loyalty.show', compact('customer', 'payload', 'cardUrl', 'qrImage'));
    }

    public function saveConfig(Request $request)
    {
        $this->ensureEnabled();
        if (! auth()->user()?->isSoftwareOwner() && ! auth()->user()?->can('settings.system')) {
            abort(403);
        }

        $data = $request->validate([
            'loyalty_stamps_required' => 'required|integer|min:1|max:50',
            'loyalty_reward_label' => 'required|string|max:100',
            'loyalty_card_expiry_days' => 'nullable|integer|min:0|max:3650',
            'loyalty_category_ids' => 'nullable|array',
            'loyalty_category_ids.*' => 'integer|exists:categories,id',
        ]);

        Setting::set('loyalty_stamps_required', (string) $data['loyalty_stamps_required'], 'loyalty', null, 'integer');
        Setting::set('loyalty_reward_label', $data['loyalty_reward_label'], 'loyalty', null, 'string');
        Setting::set('loyalty_card_expiry_days', (string) ($data['loyalty_card_expiry_days'] ?? 365), 'loyalty', null, 'integer');
        Setting::set('loyalty_category_ids', json_encode(array_values($data['loyalty_category_ids'] ?? [])), 'loyalty', null, 'string');

        return back()->with('success', 'Loyalty stamp card settings saved.');
    }
}
