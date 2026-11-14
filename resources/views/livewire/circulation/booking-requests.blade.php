<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Circulation & Desk'), 'icon' => 'heroicon-o-arrows-right-left'],
        ['label' => __('Booking & Reservation Requests'), 'icon' => 'heroicon-o-inbox-stack'],
    ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasRole('superadmin'))
                    <select wire:model.live="selectedOrganizationId" class="select select-sm select-bordered w-48 text-xs font-semibold">
                        <option value="all">{{ __('All Colleges') }}</option>
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
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-4 sm:p-5">
            <!-- Search & Filters Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button"
                        wire:click="$set('statusFilter', 'all')"
                        class="btn btn-xs {{ $statusFilter === 'all' ? 'btn-neutral' : 'btn-ghost' }} font-semibold">
                        {{ __('All') }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'pending')"
                        class="btn btn-xs {{ $statusFilter === 'pending' ? 'btn-warning text-warning-content' : 'btn-ghost' }} font-semibold">
                        {{ __('Pending Review') }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'approved')"
                        class="btn btn-xs {{ $statusFilter === 'approved' ? 'btn-info text-info-content' : 'btn-ghost' }} font-semibold">
                        {{ __('Approved') }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'rejected')"
                        class="btn btn-xs {{ $statusFilter === 'rejected' ? 'btn-error text-error-content' : 'btn-ghost' }} font-semibold">
                        {{ __('Rejected') }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'cancelled')"
                        class="btn btn-xs {{ $statusFilter === 'cancelled' ? 'btn-neutral' : 'btn-ghost' }} font-semibold">
                        {{ __('Cancelled') }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60 text-primary" />
                        <input type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search student, item, or purpose...') }}"
                            class="grow text-xs" />
                    </label>
                </div>
            </div>

            <!-- Table of Reservation Requests -->
            <div class="overflow-x-auto border border-base-300 rounded-box">
                <table class="table table-sm table-zebra w-full text-xs">
                    <thead>
                        <tr class="bg-base-200/60 text-base-content/80 font-bold uppercase text-[10px]">
                            <th>{{ __('Ref / Borrower') }}</th>
                            <th>{{ __('Equipment Item') }}</th>
                            <th>{{ __('Schedule Window') }}</th>
                            <th>{{ __('Purpose & Remarks') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reservations as $reservation)
                            <tr class="hover">
                                <td>
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5 font-bold text-base-content">
                                            <span>#{{ $reservation->id }}</span>
                                            <span>•</span>
                                            <span>{{ $reservation->user->name }}</span>
                                        </div>
                                        <div class="text-base-content/60 text-[11px]">{{ $reservation->user->email }}</div>
                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $reservation->organization->code }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-base-content block">{{ $reservation->item->name }}</span>
                                        @if ($reservation->itemUnit)
                                            <span class="font-mono text-primary text-[11px] bg-primary/10 px-1.5 py-0.5 rounded">
                                                {{ $reservation->itemUnit->asset_tag }}
                                            </span>
                                        @else
                                            <span class="text-base-content/50 text-[11px] italic">{{ __('Unassigned Unit') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-0.5">
                                        <div class="font-semibold text-base-content flex items-center gap-1">
                                            <x-heroicon-o-calendar class="w-3.5 h-3.5 text-primary" />
                                            <span>{{ $reservation->start_time->format('M d, Y') }}</span>
                                        </div>
                                        <div class="text-[11px] text-base-content/60">
                                            {{ $reservation->start_time->format('H:i') }} – {{ $reservation->end_time->format('M d, H:i') }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="max-w-xs space-y-1">
                                        <span class="block truncate" title="{{ $reservation->purpose }}">
                                            {{ $reservation->purpose }}
                                        </span>
                                        @if ($reservation->isRejected() && $reservation->rejection_remarks)
                                            <div class="text-error bg-error/10 p-1.5 rounded text-[11px]">
                                                <span class="font-bold">{{ __('Reason') }}:</span> {{ $reservation->rejection_remarks }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="badge badge-xs {{ $reservation->status->badgeClass() }} font-semibold whitespace-nowrap">
                                            {{ $reservation->status->label() }}
                                        </span>
                                        @if ($reservation->isMissedPickup())
                                            <span class="badge badge-xs badge-warning font-semibold whitespace-nowrap" title="{{ __('Pickup date has passed. Cancel this booking to make the unit available again.') }}">
                                                {{ __('Not Picked Up') }}
                                            </span>
                                            <span class="text-[11px] text-warning font-medium">
                                                {{ __('Pickup missed — cancel to release unit') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">
                                    @if ($reservation->isPending())
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                wire:click="openApproveModal({{ $reservation->id }})"
                                                class="btn btn-xs btn-primary font-semibold gap-1">
                                                <x-heroicon-o-check class="w-3.5 h-3.5" />
                                                <span>{{ __('Approve') }}</span>
                                            </button>
                                            <button type="button"
                                                wire:click="openRejectModal({{ $reservation->id }})"
                                                class="btn btn-xs btn-error btn-outline font-semibold gap-1">
                                                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                                <span>{{ __('Reject') }}</span>
                                            </button>
                                        </div>
                                    @elseif ($reservation->isApproved())
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="text-[11px] text-base-content/60 text-right">
                                                <span class="block text-success font-semibold">✓ {{ __('Approved') }}</span>
                                                <span>{{ $reservation->approved_at?->diffForHumans() }}</span>
                                            </div>
                                            <button type="button"
                                                wire:click="openCancelModal({{ $reservation->id }})"
                                                class="btn btn-xs {{ $reservation->isMissedPickup() ? 'btn-warning' : 'btn-ghost text-error' }} font-semibold gap-1">
                                                <x-heroicon-o-no-symbol class="w-3.5 h-3.5" />
                                                <span>{{ __('Cancel Booking') }}</span>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-base-content/40 text-[11px]">{{ __('Processed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-base-content/60">
                                    <x-heroicon-o-inbox class="w-8 h-8 mx-auto mb-2 opacity-60" />
                                    <span>{{ __('No booking requests found matching your filter.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->reservations->count() < $this->totalCount)
                <div class="mt-4 text-center">
                    <button type="button" wire:click="loadMore" class="btn btn-ghost btn-xs font-semibold">
                        {{ __('Load More Requests (:remaining remaining)', ['remaining' => $this->totalCount - $this->reservations->count()]) }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- APPROVAL MODAL -->
    <dialog class="modal {{ $approveModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-md bg-base-100 p-5">
            <h3 class="font-bold text-base flex items-center gap-2 text-base-content">
                <x-heroicon-o-check-circle class="w-5 h-5 text-success" />
                <span>{{ __('Approve Equipment Reservation') }}</span>
            </h3>

            @if ($approvingReservationId)
                @php $appRes = \App\Models\Reservation::with(['user', 'item'])->find($approvingReservationId); @endphp
                @if ($appRes)
                    <div class="bg-base-200/50 p-3 rounded-box text-xs space-y-1 my-3 border border-base-300">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Student') }}:</span>
                            <span class="font-bold text-base-content">{{ $appRes->user->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Equipment') }}:</span>
                            <span class="font-semibold text-base-content">{{ $appRes->item->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Dates') }}:</span>
                            <span>{{ $appRes->start_time->format('M d, H:i') }} – {{ $appRes->end_time->format('M d, H:i') }}</span>
                        </div>
                    </div>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">
                            {{ __('Assign Physical Unit') }} <span class="text-error">*</span>
                        </legend>
                        <select wire:model="assignedUnitId" class="select select-sm w-full text-xs @error('assignedUnitId') select-error @enderror" required>
                            <option value="">{{ __('Select a physical unit...') }}</option>
                            @foreach ($this->availableUnitsForApproving as $unit)
                                <option value="{{ $unit->id }}">
                                    {{ $unit->asset_tag }} ({{ $unit->serial_number ?? 'No SN' }}) - {{ $unit->condition->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('assignedUnitId')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                        @if ($this->availableUnitsForApproving->isEmpty())
                            <p class="label text-warning text-xs">
                                {{ __('No available units found for this equipment.') }}
                            </p>
                        @endif
                        <span class="label text-[11px] text-base-content/60">
                            {{ __('Approving locks the selected unit as Reserved until checkout.') }}
                        </span>
                    </fieldset>
                @endif
            @endif

            <div class="modal-action mt-4">
                <button type="button" wire:click="$set('approveModal', false)" class="btn btn-ghost btn-sm">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmApproval" class="btn btn-primary btn-sm font-semibold gap-1">
                    <x-heroicon-o-check class="w-4 h-4" />
                    <span>{{ __('Confirm Approval') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('approveModal', false)">close</button>
        </form>
    </dialog>

    <!-- REJECT MODAL -->
    <dialog class="modal {{ $rejectModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-md bg-base-100 p-5">
            <h3 class="font-bold text-base flex items-center gap-2 text-error">
                <x-heroicon-o-x-circle class="w-5 h-5 text-error" />
                <span>{{ __('Reject Equipment Booking') }}</span>
            </h3>

            @if ($rejectingReservationId)
                @php $rejRes = \App\Models\Reservation::with(['user', 'item'])->find($rejectingReservationId); @endphp
                @if ($rejRes)
                    <p class="text-xs text-base-content/70 mt-2">
                        {{ __('You are rejecting :item booking for :student.', ['item' => $rejRes->item->name, 'student' => $rejRes->user->name]) }}
                    </p>
                @endif
            @endif

            <div class="space-y-3 mt-3 text-xs">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold text-error">{{ __('Rejection Remarks / Reason') }} *</legend>
                    <textarea wire:model="rejectionRemarks"
                        rows="3"
                        placeholder="{{ __('e.g. Schedule conflict with university examination period, or equipment undergoing repair.') }}"
                        class="textarea textarea-sm w-full text-xs @error('rejectionRemarks') textarea-error @enderror"
                        required></textarea>
                    @error('rejectionRemarks')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>
            </div>

            <div class="modal-action mt-4">
                <button type="button" wire:click="$set('rejectModal', false)" class="btn btn-ghost btn-sm">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="confirmRejection" class="btn btn-error btn-sm font-semibold gap-1 text-white">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                    <span>{{ __('Decline & Send Reason') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('rejectModal', false)">close</button>
        </form>
    </dialog>

    <!-- CANCEL APPROVED BOOKING MODAL -->
    <dialog class="modal {{ $cancelModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-md bg-base-100 p-5">
            <h3 class="font-bold text-base flex items-center gap-2 text-warning">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning" />
                <span>{{ __('Cancel Approved Booking') }}</span>
            </h3>

            @if ($cancellingReservationId)
                @php $cancelRes = \App\Models\Reservation::with(['user', 'item', 'itemUnit'])->find($cancellingReservationId); @endphp
                @if ($cancelRes)
                    <div class="bg-base-200/50 p-3 rounded-box text-xs space-y-1 my-3 border border-base-300">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Student') }}:</span>
                            <span class="font-bold text-base-content">{{ $cancelRes->user->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Equipment') }}:</span>
                            <span class="font-semibold text-base-content">{{ $cancelRes->item->name }}</span>
                        </div>
                        @if ($cancelRes->itemUnit)
                            <div class="flex justify-between">
                                <span class="text-base-content/60">{{ __('Assigned Unit') }}:</span>
                                <span class="font-mono font-semibold text-primary">{{ $cancelRes->itemUnit->asset_tag }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Pickup Date') }}:</span>
                            <span>{{ $cancelRes->start_time->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-base-content/80">
                        {{ __('Cancelling this booking will release the reserved unit back to Available so other borrowers can use it.') }}
                    </p>
                @endif
            @endif

            <div class="modal-action mt-4">
                <button type="button" wire:click="$set('cancelModal', false)" class="btn btn-ghost btn-sm">
                    {{ __('Keep Booking') }}
                </button>
                <button type="button" wire:click="confirmCancellation" class="btn btn-warning btn-sm font-semibold gap-1">
                    <x-heroicon-o-no-symbol class="w-4 h-4" />
                    <span>{{ __('Cancel Booking & Release Unit') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('cancelModal', false)">close</button>
        </form>
    </dialog>
</div>
