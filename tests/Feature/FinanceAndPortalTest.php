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
use App\Enums\PenaltyType;
use App\Models\Reservation;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Livewire\Circulation\Desk;
use Spatie\Permission\Models\Role;
use App\Livewire\Borrower\BrowseEquipment;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Penalties\Index as PenaltiesIndex;
use App\Livewire\Borrower\Penalties as BorrowerPenalties;
use App\Livewire\Borrower\ActiveLoans as BorrowerActiveLoans;
use App\Livewire\Borrower\Reservations as BorrowerReservations;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('blocks suspended students from checking out equipment at the circulation desk', function (): void {
    $org   = Organization::factory()->create(['code' => 'CCS']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id]);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
    ]);

    // Give student an unpaid penalty
    Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 500.00,
        'balance_remaining' => 500.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(Desk::class)
        ->set('selectedUserId', $student->id)
        ->set('selectedUnitId', $unit->id)
        ->set('dueAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('processCheckout')
        ->assertDispatched('alert:dialog');

    expect(Loan::where('user_id', $student->id)->count())->toBe(0);
});

it('allows staff to assess a damage penalty during check-in return', function (): void {
    $org   = Organization::factory()->create(['code' => 'COET']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

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
        'status'          => LoanStatus::Active,
        'due_at'          => now()->addDay(),
        'returned_at'     => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(Desk::class)
        ->call('setMode', 'checkin')
        ->call('inspectLoan', $loan->id)
        ->set('isDamaged', true)
        ->set('assessPenalty', true)
        ->set('penaltyAmount', '600')
        ->set('penaltyNotes', 'Broken probe clip on return')
        ->call('processCheckin')
        ->assertHasNoErrors();

    expect(Penalty::where('loan_id', $loan->id)->count())->toBe(1);

    $penalty = Penalty::where('loan_id', $loan->id)->first();
    expect($penalty->type)->toBe(PenaltyType::Damage)
        ->and((float) $penalty->amount)->toBe(600.00)
        ->and($student->fresh()->canBorrow())->toBeFalse();
});

it('records a payment, reduces balance, and issues a receipt number', function (): void {
    $org   = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $student = User::factory()->create();
    $student->assignRole('member');

    $cash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'cash'],
        ['name' => 'Cash', 'description' => 'Cash Payment', 'is_active' => true]
    );

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 1000.00,
        'balance_remaining' => 1000.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(PenaltiesIndex::class)
        ->call('openPaymentModal', $penalty->id)
        ->set('paymentMethodId', $cash->id)
        ->set('paymentAmount', '400')
        ->call('submitPayment')
        ->assertHasNoErrors();

    $penalty->refresh();

    expect((float) $penalty->paid_amount)->toBe(400.00)
        ->and((float) $penalty->balance_remaining)->toBe(600.00)
        ->and($penalty->status)->toBe(PenaltyStatus::PartiallyPaid);

    $payment = Payment::where('penalty_id', $penalty->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->receipt_number)->toMatch('/^RCP-\d{4}-\d{4}$/');
});

it('requires administrative remarks when waiving a penalty', function (): void {
    $org   = Organization::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'amount'            => 300.00,
        'balance_remaining' => 300.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(PenaltiesIndex::class)
        ->call('openWaiveModal', $penalty->id, 'waive')
        ->set('waiveReason', '')
        ->call('submitWaiveOrVoid')
        ->assertHasErrors(['waiveReason']);

    Livewire::test(PenaltiesIndex::class)
        ->call('openWaiveModal', $penalty->id, 'waive')
        ->set('waiveReason', 'Approved medical excuse from department')
        ->call('submitWaiveOrVoid')
        ->assertHasNoErrors();

    expect($penalty->fresh()->status)->toBe(PenaltyStatus::Waived)
        ->and((float) $penalty->fresh()->balance_remaining)->toBe(0.00);
});

it('enforces college multi-tenancy on penalties and payments', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS']);
    $coet = Organization::factory()->create(['code' => 'COET']);

    $ccsAdmin = User::factory()->create();
    $ccsAdmin->assignRole('organization_admin');
    $ccsAdmin->organizations()->attach($ccs->id, ['role' => 'admin']);

    $member = User::factory()->create();
    $member->assignRole('member');

    $coetPenalty = Penalty::factory()->unpaid()->create([
        'organization_id'  => $coet->id,
        'assessment_notes' => 'COET Confidential Damage Note',
    ]);

    $coetPayment = Payment::factory()->cash()->create([
        'organization_id' => $coet->id,
        'penalty_id'      => $coetPenalty->id,
    ]);

    // Member cannot access staff finance pages
    $this->actingAs($member)
        ->get(route('finance.penalties'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('finance.payments'))
        ->assertForbidden();

    // CCS admin only sees CCS records, not COET records
    $this->actingAs($ccsAdmin);

    Livewire::test(PenaltiesIndex::class)
        ->assertDontSee('COET Confidential Damage Note');

    Livewire::test(PaymentsIndex::class)
        ->assertDontSee($coetPayment->receipt_number);
});

it('scopes borrower portal views to the authenticated student and blocks suspended bookings', function (): void {
    $org = Organization::factory()->create(['code' => 'CCS']);

    $studentA = User::factory()->create(['name' => 'Student Alpha']);
    $studentA->assignRole('member');
    $studentA->organizations()->attach($org->id, ['role' => 'member']);

    $studentB = User::factory()->create(['name' => 'Student Beta']);
    $studentB->assignRole('member');
    $studentB->organizations()->attach($org->id, ['role' => 'member']);

    $itemA = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Alpha Oscilloscope']);
    $itemB = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Beta Microscope']);

    $unitA = ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $itemA->id]);
    $unitB = ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $itemB->id]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $studentA->id,
        'item_id'         => $itemA->id,
        'item_unit_id'    => $unitA->id,
        'status'          => LoanStatus::Active,
        'due_at'          => now()->addDay(),
        'returned_at'     => null,
    ]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $studentB->id,
        'item_id'         => $itemB->id,
        'item_unit_id'    => $unitB->id,
        'status'          => LoanStatus::Active,
        'due_at'          => now()->addDay(),
        'returned_at'     => null,
    ]);

    Penalty::factory()->unpaid()->create([
        'organization_id'  => $org->id,
        'user_id'          => $studentA->id,
        'assessment_notes' => 'Alpha Fine Reason',
        'amount'           => 250.00,
    ]);

    Penalty::factory()->unpaid()->create([
        'organization_id'  => $org->id,
        'user_id'          => $studentB->id,
        'assessment_notes' => 'Beta Secret Fine',
        'amount'           => 999.00,
    ]);

    $pendingResA = Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $studentA->id,
        'item_id'         => $itemA->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $this->actingAs($studentA);

    Livewire::test(BorrowerActiveLoans::class)
        ->assertSee('Alpha Oscilloscope')
        ->assertDontSee('Beta Microscope');

    Livewire::test(BorrowerPenalties::class)
        ->assertSee('Alpha Fine Reason')
        ->assertDontSee('Beta Secret Fine');

    Livewire::test(BorrowerReservations::class)
        ->assertSee('Alpha Oscilloscope')
        ->call('cancelReservation', $pendingResA->id);

    expect($pendingResA->fresh()->status)->toBe(ReservationStatus::Cancelled);

    // Suspended Student A cannot submit a new booking request
    $availableItem = Item::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
    ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $availableItem->id]);

    $beforeCount = Reservation::where('user_id', $studentA->id)->count();

    Livewire::test(BrowseEquipment::class)
        ->set('bookingItemId', $availableItem->id)
        ->set('bookingStartTime', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('bookingEndTime', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('bookingPurpose', 'Lab project testing')
        ->call('submitBooking')
        ->assertDispatched('alert:dialog');

    expect(Reservation::where('user_id', $studentA->id)->count())->toBe($beforeCount);
});

it('forbids members from accessing staff inventory and allows eligible borrowers to reserve via portal browse', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Digital Multimeter',
        'is_active'       => true,
    ]);
    ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
    ]);

    $this->actingAs($student)
        ->get(route('inventory.index'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('portal.browse'))
        ->assertOk()
        ->assertSee('Digital Multimeter')
        ->assertDontSee('Restock');

    Livewire::test(BrowseEquipment::class)
        ->call('openBookingModal', $item->id)
        ->assertSet('bookingModal', true)
        ->set('bookingStartTime', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('bookingEndTime', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('bookingPurpose', 'Electronics lab measurement')
        ->call('submitBooking')
        ->assertHasNoErrors();

    expect(Reservation::where('user_id', $student->id)->where('item_id', $item->id)->count())->toBe(1);
});
