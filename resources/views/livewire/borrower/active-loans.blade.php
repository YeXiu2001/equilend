<div>
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('My Active Loans'), 'icon' => 'heroicon-o-arrow-path'],
    ]">
        <x-slot:actions>
            <a href="{{ route('portal.browse') }}" class="btn btn-primary btn-sm px-4 font-semibold gap-1.5">
                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                <span class="hidden sm:inline">{{ __('Browse Equipment') }}</span>
            </a>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-4 sm:p-6">
            <!-- Clean Toolbar: Count on left, Equipment Name Search on right -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-base-content/60">
                        {{ trans_choice('{0} No active loans|{1} Showing 1 active loan|[2,*] Showing :count active loans', $this->activeLoans->count(), ['count' => $this->activeLoans->count()]) }}
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
                <div wire:loading.flex wire:target="search" class="absolute inset-0 bg-base-100/60 z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($this->activeLoans as $loan)
                        @php $isOverdue = $loan->isOverdue(); @endphp
                        <div wire:key="active-loan-{{ $loan->id }}"
                            class="card bg-base-100 card-border {{ $isOverdue ? 'border-error/60' : 'border-base-300' }} shadow-xs flex flex-col justify-between"
                            x-data="{
                                dueAt: new Date('{{ $loan->due_at->toIso8601String() }}').getTime(),
                                countdown: '',
                                isOverdue: {{ $isOverdue ? 'true' : 'false' }},
                                updateTimer() {
                                    const diff = this.dueAt - Date.now();
                                    if (diff <= 0) {
                                        this.isOverdue = true;
                                        const abs = Math.abs(diff);
                                        const hrs = Math.floor(abs / 3600000);
                                        const mins = Math.floor((abs % 3600000) / 60000);
                                        this.countdown = hrs + 'h ' + mins + 'm overdue';
                                    } else {
                                        const days = Math.floor(diff / 86400000);
                                        const hrs = Math.floor((diff % 86400000) / 3600000);
                                        const mins = Math.floor((diff % 3600000) / 60000);
                                        this.countdown = (days > 0 ? days + 'd ' : '') + hrs + 'h ' + mins + 'm left';
                                    }
                                }
                            }"
                            x-init="updateTimer(); setInterval(() => updateTimer(), 30000)">
                            <div class="card-body p-4 flex flex-col justify-between gap-3">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="font-bold text-base text-base-content truncate">{{ $loan->item->name }}</h3>
                                            <p class="text-xs font-mono text-primary font-semibold mt-0.5">{{ $loan->itemUnit->asset_tag }}</p>
                                        </div>
                                        <span class="badge badge-sm shrink-0 whitespace-nowrap {{ $loan->status->badgeClass() }}">
                                            {{ $loan->status->label() }}
                                        </span>
                                    </div>

                                    <!-- Clear Lender Details -->
                                    <div class="mt-3 p-2.5 rounded-lg bg-base-200/70 border border-base-300/60 text-xs">
                                        <div class="flex items-center gap-1.5 text-base-content/60 font-medium">
                                            <x-heroicon-o-building-office-2 class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ __('Lender') }}</span>
                                        </div>
                                        <div class="font-semibold text-base-content mt-0.5">
                                            {{ $loan->organization->name ?? 'N/A' }}
                                            @if ($loan->organization?->code)
                                                <span class="text-base-content/60 font-normal">({{ $loan->organization->code }})</span>
                                            @endif
                                        </div>
                                        @if ($loan->issuedBy)
                                            <div class="text-[11px] text-base-content/60 mt-0.5">
                                                {{ __('Issued by :name', ['name' => $loan->issuedBy->name]) }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Loan Timing & Condition -->
                                    <div class="mt-2.5 space-y-1.5 text-xs">
                                        <div class="flex justify-between">
                                            <span class="text-base-content/60">{{ __('Borrowed') }}</span>
                                            <span class="font-medium">{{ $loan->borrowed_at->format('M d, Y H:i') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-base-content/60">{{ __('Due Deadline') }}</span>
                                            <span class="font-semibold {{ $isOverdue ? 'text-error' : '' }}">{{ $loan->due_at->format('M d, Y H:i') }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-base-content/60">{{ __('Condition Out') }}</span>
                                            <span class="text-xs font-semibold whitespace-nowrap {{ $loan->checkout_condition->textClass() }}">{{ $loan->checkout_condition->label() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2.5 border-t border-base-200 flex items-center justify-between gap-2">
                                    <div>
                                        <span class="text-[11px] text-base-content/60 block">{{ __('Time Remaining') }}</span>
                                        <span class="font-mono text-xs font-bold"
                                            x-bind:class="isOverdue ? 'text-error' : 'text-primary'"
                                            x-text="countdown"></span>
                                    </div>

                                    <button type="button"
                                        wire:click="openSpecsModal({{ $loan->item_id }})"
                                        class="btn btn-primary btn-xs gap-1 font-semibold">
                                        <x-heroicon-o-information-circle class="w-3.5 h-3.5" />
                                        <span>{{ __('View Specs') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12 text-base-content/60">
                            <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-success opacity-80" />
                            <p class="font-semibold text-sm text-base-content">
                                {{ mb_trim($search) !== '' ? __('No matching active loans found.') : __('No Active Equipment Loans') }}
                            </p>
                            <p class="text-xs mt-1">
                                {{ mb_trim($search) !== '' ? __('Try adjusting your equipment name search.') : __('You currently have no borrowed equipment checked out.') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- View Specifications Modal                  -->
    <!-- ========================================== -->
    <dialog class="modal" @if ($specsModal && $this->viewingItem) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->viewingItem)
                @php $specItem = $this->viewingItem; @endphp
                <div class="flex items-start justify-between gap-2 border-b border-base-200 pb-3">
                    <div>
                        <p class="text-xs font-semibold text-base-content/60">
                            {{ $specItem->organization->name ?? 'N/A' }}
                            @if ($specItem->organization?->code)
                                ({{ $specItem->organization->code }})
                            @endif
                            @if ($specItem->category)
                                • {{ $specItem->category->name }}
                            @endif
                        </p>
                        <h3 class="font-bold text-lg mt-1">{{ $specItem->name }}</h3>
                        @if ($specItem->brand || $specItem->model_number)
                            <p class="text-xs text-base-content/60">
                                {{ trim(($specItem->brand ?? '') . ' ' . ($specItem->model_number ?? '')) }}
                            </p>
                        @endif
                    </div>
                    <button type="button" wire:click="$set('specsModal', false)" class="btn btn-ghost btn-xs btn-circle">✕</button>
                </div>

                <div class="py-4 space-y-3 text-xs">
                    @if (! empty($specItem->description))
                        <div>
                            <span class="font-semibold block mb-1 text-base-content/80">{{ __('Description') }}</span>
                            <p class="text-base-content/75 leading-relaxed">{{ $specItem->description }}</p>
                        </div>
                    @endif

                    <div class="pt-2 border-t border-base-200">
                        <span class="font-semibold block mb-1.5 text-base-content/80">{{ __('Technical Specifications') }}</span>
                        @if (empty($specItem->specifications))
                            <p class="text-base-content/60">{{ __('No detailed specifications provided.') }}</p>
                        @elseif (is_array($specItem->specifications))
                            <div class="space-y-1">
                                @foreach ($specItem->specifications as $specKey => $specVal)
                                    <div class="flex justify-between gap-2 py-1 border-b border-base-200/60 last:border-b-0">
                                        <span class="text-base-content/60">{{ is_string($specKey) ? ucfirst(str_replace('_', ' ', $specKey)) : __('Spec') }}</span>
                                        <span class="font-semibold text-right">{{ is_array($specVal) ? implode(', ', $specVal) : $specVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-base-content/80 whitespace-pre-line">{{ $specItem->specifications }}</p>
                        @endif
                    </div>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="$set('specsModal', false)" class="btn btn-sm btn-ghost">{{ __('Close') }}</button>
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('specsModal', false)">close</button>
        </form>
    </dialog>
</div>
