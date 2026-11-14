<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Penalty;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Enums\PenaltyStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Notifications\LoanOverdueNotification;

class ProcessOverdueLoans extends Command
{
    /**
     * Default daily overdue fine rate in PHP.
     */
    public const float DAILY_OVERDUE_RATE = 50.00;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'equilend:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark past-due active loans as overdue, assess initial overdue penalties, and notify borrowers.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now       = now();
        $processed = 0;

        Loan::query()
            ->with(['user', 'item', 'itemUnit', 'organization'])
            ->where('status', LoanStatus::Active)
            ->whereNull('returned_at')
            ->where('due_at', '<', $now)
            ->chunkById(100, function ($loans) use ($now, &$processed): void {
                foreach ($loans as $loan) {
                    $penalty = DB::transaction(function () use ($loan, $now): ?Penalty {
                        $lockedLoan = Loan::lockForUpdate()->find($loan->id);

                        if ($lockedLoan === null || $lockedLoan->status !== LoanStatus::Active) {
                            return null;
                        }

                        $lockedLoan->markAsOverdue();

                        $existingPenalty = Penalty::query()
                            ->where('loan_id', $lockedLoan->id)
                            ->where('type', PenaltyType::Overdue)
                            ->first();

                        if ($existingPenalty !== null) {
                            return $existingPenalty;
                        }

                        $hoursOverdue = max(1, (int) ceil($lockedLoan->due_at->diffInMinutes($now) / 60));
                        $daysOverdue  = max(1, (int) ceil($hoursOverdue / 24));
                        $fineAmount   = round($daysOverdue * self::DAILY_OVERDUE_RATE, 2);

                        return Penalty::create([
                            'organization_id'   => $lockedLoan->organization_id,
                            'loan_id'           => $lockedLoan->id,
                            'user_id'           => $lockedLoan->user_id,
                            'item_unit_id'      => $lockedLoan->item_unit_id,
                            'type'              => PenaltyType::Overdue,
                            'amount'            => $fineAmount,
                            'paid_amount'       => 0.00,
                            'balance_remaining' => $fineAmount,
                            'status'            => PenaltyStatus::PendingApproval,
                            'assessment_notes'  => "Automated overdue fine ({$daysOverdue} day(s) past due at ₱".number_format(self::DAILY_OVERDUE_RATE, 2).'/day) — pending admin approval.',
                            'assessed_by'       => null,
                        ]);
                    });

                    if ($penalty === null && $loan->status !== LoanStatus::Overdue) {
                        continue;
                    }

                    $loan->refresh();

                    if ($loan->user !== null) {
                        $loan->user->notify(new LoanOverdueNotification($loan, $penalty));
                    }

                    $processed++;
                }
            });

        $this->info("Processed {$processed} overdue loan(s).");

        return self::SUCCESS;
    }
}
