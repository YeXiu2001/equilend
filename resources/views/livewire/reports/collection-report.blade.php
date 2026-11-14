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
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 bg-base-200 border border-base-300 px-2.5 py-1 rounded-md whitespace-nowrap">
                        <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                        {{ auth()->user()?->organization?->code ?? 'Hub' }}
                    </span>
                @endif

                {{-- <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv" class="btn btn-primary btn-sm font-semibold gap-1.5">
                    <span wire:loading wire:target="exportCsv" class="loading loading-spinner loading-xs"></span>
                    <x-heroicon-o-arrow-down-tray wire:loading.remove wire:target="exportCsv" class="w-4 h-4" />
                    <span>{{ __('Download CSV Report') }}</span>
                </button> --}}
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="space-y-3 mt-1" wire:loading.class="opacity-60 transition-opacity" wire:target="selectedOrganizationId">
        <!-- KPI Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Fines Levied') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-base-200 text-base-content/75 flex items-center justify-center shrink-0">
                            <x-heroicon-o-document-text class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-base-content tabular-nums">₱{{ number_format($this->summaryStats['total_levied'], 2) }}</span>
                    <span class="text-[11px] text-base-content/55">₱{{ number_format($this->summaryStats['total_waived'], 2) }} {{ __('waived') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Collected') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0">
                            <x-heroicon-o-banknotes class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-success tabular-nums">₱{{ number_format($this->summaryStats['total_collected'], 2) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Verified receipts') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Outstanding Debt') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-error tabular-nums">₱{{ number_format($this->summaryStats['outstanding_debt'], 2) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Unsettled balances') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Collection Rate') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <x-heroicon-o-receipt-percent class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-primary tabular-nums">{{ $this->summaryStats['collection_rate'] }}%</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Of collectible fines') }}</span>
                </div>
            </div>
        </div>

        <!-- Breakdown Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <!-- Revenue by Payment Channel -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-credit-card class="w-4 h-4 text-success shrink-0" />
                            <span>{{ __('Revenue Breakdown by Channel') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">{{ __('Verified payments') }}</span>
                    </div>

                    @if (empty($this->channelBreakdown))
                        <div class="py-8 text-center">
                            <div class="w-10 h-10 rounded-full bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-2">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                            </div>
                            <p class="text-xs font-medium text-base-content/60">{{ __('No verified payments recorded yet.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->channelBreakdown as $row)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-semibold text-base-content">{{ $row['channel'] }} <span class="text-base-content/55 font-normal">({{ $row['count'] }} {{ __('txns') }})</span></span>
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
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-presentation-chart-line class="w-4 h-4 text-primary shrink-0" />
                            <span>{{ __('Monthly Collection Trend') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">{{ __('Payment volume over time') }}</span>
                    </div>

                    @if (empty($this->monthlyCollections))
                        <div class="py-8 text-center">
                            <div class="w-10 h-10 rounded-full bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-2">
                                <x-heroicon-o-presentation-chart-line class="w-5 h-5" />
                            </div>
                            <p class="text-xs font-medium text-base-content/60">{{ __('No monthly collection data available.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->monthlyCollections as $row)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-semibold text-base-content">{{ $row['month'] }} <span class="text-base-content/55 font-normal">({{ $row['count'] }} {{ __('payments') }})</span></span>
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
            <div class="card-body p-3.5 sm:p-4">
                <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                    <h2 class="card-title text-sm font-bold flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-error shrink-0" />
                        <span>{{ __('Outstanding Student Debt Ledger') }}</span>
                    </h2>
                    <span class="text-[11px] font-medium text-base-content/60">{{ __('Unsettled penalties ordered by balance') }}</span>
                </div>

                <div class="overflow-x-auto border border-base-300 rounded-box">
                    <table class="table table-zebra table-sm w-full text-xs">
                        <thead>
                            <tr class="bg-base-200/60 text-base-content/80 font-bold uppercase text-[10px]">
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
                                <tr wire:key="debt-{{ $penalty->id }}" class="hover:bg-base-200/40">
                                    <td>
                                        <div class="font-semibold text-base-content">{{ $penalty->user?->name ?? __('Unknown') }}</div>
                                        <div class="text-[11px] text-base-content/60">{{ $penalty->user?->email }}</div>
                                    </td>
                                    <td>
                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $penalty->organization?->code ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold whitespace-nowrap {{ $penalty->type->textClass() }}">{{ $penalty->type->label() }}</span>
                                    </td>
                                    <td class="text-right tabular-nums">₱{{ number_format((float) $penalty->amount, 2) }}</td>
                                    <td class="text-right tabular-nums text-success">₱{{ number_format((float) $penalty->paid_amount, 2) }}</td>
                                    <td class="text-right tabular-nums font-bold text-error">₱{{ number_format((float) $penalty->balance_remaining, 2) }}</td>
                                    <td>
                                        <span class="badge badge-xs whitespace-nowrap {{ $penalty->status->badgeClass() }}">{{ $penalty->status->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8">
                                        <div class="w-10 h-10 rounded-full bg-success/10 text-success flex items-center justify-center mx-auto mb-2">
                                            <x-heroicon-o-check-circle class="w-5 h-5" />
                                        </div>
                                        <p class="text-xs font-medium text-base-content/60">{{ __('No outstanding student debt for the selected scope.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
