<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Reports & Analytics'), 'icon' => 'heroicon-o-chart-bar-square'],
        ['label' => __('Inventory Reports'), 'icon' => 'heroicon-o-chart-pie'],
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
        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Assets') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-base-200 text-base-content/75 flex items-center justify-center shrink-0">
                            <x-heroicon-o-archive-box class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-base-content tabular-nums">{{ number_format($this->summaryStats['total_units']) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ $this->summaryStats['total_items'] }} {{ __('catalog items') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Available Units') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-success tabular-nums">{{ number_format($this->summaryStats['available_units']) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Ready for checkout') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('In-Maintenance') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-warning/10 text-warning flex items-center justify-center shrink-0">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-warning tabular-nums">{{ number_format($this->summaryStats['maintenance_units']) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Servicing / calibration') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Damaged / Lost') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-error tabular-nums">{{ number_format($this->summaryStats['damaged_lost']) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Requires inspection') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors col-span-2 lg:col-span-1">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Utilization Rate') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <x-heroicon-o-chart-pie class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-primary tabular-nums">{{ $this->summaryStats['utilization_rate'] }}%</span>
                    <span class="text-[11px] text-base-content/55">{{ $this->summaryStats['total_checkouts'] }} {{ __('total checkouts') }}</span>
                </div>
            </div>
        </div>

        <!-- Visual Breakdown Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <!-- Unit Status Distribution -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-chart-pie class="w-4 h-4 text-primary shrink-0" />
                            <span>{{ __('Asset Status Distribution') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">{{ __('By current unit status') }}</span>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($this->statusBreakdown as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-xs {{ $row['badge_class'] }}"></span>
                                        <span class="font-medium text-base-content">{{ $row['label'] }}</span>
                                    </div>
                                    <span class="font-semibold tabular-nums text-base-content/85">{{ $row['count'] }} ({{ $row['percentage'] }}%)</span>
                                </div>
                                <progress class="progress progress-primary w-full h-2" value="{{ $row['percentage'] }}" max="100"></progress>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Condition Distribution -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-secondary shrink-0" />
                            <span>{{ __('Physical Condition Breakdown') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">{{ __('By inspection condition') }}</span>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($this->conditionBreakdown as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-xs {{ $row['badge_class'] }}"></span>
                                        <span class="font-medium text-base-content">{{ $row['label'] }}</span>
                                    </div>
                                    <span class="font-semibold tabular-nums text-base-content/85">{{ $row['count'] }} ({{ $row['percentage'] }}%)</span>
                                </div>
                                <progress class="progress progress-secondary w-full h-2" value="{{ $row['percentage'] }}" max="100"></progress>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Frequently Borrowed Equipment Table -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3.5 sm:p-4">
                <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                    <h2 class="card-title text-sm font-bold flex items-center gap-2">
                        <x-heroicon-o-trophy class="w-4 h-4 text-warning shrink-0" />
                        <span>{{ __('Most Frequently Borrowed Equipment') }}</span>
                    </h2>
                    <span class="text-[11px] font-medium text-base-content/60">{{ __('Top 15 by checkout volume') }}</span>
                </div>

                <div class="overflow-x-auto border border-base-300 rounded-box">
                    <table class="table table-zebra table-sm w-full text-xs">
                        <thead>
                            <tr class="bg-base-200/60 text-base-content/80 font-bold uppercase text-[10px]">
                                <th class="w-10">#</th>
                                <th>{{ __('Item') }}</th>
                                <th>{{ __('College') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th class="text-right">{{ __('Units') }}</th>
                                <th class="text-right">{{ __('Total Checkouts') }}</th>
                                <th class="text-right">{{ __('Active Loans') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->topBorrowedItems as $item)
                                <tr wire:key="top-item-{{ $item->id }}" class="hover:bg-base-200/40">
                                    <td class="font-mono font-semibold text-base-content/60">{{ $loop->iteration }}</td>
                                    <td class="font-semibold text-base-content">{{ $item->name }}</td>
                                    <td>
                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $item->organization?->code ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-base-content/75">{{ $item->category?->name ?? __('Uncategorized') }}</td>
                                    <td class="text-right tabular-nums">{{ $item->units_count }}</td>
                                    <td class="text-right tabular-nums font-bold text-primary">{{ $item->loans_count }}</td>
                                    <td class="text-right tabular-nums">{{ $item->active_loans_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8">
                                        <div class="w-10 h-10 rounded-full bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-2">
                                            <x-heroicon-o-archive-box class="w-5 h-5" />
                                        </div>
                                        <p class="text-xs font-medium text-base-content/60">{{ __('No inventory items found for the selected scope.') }}</p>
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
