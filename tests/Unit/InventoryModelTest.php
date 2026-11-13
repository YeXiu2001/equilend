<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use App\Models\ItemUnit;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\InventoryLogType;
use App\Enums\ItemUnitCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('category can be global or college-scoped and generates slug', function () {
    $globalCategory = Category::create([
        'name'            => 'Computing Devices',
        'organization_id' => null,
    ]);

    expect($globalCategory->slug)->toBe('computing-devices')
        ->and($globalCategory->isGlobal())->toBeTrue()
        ->and($globalCategory->organization)->toBeNull();

    $college = Organization::factory()->create(['name' => 'College of Computer Studies', 'code' => 'CCS']);

    $collegeCategory = Category::factory()->forOrganization($college)->create([
        'name' => 'CCS Robotics Lab Equipment',
    ]);

    expect($collegeCategory->slug)->toBe('ccs-robotics-lab-equipment')
        ->and($collegeCategory->isGlobal())->toBeFalse()
        ->and($collegeCategory->organization->id)->toBe($college->id);

    // Test scopes
    expect(Category::global()->count())->toBe(1)
        ->and(Category::forOrganization($college)->count())->toBe(1)
        ->and(Category::availableFor($college)->count())->toBe(2);
});

test('item belongs to organization and category with proper casts', function () {
    $college  = Organization::factory()->create();
    $category = Category::factory()->create();

    $item = Item::factory()->create([
        'organization_id' => $college->id,
        'category_id'     => $category->id,
        'name'            => 'MacBook Air M2',
        'specifications'  => [
            'ram'     => '16GB',
            'storage' => '512GB',
        ],
        'is_active' => true,
    ]);

    expect($item->organization->id)->toBe($college->id)
        ->and($item->category->id)->toBe($category->id)
        ->and($item->specifications)->toBeArray()
        ->and($item->specifications['ram'])->toBe('16GB')
        ->and($item->is_active)->toBeTrue();
});

test('item calculates total and available unit counts accurately', function () {
    $item = Item::factory()->create();

    // 2 available units
    ItemUnit::factory()->count(2)->available()->forItem($item)->create();

    // 1 maintenance unit
    ItemUnit::factory()->maintenance()->forItem($item)->create();

    // 1 damaged unit
    ItemUnit::factory()->damaged()->forItem($item)->create();

    // Fresh model instance (without relation loaded)
    $freshItem = Item::find($item->id);
    expect($freshItem->total_units_count)->toBe(4)
        ->and($freshItem->available_units_count)->toBe(2);

    // Eager-loaded relation
    $eagerLoadedItem = Item::with('units')->find($item->id);
    expect($eagerLoadedItem->total_units_count)->toBe(4)
        ->and($eagerLoadedItem->available_units_count)->toBe(2);
});

test('item unit casts status and condition to enums and provides helpers', function () {
    $unit = ItemUnit::factory()->create([
        'condition' => ItemUnitCondition::New,
        'status'    => ItemUnitStatus::Available,
    ]);

    expect($unit->condition)->toBeInstanceOf(ItemUnitCondition::class)
        ->and($unit->condition)->toBe(ItemUnitCondition::New)
        ->and($unit->condition->label())->toBe('New')
        ->and($unit->condition->badgeClass())->toBe('badge-success')
        ->and($unit->status)->toBeInstanceOf(ItemUnitStatus::class)
        ->and($unit->status)->toBe(ItemUnitStatus::Available)
        ->and($unit->status->label())->toBe('Available')
        ->and($unit->isAvailable())->toBeTrue()
        ->and($unit->isUnderMaintenance())->toBeFalse()
        ->and($unit->isDamaged())->toBeFalse();

    $unit->update([
        'status'    => ItemUnitStatus::UnderMaintenance,
        'condition' => ItemUnitCondition::Fair,
    ]);

    expect($unit->fresh()->isUnderMaintenance())->toBeTrue()
        ->and($unit->fresh()->status->badgeClass())->toBe('badge-warning')
        ->and($unit->fresh()->condition->badgeClass())->toBe('badge-warning');
});

test('item unit factory states work as expected', function () {
    $availableUnit   = ItemUnit::factory()->available()->create();
    $maintenanceUnit = ItemUnit::factory()->maintenance()->create();
    $damagedUnit     = ItemUnit::factory()->damaged()->create();

    expect($availableUnit->status)->toBe(ItemUnitStatus::Available)
        ->and($maintenanceUnit->status)->toBe(ItemUnitStatus::UnderMaintenance)
        ->and($damagedUnit->status)->toBe(ItemUnitStatus::Damaged)
        ->and($damagedUnit->condition)->toBe(ItemUnitCondition::Poor);

    expect(ItemUnit::available()->count())->toBe(1)
        ->and(ItemUnit::underMaintenance()->count())->toBe(1)
        ->and(ItemUnit::damaged()->count())->toBe(1);
});

test('inventory log records stock intake and audit movements with relations', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create();
    $unit = ItemUnit::factory()->forItem($item)->create();

    $restockLog = InventoryLog::factory()->restock(10, 'PO-2026-001')->create([
        'item_id'         => $item->id,
        'item_unit_id'    => null,
        'organization_id' => $item->organization_id,
        'user_id'         => $user->id,
    ]);

    expect($restockLog->type)->toBe(InventoryLogType::Restock)
        ->and($restockLog->type->label())->toBe('Restock')
        ->and($restockLog->quantity_change)->toBe(10)
        ->and($restockLog->reference_number)->toBe('PO-2026-001')
        ->and($restockLog->item->id)->toBe($item->id)
        ->and($restockLog->user->id)->toBe($user->id)
        ->and($restockLog->organization->id)->toBe($item->organization_id);

    $unitLog = InventoryLog::factory()->forUnit($unit)->statusChange('Sent for repair')->create([
        'user_id' => $user->id,
    ]);

    expect($unitLog->type)->toBe(InventoryLogType::StatusChange)
        ->and($unitLog->item_unit_id)->toBe($unit->id)
        ->and($unitLog->itemUnit->id)->toBe($unit->id);
});

test('organization and user reverse relationships are defined and accessible', function () {
    $org      = Organization::factory()->create();
    $user     = User::factory()->create();
    $category = Category::factory()->forOrganization($org)->create();
    $item     = Item::factory()->forOrganization($org)->forCategory($category)->create();
    $unit     = ItemUnit::factory()->forItem($item)->create();
    $log      = InventoryLog::factory()->forUnit($unit)->create(['user_id' => $user->id]);

    expect($org->categories)->toHaveCount(1)
        ->and($org->items)->toHaveCount(1)
        ->and($org->itemUnits)->toHaveCount(1)
        ->and($org->inventoryLogs)->toHaveCount(1)
        ->and($user->inventoryLogs)->toHaveCount(1);
});
