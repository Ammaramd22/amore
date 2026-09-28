<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /** Apply SMTP / mail settings from the database onto Laravel config. */
    public static function applyFromSettings(): void
    {
        try {
            $mailer = (string) Setting::get('mail_mailer', config('mail.default', 'log'));
            if ($mailer === '') {
                $mailer = 'log';
            }

            Config::set('mail.default', $mailer);

            $host = Setting::get('mail_host');
            if ($host) {
                Config::set('mail.mailers.smtp.host', $host);
            }
            $port = Setting::get('mail_port');
            if ($port !== null && $port !== '') {
                Config::set('mail.mailers.smtp.port', (int) $port);
            }
            $username = Setting::get('mail_username');
            if ($username !== null && $username !== '') {
                Config::set('mail.mailers.smtp.username', $username);
            }
            $password = Setting::get('mail_password');
            if ($password !== null && $password !== '') {
                Config::set('mail.mailers.smtp.password', $password);
            }
            $encryption = Setting::get('mail_encryption');
            if ($encryption === 'none' || $encryption === '') {
                Config::set('mail.mailers.smtp.encryption', null);
            } elseif ($encryption) {
                Config::set('mail.mailers.smtp.encryption', $encryption);
            }

            $fromAddress = Setting::get('mail_from_address');
            if ($fromAddress) {
                Config::set('mail.from.address', $fromAddress);
            }
            $fromName = Setting::get('mail_from_name');
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Throwable $e) {
            // Settings table may not exist during early migrate
        }
    }
}
