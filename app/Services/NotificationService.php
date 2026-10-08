<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public static function smsConfigured(): bool
    {
        if (! Setting::get('sms_enabled', false)) {
            return false;
        }

        $provider = Setting::get('sms_provider', 'notify_lk');

        if ($provider === 'notify_lk') {
            return (bool) Setting::get('notify_lk_api_key');
        }

        if ($provider === 'smslenz') {
            return (bool) Setting::get('smslenz_user_id')
                && (bool) Setting::get('smslenz_api_key');
        }

        return false;
    }

    public static function whatsappConfigured(): bool
    {
        if (! Setting::get('whatsapp_enabled', false)) {
            return false;
        }

        return (bool) Setting::get('meta_wa_access_token')
            && (bool) Setting::get('meta_wa_phone_number_id');
    }

    public static function sendSms(string $to, string $message, ?string $type = 'general'): bool
    {
        if (! self::smsConfigured()) {
            Log::warning('SMS not configured or disabled');
            self::log('sms', Setting::get('sms_provider', 'notify_lk'), $to, $message, false, $type, 'SMS disabled or not configured');

            return false;
        }

        $provider = Setting::get('sms_provider', 'notify_lk');
        $phone = self::normalizePhone($to);
        $result = false;
        $responseBody = null;

        if ($provider === 'notify_lk') {
            [$result, $responseBody] = self::sendViaNotifyLk($phone, $message);
        } elseif ($provider === 'smslenz') {
            [$result, $responseBody] = self::sendViaSmsLenz($phone, $message);
        }

        self::log('sms', $provider, $phone, $message, $result, $type, $responseBody);

        return $result;
    }

    public static function sendWhatsApp(string $to, string $message, ?string $type = 'general'): bool
    {
        if (! self::whatsappConfigured()) {
            Log::warning('WhatsApp not configured or disabled');
            self::log('whatsapp', 'meta_cloud', $to, $message, false, $type, 'WhatsApp disabled or Meta Cloud API credentials missing');

            return false;
        }

        $phone = self::normalizePhone($to);
        [$result, $responseBody] = self::sendViaMetaCloud($phone, $message, $type);
        self::log('whatsapp', 'meta_cloud', $phone, $message, $result, $type, $responseBody);

        return $result;
    }

    /**
     * Normalize to digits for Meta Cloud API / Notify.lk (Sri Lanka-friendly).
     * Examples: 0771234567 → 94771234567, +94 77 123 4567 → 94771234567
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '94'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '94'.$digits;
        }

        return $digits;
    }

    private static function sendViaNotifyLk(string $to, string $message): array
    {
        $apiKey = Setting::get('notify_lk_api_key');
        $sender = Setting::get('notify_lk_sender_id', 'ResPOS');

        if (! $apiKey) {
            return [false, 'Notify.lk API key not configured'];
        }

        try {
            $response = Http::timeout(20)->asForm()->post('https://app.notify.lk/api/v1/send', [
                'user_id' => Setting::get('notify_lk_user_id'),
                'api_key' => $apiKey,
                'sender_id' => $sender,
                'to' => $to,
                'message' => $message,
            ]);

            $ok = $response->successful() && (
                ($response->json('status') === 'success')
                || ($response->json('message') === 'OK')
                || data_get($response->json(), 'status') === '1'
            );

            return [$ok, $response->body()];
        } catch (\Throwable $e) {
            Log::error('Notify.lk SMS failed: '.$e->getMessage());

            return [false, $e->getMessage()];
        }
    }

    /**
     * SMSLenz API (Sri Lanka) — https://smslenz.lk
     * Expects user_id, api_key, sender_id, and destination number.
     */
    private static function sendViaSmsLenz(string $to, string $message): array
    {
        $userId = trim((string) Setting::get('smslenz_user_id'));
        $apiKey = trim((string) Setting::get('smslenz_api_key'));
        $sender = trim((string) Setting::get('smslenz_sender_id', Setting::get('notify_lk_sender_id', 'ResPOS')));

        if ($userId === '' || $apiKey === '') {
            return [false, 'SMSLenz user id / API key not configured'];
        }

        try {
            $response = Http::timeout(20)->asForm()->post('https://smslenz.lk/api/send-sms', [
                'user_id' => $userId,
                'api_key' => $apiKey,
                'sender_id' => $sender,
                'contact' => $to,
                'message' => $message,
            ]);

            $json = $response->json();
            $ok = $response->successful() && (
                data_get($json, 'status') === 'success'
                || data_get($json, 'success') === true
                || data_get($json, 'status') === 1
                || data_get($json, 'status') === '1'
            );

            return [$ok, $response->body()];
        } catch (\Throwable $e) {
            Log::error('SMSLenz SMS failed: '.$e->getMessage());

            return [false, $e->getMessage()];
        }
    }

    /**
     * Meta WhatsApp Cloud API
     * https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages
     *
     * Free-form text works inside the 24h customer service window (and for test numbers).
     * Outside that window / for promos, set an approved template name in Settings.
     */
    private static function sendViaMetaCloud(string $to, string $message, ?string $type = null): array
    {
        $token = trim((string) Setting::get('meta_wa_access_token'));
        $phoneNumberId = trim((string) Setting::get('meta_wa_phone_number_id'));
        $version = trim((string) Setting::get('meta_wa_api_version', 'v22.0')) ?: 'v22.0';
        $template = trim((string) Setting::get('meta_wa_template_name', ''));
        $templateLang = trim((string) Setting::get('meta_wa_template_language', 'en')) ?: 'en';

        if ($token === '' || $phoneNumberId === '') {
            return [false, 'Meta Access Token and Phone Number ID are required'];
        }

        if ($to === '') {
            return [false, 'Invalid phone number'];
        }

        $version = ltrim($version, '/');
        if (! str_starts_with($version, 'v')) {
            $version = 'v'.$version;
        }

        $endpoint = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $useTemplate = $template !== '' && self::shouldUseTemplate($type);

        $payload = $useTemplate
            ? [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $templateLang],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => mb_substr($message, 0, 1024),
                                ],
                            ],
                        ],
                    ],
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => true,
                    'body' => mb_substr($message, 0, 4096),
                ],
            ];

        try {
            $response = Http::timeout(30)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload);

            $json = $response->json();
            $ok = $response->successful() && (bool) data_get($json, 'messages.0.id');

            return [$ok, $response->body()];
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp Cloud API failed: '.$e->getMessage());

            return [false, $e->getMessage()];
        }
    }

    /**
     * Use approved template for cold / promo sends when a template name is configured.
     * Session-style messages (QR, rating) stay as free-form text when possible.
     */
    private static function shouldUseTemplate(?string $type): bool
    {
        $type = (string) $type;

        return str_starts_with($type, 'promo')
            || $type === 'marketing'
            || $type === 'campaign';
    }

    private static function log(
        string $type,
        string $provider,
        string $to,
        string $message,
        bool $ok,
        ?string $referenceType,
        ?string $response = null
    ): void {
        try {
            NotificationLog::create([
                'type' => $type,
                'provider' => $provider,
                'to' => $to,
                'message' => $message,
                'response' => $response,
                'status' => $ok ? 'sent' : 'failed',
                'reference_type' => $referenceType,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write notification log: '.$e->getMessage());
        }
    }

    public static function notifyOrderReady(string $phone, string $orderNumber): bool
    {
        $msg = 'Dear Customer, your order '.$orderNumber.' is ready for pickup. Thank you! - '
            .Setting::get('company_name', 'Restaurant');

        return self::sendSms($phone, $msg, 'order_ready');
    }

    public static function notifyDeliveryAssigned(string $phone, string $orderNumber, string $riderName): bool
    {
        $msg = 'Your order '.$orderNumber.' has been assigned to rider '.$riderName
            .'. Track your delivery. - '.Setting::get('company_name', 'Restaurant');

        return self::sendSms($phone, $msg, 'delivery_assigned');
    }

    public static function sendEmail(string $to, string $subject, string $message, ?string $type = 'general'): bool
    {
        MailConfigService::applyFromSettings();

        $recipients = collect(preg_split('/[,;]+/', $to) ?: [])
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if ($recipients === []) {
            self::log('email', 'smtp', $to, $message, false, $type, 'No valid email recipients');

            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($recipients, $subject) {
                $mail->to($recipients)->subject($subject);
            });
            self::log('email', (string) Setting::get('mail_mailer', 'smtp'), implode(', ', $recipients), $message, true, $type, $subject);

            return true;
        } catch (\Throwable $e) {
            Log::error('Reminder email failed: '.$e->getMessage());
            self::log('email', (string) Setting::get('mail_mailer', 'smtp'), implode(', ', $recipients), $message, false, $type, $e->getMessage());

            return false;
        }
    }

    /**
     * @param  list<string>  $channels
     * @return array<string, bool>
     */
    public static function sendReminder(
        string $kind,
        string $message,
        array $channels,
        ?string $email = null,
        ?string $phone = null,
        string $subject = 'QRPOS reminder',
    ): array {
        $results = [];

        foreach ($channels as $channel) {
            $channel = strtolower(trim($channel));
            if ($channel === 'email') {
                $results['email'] = self::sendEmail((string) $email, $subject, $message, $kind);
            } elseif ($channel === 'sms') {
                $results['sms'] = self::sendSms((string) $phone, $message, $kind);
            } elseif ($channel === 'whatsapp') {
                $results['whatsapp'] = self::sendWhatsApp((string) $phone, $message, $kind);
            } elseif ($channel === 'inapp') {
                $results['inapp'] = self::sendInApp($kind, $subject, $message, $email);
            }
        }

        // Only push in-app when that channel is selected (do not auto-spam sender)
        return $results;
    }

    /**
     * Push to recipient accounts only (by email).
     * Does not notify the sender. Software owner is only notified if their email is the target.
     */
    public static function sendInApp(string $kind, string $title, string $message, ?string $email = null): bool
    {
        try {
            $emails = collect(preg_split('/[,;]+/', (string) $email) ?: [])
                ->map(fn ($e) => strtolower(trim($e)))
                ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values();

            $senderId = auth()->id();
            $recipients = collect();

            if ($emails->isNotEmpty()) {
                $recipients = \App\Models\User::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($emails) {
                        foreach ($emails as $e) {
                            $q->orWhereRaw('LOWER(email) = ?', [$e]);
                        }
                    })
                    ->get();
            } else {
                $recipients = \App\Models\User::query()
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'manager']))
                            ->orWhereIn('user_type', ['admin', 'manager']);
                    })
                    ->where('user_type', '!=', 'software_owner')
                    ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'software_owner'))
                    ->get();
            }

            $recipients = $recipients
                ->unique('id')
                ->reject(fn ($u) => $senderId && (int) $u->id === (int) $senderId)
                ->values();

            foreach ($recipients as $user) {
                $user->notify(new \App\Notifications\InAppReminderNotification(
                    $kind,
                    $title,
                    $message,
                    $email
                ));
            }

            self::log(
                'email',
                'inapp',
                $emails->implode(', ') ?: 'admins',
                $message,
                $recipients->isNotEmpty(),
                $kind,
                'in-app header → '.$recipients->count().' user(s)'
            );

            return $recipients->isNotEmpty();
        } catch (\Throwable $e) {
            Log::error('In-app reminder failed: '.$e->getMessage());
            self::log('email', 'inapp', (string) $email, $message, false, $kind, $e->getMessage());

            return false;
        }
    }

    /** Run daily: auto hosting reminder when due date is within N days. */
    public static function processHostingAutoReminder(): array
    {
        if (! Setting::get('hosting_reminder_enabled', false)) {
            return ['skipped' => true, 'reason' => 'disabled'];
        }

        $due = (string) Setting::get('hosting_reminder_due_date', '');
        if ($due === '') {
            return ['skipped' => true, 'reason' => 'no_due_date'];
        }

        try {
            $dueDate = \Carbon\Carbon::parse($due)->startOfDay();
        } catch (\Throwable $e) {
            return ['skipped' => true, 'reason' => 'invalid_due_date'];
        }

        $daysBefore = (int) Setting::get('hosting_reminder_days_before', 7);
        $today = now()->startOfDay();
        $windowStart = $dueDate->copy()->subDays(max(0, $daysBefore));

        if ($today->lt($windowStart) || $today->gt($dueDate)) {
            return ['skipped' => true, 'reason' => 'outside_window', 'due' => $dueDate->toDateString()];
        }

        $lastSent = (string) Setting::get('hosting_reminder_last_sent', '');
        if ($lastSent === $today->toDateString()) {
            return ['skipped' => true, 'reason' => 'already_sent_today'];
        }

        $company = Setting::get('company_name', 'QRPOS');
        $message = "Dear Customer,\n\nAutomated reminder: hosting for {$company} (QRPOS) renews on {$dueDate->format('d M Y')}.\nPlease renew to avoid interruption.\n\nAvenque Support\nqrpos@avenque.io | 076 822 2201";
        $channels = array_filter(array_map('trim', explode(',', (string) Setting::get('hosting_reminder_channels', 'email'))));
        $email = (string) Setting::get('hosting_reminder_email', Setting::get('company_email', ''));
        $phone = (string) Setting::get('hosting_reminder_phone', '');

        $results = self::sendReminder(
            kind: 'hosting_auto',
            message: $message,
            channels: $channels ?: ['email'],
            email: $email,
            phone: $phone,
            subject: 'Hosting renewal reminder — QRPOS ('.$dueDate->format('d M Y').')',
        );

        Setting::set('hosting_reminder_last_sent', $today->toDateString(), 'reminders');

        return ['skipped' => false, 'results' => $results, 'due' => $dueDate->toDateString()];
    }
}
