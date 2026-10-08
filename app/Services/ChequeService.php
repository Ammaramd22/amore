<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class ChequeService
{
    public function __construct(protected AccountService $accounts) {}

    public static function enabled(): bool
    {
        return Cheque::managementEnabled();
    }

    public function createFromPayment(SupplierPayment $payment, array $chequeData): ?Cheque
    {
        if ($payment->method !== 'cheque' || ! self::enabled()) {
            return null;
        }

        $accountId = $chequeData['account_id'] ?? Account::defaultBank()?->id;

        return Cheque::create([
            'supplier_id' => $payment->supplier_id,
            'purchase_id' => $payment->purchase_id,
            'supplier_payment_id' => $payment->id,
            'account_id' => $accountId,
            'cheque_number' => $chequeData['cheque_number'] ?? $payment->reference_number ?? ('CHQ-'.$payment->id),
            'bank_name' => $chequeData['bank_name'] ?? null,
            'branch_name' => $chequeData['branch_name'] ?? null,
            'amount' => $payment->amount,
            'cheque_date' => $chequeData['cheque_date'] ?? $payment->payment_date,
            'due_date' => $chequeData['due_date'] ?? $payment->payment_date ?? today(),
            'status' => 'pending',
            'notes' => $chequeData['notes'] ?? $payment->notes,
            'created_by' => $payment->created_by ?? auth()->id(),
        ]);
    }

    public function clear(Cheque $cheque, ?int $accountId = null): Cheque
    {
        if ($cheque->status !== 'pending') {
            throw new \RuntimeException('Only pending cheques can be cleared.');
        }

        return DB::transaction(function () use ($cheque, $accountId) {
            if ($accountId) {
                $cheque->account_id = $accountId;
            }
            if (! $cheque->account_id) {
                $cheque->account_id = Account::defaultBank()?->id;
            }

            $cheque->status = 'cleared';
            $cheque->cleared_at = now();
            $cheque->returned_reason = null;
            $cheque->returned_at = null;
            $cheque->save();

            if ($cheque->payment) {
                $this->accounts->postSupplierPayment(
                    $cheque->payment->loadMissing('purchase'),
                    $cheque->account_id ? Account::find($cheque->account_id) : null
                );
            } else {
                $account = Account::find($cheque->account_id) ?? Account::defaultBank();
                if ($account) {
                    $this->accounts->postManual(
                        $account,
                        'out',
                        (float) $cheque->amount,
                        'Cheque cleared — '.$cheque->cheque_number.($cheque->supplier ? ' ('.$cheque->supplier->name.')' : ''),
                        $cheque->cheque_number
                    );
                }
            }

            return $cheque->fresh(['supplier', 'purchase', 'account', 'payment']);
        });
    }

    public function returnCheque(Cheque $cheque, ?string $reason = null): Cheque
    {
        if (! in_array($cheque->status, ['pending', 'cleared'], true)) {
            throw new \RuntimeException('Only pending or cleared cheques can be returned.');
        }

        return DB::transaction(function () use ($cheque, $reason) {
            $wasCleared = $cheque->status === 'cleared';

            if ($wasCleared && $cheque->payment) {
                $this->accounts->reverseSource($cheque->payment);
            }

            if ($cheque->payment && $cheque->purchase_id) {
                $this->rollbackPurchasePayment($cheque->payment);
            }

            $cheque->status = 'returned';
            $cheque->returned_at = now();
            $cheque->returned_reason = $reason;
            $cheque->save();

            return $cheque->fresh(['supplier', 'purchase', 'account', 'payment']);
        });
    }

    public function cancel(Cheque $cheque): Cheque
    {
        if ($cheque->status !== 'pending') {
            throw new \RuntimeException('Only pending cheques can be cancelled.');
        }

        return DB::transaction(function () use ($cheque) {
            if ($cheque->payment && $cheque->purchase_id) {
                $this->rollbackPurchasePayment($cheque->payment);
            }

            $cheque->status = 'cancelled';
            $cheque->save();

            return $cheque->fresh();
        });
    }

    protected function rollbackPurchasePayment(SupplierPayment $payment): void
    {
        $purchase = Purchase::find($payment->purchase_id);
        if (! $purchase) {
            return;
        }

        $newPaid = max(0, (float) $purchase->paid_amount - (float) $payment->amount);
        $status = 'unpaid';
        if ($newPaid >= (float) $purchase->total_amount && $newPaid > 0) {
            $status = 'paid';
        } elseif ($newPaid > 0) {
            $status = 'partial';
        }

        $purchase->update([
            'paid_amount' => $newPaid,
            'payment_status' => $status,
        ]);
    }

    public static function processDueReminders(): array
    {
        if (! self::enabled() || ! Setting::get('cheque_reminder_enabled', false)) {
            return ['skipped' => true, 'reason' => 'disabled'];
        }

        $daysBefore = (int) Setting::get('cheque_reminder_days_before', 3);
        $channels = array_filter(array_map('trim', explode(',', (string) Setting::get('cheque_reminder_channels', 'inapp,email'))));
        $email = (string) Setting::get('cheque_reminder_email', Setting::get('company_email', ''));
        $phone = (string) Setting::get('cheque_reminder_phone', Setting::get('company_phone', ''));
        $currency = Setting::get('currency_symbol', 'LKR');
        $today = now()->toDateString();

        $cheques = Cheque::with('supplier')
            ->pending()
            ->whereDate('due_date', '<=', now()->addDays(max(0, $daysBefore))->toDateString())
            ->where(function ($q) use ($today) {
                $q->whereNull('last_reminded_at')
                    ->orWhereDate('last_reminded_at', '<', $today);
            })
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        if ($cheques->isEmpty()) {
            return ['skipped' => true, 'reason' => 'none_due'];
        }

        $sent = [];
        foreach ($cheques as $cheque) {
            $overdue = $cheque->due_date->lt(now()->startOfDay());
            $subject = ($overdue ? 'Overdue cheque' : 'Cheque due reminder').' — '.$cheque->cheque_number;
            $message = sprintf(
                "%s cheque %s for %s %s (supplier: %s) is %s on %s.",
                $overdue ? 'OVERDUE' : 'Pending',
                $cheque->cheque_number,
                $currency,
                number_format((float) $cheque->amount, 2),
                $cheque->supplier?->name ?? '—',
                $overdue ? 'overdue' : 'due',
                $cheque->due_date->format('d M Y')
            );

            $results = NotificationService::sendReminder(
                kind: 'cheque_due',
                message: $message,
                channels: $channels ?: ['inapp'],
                email: $email ?: null,
                phone: $phone ?: null,
                subject: $subject,
            );

            $cheque->update(['last_reminded_at' => $today]);
            $sent[] = ['id' => $cheque->id, 'cheque_number' => $cheque->cheque_number, 'results' => $results];
        }

        return ['skipped' => false, 'count' => count($sent), 'sent' => $sent];
    }
}
