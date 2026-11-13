<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Loan;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ReservationStatus;
use App\Models\EquipmentSchedule;
use App\Enums\EquipmentScheduleType;
use Database\Seeders\CategorySeeder;
use Database\Seeders\InventorySeeder;
use Database\Seeders\CirculationSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
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
});

it('seeds circulation records for all 5 university colleges', function () {
    $colleges = Organization::whereIn('code', ['CCS', 'CED', 'COET', 'CSM', 'CASS'])->get();

    expect($colleges)->toHaveCount(5);

    foreach ($colleges as $college) {
        // Active loans (1-3 range per college, exactly 2 seeded)
        $activeLoans = Loan::forOrganization($college)
            ->where('status', LoanStatus::Active)
            ->get();
        expect($activeLoans)->toHaveCount(2);

        // Overdue loans (1-2 range per college, exactly 1 seeded)
        $overdueLoans = Loan::forOrganization($college)
            ->where('status', LoanStatus::Overdue)
            ->get();
        expect($overdueLoans)->toHaveCount(1);

        // Pending reservations (2-4 range per college, exactly 3 seeded)
        $pendingReservations = Reservation::forOrganization($college)
            ->where('status', ReservationStatus::Pending)
            ->get();
        expect($pendingReservations)->toHaveCount(3);

        // Approved upcoming reservations (1-2 range per college, exactly 1 seeded)
        $approvedReservations = Reservation::forOrganization($college)
            ->where('status', ReservationStatus::Approved)
            ->get();
        expect($approvedReservations)->toHaveCount(1);

        // Historical completed loans (3-5 range per college, exactly 3 seeded)
        $returnedLoans = Loan::forOrganization($college)
            ->where('status', LoanStatus::Returned)
            ->get();
        expect($returnedLoans)->toHaveCount(3);

        // Schedules: 1 maintenance and 1 blackout
        $schedules = EquipmentSchedule::forOrganization($college)->get();
        expect($schedules)->toHaveCount(2)
            ->and($schedules->where('type', EquipmentScheduleType::Maintenance))->toHaveCount(1)
            ->and($schedules->where('type', EquipmentScheduleType::Blackout))->toHaveCount(1);
    }
});

it('correctly synchronizes item_unit statuses for borrowed and reserved equipment', function () {
    $colleges = Organization::whereIn('code', ['CCS', 'CED', 'COET', 'CSM', 'CASS'])->get();

    foreach ($colleges as $college) {
        $activeLoanUnitIds = Loan::forOrganization($college)
            ->where('status', LoanStatus::Active)
            ->pluck('item_unit_id');

        $overdueLoanUnitIds = Loan::forOrganization($college)
            ->where('status', LoanStatus::Overdue)
            ->pluck('item_unit_id');

        $allBorrowedUnitIds = $activeLoanUnitIds->merge($overdueLoanUnitIds);

        // Verify each unit attached to an active or overdue loan is marked 'borrowed'
        foreach ($allBorrowedUnitIds as $unitId) {
            $unit = $college->itemUnits()->find($unitId);
            expect($unit)->not->toBeNull()
                ->and($unit->status)->toBe(ItemUnitStatus::Borrowed);
        }

        // Verify reserved unit for approved reservation is marked 'reserved'
        $approvedReservation = Reservation::forOrganization($college)
            ->where('status', ReservationStatus::Approved)
            ->first();

        expect($approvedReservation)->not->toBeNull()
            ->and($approvedReservation->item_unit_id)->not->toBeNull()
            ->and($approvedReservation->itemUnit->status)->toBe(ItemUnitStatus::Reserved);
    }
});

it('ensures overdue loans have return deadlines in the past and null returned_at', function () {
    $overdueLoans = Loan::where('status', LoanStatus::Overdue)->get();

    expect($overdueLoans)->toHaveCount(5);

    foreach ($overdueLoans as $loan) {
        expect($loan->returned_at)->toBeNull()
            ->and($loan->due_at->isPast())->toBeTrue()
            ->and($loan->isOverdue())->toBeTrue();
    }
});

it('ensures completed historical loans have valid return timestamps and condition logs', function () {
    $returnedLoans = Loan::where('status', LoanStatus::Returned)->get();

    expect($returnedLoans)->toHaveCount(15);

    foreach ($returnedLoans as $loan) {
        expect($loan->returned_at)->not->toBeNull()
            ->and($loan->received_by)->not->toBeNull()
            ->and($loan->checkin_condition)->not->toBeNull()
            ->and($loan->itemUnit->status)->toBe(ItemUnitStatus::Available);
    }
});
