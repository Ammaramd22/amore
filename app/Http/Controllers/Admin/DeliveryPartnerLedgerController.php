<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DeliveryPartner;
use App\Models\DeliveryPartnerLedgerEntry;
use App\Models\DeliveryPartnerPayment;
use App\Models\Order;
use App\Models\Setting;
use App\Services\DeliveryPartnerLedgerService;
use Illuminate\Http\Request;

class DeliveryPartnerLedgerController extends Controller
{
    public function __construct(protected DeliveryPartnerLedgerService $ledger) {}

    public function index(Request $request)
    {
        $currency = Setting::get('currency_symbol', 'LKR');
        $partners = DeliveryPartner::query()->orderBy('name')->get();
        $selectedId = $request->get('partner_id');
        $partner = $selectedId ? DeliveryPartner::find($selectedId) : $partners->first();

        $balances = [];
        foreach ($partners as $p) {
            $balances[$p->id] = $this->ledger->currentBalance($p);
        }

        $entries = collect();
        $pendingOrders = collect();
        $payments = collect();
        if ($partner) {
            $entries = DeliveryPartnerLedgerEntry::query()
                ->with(['order:id,order_number,total_amount', 'payment'])
                ->where('delivery_partner_id', $partner->id)
                ->latest('id')
                ->paginate(30)
                ->withQueryString();

            $pendingOrders = Order::query()
                ->where('delivery_partner_id', $partner->id)
                ->whereIn('partner_settlement_status', ['pending', 'partial'])
                ->latest('id')
                ->limit(50)
                ->get();

            $payments = DeliveryPartnerPayment::query()
                ->with('account')
                ->where('delivery_partner_id', $partner->id)
                ->latest('payment_date')
                ->limit(20)
                ->get();
        }

        $accounts = Account::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.delivery-partners.ledger', compact(
            'partners', 'partner', 'balances', 'entries', 'pendingOrders', 'payments', 'accounts', 'currency'
        ));
    }

    public function storePayment(Request $request, DeliveryPartner $deliveryPartner)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'nullable|exists:accounts,id',
            'method' => 'required|in:cash,bank_transfer,online,cheque',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->ledger->receivePayment($deliveryPartner, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('delivery-partners.ledger', ['partner_id' => $deliveryPartner->id])
            ->with('success', 'Payment received and posted to ledger.');
    }
}
