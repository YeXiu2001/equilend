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

    <div class="space-y-3 mt-1" wire:loading.class="opacity-60 transition-opacity" wire:target="periodDays,selectedOrganizationId">
        <!-- KPI Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Loan Volume') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-base-200 text-base-content/75 flex items-center justify-center shrink-0">
                            <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-base-content tabular-nums">{{ number_format($this->summaryStats['total_loans']) }}</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Transactions in period') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('On-Time Punctuality') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-success tabular-nums">{{ $this->summaryStats['punctuality_rate'] }}%</span>
                    <span class="text-[11px] text-base-content/55">{{ $this->summaryStats['on_time_count'] }} {{ __('on-time returns') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Late / Overdue Rate') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-error tabular-nums">{{ $this->summaryStats['overdue_rate'] }}%</span>
                    <span class="text-[11px] text-base-content/55">{{ $this->summaryStats['late_count'] }} {{ __('late or overdue') }}</span>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-2xs hover:border-base-content/20 transition-colors">
                <div class="card-body p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Avg. Turnaround') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <x-heroicon-o-clock class="w-4 h-4" />
                        </div>
                    </div>
                    <span class="text-xl font-bold text-primary tabular-nums">{{ $this->summaryStats['avg_turnaround_hours'] }}h</span>
                    <span class="text-[11px] text-base-content/55">{{ __('Checkout to check-in') }}</span>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <!-- Monthly Loan Volume -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-chart-bar-square class="w-4 h-4 text-primary shrink-0" />
                            <span>{{ __('Monthly Loan Volume') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">
                            {{ $periodDays === 'all' ? __('All Time') : __('Last :days Days', ['days' => $periodDays]) }}
                        </span>
                    </div>

                    @if (empty($this->monthlyTrends))
                        <div class="py-8 text-center">
                            <div class="w-10 h-10 rounded-full bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-2">
                                <x-heroicon-o-chart-bar-square class="w-5 h-5" />
                            </div>
                            <p class="text-xs font-medium text-base-content/60">{{ __('No loan activity in selected period.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->monthlyTrends as $row)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-semibold text-base-content">{{ $row['month'] }}</span>
                                        <span class="tabular-nums text-base-content/80">
                                            <span class="font-bold text-primary">{{ $row['count'] }}</span> {{ __('loans') }}
                                            <span class="text-[11px] text-success font-medium">({{ $row['on_time'] }} {{ __('on-time') }})</span>
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
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                        <h2 class="card-title text-sm font-bold flex items-center gap-2">
                            <x-heroicon-o-calendar-days class="w-4 h-4 text-secondary shrink-0" />
                            <span>{{ __('Peak Borrowing Days') }}</span>
                        </h2>
                        <span class="text-[11px] font-medium text-base-content/60">{{ __('By day of week') }}</span>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($this->peakDays as $row)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-9 font-semibold text-base-content/80">{{ $row['day'] }}</span>
                                <progress class="progress progress-secondary flex-1 h-2" value="{{ $row['percentage'] }}" max="100"></progress>
                                <span class="w-10 text-right font-bold tabular-nums text-base-content">{{ $row['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Peak Borrowing Hours -->
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body p-3.5 sm:p-4">
                <div class="flex items-center justify-between gap-2 border-b border-base-200 pb-2.5 mb-3">
                    <h2 class="card-title text-sm font-bold flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-primary shrink-0" />
                        <span>{{ __('Peak Borrowing Hours') }}</span>
                    </h2>
                    <span class="text-[11px] font-medium text-base-content/60">{{ __('Checkout time windows') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($this->peakHours as $bucket)
                        <div class="p-3 rounded-xl bg-base-200/50 border border-base-300/80">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-base-content/80">{{ $bucket['label'] }}</p>
                                <span class="text-[11px] font-semibold text-base-content/50 tabular-nums">{{ $bucket['percentage'] }}%</span>
                            </div>
                            <p class="text-lg font-bold text-primary mt-1.5 tabular-nums">
                                {{ $bucket['count'] }}
                                <span class="text-xs font-normal text-base-content/60">{{ __('loans') }}</span>
                            </p>
                            <progress class="progress progress-primary w-full h-1.5 mt-2" value="{{ $bucket['percentage'] }}" max="100"></progress>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
