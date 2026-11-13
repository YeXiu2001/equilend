<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Inventory;

use App\Models\Item;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Category;
use App\Models\ItemUnit;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\InventoryLogType;
use App\Enums\ItemUnitCondition;
use App\Livewire\Inventory\Index;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Auth;
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

    $this->ccsAdmin   = User::where('email', 'admin.ccs@equilend.test')->firstOrFail();
    $this->superadmin = User::where('email', 'superadmin@equilend.test')->firstOrFail();
    $this->ccsOrg     = Organization::where('code', 'CCS')->firstOrFail();
    $this->coetOrg    = Organization::where('code', 'COET')->firstOrFail();
});

it('renders the inventory catalog component successfully for authenticated users', function () {
    Auth::login($this->ccsAdmin);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertViewIs('livewire.inventory.index');
});

it('enforces college scoping so college admin cannot see other colleges inventory', function () {
    Auth::login($this->ccsAdmin);

    $component = Livewire::test(Index::class);

    // CCS admin should see CCS items
    $component->assertSee('Apple MacBook Air M2')
        ->assertDontSee('Rigol Digital Storage Oscilloscopes')
        ->assertDontSee('Topcon Digital Total Station');

    // Attempting to tamper with selectedOrganizationId to view COET should fail silently and keep CCS scope
    $component->set('selectedOrganizationId', (string) $this->coetOrg->id)
        ->assertSee('Apple MacBook Air M2')
        ->assertDontSee('Rigol Digital Storage Oscilloscopes');

    $items = $component->get('items');

    foreach ($items as $item) {
        expect($item->organization_id)->toBe($this->ccsOrg->id);
    }
});

it('supports paginated infinite scrolling with limit 10', function () {
    Auth::login($this->superadmin);

    $component = Livewire::test(Index::class)
        ->assertSet('limit', 10);

    expect($component->get('items'))->toHaveCount(10)
        ->and($component->get('totalCount'))->toBe(21)
        ->and($component->get('hasMore'))->toBeTrue();

    $component->call('loadMore')
        ->assertSet('limit', 20);

    expect($component->get('items'))->toHaveCount(20)
        ->and($component->get('hasMore'))->toBeTrue();

    $component->call('loadMore')
        ->assertSet('limit', 30);

    expect($component->get('items'))->toHaveCount(21)
        ->and($component->get('hasMore'))->toBeFalse();
});

it('allows superadmin to view campus-wide items and switch between colleges via filter modal', function () {
    Auth::login($this->superadmin);

    $component = Livewire::test(Index::class)
        ->set('limit', 50);

    // Default 'all' sees items across different colleges
    $component->assertSee('Apple MacBook Air M2')
        ->assertSee('Rigol Digital Storage Oscilloscopes')
        ->assertSee('Olympus Binocular Compound Microscopes');

    // Open filter modal, select COET, and apply
    $component->call('openFilterModal')
        ->assertSet('filterModal', true)
        ->set('filterOrganizationId', (string) $this->coetOrg->id)
        ->call('applyFilters')
        ->assertSet('filterModal', false)
        ->assertSet('selectedOrganizationId', (string) $this->coetOrg->id)
        ->assertSet('activeFilterCount', 1)
        ->assertSee('Rigol Digital Storage Oscilloscopes')
        ->assertDontSee('Apple MacBook Air M2');

    $items = $component->get('items');

    foreach ($items as $item) {
        expect($item->organization_id)->toBe($this->coetOrg->id);
    }

    // Reset filters back to 'all'
    $component->call('resetFilters')
        ->assertSet('selectedOrganizationId', 'all')
        ->assertSet('activeFilterCount', 0)
        ->assertSee('Apple MacBook Air M2')
        ->assertSee('Rigol Digital Storage Oscilloscopes');
});

it('re-mounts a keyed infinite scroll trigger after applying and resetting filters', function () {
    Auth::login($this->superadmin);

    $component = Livewire::test(Index::class);

    // Filter down to COET (4 items -> hasMore = false, renders inventory-all-loaded)
    $component->call('openFilterModal')
        ->set('filterOrganizationId', (string) $this->coetOrg->id)
        ->call('applyFilters')
        ->assertSet('hasMore', false)
        ->assertSeeHtml('wire:key="inventory-all-loaded"');

    // Reset filters -> back to 21 total items with limit 10 (hasMore = true, fresh keyed trigger)
    $component->call('resetFilters')
        ->assertSet('limit', 10)
        ->assertSet('totalCount', 21)
        ->assertSet('hasMore', true)
        ->assertSeeHtml('wire:key="inventory-load-more-10-21-')
        ->assertSeeHtml('wire:intersect="loadMore"');

    // Triggering loadMore loads the next batch with a new key
    $component->call('loadMore')
        ->assertSet('limit', 20)
        ->assertSeeHtml('wire:key="inventory-load-more-20-21-');
});

it('searches equipment catalog by equipment name only', function () {
    Auth::login($this->superadmin);

    // Search by equipment name
    Livewire::test(Index::class)
        ->set('search', 'MacBook')
        ->assertSee('Apple MacBook Air M2')
        ->assertDontSee('Rigol Digital Storage Oscilloscopes');

    // Searching by asset tag or model number should NOT match since search is on equipment name only
    Livewire::test(Index::class)
        ->set('search', 'COET-OSC-001')
        ->assertDontSee('Rigol Digital Storage Oscilloscopes')
        ->assertDontSee('Apple MacBook Air M2');
});

it('filters equipment catalog by category via filter modal', function () {
    Auth::login($this->superadmin);

    $computingCategory = Category::where('name', 'Computing Devices & Laptops')->firstOrFail();

    Livewire::test(Index::class)
        ->call('openFilterModal')
        ->set('filterCategoryId', (string) $computingCategory->id)
        ->call('applyFilters')
        ->assertSet('categoryId', (string) $computingCategory->id)
        ->assertSee('Apple MacBook Air M2')
        ->assertDontSee('Rigol Digital Storage Oscilloscopes');
});

it('filters equipment catalog by availability status via filter modal', function () {
    Auth::login($this->ccsAdmin);

    // All CCS items have at least 1 unit under maintenance
    Livewire::test(Index::class)
        ->call('openFilterModal')
        ->set('filterStatus', 'under_maintenance')
        ->call('applyFilters')
        ->assertSet('status', 'under_maintenance')
        ->assertSee('Apple MacBook Air M2');
});

it('allows creating a new equipment catalog item', function () {
    Auth::login($this->ccsAdmin);

    $category = Category::where('slug', 'computing-devices-laptops')->firstOrFail();

    Livewire::test(Index::class)
        ->call('openCreateItemModal')
        ->assertSet('itemModal', true)
        ->set('itemForm.name', 'Dell XPS 15 Workstation')
        ->set('itemForm.brand', 'Dell')
        ->set('itemForm.model_number', 'XPS-9530')
        ->set('itemForm.category_id', $category->id)
        ->set('itemForm.description', 'High performance rendering machine for CCS graphics lab.')
        ->set('itemForm.specifications_text', "RAM: 32GB\nStorage: 1TB NVMe\nGPU: RTX 4070")
        ->call('saveItem')
        ->assertSet('itemModal', false)
        ->assertDispatched('alert:dialog');

    $newItem = Item::where('name', 'Dell XPS 15 Workstation')->first();

    expect($newItem)->not->toBeNull()
        ->and($newItem->organization_id)->toBe($this->ccsOrg->id)
        ->and($newItem->specifications)->toBeArray()
        ->and($newItem->specifications['ram'])->toBe('32GB');
});

it('allows editing an existing equipment catalog item', function () {
    Auth::login($this->ccsAdmin);

    $item = Item::where('organization_id', $this->ccsOrg->id)->firstOrFail();

    Livewire::test(Index::class)
        ->call('openEditItemModal', $item->id)
        ->assertSet('editingItemId', $item->id)
        ->set('itemForm.name', 'Apple MacBook Air M2 (Upgraded Edition)')
        ->call('saveItem')
        ->assertSet('itemModal', false)
        ->assertDispatched('alert:dialog');

    expect($item->fresh()->name)->toBe('Apple MacBook Air M2 (Upgraded Edition)');
});

it('allows deleting an item from the catalog', function () {
    Auth::login($this->ccsAdmin);

    $item = Item::factory()->forOrganization($this->ccsOrg)->create(['name' => 'Temporary Obsolete Router']);

    Livewire::test(Index::class)
        ->call('deleteItem', $item->id)
        ->assertDispatched('alert:dialog');

    expect(Item::find($item->id))->toBeNull();
});

it('inspects units and updates unit status while creating an audit log', function () {
    Auth::login($this->ccsAdmin);

    $item = Item::where('organization_id', $this->ccsOrg->id)->where('name', 'Apple MacBook Air M2')->firstOrFail();
    $unit = $item->units()->where('status', ItemUnitStatus::Available)->firstOrFail();

    Livewire::test(Index::class)
        ->call('openUnitInspector', $item->id)
        ->assertSet('unitModal', true)
        ->assertSet('inspectingItemId', $item->id)
        ->call('updateUnitStatus', $unit->id, 'under_maintenance')
        ->assertDispatched('alert:dialog');

    expect($unit->fresh()->status)->toBe(ItemUnitStatus::UnderMaintenance);

    $log = InventoryLog::where('item_unit_id', $unit->id)
        ->where('type', InventoryLogType::StatusChange)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->ccsAdmin->id);
});

it('adds a single physical unit to an item via inspector', function () {
    Auth::login($this->ccsAdmin);

    $item   = Item::where('organization_id', $this->ccsOrg->id)->where('name', 'Apple MacBook Air M2')->firstOrFail();
    $newTag = 'CCS-LPT-999';

    Livewire::test(Index::class)
        ->call('openUnitInspector', $item->id)
        ->set('newUnitForm.asset_tag', $newTag)
        ->set('newUnitForm.serial_number', 'SN-CCS-9999')
        ->set('newUnitForm.condition', 'new')
        ->set('newUnitForm.status', 'available')
        ->set('newUnitForm.notes', 'Spare backup unit received from donation.')
        ->call('saveUnit')
        ->assertDispatched('alert:dialog');

    $unit = ItemUnit::where('asset_tag', $newTag)->first();

    expect($unit)->not->toBeNull()
        ->and($unit->item_id)->toBe($item->id)
        ->and($unit->condition)->toBe(ItemUnitCondition::New)
        ->and($unit->status)->toBe(ItemUnitStatus::Available);
});

it('records restocking batch intake and automatically generates serialized units', function () {
    Auth::login($this->ccsAdmin);

    $item         = Item::where('organization_id', $this->ccsOrg->id)->where('name', 'Apple MacBook Air M2')->firstOrFail();
    $initialCount = $item->units()->count();

    Livewire::test(Index::class)
        ->call('openRestockModal', $item->id)
        ->assertSet('restockModal', true)
        ->set('restockForm.type', 'restock')
        ->set('restockForm.quantity', 3)
        ->set('restockForm.reference_number', 'PO-CCS-RESTOCK-2026')
        ->set('restockForm.auto_generate_units', true)
        ->set('restockForm.asset_prefix', 'CCS-LPT-NEW')
        ->call('saveRestock')
        ->assertSet('restockModal', false)
        ->assertDispatched('alert:dialog');

    expect($item->fresh()->units()->count())->toBe($initialCount + 3);

    $log = InventoryLog::where('item_id', $item->id)
        ->where('reference_number', 'PO-CCS-RESTOCK-2026')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->type)->toBe(InventoryLogType::Restock)
        ->and($log->quantity_change)->toBe(3)
        ->and($log->user_id)->toBe($this->ccsAdmin->id);

    // Check generated units
    expect(ItemUnit::where('asset_tag', 'like', 'CCS-LPT-NEW-%')->count())->toBe(3);
});

it('records damage write-off movement with negative quantity change', function () {
    Auth::login($this->ccsAdmin);

    $item = Item::where('organization_id', $this->ccsOrg->id)->firstOrFail();

    Livewire::test(Index::class)
        ->call('openRestockModal', $item->id)
        ->set('restockForm.type', 'damage_writeoff')
        ->set('restockForm.quantity', 2)
        ->set('restockForm.reference_number', 'WRT-CCS-2026-001')
        ->set('restockForm.notes', 'Decommissioned water-damaged hardware.')
        ->call('saveRestock')
        ->assertDispatched('alert:dialog');

    $log = InventoryLog::where('item_id', $item->id)
        ->where('reference_number', 'WRT-CCS-2026-001')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->type)->toBe(InventoryLogType::DamageWriteoff)
        ->and($log->quantity_change)->toBe(-2);
});
