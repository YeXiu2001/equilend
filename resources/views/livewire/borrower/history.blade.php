<div>
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('Borrowing History'), 'icon' => 'heroicon-o-clock'],
    ]" />

    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-4 sm:p-6">
            <!-- Clean Toolbar: Count on left, Search on right -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-base-content/60">
                        {{ __('Showing :count of :total loans', ['count' => $this->loans->count(), 'total' => $this->totalCount]) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                        <input type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search equipment name...') }}"
                            class="grow" />
                    </label>
                </div>
            </div>

            <!-- Loading overlay -->
            <div class="relative min-h-[200px]">
                <div wire:loading.flex wire:target="search,sortBy" class="absolute inset-0 bg-base-100/60 z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('id')">
                                        <span>{{ __('Loan #') }}</span>
                                        @if ($sort['column'] === 'id')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>{{ __('Equipment') }}</th>
                                <th>{{ __('College') }}</th>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('borrowed_at')">
                                        <span>{{ __('Borrowed') }}</span>
                                        @if ($sort['column'] === 'borrowed_at')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('due_at')">
                                        <span>{{ __('Due Date') }}</span>
                                        @if ($sort['column'] === 'due_at')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                        wire:click="sortBy('returned_at')">
                                        <span>{{ __('Returned') }}</span>
                                        @if ($sort['column'] === 'returned_at')
                                            <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </button>
                                </th>
                                <th>{{ __('Return Status') }}</th>
                                <th>{{ __('Loan Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->loans as $loan)
                                @php
                                    $isUnreturned = $loan->returned_at === null && in_array($loan->status, [\App\Enums\LoanStatus::Active, \App\Enums\LoanStatus::Overdue], true);
                                    $isOverdue = $isUnreturned && $loan->isOverdue();
                                    $wasOnTime = $loan->returned_at && $loan->due_at && $loan->returned_at <= $loan->due_at;
                                @endphp
                                <tr wire:key="history-row-{{ $loan->id }}" class="hover">
                                    <td class="font-mono font-semibold text-xs whitespace-nowrap">#{{ $loan->id }}</td>
                                    <td>
                                        <div class="font-medium text-base-content">{{ $loan->item->name }}</div>
                                        <div class="text-xs font-mono text-primary">{{ $loan->itemUnit->asset_tag }}</div>
                                    </td>
                                    <td class="text-sm text-base-content/80 whitespace-nowrap">
                                        {{ $loan->organization->code ?? 'N/A' }}
                                    </td>
                                    <td class="text-xs whitespace-nowrap">
                                        {{ $loan->borrowed_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="text-xs whitespace-nowrap {{ $isOverdue ? 'text-error font-semibold' : '' }}">
                                        {{ $loan->due_at?->format('M d, Y H:i') ?? '—' }}
                                    </td>
                                    <td class="text-xs whitespace-nowrap">
                                        @if ($loan->returned_at)
                                            {{ $loan->returned_at->format('M d, Y H:i') }}
                                        @else
                                            <span class="text-base-content/50 italic">{{ __('Not returned yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if ($loan->returned_at)
                                            <span class="badge badge-sm whitespace-nowrap {{ $wasOnTime ? 'badge-success' : 'badge-warning' }}">
                                                {{ $wasOnTime ? __('On Time') : __('Late') }}
                                            </span>
                                        @elseif ($isOverdue)
                                            <span class="badge badge-sm badge-error whitespace-nowrap">
                                                {{ __('Overdue') }}
                                            </span>
                                        @elseif ($isUnreturned)
                                            <span class="badge badge-sm badge-info whitespace-nowrap">
                                                {{ __('Needs Return') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-base-content/40">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if ($isOverdue)
                                            <span class="badge badge-sm badge-error whitespace-nowrap">{{ __('Overdue') }}</span>
                                        @else
                                            <span class="badge badge-sm whitespace-nowrap {{ $loan->status->badgeClass() }}">{{ $loan->status->label() }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-10 text-base-content/60">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-heroicon-o-clock class="w-8 h-8 opacity-40" />
                                            <p class="font-medium">{{ __('No borrowing history found.') }}</p>
                                            <p class="text-xs">
                                                {{ mb_trim($search) !== '' ? __('Try adjusting your equipment name search.') : __('Your past and active equipment loans will appear here.') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scrolling Trigger & Indicator -->
            @if ($this->hasMore)
                <div wire:key="history-load-more-{{ $this->limit }}-{{ $this->totalCount }}-{{ md5((string) $this->search) }}"
                    wire:intersect="loadMore"
                    class="flex flex-col items-center justify-center py-6 gap-2">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-xs text-base-content/50">{{ __('Loading more loans...') }}</span>
                </div>
            @elseif ($this->loans->isNotEmpty())
                <div wire:key="history-all-loaded" class="text-center py-4 text-xs text-base-content/40">
                    {{ __('All :count loans loaded.', ['count' => $this->totalCount]) }}
                </div>
            @endif
        </div>
    </div>
</div>
