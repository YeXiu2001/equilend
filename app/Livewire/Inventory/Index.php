<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Item;
use Livewire\Component;
use App\Models\Category;
use App\Models\ItemUnit;
use Illuminate\Support\Str;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Livewire\Traits\Alert;
use App\Enums\InventoryLogType;
use App\Enums\ItemUnitCondition;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Index extends Component
{
    use Alert;

    public int $limit = 10;

    public ?string $search = '';

    // Applied Filters
    public string $categoryId = 'all';

    public string $status = 'all';

    public string $selectedOrganizationId = 'all';

    // Filter Modal state & pending values
    public bool $filterModal = false;

    public string $filterCategoryId = 'all';

    public string $filterStatus = 'all';

    public string $filterOrganizationId = 'all';

    public string $viewMode = 'table';

    public array $sort = [
        'column'    => 'created_at',
        'direction' => 'desc',
    ];

    // Item Add / Edit Modal state
    public bool $itemModal = false;

    public ?int $editingItemId = null;

    /**
     * @var array{
     *     organization_id: int|string|null,
     *     category_id: int|string|null,
     *     name: string,
     *     brand: string,
     *     model_number: string,
     *     description: string,
     *     specifications_text: string,
     *     image_url: string,
     *     is_active: bool,
     * }
     */
    public array $itemForm = [
        'organization_id'     => null,
        'category_id'         => '',
        'name'                => '',
        'brand'               => '',
        'model_number'        => '',
        'description'         => '',
        'specifications_text' => '',
        'image_url'           => '',
        'is_active'           => true,
    ];

    // Unit Inspector Modal state
    public bool $unitModal = false;

    public ?int $inspectingItemId = null;

    public bool $showAddUnit = false;

    /**
     * @var array{
     *     asset_tag: string,
     *     serial_number: string,
     *     condition: string,
     *     status: string,
     *     notes: string,
     * }
     */
    public array $newUnitForm = [
        'asset_tag'     => '',
        'serial_number' => '',
        'condition'     => 'good',
        'status'        => 'available',
        'notes'         => '',
    ];

    // Restock / Stock Movement Dialog state
    public bool $restockModal = false;

    public ?int $restockItemId = null;

    /**
     * @var array{
     *     type: string,
     *     quantity: int,
     *     reference_number: string,
     *     notes: string,
     *     auto_generate_units: bool,
     *     asset_prefix: string,
     * }
     */
    public array $restockForm = [
        'type'                => 'restock',
        'quantity'            => 1,
        'reference_number'    => '',
        'notes'               => '',
        'auto_generate_units' => true,
        'asset_prefix'        => '',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to inventory management.');
        }

        if (! $user->hasRole('superadmin')) {
            $userOrg                      = $user->organization;
            $this->selectedOrganizationId = $userOrg ? (string) $userOrg->id : 'all';
            $this->filterOrganizationId   = $this->selectedOrganizationId;
        }
    }

    public function updatedSearch(): void
    {
        $this->limit = 10;
    }

    public function updatedCategoryId(): void
    {
        $this->limit = 10;
    }

    public function updatedStatus(): void
    {
        $this->limit = 10;
    }

    public function updatedSelectedOrganizationId(): void
    {
        $this->limit = 10;
    }

    /**
     * Sort the catalog by a column.
     */
    public function sortBy(string $column): void
    {
        if ($this->sort['column'] === $column) {
            $this->sort['direction'] = $this->sort['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort['column']    = $column;
            $this->sort['direction'] = 'asc';
        }

        $this->limit = 10;
    }

    /**
     * Increase item limit for paginated infinite scrolling.
     */
    public function loadMore(): void
    {
        $this->limit += 10;
    }

    /**
     * Open Filter Modal synced with active filters.
     */
    public function openFilterModal(): void
    {
        $this->filterOrganizationId = $this->selectedOrganizationId;
        $this->filterCategoryId     = $this->categoryId;
        $this->filterStatus         = $this->status;
        $this->filterModal          = true;
    }

    /**
     * Apply pending modal filters to the table.
     */
    public function applyFilters(): void
    {
        if (Auth::user()?->hasRole('superadmin')) {
            $this->selectedOrganizationId = $this->filterOrganizationId;
        }

        $this->categoryId  = $this->filterCategoryId;
        $this->status      = $this->filterStatus;
        $this->limit       = 10;
        $this->filterModal = false;
    }

    /**
     * Reset all filters back to default.
     */
    public function resetFilters(): void
    {
        if (Auth::user()?->hasRole('superadmin')) {
            $this->selectedOrganizationId = 'all';
            $this->filterOrganizationId   = 'all';
        }

        $this->categoryId       = 'all';
        $this->filterCategoryId = 'all';
        $this->status           = 'all';
        $this->filterStatus     = 'all';
        $this->limit            = 10;
        $this->filterModal      = false;
    }

    /**
     * Count active filters for UI badge/indicator.
     */
    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;

        if (Auth::user()?->hasRole('superadmin') && $this->selectedOrganizationId !== 'all') {
            $count++;
        }

        if ($this->categoryId !== 'all') {
            $count++;
        }

        if ($this->status !== 'all') {
            $count++;
        }

        return $count;
    }

    /**
     * Resolve effective organization ID based on current user role.
     */
    public function getEffectiveOrganizationId(): ?int
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            return $user->organization?->id;
        }

        if ($this->selectedOrganizationId === 'all' || empty($this->selectedOrganizationId)) {
            return null;
        }

        return (int) $this->selectedOrganizationId;
    }

    /**
     * Active organizations list for superadmin switching.
     *
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::active()->orderBy('name')->get();
    }

    /**
     * Categories available in the current context.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Category::query()
            ->availableFor($orgId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Total matching items count for infinite scroll.
     */
    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * Paginated infinite-scroll equipment catalog items.
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->baseQuery()
            ->orderBy($this->sort['column'], $this->sort['direction'])
            ->limit($this->limit)
            ->get();
    }

    /**
     * Whether more items exist beyond the current limit.
     */
    #[Computed]
    public function hasMore(): bool
    {
        return $this->items->count() < $this->totalCount;
    }

    /**
     * Item currently being inspected.
     */
    #[Computed]
    public function inspectingItem(): ?Item
    {
        if (! $this->inspectingItemId) {
            return null;
        }

        return Item::with(['units', 'organization', 'category'])->find($this->inspectingItemId);
    }

    /**
     * Item currently being restocked / adjusted.
     */
    #[Computed]
    public function restockItem(): ?Item
    {
        if (! $this->restockItemId) {
            return null;
        }

        return Item::with('organization')->find($this->restockItemId);
    }

    /**
     * Open Item Create Modal.
     */
    public function openCreateItemModal(): void
    {
        $defaultOrgId = $this->getEffectiveOrganizationId() ?? Organization::first()?->id;

        $this->editingItemId = null;
        $this->itemForm      = [
            'organization_id'     => $defaultOrgId,
            'category_id'         => $this->categories->first()?->id ?? '',
            'name'                => '',
            'brand'               => '',
            'model_number'        => '',
            'description'         => '',
            'specifications_text' => '',
            'image_url'           => '',
            'is_active'           => true,
        ];

        $this->itemModal = true;
    }

    /**
     * Open Item Edit Modal.
     */
    public function openEditItemModal(int $id): void
    {
        $item = $this->findAccessibleItem($id);

        $this->editingItemId = $item->id;

        $specsText = '';

        if (is_array($item->specifications)) {
            $lines = [];

            foreach ($item->specifications as $key => $val) {
                $lines[] = is_string($key) ? ucfirst($key).': '.$val : (string) $val;
            }
            $specsText = implode("\n", $lines);
        }

        $this->itemForm = [
            'organization_id'     => $item->organization_id,
            'category_id'         => $item->category_id,
            'name'                => $item->name,
            'brand'               => $item->brand ?? '',
            'model_number'        => $item->model_number ?? '',
            'description'         => $item->description ?? '',
            'specifications_text' => $specsText,
            'image_url'           => $item->image_url ?? '',
            'is_active'           => (bool) $item->is_active,
        ];

        $this->itemModal = true;
    }

    /**
     * Save Item (Create or Update).
     */
    public function saveItem(): void
    {
        $isSuperadmin = Auth::user()?->hasRole('superadmin');
        $userOrgId    = Auth::user()?->organization?->id;

        $this->validate([
            'itemForm.name'                => 'required|string|max:255',
            'itemForm.category_id'         => 'required|exists:categories,id',
            'itemForm.organization_id'     => 'required|exists:organizations,id',
            'itemForm.brand'               => 'nullable|string|max:255',
            'itemForm.model_number'        => 'nullable|string|max:255',
            'itemForm.description'         => 'nullable|string',
            'itemForm.specifications_text' => 'nullable|string',
            'itemForm.image_url'           => 'nullable|string|max:500',
            'itemForm.is_active'           => 'boolean',
        ]);

        $orgId = $isSuperadmin
            ? (int) $this->itemForm['organization_id']
            : ($userOrgId ?? (int) $this->itemForm['organization_id']);

        $specifications = [];

        if (! empty($this->itemForm['specifications_text'])) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $this->itemForm['specifications_text']);

            foreach ($lines as $line) {
                $line = mb_trim((string) $line);

                if ($line === '') {
                    continue;
                }

                if (str_contains($line, ':')) {
                    [$key, $val]                                   = explode(':', $line, 2);
                    $specifications[Str::slug(mb_trim($key), '_')] = mb_trim($val);
                } else {
                    $specifications[] = $line;
                }
            }
        }

        $payload = [
            'organization_id' => $orgId,
            'category_id'     => (int) $this->itemForm['category_id'],
            'name'            => mb_trim($this->itemForm['name']),
            'brand'           => ! empty($this->itemForm['brand']) ? mb_trim($this->itemForm['brand']) : null,
            'model_number'    => ! empty($this->itemForm['model_number']) ? mb_trim($this->itemForm['model_number']) : null,
            'description'     => ! empty($this->itemForm['description']) ? mb_trim($this->itemForm['description']) : null,
            'specifications'  => ! empty($specifications) ? $specifications : null,
            'image_url'       => ! empty($this->itemForm['image_url']) ? mb_trim($this->itemForm['image_url']) : null,
            'is_active'       => (bool) $this->itemForm['is_active'],
        ];

        if ($this->editingItemId) {
            $item = $this->findAccessibleItem($this->editingItemId);
            $item->update($payload);
            $this->success('Equipment item updated successfully.');
        } else {
            Item::create($payload);
            $this->success('Equipment item created successfully.');
        }

        $this->itemModal     = false;
        $this->editingItemId = null;
    }

    /**
     * Delete an item.
     */
    public function deleteItem(int $id): void
    {
        $item = $this->findAccessibleItem($id);
        $name = $item->name;
        $item->delete();

        $this->success("Item '{$name}' was deleted successfully.");
    }

    /**
     * Open Unit Inspector.
     */
    public function openUnitInspector(int $itemId): void
    {
        $item                   = $this->findAccessibleItem($itemId);
        $this->inspectingItemId = $item->id;
        $this->showAddUnit      = false;

        $collegeCode = $item->organization->code ?? 'EQ';
        $prefix      = sprintf('%s-%s', $collegeCode, mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $item->name), 0, 3)));
        $nextNum     = $item->units()->count() + 1;

        $this->newUnitForm = [
            'asset_tag'     => sprintf('%s-%03d', $prefix, $nextNum),
            'serial_number' => sprintf('SN-%s-%04d', $collegeCode, ($item->id * 100) + $nextNum),
            'condition'     => 'good',
            'status'        => 'available',
            'notes'         => '',
        ];

        $this->unitModal = true;
    }

    /**
     * Update unit status.
     */
    public function updateUnitStatus(int $unitId, string $statusValue): void
    {
        $unit = ItemUnit::with('item')->findOrFail($unitId);
        $this->findAccessibleItem($unit->item_id);

        $newStatus = ItemUnitStatus::from($statusValue);
        $oldStatus = $unit->status;

        $unit->update(['status' => $newStatus]);

        InventoryLog::create([
            'item_id'          => $unit->item_id,
            'item_unit_id'     => $unit->id,
            'organization_id'  => $unit->organization_id,
            'user_id'          => Auth::id(),
            'type'             => InventoryLogType::StatusChange,
            'quantity_change'  => 0,
            'reference_number' => sprintf('STC-%s-%04d', date('Ymd'), $unit->id),
            'notes'            => sprintf('Status transitioned from %s to %s.', $oldStatus->label(), $newStatus->label()),
        ]);

        $this->success("Unit {$unit->asset_tag} status updated to {$newStatus->label()}.");
    }

    /**
     * Update unit condition.
     */
    public function updateUnitCondition(int $unitId, string $conditionValue): void
    {
        $unit = ItemUnit::with('item')->findOrFail($unitId);
        $this->findAccessibleItem($unit->item_id);

        $newCondition = ItemUnitCondition::from($conditionValue);
        $unit->update(['condition' => $newCondition]);

        $this->success("Unit {$unit->asset_tag} condition updated to {$newCondition->label()}.");
    }

    /**
     * Save new physical unit.
     */
    public function saveUnit(): void
    {
        if (! $this->inspectingItemId) {
            return;
        }

        $item = $this->findAccessibleItem($this->inspectingItemId);

        $this->validate([
            'newUnitForm.asset_tag'     => 'required|string|max:100|unique:item_units,asset_tag',
            'newUnitForm.serial_number' => 'nullable|string|max:100',
            'newUnitForm.condition'     => 'required|in:new,good,fair,poor',
            'newUnitForm.status'        => 'required|in:available,under_maintenance,damaged,reserved,borrowed,lost,retired',
            'newUnitForm.notes'         => 'nullable|string',
        ]);

        $unit = ItemUnit::create([
            'item_id'         => $item->id,
            'organization_id' => $item->organization_id,
            'asset_tag'       => mb_trim($this->newUnitForm['asset_tag']),
            'serial_number'   => ! empty($this->newUnitForm['serial_number']) ? mb_trim($this->newUnitForm['serial_number']) : null,
            'condition'       => ItemUnitCondition::from($this->newUnitForm['condition']),
            'status'          => ItemUnitStatus::from($this->newUnitForm['status']),
            'notes'           => ! empty($this->newUnitForm['notes']) ? mb_trim($this->newUnitForm['notes']) : null,
        ]);

        InventoryLog::create([
            'item_id'          => $item->id,
            'item_unit_id'     => $unit->id,
            'organization_id'  => $item->organization_id,
            'user_id'          => Auth::id(),
            'type'             => InventoryLogType::Restock,
            'quantity_change'  => 1,
            'reference_number' => sprintf('INT-%s-%04d', date('Ymd'), $unit->id),
            'notes'            => 'Single serialized physical unit added via inventory inspector.',
        ]);

        $this->showAddUnit = false;
        $this->success("Unit {$unit->asset_tag} added to catalog.");
    }

    /**
     * Open Restock / Adjustment dialog.
     */
    public function openRestockModal(int $itemId): void
    {
        $item                = $this->findAccessibleItem($itemId);
        $this->restockItemId = $item->id;

        $collegeCode = $item->organization->code ?? 'EQ';
        $prefix      = sprintf('%s-%s', $collegeCode, mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $item->name), 0, 3)));

        $this->restockForm = [
            'type'                => 'restock',
            'quantity'            => 1,
            'reference_number'    => sprintf('PO-%s-%04d', date('Ymd'), $item->id),
            'notes'               => '',
            'auto_generate_units' => true,
            'asset_prefix'        => $prefix,
        ];

        $this->restockModal = true;
    }

    /**
     * Save Restock / Stock Movement.
     */
    public function saveRestock(): void
    {
        if (! $this->restockItemId) {
            return;
        }

        $item = $this->findAccessibleItem($this->restockItemId);

        $this->validate([
            'restockForm.type'             => 'required|in:restock,audit_adjustment,damage_writeoff,status_change',
            'restockForm.quantity'         => 'required|integer|min:1|max:100',
            'restockForm.reference_number' => 'nullable|string|max:100',
            'restockForm.notes'            => 'nullable|string',
            'restockForm.asset_prefix'     => 'nullable|string|max:50',
        ]);

        $type      = InventoryLogType::from($this->restockForm['type']);
        $quantity  = (int) $this->restockForm['quantity'];
        $qtyChange = match ($type) {
            InventoryLogType::Restock         => $quantity,
            InventoryLogType::DamageWriteoff  => -$quantity,
            InventoryLogType::AuditAdjustment => $quantity,
            InventoryLogType::StatusChange    => 0,
        };

        $createdUnitsCount = 0;

        if ($type === InventoryLogType::Restock && ! empty($this->restockForm['auto_generate_units'])) {
            $prefix = ! empty($this->restockForm['asset_prefix'])
                ? mb_trim($this->restockForm['asset_prefix'])
                : sprintf('%s-AST', $item->organization->code ?? 'EQ');

            $currentCount = $item->units()->count();

            for ($i = 1; $i <= $quantity; $i++) {
                $unitNum = $currentCount + $i;
                $tag     = sprintf('%s-%03d', $prefix, $unitNum);

                while (ItemUnit::where('asset_tag', $tag)->exists()) {
                    $unitNum++;
                    $tag = sprintf('%s-%03d', $prefix, $unitNum);
                }

                ItemUnit::create([
                    'item_id'         => $item->id,
                    'organization_id' => $item->organization_id,
                    'asset_tag'       => $tag,
                    'serial_number'   => sprintf('SN-%s-%04d', $item->organization->code ?? 'EQ', ($item->id * 100) + $unitNum),
                    'condition'       => ItemUnitCondition::New,
                    'status'          => ItemUnitStatus::Available,
                    'notes'           => 'Generated via batch intake log: '.($this->restockForm['reference_number'] ?? 'N/A'),
                ]);

                $createdUnitsCount++;
            }
        }

        InventoryLog::create([
            'item_id'          => $item->id,
            'item_unit_id'     => null,
            'organization_id'  => $item->organization_id,
            'user_id'          => Auth::id(),
            'type'             => $type,
            'quantity_change'  => $qtyChange,
            'reference_number' => ! empty($this->restockForm['reference_number']) ? mb_trim($this->restockForm['reference_number']) : null,
            'notes'            => ! empty($this->restockForm['notes'])
                ? mb_trim($this->restockForm['notes'])
                : sprintf('Stock intake / audit ledger entry (%s units).', $quantity),
        ]);

        $this->restockModal  = false;
        $this->restockItemId = null;

        $msg = $createdUnitsCount > 0
            ? "Stock movement recorded and {$createdUnitsCount} serialized unit(s) generated successfully."
            : 'Inventory movement recorded successfully.';

        $this->success($msg);
    }

    public function render(): View
    {
        return view('livewire.inventory.index');
    }

    /**
     * Base query scoped by user role, equipment name search, and applied filters.
     *
     * @return Builder<Item>
     */
    protected function baseQuery(): Builder
    {
        $effectiveOrgId = $this->getEffectiveOrganizationId();

        return Item::query()
            ->with(['category', 'organization', 'units'])
            ->when($effectiveOrgId !== null, fn (Builder $q) => $q->where('organization_id', $effectiveOrgId))
            ->when($this->search !== null && mb_trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.mb_trim((string) $this->search).'%';
                $query->where('name', 'like', $term);
            })
            ->when($this->categoryId !== 'all' && ! empty($this->categoryId), function (Builder $query): void {
                $query->where('category_id', (int) $this->categoryId);
            })
            ->when($this->status !== 'all', function (Builder $query): void {
                if ($this->status === 'available') {
                    $query->whereHas('units', fn (Builder $u) => $u->where('status', ItemUnitStatus::Available));
                } elseif ($this->status === 'under_maintenance') {
                    $query->whereHas('units', fn (Builder $u) => $u->where('status', ItemUnitStatus::UnderMaintenance));
                } elseif ($this->status === 'damaged') {
                    $query->whereHas('units', fn (Builder $u) => $u->where('status', ItemUnitStatus::Damaged));
                } elseif ($this->status === 'out_of_stock') {
                    $query->whereDoesntHave('units', fn (Builder $u) => $u->where('status', ItemUnitStatus::Available));
                }
            });
    }

    /**
     * Find item and ensure caller has permission to view/modify it.
     */
    protected function findAccessibleItem(int $id): Item
    {
        $user = Auth::user();

        $query = Item::query()->with('organization');

        if ($user && ! $user->hasRole('superadmin')) {
            $userOrgId = $user->organization?->id;
            $query->where('organization_id', $userOrgId);
        }

        return $query->findOrFail($id);
    }
}
