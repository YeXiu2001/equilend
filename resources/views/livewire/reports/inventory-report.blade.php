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

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-3">
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Assets') }}</span>
                <span class="text-lg font-bold text-base-content">{{ $this->summaryStats['total_units'] }}</span>
                <span class="text-[11px] text-base-content/50">{{ $this->summaryStats['total_items'] }} {{ __('catalog items') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Available Units') }}</span>
                <span class="text-lg font-bold text-success">{{ $this->summaryStats['available_units'] }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Ready for checkout') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('In-Maintenance') }}</span>
                <span class="text-lg font-bold text-warning">{{ $this->summaryStats['maintenance_units'] }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Servicing / calibration') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Damaged / Lost') }}</span>
                <span class="text-lg font-bold text-error">{{ $this->summaryStats['damaged_lost'] }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Requires inspection') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs col-span-2 lg:col-span-1">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Utilization Rate') }}</span>
                <span class="text-lg font-bold text-primary">{{ $this->summaryStats['utilization_rate'] }}%</span>
                <span class="text-[11px] text-base-content/50">{{ $this->summaryStats['total_checkouts'] }} {{ __('total checkouts') }}</span>
            </div>
        </div>
    </div>

    <!-- Visual Breakdown Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <!-- Unit Status Distribution -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Asset Status Distribution') }}</h2>
                <div class="space-y-2.5">
                    @foreach ($this->statusBreakdown as $row)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-xs {{ $row['badge_class'] }}"></span>
                                    <span class="font-medium">{{ $row['label'] }}</span>
                                </div>
                                <span class="font-semibold tabular-nums">{{ $row['count'] }} ({{ $row['percentage'] }}%)</span>
                            </div>
                            <progress class="progress progress-primary w-full h-2" value="{{ $row['percentage'] }}" max="100"></progress>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Condition Distribution -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Physical Condition Breakdown') }}</h2>
                <div class="space-y-2.5">
                    @foreach ($this->conditionBreakdown as $row)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-xs {{ $row['badge_class'] }}"></span>
                                    <span class="font-medium">{{ $row['label'] }}</span>
                                </div>
                                <span class="font-semibold tabular-nums">{{ $row['count'] }} ({{ $row['percentage'] }}%)</span>
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
        <div class="card-body p-3 sm:p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="card-title text-sm font-bold">{{ __('Most Frequently Borrowed Equipment') }}</h2>
                <span class="text-xs text-base-content/60">{{ __('Top 15 by checkout volume') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                    <thead>
                        <tr>
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
                            <tr wire:key="top-item-{{ $item->id }}">
                                <td class="font-semibold text-xs">{{ $item->name }}</td>
                                <td>
                                    <span class="badge badge-ghost badge-xs font-semibold">{{ $item->organization?->code ?? 'N/A' }}</span>
                                </td>
                                <td class="text-xs text-base-content/75">{{ $item->category?->name ?? __('Uncategorized') }}</td>
                                <td class="text-right tabular-nums text-xs">{{ $item->units_count }}</td>
                                <td class="text-right tabular-nums font-bold text-primary text-xs">{{ $item->loans_count }}</td>
                                <td class="text-right tabular-nums text-xs">{{ $item->active_loans_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-xs text-base-content/60">
                                    {{ __('No inventory items found for the selected scope.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
