<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use App\Enums\ItemUnitCondition;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use App\Models\OrganizationPaymentMethod;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Penalties\Index as PenaltiesIndex;
use App\Livewire\Borrower\Penalties as BorrowerPenalties;

beforeEach(function (): void {
    Storage::fake('public');
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('allows a borrower to submit an online payment with a receipt image for all unsettled penalties and admin to confirm and clear them', function (): void {
    $org = Organization::factory()->create(['code' => 'CCS']);

    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'description' => 'GCash Wallet', 'is_active' => true]
    );

    $channel = OrganizationPaymentMethod::create([
        'organization_id'   => $org->id,
        'payment_method_id' => $gcash->id,
        'account_name'      => 'CCS Treasury',
        'account_numbers'   => ['09171234567'],
        'is_enabled'        => true,
    ]);

    $penaltyA = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 250.00,
        'balance_remaining' => 250.00,
    ]);

    $penaltyB = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 150.00,
        'balance_remaining' => 150.00,
    ]);

    expect($student->fresh()->canBorrow())->toBeFalse();

    $this->actingAs($student);

    $receipt = UploadedFile::fake()->image('gcash-receipt.png', 600, 800);

    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal')
        ->assertSet('payModal', true)
        ->assertSet('payScope', 'all')
        ->set('selectedChannelId', $channel->id)
        ->set('referenceNumber', 'GCASH-99887766')
        ->set('receiptImage', $receipt)
        ->call('submitOnlinePayment')
        ->assertHasNoErrors()
        ->assertSet('payModal', false);

    // Payments are pending verification; penalties are still unpaid until admin confirms
    $pendingPayments = Payment::where('user_id', $student->id)->get();
    expect($pendingPayments)->toHaveCount(2)
        ->and($pendingPayments->every(fn (Payment $p): bool => $p->status === PaymentStatus::PendingVerification))->toBeTrue()
        ->and($penaltyA->fresh()->status)->toBe(PenaltyStatus::Unpaid)
        ->and($penaltyB->fresh()->status)->toBe(PenaltyStatus::Unpaid)
        ->and($student->fresh()->canBorrow())->toBeFalse();

    Storage::disk('public')->assertExists($pendingPayments->first()->payment_proof_url);

    // Admin reviews and confirms payment on /finance/penalties
    $this->actingAs($admin);

    Livewire::test(PenaltiesIndex::class)
        ->assertSee('Verify Receipt')
        ->call('openVerifyModal', $penaltyA->id)
        ->assertSet('verifyModal', true)
        ->call('confirmPendingPayment', false)
        ->assertSet('verifyModal', false);

    expect($penaltyA->fresh()->status)->toBe(PenaltyStatus::Paid)
        ->and((float) $penaltyA->fresh()->balance_remaining)->toBe(0.00)
        ->and($penaltyB->fresh()->status)->toBe(PenaltyStatus::Paid)
        ->and((float) $penaltyB->fresh()->balance_remaining)->toBe(0.00)
        ->and($student->fresh()->canBorrow())->toBeTrue();

    expect(Payment::where('user_id', $student->id)->where('status', PaymentStatus::Verified)->count())->toBe(2);
});

it('allows an admin to reject an invalid receipt without clearing penalties and lets the borrower re-submit', function (): void {
    $org = Organization::factory()->create(['code' => 'COET']);

    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $maya = PaymentMethod::firstOrCreate(
        ['payment_code' => 'maya'],
        ['name' => 'Maya', 'description' => 'Maya Wallet', 'is_active' => true]
    );

    $channel = OrganizationPaymentMethod::create([
        'organization_id'   => $org->id,
        'payment_method_id' => $maya->id,
        'account_name'      => 'COET Finance',
        'account_numbers'   => ['09181112222'],
        'is_enabled'        => true,
    ]);

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 300.00,
        'balance_remaining' => 300.00,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal', $penalty->id)
        ->assertSet('payScope', 'single')
        ->set('selectedChannelId', $channel->id)
        ->set('receiptImage', UploadedFile::fake()->image('blurry.jpg'))
        ->call('submitOnlinePayment')
        ->assertHasNoErrors();

    $payment = Payment::where('penalty_id', $penalty->id)->firstOrFail();

    // Admin rejects via /finance/payments
    $this->actingAs($admin);

    Livewire::test(PaymentsIndex::class)
        ->call('viewReceipt', $payment->id)
        ->set('rejectReason', 'Receipt image is unreadable')
        ->call('rejectPayment', $payment->id)
        ->assertSet('receiptModal', false);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Voided)
        ->and($payment->fresh()->notes)->toContain('Receipt image is unreadable')
        ->and($penalty->fresh()->status)->toBe(PenaltyStatus::Unpaid)
        ->and((float) $penalty->fresh()->balance_remaining)->toBe(300.00);

    // Borrower can re-submit a new receipt
    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal', $penalty->id)
        ->assertSet('payModal', true)
        ->set('selectedChannelId', $channel->id)
        ->set('receiptImage', UploadedFile::fake()->image('clear-receipt.png'))
        ->call('submitOnlinePayment')
        ->assertHasNoErrors();

    expect(Payment::where('penalty_id', $penalty->id)->where('status', PaymentStatus::PendingVerification)->count())->toBe(1);
});

it('allows admin to confirm payment and clear all remaining borrower penalties at once', function (): void {
    $org = Organization::factory()->create(['code' => 'CCS']);

    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'description' => 'GCash Wallet', 'is_active' => true]
    );

    $channel = OrganizationPaymentMethod::create([
        'organization_id'   => $org->id,
        'payment_method_id' => $gcash->id,
        'account_name'      => 'CCS Treasury',
        'account_numbers'   => ['09171234567'],
        'is_enabled'        => true,
    ]);

    $penalty1 = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 200.00,
        'balance_remaining' => 200.00,
    ]);

    $penalty2 = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 100.00,
        'balance_remaining' => 100.00,
    ]);

    $this->actingAs($student);

    // Borrower submits receipt for penalty1 only
    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal', $penalty1->id)
        ->set('selectedChannelId', $channel->id)
        ->set('receiptImage', UploadedFile::fake()->image('receipt.png'))
        ->call('submitOnlinePayment')
        ->assertHasNoErrors();

    $this->actingAs($admin);

    // Admin confirms & clears all penalties for the borrower
    Livewire::test(PenaltiesIndex::class)
        ->call('openVerifyModal', $penalty1->id)
        ->call('confirmPendingPayment', true)
        ->assertSet('verifyModal', false);

    expect($penalty1->fresh()->status)->toBe(PenaltyStatus::Paid)
        ->and($penalty2->fresh()->status)->toBe(PenaltyStatus::Paid)
        ->and($student->fresh()->getOutstandingFinesTotal())->toBe(0.0)
        ->and($student->fresh()->canBorrow())->toBeTrue();
});

it('requires a receipt image when submitting an online payment and prevents cross-college verification', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS']);
    $coet = Organization::factory()->create(['code' => 'COET']);

    $coetAdmin = User::factory()->create();
    $coetAdmin->assignRole('organization_admin');
    $coetAdmin->organizations()->attach($coet->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($ccs->id, ['role' => 'member']);

    $gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'description' => 'GCash Wallet', 'is_active' => true]
    );

    $channel = OrganizationPaymentMethod::create([
        'organization_id'   => $ccs->id,
        'payment_method_id' => $gcash->id,
        'account_name'      => 'CCS Treasury',
        'account_numbers'   => ['09171234567'],
        'is_enabled'        => true,
    ]);

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $ccs->id,
        'user_id'           => $student->id,
        'amount'            => 100.00,
        'balance_remaining' => 100.00,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal', $penalty->id)
        ->set('selectedChannelId', $channel->id)
        ->call('submitOnlinePayment')
        ->assertHasErrors(['receiptImage']);

    $payment = Payment::factory()->create([
        'organization_id' => $ccs->id,
        'penalty_id'      => $penalty->id,
        'user_id'         => $student->id,
        'status'          => PaymentStatus::PendingVerification,
    ]);

    $this->actingAs($coetAdmin);

    Livewire::test(PaymentsIndex::class)
        ->call('confirmPayment', $payment->id)
        ->assertForbidden();
});

it('displays the linked loan number in the penalty breakdown and shows relevant loan details in a modal', function (): void {
    $org = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);

    $staff   = User::factory()->create(['name' => 'Engr. Cruz']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('member');

    $item = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Fluke Multimeter',
        'brand'           => 'Fluke',
        'model_number'    => '87V',
    ]);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CCS-MM-042',
    ]);

    $loan = Loan::factory()->create([
        'organization_id'    => $org->id,
        'user_id'            => $student->id,
        'item_id'            => $item->id,
        'item_unit_id'       => $unit->id,
        'issued_by'          => $staff->id,
        'status'             => LoanStatus::Damaged,
        'checkout_condition' => ItemUnitCondition::Good,
        'checkin_condition'  => ItemUnitCondition::Poor,
        'notes'              => 'Returned with cracked LCD',
    ]);

    $otherUnit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
    ]);

    $otherLoan = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $otherStudent->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $otherUnit->id,
    ]);

    Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'loan_id'           => $loan->id,
        'item_unit_id'      => $unit->id,
        'amount'            => 500.00,
        'balance_remaining' => 500.00,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->assertSee('#'.$loan->id)
        ->assertSee('Fluke Multimeter')
        ->call('openLoanModal', $loan->id)
        ->assertSet('loanModal', true)
        ->assertSee('Loan #'.$loan->id)
        ->assertSee('CCS-MM-042')
        ->assertSee('Fluke 87V')
        ->assertSee('Engr. Cruz')
        ->assertSee('Returned with cracked LCD')
        ->call('openLoanModal', $otherLoan->id)
        ->assertNotFound();
});

it('requires automated overdue penalties to be approved by admin and the item returned before borrower can pay', function (): void {
    $org = Organization::factory()->create(['code' => 'CCS']);

    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'description' => 'GCash Wallet', 'is_active' => true]
    );

    $channel = OrganizationPaymentMethod::create([
        'organization_id'   => $org->id,
        'payment_method_id' => $gcash->id,
        'account_name'      => 'CCS Treasury',
        'account_numbers'   => ['09171234567'],
        'is_enabled'        => true,
    ]);

    $item = Item::factory()->create(['organization_id' => $org->id]);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
    ]);

    $loan = Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $admin->id,
        'borrowed_at'     => now()->subDays(3),
        'due_at'          => now()->subDay(),
        'returned_at'     => null,
        'status'          => LoanStatus::Active,
    ]);

    // Automated overdue command creates a PendingApproval penalty
    $this->artisan('equilend:check-overdue')->assertSuccessful();

    $penalty = Penalty::where('loan_id', $loan->id)->firstOrFail();
    expect($penalty->status)->toBe(PenaltyStatus::PendingApproval)
        ->and($penalty->isPayableByBorrower())->toBeFalse();

    // Borrower cannot pay while pending approval
    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->assertSee('Awaiting Approval')
        ->call('openPayModal', $penalty->id)
        ->assertSet('payModal', false);

    // Admin approves & finalizes the automated penalty
    $this->actingAs($admin);

    Livewire::test(PenaltiesIndex::class)
        ->assertSee('Approve')
        ->call('openApproveModal', $penalty->id)
        ->assertSet('approveModal', true)
        ->set('approveAmount', '100.00')
        ->call('submitApprovePenalty')
        ->assertHasNoErrors()
        ->assertSet('approveModal', false);

    $penalty->refresh();
    expect($penalty->status)->toBe(PenaltyStatus::Unpaid)
        ->and((float) $penalty->amount)->toBe(100.00)
        ->and((float) $penalty->balance_remaining)->toBe(100.00)
        ->and($penalty->isPayableByBorrower())->toBeFalse(); // Still not returned!

    // Borrower still cannot pay because the item has not been returned yet
    $this->actingAs($student);

    Livewire::test(BorrowerPenalties::class)
        ->assertSee('Return Item First')
        ->call('openPayModal', $penalty->id)
        ->assertSet('payModal', false);

    // Item is returned
    $loan->markAsReturned($admin);

    expect($penalty->fresh()->isPayableByBorrower())->toBeTrue();

    // Now borrower can pay online
    Livewire::test(BorrowerPenalties::class)
        ->call('openPayModal', $penalty->id)
        ->assertSet('payModal', true)
        ->set('selectedChannelId', $channel->id)
        ->set('receiptImage', UploadedFile::fake()->image('receipt.png'))
        ->call('submitOnlinePayment')
        ->assertHasNoErrors()
        ->assertSet('payModal', false);

    expect(Payment::where('penalty_id', $penalty->id)->where('status', PaymentStatus::PendingVerification)->count())->toBe(1);
});

it('allows finance admins to click loan # on /finance/payments to inspect loan details while enforcing college scoping', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $coet = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);

    $ccsAdmin = User::factory()->create();
    $ccsAdmin->assignRole('organization_admin');
    $ccsAdmin->organizations()->attach($ccs->id, ['role' => 'admin']);

    $coetAdmin = User::factory()->create();
    $coetAdmin->assignRole('organization_admin');
    $coetAdmin->organizations()->attach($coet->id, ['role' => 'admin']);

    $student = User::factory()->create(['name' => 'Juan Dela Cruz']);
    $student->assignRole('member');
    $student->organizations()->attach($ccs->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'Oscilloscope Pro']);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $ccs->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CCS-OSC-101',
    ]);

    $loan = Loan::factory()->create([
        'organization_id' => $ccs->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'notes'           => 'Lab 2 testing',
    ]);

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $ccs->id,
        'user_id'           => $student->id,
        'loan_id'           => $loan->id,
        'item_unit_id'      => $unit->id,
        'amount'            => 200.00,
        'balance_remaining' => 200.00,
    ]);

    Payment::factory()->cash()->create([
        'organization_id' => $ccs->id,
        'penalty_id'      => $penalty->id,
        'user_id'         => $student->id,
        'amount_paid'     => 200.00,
    ]);

    $this->actingAs($ccsAdmin);

    Livewire::test(PaymentsIndex::class)
        ->assertSee('#'.$loan->id)
        ->call('openLoanModal', $loan->id)
        ->assertSet('loanModal', true)
        ->assertSee('Loan #'.$loan->id)
        ->assertSee('Juan Dela Cruz')
        ->assertSee('Oscilloscope Pro')
        ->assertSee('CCS-OSC-101')
        ->assertSee('Lab 2 testing');

    $this->actingAs($coetAdmin);

    Livewire::test(PaymentsIndex::class)
        ->call('openLoanModal', $loan->id)
        ->assertForbidden();
});
