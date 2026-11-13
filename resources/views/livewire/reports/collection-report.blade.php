<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Reports & Analytics'), 'icon' => 'heroicon-o-chart-bar-square'],
        ['label' => __('Penalty Collections'), 'icon' => 'heroicon-o-presentation-chart-line'],
    ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasRole('superadmin'))
                    <select wire:model.live="selectedOrganizationId" class="select select-sm select-bordered w-44 text-xs font-semibold">
                        <option value="all">{{ __('All Colleges') }}</option>
                        @foreach ($this->organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="badge badge-neutral badge-sm font-semibold gap-1">
                        <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                        {{ auth()->user()?->organization?->code ?? 'Hub' }}
                    </span>
                @endif

                <button type="button" wire:click="exportCsv" class="btn btn-primary btn-sm font-semibold gap-1">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    <span>{{ __('Download CSV Report') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <!-- KPI Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Fines Levied') }}</span>
                <span class="text-lg font-bold text-base-content">₱{{ number_format($this->summaryStats['total_levied'], 2) }}</span>
                <span class="text-[11px] text-base-content/50">₱{{ number_format($this->summaryStats['total_waived'], 2) }} {{ __('waived') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Collected') }}</span>
                <span class="text-lg font-bold text-success">₱{{ number_format($this->summaryStats['total_collected'], 2) }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Verified receipts') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Outstanding Debt') }}</span>
                <span class="text-lg font-bold text-error">₱{{ number_format($this->summaryStats['outstanding_debt'], 2) }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Unsettled balances') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Collection Rate') }}</span>
                <span class="text-lg font-bold text-primary">{{ $this->summaryStats['collection_rate'] }}%</span>
                <span class="text-[11px] text-base-content/50">{{ __('Of collectible fines') }}</span>
            </div>
        </div>
    </div>

    <!-- Breakdown Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <!-- Revenue by Payment Channel -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Revenue Breakdown by Channel') }}</h2>
                @if (empty($this->channelBreakdown))
                    <p class="text-xs text-base-content/60 py-6 text-center">{{ __('No verified payments recorded yet.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($this->channelBreakdown as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold">{{ $row['channel'] }} <span class="text-base-content/50 font-normal">({{ $row['count'] }} {{ __('txns') }})</span></span>
                                    <span class="font-bold tabular-nums text-success">₱{{ number_format($row['amount'], 2) }} ({{ $row['percentage'] }}%)</span>
                                </div>
                                <progress class="progress progress-success w-full h-2.5" value="{{ $row['percentage'] }}" max="100"></progress>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Monthly Collection Trend -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Monthly Collection Trend') }}</h2>
                @if (empty($this->monthlyCollections))
                    <p class="text-xs text-base-content/60 py-6 text-center">{{ __('No monthly collection data available.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($this->monthlyCollections as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold">{{ $row['month'] }} <span class="text-base-content/50 font-normal">({{ $row['count'] }} {{ __('payments') }})</span></span>
                                    <span class="font-bold tabular-nums text-primary">₱{{ number_format($row['amount'], 2) }}</span>
                                </div>
                                <progress class="progress progress-primary w-full h-2.5" value="{{ $row['percentage'] }}" max="100"></progress>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Outstanding Student Debt Ledger -->
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="card-title text-sm font-bold">{{ __('Outstanding Student Debt Ledger') }}</h2>
                <span class="text-xs text-base-content/60">{{ __('Unsettled penalties ordered by balance') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('College') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-right">{{ __('Assessed') }}</th>
                            <th class="text-right">{{ __('Paid') }}</th>
                            <th class="text-right">{{ __('Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->outstandingLedger as $penalty)
                            <tr wire:key="debt-{{ $penalty->id }}">
                                <td>
                                    <div class="font-semibold text-xs">{{ $penalty->user?->name ?? __('Unknown') }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ $penalty->user?->email }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-ghost badge-xs font-semibold">{{ $penalty->organization?->code ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-xs {{ $penalty->type->badgeClass() }}">{{ $penalty->type->label() }}</span>
                                </td>
                                <td class="text-right tabular-nums text-xs">₱{{ number_format((float) $penalty->amount, 2) }}</td>
                                <td class="text-right tabular-nums text-xs text-success">₱{{ number_format((float) $penalty->paid_amount, 2) }}</td>
                                <td class="text-right tabular-nums font-bold text-error text-xs">₱{{ number_format((float) $penalty->balance_remaining, 2) }}</td>
                                <td>
                                    <span class="badge badge-xs {{ $penalty->status->badgeClass() }}">{{ $penalty->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-xs text-base-content/60">
                                    {{ __('No outstanding student debt for the selected scope.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
