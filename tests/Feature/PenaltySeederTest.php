<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;

it('seeds realistic penalties, payments, and borrower lockouts across all 5 colleges', function (): void {
    $this->seed(DatabaseSeeder::class);

    $colleges = Organization::whereIn('code', ['CCS', 'CED', 'COET', 'CSM', 'CASS'])->get();

    expect($colleges)->toHaveCount(5)
        ->and(Penalty::where('assessment_notes', 'not like', '[AnalyticsSeeder]%')->count())->toBe(35)
        ->and(Payment::where('notes', 'not like', '[AnalyticsSeeder]%')->count())->toBe(20);

    foreach ($colleges as $college) {
        $orgPenalties = Penalty::where('organization_id', $college->id)
            ->where('assessment_notes', 'not like', '[AnalyticsSeeder]%')
            ->get();
        $orgPayments = Payment::where('organization_id', $college->id)
            ->where('notes', 'not like', '[AnalyticsSeeder]%')
            ->get();

        expect($orgPenalties)->toHaveCount(7)
            ->and($orgPenalties->where('status', PenaltyStatus::Unpaid))->toHaveCount(2)
            ->and($orgPenalties->where('status', PenaltyStatus::Paid))->toHaveCount(3)
            ->and($orgPenalties->where('status', PenaltyStatus::PartiallyPaid))->toHaveCount(1)
            ->and($orgPenalties->where('status', PenaltyStatus::Waived))->toHaveCount(1)
            ->and($orgPayments)->toHaveCount(4)
            ->and($orgPayments->where('status', PaymentStatus::Verified))->toHaveCount(4);

        $partial = $orgPenalties->firstWhere('status', PenaltyStatus::PartiallyPaid);
        expect((float) $partial->amount)->toBe(1000.00)
            ->and((float) $partial->paid_amount)->toBe(400.00)
            ->and((float) $partial->balance_remaining)->toBe(600.00);

        $student1 = User::where('email', sprintf('student.%s@equilend.test', mb_strtolower($college->code)))->first();
        expect($student1)->not->toBeNull()
            ->and($student1->canBorrow())->toBeFalse()
            ->and($student1->getOutstandingFinesTotal())->toBeGreaterThan(0.0)
            ->and($student1->getBorrowingBlockReasons())->not->toBeEmpty();
    }

    // Verify receipt format on all seeded payments
    Payment::all()->each(function (Payment $payment): void {
        expect($payment->receipt_number)->toMatch('/^RCP-\d{4}-\d{4}$/');
    });

    // Verify idempotency when running PenaltySeeder a second time
    $this->seed(DatabaseSeeder::class);

    expect(Penalty::where('assessment_notes', 'not like', '[AnalyticsSeeder]%')->count())->toBe(35)
        ->and(Payment::where('notes', 'not like', '[AnalyticsSeeder]%')->count())->toBe(20);
});
