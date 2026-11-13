<div>
    <x-breadcrumbs :items="[
        ['label' => __('Inventory & Assets'), 'icon' => 'heroicon-o-archive-box'],
        ['label' => __('Equipment Catalog'), 'icon' => 'heroicon-o-queue-list'],
    ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <!-- View Mode Toggle -->
                <div class="join">
                    <button type="button"
                        class="join-item btn btn-sm {{ $viewMode === 'table' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('viewMode', 'table')"
                        title="{{ __('Table View') }}">
                        <x-heroicon-o-table-cells class="w-4 h-4" />
                    </button>
                    <button type="button"
                        class="join-item btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-ghost' }}"
                        wire:click="$set('viewMode', 'grid')"
                        title="{{ __('Grid Card View') }}">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                    </button>
                </div>

                <button type="button"
                    class="btn btn-primary btn-sm px-4 font-semibold"
                    wire:click="openCreateItemModal">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span>{{ __('Add Equipment') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-4 sm:p-6">
            <!-- Clean Toolbar: Count on left, Search + Filter Icon on right -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-base-content/60">
                        {{ __('Showing :count of :total equipment items', ['count' => $this->items->count(), 'total' => $this->totalCount]) }}
                    </span>
                    @if ($this->activeFilterCount > 0)
                        <button type="button"
                            wire:click="resetFilters"
                            class="btn btn-ghost btn-xs text-error">
                            {{ __('Clear filters') }}
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                        <input type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search equipment name...') }}"
                            class="grow" />
                    </label>

                    <button type="button"
                        wire:click="openFilterModal"
                        class="btn btn-sm {{ $this->activeFilterCount > 0 ? 'btn-primary' : 'btn-outline border-base-300' }} gap-1.5"
                        title="{{ __('Filter Equipment') }}">
                        <x-heroicon-o-funnel class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ __('Filters') }}</span>
                        @if ($this->activeFilterCount > 0)
                            <span class="text-xs font-bold">({{ $this->activeFilterCount }})</span>
                        @endif
                    </button>
                </div>
            </div>

            <!-- Loading overlay -->
            <div class="relative min-h-[200px]">
                <div wire:loading.flex wire:target="search,sortBy,applyFilters,resetFilters" class="absolute inset-0 bg-base-100/60 z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                @if ($viewMode === 'table')
                    <!-- Clean Table View -->
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>
                                        <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                            wire:click="sortBy('name')">
                                            <span>{{ __('Equipment Name') }}</span>
                                            @if ($sort['column'] === 'name')
                                                <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th>{{ __('College') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Unit Stock') }}</th>
                                    <th>
                                        <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                            wire:click="sortBy('is_active')">
                                            <span>{{ __('Active') }}</span>
                                            @if ($sort['column'] === 'is_active')
                                                <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->items as $item)
                                    <tr wire:key="item-row-{{ $item->id }}" class="hover">
                                        <td class="font-medium text-base-content">
                                            {{ $item->name }}
                                        </td>
                                        <td class="text-sm text-base-content/80 whitespace-nowrap">
                                            {{ $item->organization->code ?? 'N/A' }}
                                        </td>
                                        <td class="text-sm text-base-content/80">
                                            {{ $item->category->name ?? 'Uncategorized' }}
                                        </td>
                                        <td class="whitespace-nowrap text-sm">
                                            <span class="font-medium {{ $item->available_units_count > 0 ? 'text-success' : ($item->total_units_count > 0 ? 'text-warning' : 'text-error') }}">
                                                {{ $item->available_units_count }}
                                            </span>
                                            <span class="text-base-content/60">/ {{ $item->total_units_count }} {{ __('available') }}</span>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            @if ($item->is_active)
                                                <span class="badge badge-success badge-sm whitespace-nowrap">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-ghost badge-sm whitespace-nowrap">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1">
                                                <button type="button"
                                                    wire:click="openUnitInspector({{ $item->id }})"
                                                    class="btn btn-ghost btn-xs gap-1"
                                                    title="{{ __('Inspect Serialized Units') }}">
                                                    <x-heroicon-o-eye class="w-3.5 h-3.5 text-primary" />
                                                    <span class="hidden sm:inline">{{ __('Units') }} ({{ $item->total_units_count }})</span>
                                                </button>

                                                <button type="button"
                                                    wire:click="openRestockModal({{ $item->id }})"
                                                    class="btn btn-ghost btn-xs gap-1"
                                                    title="{{ __('Restock / Stock Movement') }}">
                                                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-success" />
                                                    <span class="hidden sm:inline">{{ __('Restock') }}</span>
                                                </button>

                                                <button type="button"
                                                    wire:click="openEditItemModal({{ $item->id }})"
                                                    class="btn btn-ghost btn-xs btn-square"
                                                    title="{{ __('Edit Item') }}">
                                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                                </button>

                                                <button type="button"
                                                    wire:click="deleteItem({{ $item->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to delete this equipment item and all associated unit records?') }}"
                                                    class="btn btn-ghost btn-xs btn-square text-error"
                                                    title="{{ __('Delete Item') }}">
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-base-content/60">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <x-heroicon-o-archive-box-x-mark class="w-8 h-8 opacity-40" />
                                                <p class="font-medium">{{ __('No equipment items found.') }}</p>
                                                <p class="text-xs">{{ __('Try adjusting your search or filter criteria.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Grid Card View -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->items as $item)
                            <div wire:key="item-card-{{ $item->id }}" class="card bg-base-100 card-border border-base-300 shadow-xs hover:border-primary/40 transition-colors">
                                <div class="card-body p-4 flex flex-col justify-between gap-3">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-xs font-semibold text-base-content/60">{{ $item->organization->code ?? 'N/A' }} • {{ $item->category->name ?? 'Category' }}</p>
                                                <h3 class="font-bold text-base text-base-content mt-0.5">{{ $item->name }}</h3>
                                            </div>
                                            @if ($item->is_active)
                                                <span class="badge badge-success badge-sm whitespace-nowrap">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-ghost badge-sm whitespace-nowrap">{{ __('Inactive') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-base-200 flex flex-col gap-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-base-content/70">{{ __('Unit Stock:') }}</span>
                                            <span>
                                                <strong class="{{ $item->available_units_count > 0 ? 'text-success' : ($item->total_units_count > 0 ? 'text-warning' : 'text-error') }}">
                                                    {{ $item->available_units_count }}
                                                </strong>
                                                <span class="text-base-content/60">/ {{ $item->total_units_count }} {{ __('available') }}</span>
                                            </span>
                                        </div>

                                        <div class="card-actions justify-end items-center gap-1 pt-1">
                                            <button type="button"
                                                wire:click="openUnitInspector({{ $item->id }})"
                                                class="btn btn-ghost btn-xs gap-1">
                                                <x-heroicon-o-eye class="w-3.5 h-3.5 text-primary" />
                                                <span>{{ __('Units') }} ({{ $item->total_units_count }})</span>
                                            </button>
                                            <button type="button"
                                                wire:click="openRestockModal({{ $item->id }})"
                                                class="btn btn-ghost btn-xs gap-1">
                                                <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-success" />
                                                <span>{{ __('Restock') }}</span>
                                            </button>
                                            <button type="button"
                                                wire:click="openEditItemModal({{ $item->id }})"
                                                class="btn btn-ghost btn-xs btn-square">
                                                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                            </button>
                                            <button type="button"
                                                wire:click="deleteItem({{ $item->id }})"
                                                wire:confirm="{{ __('Delete item?') }}"
                                                class="btn btn-ghost btn-xs btn-square text-error">
                                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-base-content/60">
                                <x-heroicon-o-archive-box-x-mark class="w-8 h-8 opacity-40 mx-auto mb-2" />
                                <p class="font-medium">{{ __('No equipment items found.') }}</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <!-- Infinite Scrolling Trigger & Indicator -->
            @if ($this->hasMore)
                <div wire:key="inventory-load-more-{{ $this->limit }}-{{ $this->totalCount }}-{{ md5((string) $this->search . '|' . $this->categoryId . '|' . $this->status . '|' . $this->selectedOrganizationId) }}"
                    wire:intersect="loadMore"
                    class="flex flex-col items-center justify-center py-6 gap-2">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-xs text-base-content/50">{{ __('Loading more equipment...') }}</span>
                </div>
            @elseif ($this->items->isNotEmpty())
                <div wire:key="inventory-all-loaded" class="text-center py-4 text-xs text-base-content/40">
                    {{ __('All :count equipment items loaded.', ['count' => $this->totalCount]) }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 0. Filter Options Modal                    -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($filterModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ __('Filter Equipment Catalog') }}</h3>

            <form id="inventory-filter-form" wire:submit="applyFilters" class="space-y-4">
                @if (auth()->user()?->hasRole('superadmin'))
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('College / Hub') }}</legend>
                        <select wire:model="filterOrganizationId" class="select select-sm w-full">
                            <option value="all">{{ __('All Colleges (Campus-Wide)') }}</option>
                            @foreach ($this->organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                            @endforeach
                        </select>
                    </fieldset>
                @endif

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Category') }}</legend>
                    <select wire:model="filterCategoryId" class="select select-sm w-full">
                        <option value="all">{{ __('All Categories') }}</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Availability Status') }}</legend>
                    <select wire:model="filterStatus" class="select select-sm w-full">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="available">{{ __('Available for Loan') }}</option>
                        <option value="under_maintenance">{{ __('Under Maintenance') }}</option>
                        <option value="damaged">{{ __('Damaged') }}</option>
                        <option value="out_of_stock">{{ __('Out of Stock') }}</option>
                    </select>
                </fieldset>
            </form>

            <div class="modal-action justify-between">
                <button type="button" class="btn btn-ghost btn-sm text-error" wire:click="resetFilters">
                    {{ __('Reset') }}
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('filterModal', false)">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="inventory-filter-form" class="btn btn-primary btn-sm">
                        {{ __('Apply Filters') }}
                    </button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('filterModal', false)">close</button>
        </form>
    </dialog>

    <!-- ========================================== -->
    <!-- 1. Add / Edit Equipment Modal             -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($itemModal) open @endif>
        <div class="modal-box max-w-2xl">
            <h3 class="text-lg font-bold mb-4">
                {{ $editingItemId ? __('Edit Equipment Item') : __('Add New Equipment Item') }}
            </h3>

            <form id="equipment-item-form" wire:submit="saveItem" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('College / Hub') }} *</legend>
                        @if (auth()->user()?->hasRole('superadmin'))
                            <select wire:model="itemForm.organization_id" class="select select-sm w-full @error('itemForm.organization_id') select-error @enderror" required>
                                <option value="">{{ __('Select College...') }}</option>
                                @foreach ($this->organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->code }})</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text"
                                value="{{ auth()->user()?->organization?->name ?? 'Default Hub' }}"
                                readonly
                                class="input input-sm w-full bg-base-200 cursor-not-allowed opacity-80" />
                        @endif
                        @error('itemForm.organization_id')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Category') }} *</legend>
                        <select wire:model="itemForm.category_id" class="select select-sm w-full @error('itemForm.category_id') select-error @enderror" required>
                            <option value="">{{ __('Select Category...') }}</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('itemForm.category_id')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Equipment Item Name') }} *</legend>
                    <input type="text"
                        wire:model="itemForm.name"
                        placeholder="{{ __('e.g. Workstation Laptop, Digital Oscilloscope') }}"
                        class="input input-sm w-full @error('itemForm.name') input-error @enderror"
                        required />
                    @error('itemForm.name')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Manufacturer / Brand') }}</legend>
                        <input type="text"
                            wire:model="itemForm.brand"
                            placeholder="e.g. Apple, Rigol, Olympus"
                            class="input input-sm w-full @error('itemForm.brand') input-error @enderror" />
                        @error('itemForm.brand')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Model Number') }}</legend>
                        <input type="text"
                            wire:model="itemForm.model_number"
                            placeholder="e.g. DS1102Z-E, MLXY3PP/A"
                            class="input input-sm w-full @error('itemForm.model_number') input-error @enderror" />
                        @error('itemForm.model_number')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Description') }}</legend>
                    <textarea wire:model="itemForm.description"
                        rows="2"
                        placeholder="{{ __('Detailed description of equipment purpose and lending guidelines...') }}"
                        class="textarea textarea-sm w-full @error('itemForm.description') textarea-error @enderror"></textarea>
                    @error('itemForm.description')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Specifications (One Key: Value per line)') }}</legend>
                    <textarea wire:model="itemForm.specifications_text"
                        rows="3"
                        placeholder="RAM: 16GB&#10;Storage: 512GB SSD&#10;Processor: Apple M2"
                        class="textarea textarea-sm font-mono text-xs w-full @error('itemForm.specifications_text') textarea-error @enderror"></textarea>
                    @error('itemForm.specifications_text')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <label class="label cursor-pointer justify-start gap-2">
                    <input type="checkbox" wire:model="itemForm.is_active" class="checkbox checkbox-primary checkbox-sm" />
                    <span class="label-text text-sm font-medium">{{ __('Equipment is active and visible in catalog') }}</span>
                </label>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('itemModal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="equipment-item-form" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveItem">
                    <span wire:loading wire:target="saveItem" class="loading loading-spinner loading-xs"></span>
                    <span>{{ $editingItemId ? __('Update Item') : __('Save Item') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('itemModal', false)">close</button>
        </form>
    </dialog>

    <!-- ========================================== -->
    <!-- 2. Unit Inspector Modal                   -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($unitModal) open @endif>
        <div class="modal-box max-w-4xl max-h-[90vh] flex flex-col">
            @if ($this->inspectingItem)
                <div class="flex items-start justify-between pb-3 border-b border-base-200">
                    <div>
                        <h3 class="text-lg font-bold">{{ $this->inspectingItem->name }}</h3>
                        <p class="text-xs text-base-content/60">
                            {{ $this->inspectingItem->organization->code ?? 'N/A' }}
                            • {{ $this->inspectingItem->brand }} {{ $this->inspectingItem->model_number ? '('.$this->inspectingItem->model_number.')' : '' }}
                            • {{ $this->inspectingItem->category->name ?? 'Category' }}
                        </p>
                    </div>

                    <button type="button"
                        class="btn btn-primary btn-xs gap-1"
                        wire:click="$toggle('showAddUnit')">
                        <x-heroicon-o-plus class="w-3.5 h-3.5" />
                        <span>{{ $showAddUnit ? __('Hide Unit Form') : __('Add Unit') }}</span>
                    </button>
                </div>

                <!-- Unit Stats Header -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 my-3">
                    <div class="p-2.5 rounded-lg bg-base-200 text-center">
                        <span class="text-xs text-base-content/70">{{ __('Total Units') }}</span>
                        <p class="text-lg font-bold">{{ $this->inspectingItem->total_units_count }}</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-success/10 text-center text-success">
                        <span class="text-xs">{{ __('Available') }}</span>
                        <p class="text-lg font-bold">{{ $this->inspectingItem->available_units_count }}</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-warning/10 text-center text-warning">
                        <span class="text-xs">{{ __('Maintenance') }}</span>
                        <p class="text-lg font-bold">{{ $this->inspectingItem->units->where('status', \App\Enums\ItemUnitStatus::UnderMaintenance)->count() }}</p>
                    </div>
                    <div class="p-2.5 rounded-lg bg-error/10 text-center text-error">
                        <span class="text-xs">{{ __('Damaged') }}</span>
                        <p class="text-lg font-bold">{{ $this->inspectingItem->units->where('status', \App\Enums\ItemUnitStatus::Damaged)->count() }}</p>
                    </div>
                </div>

                <!-- Add Unit Form (Collapsible) -->
                @if ($showAddUnit)
                    <div class="p-3 bg-base-200/60 rounded-lg border border-base-300 mb-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider mb-2 text-primary">{{ __('Register New Physical Asset Unit') }}</h4>
                        <form wire:submit="saveUnit" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <fieldset class="fieldset">
                                <legend class="fieldset-legend text-xs">{{ __('Asset Tag / Barcode') }} *</legend>
                                <input type="text" wire:model="newUnitForm.asset_tag" class="input input-xs w-full" required />
                                @error('newUnitForm.asset_tag') <p class="label text-error text-[11px]">{{ $message }}</p> @enderror
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend text-xs">{{ __('Serial Number') }}</legend>
                                <input type="text" wire:model="newUnitForm.serial_number" class="input input-xs w-full" />
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend text-xs">{{ __('Condition') }}</legend>
                                <select wire:model="newUnitForm.condition" class="select select-xs w-full">
                                    <option value="new">{{ __('New') }}</option>
                                    <option value="good">{{ __('Good') }}</option>
                                    <option value="fair">{{ __('Fair') }}</option>
                                    <option value="poor">{{ __('Poor') }}</option>
                                </select>
                            </fieldset>

                            <fieldset class="fieldset">
                                <legend class="fieldset-legend text-xs">{{ __('Initial Status') }}</legend>
                                <select wire:model="newUnitForm.status" class="select select-xs w-full">
                                    <option value="available">{{ __('Available') }}</option>
                                    <option value="under_maintenance">{{ __('Under Maintenance') }}</option>
                                    <option value="damaged">{{ __('Damaged') }}</option>
                                </select>
                            </fieldset>

                            <fieldset class="fieldset sm:col-span-2">
                                <legend class="fieldset-legend text-xs">{{ __('Notes') }}</legend>
                                <input type="text" wire:model="newUnitForm.notes" placeholder="Condition or intake remarks..." class="input input-xs w-full" />
                            </fieldset>

                            <div class="sm:col-span-3 flex justify-end gap-2 mt-1">
                                <button type="button" class="btn btn-ghost btn-xs" wire:click="$set('showAddUnit', false)">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary btn-xs" wire:loading.attr="disabled" wire:target="saveUnit">
                                    <span wire:loading wire:target="saveUnit" class="loading loading-spinner loading-xs"></span>
                                    <span>{{ __('Save Physical Unit') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Serialized Units Table -->
                <div class="overflow-y-auto flex-1 min-h-[220px]">
                    <table class="table table-xs sm:table-sm w-full">
                        <thead class="sticky top-0 bg-base-100 z-10">
                            <tr>
                                <th>{{ __('Asset Tag') }}</th>
                                <th>{{ __('Serial Number') }}</th>
                                <th>{{ __('Condition') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th class="text-end">{{ __('Quick Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->inspectingItem->units as $unit)
                                <tr wire:key="inspect-unit-{{ $unit->id }}" class="hover">
                                    <td class="font-mono font-bold text-xs whitespace-nowrap">{{ $unit->asset_tag }}</td>
                                    <td class="font-mono text-xs text-base-content/70 whitespace-nowrap">{{ $unit->serial_number ?? '—' }}</td>
                                    <td>
                                        <select class="select select-xs w-24"
                                            wire:change="updateUnitCondition({{ $unit->id }}, $event.target.value)">
                                            @foreach (\App\Enums\ItemUnitCondition::cases() as $cond)
                                                <option value="{{ $cond->value }}" @selected($unit->condition === $cond)>
                                                    {{ $cond->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="badge badge-sm whitespace-nowrap {{ $unit->status->badgeClass() }}">
                                            {{ $unit->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-base-content/70 max-w-xs truncate" title="{{ $unit->notes }}">
                                        {{ $unit->notes ?? '—' }}
                                    </td>
                                    <td class="text-end whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            @if ($unit->isAvailable())
                                                <button type="button"
                                                    wire:click="updateUnitStatus({{ $unit->id }}, 'under_maintenance')"
                                                    class="btn btn-xs btn-outline btn-warning"
                                                    title="{{ __('Mark as Under Maintenance') }}">
                                                    {{ __('Send Maint.') }}
                                                </button>
                                                <button type="button"
                                                    wire:click="updateUnitStatus({{ $unit->id }}, 'damaged')"
                                                    class="btn btn-xs btn-outline btn-error"
                                                    title="{{ __('Mark as Damaged') }}">
                                                    {{ __('Damaged') }}
                                                </button>
                                            @else
                                                <button type="button"
                                                    wire:click="updateUnitStatus({{ $unit->id }}, 'available')"
                                                    class="btn btn-xs btn-outline btn-success"
                                                    title="{{ __('Restore to Available') }}">
                                                    {{ __('Set Available') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-6 text-base-content/60">
                                        {{ __('No physical units registered yet for this item.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="modal-action pt-3 border-t border-base-200 mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('unitModal', false)">
                        {{ __('Close Inspector') }}
                    </button>
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('unitModal', false)">close</button>
        </form>
    </dialog>

    <!-- ========================================== -->
    <!-- 3. Restock Intake & Stock Movement Modal   -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($restockModal) open @endif>
        <div class="modal-box max-w-lg">
            @if ($this->restockItem)
                <h3 class="text-lg font-bold mb-1">{{ __('Inventory Stock & Audit Ledger') }}</h3>
                <p class="text-xs text-base-content/60 mb-4">
                    {{ $this->restockItem->name }} ({{ $this->restockItem->organization->code ?? 'N/A' }})
                    • {{ __('Current units: ') }} <strong>{{ $this->restockItem->total_units_count }}</strong>
                </p>

                <form id="restock-form" wire:submit="saveRestock" class="space-y-3">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Movement / Ledger Type') }} *</legend>
                        <select wire:model.live="restockForm.type" class="select select-sm w-full" required>
                            <option value="restock">{{ __('Restock Intake (Receive new units)') }}</option>
                            <option value="audit_adjustment">{{ __('Audit Adjustment (Reconcile stock count)') }}</option>
                            <option value="damage_writeoff">{{ __('Damage Write-off (Decommission units)') }}</option>
                            <option value="status_change">{{ __('Status Change Ledger Log') }}</option>
                        </select>
                    </fieldset>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">{{ __('Quantity') }} *</legend>
                            <input type="number"
                                min="1"
                                max="100"
                                wire:model="restockForm.quantity"
                                class="input input-sm w-full @error('restockForm.quantity') input-error @enderror"
                                required />
                            @error('restockForm.quantity')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">{{ __('PO / Ticket Reference') }}</legend>
                            <input type="text"
                                wire:model="restockForm.reference_number"
                                placeholder="e.g. PO-2026-001"
                                class="input input-sm w-full font-mono text-xs @error('restockForm.reference_number') input-error @enderror" />
                            @error('restockForm.reference_number')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>

                    @if ($restockForm['type'] === 'restock')
                        <div class="p-3 bg-base-200/50 rounded-lg border border-base-300 space-y-2">
                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" wire:model.live="restockForm.auto_generate_units" class="checkbox checkbox-primary checkbox-sm" />
                                <span class="label-text text-xs font-semibold">{{ __('Auto-generate serialized physical units with barcode tags') }}</span>
                            </label>

                            @if ($restockForm['auto_generate_units'])
                                <fieldset class="fieldset w-full">
                                    <legend class="fieldset-legend text-xs">{{ __('Asset Tag Prefix') }}</legend>
                                    <input type="text"
                                        wire:model="restockForm.asset_prefix"
                                        placeholder="e.g. CCS-LPT"
                                        class="input input-xs w-full font-mono" />
                                    <p class="text-[11px] text-base-content/60 mt-0.5">
                                        {{ __('Units will be tagged as [PREFIX]-001, [PREFIX]-002, etc.') }}
                                    </p>
                                </fieldset>
                            @endif
                        </div>
                    @endif

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Intake Notes / Audit Remarks') }}</legend>
                        <textarea wire:model="restockForm.notes"
                            rows="2"
                            placeholder="Supplier delivery receipt details or audit remarks..."
                            class="textarea textarea-sm w-full"></textarea>
                    </fieldset>
                </form>

                <div class="modal-action">
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('restockModal', false)">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="restock-form" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="saveRestock">
                        <span wire:loading wire:target="saveRestock" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Commit Ledger Record') }}</span>
                    </button>
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('restockModal', false)">close</button>
        </form>
    </dialog>
</div>
