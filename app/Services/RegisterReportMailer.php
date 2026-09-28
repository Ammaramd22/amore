<?php

namespace App\Services;

use App\Mail\RegisterCloseReportMail;
use App\Models\CashRegister;
use App\Models\OrderItem;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterReportMailer
{
    public static function sendIfEnabled(CashRegister $register): void
    {
        if (! (bool) Setting::get('day_end_email_enabled', false)) {
            return;
        }

        $to = self::recipients();
        if ($to === []) {
            return;
        }

        try {
            MailConfigService::applyFromSettings();

            $register->loadMissing('user');
            $settings = Setting::getGroup('business');
            $topCategories = self::topCategories($register);
            $pdfBinary = null;

            try {
                $view = $register->mode === 'day_end' ? 'pos.day_end_report_80mm' : 'pos.shift_report_80mm';
                $pdfBinary = Pdf::loadView($view, [
                    'register' => $register,
                    'settings' => $settings,
                    'topCategories' => $topCategories,
                    'cashierBreakdown' => $register->cashierBreakdown(),
                    'shiftDetails' => $register->mode === 'day_end'
                        ? CashRegister::shiftsForDate(
                            $register->business_date?->toDateString() ?? $register->opened_at?->toDateString() ?? now()->toDateString(),
                            false
                        )
                        : collect([$register]),
                ])->setPaper([0, 0, 226.77, 2000])->output();
            } catch (\Throwable $e) {
                Log::warning('Register report PDF failed: '.$e->getMessage());
            }

            Mail::to($to)->send(new RegisterCloseReportMail($register, $settings, $topCategories, $pdfBinary));
        } catch (\Throwable $e) {
            Log::error('Register close email failed: '.$e->getMessage());
        }
    }

    /** @return list<string> */
    public static function recipients(): array
    {
        $raw = (string) Setting::get('day_end_email_to', '');
        if (trim($raw) === '') {
            $raw = (string) Setting::get('company_email', '');
        }

        return collect(preg_split('/[,;\s]+/', $raw) ?: [])
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    public static function topCategories(CashRegister $register): \Illuminate\Support\Collection
    {
        $closedAt = $register->closed_at ?? now();

        return OrderItem::query()
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as qty'),
                DB::raw('SUM(order_items.total_price) as revenue')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where(function ($q) use ($register, $closedAt) {
                $q->where('orders.register_id', $register->id)
                    ->orWhere(function ($q2) use ($register, $closedAt) {
                        $q2->where('orders.cashier_id', $register->user_id)
                            ->whereBetween('orders.created_at', [$register->opened_at, $closedAt]);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('orders.is_void')->orWhere('orders.is_void', false);
            })
            ->where(function ($q) {
                $q->whereNull('order_items.is_void')->orWhere('order_items.is_void', false);
            })
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category_name ?: 'Uncategorized',
                'qty' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ]);
    }
}
