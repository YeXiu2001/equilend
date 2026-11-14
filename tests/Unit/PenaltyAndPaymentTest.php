<?php

declare(strict_types=1);

use App\Models\Loan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('rejects partial payments and transitions status to paid on full payment', function (): void {
    $org   = Organization::factory()->create();
    $user  = User::factory()->create();
    $staff = User::factory()->create();
    $cash  = PaymentMethod::firstOrCreate(
        ['payment_code' => 'cash'],
        ['name' => 'Cash', 'description' => 'Over-the-counter Cash Payment', 'is_active' => true]
    );

    $penalty = Penalty::factory()->create([
        'organization_id'   => $org->id,
        'user_id'           => $user->id,
        'assessed_by'       => $staff->id,
        'type'              => PenaltyType::Damage,
        'amount'            => 1000.00,
        'paid_amount'       => 0.00,
        'balance_remaining' => 1000.00,
        'status'            => PenaltyStatus::Unpaid,
    ]);

    expect(fn () => $penalty->recordPayment(400.00, [
        'payment_method_id' => $cash->id,
        'received_by'       => $staff->id,
    ]))->toThrow(DomainException::class);

    $payment = $penalty->recordPayment(1000.00, [
        'payment_method_id' => $cash->id,
        'received_by'       => $staff->id,
    ]);

    expect((float) $penalty->paid_amount)->toBe(1000.00)
        ->and((float) $penalty->balance_remaining)->toBe(0.00)
        ->and($penalty->status)->toBe(PenaltyStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Verified)
        ->and((float) $payment->amount_paid)->toBe(1000.00)
        ->and($penalty->payments()->count())->toBe(1);
});

it('rejects overpayments and non-positive payment amounts', function (): void {
    $penalty = Penalty::factory()->unpaid()->create([
        'amount'            => 500.00,
        'balance_remaining' => 500.00,
    ]);

    expect(fn () => $penalty->recordPayment(0))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $penalty->recordPayment(600.00))
        ->toThrow(DomainException::class);
});

it('requires a reason to waive a penalty and clears the remaining balance', function (): void {
    $staff   = User::factory()->create();
    $penalty = Penalty::factory()->unpaid()->create([
        'amount'            => 350.00,
        'balance_remaining' => 350.00,
    ]);

    expect(fn () => $penalty->waive('   ', $staff->id))
        ->toThrow(InvalidArgumentException::class);

    $penalty->waive('Medical emergency certificate submitted', $staff->id);
    $penalty->refresh();

    expect($penalty->status)->toBe(PenaltyStatus::Waived)
        ->and((float) $penalty->balance_remaining)->toBe(0.00)
        ->and($penalty->waived_reason)->toBe('Medical emergency certificate submitted');
});

it('blocks borrowing when user has overdue loans or unpaid penalties', function (): void {
    $org  = Organization::factory()->create();
    $user = User::factory()->create();

    expect($user->canBorrow())->toBeTrue()
        ->and($user->getBorrowingBlockReasons())->toBeEmpty()
        ->and($user->getOutstandingFinesTotal())->toBe(0.0);

    // Unpaid penalty blocks borrowing
    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $user->id,
        'amount'            => 750.00,
        'balance_remaining' => 750.00,
    ]);

    expect($user->canBorrow())->toBeFalse()
        ->and($user->getOutstandingFinesTotal())->toBe(750.0)
        ->and($user->getBorrowingBlockReasons())->toHaveCount(1)
        ->and($user->getBorrowingBlockReasons()[0])->toContain('750.00');

    // Settling the penalty restores borrowing if no overdue loans exist
    $penalty->recordPayment(750.00);

    expect($user->canBorrow())->toBeTrue()
        ->and($user->getOutstandingFinesTotal())->toBe(0.0);

    // Overdue loan blocks borrowing
    $loan = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $user->id,
        'status'          => LoanStatus::Overdue,
        'due_at'          => now()->subDay(),
        'returned_at'     => null,
    ]);

    expect($user->canBorrow())->toBeFalse()
        ->and($user->getBorrowingBlockReasons()[0])->toContain('overdue equipment loan');

    // Returning the loan restores borrowing
    $loan->update([
        'status'      => LoanStatus::Returned,
        'returned_at' => now(),
    ]);

    expect($user->canBorrow())->toBeTrue();
});

it('automatically generates sequential receipt numbers on payment creation', function (): void {
    $year = now()->format('Y');

    $payment1 = Payment::factory()->cash()->create();
    $payment2 = Payment::factory()->gcash()->create();

    expect($payment1->receipt_number)->toBe("RCP-{$year}-0001")
        ->and($payment2->receipt_number)->toBe("RCP-{$year}-0002");
});
