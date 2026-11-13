<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use App\Enums\ReservationStatus;
use App\Models\EquipmentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('reservation belongs to organization, user, item, and approved_by with correct casts', function () {
    $college = Organization::factory()->create(['name' => 'College of Computer Studies', 'code' => 'CCS']);
    $student = User::factory()->create(['name' => 'Borrower Student']);
    $admin   = User::factory()->create(['name' => 'CCS Admin']);
    $item    = Item::factory()->forOrganization($college)->create(['name' => 'Sony Alpha A7 IV']);
    $unit    = ItemUnit::factory()->forItem($item)->available()->create();

    $reservation = Reservation::factory()->create([
        'organization_id' => $college->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'start_time'      => now()->addDay(),
        'end_time'        => now()->addDays(3),
        'purpose'         => 'Capstone Project Defense Video Documentation',
        'status'          => ReservationStatus::Pending,
    ]);

    expect($reservation->organization->id)->toBe($college->id)
        ->and($reservation->user->id)->toBe($student->id)
        ->and($reservation->borrower->id)->toBe($student->id)
        ->and($reservation->item->id)->toBe($item->id)
        ->and($reservation->itemUnit->id)->toBe($unit->id)
        ->and($reservation->status)->toBe(ReservationStatus::Pending)
        ->and($reservation->status->label())->toBe('Pending')
        ->and($reservation->status->badgeClass())->toBe('badge-warning')
        ->and($reservation->isPending())->toBeTrue()
        ->and($reservation->isApproved())->toBeFalse();

    // Test approve transition
    $reservation->approve($admin, $unit);

    expect($reservation->fresh()->isApproved())->toBeTrue()
        ->and($reservation->fresh()->approvedBy->id)->toBe($admin->id)
        ->and($reservation->fresh()->approved_at)->not->toBeNull()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Reserved);
});

test('reservation reject enforces mandatory rejection remarks', function () {
    $admin       = User::factory()->create();
    $reservation = Reservation::factory()->pending()->create();

    // Attempting to reject without reason throws exception
    expect(fn () => $reservation->reject($admin, ''))
        ->toThrow(InvalidArgumentException::class, 'Rejection remarks are mandatory');

    $reservation->reject($admin, 'Equipment is undergoing scheduled lens calibration.');

    expect($reservation->fresh()->isRejected())->toBeTrue()
        ->and($reservation->fresh()->rejection_remarks)->toBe('Equipment is undergoing scheduled lens calibration.')
        ->and($reservation->fresh()->approved_by)->toBe($admin->id);
});

test('loan belongs to organization, user, staff, item, unit, and reservation', function () {
    $college     = Organization::factory()->create();
    $borrower    = User::factory()->create();
    $staffIssuer = User::factory()->create();
    $item        = Item::factory()->forOrganization($college)->create();
    $unit        = ItemUnit::factory()->forItem($item)->available()->create();
    $reservation = Reservation::factory()->approved()->create([
        'organization_id' => $college->id,
        'user_id'         => $borrower->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
    ]);

    $loan = Loan::checkOut([
        'organization_id'    => $college->id,
        'reservation_id'     => $reservation->id,
        'user_id'            => $borrower->id,
        'item_id'            => $item->id,
        'item_unit_id'       => $unit->id,
        'issued_by'          => $staffIssuer->id,
        'borrowed_at'        => now(),
        'due_at'             => now()->addDays(2),
        'checkout_condition' => ItemUnitCondition::Good,
    ]);

    expect($loan->organization->id)->toBe($college->id)
        ->and($loan->user->id)->toBe($borrower->id)
        ->and($loan->borrower->id)->toBe($borrower->id)
        ->and($loan->issuedBy->id)->toBe($staffIssuer->id)
        ->and($loan->item->id)->toBe($item->id)
        ->and($loan->itemUnit->id)->toBe($unit->id)
        ->and($loan->reservation->id)->toBe($reservation->id)
        ->and($loan->status)->toBe(LoanStatus::Active)
        ->and($reservation->fresh()->isCheckedOut())->toBeTrue();
});

test('physical unit status changes automatically to borrowed upon checkout', function () {
    $item = Item::factory()->create();
    $unit = ItemUnit::factory()->forItem($item)->available()->create();

    expect($unit->status)->toBe(ItemUnitStatus::Available);

    $loan = Loan::checkOut([
        'item_unit_id' => $unit->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]);

    expect($unit->fresh()->status)->toBe(ItemUnitStatus::Borrowed)
        ->and($loan->status)->toBe(LoanStatus::Active);
});

test('physical unit status restores to available when returned in good condition', function () {
    $receiver = User::factory()->create();
    $unit     = ItemUnit::factory()->available()->create();

    $loan = Loan::checkOut([
        'item_unit_id' => $unit->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]);

    expect($unit->fresh()->status)->toBe(ItemUnitStatus::Borrowed);

    // Return the unit
    $loan->markAsReturned($receiver, ItemUnitCondition::Good, 'Unit returned on time and fully functional.');

    expect($loan->fresh()->status)->toBe(LoanStatus::Returned)
        ->and($loan->fresh()->checkin_condition)->toBe(ItemUnitCondition::Good)
        ->and($loan->fresh()->received_by)->toBe($receiver->id)
        ->and($loan->fresh()->returned_at)->not->toBeNull()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Available)
        ->and($unit->fresh()->condition)->toBe(ItemUnitCondition::Good);
});

test('physical unit status transitions to damaged when returned in poor condition', function () {
    $receiver = User::factory()->create();
    $unit     = ItemUnit::factory()->available()->create();

    $loan = Loan::checkOut([
        'item_unit_id' => $unit->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]);

    $loan->markAsReturned($receiver, ItemUnitCondition::Poor, 'Lens scratched and shutter button loose.');

    expect($loan->fresh()->status)->toBe(LoanStatus::Damaged)
        ->and($loan->fresh()->checkin_condition)->toBe(ItemUnitCondition::Poor)
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Damaged)
        ->and($unit->fresh()->condition)->toBe(ItemUnitCondition::Poor);
});

test('validation prevents checkout of an already borrowed unit', function () {
    $unit = ItemUnit::factory()->available()->create();

    // First checkout succeeds
    Loan::checkOut([
        'item_unit_id' => $unit->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]);

    expect($unit->fresh()->status)->toBe(ItemUnitStatus::Borrowed);

    // Second checkout attempt on the same unit must be blocked
    expect(fn () => Loan::checkOut([
        'item_unit_id' => $unit->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]))->toThrow(DomainException::class);
});

test('validation prevents checkout of unit under maintenance, damaged, or lost', function () {
    $maintenanceUnit = ItemUnit::factory()->maintenance()->create();
    $damagedUnit     = ItemUnit::factory()->damaged()->create();
    $lostUnit        = ItemUnit::factory()->create(['status' => ItemUnitStatus::Lost]);

    $user  = User::factory()->create();
    $staff = User::factory()->create();

    expect(fn () => Loan::checkOut([
        'item_unit_id' => $maintenanceUnit->id,
        'user_id'      => $user->id,
        'issued_by'    => $staff->id,
        'due_at'       => now()->addDays(2),
    ]))->toThrow(DomainException::class);

    expect(fn () => Loan::checkOut([
        'item_unit_id' => $damagedUnit->id,
        'user_id'      => $user->id,
        'issued_by'    => $staff->id,
        'due_at'       => now()->addDays(2),
    ]))->toThrow(DomainException::class);

    expect(fn () => Loan::checkOut([
        'item_unit_id' => $lostUnit->id,
        'user_id'      => $user->id,
        'issued_by'    => $staff->id,
        'due_at'       => now()->addDays(2),
    ]))->toThrow(DomainException::class);
});

test('loan overdue helper and scopes work as expected', function () {
    $unit1 = ItemUnit::factory()->available()->create();
    $unit2 = ItemUnit::factory()->available()->create();
    $unit3 = ItemUnit::factory()->available()->create();

    // Active, not overdue
    $activeLoan = Loan::checkOut([
        'item_unit_id' => $unit1->id,
        'user_id'      => User::factory()->create()->id,
        'issued_by'    => User::factory()->create()->id,
        'due_at'       => now()->addDays(2),
    ]);

    // Active with due_at in the past (overdue dynamically)
    $overdueLoan = Loan::factory()->forUnit($unit2)->create([
        'status'      => LoanStatus::Active,
        'borrowed_at' => now()->subDays(4),
        'due_at'      => now()->subDays(1),
        'returned_at' => null,
    ]);

    // Explicitly marked overdue
    $explicitOverdue = Loan::factory()->forUnit($unit3)->overdue()->create();

    expect($activeLoan->isOverdue())->toBeFalse()
        ->and($overdueLoan->isOverdue())->toBeTrue()
        ->and($explicitOverdue->isOverdue())->toBeTrue();

    expect(Loan::active()->count())->toBe(2)
        ->and(Loan::overdue()->count())->toBe(2);
});

test('equipment schedule tracks maintenance and blackout windows with relationships', function () {
    $college = Organization::factory()->create();
    $item    = Item::factory()->forOrganization($college)->create();
    $unit    = ItemUnit::factory()->forItem($item)->create();

    $maintenance = EquipmentSchedule::factory()->maintenance()->forOrganization($college)->forItem($item)->forUnit($unit)->create([
        'title'      => 'Annual Sensor Calibration Window',
        'start_time' => now()->addDays(1),
        'end_time'   => now()->addDays(3),
    ]);

    $blackout = EquipmentSchedule::factory()->blackout()->forOrganization($college)->create([
        'title'      => 'Final Examination Week Blackout',
        'start_time' => now()->addDays(10),
        'end_time'   => now()->addDays(17),
    ]);

    expect($maintenance->organization->id)->toBe($college->id)
        ->and($maintenance->item->id)->toBe($item->id)
        ->and($maintenance->itemUnit->id)->toBe($unit->id)
        ->and($maintenance->isMaintenance())->toBeTrue()
        ->and($maintenance->isBlackout())->toBeFalse()
        ->and($blackout->isBlackout())->toBeTrue()
        ->and($blackout->item_id)->toBeNull()
        ->and(EquipmentSchedule::maintenance()->count())->toBe(1)
        ->and(EquipmentSchedule::blackout()->count())->toBe(1);
});

test('model factories create valid states across circulation domain', function () {
    $pendingRes    = Reservation::factory()->pending()->create();
    $approvedRes   = Reservation::factory()->approved()->create();
    $rejectedRes   = Reservation::factory()->rejected('Schedule conflict')->create();
    $checkedOutRes = Reservation::factory()->checkedOut()->create();
    $expiredRes    = Reservation::factory()->expired()->create();

    expect($pendingRes->status)->toBe(ReservationStatus::Pending)
        ->and($approvedRes->status)->toBe(ReservationStatus::Approved)
        ->and($rejectedRes->status)->toBe(ReservationStatus::Rejected)
        ->and($checkedOutRes->status)->toBe(ReservationStatus::CheckedOut)
        ->and($expiredRes->status)->toBe(ReservationStatus::Expired);

    $activeLoan   = Loan::factory()->active()->create();
    $returnedLoan = Loan::factory()->returned()->create();
    $overdueLoan  = Loan::factory()->overdue()->create();
    $damagedLoan  = Loan::factory()->damaged()->create();
    $lostLoan     = Loan::factory()->lost()->create();

    expect($activeLoan->status)->toBe(LoanStatus::Active)
        ->and($activeLoan->itemUnit->status)->toBe(ItemUnitStatus::Borrowed)
        ->and($returnedLoan->status)->toBe(LoanStatus::Returned)
        ->and($returnedLoan->itemUnit->status)->toBe(ItemUnitStatus::Available)
        ->and($overdueLoan->status)->toBe(LoanStatus::Overdue)
        ->and($overdueLoan->itemUnit->status)->toBe(ItemUnitStatus::Borrowed)
        ->and($damagedLoan->status)->toBe(LoanStatus::Damaged)
        ->and($damagedLoan->itemUnit->status)->toBe(ItemUnitStatus::Damaged)
        ->and($lostLoan->status)->toBe(LoanStatus::Lost)
        ->and($lostLoan->itemUnit->status)->toBe(ItemUnitStatus::Lost);
});

test('reverse relationships from User, College, Item, and ItemUnit to circulation models resolve properly', function () {
    $college = Organization::factory()->create();
    $student = User::factory()->create();
    $staff   = User::factory()->create();
    $item    = Item::factory()->forOrganization($college)->create();
    $unit    = ItemUnit::factory()->forItem($item)->available()->create();

    $reservation = Reservation::factory()->create([
        'organization_id' => $college->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'approved_by'     => $staff->id,
    ]);

    $loan = Loan::checkOut([
        'organization_id' => $college->id,
        'reservation_id'  => $reservation->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $staff->id,
        'due_at'          => now()->addDays(2),
    ]);

    $schedule = EquipmentSchedule::factory()->maintenance()->create([
        'organization_id' => $college->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
    ]);

    // Reverse relationships
    expect($college->reservations)->toHaveCount(1)
        ->and($college->loans)->toHaveCount(1)
        ->and($college->equipmentSchedules)->toHaveCount(1)
        ->and($item->reservations)->toHaveCount(1)
        ->and($item->loans)->toHaveCount(1)
        ->and($item->equipmentSchedules)->toHaveCount(1)
        ->and($unit->reservations)->toHaveCount(1) // checkout fulfilled and linked the unit to the reservation
        ->and($unit->loans)->toHaveCount(1)
        ->and($unit->equipmentSchedules)->toHaveCount(1)
        ->and($unit->activeLoan->id)->toBe($loan->id)
        ->and($student->reservations)->toHaveCount(1)
        ->and($student->loans)->toHaveCount(1)
        ->and($staff->issuedLoans)->toHaveCount(1)
        ->and($staff->approvedReservations)->toHaveCount(1);
});
