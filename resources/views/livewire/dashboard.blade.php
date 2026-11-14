<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Equilend'), 'icon' => 'heroicon-o-home'],
        ['label' => __('Dashboard'), 'icon' => 'heroicon-o-squares-2x2'],
    ]">
        <x-slot:actions>
            @if ($this->isStaff)
                <div class="flex items-center gap-2">
                    @if ($this->isSuperAdmin)
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
                </div>
            @endif
        </x-slot:actions>
    </x-breadcrumbs>

    @if (! $this->isStaff)
        {{-- ================================================================= --}}
        {{-- STUDENT (BORROWER) DASHBOARD                                      --}}
        {{-- ================================================================= --}}
        <div class="space-y-3">
            <!-- Borrowing Eligibility Banner -->
            @if ($this->studentEligibility['can_borrow'])
                <div role="alert" class="alert alert-success alert-soft border border-success/30 shadow-2xs py-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-bold text-sm">{{ __('Welcome back, :name — Cleared to Borrow', ['name' => auth()->user()->name]) }}</h3>
                        <p class="text-xs opacity-80">{{ __('Your borrowing privileges are active. You can reserve and check out available college equipment.') }}</p>
                    </div>
                    <a href="{{ route('portal.browse') }}" class="btn btn-success btn-sm font-semibold">{{ __('Browse Equipment') }}</a>
                </div>
            @else
                <div role="alert" class="alert alert-error alert-soft border border-error/30 shadow-2xs py-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-bold text-sm">{{ __('Borrowing Privileges Temporarily Suspended') }}</h3>
                        <ul class="text-xs list-disc list-inside mt-1 space-y-0.5">
                            @foreach ($this->studentEligibility['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('portal.penalties') }}" class="btn btn-error btn-sm font-semibold">{{ __('View Penalties') }}</a>
                    </div>
                </div>
            @endif

            <!-- 4 Student KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Active Loans') }}</span>
                        <span class="text-lg font-bold text-primary">{{ $this->studentStats['active_loans'] }}</span>
                        <span class="text-[11px] text-base-content/50">{{ __('Currently checked out') }}</span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Due Soon / Overdue') }}</span>
                        <span class="text-lg font-bold {{ $this->studentStats['urgent_loans'] > 0 ? 'text-error' : 'text-success' }}">
                            {{ $this->studentStats['urgent_loans'] }}
                        </span>
                        <span class="text-[11px] text-base-content/50">{{ __('Within 24h or past due') }}</span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Open Reservations') }}</span>
                        <span class="text-lg font-bold text-info">{{ $this->studentStats['open_reservations'] }}</span>
                        <span class="text-[11px] text-base-content/50">{{ __('Pending & approved') }}</span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Unsettled Fines') }}</span>
                        <span class="text-lg font-bold {{ $this->studentStats['outstanding_fines'] > 0 ? 'text-error' : 'text-success' }}">
                            ₱{{ number_format($this->studentStats['outstanding_fines'], 2) }}
                        </span>
                        <span class="text-[11px] text-base-content/50">{{ __('Outstanding balance') }}</span>
                    </div>
                </div>
            </div>

            <!-- Student Portal Quick Shortcuts -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
                <a href="{{ route('portal.browse') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Browse Equipment') }}</span>
                </a>
                <a href="{{ route('portal.reservations') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-calendar-days class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('My Reservations') }}</span>
                </a>
                <a href="{{ route('portal.loans') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('My Active Loans') }}</span>
                </a>
                <a href="{{ route('portal.history') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-clock class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Borrowing History') }}</span>
                </a>
                <a href="{{ route('portal.penalties') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-receipt-percent class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('My Penalties') }}</span>
                </a>
                <a href="{{ route('user.profile') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-user class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Profile') }}</span>
                </a>
            </div>

            <!-- Student Operational Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- My Active Loans -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-2">
                            <h2 class="card-title text-sm font-bold">{{ __('My Active Loans & Due Dates') }}</h2>
                            <a href="{{ route('portal.loans') }}" class="link link-primary text-xs font-semibold">{{ __('View all') }}</a>
                        </div>

                        @if ($this->studentActiveLoans->isEmpty())
                            <div class="py-6 text-center text-xs text-base-content/60">
                                {{ __('You have no active equipment loans.') }}
                            </div>
                        @else
                            <div class="divide-y divide-base-200">
                                @foreach ($this->studentActiveLoans as $loan)
                                    <div wire:key="student-loan-{{ $loan->id }}" class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-semibold truncate">{{ $loan->item?->name }}</p>
                                            <p class="text-[11px] text-base-content/60 font-mono">
                                                {{ $loan->itemUnit?->asset_tag }} • {{ $loan->organization?->code }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="badge badge-xs whitespace-nowrap {{ $loan->isOverdue() ? 'badge-error' : 'badge-info' }}">
                                                {{ $loan->isOverdue() ? __('Overdue') : __('Active') }}
                                            </span>
                                            <p class="text-[11px] text-base-content/70 mt-0.5">
                                                {{ __('Due') }} {{ $loan->due_at?->format('M d, g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- My Upcoming Reservations -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-2">
                            <h2 class="card-title text-sm font-bold">{{ __('Upcoming Reservations') }}</h2>
                            <a href="{{ route('portal.reservations') }}" class="link link-primary text-xs font-semibold">{{ __('View all') }}</a>
                        </div>

                        @if ($this->studentReservations->isEmpty())
                            <div class="py-6 text-center text-xs text-base-content/60">
                                {{ __('No pending or approved reservations.') }}
                            </div>
                        @else
                            <div class="divide-y divide-base-200">
                                @foreach ($this->studentReservations as $reservation)
                                    <div wire:key="student-res-{{ $reservation->id }}" class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-semibold truncate">{{ $reservation->item?->name }}</p>
                                            <p class="text-[11px] text-base-content/60">
                                                {{ $reservation->organization?->code }} • {{ $reservation->start_time?->format('M d, g:i A') }}
                                            </p>
                                        </div>
                                        <span class="badge badge-xs whitespace-nowrap {{ $reservation->status->badgeClass() }} shrink-0">
                                            {{ $reservation->status->label() }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ================================================================= --}}
        {{-- STAFF / ADMIN OPERATIONAL COMMAND CENTER                          --}}
        {{-- ================================================================= --}}
        <div class="space-y-3">
            <!-- 6 Operational KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Available Units') }}</span>
                        <span class="text-lg font-bold text-success">
                            {{ $this->staffStats['available_units'] }}
                            <span class="text-xs font-normal text-base-content/50">/ {{ $this->staffStats['total_units'] }}</span>
                        </span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Active Loans') }}</span>
                        <span class="text-lg font-bold text-primary">{{ $this->staffStats['active_loans'] }}</span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Overdue Loans') }}</span>
                        <span class="text-lg font-bold {{ $this->staffStats['overdue_loans'] > 0 ? 'text-error' : 'text-base-content' }}">
                            {{ $this->staffStats['overdue_loans'] }}
                        </span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Pending Requests') }}</span>
                        <span class="text-lg font-bold {{ $this->staffStats['pending_reservations'] > 0 ? 'text-warning' : 'text-base-content' }}">
                            {{ $this->staffStats['pending_reservations'] }}
                        </span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Unsettled Fines') }}</span>
                        <span class="text-lg font-bold text-error">₱{{ number_format($this->staffStats['unsettled_fines'], 2) }}</span>
                    </div>
                </div>
                <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                    <div class="card-body p-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __("Today's Collections") }}</span>
                        <span class="text-lg font-bold text-success">₱{{ number_format($this->staffStats['todays_collections'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Staff Quick Shortcuts -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
                <a href="{{ route('circulation.desk') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Circulation Desk') }}</span>
                </a>
                <a href="{{ route('circulation.reservations') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-inbox-stack class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Booking Requests') }}</span>
                </a>
                <a href="{{ route('circulation.overdue') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Overdue Tracking') }}</span>
                </a>
                <a href="{{ route('inventory.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-archive-box class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Equipment Catalog') }}</span>
                </a>
                <a href="{{ route('finance.penalties') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-banknotes class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Penalties & Fines') }}</span>
                </a>
                <a href="{{ route('reports.inventory') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-chart-pie class="w-4 h-4" />
                    </div>
                    <span class="text-xs font-semibold text-base-content group-hover:text-primary">{{ __('Analytics Reports') }}</span>
                </a>
            </div>

            <!-- Urgent Attention Queues -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- Pending Booking Requests Queue -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-2">
                            <h2 class="card-title text-sm font-bold">{{ __('Pending Booking Requests') }}</h2>
                            <a href="{{ route('circulation.reservations') }}" class="link link-primary text-xs font-semibold">{{ __('Review queue') }}</a>
                        </div>

                        @if ($this->pendingRequestsQueue->isEmpty())
                            <div class="py-6 text-center text-xs text-base-content/60">
                                {{ __('No pending reservation requests awaiting approval.') }}
                            </div>
                        @else
                            <div class="divide-y divide-base-200">
                                @foreach ($this->pendingRequestsQueue as $req)
                                    <div wire:key="pending-req-{{ $req->id }}" class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-semibold truncate">{{ $req->user?->name }} — {{ $req->item?->name }}</p>
                                            <p class="text-[11px] text-base-content/60">
                                                {{ $req->organization?->code }} • {{ $req->start_time?->format('M d, g:i A') }}
                                            </p>
                                        </div>
                                        <span class="badge badge-warning badge-xs shrink-0 whitespace-nowrap">{{ __('Pending') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Overdue & Due-Today Loans Queue -->
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-2">
                            <h2 class="card-title text-sm font-bold">{{ __('Overdue & Due-Today Loans') }}</h2>
                            <a href="{{ route('circulation.overdue') }}" class="link link-primary text-xs font-semibold">{{ __('View overdue') }}</a>
                        </div>

                        @if ($this->urgentLoansQueue->isEmpty())
                            <div class="py-6 text-center text-xs text-base-content/60">
                                {{ __('No overdue or due-today loans.') }}
                            </div>
                        @else
                            <div class="divide-y divide-base-200">
                                @foreach ($this->urgentLoansQueue as $loan)
                                    <div wire:key="urgent-loan-{{ $loan->id }}" class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-semibold truncate">{{ $loan->user?->name }} — {{ $loan->item?->name }}</p>
                                            <p class="text-[11px] text-base-content/60 font-mono">
                                                {{ $loan->itemUnit?->asset_tag }} • {{ $loan->organization?->code }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="badge badge-xs whitespace-nowrap {{ $loan->isOverdue() ? 'badge-error' : 'badge-warning' }}">
                                                {{ $loan->isOverdue() ? __('Overdue') : __('Due Today') }}
                                            </span>
                                            <p class="text-[11px] text-base-content/70 mt-0.5">
                                                {{ $loan->due_at?->format('M d, g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Superadmin Per-College Snapshot Table -->
            @if ($this->isSuperAdmin && ! empty($this->collegeSnapshot))
                <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                    <div class="card-body p-3 sm:p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="card-title text-sm font-bold">{{ __('Campus-Wide Per-College Snapshot') }}</h2>
                            <span class="text-xs text-base-content/60">{{ __('All active colleges') }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table table-zebra table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('College') }}</th>
                                        <th class="text-right">{{ __('Available / Total Units') }}</th>
                                        <th class="text-right">{{ __('Active Loans') }}</th>
                                        <th class="text-right">{{ __('Overdue') }}</th>
                                        <th class="text-right">{{ __('Pending Requests') }}</th>
                                        <th class="text-right">{{ __('Unsettled Fines') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->collegeSnapshot as $row)
                                        <tr wire:key="college-snap-{{ $row['id'] }}">
                                            <td>
                                                <span class="text-[11px] font-mono font-bold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded mr-1.5 whitespace-nowrap">{{ $row['code'] }}</span>
                                                <span class="text-xs font-medium">{{ $row['name'] }}</span>
                                            </td>
                                            <td class="text-right tabular-nums text-xs">
                                                <span class="font-bold text-success">{{ $row['available_units'] }}</span> / {{ $row['total_units'] }}
                                            </td>
                                            <td class="text-right tabular-nums text-xs font-semibold">{{ $row['active_loans'] }}</td>
                                            <td class="text-right tabular-nums text-xs {{ $row['overdue_loans'] > 0 ? 'font-bold text-error' : '' }}">
                                                {{ $row['overdue_loans'] }}
                                            </td>
                                            <td class="text-right tabular-nums text-xs {{ $row['pending_requests'] > 0 ? 'font-bold text-warning' : '' }}">
                                                {{ $row['pending_requests'] }}
                                            </td>
                                            <td class="text-right tabular-nums text-xs font-semibold text-error">
                                                ₱{{ number_format($row['unsettled_fines'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
