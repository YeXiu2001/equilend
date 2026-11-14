<div>
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('My Reservations'), 'icon' => 'heroicon-o-calendar-days'],
    ]">
        <x-slot:actions>
            <a href="{{ route('portal.browse') }}" class="btn btn-primary btn-sm px-4 font-semibold gap-1.5">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span>{{ __('Browse & Reserve') }}</span>
            </a>
        </x-slot:actions>
    </x-breadcrumbs>

    @php $borrower = auth()->user(); @endphp
    @if ($borrower && ! $borrower->canBorrow())
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
                        {{ __('Showing :count of :total reservations', ['count' => $this->reservations->count(), 'total' => $this->totalCount]) }}
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
                        title="{{ __('Filter Reservations') }}">
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
                <div wire:loading.flex wire:target="search,sortBy,applyFilters,resetFilters,statusFilter,cancelReservation" class="absolute inset-0 bg-base-100/60 z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('id')">
                                        <span>{{ __('Ref #') }}</span>
                                        @if ($sort['column'] === 'id')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>{{ __('Equipment') }}</th>
                                <th>{{ __('College') }}</th>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('start_time')">
                                        <span>{{ __('Schedule') }}</span>
                                        @if ($sort['column'] === 'start_time')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>{{ __('Purpose / Remarks') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reservations as $res)
                                <tr wire:key="reservation-row-{{ $res->id }}" class="hover">
                                    <td class="font-mono font-semibold text-xs whitespace-nowrap">#{{ $res->id }}</td>
                                    <td>
                                        <div class="font-medium text-base-content">{{ $res->item->name }}</div>
                                        @if ($res->itemUnit)
                                            <div class="text-xs font-mono text-primary">{{ $res->itemUnit->asset_tag }}</div>
                                        @endif
                                    </td>
                                    <td class="text-sm text-base-content/80 whitespace-nowrap">
                                        {{ $res->organization->code ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap text-xs">
                                        <div class="font-medium text-base-content">{{ $res->start_time->format('M d, Y H:i') }}</div>
                                        <div class="text-base-content/60">{{ __('to :end', ['end' => $res->end_time->format('M d, Y H:i')]) }}</div>
                                    </td>
                                    <td class="max-w-xs text-xs">
                                        <div class="line-clamp-2 text-base-content/80">{{ $res->purpose ?: '—' }}</div>
                                        @if ($res->rejection_remarks)
                                            <div class="text-error font-semibold mt-0.5">
                                                {{ __('Rejection Note: :note', ['note' => $res->rejection_remarks]) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="badge badge-sm whitespace-nowrap {{ $res->status->badgeClass() }}">
                                            {{ $res->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end whitespace-nowrap">
                                        @if (in_array($res->status, [\App\Enums\ReservationStatus::Pending, \App\Enums\ReservationStatus::Approved], true))
                                            <button type="button"
                                                wire:click="openCancelModal({{ $res->id }})"
                                                class="btn btn-ghost btn-xs text-error font-semibold">
                                                {{ __('Cancel') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-base-content/40">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-base-content/60">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-heroicon-o-calendar-days class="w-8 h-8 opacity-40" />
                                            <p class="font-medium">{{ __('No reservations found.') }}</p>
                                            <p class="text-xs">
                                                {{ mb_trim($search) !== '' || $this->activeFilterCount > 0 ? __('Try adjusting your search or filter criteria.') : __('Browse equipment to submit your first booking request.') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scrolling Trigger & Indicator -->
            @if ($this->hasMore)
                <div wire:key="reservations-load-more-{{ $this->limit }}-{{ $this->totalCount }}-{{ md5((string) $this->search . '|' . $this->statusFilter) }}"
                    wire:intersect="loadMore"
                    class="flex flex-col items-center justify-center py-6 gap-2">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-xs text-base-content/50">{{ __('Loading more reservations...') }}</span>
                </div>
            @elseif ($this->reservations->isNotEmpty())
                <div wire:key="reservations-all-loaded" class="text-center py-4 text-xs text-base-content/40">
                    {{ __('All :count reservations loaded.', ['count' => $this->totalCount]) }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- Filter Options Modal                       -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($filterModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ __('Filter Reservations') }}</h3>

            <form id="reservations-filter-form" wire:submit="applyFilters" class="space-y-4">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Reservation Status') }}</legend>
                    <select wire:model="filterStatus" class="select select-sm w-full">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="checked_out">{{ __('Checked Out') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
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
                    <button type="submit" form="reservations-filter-form" class="btn btn-primary btn-sm">
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
    <!-- Cancel Reservation Caution Dialog          -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($cancelModal && $this->cancellingReservation) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->cancellingReservation)
                @php $cr = $this->cancellingReservation; @endphp
                <div class="flex flex-col items-center text-center pt-1">
                    <div class="w-12 h-12 rounded-full bg-warning/15 text-warning flex items-center justify-center mb-3">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-base-content">
                        {{ __('Cancel Reservation #:id?', ['id' => $cr->id]) }}
                    </h3>
                    <p class="text-xs text-base-content/70 mt-1 max-w-sm">
                        @if ($cr->status === \App\Enums\ReservationStatus::Approved)
                            {{ __('This reservation has already been approved. Cancelling it will release your reserved equipment back to the available pool.') }}
                        @else
                            {{ __('Are you sure you want to withdraw this pending reservation request? This action cannot be undone.') }}
                        @endif
                    </p>
                </div>

                <div class="bg-base-200/70 border border-base-300 rounded-box p-3 mt-4 text-xs space-y-1.5">
                    <div class="flex justify-between gap-2">
                        <span class="text-base-content/60">{{ __('Equipment') }}</span>
                        <span class="font-bold text-right">{{ $cr->item?->name ?? '—' }}</span>
                    </div>
                    @if ($cr->itemUnit)
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/60">{{ __('Assigned Unit') }}</span>
                            <span class="font-mono font-semibold text-primary">{{ $cr->itemUnit->asset_tag }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2">
                        <span class="text-base-content/60">{{ __('Schedule') }}</span>
                        <span class="text-right">{{ $cr->start_time->format('M d, Y H:i') }} – {{ $cr->end_time->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-base-content/60">{{ __('Status') }}</span>
                        <span class="badge badge-xs whitespace-nowrap {{ $cr->status->badgeClass() }}">{{ $cr->status->label() }}</span>
                    </div>
                </div>

                <div class="modal-action justify-end gap-2 mt-5">
                    <button type="button" wire:click="$set('cancelModal', false)" class="btn btn-ghost btn-sm">
                        {{ __('Keep Reservation') }}
                    </button>
                    <button type="button" wire:click="confirmCancelReservation" wire:loading.attr="disabled" wire:target="confirmCancelReservation" class="btn btn-error btn-sm gap-1.5">
                        <span wire:loading wire:target="confirmCancelReservation" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Yes, Cancel Reservation') }}</span>
                    </button>
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('cancelModal', false)">close</button>
        </form>
    </dialog>
</div>
