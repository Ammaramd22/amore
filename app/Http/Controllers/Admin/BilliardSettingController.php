<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BilliardsService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class BilliardSettingController extends Controller
{
    protected function ensureEnabled(): void
    {
        abort_unless(BilliardsService::enabled(), 403, 'Billiards module is disabled. Ask the software owner to enable it.');
    }

    public function index()
    {
        $this->ensureEnabled();
        abort_unless(
            auth()->user()?->can('billiards.access')
            || auth()->user()?->can('settings.edit')
            || auth()->user()?->isSoftwareOwner(),
            403
        );

        return view('billiards.settings', [
            'endAlertMinutes' => BilliardsService::endAlertMinutes(),
            'displayToken' => (string) Setting::get('billiards_display_token', ''),
            'autoStartOnPay' => (bool) Setting::get('billiards_auto_start_on_pay', true),
            'printAsk' => (bool) Setting::get('billiards_print_ask', true),
            'smsOnPay' => (bool) Setting::get('billiards_sms_on_pay', false),
            'receiptFooter' => (string) Setting::get(
                'billiards_receipt_footer',
                'Thanks for playing! See you at the tables.'
            ),
            'smsReady' => NotificationService::smsConfigured(),
            'smsProvider' => (string) Setting::get('sms_provider', 'notify_lk'),
            'currency' => Setting::get('currency_symbol', 'LKR'),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureEnabled();
        abort_unless(
            auth()->user()?->can('billiards.access')
            || auth()->user()?->can('settings.edit')
            || auth()->user()?->isSoftwareOwner(),
            403
        );

        $data = $request->validate([
            'billiards_end_alert_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'billiards_display_token' => ['nullable', 'string', 'max:120'],
            'billiards_receipt_footer' => ['nullable', 'string', 'max:200'],
            'billiards_auto_start_on_pay' => ['nullable', 'boolean'],
            'billiards_print_ask' => ['nullable', 'boolean'],
            'billiards_sms_on_pay' => ['nullable', 'boolean'],
        ]);

        Setting::set('billiards_end_alert_minutes', (string) $data['billiards_end_alert_minutes'], 'billiards', null, 'integer');
        Setting::set('billiards_display_token', (string) ($data['billiards_display_token'] ?? ''), 'billiards', null, 'string');
        Setting::set(
            'billiards_receipt_footer',
            trim((string) ($data['billiards_receipt_footer'] ?? '')) ?: 'Thanks for playing! See you at the tables.',
            'billiards',
            null,
            'string'
        );
        Setting::set('billiards_auto_start_on_pay', $request->boolean('billiards_auto_start_on_pay') ? '1' : '0', 'billiards', null, 'boolean');
        Setting::set('billiards_print_ask', $request->boolean('billiards_print_ask') ? '1' : '0', 'billiards', null, 'boolean');
        Setting::set('billiards_sms_on_pay', $request->boolean('billiards_sms_on_pay') ? '1' : '0', 'billiards', null, 'boolean');
        Setting::flushCache();

        return back()->with('success', 'Billiards settings saved.');
    }
}
