<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('Browse Equipment'), 'icon' => 'heroicon-o-magnifying-glass'],
    ]">
        <x-slot:actions>
            <a href="{{ route('portal.reservations') }}" class="btn btn-ghost btn-sm font-semibold gap-1">
                <x-heroicon-o-calendar-days class="w-4 h-4" />
                <span>{{ __('My Reservations') }}</span>
            </a>
        </x-slot:actions>
    </x-breadcrumbs>

    @php
        $borrower = auth()->user();
        $canBorrow = $borrower ? $borrower->canBorrow() : true;
    @endphp

    @if (! $canBorrow)
        <div role="alert" class="alert alert-error text-xs py-2.5 px-3 mt-2 shadow-xs">
            <x-heroicon-o-shield-exclamation class="w-5 h-5 shrink-0" />
            <div>
                <span class="font-bold block">{{ __('Borrowing Privileges Suspended') }}</span>
                <span>{{ implode(' ', $borrower->getBorrowingBlockReasons()) }}</span>
            </div>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-3">
            <div class="flex flex-col md:flex-row gap-2">
                <label class="input input-sm flex items-center gap-2 flex-1">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search equipment, brand, model, or specs...') }}"
                        class="grow text-xs" />
                </label>

                <select wire:model.live="selectedOrganizationId" class="select select-sm w-full md:w-48 text-xs">
                    <option value="all">{{ __('All Colleges') }}</option>
                    @foreach ($this->organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="categoryId" class="select select-sm w-full md:w-44 text-xs">
                    <option value="all">{{ __('All Categories') }}</option>
                    @foreach ($this->categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="availabilityFilter" class="select select-sm w-full md:w-40 text-xs">
                    <option value="all">{{ __('All Items') }}</option>
                    <option value="available_only">{{ __('Available Only') }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Equipment Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
        @forelse ($this->items as $item)
            <div class="card bg-base-100 card-border border-base-300 shadow-xs flex flex-col justify-between">
                <div class="card-body p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="badge badge-ghost badge-xs font-semibold">{{ $item->organization->code }}</span>
                                @if ($item->category)
                                    <span class="badge badge-outline badge-xs">{{ $item->category->name }}</span>
                                @endif
                            </div>
                            <h3 class="font-bold text-sm text-base-content truncate">{{ $item->name }}</h3>
                            <p class="text-xs text-base-content/60 truncate">
                                {{ trim(($item->brand ?? '') . ' ' . ($item->model ?? '')) ?: __('Standard Lab Equipment') }}
                            </p>
                        </div>

                        <span class="badge badge-xs shrink-0 {{ $item->available_units_count > 0 ? 'badge-success' : 'badge-ghost' }}">
                            {{ $item->available_units_count > 0 ? __(':count available', ['count' => $item->available_units_count]) : __('Unavailable') }}
                        </span>
                    </div>

                    @if (! empty($item->specifications))
                        @if (is_array($item->specifications))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach (array_slice($item->specifications, 0, 3, true) as $specKey => $specVal)
                                    <span class="badge badge-ghost badge-xs text-[10px]">
                                        {{ $specKey }}: {{ is_array($specVal) ? implode(', ', $specVal) : $specVal }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-base-content/75 line-clamp-2 mt-2">
                                {{ $item->specifications }}
                            </p>
                        @endif
                    @endif

                    <div class="card-actions justify-end items-center gap-2 pt-3 mt-2 border-t border-base-200">
                        <button type="button" wire:click="openSpecsModal({{ $item->id }})" class="btn btn-ghost btn-xs font-semibold">
                            {{ __('View Specs') }}
                        </button>
                        <button type="button"
                            wire:click="openBookingModal({{ $item->id }})"
                            @disabled(! $canBorrow || $item->available_units_count === 0)
                            class="btn btn-primary btn-xs font-semibold gap-1">
                            <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                            <span>{{ __('Request Booking') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-8 text-center text-xs text-base-content/60">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 mx-auto mb-2 opacity-40" />
                    <span class="font-bold text-sm text-base-content block">{{ __('No Equipment Found') }}</span>
                    <span class="mt-1 block">{{ __('Try adjusting your college, category, or search filters.') }}</span>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $this->items->links() }}
    </div>

    <!-- View Specs Modal -->
    <dialog class="modal" @if ($specsModal && $this->viewingItem) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->viewingItem)
                @php $specItem = $this->viewingItem; @endphp
                <div class="flex items-start justify-between gap-2 border-b border-base-200 pb-2">
                    <div>
                        <span class="badge badge-ghost badge-xs font-semibold">{{ $specItem->organization->code }}</span>
                        <h3 class="font-bold text-base mt-1">{{ $specItem->name }}</h3>
                        <p class="text-xs text-base-content/60">{{ trim(($specItem->brand ?? '') . ' ' . ($specItem->model ?? '')) }}</p>
                    </div>
                    <button type="button" wire:click="$set('specsModal', false)" class="btn btn-ghost btn-xs btn-circle">✕</button>
                </div>

                <div class="py-3 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Category') }}</span>
                        <span class="font-semibold">{{ $specItem->category?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Available Units') }}</span>
                        <span class="font-bold {{ $specItem->available_units_count > 0 ? 'text-success' : 'text-error' }}">
                            {{ $specItem->available_units_count }}
                        </span>
                    </div>
                    <div class="pt-2 border-t border-base-200">
                        <span class="font-semibold block mb-1">{{ __('Technical Specifications') }}</span>
                        @if (empty($specItem->specifications))
                            <p class="text-base-content/60">{{ __('No detailed specifications provided.') }}</p>
                        @elseif (is_array($specItem->specifications))
                            <div class="space-y-1">
                                @foreach ($specItem->specifications as $specKey => $specVal)
                                    <div class="flex justify-between gap-2 py-0.5 border-b border-base-200/60 last:border-b-0">
                                        <span class="text-base-content/60">{{ $specKey }}</span>
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
                    @if ($canBorrow && $specItem->available_units_count > 0)
                        <button type="button" wire:click="$set('specsModal', false); openBookingModal({{ $specItem->id }})" class="btn btn-sm btn-primary">
                            {{ __('Request Booking') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>
        <div class="modal-backdrop" wire:click="$set('specsModal', false)"></div>
    </dialog>

    <!-- Request Booking Modal -->
    <dialog class="modal" @if ($bookingModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base">{{ __('Request Equipment Booking') }}</h3>
            <div class="space-y-3 mt-3 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Pickup Date & Time') }} *</legend>
                        <input type="datetime-local" wire:model="bookingStartTime" class="input input-sm w-full" />
                        @error('bookingStartTime') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Return Date & Time') }} *</legend>
                        <input type="datetime-local" wire:model="bookingEndTime" class="input input-sm w-full" />
                        @error('bookingEndTime') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Academic / Lab Purpose') }} *</legend>
                    <textarea wire:model="bookingPurpose" rows="3" class="textarea textarea-sm w-full"
                        placeholder="{{ __('e.g. Capstone prototype testing in Lab 302') }}"></textarea>
                    @error('bookingPurpose') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('bookingModal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm" wire:click="submitBooking" wire:loading.attr="disabled" wire:target="submitBooking">
                    <span wire:loading wire:target="submitBooking" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Submit Request') }}</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('bookingModal', false)"></div>
    </dialog>
</div>
