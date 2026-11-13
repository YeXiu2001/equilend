<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Circulation & Desk'), 'icon' => 'heroicon-o-arrows-right-left'],
        ['label' => __('Overdue Equipment Tracking'), 'icon' => 'heroicon-o-exclamation-circle'],
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
                    <span class="badge badge-neutral badge-sm font-semibold gap-1">
                        <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                        {{ auth()->user()?->organization?->code ?? 'Hub' }}
                    </span>
                @endif

                <a href="{{ route('circulation.desk') }}" class="btn btn-primary btn-sm font-semibold gap-1">
                    <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                    <span>{{ __('Desk Operations') }}</span>
                </a>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <!-- Top Attention Banner if Overdue Exists -->
    @if ($this->totalCount > 0)
        <div class="alert alert-error shadow-xs border border-error/30 mt-2 py-2.5 px-3 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-error-content">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0" />
                <span>
                    <strong>{{ __(':count overdue loan(s) detected.', ['count' => $this->totalCount]) }}</strong>
                    {{ __('Equipment return deadlines have elapsed without physical check-in. Borrowers are subject to Tier 4 penalties and lending suspension.') }}
                </span>
            </div>
        </div>
    @endif

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-base-content flex items-center gap-1.5">
                        <x-heroicon-o-clock class="w-4 h-4 text-error" />
                        <span>{{ __('Past-Due Equipment Ledger') }}</span>
                    </span>
                    <span class="badge badge-error badge-xs font-bold text-error-content">
                        {{ $this->totalCount }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60 text-primary" />
                        <input type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search student, item, or asset tag...') }}"
                            class="grow text-xs" />
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto border border-base-300 rounded-box">
                <table class="table table-sm table-zebra w-full text-xs">
                    <thead>
                        <tr class="bg-base-200/60 text-base-content/80 font-bold uppercase text-[10px]">
                            <th>{{ __('Borrower Contact') }}</th>
                            <th>{{ __('Overdue Equipment') }}</th>
                            <th>{{ __('Original Return Deadline') }}</th>
                            <th>{{ __('Elapsed Delay') }}</th>
                            <th>{{ __('Issued By') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->overdueLoans as $loan)
                            <tr class="hover">
                                <td>
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5 font-bold text-base-content">
                                            <span>{{ $loan->user->name }}</span>
                                            <span class="badge badge-ghost badge-xs">{{ $loan->organization->code }}</span>
                                        </div>
                                        <div class="text-base-content/60 text-[11px]">{{ $loan->user->email }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-base-content block">{{ $loan->item->name }}</span>
                                        <div class="flex items-center gap-1">
                                            <span class="font-mono text-error font-bold text-[11px] bg-error/10 px-1.5 py-0.5 rounded">
                                                {{ $loan->itemUnit->asset_tag }}
                                            </span>
                                            @if ($loan->itemUnit->serial_number)
                                                <span class="text-base-content/50 text-[10px] font-mono">
                                                    ({{ $loan->itemUnit->serial_number }})
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-0.5">
                                        <span class="text-base-content font-medium block">{{ $loan->due_at->format('M d, Y') }}</span>
                                        <span class="text-base-content/60 text-[11px]">{{ $loan->due_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-xs badge-error font-bold gap-1 py-2 px-2">
                                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                                        <span>{{ __('Overdue by :time', ['time' => $loan->due_at->diffForHumans(now(), true)]) }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-base-content/70">{{ $loan->issuedBy->name }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                            wire:click="sendReminder({{ $loan->id }})"
                                            class="btn btn-xs btn-outline btn-warning font-semibold gap-1">
                                            <x-heroicon-o-bell-alert class="w-3.5 h-3.5" />
                                            <span>{{ __('Notify') }}</span>
                                        </button>
                                        <a href="{{ route('circulation.desk') }}" class="btn btn-xs btn-primary font-semibold gap-1">
                                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                            <span>{{ __('Check-In') }}</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-base-content/60">
                                    <x-heroicon-o-shield-check class="w-8 h-8 mx-auto mb-2 text-success opacity-80" />
                                    <span class="font-bold block text-base-content">{{ __('No Overdue Equipment Loans') }}</span>
                                    <span class="text-[11px] block text-base-content/60 mt-0.5">
                                        {{ __('All active equipment circulations are on schedule within their designated return deadlines.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->overdueLoans->count() < $this->totalCount)
                <div class="mt-4 text-center">
                    <button type="button" wire:click="loadMore" class="btn btn-ghost btn-xs font-semibold">
                        {{ __('Load More Overdue Loans (:remaining remaining)', ['remaining' => $this->totalCount - $this->overdueLoans->count()]) }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
