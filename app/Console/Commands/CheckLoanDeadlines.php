<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Enums\LoanStatus;
use Illuminate\Console\Command;
use App\Notifications\AdvanceReturnReminderNotification;

class CheckLoanDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equilend:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active loans due within the next hour and dispatch advance return reminders.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now     = now();
        $oneHour = $now->copy()->addHour();
        $sent    = 0;

        Loan::query()
            ->with(['user', 'item', 'itemUnit', 'organization'])
            ->where('status', LoanStatus::Active)
            ->whereNull('returned_at')
            ->whereNull('reminder_sent_at')
            ->whereBetween('due_at', [$now, $oneHour])
            ->chunkById(100, function ($loans) use (&$sent): void {
                foreach ($loans as $loan) {
                    if ($loan->user === null) {
                        continue;
                    }

                    $updated = Loan::query()
                        ->whereKey($loan->id)
                        ->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => now()]);

                    if ($updated === 0) {
                        continue;
                    }

                    $loan->user->notify(new AdvanceReturnReminderNotification($loan));
                    $sent++;
                }
            });

        $this->info("Dispatched {$sent} advance return reminder(s).");

        return self::SUCCESS;
    }
}
