<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('My Reservations'), 'icon' => 'heroicon-o-calendar-days'],
    ]">
        <x-slot:actions>
            <a href="{{ route('portal.browse') }}" class="btn btn-primary btn-sm font-semibold gap-1">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span>{{ __('Browse & Reserve') }}</span>
            </a>
        </x-slot:actions>
    </x-breadcrumbs>

    @php $borrower = auth()->user(); @endphp
    @if ($borrower && ! $borrower->canBorrow())
        <div role="alert" class="alert alert-error text-xs py-2.5 px-3 mt-2 shadow-xs">
            <x-heroicon-o-shield-exclamation class="w-5 h-5 shrink-0" />
            <div>
                <span class="font-bold block">{{ __('Borrowing Privileges Suspended') }}</span>
                <span>{{ implode(' ', $borrower->getBorrowingBlockReasons()) }}</span>
            </div>
        </div>
    @endif

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-3 sm:p-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="font-bold text-sm text-base-content">{{ __('Equipment Booking Requests') }}</h2>
                <select wire:model.live="statusFilter" class="select select-sm w-44 text-xs">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="checked_out">{{ __('Checked Out') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm text-xs">
                    <thead>
                        <tr class="text-base-content/70">
                            <th>{{ __('Ref #') }}</th>
                            <th>{{ __('Equipment') }}</th>
                            <th>{{ __('College') }}</th>
                            <th>{{ __('Schedule') }}</th>
                            <th>{{ __('Purpose / Remarks') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->reservations as $res)
                            <tr class="hover:bg-base-200/40">
                                <td class="font-mono font-semibold">#{{ $res->id }}</td>
                                <td>
                                    <div class="font-bold text-base-content">{{ $res->item->name }}</div>
                                    @if ($res->itemUnit)
                                        <div class="text-[11px] font-mono text-primary">{{ $res->itemUnit->asset_tag }}</div>
                                    @endif
                                </td>
                                <td><span class="badge badge-ghost badge-xs font-semibold">{{ $res->organization->code }}</span></td>
                                <td>
                                    <div>{{ $res->start_time->format('M d, Y H:i') }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ __('to :end', ['end' => $res->end_time->format('M d, Y H:i')]) }}</div>
                                </td>
                                <td class="max-w-xs">
                                    <div class="line-clamp-2">{{ $res->purpose ?: '—' }}</div>
                                    @if ($res->rejection_remarks)
                                        <div class="text-[11px] text-error font-semibold mt-0.5">
                                            {{ __('Rejection Note: :note', ['note' => $res->rejection_remarks]) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-xs {{ $res->status->badgeClass() }}">{{ $res->status->label() }}</span>
                                </td>
                                <td class="text-right">
                                    @if ($res->status === \App\Enums\ReservationStatus::Pending)
                                        <button type="button" wire:click="cancelReservation({{ $res->id }})"
                                            class="btn btn-xs btn-ghost text-error font-semibold">
                                            {{ __('Cancel') }}
                                        </button>
                                    @else
                                        <span class="text-[11px] text-base-content/50">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-base-content/60">
                                    {{ __('You have no equipment reservations yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->reservations->links() }}
            </div>
        </div>
    </div>
</div>
