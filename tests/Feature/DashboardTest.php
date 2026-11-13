<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Penalty;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Livewire\Dashboard;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ReservationStatus;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('displays borrower eligibility, active loans, and reservations for student members', function (): void {
    $org     = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $staff   = User::factory()->create();
    $student = User::factory()->create(['name' => 'Maria Santos']);
    $student->assignRole('member');
    $student->organizations()->attach($org->id, ['role' => 'member']);

    $item = Item::factory()->create(['organization_id' => $org->id, 'name' => 'Sony Alpha A7']);
    $unit = ItemUnit::factory()->available()->create([
        'organization_id' => $org->id,
        'item_id'         => $item->id,
        'asset_tag'       => 'CCS-CAM-101',
    ]);

    Loan::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'item_unit_id'    => $unit->id,
        'issued_by'       => $staff->id,
        'borrowed_at'     => now()->subHours(2),
        'due_at'          => now()->addHours(6),
        'status'          => LoanStatus::Active,
    ]);

    Reservation::factory()->create([
        'organization_id' => $org->id,
        'user_id'         => $student->id,
        'item_id'         => $item->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $this->actingAs($student);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cleared to Borrow')
        ->assertSee('Sony Alpha A7')
        ->assertSee('CCS-CAM-101')
        ->assertDontSee('Pending Booking Requests')
        ->assertDontSee('Campus-Wide Per-College Snapshot');

    // Add unpaid penalty -> student becomes suspended
    Penalty::factory()->unpaid()->create([
        'organization_id'   => $org->id,
        'user_id'           => $student->id,
        'type'              => PenaltyType::Damage,
        'amount'            => 350.00,
        'balance_remaining' => 350.00,
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Borrowing Privileges Temporarily Suspended')
        ->assertSee('₱350.00');
});

it('displays college-scoped operational command center for organization admins', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $coet = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);

    $ccsAdmin = User::factory()->create();
    $ccsAdmin->assignRole('organization_admin');
    $ccsAdmin->organizations()->attach($ccs->id, ['role' => 'admin']);

    $student = User::factory()->create(['name' => 'Pedro Reyes']);

    $ccsItem  = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'CCS Projector']);
    $coetItem = Item::factory()->create(['organization_id' => $coet->id, 'name' => 'COET Oscilloscope']);

    Reservation::factory()->create([
        'organization_id' => $ccs->id,
        'user_id'         => $student->id,
        'item_id'         => $ccsItem->id,
        'status'          => ReservationStatus::Pending,
    ]);

    Reservation::factory()->create([
        'organization_id' => $coet->id,
        'user_id'         => $student->id,
        'item_id'         => $coetItem->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $this->actingAs($ccsAdmin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pending Booking Requests')
        ->assertSee('CCS Projector')
        ->assertDontSee('COET Oscilloscope')
        ->assertDontSee('Campus-Wide Per-College Snapshot');

    // Even if tampered with, remains scoped to CCS
    Livewire::test(Dashboard::class)
        ->set('selectedOrganizationId', (string) $coet->id)
        ->assertSee('CCS Projector')
        ->assertDontSee('COET Oscilloscope');
});

it('displays campus-wide per-college snapshot and college filter for superadmin', function (): void {
    $ccs  = Organization::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    $coet = Organization::factory()->create(['code' => 'COET', 'name' => 'College of Engineering']);

    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $student  = User::factory()->create();
    $ccsItem  = Item::factory()->create(['organization_id' => $ccs->id, 'name' => 'CCS Drone']);
    $coetItem = Item::factory()->create(['organization_id' => $coet->id, 'name' => 'COET Lathe']);

    Reservation::factory()->create([
        'organization_id' => $ccs->id,
        'user_id'         => $student->id,
        'item_id'         => $ccsItem->id,
        'status'          => ReservationStatus::Pending,
    ]);

    Reservation::factory()->create([
        'organization_id' => $coet->id,
        'user_id'         => $student->id,
        'item_id'         => $coetItem->id,
        'status'          => ReservationStatus::Pending,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(Dashboard::class)
        ->assertSee('Campus-Wide Per-College Snapshot')
        ->assertSee('CCS Drone')
        ->assertSee('COET Lathe')
        ->set('selectedOrganizationId', (string) $ccs->id)
        ->assertSee('CCS Drone')
        ->assertDontSee('COET Lathe')
        ->assertDontSee('Campus-Wide Per-College Snapshot');
});
