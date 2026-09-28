<?php

namespace App\Console\Commands;

use App\Services\ChequeService;
use Illuminate\Console\Command;

class SendChequeReminders extends Command
{
    protected $signature = 'reminders:cheques';

    protected $description = 'Remind owner about pending/overdue supplier cheques';

    public function handle(): int
    {
        $result = ChequeService::processDueReminders();

        if (! empty($result['skipped'])) {
            $this->info('Skipped: '.($result['reason'] ?? 'unknown'));

            return self::SUCCESS;
        }

        $this->info('Cheque reminders sent: '.($result['count'] ?? 0));

        return self::SUCCESS;
    }
}
