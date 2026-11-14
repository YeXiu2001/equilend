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
use App\Enums\ItemUnitStatus;
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
        ->assertHasErrors(['paymentAmount']);

    Livewire::test(PenaltiesIndex::class)
        ->call('openPaymentModal', $penalty->id)
        ->set('paymentMethodId', $cash->id)
        ->set('paymentAmount', '1000')
        ->call('submitPayment')
        ->assertHasNoErrors();

    $penalty->refresh();

    expect((float) $penalty->paid_amount)->toBe(1000.00)
        ->and((float) $penalty->balance_remaining)->toBe(0.00)
        ->and($penalty->status)->toBe(PenaltyStatus::Paid);

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

it('enforces enabled payment channels on finance penalties and hides cash from student portal penalties', function (): void {
    $org   = Organization::factory()->create(['code' => 'CCS']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($org->id, ['role' => 'admin']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $cash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'cash'],
        ['name' => 'Cash', 'is_active' => true]
    );
    $gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'is_active' => true]
    );
    $maya = PaymentMethod::firstOrCreate(
        ['payment_code' => 'maya'],
        ['name' => 'Maya', 'is_active' => true]
    );

    // Cash enabled in org config, GCash disabled, Maya not configured
    $org->organizationPaymentMethods()->create([
        'payment_method_id' => $cash->id,
        'account_name'      => '',
        'account_numbers'   => [],
        'is_enabled'        => true,
    ]);
    $org->organizationPaymentMethods()->create([
        'payment_method_id' => $gcash->id,
        'account_name'      => 'CCS Cashiers',
        'account_numbers'   => ['09171112222'],
        'is_enabled'        => false,
    ]);

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'amount'            => 150.00,
        'paid_amount'       => 0,
        'balance_remaining' => 150.00,
    ]);

    // Student portal: no digital payment is enabled -> cash must NOT be displayed, show online payment unavailable notice
    Auth::login($student);

    $portal = Livewire::test(BorrowerPenalties::class)
        ->assertSee('This organization does not allow online payment')
        ->assertDontSee('Pay Online');

    expect($portal->instance()->paymentChannels)->toHaveCount(0);

    $portal->call('openPayModal', $penalty->id)
        ->assertSet('payModal', false);

    // Finance penalties: only cash is available; submitting disabled GCash fails validation
    Auth::login($admin);

    $finance = Livewire::test(PenaltiesIndex::class)
        ->assertDontSee('Assess Penalty')
        ->call('openPaymentModal', $penalty->id);

    expect($finance->instance()->paymentMethods->pluck('id')->all())->toBe([$cash->id]);

    $finance->set('paymentMethodId', $gcash->id)
        ->set('referenceNumber', 'GCASH-123456')
        ->call('submitPayment')
        ->assertHasErrors(['paymentMethodId']);

    // Enable GCash -> now both admin finance and student portal see GCash (student still does NOT see Cash)
    $org->organizationPaymentMethods()
        ->where('payment_method_id', $gcash->id)
        ->update(['is_enabled' => true]);

    Auth::login($student);

    $portalWithDigital = Livewire::test(BorrowerPenalties::class)
        ->assertSee('GCash')
        ->assertDontSee('This organization does not allow online payment');

    expect($portalWithDigital->instance()->paymentChannels->pluck('payment_method_id')->all())
        ->toBe([$gcash->id]);

    // Admin can record Cash payment without reference number
    Auth::login($admin);

    Livewire::test(PenaltiesIndex::class)
        ->call('openPaymentModal', $penalty->id)
        ->set('paymentMethodId', $cash->id)
        ->set('referenceNumber', '')
        ->call('submitPayment')
        ->assertHasNoErrors();

    expect($penalty->fresh()->status)->toBe(PenaltyStatus::Paid);
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

it('supports table and card views, modal filters, and equipment-name-only search on portal browse', function (): void {
    $orgA = Organization::factory()->create(['code' => 'CCS']);
    $orgB = Organization::factory()->create(['code' => 'COE']);

    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($orgA->id, ['role' => 'member']);

    $oscilloscope = Item::factory()->create([
        'organization_id' => $orgA->id,
        'name'            => 'Digital Oscilloscope',
        'brand'           => 'UniqueBrandXYZ',
        'model_number'    => 'MOD-999',
        'is_active'       => true,
    ]);
    ItemUnit::factory()->available()->create([
        'organization_id' => $orgA->id,
        'item_id'         => $oscilloscope->id,
    ]);

    $microscope = Item::factory()->create([
        'organization_id' => $orgB->id,
        'name'            => 'Optical Microscope',
        'brand'           => 'Olympus',
        'is_active'       => true,
    ]);

    $this->actingAs($student);

    // Search only matches equipment name, not brand or model_number
    Livewire::test(BrowseEquipment::class)
        ->assertSet('viewMode', 'table')
        ->assertSee('Digital Oscilloscope')
        ->set('search', 'UniqueBrandXYZ')
        ->assertDontSee('Digital Oscilloscope')
        ->set('search', 'Oscilloscope')
        ->assertSee('Digital Oscilloscope')
        ->set('viewMode', 'grid')
        ->assertSee('Digital Oscilloscope');

    // Filter modal apply and reset
    Livewire::test(BrowseEquipment::class)
        ->call('openFilterModal')
        ->assertSet('filterModal', true)
        ->set('filterOrganizationId', (string) $orgB->id)
        ->set('filterAvailability', 'all')
        ->call('applyFilters')
        ->assertSet('filterModal', false)
        ->assertSee('Optical Microscope')
        ->assertDontSee('Digital Oscilloscope')
        ->call('resetFilters')
        ->assertSet('selectedOrganizationId', 'all')
        ->assertSee('Digital Oscilloscope')
        ->assertSee('Optical Microscope');
});

it('supports equipment name search, modal filter, and infinite scroll on portal reservations', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $oscilloscope = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Alpha Oscilloscope',
        'is_active'       => true,
    ]);
    $multimeter = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Beta Multimeter',
        'is_active'       => true,
    ]);

    for ($i = 0; $i < 11; $i++) {
        Reservation::factory()->create([
            'organization_id' => $org->id,
            'user_id'         => $student->id,
            'item_id'         => $oscilloscope->id,
            'status'          => ReservationStatus::Pending,
        ]);
    }

    Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $multimeter->id,
        'status'          => ReservationStatus::Approved,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerReservations::class)
        ->assertSet('totalCount', 12)
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertSet('limit', 20)
        ->assertSet('hasMore', false)
        ->set('search', 'Beta Multimeter')
        ->assertSee('Beta Multimeter')
        ->assertDontSee('Alpha Oscilloscope')
        ->set('search', '')
        ->call('openFilterModal')
        ->assertSet('filterModal', true)
        ->set('filterStatus', 'approved')
        ->call('applyFilters')
        ->assertSet('filterModal', false)
        ->assertSee('Beta Multimeter')
        ->assertDontSee('Alpha Oscilloscope')
        ->call('resetFilters')
        ->assertSet('statusFilter', 'all')
        ->assertSee('Alpha Oscilloscope');
});

it('displays clear lender details, specs modal, and equipment name search on portal loans', function (): void {
    $org = Organization::factory()->create([
        'name' => 'College of Computer Studies',
        'code' => 'CCS',
    ]);
    $staff   = User::factory()->create(['name' => 'Engr. Staff']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $oscilloscope = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Digital Oscilloscope',
        'specifications'  => ['bandwidth' => '100MHz'],
    ]);
    $unitA = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $oscilloscope->id,
    ]);

    $multimeter = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Bench Multimeter',
    ]);
    $unitB = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $multimeter->id,
    ]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $oscilloscope->id,
        'item_unit_id'    => $unitA->id,
        'issued_by'       => $staff->id,
        'status'          => LoanStatus::Active,
        'returned_at'     => null,
    ]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $multimeter->id,
        'item_unit_id'    => $unitB->id,
        'issued_by'       => $staff->id,
        'status'          => LoanStatus::Active,
        'returned_at'     => null,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerActiveLoans::class)
        ->assertSee('College of Computer Studies')
        ->assertSee('Issued by Engr. Staff')
        ->assertSee('Digital Oscilloscope')
        ->assertSee('Bench Multimeter')
        ->set('search', 'Digital Oscilloscope')
        ->assertSee('Digital Oscilloscope')
        ->assertDontSee('Bench Multimeter')
        ->call('openSpecsModal', $oscilloscope->id)
        ->assertSet('specsModal', true)
        ->assertSee('100MHz');
});

it('shows active, overdue, and returned loans with infinite scroll and equipment name search on portal history', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $oscilloscope = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Active Oscilloscope',
    ]);
    $overdueItem = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Overdue Multimeter',
    ]);
    $returnedItem = Item::factory()->create([
        'organization_id' => $org->id,
        'name'            => 'Returned Tripod',
    ]);

    $unitActive = ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $oscilloscope->id]);
    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $oscilloscope->id,
        'item_unit_id'    => $unitActive->id,
        'status'          => LoanStatus::Active,
        'due_at'          => now()->addDays(2),
        'returned_at'     => null,
    ]);

    $unitOverdue = ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $overdueItem->id]);
    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $overdueItem->id,
        'item_unit_id'    => $unitOverdue->id,
        'status'          => LoanStatus::Overdue,
        'due_at'          => now()->subDay(),
        'returned_at'     => null,
    ]);

    for ($i = 0; $i < 11; $i++) {
        $unit = ItemUnit::factory()->available()->create(['organization_id' => $org->id, 'item_id' => $returnedItem->id]);
        Loan::factory()->create([
            'organization_id' => $org->id,
            'user_id'         => $student->id,
            'item_id'         => $returnedItem->id,
            'item_unit_id'    => $unit->id,
            'status'          => LoanStatus::Returned,
            'borrowed_at'     => now()->subDays(5 + $i),
            'due_at'          => now()->subDays(3 + $i),
            'returned_at'     => now()->subDays(4 + $i),
        ]);
    }

    $this->actingAs($student);

    Livewire::test(App\Livewire\Borrower\History::class)
        ->assertDontSee('Punctuality Rate')
        ->assertDontSee('Total Returned')
        ->assertSee('Active Oscilloscope')
        ->assertSee('Needs Return')
        ->assertSee('Overdue Multimeter')
        ->assertSee('Overdue')
        ->assertSet('totalCount', 13)
        ->assertSet('hasMore', true)
        ->call('loadMore')
        ->assertSet('limit', 24)
        ->assertSet('hasMore', false)
        ->set('search', 'Overdue Multimeter')
        ->assertSee('Overdue Multimeter')
        ->assertDontSee('Active Oscilloscope');
});

it('allows borrowers to cancel approved reservations and releases any reserved unit', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Portable Projector']);
    $unit = ItemUnit::factory()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'status'          => ItemUnitStatus::Reserved,
    ]);

    $approved = Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'status'          => ReservationStatus::Approved,
    ]);

    $checkedOut = Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'status'          => ReservationStatus::CheckedOut,
    ]);

    $this->actingAs($student);

    Livewire::test(BorrowerReservations::class)
        ->call('openCancelModal', $approved->id)
        ->assertSet('cancelModal', true)
        ->assertSee('Portable Projector')
        ->assertSee('Yes, Cancel Reservation')
        ->call('confirmCancelReservation')
        ->assertSet('cancelModal', false)
        ->call('cancelReservation', $checkedOut->id);

    expect($approved->fresh()->status)->toBe(ReservationStatus::Cancelled)
        ->and($unit->fresh()->status)->toBe(ItemUnitStatus::Available)
        ->and($checkedOut->fresh()->status)->toBe(ReservationStatus::CheckedOut);
});

it('limits a student to one active reservation per equipment item', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
    ItemUnit::factory()->available()->count(2)->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
    ]);

    $this->actingAs($student);

    // First reservation succeeds
    Livewire::test(BrowseEquipment::class)
        ->call('openBookingModal', $item->id)
        ->assertSet('bookingModal', true)
        ->set('bookingStartTime', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('bookingEndTime', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('bookingPurpose', 'First lab experiment')
        ->call('submitBooking')
        ->assertHasNoErrors()
        ->assertSee('Reserved');

    expect(Reservation::where('user_id', $student->id)->where('item_id', $item->id)->count())->toBe(1);

    // Second reservation for the same equipment is blocked while active
    Livewire::test(BrowseEquipment::class)
        ->call('openBookingModal', $item->id)
        ->assertSet('bookingModal', false)
        ->assertDispatched('alert:dialog')
        ->set('bookingItemId', $item->id)
        ->set('bookingStartTime', now()->addDays(3)->format('Y-m-d\TH:i'))
        ->set('bookingEndTime', now()->addDays(4)->format('Y-m-d\TH:i'))
        ->set('bookingPurpose', 'Duplicate booking attempt')
        ->call('submitBooking')
        ->assertDispatched('alert:dialog');

    expect(Reservation::where('user_id', $student->id)->where('item_id', $item->id)->count())->toBe(1);

    // After cancelling the first reservation, student can reserve the same equipment again
    $reservation = Reservation::where('user_id', $student->id)->where('item_id', $item->id)->firstOrFail();
    $reservation->cancel();

    Livewire::test(BrowseEquipment::class)
        ->call('openBookingModal', $item->id)
        ->assertSet('bookingModal', true);
});
