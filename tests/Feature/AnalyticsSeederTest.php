<?php

declare(strict_types=1);

use App\Models\Loan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use App\Enums\LoanStatus;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\AnalyticsSeeder;
use Database\Seeders\NotificationSeeder;

it('seeds historical loans across multiple months and payment channels per college', function (): void {
    $this->seed(DatabaseSeeder::class);

    $collegeCodes = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

    foreach ($collegeCodes as $code) {
        $college = Organization::where('code', $code)->first();
        expect($college)->not->toBeNull();

        $historicalLoans = Loan::where('organization_id', $college->id)
            ->where('notes', 'like', '[AnalyticsSeeder]%')
            ->get();

        expect($historicalLoans)->toHaveCount(30);

        $distinctMonths = $historicalLoans
            ->map(fn (Loan $loan): string => $loan->borrowed_at->format('Y-m'))
            ->unique();

        expect($distinctMonths->count())->toBeGreaterThanOrEqual(2);

        $onTimeCount = $historicalLoans
            ->filter(fn (Loan $loan): bool => $loan->status === LoanStatus::Returned && $loan->returned_at->lte($loan->due_at))
            ->count();

        $lateCount = $historicalLoans
            ->filter(fn (Loan $loan): bool => $loan->status === LoanStatus::Returned && $loan->returned_at->gt($loan->due_at))
            ->count();

        expect($onTimeCount)->toBe(25)
            ->and($lateCount)->toBe(5);

        $historicalPenalties = Penalty::where('organization_id', $college->id)
            ->where('assessment_notes', 'like', '[AnalyticsSeeder]%')
            ->count();

        expect($historicalPenalties)->toBe(5);

        $historicalPayments = Payment::where('organization_id', $college->id)
            ->where('notes', 'like', '[AnalyticsSeeder]%')
            ->where('status', PaymentStatus::Verified)
            ->count();

        expect($historicalPayments)->toBe(5);
    }
});

it('seeds unread and read in-app notifications for college admins and students', function (): void {
    $this->seed(DatabaseSeeder::class);

    $ccsAdmin    = User::where('email', 'admin.ccs@equilend.test')->first();
    $ccsStudent  = User::where('email', 'student.ccs@equilend.test')->first();
    $ccsStudent2 = User::where('email', 'student2.ccs@equilend.test')->first();

    expect($ccsAdmin)->not->toBeNull()
        ->and($ccsAdmin->unreadNotifications()->count())->toBe(2)
        ->and($ccsAdmin->notifications()->count())->toBe(3);

    expect($ccsStudent)->not->toBeNull()
        ->and($ccsStudent->unreadNotifications()->count())->toBe(2)
        ->and($ccsStudent->notifications()->count())->toBe(3);

    expect($ccsStudent2)->not->toBeNull()
        ->and($ccsStudent2->unreadNotifications()->count())->toBe(1);
});

it('is idempotent when running AnalyticsSeeder and NotificationSeeder multiple times', function (): void {
    $this->seed(DatabaseSeeder::class);

    $loanCountBefore         = Loan::count();
    $notificationCountBefore = Illuminate\Support\Facades\DB::table('notifications')->count();

    $this->seed(AnalyticsSeeder::class);
    $this->seed(NotificationSeeder::class);

    expect(Loan::count())->toBe($loanCountBefore)
        ->and(Illuminate\Support\Facades\DB::table('notifications')->count())->toBe($notificationCountBefore);
});
