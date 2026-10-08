<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\DeliveryPartnerPayment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function postPayment(Payment $payment, ?float $ledgerAmount = null): ?AccountTransaction
    {
        if ($payment->status !== 'completed') {
            return null;
        }

        $method = $payment->method;
        if (in_array($method, ['credit', 'split'], true)) {
            return null;
        }

        $account = Account::forPaymentMethod($method);
        if (! $account) {
            return null;
        }

        $amount = round($ledgerAmount ?? (float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $orderNumber = $payment->order?->order_number;

        return $this->post(
            account: $account,
            direction: 'in',
            amount: $amount,
            description: 'Sale payment'.($orderNumber ? " — {$orderNumber}" : ''),
            reference: $payment->reference_number,
            paymentMethod: $method,
            source: $payment,
            transactedAt: $payment->created_at,
        );
    }

    /** Ledger outflow for a refund payment (negative or refunded status). */
    public function postRefund(Payment $payment, ?float $ledgerAmount = null): ?AccountTransaction
    {
        if (! in_array($payment->status, ['completed', 'refunded'], true)) {
            return null;
        }

        $method = $payment->method;
        if (in_array($method, ['credit', 'split'], true)) {
            return null;
        }

        $account = Account::forPaymentMethod($method);
        if (! $account) {
            return null;
        }

        $amount = abs(round($ledgerAmount ?? (float) $payment->amount, 2));
        if ($amount <= 0) {
            return null;
        }

        $orderNumber = $payment->order?->order_number;

        return $this->post(
            account: $account,
            direction: 'out',
            amount: $amount,
            description: 'Refund'.($orderNumber ? " — {$orderNumber}" : ''),
            reference: $payment->reference_number,
            paymentMethod: $method,
            source: $payment,
            transactedAt: $payment->created_at,
        );
    }

    public function postCashMovement(Account $account, string $type, float $amount, string $reason, ?Model $source = null): ?AccountTransaction
    {
        return $this->post(
            account: $account,
            direction: $type === 'in' ? 'in' : 'out',
            amount: $amount,
            description: 'Cash '.($type === 'in' ? 'in' : 'out').' — '.$reason,
            reference: null,
            paymentMethod: 'cash',
            source: $source,
        );
    }

    public function postExpense(Expense $expense): ?AccountTransaction
    {
        $account = $expense->account_id
            ? Account::find($expense->account_id)
            : Account::defaultCash();

        if (! $account) {
            return null;
        }

        return $this->post(
            account: $account,
            direction: 'out',
            amount: (float) $expense->amount,
            description: 'Expense — '.$expense->title,
            reference: $expense->category,
            paymentMethod: $account->type === 'bank' ? 'bank_transfer' : 'cash',
            source: $expense,
            transactedAt: $expense->expense_date?->startOfDay() ?? now(),
        );
    }

    public function reverseSource(Model $source): void
    {
        $rows = AccountTransaction::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->get();

        foreach ($rows as $row) {
            $this->deleteAndRecalc($row);
        }
    }

    public function postSupplierPayment(SupplierPayment $payment, ?Account $account = null): ?AccountTransaction
    {
        // When cheque management is on, pending cheques post only on clear
        if ($payment->method === 'cheque' && ChequeService::enabled() && ! $account) {
            $cheque = \App\Models\Cheque::where('supplier_payment_id', $payment->id)->first();
            if ($cheque && $cheque->status !== 'cleared') {
                return null;
            }
            if (! $cheque) {
                // Payment just created — ChequeService will create pending record; defer ledger
                return null;
            }
            $account = $cheque->account_id ? Account::find($cheque->account_id) : null;
        }

        $method = $payment->method === 'cheque' ? 'bank_transfer' : $payment->method;
        $account = $account
            ?? Account::forPaymentMethod($method)
            ?? ($method === 'cash' ? Account::defaultCash() : Account::defaultBank());

        if (! $account) {
            return null;
        }

        return $this->post(
            account: $account,
            direction: 'out',
            amount: (float) $payment->amount,
            description: 'Supplier payment'.($payment->purchase?->invoice_number ? ' — '.$payment->purchase->invoice_number : '')
                .($payment->method === 'cheque' ? ' (cheque cleared)' : ''),
            reference: $payment->reference_number,
            paymentMethod: $payment->method === 'cheque' ? 'cheque' : $method,
            source: $payment,
            transactedAt: $payment->payment_date?->startOfDay() ?? $payment->created_at,
        );
    }

    public function postDeliveryPartnerPayment(DeliveryPartnerPayment $payment, ?Account $account = null): ?AccountTransaction
    {
        $method = $payment->method ?: 'bank_transfer';
        $account = $account
            ?? ($payment->account_id ? Account::find($payment->account_id) : null)
            ?? Account::forPaymentMethod($method)
            ?? Account::defaultBank()
            ?? Account::defaultCash();

        if (! $account) {
            return null;
        }

        return $this->post(
            account: $account,
            direction: 'in',
            amount: (float) $payment->amount,
            description: 'Delivery partner payment — '.($payment->partner?->name ?? 'Partner'),
            reference: $payment->reference,
            paymentMethod: $method,
            source: $payment,
            transactedAt: $payment->payment_date?->startOfDay() ?? $payment->created_at,
        );
    }

    public function transfer(Account $from, Account $to, float $amount, ?string $description = null): array
    {
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same account.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }

        return DB::transaction(function () use ($from, $to, $amount, $description) {
            $ref = 'TXF-'.now()->format('YmdHis').'-'.random_int(100, 999);
            $out = $this->post(
                account: $from,
                direction: 'out',
                amount: $amount,
                description: $description ?: ('Transfer to '.$to->name),
                reference: $ref,
                useTransaction: false,
            );
            $in = $this->post(
                account: $to,
                direction: 'in',
                amount: $amount,
                description: $description ?: ('Transfer from '.$from->name),
                reference: $ref,
                useTransaction: false,
            );

            return ['out' => $out, 'in' => $in];
        });
    }

    public function postManual(Account $account, string $direction, float $amount, string $description, ?string $reference = null): ?AccountTransaction
    {
        return $this->post(
            account: $account,
            direction: $direction,
            amount: $amount,
            description: $description,
            reference: $reference,
        );
    }

    public function setOpeningBalance(Account $account, float $openingBalance): Account
    {
        return DB::transaction(function () use ($account, $openingBalance) {
            $account->opening_balance = round($openingBalance, 2);
            $account->save();
            $this->recalculateBalance($account);

            return $account->fresh();
        });
    }

    public function recalculateBalance(Account $account): float
    {
        $net = (float) $account->transactions()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END), 0) as net")
            ->value('net');

        $balance = round((float) $account->opening_balance + $net, 2);
        $account->update(['current_balance' => $balance]);

        // Refresh running balance_after chronologically
        $running = (float) $account->opening_balance;
        $account->transactions()->orderBy('transacted_at')->orderBy('id')->each(function (AccountTransaction $tx) use (&$running) {
            $running += $tx->direction === 'in' ? (float) $tx->amount : -(float) $tx->amount;
            if ((float) $tx->balance_after !== round($running, 2)) {
                $tx->update(['balance_after' => round($running, 2)]);
            }
        });

        return $balance;
    }

    protected function post(
        Account $account,
        string $direction,
        float $amount,
        ?string $description = null,
        ?string $reference = null,
        ?string $paymentMethod = null,
        ?Model $source = null,
        $transactedAt = null,
        bool $useTransaction = true
    ): ?AccountTransaction {
        $amount = round($amount, 2);
        if ($amount <= 0 || ! in_array($direction, ['in', 'out'], true)) {
            return null;
        }

        $runner = function () use ($account, $direction, $amount, $description, $reference, $paymentMethod, $source, $transactedAt) {
            if ($source) {
                $exists = AccountTransaction::query()
                    ->where('source_type', $source->getMorphClass())
                    ->where('source_id', $source->getKey())
                    ->where('account_id', $account->id)
                    ->exists();
                if ($exists) {
                    return AccountTransaction::query()
                        ->where('source_type', $source->getMorphClass())
                        ->where('source_id', $source->getKey())
                        ->where('account_id', $account->id)
                        ->first();
                }
            }

            $account = Account::query()->lockForUpdate()->findOrFail($account->id);
            $next = round(
                (float) $account->current_balance + ($direction === 'in' ? $amount : -$amount),
                2
            );

            $tx = AccountTransaction::create([
                'account_id' => $account->id,
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $next,
                'payment_method' => $paymentMethod,
                'description' => $description,
                'reference' => $reference,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'created_by' => auth()->id(),
                'transacted_at' => $transactedAt ?? now(),
            ]);

            $account->update(['current_balance' => $next]);

            return $tx;
        };

        return $useTransaction ? DB::transaction($runner) : $runner();
    }

    protected function deleteAndRecalc(AccountTransaction $tx): void
    {
        $accountId = $tx->account_id;
        $tx->delete();
        $account = Account::find($accountId);
        if ($account) {
            $this->recalculateBalance($account);
        }
    }
}
