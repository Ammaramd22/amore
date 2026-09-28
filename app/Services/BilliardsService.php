<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BilliardBooking;
use App\Models\BilliardPayment;
use App\Models\BilliardTable;
use App\Models\Customer;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BilliardsService
{
    public static function enabled(): bool
    {
        return BilliardBooking::moduleEnabled();
    }

    public static function endAlertMinutes(): int
    {
        return max(1, (int) Setting::get('billiards_end_alert_minutes', 10));
    }

    public static function calcAmount(float $hours, float $hourlyRate): float
    {
        return round(max(0, $hours) * max(0, $hourlyRate), 2);
    }

    public function resolveCustomer(?int $customerId, ?string $name, ?string $phone, bool $createIfMissing = true): ?Customer
    {
        if ($customerId) {
            return Customer::query()->find($customerId);
        }

        $phoneDigits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        $name = trim((string) $name);

        if ($phoneDigits !== '') {
            $existing = Customer::query()
                ->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ['%'.substr($phoneDigits, -9).'%'])
                ->first();
            if ($existing) {
                if ($name !== '' && blank($existing->name)) {
                    $existing->update(['name' => $name]);
                }

                return $existing;
            }
        }

        if (! $createIfMissing || ($name === '' && $phoneDigits === '')) {
            return null;
        }

        return Customer::create([
            'name' => $name !== '' ? $name : 'Billiards guest',
            'phone' => $phone ?: null,
            'is_active' => true,
        ]);
    }

    public function createBooking(array $data, ?int $userId = null): BilliardBooking
    {
        $table = BilliardTable::query()->findOrFail($data['billiard_table_id']);
        $hours = max(0.25, (float) ($data['hours'] ?? 1));
        $start = Carbon::parse($data['scheduled_start']);
        $end = isset($data['scheduled_end'])
            ? Carbon::parse($data['scheduled_end'])
            : $start->copy()->addMinutes((int) round($hours * 60));

        if (! $table->isAvailableBetween($start, $end)) {
            throw new \RuntimeException('This table is already booked for the selected time.');
        }

        $rate = isset($data['hourly_rate'])
            ? (float) $data['hourly_rate']
            : (float) $table->hourly_rate;

        $customer = $this->resolveCustomer(
            isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            (bool) ($data['create_customer'] ?? true)
        );

        $number = OrderNumberService::generate('BIL-', 'billiard_bookings', true, null, 'booking_number');

        return BilliardBooking::create([
            'booking_number' => $number,
            'billiard_table_id' => $table->id,
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?? ($data['customer_name'] ?? null),
            'customer_phone' => $customer?->phone ?? ($data['customer_phone'] ?? null),
            'source' => $data['source'] ?? 'walk_in',
            'status' => $data['status'] ?? 'booked',
            'payment_status' => 'unpaid',
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'hours' => $hours,
            'hourly_rate' => $rate,
            'amount' => self::calcAmount($hours, $rate),
            'paid_amount' => 0,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function startSession(BilliardBooking $booking): BilliardBooking
    {
        if (in_array($booking->status, ['cancelled', 'completed', 'no_show'], true)) {
            throw new \RuntimeException('Cannot start this booking.');
        }

        $booking->update([
            'status' => 'active',
            'actual_start' => $booking->actual_start ?? now(),
        ]);

        return $booking->fresh(['table', 'customer', 'payments']);
    }

    public function completeSession(BilliardBooking $booking, ?float $actualHours = null): BilliardBooking
    {
        $end = now();
        $start = $booking->actual_start ?? $booking->scheduled_start;
        $hours = $actualHours ?? max(0.25, round($start->diffInMinutes($end) / 60, 2));

        $booking->update([
            'status' => 'completed',
            'actual_end' => $end,
            'hours' => $hours,
            'amount' => self::calcAmount($hours, (float) $booking->hourly_rate),
        ]);
        $booking->refreshPaymentStatus();

        return $booking->fresh(['table', 'customer', 'payments']);
    }

    public function recordPayment(
        BilliardBooking $booking,
        string $method,
        float $amount,
        ?string $reference = null,
        ?int $userId = null,
        ?string $notes = null
    ): BilliardPayment {
        if (! in_array($method, BilliardPayment::METHODS, true)) {
            throw new \InvalidArgumentException('Invalid payment method.');
        }
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($booking, $method, $amount, $reference, $userId, $notes) {
            $payment = BilliardPayment::create([
                'billiard_booking_id' => $booking->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $booking->refreshPaymentStatus();
            $this->postToAccounts($payment);

            return $payment;
        });
    }

    protected function postToAccounts(BilliardPayment $payment): void
    {
        if ($payment->method === 'credit') {
            return;
        }

        $account = Account::forPaymentMethod($payment->method)
            ?? ($payment->method === 'cash' ? Account::defaultCash() : Account::defaultBank());

        if (! $account) {
            return;
        }

        $booking = $payment->booking;
        app(AccountService::class)->postManual(
            $account,
            'in',
            (float) $payment->amount,
            'Billiards — '.($booking?->booking_number ?? '#'.$payment->billiard_booking_id),
            $payment->reference
        );
    }

    public function sendEbillSms(BilliardBooking $booking): bool
    {
        $phone = $booking->displayPhone();
        if (! $phone) {
            return false;
        }

        $currency = Setting::get('currency_symbol', 'LKR');
        $company = Setting::get('company_name', 'QRPOS');
        $msg = "{$company} Billiards eBill\n"
            ."#{$booking->booking_number}\n"
            .'Table: '.($booking->table?->name ?? '-')."\n"
            ."Hours: {$booking->hours}\n"
            ."Total: {$currency} ".number_format((float) $booking->amount, 2)."\n"
            .'Paid: '.$currency.' '.number_format((float) $booking->paid_amount, 2)."\n"
            .'Due: '.$currency.' '.number_format($booking->balanceDue(), 2);

        return NotificationService::sendSms($phone, $msg, 'billiards_ebill');
    }

    public function sendPaymentReminder(BilliardBooking $booking): bool
    {
        $phone = $booking->displayPhone();
        if (! $phone) {
            return false;
        }

        $currency = Setting::get('currency_symbol', 'LKR');
        $company = Setting::get('company_name', 'QRPOS');
        $when = $booking->scheduled_start?->format('d M H:i');
        $msg = "{$company}: Reminder — billiards booking #{$booking->booking_number}"
            ." on {$when} is unpaid. Due {$currency} ".number_format($booking->balanceDue(), 2)
            .'. Please pay at the desk.';

        $ok = NotificationService::sendSms($phone, $msg, 'billiards_reminder');
        if ($ok) {
            $booking->update(['reminder_sent_at' => now()]);
        }

        return $ok;
    }

    /** Active/booked sessions ending within alert window. */
    public function endingSoonQuery()
    {
        $mins = self::endAlertMinutes();
        $until = now()->addMinutes($mins);

        return BilliardBooking::query()
            ->with(['table', 'customer'])
            ->whereIn('status', ['booked', 'active'])
            ->where('scheduled_end', '>', now())
            ->where('scheduled_end', '<=', $until);
    }

    public function todayBookingsQuery()
    {
        return BilliardBooking::query()
            ->with(['table', 'customer', 'payments'])
            ->whereDate('scheduled_start', today())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_start');
    }
}
