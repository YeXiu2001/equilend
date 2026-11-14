<div>
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('Browse Equipment'), 'icon' => 'heroicon-o-magnifying-glass'],
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

                <a href="{{ route('portal.reservations') }}" class="btn btn-outline border-base-300 btn-sm font-semibold gap-1.5">
                    <x-heroicon-o-calendar-days class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('My Reservations') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    @php
        $borrower = auth()->user();
        $canBorrow = $borrower ? $borrower->canBorrow() : true;
    @endphp

    @if (! $canBorrow)
        <div role="alert" class="alert alert-error text-xs py-2.5 px-3 mb-4 shadow-xs">
            <x-heroicon-o-shield-exclamation class="w-5 h-5 shrink-0" />
            <div>
                <span class="font-bold block">{{ __('Borrowing Privileges Suspended') }}</span>
                <span>{{ implode(' ', $borrower->getBorrowingBlockReasons()) }}</span>
            </div>
        </div>
    @endif

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
                <div wire:loading.flex wire:target="search,sortBy,applyFilters,resetFilters,viewMode" class="absolute inset-0 bg-base-100/60 z-20 items-center justify-center">
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
                                    <th>{{ __('Availability') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->items as $item)
                                    <tr wire:key="browse-row-{{ $item->id }}" class="hover">
                                        <td>
                                            <div class="font-medium text-base-content">{{ $item->name }}</div>
                                            @if ($item->brand || $item->model_number)
                                                <div class="text-xs text-base-content/60">
                                                    {{ trim(($item->brand ?? '') . ' ' . ($item->model_number ?? '')) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-sm text-base-content/80 whitespace-nowrap">
                                            {{ $item->organization->code ?? 'N/A' }}
                                        </td>
                                        <td class="text-sm text-base-content/80">
                                            {{ $item->category->name ?? __('Uncategorized') }}
                                        </td>
                                        <td class="whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <span>
                                                    <span class="font-medium {{ $item->available_units_count > 0 ? 'text-success' : ($item->total_units_count > 0 ? 'text-warning' : 'text-error') }}">
                                                        {{ $item->available_units_count }}
                                                    </span>
                                                    <span class="text-base-content/60">/ {{ $item->total_units_count }} {{ __('available') }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-end whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                <button type="button"
                                                    wire:click="openSpecsModal({{ $item->id }})"
                                                    class="btn btn-ghost btn-xs gap-1"
                                                    title="{{ __('View Specifications') }}">
                                                    <x-heroicon-o-information-circle class="w-3.5 h-3.5 text-primary" />
                                                    <span class="hidden sm:inline">{{ __('View Specs') }}</span>
                                                </button>

                                                @if (in_array($item->id, $this->activeReservedItemIds, true))
                                                    <span class="badge badge-info badge-sm font-semibold whitespace-nowrap">{{ __('Reserved') }}</span>
                                                @else
                                                    <button type="button"
                                                        wire:click="openBookingModal({{ $item->id }})"
                                                        @disabled(! $canBorrow || $item->available_units_count === 0)
                                                        class="btn btn-primary btn-xs gap-1 font-semibold">
                                                        <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                                        <span>{{ __('Request Booking') }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-8 text-base-content/60">
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
                            <div wire:key="browse-card-{{ $item->id }}" class="card bg-base-100 card-border border-base-300 shadow-xs hover:border-primary/40 transition-colors">
                                <div class="card-body p-4 flex flex-col justify-between gap-3">
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-base-content/60">
                                                    {{ $item->organization->code ?? 'N/A' }} • {{ $item->category->name ?? __('Uncategorized') }}
                                                </p>
                                                <h3 class="font-bold text-base text-base-content mt-0.5 truncate">{{ $item->name }}</h3>
                                                @if ($item->brand || $item->model_number)
                                                    <p class="text-xs text-base-content/60 truncate">
                                                        {{ trim(($item->brand ?? '') . ' ' . ($item->model_number ?? '')) }}
                                                    </p>
                                                @endif
                                            </div>

                                            <span class="badge badge-sm shrink-0 whitespace-nowrap {{ $item->available_units_count > 0 ? 'badge-success' : 'badge-ghost' }}">
                                                {{ $item->available_units_count > 0 ? __(':count available', ['count' => $item->available_units_count]) : __('Unavailable') }}
                                            </span>
                                        </div>

                                        @if (! empty($item->specifications))
                                            @if (is_array($item->specifications))
                                                <div class="flex flex-wrap gap-1 mt-3">
                                                    @foreach (array_slice($item->specifications, 0, 3, true) as $specKey => $specVal)
                                                        <span class="bg-base-200/80 text-base-content/75 px-2 py-0.5 rounded text-[11px] whitespace-nowrap">
                                                            {{ is_string($specKey) ? ucfirst(str_replace('_', ' ', $specKey)).': ' : '' }}{{ is_array($specVal) ? implode(', ', $specVal) : $specVal }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-base-content/75 line-clamp-2 mt-3">
                                                    {{ $item->specifications }}
                                                </p>
                                            @endif
                                        @endif
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

                                        <div class="card-actions justify-end items-center gap-1.5 pt-1">
                                            <button type="button"
                                                wire:click="openSpecsModal({{ $item->id }})"
                                                class="btn btn-ghost btn-xs gap-1">
                                                <x-heroicon-o-information-circle class="w-3.5 h-3.5 text-primary" />
                                                <span>{{ __('View Specs') }}</span>
                                            </button>
                                            @if (in_array($item->id, $this->activeReservedItemIds, true))
                                                <span class="badge badge-info badge-sm font-semibold whitespace-nowrap">{{ __('Reserved') }}</span>
                                            @else
                                                <button type="button"
                                                    wire:click="openBookingModal({{ $item->id }})"
                                                    @disabled(! $canBorrow || $item->available_units_count === 0)
                                                    class="btn btn-primary btn-xs gap-1 font-semibold">
                                                    <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                                    <span>{{ __('Request Booking') }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-base-content/60">
                                <x-heroicon-o-archive-box-x-mark class="w-8 h-8 opacity-40 mx-auto mb-2" />
                                <p class="font-medium">{{ __('No equipment items found.') }}</p>
                                <p class="text-xs mt-1">{{ __('Try adjusting your search or filter criteria.') }}</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <!-- Infinite Scrolling Trigger & Indicator -->
            @if ($this->hasMore)
                <div wire:key="browse-load-more-{{ $this->limit }}-{{ $this->totalCount }}-{{ md5((string) $this->search . '|' . $this->categoryId . '|' . $this->availabilityFilter . '|' . $this->selectedOrganizationId) }}"
                    wire:intersect="loadMore"
                    class="flex flex-col items-center justify-center py-6 gap-2">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-xs text-base-content/50">{{ __('Loading more equipment...') }}</span>
                </div>
            @elseif ($this->items->isNotEmpty())
                <div wire:key="browse-all-loaded" class="text-center py-4 text-xs text-base-content/40">
                    {{ __('All :count equipment items loaded.', ['count' => $this->totalCount]) }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. Filter Options Modal                    -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($filterModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ __('Filter Equipment Catalog') }}</h3>

            <form id="browse-filter-form" wire:submit="applyFilters" class="space-y-4">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('College / Hub') }}</legend>
                    <select wire:model.live="filterOrganizationId" class="select select-sm w-full">
                        <option value="all">{{ __('All Colleges (Campus-Wide)') }}</option>
                        @foreach ($this->organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                        @endforeach
                    </select>
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Category') }}</legend>
                    <select wire:model="filterCategoryId" class="select select-sm w-full">
                        <option value="all">{{ __('All Categories') }}</option>
                        @foreach ($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Availability Status') }}</legend>
                    <select wire:model="filterAvailability" class="select select-sm w-full">
                        <option value="all">{{ __('All Items') }}</option>
                        <option value="available_only">{{ __('Available Only') }}</option>
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
                    <button type="submit" form="browse-filter-form" class="btn btn-primary btn-sm">
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
    <!-- 2. View Specifications Modal               -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($specsModal && $this->viewingItem) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->viewingItem)
                @php $specItem = $this->viewingItem; @endphp
                <div class="flex items-start justify-between gap-2 border-b border-base-200 pb-3">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $specItem->organization->code ?? 'N/A' }}</span>
                            @if ($specItem->category)
                                <span class="text-xs font-medium text-base-content/70">{{ $specItem->category->name }}</span>
                            @endif
                        </div>
                        <h3 class="font-bold text-lg mt-1">{{ $specItem->name }}</h3>
                        @if ($specItem->brand || $specItem->model_number)
                            <p class="text-xs text-base-content/60">
                                {{ trim(($specItem->brand ?? '') . ' ' . ($specItem->model_number ?? '')) }}
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="$set('specsModal', false)" class="btn btn-ghost btn-xs btn-circle">✕</button>
                </div>

                <div class="py-4 space-y-3 text-xs">
                    <div class="flex justify-between items-center p-2.5 rounded-lg bg-base-200/70">
                        <span class="text-base-content/70 font-medium">{{ __('Available Units') }}</span>
                        <span class="font-bold text-sm {{ $specItem->available_units_count > 0 ? 'text-success' : 'text-error' }}">
                            {{ $specItem->available_units_count }} <span class="font-normal text-base-content/60">/ {{ $specItem->total_units_count }}</span>
                        </span>
                    </div>

                    @if (! empty($specItem->description))
                        <div>
                            <span class="font-semibold block mb-1 text-base-content/80">{{ __('Description') }}</span>
                            <p class="text-base-content/75 leading-relaxed">{{ $specItem->description }}</p>
                        </div>
                    @endif

                    <div class="pt-2 border-t border-base-200">
                        <span class="font-semibold block mb-1.5 text-base-content/80">{{ __('Technical Specifications') }}</span>
                        @if (empty($specItem->specifications))
                            <p class="text-base-content/60">{{ __('No detailed specifications provided.') }}</p>
                        @elseif (is_array($specItem->specifications))
                            <div class="space-y-1">
                                @foreach ($specItem->specifications as $specKey => $specVal)
                                    <div class="flex justify-between gap-2 py-1 border-b border-base-200/60 last:border-b-0">
                                        <span class="text-base-content/60">{{ is_string($specKey) ? ucfirst(str_replace('_', ' ', $specKey)) : __('Spec') }}</span>
                                        <span class="font-semibold text-right">{{ is_array($specVal) ? implode(', ', $specVal) : $specVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-base-content/80 whitespace-pre-line">{{ $specItem->specifications }}</p>
                        @endif
                    </div>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="$set('specsModal', false)" class="btn btn-sm btn-ghost">{{ __('Close') }}</button>
                    @if (in_array($specItem->id, $this->activeReservedItemIds, true))
                        <span class="badge badge-info font-semibold whitespace-nowrap">{{ __('Already Reserved') }}</span>
                    @elseif ($canBorrow && $specItem->available_units_count > 0)
                        <button type="button" wire:click="$set('specsModal', false); openBookingModal({{ $specItem->id }})" class="btn btn-sm btn-primary gap-1">
                            <x-heroicon-o-calendar-days class="w-4 h-4" />
                            <span>{{ __('Request Booking') }}</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('specsModal', false)">close</button>
        </form>
    </dialog>

    <!-- ========================================== -->
    <!-- 3. Request Booking Modal                   -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($bookingModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-lg">{{ __('Request Equipment Booking') }}</h3>
            @if ($this->bookingItem)
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ $this->bookingItem->name }} ({{ $this->bookingItem->organization->code ?? 'N/A' }})
                </p>
            @endif

            <form id="booking-request-form" wire:submit="submitBooking" class="space-y-3 mt-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Pickup Date & Time') }} *</legend>
                        <input type="datetime-local" wire:model="bookingStartTime" class="input input-sm w-full @error('bookingStartTime') input-error @enderror" required />
                        @error('bookingStartTime') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Return Date & Time') }} *</legend>
                        <input type="datetime-local" wire:model="bookingEndTime" class="input input-sm w-full @error('bookingEndTime') input-error @enderror" required />
                        @error('bookingEndTime') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Academic / Lab Purpose') }} *</legend>
                    <textarea wire:model="bookingPurpose" rows="3" class="textarea textarea-sm w-full @error('bookingPurpose') textarea-error @enderror"
                        placeholder="{{ __('e.g. Capstone prototype testing in Lab 302') }}" required></textarea>
                    @error('bookingPurpose') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('bookingModal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="booking-request-form" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="submitBooking">
                    <span wire:loading wire:target="submitBooking" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Submit Request') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('bookingModal', false)">close</button>
        </form>
    </dialog>
</div>
