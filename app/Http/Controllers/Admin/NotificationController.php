<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $this->ensureReminderSettings();

        $logs = NotificationLog::query()
            ->whereIn('reference_type', ['payment_reminder', 'hosting_reminder', 'hosting_auto'])
            ->latest()
            ->paginate(20);

        $config = [
            'hosting_enabled' => (bool) Setting::get('hosting_reminder_enabled', false),
            'hosting_due_date' => (string) Setting::get('hosting_reminder_due_date', ''),
            'hosting_days_before' => (int) Setting::get('hosting_reminder_days_before', 7),
            'hosting_email' => (string) Setting::get('hosting_reminder_email', Setting::get('company_email', '')),
            'hosting_phone' => (string) Setting::get('hosting_reminder_phone', ''),
            'hosting_channels' => array_filter(array_map('trim', explode(',', (string) Setting::get('hosting_reminder_channels', 'email')))),
            'hosting_last_sent' => (string) Setting::get('hosting_reminder_last_sent', ''),
            'payment_email' => (string) Setting::get('payment_reminder_default_to', Setting::get('company_email', '')),
            'payment_phone' => (string) Setting::get('payment_reminder_default_phone', ''),
        ];

        $company = Setting::get('company_name', 'QRPOS');
        $defaults = [
            'payment' => "Dear Customer,\n\nThis is a friendly payment reminder for your {$company} / QRPOS subscription.\nPlease settle the outstanding amount at your earliest convenience.\n\nThank you,\nAvenque Support\nqrpos@avenque.io | 076 822 2201",
            'hosting' => "Dear Customer,\n\nThis is a reminder that your hosting for {$company} (QRPOS) is due for renewal.\nPlease renew on time to avoid service interruption.\n\nThank you,\nAvenque Support\nqrpos@avenque.io | 076 822 2201",
        ];

        return view('admin.notifications.index', compact('logs', 'config', 'defaults'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|in:payment_reminder,hosting_reminder',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms,whatsapp,inapp',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:40',
            'message' => 'required|string|max:4000',
            'subject' => 'nullable|string|max:200',
        ]);

        $channels = $data['channels'];
        $needsEmail = in_array('email', $channels, true);
        $needsPhone = in_array('sms', $channels, true) || in_array('whatsapp', $channels, true);
        $needsInApp = in_array('inapp', $channels, true);

        if ($needsEmail && blank($data['email'] ?? null)) {
            return back()->withInput()->with('error', 'Email address is required for email channel.');
        }
        if ($needsPhone && blank($data['phone'] ?? null)) {
            return back()->withInput()->with('error', 'Phone number is required for SMS / WhatsApp.');
        }
        if (! $needsEmail && ! $needsPhone && ! $needsInApp) {
            return back()->withInput()->with('error', 'Select at least one channel.');
        }

        $subject = $data['subject']
            ?? ($data['kind'] === 'payment_reminder' ? 'Payment reminder — QRPOS' : 'Hosting renewal reminder — QRPOS');

        $results = NotificationService::sendReminder(
            kind: $data['kind'],
            message: $data['message'],
            channels: $channels,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            subject: $subject,
        );

        if (in_array('inapp', $channels, true) && empty($results['inapp'])) {
            return back()->withInput()->with(
                'error',
                'In-app not delivered: no active user matches that email (use the restaurant admin login email). Sender is never notified.'
            );
        }

        $ok = collect($results)->contains(fn ($r) => $r === true);
        $summary = collect($results)->map(fn ($okChan, $chan) => $chan.': '.($okChan ? 'sent' : 'failed'))->implode(', ');

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Reminder sent ('.$summary.').' : 'Reminder failed ('.$summary.').'
        );
    }

    public function saveHostingAuto(Request $request)
    {
        $data = $request->validate([
            'hosting_reminder_enabled' => 'nullable|boolean',
            'hosting_reminder_due_date' => 'nullable|date',
            'hosting_reminder_days_before' => 'required|integer|min:0|max:90',
            'hosting_reminder_email' => 'nullable|string|max:500',
            'hosting_reminder_phone' => 'nullable|string|max:40',
            'hosting_reminder_channels' => 'nullable|array',
            'hosting_reminder_channels.*' => 'in:email,sms,whatsapp,inapp',
        ]);

        Setting::set('hosting_reminder_enabled', $request->boolean('hosting_reminder_enabled') ? '1' : '0', 'reminders', 'Enable automated hosting renewal reminders', 'boolean');
        Setting::set('hosting_reminder_due_date', $data['hosting_reminder_due_date'] ?? '', 'reminders', 'Next hosting renewal date', 'string');
        Setting::set('hosting_reminder_days_before', (string) $data['hosting_reminder_days_before'], 'reminders', 'Days before due date to auto-send', 'integer');
        Setting::set('hosting_reminder_email', $data['hosting_reminder_email'] ?? '', 'reminders', 'Hosting reminder email recipients', 'string');
        Setting::set('hosting_reminder_phone', $data['hosting_reminder_phone'] ?? '', 'reminders', 'Hosting reminder phone', 'string');
        Setting::set(
            'hosting_reminder_channels',
            implode(',', $data['hosting_reminder_channels'] ?? ['email']),
            'reminders',
            'Hosting reminder channels',
            'string'
        );

        return back()->with('success', 'Automated hosting reminder settings saved.');
    }

    protected function ensureReminderSettings(): void
    {
        foreach ([
            'hosting_reminder_enabled' => ['0', 'boolean', 'Enable automated hosting renewal reminders'],
            'hosting_reminder_due_date' => ['', 'string', 'Next hosting renewal date (Y-m-d)'],
            'hosting_reminder_days_before' => ['7', 'integer', 'Days before due date to auto-send'],
            'hosting_reminder_email' => ['', 'string', 'Hosting reminder email recipients'],
            'hosting_reminder_phone' => ['', 'string', 'Hosting reminder SMS/WhatsApp number'],
            'hosting_reminder_channels' => ['email', 'string', 'Channels: email,sms,whatsapp'],
            'hosting_reminder_last_sent' => ['', 'string', 'Last auto hosting reminder date'],
            'payment_reminder_default_to' => ['', 'string', 'Default payment reminder email'],
            'payment_reminder_default_phone' => ['', 'string', 'Default payment reminder phone'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'reminders',
                    'description' => $desc,
                ]
            );
        }
    }
}
