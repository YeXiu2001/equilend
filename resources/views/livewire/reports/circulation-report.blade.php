<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Reports & Analytics'), 'icon' => 'heroicon-o-chart-bar-square'],
        ['label' => __('Circulation Metrics'), 'icon' => 'heroicon-o-arrows-right-left'],
    ]">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="periodDays" class="select select-sm select-bordered w-36 text-xs font-semibold">
                    <option value="30">{{ __('Last 30 Days') }}</option>
                    <option value="90">{{ __('Last 90 Days') }}</option>
                    <option value="180">{{ __('Last 180 Days') }}</option>
                    <option value="all">{{ __('All Time') }}</option>
                </select>

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
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Loan Volume') }}</span>
                <span class="text-lg font-bold text-base-content">{{ $this->summaryStats['total_loans'] }}</span>
                <span class="text-[11px] text-base-content/50">{{ __('Transactions in period') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('On-Time Punctuality') }}</span>
                <span class="text-lg font-bold text-success">{{ $this->summaryStats['punctuality_rate'] }}%</span>
                <span class="text-[11px] text-base-content/50">{{ $this->summaryStats['on_time_count'] }} {{ __('on-time returns') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Late / Overdue Rate') }}</span>
                <span class="text-lg font-bold text-error">{{ $this->summaryStats['overdue_rate'] }}%</span>
                <span class="text-[11px] text-base-content/50">{{ $this->summaryStats['late_count'] }} {{ __('late or overdue') }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Avg. Turnaround') }}</span>
                <span class="text-lg font-bold text-primary">{{ $this->summaryStats['avg_turnaround_hours'] }}h</span>
                <span class="text-[11px] text-base-content/50">{{ __('Checkout to check-in') }}</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
        <!-- Monthly Loan Volume -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Monthly Loan Volume') }}</h2>
                @if (empty($this->monthlyTrends))
                    <p class="text-xs text-base-content/60 py-6 text-center">{{ __('No loan activity in selected period.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($this->monthlyTrends as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold">{{ $row['month'] }}</span>
                                    <span class="tabular-nums text-base-content/80">
                                        <span class="font-bold text-primary">{{ $row['count'] }}</span> {{ __('loans') }}
                                        <span class="text-[11px] text-success">({{ $row['on_time'] }} {{ __('on-time') }})</span>
                                    </span>
                                </div>
                                <progress class="progress progress-primary w-full h-2.5" value="{{ $row['percentage'] }}" max="100"></progress>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Peak Borrowing Days -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3 sm:p-4">
                <h2 class="card-title text-sm font-bold mb-2">{{ __('Peak Borrowing Days') }}</h2>
                <div class="space-y-2">
                    @foreach ($this->peakDays as $row)
                        <div class="flex items-center gap-3 text-xs">
                            <span class="w-9 font-semibold text-base-content/80">{{ $row['day'] }}</span>
                            <progress class="progress progress-secondary flex-1 h-2" value="{{ $row['percentage'] }}" max="100"></progress>
                            <span class="w-10 text-right font-bold tabular-nums">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Peak Borrowing Hours -->
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-3 sm:p-4">
            <h2 class="card-title text-sm font-bold mb-2">{{ __('Peak Borrowing Hours') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ($this->peakHours as $bucket)
                    <div class="p-3 rounded-lg bg-base-200/60 border border-base-300">
                        <p class="text-xs font-semibold text-base-content/80">{{ $bucket['label'] }}</p>
                        <p class="text-lg font-bold text-primary mt-1 tabular-nums">{{ $bucket['count'] }} <span class="text-xs font-normal text-base-content/60">{{ __('loans') }}</span></p>
                        <progress class="progress progress-primary w-full h-1.5 mt-2" value="{{ $bucket['percentage'] }}" max="100"></progress>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
