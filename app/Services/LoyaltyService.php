<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyStampLog;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyService
{
    public static function enabled(): bool
    {
        return (bool) Setting::get('loyalty_enabled', false);
    }

    public static function stampsRequired(): int
    {
        return max(1, (int) Setting::get('loyalty_stamps_required', 10));
    }

    public static function rewardLabel(): string
    {
        return (string) Setting::get('loyalty_reward_label', 'Free drink');
    }

    /** Days until digital card expires after join. 0 = never expires. */
    public static function cardExpiryDays(): int
    {
        return max(0, (int) Setting::get('loyalty_card_expiry_days', 365));
    }

    /** @return list<int> */
    public static function categoryIds(): array
    {
        $raw = Setting::get('loyalty_category_ids', '[]');
        if (is_array($raw)) {
            return array_values(array_map('intval', $raw));
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
    }

    public static function config(): array
    {
        return [
            'enabled' => self::enabled(),
            'stamps_required' => self::stampsRequired(),
            'reward_label' => self::rewardLabel(),
            'category_ids' => self::categoryIds(),
            'card_expiry_days' => self::cardExpiryDays(),
        ];
    }

    public function isExpired(Customer $customer): bool
    {
        if (! $customer->loyalty_joined) {
            return false;
        }
        if (! $customer->loyalty_expires_at) {
            return false;
        }

        return $customer->loyalty_expires_at->isPast();
    }

    public function enroll(Customer $customer): Customer
    {
        $days = self::cardExpiryDays();
        $expiresAt = $days > 0 ? now()->addDays($days) : null;

        if ($customer->loyalty_joined && $customer->loyalty_token) {
            // Refresh expiry on re-join / keep active card expiry if still valid
            if ($this->isExpired($customer) || ! $customer->loyalty_expires_at) {
                $customer->update(['loyalty_expires_at' => $expiresAt]);
            }

            return $customer->fresh();
        }

        $customer->update([
            'loyalty_joined' => true,
            'loyalty_token' => $customer->loyalty_token ?: Str::random(40),
            'loyalty_joined_at' => $customer->loyalty_joined_at ?: now(),
            'loyalty_expires_at' => $expiresAt,
        ]);

        LoyaltyStampLog::create([
            'customer_id' => $customer->id,
            'type' => 'join',
            'stamps_delta' => 0,
            'free_delta' => 0,
            'stamps_after' => (int) $customer->loyalty_stamps,
            'free_after' => (int) $customer->loyalty_free_drinks,
            'note' => 'Joined loyalty stamp card'.($expiresAt ? ' · expires '.$expiresAt->format('Y-m-d') : ''),
            'created_by' => auth()->id(),
        ]);

        return $customer->fresh();
    }

    public function cardUrl(Customer $customer): ?string
    {
        if (! $customer->loyalty_token) {
            return null;
        }

        return route('loyalty.card', $customer->loyalty_token);
    }

    public function qrPayload(Customer $customer): string
    {
        return 'LOYALTY:'.($customer->loyalty_token ?? '');
    }

    public function findByToken(?string $token): ?Customer
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }
        if (str_starts_with(strtoupper($token), 'LOYALTY:')) {
            $token = substr($token, 8);
        }
        if (str_contains($token, '/loyalty/card/')) {
            $token = basename(parse_url($token, PHP_URL_PATH) ?: $token);
        }

        return Customer::query()
            ->where('loyalty_token', $token)
            ->where('loyalty_joined', true)
            ->where('is_active', true)
            ->first();
    }

    public function customerPayload(Customer $customer): array
    {
        $required = self::stampsRequired();
        $stamps = (int) $customer->loyalty_stamps;
        $free = (int) $customer->loyalty_free_drinks;
        $expired = $this->isExpired($customer);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'joined' => (bool) $customer->loyalty_joined,
            'expired' => $expired,
            'expires_at' => $customer->loyalty_expires_at?->toDateString(),
            'stamps' => $stamps,
            'stamps_required' => $required,
            'stamps_toward_next' => $stamps % $required,
            'free_drinks' => $expired ? 0 : $free,
            'reward_label' => self::rewardLabel(),
            'card_url' => $this->cardUrl($customer),
            'qr' => $customer->loyalty_joined ? $this->qrPayload($customer) : null,
            'can_redeem' => ! $expired && $free > 0,
        ];
    }

    /**
     * Award stamps from a paid order for enrolled customers only.
     * 1 qualifying item qty = 1 stamp. Free/redeemed lines do not earn stamps.
     * Every N stamps → +1 free drink.
     *
     * @param  int  $skipQualifyingQty  Extra qualifying qty to ignore (e.g. open-bill free via discount)
     */
    public function awardFromOrder(Order $order, int $skipQualifyingQty = 0): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $customer = $order->customer_id ? Customer::find($order->customer_id) : null;
        if (! $customer || ! $customer->loyalty_joined || $this->isExpired($customer)) {
            return null;
        }

        $categoryIds = self::categoryIds();
        if ($categoryIds === []) {
            return null;
        }

        $order->loadMissing('items.product');
        $earned = 0;
        foreach ($order->items as $item) {
            if ($item->is_void) {
                continue;
            }
            $instructions = strtolower((string) ($item->special_instructions ?? ''));
            if (str_contains($instructions, 'loyalty free') || str_contains($instructions, 'free drink')) {
                continue;
            }
            if ((float) $item->unit_price <= 0 && (float) $item->total_price <= 0) {
                continue;
            }
            $catId = (int) ($item->product?->category_id ?? 0);
            if ($catId && in_array($catId, $categoryIds, true)) {
                $earned += (int) max(1, round((float) $item->quantity));
            }
        }

        $earned = max(0, $earned - max(0, $skipQualifyingQty));

        if ($earned <= 0) {
            return $this->customerPayload($customer);
        }

        $already = LoyaltyStampLog::query()
            ->where('order_id', $order->id)
            ->where('type', 'earn')
            ->exists();
        if ($already) {
            return $this->customerPayload($customer);
        }

        return DB::transaction(function () use ($customer, $order, $earned) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $required = self::stampsRequired();
            $stamps = (int) $customer->loyalty_stamps + $earned;
            $free = (int) $customer->loyalty_free_drinks;
            $freeGained = intdiv($stamps, $required);
            $remainder = $stamps % $required;
            $free += $freeGained;

            $customer->update([
                'loyalty_stamps' => $remainder,
                'loyalty_free_drinks' => $free,
                'loyalty_points' => $remainder,
            ]);

            LoyaltyStampLog::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'type' => 'earn',
                'stamps_delta' => $earned,
                'free_delta' => $freeGained,
                'stamps_after' => $remainder,
                'free_after' => $free,
                'note' => "+{$earned} stamp(s) from order {$order->order_number}",
                'created_by' => auth()->id(),
            ]);

            $payload = $this->customerPayload($customer->fresh());
            $payload['earned'] = $earned;
            $payload['free_gained'] = $freeGained;
            $payload['just_completed'] = $freeGained > 0;

            return $payload;
        });
    }

    public function redeemFree(Customer $customer, ?Order $order = null, ?float $value = null): array
    {
        if (! self::enabled()) {
            throw new \RuntimeException('Loyalty is disabled.');
        }
        if (! $customer->loyalty_joined) {
            throw new \RuntimeException('Customer is not on the loyalty stamp card.');
        }
        if ($this->isExpired($customer)) {
            throw new \RuntimeException('Loyalty card has expired. Please renew / re-join.');
        }

        return DB::transaction(function () use ($customer, $order, $value) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ((int) $customer->loyalty_free_drinks < 1) {
                throw new \RuntimeException('No free drink available yet.');
            }

            $free = (int) $customer->loyalty_free_drinks - 1;
            $customer->update(['loyalty_free_drinks' => $free]);

            $note = 'Redeemed '.self::rewardLabel();
            if ($value !== null && $value > 0) {
                $note .= ' (−'.number_format($value, 2).')';
            }
            if ($order) {
                $note .= ' on order '.$order->order_number;
            }

            LoyaltyStampLog::create([
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'type' => 'redeem',
                'stamps_delta' => 0,
                'free_delta' => -1,
                'stamps_after' => (int) $customer->loyalty_stamps,
                'free_after' => $free,
                'note' => $note,
                'created_by' => auth()->id(),
            ]);

            $payload = $this->customerPayload($customer->fresh());
            $payload['redeemed'] = true;
            $payload['redeem_value'] = $value;

            return $payload;
        });
    }
}
