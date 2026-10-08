<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendHostingReminders extends Command
{
    protected $signature = 'reminders:hosting';

    protected $description = 'Send automated hosting renewal reminders when due date is within the configured window';

    public function handle(): int
    {
        $result = NotificationService::processHostingAutoReminder();

        if (! empty($result['skipped'])) {
            $reason = $result['reason'] ?? 'unknown';
            $this->info('Skipped: '.$reason);

            return self::SUCCESS;
        }

        $due = $result['due'] ?? '';
        $payload = json_encode($result['results'] ?? []);
        $this->info('Hosting reminder processed for due '.$due.': '.$payload);

        return self::SUCCESS;
    }
}
