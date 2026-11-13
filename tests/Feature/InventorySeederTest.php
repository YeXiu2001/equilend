<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemUnit;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\InventoryLogType;
use Database\Seeders\CategorySeeder;
use Database\Seeders\InventorySeeder;
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
    ]);
});

it('seeds the 5 universal campus categories with null organization id', function () {
    $expectedCategories = [
        'Computing Devices & Laptops',
        'Audio & Visual Equipment',
        'Laboratory & Scientific Instruments',
        'Electronics, Microcontrollers & Robotics',
        'Surveying & Field Hardware',
    ];

    expect(Category::count())->toBe(5);

    foreach ($expectedCategories as $name) {
        $category = Category::where('name', $name)->first();

        expect($category)->not->toBeNull()
            ->and($category->isGlobal())->toBeTrue()
            ->and($category->organization_id)->toBeNull();
    }
});

it('seeds specialized equipment catalogs for all 5 colleges', function () {
    $colleges = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

    foreach ($colleges as $code) {
        $org = Organization::where('code', $code)->first();

        expect($org)->not->toBeNull();

        $items = Item::where('organization_id', $org->id)->get();

        expect($items->count())->toBeGreaterThanOrEqual(3)
            ->and($items->count())->toBeLessThanOrEqual(5);

        // Every item should belong to a category and have units
        foreach ($items as $item) {
            expect($item->category)->not->toBeNull()
                ->and($item->units->count())->toBeGreaterThanOrEqual(2);
        }
    }
});

it('seeds specific landmark equipment for each college', function () {
    // CCS
    $ccs = Organization::where('code', 'CCS')->first();
    expect(Item::where('organization_id', $ccs->id)->where('name', 'Apple MacBook Air M2')->exists())->toBeTrue()
        ->and(Item::where('organization_id', $ccs->id)->where('name', 'Meta Quest 2 VR Development Headsets')->exists())->toBeTrue();

    // CED
    $ced = Organization::where('code', 'CED')->first();
    expect(Item::where('organization_id', $ced->id)->where('name', 'AVer Document Cameras & Visualizers')->exists())->toBeTrue()
        ->and(Item::where('organization_id', $ced->id)->where('name', 'Logitech Spotlight Wireless Presenters')->exists())->toBeTrue();

    // COET
    $coet = Organization::where('code', 'COET')->first();
    expect(Item::where('organization_id', $coet->id)->where('name', 'Rigol Digital Storage Oscilloscopes (100MHz)')->exists())->toBeTrue()
        ->and(Item::where('organization_id', $coet->id)->where('name', 'Topcon Digital Total Station Survey Equipment')->exists())->toBeTrue();

    // CSM
    $csm = Organization::where('code', 'CSM')->first();
    expect(Item::where('organization_id', $csm->id)->where('name', 'Olympus Binocular Compound Microscopes')->exists())->toBeTrue()
        ->and(Item::where('organization_id', $csm->id)->where('name', 'Ohaus Analytical Precision Balances (0.1mg)')->exists())->toBeTrue();

    // CASS
    $cass = Organization::where('code', 'CASS')->first();
    expect(Item::where('organization_id', $cass->id)->where('name', 'Sony Alpha A7 III Full-Frame Cameras')->exists())->toBeTrue()
        ->and(Item::where('organization_id', $cass->id)->where('name', 'Rode Wireless GO II Dual Lavalier Mic Systems')->exists())->toBeTrue();
});

it('seeds units with realistic asset tags and expected maintenance and damaged states', function () {
    $colleges = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

    foreach ($colleges as $code) {
        $org = Organization::where('code', $code)->first();

        $units = ItemUnit::where('organization_id', $org->id)->get();

        expect($units->count())->toBeGreaterThan(0);

        // Verify asset tag prefix matches college code
        expect($units->first()->asset_tag)->toStartWith($code);

        // Verify presence of available, maintenance, and damaged units for realistic testing
        $availableCount   = $units->where('status', ItemUnitStatus::Available)->count();
        $maintenanceCount = $units->where('status', ItemUnitStatus::UnderMaintenance)->count();
        $damagedCount     = $units->where('status', ItemUnitStatus::Damaged)->count();

        expect($availableCount)->toBeGreaterThan(0)
            ->and($maintenanceCount)->toBeGreaterThanOrEqual(1)
            ->and($damagedCount)->toBeGreaterThanOrEqual(1);
    }
});

it('seeds initial batch restock inventory logs for every item', function () {
    $items = Item::all();

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        $log = InventoryLog::where('item_id', $item->id)
            ->where('type', InventoryLogType::Restock)
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->quantity_change)->toBe($item->units()->count())
            ->and($log->organization_id)->toBe($item->organization_id)
            ->and($log->reference_number)->not->toBeNull()
            ->and($log->user_id)->not->toBeNull();
    }
});
