<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Circulation;

use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use App\Enums\ReservationStatus;
use App\Livewire\Circulation\Desk;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Auth;
use Database\Seeders\InventorySeeder;
use Database\Seeders\CirculationSeeder;
use Database\Seeders\OrganizationSeeder;
use App\Livewire\Circulation\ActiveLoans;
use Database\Seeders\PaymentMethodSeeder;
use App\Livewire\Circulation\BookingRequests;
use App\Livewire\Circulation\OverdueTracking;
use Database\Seeders\RoleAndPermissionSeeder;
use App\Livewire\Circulation\AvailabilityCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RoleAndPermissionSeeder::class,
        PaymentMethodSeeder::class,
        OrganizationSeeder::class,
        CategorySeeder::class,
        InventorySeeder::class,
        CirculationSeeder::class,
    ]);

    $this->ccsAdmin   = User::where('email', 'admin.ccs@equilend.test')->firstOrFail();
    $this->coetAdmin  = User::where('email', 'admin.coet@equilend.test')->firstOrFail();
    $this->superadmin = User::where('email', 'superadmin@equilend.test')->firstOrFail();
    $this->ccsOrg     = Organization::where('code', 'CCS')->firstOrFail();
    $this->coetOrg    = Organization::where('code', 'COET')->firstOrFail();
    $this->ccsStudent = User::factory()->create();
    $this->ccsStudent->assignRole('member');
    $this->ccsStudent->organizations()->attach($this->ccsOrg->id, ['role' => 'member']);
});

it('renders the circulation desk component successfully for authenticated staff', function () {
    Auth::login($this->ccsAdmin);

    Livewire::test(Desk::class)
        ->assertOk()
        ->assertViewIs('livewire.circulation.desk');
});

it('allows circulation desk checkout to create loan and change unit status to borrowed', function () {
    Auth::login($this->ccsAdmin);

    // Find an available CCS unit
    $unit = ItemUnit::where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    Livewire::test(Desk::class)
        ->set('selectedUserId', $this->ccsStudent->id)
        ->set('selectedItemId', $unit->item_id)
        ->set('selectedUnitId', $unit->id)
        ->set('dueAt', now()->addDays(2)->setTime(17, 0)->format('Y-m-d\TH:i'))
        ->set('checkoutCondition', 'good')
        ->set('checkoutNotes', 'Walk-in circulation checkout for lab testing.')
        ->call('processCheckout')
        ->assertDispatched('alert:dialog');

    // Verify database state
    expect($unit->fresh()->status)->toBe(ItemUnitStatus::Borrowed);

    $loan = Loan::where('item_unit_id', $unit->id)
        ->where('user_id', $this->ccsStudent->id)
        ->where('status', LoanStatus::Active)
        ->first();

    expect($loan)->not->toBeNull()
        ->and($loan->issued_by)->toBe($this->ccsAdmin->id)
        ->and($loan->checkout_condition)->toBe(ItemUnitCondition::Good)
        ->and($loan->notes)->toBe('Walk-in circulation checkout for lab testing.');
});

it('fulfills an approved reservation during desk checkout and transitions reservation to checked_out', function () {
    Auth::login($this->ccsAdmin);

    $unit = ItemUnit::where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    $reservation = Reservation::factory()->approved($this->ccsAdmin, $unit)->create([
        'organization_id' => $this->ccsOrg->id,
        'user_id'         => $this->ccsStudent->id,
        'item_id'         => $unit->item_id,
        'item_unit_id'    => $unit->id,
    ]);

    expect($reservation->isApproved())->toBeTrue();

    Livewire::test(Desk::class)
        ->call('applyReservation', $reservation->id)
        ->assertSet('selectedReservationId', $reservation->id)
        ->assertSet('selectedUserId', $this->ccsStudent->id)
        ->assertSet('selectedItemId', $unit->item_id)
        ->assertSet('selectedUnitId', $unit->id)
        ->call('processCheckout')
        ->assertDispatched('alert:dialog');

    expect($reservation->fresh()->isCheckedOut())->toBeTrue()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Borrowed);
});

it('allows desk return check-in to restore unit status to available in good condition', function () {
    Auth::login($this->ccsAdmin);

    $activeLoan = Loan::where('organization_id', $this->ccsOrg->id)
        ->where('status', LoanStatus::Active)
        ->firstOrFail();

    $unit = $activeLoan->itemUnit;
    expect($unit->status)->toBe(ItemUnitStatus::Borrowed);

    Livewire::test(Desk::class)
        ->set('mode', 'checkin')
        ->call('inspectLoan', $activeLoan->id)
        ->assertSet('inspectingLoanId', $activeLoan->id)
        ->set('checkinCondition', 'good')
        ->set('checkinNotes', 'Unit returned on time and intact.')
        ->call('processCheckin')
        ->assertDispatched('alert:dialog');

    expect($activeLoan->fresh()->status)->toBe(LoanStatus::Returned)
        ->and($activeLoan->fresh()->received_by)->toBe($this->ccsAdmin->id)
        ->and($activeLoan->fresh()->returned_at)->not->toBeNull()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Available);
});

it('marks loan and physical unit as damaged when checkin inspection identifies damage', function () {
    Auth::login($this->ccsAdmin);

    $activeLoan = Loan::where('organization_id', $this->ccsOrg->id)
        ->where('status', LoanStatus::Active)
        ->firstOrFail();

    $unit = $activeLoan->itemUnit;

    Livewire::test(Desk::class)
        ->set('mode', 'checkin')
        ->call('inspectLoan', $activeLoan->id)
        ->set('isDamaged', true)
        ->set('checkinCondition', 'poor')
        ->set('checkinNotes', 'Screen cracked and hinge dislocated.')
        ->call('processCheckin')
        ->assertDispatched('alert:dialog');

    expect($activeLoan->fresh()->status)->toBe(LoanStatus::Damaged)
        ->and($activeLoan->fresh()->checkin_condition)->toBe(ItemUnitCondition::Poor)
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Damaged)
        ->and($unit->fresh()->condition)->toBe(ItemUnitCondition::Poor);
});

it('enforces mandatory rejection remarks when rejecting a reservation request', function () {
    Auth::login($this->ccsAdmin);

    $pendingReservation = Reservation::where('organization_id', $this->ccsOrg->id)
        ->where('status', ReservationStatus::Pending)
        ->firstOrFail();

    // Attempting rejection with blank remarks fails validation
    Livewire::test(BookingRequests::class)
        ->call('openRejectModal', $pendingReservation->id)
        ->set('rejectionRemarks', '')
        ->call('confirmRejection')
        ->assertHasErrors(['rejectionRemarks']);

    expect($pendingReservation->fresh()->isPending())->toBeTrue();

    // Rejection with valid remarks succeeds
    Livewire::test(BookingRequests::class)
        ->set('rejectingReservationId', $pendingReservation->id)
        ->set('rejectionRemarks', 'Laboratory facility closed for university accreditation review.')
        ->call('confirmRejection')
        ->assertDispatched('alert:dialog');

    expect($pendingReservation->fresh()->isRejected())->toBeTrue()
        ->and($pendingReservation->fresh()->rejection_remarks)->toBe('Laboratory facility closed for university accreditation review.')
        ->and($pendingReservation->fresh()->approved_by)->toBe($this->ccsAdmin->id);
});

it('requires a physical unit when approving a pending reservation and reserves it', function () {
    Auth::login($this->ccsAdmin);

    $pendingReservation = Reservation::where('organization_id', $this->ccsOrg->id)
        ->where('status', ReservationStatus::Pending)
        ->firstOrFail();

    $unit = ItemUnit::where('item_id', $pendingReservation->item_id)
        ->where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->first()
        ?? ItemUnit::factory()->forItem($pendingReservation->item)->available()->create();

    $initialAvailableCount = $pendingReservation->item->available_units_count;

    // Attempting approval without selecting a physical unit fails validation
    Livewire::test(BookingRequests::class)
        ->call('openApproveModal', $pendingReservation->id)
        ->set('assignedUnitId', null)
        ->call('confirmApproval')
        ->assertHasErrors(['assignedUnitId']);

    expect($pendingReservation->fresh()->isPending())->toBeTrue();

    // Approval with a physical unit succeeds and deducts available units
    Livewire::test(BookingRequests::class)
        ->call('openApproveModal', $pendingReservation->id)
        ->set('assignedUnitId', $unit->id)
        ->call('confirmApproval')
        ->assertHasNoErrors()
        ->assertDispatched('alert:dialog');

    expect($pendingReservation->fresh()->isApproved())->toBeTrue()
        ->and($pendingReservation->fresh()->approved_by)->toBe($this->ccsAdmin->id)
        ->and($pendingReservation->fresh()->item_unit_id)->toBe($unit->id)
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Reserved)
        ->and($pendingReservation->item->fresh()->available_units_count)->toBe($initialAvailableCount - 1);
});

it('shows a missed pickup suggestion for overdue approved bookings and lets admin cancel to release the unit', function () {
    Auth::login($this->ccsAdmin);

    $item = \App\Models\Item::where('organization_id', $this->ccsOrg->id)->firstOrFail();
    $unit = ItemUnit::factory()->forItem($item)->available()->create();

    $missedReservation = Reservation::factory()->create([
        'organization_id' => $this->ccsOrg->id,
        'user_id'         => $this->ccsStudent->id,
        'item_id'         => $item->id,
        'start_time'      => now()->subDay(),
        'end_time'        => now()->addDay(),
        'status'          => ReservationStatus::Pending,
    ]);
    $missedReservation->approve($this->ccsAdmin, $unit);

    expect($missedReservation->fresh()->isMissedPickup())->toBeTrue()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Reserved);

    Livewire::test(BookingRequests::class)
        ->set('statusFilter', 'approved')
        ->assertSee('Not Picked Up')
        ->assertSee('Pickup missed — cancel to release unit')
        ->call('openCancelModal', $missedReservation->id)
        ->call('confirmCancellation')
        ->assertDispatched('alert:dialog');

    expect($missedReservation->fresh()->isCancelled())->toBeTrue()
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Available);
});

it('isolates overdue loans with the overdue tracker filter', function () {
    Auth::login($this->ccsAdmin);

    // ActiveLoans overdue filter
    Livewire::test(ActiveLoans::class)
        ->set('statusFilter', 'overdue')
        ->assertSee('Overdue by')
        ->assertDontSee('Due in');

    // OverdueTracking view
    Livewire::test(OverdueTracking::class)
        ->assertOk()
        ->assertSee('Past-Due Equipment Ledger');
});

it('enforces college scoping so COET admin cannot check out or access CCS loans', function () {
    Auth::login($this->coetAdmin);

    $ccsUnit = ItemUnit::where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    // Attempt checkout of CCS unit by COET admin
    Livewire::test(Desk::class)
        ->set('selectedUserId', $this->ccsStudent->id)
        ->set('selectedItemId', $ccsUnit->item_id)
        ->set('selectedUnitId', $ccsUnit->id)
        ->set('dueAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('processCheckout')
        ->assertDispatched('alert:dialog');

    // Unit must still be Available (checkout blocked)
    expect($ccsUnit->fresh()->status)->toBe(ItemUnitStatus::Available);

    // Attempt inspecting a CCS loan by COET admin results in 403
    $ccsLoan = Loan::where('organization_id', $this->ccsOrg->id)->firstOrFail();

    Livewire::test(Desk::class)
        ->call('inspectLoan', $ccsLoan->id)
        ->assertForbidden();
});

it('renders the availability calendar and displays scheduled events and blackouts', function () {
    Auth::login($this->ccsAdmin);

    Livewire::test(AvailabilityCalendar::class)
        ->assertOk()
        ->assertSee('Equipment Availability Calendar')
        ->assertSee('Approved Booking')
        ->assertSee('Maintenance Window')
        ->assertSee('College Blackout Period');
});

it('forbids borrowers without staff privileges from accessing circulation desk', function () {
    Auth::login($this->ccsStudent);

    Livewire::test(Desk::class)
        ->assertForbidden();

    Livewire::test(BookingRequests::class)
        ->assertForbidden();

    Livewire::test(ActiveLoans::class)
        ->assertForbidden();

    Livewire::test(OverdueTracking::class)
        ->assertForbidden();

    Livewire::test(AvailabilityCalendar::class)
        ->assertForbidden();
});

it('supports barcode scanning to auto-select physical units at the desk', function () {
    Auth::login($this->ccsAdmin);

    $unit = ItemUnit::where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    Livewire::test(Desk::class)
        ->set('unitSearch', $unit->asset_tag)
        ->assertSet('selectedItemId', $unit->item_id)
        ->assertSet('selectedUnitId', $unit->id)
        ->assertDispatched('alert:dialog');
});

it('allows sending reminders to overdue borrowers via overdue tracking', function () {
    Auth::login($this->ccsAdmin);

    $overdueLoan = Loan::where('organization_id', $this->ccsOrg->id)
        ->where('status', LoanStatus::Overdue)
        ->firstOrFail();

    Livewire::test(OverdueTracking::class)
        ->call('sendReminder', $overdueLoan->id)
        ->assertDispatched('alert:dialog');
});

it('supports month navigation and event inspection on availability calendar', function () {
    Auth::login($this->ccsAdmin);

    $component = Livewire::test(AvailabilityCalendar::class);

    $initialMonth = $component->get('month');

    $component->call('nextMonth');
    expect($component->get('month'))->not->toBe($initialMonth);

    $component->call('previousMonth');
    expect($component->get('month'))->toBe($initialMonth);

    $component->call('goToToday');
    expect($component->get('month'))->toBe(now()->month);
});

it('searches borrowers across all colleges with minimum 2 characters and limit 10', function () {
    Auth::login($this->ccsAdmin);

    $coetBorrower = User::factory()->create([
        'name'  => 'CrossCollege StudentZ',
        'email' => 'crosscollege.z@equilend.test',
    ]);
    $coetBorrower->assignRole('member');
    $coetBorrower->organizations()->attach($this->coetOrg->id, ['role' => 'member']);

    $component = Livewire::test(Desk::class);

    // Fewer than 2 characters returns no results
    $component->set('studentSearch', 'C');
    expect($component->instance()->students)->toHaveCount(0);

    // 2+ characters finds borrower from another college (COET) for CCS desk
    $component->set('studentSearch', 'CrossCollege');
    expect($component->instance()->students->pluck('id'))->toContain($coetBorrower->id);

    $component->assertSee('CrossCollege StudentZ')
        ->assertSee('COET')
        ->call('selectStudent', $coetBorrower->id)
        ->assertSet('selectedUserId', $coetBorrower->id);
});

it('supports walk-in vs reservation mode toggle and searchable approved reservations modal', function () {
    Auth::login($this->ccsAdmin);

    $unit = ItemUnit::where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    $targetRes = Reservation::factory()->approved($this->ccsAdmin, $unit)->create([
        'organization_id' => $this->ccsOrg->id,
        'user_id'         => $this->ccsStudent->id,
        'item_id'         => $unit->item_id,
        'item_unit_id'    => $unit->id,
    ]);

    $component = Livewire::test(Desk::class)
        ->assertSet('checkoutSource', 'walkin')
        ->call('setCheckoutSource', 'reservation')
        ->assertSet('checkoutSource', 'reservation')
        ->assertSet('reservationModal', true)
        ->set('reservationSearch', '#'.$targetRes->id)
        ->assertSee('#'.$targetRes->id)
        ->call('applyReservation', $targetRes->id)
        ->assertSet('reservationModal', false)
        ->assertSet('selectedReservationId', $targetRes->id)
        ->call('clearReservation')
        ->assertSet('selectedReservationId', null)
        ->assertSet('checkoutSource', 'walkin');
});

it('supports equipment item and asset tag typeahead selection at the desk', function () {
    Auth::login($this->ccsAdmin);

    $unit = ItemUnit::with('item')
        ->where('organization_id', $this->ccsOrg->id)
        ->where('status', ItemUnitStatus::Available)
        ->firstOrFail();

    $itemPrefix = mb_substr($unit->item->name, 0, 3);
    $tagPrefix  = mb_substr($unit->asset_tag, 0, 4);

    Livewire::test(Desk::class)
        ->set('itemSearch', $itemPrefix)
        ->assertSee($unit->item->name)
        ->call('selectItem', $unit->item_id)
        ->assertSet('selectedItemId', $unit->item_id)
        ->call('clearSelectedItem')
        ->assertSet('selectedItemId', null)
        ->set('unitSearch', $tagPrefix)
        ->assertSee($unit->asset_tag)
        ->call('selectUnit', $unit->id)
        ->assertSet('selectedUnitId', $unit->id)
        ->assertSet('selectedItemId', $unit->item_id)
        ->call('clearSelectedUnit')
        ->assertSet('selectedUnitId', null);
});
