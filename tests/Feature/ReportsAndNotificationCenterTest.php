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
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Spatie\Permission\Models\Role;
use App\Livewire\Notifications\Bell;
use App\Livewire\Reports\InventoryReport;
use App\Livewire\Reports\CollectionReport;
use App\Livewire\Reports\CirculationReport;
use App\Notifications\PenaltyImposedNotification;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('displays unread count and marks notifications as read in the notification bell', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS']);
    $student = User::factory()->create();
    $student->assignRole('member');

    $penalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'type'              => PenaltyType::Overdue,
        'amount'            => 150.00,
        'balance_remaining' => 150.00,
    ]);

    $student->notify(new PenaltyImposedNotification($penalty));
    $student->notify(new PenaltyImposedNotification($penalty));

    $this->actingAs($student);

    $component = Livewire::test(Bell::class)
        ->assertSee('2 Unread')
        ->assertSee('Overdue Penalty Imposed');

    $firstId = $student->unreadNotifications()->first()->id;

    $component->call('markAsRead', $firstId);
    expect($student->unreadNotifications()->count())->toBe(1);

    $component->call('markAllAsRead')
        ->assertSee('0 Unread');
    expect($student->unreadNotifications()->count())->toBe(0);

    $component->call('clearAll')
        ->assertSee('No new notifications');
    expect($student->notifications()->count())->toBe(0);
});

it('forbids member borrowers from accessing staff reports', function (): void {
    $student = User::factory()->create();
    $student->assignRole('member');

    $this->actingAs($student);

    $this->get(route('reports.inventory'))->assertForbidden();
    $this->get(route('reports.circulation'))->assertForbidden();
    $this->get(route('reports.collections'))->assertForbidden();
});

it('scopes report figures to the college admin organization and prevents cross-college access', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $coet = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);

    $ccsAdmin = User::factory()->create();
    $ccsAdmin->assignRole('organization_admin');
    $ccsAdmin->organizations()->attach($ccs->id, ['role' => 'admin']);

    $ccsItem  = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'CCS Laptop']);
    $coetItem = Item::factory()->create(['organization_id' => $coet->id, 'name' => 'COET Oscilloscope']);

    ItemUnit::factory()->available()->create(['organization_id' => $ccs->id, 'item_id' => $ccsItem->id]);
    ItemUnit::factory()->available()->count(3)->create(['organization_id' => $coet->id, 'item_id' => $coetItem->id]);

    $this->actingAs($ccsAdmin);

    $this->get(route('reports.inventory'))
        ->assertOk()
        ->assertSee('CCS Laptop')
        ->assertDontSee('COET Oscilloscope');

    // Even if college admin attempts to tamper with selectedOrganizationId, it stays scoped to CCS
    Livewire::test(InventoryReport::class)
        ->set('selectedOrganizationId', (string) $coet->id)
        ->assertSee('CCS Laptop')
        ->assertDontSee('COET Oscilloscope');
});

it('allows superadmin to view all colleges and filter by specific college across all reports', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $coet = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);

    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $borrower = User::factory()->create();

    $ccsItem  = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'CCS Projector']);
    $coetItem = Item::factory()->create(['organization_id' => $coet->id, 'name' => 'COET Multimeter']);

    $ccsUnit  = ItemUnit::factory()->available()->create(['organization_id' => $ccs->id, 'item_id' => $ccsItem->id]);
    $coetUnit = ItemUnit::factory()->available()->create(['organization_id' => $coet->id, 'item_id' => $coetItem->id]);

    Loan::factory()->create([
        'organization_id' => $ccs->id,
        'user_id'         => $borrower->id,
        'item_id'         => $ccsItem->id,
        'item_unit_id'    => $ccsUnit->id,
        'issued_by'       => $superadmin->id,
        'borrowed_at'     => now()->subDays(5),
        'due_at'          => now()->subDays(4),
        'returned_at'     => now()->subDays(4)->subHour(),
        'status'          => LoanStatus::Returned,
    ]);

    Loan::factory()->create([
        'organization_id' => $coet->id,
        'user_id'         => $borrower->id,
        'item_id'         => $coetItem->id,
        'item_unit_id'    => $coetUnit->id,
        'issued_by'       => $superadmin->id,
        'borrowed_at'     => now()->subDays(3),
        'due_at'          => now()->subDays(2),
        'returned_at'     => now()->subDays(1),
        'status'          => LoanStatus::Returned,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(InventoryReport::class)
        ->assertSee('CCS Projector')
        ->assertSee('COET Multimeter')
        ->set('selectedOrganizationId', (string) $ccs->id)
        ->assertSee('CCS Projector')
        ->assertDontSee('COET Multimeter');

    Livewire::test(CirculationReport::class)
        ->assertSet('selectedOrganizationId', 'all')
        ->assertSee('Total Loan Volume')
        ->set('selectedOrganizationId', (string) $coet->id)
        ->assertSee('1');
});

it('streams valid CSV exports from Inventory, Circulation, and Collection reports', function (): void {
    $ccs   = Organization::factory()->create(['code' => 'CCS']);
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');
    $admin->organizations()->attach($ccs->id, ['role' => 'admin']);

    $student = User::factory()->create(['name' => 'Juan Dela Cruz']);
    $item    = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'MacBook Pro']);
    $unit    = ItemUnit::factory()->available()->create(['organization_id' => $ccs->id, 'item_id' => $item->id]);

    $loan = Loan::factory()->create([
        'organization_id' => $ccs->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $admin->id,
        'borrowed_at'     => now()->subDays(2),
        'due_at'          => now()->subDay(),
        'returned_at'     => now()->subDay()->subHour(),
        'status'          => LoanStatus::Returned,
    ]);

    $cash = PaymentMethod::firstOrCreate(['payment_code' => 'cash'], ['name' => 'Cash', 'is_active' => true]);

    $unpaidPenalty = Penalty::factory()->unpaid()->create([
        'organization_id'   => $ccs->id,
        'loan_id'           => $loan->id,
        'user_id'           => $student->id,
        'type'              => PenaltyType::Damage,
        'amount'            => 300.00,
        'balance_remaining' => 300.00,
    ]);

    $paidPenalty = Penalty::factory()->create([
        'organization_id'   => $ccs->id,
        'user_id'           => $student->id,
        'type'              => PenaltyType::Overdue,
        'amount'            => 100.00,
        'paid_amount'       => 100.00,
        'balance_remaining' => 0.00,
        'status'            => PenaltyStatus::Paid,
    ]);

    Payment::factory()->create([
        'organization_id'   => $ccs->id,
        'penalty_id'        => $paidPenalty->id,
        'user_id'           => $student->id,
        'payment_method_id' => $cash->id,
        'amount_paid'       => 100.00,
        'status'            => PaymentStatus::Verified,
        'paid_at'           => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test(InventoryReport::class)
        ->call('exportCsv')
        ->assertFileDownloaded('inventory-report-'.now()->format('Y-m-d').'.csv')
        ->assertSee('MacBook Pro');

    Livewire::test(CirculationReport::class)
        ->call('exportCsv')
        ->assertFileDownloaded('circulation-metrics-'.now()->format('Y-m-d').'.csv');

    Livewire::test(CollectionReport::class)
        ->call('exportCsv')
        ->assertFileDownloaded('penalty-collections-'.now()->format('Y-m-d').'.csv')
        ->assertSee('Juan Dela Cruz');
});
