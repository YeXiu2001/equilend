<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Circulation & Desk'), 'icon' => 'heroicon-o-arrows-right-left'],
        ['label' => __('Active Equipment Loans'), 'icon' => 'heroicon-o-clipboard-document-check'],
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
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 bg-base-200 border border-base-300 px-2.5 py-1 rounded-md whitespace-nowrap">
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

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-4 sm:p-5">
            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button"
                        wire:click="$set('statusFilter', 'all')"
                        class="btn btn-xs {{ $statusFilter === 'all' ? 'btn-neutral' : 'btn-ghost' }} font-semibold">
                        {{ __('All Current (:count)', ['count' => $this->totalCount]) }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'active')"
                        class="btn btn-xs {{ $statusFilter === 'active' ? 'btn-primary' : 'btn-ghost' }} font-semibold">
                        {{ __('Active (On Schedule)') }}
                    </button>
                    <button type="button"
                        wire:click="$set('statusFilter', 'overdue')"
                        class="btn btn-xs {{ $statusFilter === 'overdue' ? 'btn-error text-error-content' : 'btn-ghost' }} font-semibold">
                        {{ __('Overdue Attention Required') }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60 text-primary" />
                        <input type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search borrower, item, or asset tag...') }}"
                            class="grow text-xs" />
                    </label>
                </div>
            </div>

            <!-- Table of Active Loans -->
            <div class="overflow-x-auto border border-base-300 rounded-box">
                <table class="table table-sm table-zebra w-full text-xs">
                    <thead>
                        <tr class="bg-base-200/60 text-base-content/80 font-bold uppercase text-[10px]">
                            <th>{{ __('Loan / Borrower') }}</th>
                            <th>{{ __('Equipment & Serial Unit') }}</th>
                            <th>{{ __('Borrowed On') }}</th>
                            <th>{{ __('Return Deadline / Urgency') }}</th>
                            <th>{{ __('Condition') }}</th>
                            <th class="text-right">{{ __('Circulation Desk') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->loans as $loan)
                            <tr class="hover">
                                <td>
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5 font-bold text-base-content">
                                            <span>#{{ $loan->id }}</span>
                                            <span>•</span>
                                            <span>{{ $loan->user->name }}</span>
                                        </div>
                                        <div class="text-base-content/60 text-[11px]">{{ $loan->user->email }}</div>
                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $loan->organization->code }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-base-content block">{{ $loan->item->name }}</span>
                                        <div class="flex items-center gap-1">
                                            <span class="font-mono text-primary text-[11px] bg-primary/10 px-1.5 py-0.5 rounded font-bold">
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
                                        <span class="text-base-content font-medium block">{{ $loan->borrowed_at->format('M d, Y') }}</span>
                                        <span class="text-base-content/60 text-[11px]">{{ $loan->borrowed_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-1">
                                        <span class="text-base-content font-medium block">{{ $loan->due_at->format('M d, Y H:i') }}</span>
                                        @if ($loan->isOverdue())
                                            <span class="badge badge-xs badge-error font-bold gap-1 animate-pulse whitespace-nowrap">
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3" />
                                                {{ __('Overdue by :time', ['time' => $loan->due_at->diffForHumans(now(), true)]) }}
                                            </span>
                                        @elseif ($loan->due_at->isToday())
                                            <span class="badge badge-xs badge-warning font-bold gap-1 whitespace-nowrap">
                                                <x-heroicon-o-clock class="w-3 h-3" />
                                                {{ __('Due Today in :time', ['time' => $loan->due_at->diffForHumans(now(), true)]) }}
                                            </span>
                                        @else
                                            <span class="badge badge-xs badge-info font-medium whitespace-nowrap">
                                                {{ __('Due in :time', ['time' => now()->diffForHumans($loan->due_at, true)]) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold whitespace-nowrap {{ $loan->checkout_condition->textClass() }}">
                                        {{ $loan->checkout_condition->label() }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('circulation.desk') }}" class="btn btn-xs btn-ghost text-primary font-semibold gap-1">
                                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                        <span>{{ __('Check-In') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-base-content/60">
                                    <x-heroicon-o-clipboard-document-check class="w-8 h-8 mx-auto mb-2 opacity-60 text-success" />
                                    <span>{{ __('No active loans found matching your criteria.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->loans->count() < $this->totalCount)
                <div class="mt-4 text-center">
                    <button type="button" wire:click="loadMore" class="btn btn-ghost btn-xs font-semibold">
                        {{ __('Load More Loans (:remaining remaining)', ['remaining' => $this->totalCount - $this->loans->count()]) }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
