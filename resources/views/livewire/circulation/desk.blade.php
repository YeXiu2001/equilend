<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Circulation & Desk'), 'icon' => 'heroicon-o-arrows-right-left'],
        ['label' => __('Circulation Desk Handover'), 'icon' => 'heroicon-o-building-storefront'],
    ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasRole('superadmin'))
                    <select wire:model.live="selectedOrganizationId" class="select select-sm select-bordered w-48 text-xs font-semibold">
                        <option value="all">{{ __('All Colleges (Campus Hub)') }}</option>
                        @foreach ($this->organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 bg-base-200 border border-base-300 px-2.5 py-1 rounded-md whitespace-nowrap">
                        <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                        {{ auth()->user()?->organization?->code ?? 'Hub' }}
                    </span>
                @endif

                <!-- Mode Switcher Tabs -->
                <div class="join">
                    <button type="button"
                        wire:click="setMode('checkout')"
                        class="join-item btn btn-sm {{ $mode === 'checkout' ? 'btn-primary' : 'btn-ghost' }} gap-1 font-semibold">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                        <span>{{ __('Check-Out') }}</span>
                    </button>
                    <button type="button"
                        wire:click="setMode('checkin')"
                        class="join-item btn btn-sm {{ $mode === 'checkin' ? 'btn-primary' : 'btn-ghost' }} gap-1 font-semibold">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                        <span>{{ __('Check-In') }}</span>
                    </button>
                </div>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    @if ($mode === 'checkout')
        <!-- CHECK-OUT INTERFACE -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-2">
            <!-- Left 2 Cols: Check-Out Wizard / Form -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Checkout Source Toggle: Walk-In vs From Reservation -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="join w-full sm:w-auto">
                            <button type="button"
                                wire:click="setCheckoutSource('walkin')"
                                class="join-item btn btn-sm flex-1 sm:flex-none gap-1.5 {{ $checkoutSource === 'walkin' ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                                <x-heroicon-o-user-plus class="w-4 h-4" />
                                <span>{{ __('Walk-In Checkout') }}</span>
                            </button>
                            <button type="button"
                                wire:click="setCheckoutSource('reservation')"
                                class="join-item btn btn-sm flex-1 sm:flex-none gap-1.5 {{ $checkoutSource === 'reservation' ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                                <x-heroicon-o-calendar-days class="w-4 h-4" />
                                <span>{{ __('From Reservation') }}</span>
                                @if ($this->approvedReservationsCount > 0)
                                    <span class="badge badge-xs whitespace-nowrap {{ $checkoutSource === 'reservation' ? 'badge-neutral' : 'badge-info' }}">
                                        {{ $this->approvedReservationsCount }}
                                    </span>
                                @endif
                            </button>
                        </div>

                        @if ($checkoutSource === 'reservation' || $this->approvedReservationsCount > 0)
                            <button type="button" wire:click="openReservationModal" class="btn btn-sm btn-outline btn-primary gap-1.5 shrink-0">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                <span>{{ $selectedReservationId ? __('Change Reservation') : __('Browse Approved Bookings') }}</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if ($selectedReservationId)
                    <div class="alert alert-success shadow-xs py-2.5 px-3 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-5 h-5 shrink-0" />
                            <div>
                                <span class="font-bold">{{ __('Fulfilling Approved Reservation #:id', ['id' => $selectedReservationId]) }}</span>
                                <span class="block text-base-content/80">{{ __('Borrower, equipment, and schedule pre-populated from booking record.') }}</span>
                            </div>
                        </div>
                        <button type="button" wire:click="clearReservation" class="btn btn-ghost btn-xs text-error font-semibold">
                            {{ __('Switch to Walk-In') }}
                        </button>
                    </div>
                @endif

                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="card-title text-base flex items-center gap-2 font-bold text-base-content">
                                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">1</span>
                                <span>{{ __('Borrower / Student Identification') }}</span>
                            </h2>
                            <span class="text-[11px] font-medium text-base-content/60 whitespace-nowrap">{{ __('All Colleges') }}</span>
                        </div>

                        <div class="space-y-3 mt-2">
                            @if (! $selectedUserId)
                                <fieldset class="fieldset w-full">
                                    <legend class="fieldset-legend font-semibold">{{ __('Search Student Name or Campus Email') }} *</legend>
                                    <label class="input input-sm flex items-center gap-2 w-full @error('selectedUserId') input-error @enderror">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                                        <input type="search"
                                            wire:model.live.debounce.300ms="studentSearch"
                                            placeholder="{{ __('Type at least 2 characters (name or email)...') }}"
                                            class="grow text-xs" />
                                        <span wire:loading wire:target="studentSearch" class="loading loading-spinner loading-xs text-primary"></span>
                                    </label>
                                    @error('selectedUserId')
                                        <p class="label text-error text-xs">{{ $message }}</p>
                                    @enderror
                                </fieldset>

                                @if (mb_strlen(mb_trim($studentSearch)) < 2)
                                    <p class="text-[11px] text-base-content/60">
                                        {{ __('Type at least 2 characters to search borrowers across all colleges (shows top 10 matches).') }}
                                    </p>
                                @elseif ($this->students->isNotEmpty())
                                    <div class="bg-base-200/50 rounded-box p-2 border border-base-300 max-h-56 overflow-y-auto space-y-1">
                                        @foreach ($this->students as $student)
                                            <button type="button"
                                                wire:click="selectStudent({{ $student->id }})"
                                                class="w-full text-left flex items-center justify-between gap-2 p-2 rounded hover:bg-base-100 transition text-xs">
                                                <div class="min-w-0">
                                                    <span class="font-bold block text-base-content truncate">{{ $student->name }}</span>
                                                    <span class="text-base-content/60 text-[11px] truncate block">{{ $student->email }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    @foreach ($student->organizations->take(2) as $org)
                                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $org->code }}</span>
                                                    @endforeach
                                                    <span class="text-xs font-semibold text-primary whitespace-nowrap">{{ __('Select') }}</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-xs text-base-content/60 bg-base-200/40 rounded-box p-3 border border-base-300">
                                        {{ __('No borrowers found matching ":query". Try refining with full name or email.', ['query' => mb_trim($studentSearch)]) }}
                                    </div>
                                @endif
                            @else
                                @php $borrower = $this->selectedBorrower; @endphp
                                <div class="flex items-center justify-between bg-primary/5 p-3 rounded-box border border-primary/20 text-xs">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="avatar avatar-placeholder shrink-0">
                                            <div class="bg-primary text-primary-content rounded-full w-9 h-9 text-xs font-bold flex items-center justify-center">
                                                {{ mb_strtoupper(mb_substr($borrower?->name ?? $studentSearch, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-sm text-base-content truncate">{{ $borrower?->name ?? $studentSearch }}</span>
                                                @if ($borrower)
                                                    @foreach ($borrower->organizations as $org)
                                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $org->code }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <span class="text-base-content/60 text-[11px] block truncate">{{ $borrower?->email ?? __('Verified Student Borrower') }}</span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="clearSelectedStudent" class="btn btn-ghost btn-xs text-base-content/70 shrink-0">
                                        {{ __('Change') }}
                                    </button>
                                </div>

                                @if ($borrower && ! $borrower->canBorrow())
                                    <div role="alert" class="alert alert-error text-xs py-2.5 px-3 shadow-xs">
                                        <x-heroicon-o-shield-exclamation class="w-5 h-5 shrink-0" />
                                        <div>
                                            <span class="font-bold block">{{ __('Borrowing Suspended — Checkout Blocked') }}</span>
                                            <ul class="list-disc list-inside mt-1 space-y-0.5">
                                                @foreach ($borrower->getBorrowingBlockReasons() as $reason)
                                                    <li>{{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Step 2: Equipment & Serialized Unit -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-4 sm:p-5">
                        <h2 class="card-title text-base flex items-center gap-2 font-bold text-base-content">
                            <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">2</span>
                            <span>{{ __('Equipment Catalog & Physical Serial Unit') }}</span>
                        </h2>

                        <div class="space-y-4 mt-2">
                            <!-- Equipment Item Typeahead -->
                            <div class="space-y-2">
                                @if (! $selectedItemId)
                                    <fieldset class="fieldset w-full">
                                        <legend class="fieldset-legend font-semibold">{{ __('Search Equipment Item') }} *</legend>
                                        <label class="input input-sm flex items-center gap-2 w-full">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                                            <input type="search"
                                                wire:model.live.debounce.300ms="itemSearch"
                                                placeholder="{{ __('Type at least 2 characters (equipment name, brand, or model)...') }}"
                                                class="grow text-xs" />
                                            <span wire:loading wire:target="itemSearch" class="loading loading-spinner loading-xs text-primary"></span>
                                        </label>
                                    </fieldset>

                                    @if (mb_strlen(mb_trim($itemSearch)) < 2)
                                        <p class="text-[11px] text-base-content/60">
                                            {{ __('Type at least 2 characters to search equipment catalog items, or scan/search an asset tag below.') }}
                                        </p>
                                    @elseif ($this->items->isNotEmpty())
                                        <div class="bg-base-200/50 rounded-box p-2 border border-base-300 max-h-56 overflow-y-auto space-y-1">
                                            @foreach ($this->items as $item)
                                                <button type="button"
                                                    wire:key="desk-item-{{ $item->id }}"
                                                    wire:click="selectItem({{ $item->id }})"
                                                    class="w-full text-left flex items-center justify-between gap-2 p-2 rounded hover:bg-base-100 transition text-xs">
                                                    <div class="min-w-0">
                                                        <span class="font-bold block text-base-content truncate">{{ $item->name }}</span>
                                                        <span class="text-base-content/60 text-[11px] truncate block">
                                                            {{ trim(($item->brand ?? '') . ' ' . ($item->model_number ?? '')) ?: __('Standard Equipment') }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        @if ($item->organization?->code)
                                                            <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $item->organization->code }}</span>
                                                        @endif
                                                        <span class="text-[11px] font-semibold {{ $item->available_units_count > 0 ? 'text-success' : 'text-error' }} whitespace-nowrap">
                                                            {{ $item->available_units_count }} {{ __('avail.') }}
                                                        </span>
                                                        <span class="text-xs font-semibold text-primary whitespace-nowrap">{{ __('Select') }}</span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-xs text-base-content/60 bg-base-200/40 rounded-box p-3 border border-base-300">
                                            {{ __('No equipment found matching ":query".', ['query' => mb_trim($itemSearch)]) }}
                                        </div>
                                    @endif
                                @else
                                    @php $chosenItem = $this->selectedItem; @endphp
                                    <div class="flex items-center justify-between bg-primary/5 p-3 rounded-box border border-primary/20 text-xs">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-sm text-base-content truncate">{{ $chosenItem?->name ?? $itemSearch }}</span>
                                                @if ($chosenItem?->organization?->code)
                                                    <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $chosenItem->organization->code }}</span>
                                                @endif
                                            </div>
                                            @if ($chosenItem && ($chosenItem->brand || $chosenItem->model_number))
                                                <span class="text-base-content/60 text-[11px] block truncate">
                                                    {{ trim(($chosenItem->brand ?? '') . ' ' . ($chosenItem->model_number ?? '')) }}
                                                </span>
                                            @endif
                                        </div>
                                        <button type="button" wire:click="clearSelectedItem" class="btn btn-ghost btn-xs text-base-content/70 shrink-0">
                                            {{ __('Change') }}
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Asset Tag / Barcode Typeahead -->
                            <div class="space-y-2 pt-2 border-t border-base-200">
                                @if (! $selectedUnitId)
                                    <fieldset class="fieldset w-full">
                                        <legend class="fieldset-legend font-semibold">{{ __('Search Asset Tag / Scan Barcode') }} *</legend>
                                        <label class="input input-sm flex items-center gap-2 w-full @error('selectedUnitId') input-error @enderror">
                                            <x-heroicon-o-qr-code class="w-4 h-4 opacity-60 text-primary" />
                                            <input type="search"
                                                wire:model.live.debounce.300ms="unitSearch"
                                                placeholder="{{ __('Type at least 2 characters (e.g. CCS-LPT-001) or scan barcode...') }}"
                                                class="grow text-xs font-mono" />
                                            <span wire:loading wire:target="unitSearch" class="loading loading-spinner loading-xs text-primary"></span>
                                        </label>
                                        @error('selectedUnitId')
                                            <p class="label text-error text-xs">{{ $message }}</p>
                                        @enderror
                                    </fieldset>

                                    @if (mb_strlen(mb_trim($unitSearch)) >= 2)
                                        @if ($this->matchingUnits->isNotEmpty())
                                            <div class="bg-base-200/50 rounded-box p-2 border border-base-300 max-h-56 overflow-y-auto space-y-1">
                                                @foreach ($this->matchingUnits as $unit)
                                                    <button type="button"
                                                        wire:key="desk-unit-match-{{ $unit->id }}"
                                                        wire:click="selectUnit({{ $unit->id }})"
                                                        class="w-full text-left flex items-center justify-between gap-2 p-2 rounded hover:bg-base-100 transition text-xs">
                                                        <div class="min-w-0">
                                                            <span class="font-mono font-bold block text-base-content truncate">{{ $unit->asset_tag }}</span>
                                                            <span class="text-base-content/60 text-[11px] truncate block">
                                                                {{ $unit->item?->name ?? __('Equipment') }}
                                                                @if ($unit->serial_number)
                                                                    • SN: {{ $unit->serial_number }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center gap-2 shrink-0">
                                                            <span class="text-[11px] font-semibold whitespace-nowrap {{ $unit->condition->textClass() }}">{{ $unit->condition->label() }}</span>
                                                            <span class="text-xs font-semibold text-primary whitespace-nowrap">{{ __('Select') }}</span>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-xs text-base-content/60 bg-base-200/40 rounded-box p-3 border border-base-300">
                                                {{ __('No available units found matching ":query".', ['query' => mb_trim($unitSearch)]) }}
                                            </div>
                                        @endif
                                    @elseif ($selectedItemId)
                                        @if ($this->availableUnits->isEmpty())
                                            <div class="alert alert-warning text-xs py-2 px-3">
                                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0" />
                                                <span>{{ __('No available units in stock for this equipment catalog item.') }}</span>
                                            </div>
                                        @else
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1.5 bg-base-200/40 rounded-box border border-base-300">
                                                @foreach ($this->availableUnits as $unit)
                                                    <button type="button"
                                                        wire:key="desk-avail-unit-{{ $unit->id }}"
                                                        wire:click="selectUnit({{ $unit->id }})"
                                                        class="p-2 rounded text-left border transition text-xs flex items-center justify-between bg-base-100 border-base-300 hover:border-primary/50">
                                                        <div>
                                                            <span class="font-mono font-bold block text-base-content">{{ $unit->asset_tag }}</span>
                                                            <span class="text-[10px] text-base-content/60">{{ $unit->serial_number ?? __('No Serial') }}</span>
                                                        </div>
                                                        <div class="text-right flex flex-col items-end gap-1">
                                                            <span class="text-[11px] font-semibold whitespace-nowrap {{ $unit->condition->textClass() }}">{{ $unit->condition->label() }}</span>
                                                            <span class="text-[11px] font-semibold text-primary whitespace-nowrap">{{ __('Select') }}</span>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-[11px] text-base-content/60">
                                            {{ __('Type at least 2 characters to search asset tags or serial numbers (shows top 10 available matches).') }}
                                        </p>
                                    @endif
                                @else
                                    @php $chosenUnit = $this->selectedUnit; @endphp
                                    <div class="flex items-center justify-between bg-primary/5 p-3 rounded-box border border-primary/20 text-xs">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-mono font-bold text-sm text-primary">{{ $chosenUnit?->asset_tag ?? $unitSearch }}</span>
                                                @if ($chosenUnit)
                                                    <span class="badge badge-xs {{ $chosenUnit->condition->badgeClass() }}">{{ $chosenUnit->condition->label() }}</span>
                                                @endif
                                            </div>
                                            <span class="text-base-content/60 text-[11px] block truncate">
                                                {{ $chosenUnit?->serial_number ? __('SN: :sn', ['sn' => $chosenUnit->serial_number]) : __('No Serial Number') }}
                                            </span>
                                        </div>
                                        <button type="button" wire:click="clearSelectedUnit" class="btn btn-ghost btn-xs text-base-content/70 shrink-0">
                                            {{ __('Change') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Terms, Due Date & Condition -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-4 sm:p-5">
                        <h2 class="card-title text-base flex items-center gap-2 font-bold text-base-content">
                            <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">3</span>
                            <span>{{ __('Return Deadline & Initial Condition') }}</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2 items-start">
                            <!-- Due Date Picker & Quick Buttons -->
                            <fieldset class="fieldset w-full">
                                <legend class="fieldset-legend font-semibold">{{ __('Return Deadline') }} *</legend>
                                <input type="datetime-local"
                                    wire:model="dueAt"
                                    class="input input-sm w-full text-xs font-mono @error('dueAt') input-error @enderror"
                                    required />
                                <div class="flex items-center gap-1 mt-1.5">
                                    <span class="text-[11px] text-base-content/60 mr-1">{{ __('Quick:') }}</span>
                                    <button type="button" wire:click="setDueDays(1)" class="btn btn-ghost btn-xs border border-base-300 text-[10px] px-2 h-6 min-h-0">+1D</button>
                                    <button type="button" wire:click="setDueDays(2)" class="btn btn-ghost btn-xs border border-base-300 text-[10px] px-2 h-6 min-h-0">+2D</button>
                                    <button type="button" wire:click="setDueDays(3)" class="btn btn-ghost btn-xs border border-base-300 text-[10px] px-2 h-6 min-h-0">+3D</button>
                                    <button type="button" wire:click="setDueDays(7)" class="btn btn-ghost btn-xs border border-base-300 text-[10px] px-2 h-6 min-h-0">+1W</button>
                                </div>
                                @error('dueAt')
                                    <p class="label text-error text-xs">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <!-- Initial Checkout Condition -->
                            <fieldset class="fieldset w-full">
                                <legend class="fieldset-legend font-semibold">{{ __('Handover Condition') }} *</legend>
                                <select wire:model="checkoutCondition" class="select select-sm w-full text-xs">
                                    <option value="new">{{ __('Brand New (Mint)') }}</option>
                                    <option value="good">{{ __('Good (Fully Functional)') }}</option>
                                    <option value="fair">{{ __('Fair (Minor Cosmetic Scratches)') }}</option>
                                </select>
                            </fieldset>
                        </div>

                        <fieldset class="fieldset w-full mt-2">
                            <legend class="fieldset-legend font-semibold">{{ __('Check-Out Notes / Purpose') }}</legend>
                            <input type="text"
                                wire:model="checkoutNotes"
                                placeholder="{{ __('e.g. For Capstone lab testing, room 304') }}"
                                class="input input-sm w-full text-xs" />
                        </fieldset>

                        <div class="mt-4 pt-3 border-t border-base-200 flex justify-end">
                            <button type="button"
                                wire:click="processCheckout"
                                wire:loading.attr="disabled"
                                @disabled($this->selectedBorrower && ! $this->selectedBorrower->canBorrow())
                                class="btn btn-primary btn-sm px-6 font-semibold gap-2 shadow-xs">
                                <span wire:loading wire:target="processCheckout" class="loading loading-spinner loading-xs"></span>
                                <x-heroicon-o-check class="w-4 h-4" />
                                <span>{{ __('Authorize & Complete Check-Out') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Summary Panel & Quick Guidelines -->
            <div class="space-y-4">
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-4 sm:p-5">
                        <h3 class="font-bold text-sm text-base-content flex items-center gap-2 border-b border-base-200 pb-2">
                            <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-primary" />
                            <span>{{ __('Check-Out Summary') }}</span>
                        </h3>

                        <div class="space-y-3 text-xs mt-2">
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="text-base-content/60">{{ __('Issuer Officer') }}</span>
                                <span class="font-semibold text-base-content">{{ auth()->user()?->name }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="text-base-content/60">{{ __('College Hub') }}</span>
                                <span class="font-semibold text-base-content">{{ auth()->user()?->organization?->code ?? 'Campus' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="text-base-content/60">{{ __('Borrower') }}</span>
                                <span class="font-semibold text-primary truncate max-w-[140px]">{{ $studentSearch ?: __('Not Selected') }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="text-base-content/60">{{ __('Unit Selected') }}</span>
                                <span class="font-mono font-bold text-base-content">
                                    {{ $selectedUnitId ? \App\Models\ItemUnit::find($selectedUnitId)?->asset_tag : __('None') }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-base-content/60">{{ __('Return Deadline') }}</span>
                                <span class="font-mono text-base-content text-[11px]">
                                    {{ $dueAt ? \Illuminate\Support\Carbon::parse($dueAt)->format('M d, Y H:i') : __('Not Set') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-200/50 card-border border-base-300 shadow-xs text-xs">
                    <div class="card-body p-4 space-y-2">
                        <span class="font-bold text-base-content flex items-center gap-1.5">
                            <x-heroicon-o-shield-check class="w-4 h-4 text-success" />
                            <span>{{ __('Circulation Protocol') }}</span>
                        </span>
                        <p class="text-base-content/70 leading-relaxed">
                            {{ __('Ensure student inspects physical serial unit and accessories before authorization. Borrowed units are locked from reservations.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    @else
        <!-- CHECK-IN / RETURN DESK INTERFACE -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-2">
            <!-- Left 2 Cols: Active Loans Lookup & Inspector -->
            <div class="lg:col-span-2 space-y-4">
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                            <h2 class="card-title text-base font-bold text-base-content flex items-center gap-2">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-primary" />
                                <span>{{ __('Look Up Active Loan for Return') }}</span>
                            </h2>
                            <span class="text-xs text-base-content/60">
                                {{ __('Showing :count active/overdue loans', ['count' => $this->activeLoansForCheckin->count()]) }}
                            </span>
                        </div>

                        <label class="input input-sm flex items-center gap-2 w-full mb-3">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60 text-primary" />
                            <input type="search"
                                wire:model.live.debounce.250ms="checkinSearch"
                                placeholder="{{ __('Search by student name, email, equipment title, or scan asset tag...') }}"
                                class="grow text-xs" />
                        </label>

                        <!-- Active Loans List -->
                        <div class="divide-y divide-base-200 overflow-hidden border border-base-300 rounded-box max-h-96 overflow-y-auto">
                            @forelse ($this->activeLoansForCheckin as $loan)
                                <div class="p-3 flex items-center justify-between hover:bg-base-200/50 transition cursor-pointer text-xs {{ $inspectingLoanId === $loan->id ? 'bg-primary/10 border-l-4 border-primary' : '' }}"
                                    wire:click="inspectLoan({{ $loan->id }})">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-base-content">{{ $loan->user->name }}</span>
                                            <span class="badge badge-xs whitespace-nowrap {{ $loan->status->badgeClass() }}">{{ $loan->status->label() }}</span>
                                            @if ($loan->isOverdue())
                                                <span class="badge badge-xs badge-error gap-1 whitespace-nowrap">
                                                    <x-heroicon-o-exclamation-triangle class="w-3 h-3" />
                                                    {{ __('Overdue') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-base-content/70 flex items-center gap-2 text-[11px]">
                                            <span class="font-semibold">{{ $loan->item->name }}</span>
                                            <span>•</span>
                                            <span class="font-mono bg-base-300 px-1 py-0.5 rounded">{{ $loan->itemUnit->asset_tag }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end gap-1">
                                        <span class="text-base-content/60 text-[11px]">
                                            {{ __('Due: :date', ['date' => $loan->due_at->format('M d, H:i')]) }}
                                        </span>
                                        <button type="button" class="btn btn-xs {{ $inspectingLoanId === $loan->id ? 'btn-primary' : 'btn-ghost' }} font-semibold">
                                            {{ $inspectingLoanId === $loan->id ? __('Inspecting') : __('Check-In') }}
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center text-base-content/60 text-xs">
                                    <x-heroicon-o-check-circle class="w-8 h-8 mx-auto mb-2 text-success opacity-80" />
                                    <span>{{ __('No active loans matching your search query.') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Check-In Return Inspector Dialog / Card -->
            <div>
                @if ($inspectingLoanId)
                    @php $currentLoan = \App\Models\Loan::with(['user', 'item', 'itemUnit'])->find($inspectingLoanId); @endphp
                    @if ($currentLoan)
                        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                            <div class="card-body p-4 sm:p-5">
                                <h3 class="card-title text-sm font-bold flex items-center justify-between border-b border-base-200 pb-2">
                                    <span class="flex items-center gap-1.5">
                                        <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-primary" />
                                        <span>{{ __('Inspect & Check-In Loan #:id', ['id' => $currentLoan->id]) }}</span>
                                    </span>
                                    <button type="button" wire:click="$set('inspectingLoanId', null)" class="btn btn-ghost btn-xs btn-circle">✕</button>
                                </h3>

                                <div class="bg-base-200/50 p-3 rounded-box space-y-1.5 text-xs my-3">
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Borrower') }}:</span>
                                        <span class="font-bold text-base-content">{{ $currentLoan->user->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Equipment') }}:</span>
                                        <span class="font-semibold text-base-content">{{ $currentLoan->item->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Unit Tag') }}:</span>
                                        <span class="font-mono font-bold text-primary">{{ $currentLoan->itemUnit->asset_tag }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Borrowed') }}:</span>
                                        <span>{{ $currentLoan->borrowed_at->format('M d, H:i') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Due Date') }}:</span>
                                        <span class="{{ $currentLoan->isOverdue() ? 'text-error font-bold' : '' }}">
                                            {{ $currentLoan->due_at->format('M d, H:i') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-base-content/60">{{ __('Out Condition') }}:</span>
                                        <span class="text-xs font-semibold whitespace-nowrap {{ $currentLoan->checkout_condition->textClass() }}">{{ $currentLoan->checkout_condition->label() }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3 text-xs">
                                    <fieldset class="fieldset w-full">
                                        <legend class="fieldset-legend font-semibold">{{ __('Return Physical Condition') }} *</legend>
                                        <select wire:model="checkinCondition" class="select select-sm w-full text-xs">
                                            <option value="good">{{ __('Good (Fully Intact & Operational)') }}</option>
                                            <option value="fair">{{ __('Fair (Minor Wear, Functional)') }}</option>
                                            <option value="poor">{{ __('Poor / Damaged (Requires Service)') }}</option>
                                        </select>
                                    </fieldset>

                                    <!-- Damage Toggle -->
                                    <label class="label cursor-pointer justify-start gap-2 bg-error/5 p-2 rounded-box border border-error/20">
                                        <input type="checkbox" wire:model.live="isDamaged" class="checkbox checkbox-error checkbox-sm" />
                                        <span class="label-text text-xs font-semibold text-error">
                                            {{ __('Mark as Damaged / Report Hardware Defect') }}
                                        </span>
                                    </label>

                                    <!-- Assess Penalty Toggle & Fields -->
                                    <label class="label cursor-pointer justify-start gap-2 bg-warning/5 p-2 rounded-box border border-warning/20">
                                        <input type="checkbox" wire:model.live="assessPenalty" class="checkbox checkbox-warning checkbox-sm" />
                                        <span class="label-text text-xs font-semibold text-warning-content">
                                            {{ __('Assess Penalty / Fine on Return') }}
                                        </span>
                                    </label>

                                    @if ($assessPenalty)
                                        <div class="bg-base-200/60 p-3 rounded-box border border-base-300 space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <fieldset class="fieldset w-full">
                                                    <legend class="fieldset-legend font-semibold text-xs">{{ __('Penalty Type') }} *</legend>
                                                    <select wire:model="penaltyType" class="select select-xs w-full">
                                                        <option value="overdue">{{ __('Overdue') }}</option>
                                                        <option value="damage">{{ __('Damage') }}</option>
                                                        <option value="loss">{{ __('Loss') }}</option>
                                                        <option value="other">{{ __('Other') }}</option>
                                                    </select>
                                                </fieldset>
                                                <fieldset class="fieldset w-full">
                                                    <legend class="fieldset-legend font-semibold text-xs">{{ __('Amount (₱)') }} *</legend>
                                                    <input type="number" step="0.01" min="1" wire:model="penaltyAmount"
                                                        placeholder="500.00"
                                                        class="input input-xs w-full @error('penaltyAmount') input-error @enderror" />
                                                    @error('penaltyAmount')
                                                        <p class="label text-error text-[10px]">{{ $message }}</p>
                                                    @enderror
                                                </fieldset>
                                            </div>
                                            <fieldset class="fieldset w-full">
                                                <legend class="fieldset-legend font-semibold text-xs">{{ __('Assessment Remarks') }} *</legend>
                                                <textarea wire:model="penaltyNotes" rows="2"
                                                    placeholder="{{ __('Describe damage, missing accessories, or overdue calculation...') }}"
                                                    class="textarea textarea-xs w-full @error('penaltyNotes') textarea-error @enderror"></textarea>
                                                @error('penaltyNotes')
                                                    <p class="label text-error text-[10px]">{{ $message }}</p>
                                                @enderror
                                            </fieldset>
                                        </div>
                                    @endif

                                    <fieldset class="fieldset w-full">
                                        <legend class="fieldset-legend font-semibold">{{ __('Receiving Notes') }}</legend>
                                        <textarea wire:model="checkinNotes"
                                            rows="2"
                                            placeholder="{{ __('e.g. Returned with case and charger, clean condition.') }}"
                                            class="textarea textarea-sm w-full text-xs"></textarea>
                                    </fieldset>

                                    <div class="pt-2">
                                        <button type="button"
                                            wire:click="processCheckin"
                                            wire:loading.attr="disabled"
                                            class="btn btn-primary btn-sm w-full font-semibold gap-1.5 shadow-xs">
                                            <span wire:loading wire:target="processCheckin" class="loading loading-spinner loading-xs"></span>
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                            <span>{{ __('Confirm Return & Check-In') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                        <div class="card-body p-6 text-center text-xs text-base-content/60">
                            <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 mx-auto mb-2 text-primary opacity-60" />
                            <span class="font-semibold block text-base-content">{{ __('No Loan Selected') }}</span>
                            <span class="mt-1 block">{{ __('Select an active loan from the left list to perform inspection and check-in return.') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Searchable Approved Reservations Modal -->
    <dialog class="modal" @if ($reservationModal) open @endif>
        <div class="modal-box max-w-2xl">
            <div class="flex items-center justify-between gap-2 border-b border-base-300 pb-3">
                <div>
                    <h3 class="font-bold text-base">{{ __('Approved Reservations Ready for Pickup') }}</h3>
                    <p class="text-xs text-base-content/60">
                        {{ __('Search by booking Ref #, student name, email, equipment, or asset tag.') }}
                    </p>
                </div>
                <span class="text-xs font-semibold text-info bg-info/10 px-2.5 py-1 rounded-md whitespace-nowrap">
                    {{ __(':count total', ['count' => $this->approvedReservationsCount]) }}
                </span>
            </div>

            <div class="mt-3">
                <label class="input input-sm flex items-center gap-2 w-full">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60 text-primary" />
                    <input type="search"
                        wire:model.live.debounce.300ms="reservationSearch"
                        placeholder="{{ __('Search #ref, student name, email, or equipment...') }}"
                        class="grow text-xs" />
                    <span wire:loading wire:target="reservationSearch" class="loading loading-spinner loading-xs text-primary"></span>
                </label>
            </div>

            <div class="mt-3 divide-y divide-base-200 border border-base-300 rounded-box max-h-80 overflow-y-auto">
                @forelse ($this->approvedReservations as $res)
                    <div class="p-3 flex items-center justify-between gap-3 hover:bg-base-200/40 transition text-xs">
                        <div class="min-w-0 space-y-0.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono font-bold text-primary">#{{ $res->id }}</span>
                                <span class="font-bold text-base-content">{{ $res->user->name }}</span>
                                @foreach ($res->user->organizations->take(2) as $org)
                                    <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $org->code }}</span>
                                @endforeach
                            </div>
                            <div class="text-base-content/80 flex items-center gap-2 flex-wrap">
                                <span class="font-medium">{{ $res->item->name }}</span>
                                @if ($res->itemUnit)
                                    <span class="font-mono text-[11px] bg-base-200 px-1.5 py-0.5 rounded">{{ $res->itemUnit->asset_tag }}</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-base-content/60">
                                {{ __('Pickup:') }} {{ $res->start_time->format('M d, Y H:i') }} — {{ $res->end_time->format('M d, Y H:i') }}
                            </div>
                        </div>
                        <button type="button" wire:click="applyReservation({{ $res->id }})" class="btn btn-xs btn-primary shrink-0 font-semibold">
                            {{ __('Load Booking') }}
                        </button>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-base-content/60">
                        {{ __('No approved reservations found matching your search.') }}
                    </div>
                @endforelse
            </div>

            <div class="modal-action justify-between items-center mt-4">
                <span class="text-[11px] text-base-content/60">
                    {{ __('Showing up to 20 earliest pickups. Refine search for more specific results.') }}
                </span>
                <button type="button" wire:click="$set('reservationModal', false)" class="btn btn-sm btn-ghost">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('reservationModal', false)"></div>
    </dialog>
</div>
