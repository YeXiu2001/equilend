<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('Borrowing History'), 'icon' => 'heroicon-o-clock'],
    ]" />

    <!-- On-Time Performance Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-2 mb-3">
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Returned') }}</span>
                <span class="text-lg font-bold text-base-content">{{ $this->metrics['total'] }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('On-Time Returns') }}</span>
                <span class="text-lg font-bold text-success">{{ $this->metrics['on_time'] }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Late Returns') }}</span>
                <span class="text-lg font-bold text-warning">{{ $this->metrics['late'] }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Punctuality Rate') }}</span>
                <span class="text-lg font-bold text-primary">{{ $this->metrics['rate'] }}%</span>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-3 sm:p-4">
            <div class="overflow-x-auto">
                <table class="table table-sm text-xs">
                    <thead>
                        <tr class="text-base-content/70">
                            <th>{{ __('Loan #') }}</th>
                            <th>{{ __('Equipment') }}</th>
                            <th>{{ __('College') }}</th>
                            <th>{{ __('Borrowed') }}</th>
                            <th>{{ __('Returned') }}</th>
                            <th>{{ __('Punctuality') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->pastLoans as $loan)
                            @php
                                $wasOnTime = $loan->returned_at && $loan->due_at && $loan->returned_at <= $loan->due_at;
                            @endphp
                            <tr class="hover:bg-base-200/40">
                                <td class="font-mono font-semibold">#{{ $loan->id }}</td>
                                <td>
                                    <div class="font-bold text-base-content">{{ $loan->item->name }}</div>
                                    <div class="text-[11px] font-mono text-primary">{{ $loan->itemUnit->asset_tag }}</div>
                                </td>
                                <td><span class="badge badge-ghost badge-xs font-semibold">{{ $loan->organization->code }}</span></td>
                                <td>{{ $loan->borrowed_at->format('M d, Y H:i') }}</td>
                                <td>{{ $loan->returned_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td>
                                    @if ($loan->returned_at)
                                        <span class="badge badge-xs {{ $wasOnTime ? 'badge-success' : 'badge-warning' }}">
                                            {{ $wasOnTime ? __('On Time') : __('Late') }}
                                        </span>
                                    @else
                                        <span>—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-xs {{ $loan->status->badgeClass() }}">{{ $loan->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-base-content/60">
                                    {{ __('No past borrowing history found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->pastLoans->links() }}
            </div>
        </div>
    </div>
</div>
