<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DeliveryPartner;
use App\Models\DeliveryPartnerLedgerEntry;
use App\Models\DeliveryPartnerPayment;
use App\Models\DeliveryPartnerPaymentOrder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeliveryPartnerLedgerService
{
    public function __construct(protected AccountService $accounts) {}

    public function currentBalance(DeliveryPartner $partner): float
    {
        $last = DeliveryPartnerLedgerEntry::query()
            ->where('delivery_partner_id', $partner->id)
            ->latest('id')
            ->first();

        return $last ? (float) $last->balance_after : 0.0;
    }

    /**
     * Record that a delivery partner owes us for an order (COD / partner remittance).
     */
    public function recordOrderDue(Order $order): ?DeliveryPartnerLedgerEntry
    {
        if ($order->order_type !== 'delivery' || ! $order->delivery_partner_id) {
            return null;
        }

        $partner = DeliveryPartner::find($order->delivery_partner_id);
        if (! $partner || ! $partner->tracks_settlement) {
            return null;
        }

        // Own delivery — restaurant collects; no partner remittance due
        if ($partner->collectsAtRestaurant()) {
            $order->update([
                'partner_settlement_status' => 'none',
                'partner_due_amount' => 0,
                'delivery_partner_fee' => 0,
            ]);

            return null;
        }

        // Partner / app remit — always due (weekly settlement), even if POS shows unpaid
        // Skip only if restaurant already took prepaid payment without partner remit tracking
        if ($partner->settlesLater() === false && ! $order->payment_on_delivery && $order->payment_status === 'paid') {
            $order->update([
                'partner_settlement_status' => 'none',
                'partner_due_amount' => 0,
                'delivery_partner_fee' => $partner->calculateCommission((float) $order->total_amount),
            ]);

            return null;
        }

        $existing = DeliveryPartnerLedgerEntry::query()
            ->where('order_id', $order->id)
            ->where('type', 'due')
            ->exists();
        if ($existing) {
            return null;
        }

        $total = (float) $order->total_amount;
        $fee = (float) $partner->calculateCommission($total);
        $due = max(0, round($total - $fee, 2));

        $order->update([
            'delivery_partner_fee' => $fee,
            'partner_due_amount' => $due,
            'partner_settled_amount' => 0,
            'partner_settlement_status' => $due > 0 ? 'pending' : 'none',
        ]);

        if ($due <= 0) {
            return null;
        }

        return DB::transaction(function () use ($partner, $order, $due, $fee) {
            $balance = $this->currentBalance($partner) + $due;

            return DeliveryPartnerLedgerEntry::create([
                'delivery_partner_id' => $partner->id,
                'type' => 'due',
                'order_id' => $order->id,
                'debit' => $due,
                'credit' => 0,
                'balance_after' => $balance,
                'note' => 'Order '.$order->order_number.' due'
                    .($fee > 0 ? ' (fee '.number_format($fee, 2).')' : ''),
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Receive a settlement payment from a partner; FIFO allocate to pending orders; post to account.
     */
    public function receivePayment(DeliveryPartner $partner, array $data): DeliveryPartnerPayment
    {
        return DB::transaction(function () use ($partner, $data) {
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Amount must be positive.');
            }

            $account = ! empty($data['account_id'])
                ? Account::find($data['account_id'])
                : (Account::forPaymentMethod($data['method'] ?? 'bank_transfer')
                    ?? Account::defaultBank()
                    ?? Account::defaultCash());

            $payment = DeliveryPartnerPayment::create([
                'delivery_partner_id' => $partner->id,
                'account_id' => $account?->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'bank_transfer',
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $remaining = $amount;
            $pendingOrders = Order::query()
                ->where('delivery_partner_id', $partner->id)
                ->whereIn('partner_settlement_status', ['pending', 'partial'])
                ->orderBy('id')
                ->get();

            foreach ($pendingOrders as $order) {
                if ($remaining <= 0) {
                    break;
                }
                $owed = max(0, (float) $order->partner_due_amount - (float) $order->partner_settled_amount);
                if ($owed <= 0) {
                    continue;
                }
                $apply = min($remaining, $owed);
                DeliveryPartnerPaymentOrder::create([
                    'delivery_partner_payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $apply,
                ]);
                $settled = round((float) $order->partner_settled_amount + $apply, 2);
                $order->update([
                    'partner_settled_amount' => $settled,
                    'partner_settlement_status' => $settled + 0.009 >= (float) $order->partner_due_amount
                        ? 'settled'
                        : 'partial',
                ]);
                $remaining = round($remaining - $apply, 2);
            }

            $balance = $this->currentBalance($partner) - $amount;
            DeliveryPartnerLedgerEntry::create([
                'delivery_partner_id' => $partner->id,
                'type' => 'payment',
                'delivery_partner_payment_id' => $payment->id,
                'debit' => 0,
                'credit' => $amount,
                'balance_after' => $balance,
                'note' => 'Payment received'.($payment->reference ? ' · '.$payment->reference : ''),
                'created_by' => auth()->id(),
            ]);

            if ($account) {
                $this->accounts->postDeliveryPartnerPayment($payment, $account);
            }

            return $payment->load(['allocations.order', 'account', 'partner']);
        });
    }
}
