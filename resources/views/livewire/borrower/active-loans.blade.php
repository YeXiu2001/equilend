<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('My Active Loans'), 'icon' => 'heroicon-o-arrow-path'],
    ]" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
        @forelse ($this->activeLoans as $loan)
            @php $isOverdue = $loan->isOverdue(); @endphp
            <div class="card bg-base-100 card-border {{ $isOverdue ? 'border-error/60' : 'border-base-300' }} shadow-xs"
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
                <div class="card-body p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="badge badge-ghost badge-xs font-semibold">{{ $loan->organization->code }}</span>
                            <h3 class="font-bold text-sm text-base-content mt-1">{{ $loan->item->name }}</h3>
                            <p class="text-xs font-mono text-primary font-semibold">{{ $loan->itemUnit->asset_tag }}</p>
                        </div>
                        <span class="badge badge-xs {{ $loan->status->badgeClass() }}">{{ $loan->status->label() }}</span>
                    </div>

                    <div class="bg-base-200/60 rounded-box p-2.5 mt-2 space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Borrowed') }}</span>
                            <span>{{ $loan->borrowed_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Due Deadline') }}</span>
                            <span class="font-semibold {{ $isOverdue ? 'text-error' : '' }}">{{ $loan->due_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Condition Out') }}</span>
                            <span class="badge badge-xs {{ $loan->checkout_condition->badgeClass() }}">{{ $loan->checkout_condition->label() }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between pt-2 border-t border-base-200">
                        <span class="text-[11px] text-base-content/60">{{ __('Time Remaining') }}</span>
                        <span class="font-mono text-xs font-bold"
                            x-bind:class="isOverdue ? 'text-error' : 'text-primary'"
                            x-text="countdown"></span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-8 text-center text-xs text-base-content/60">
                    <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-success opacity-80" />
                    <span class="font-bold text-sm text-base-content block">{{ __('No Active Equipment Loans') }}</span>
                    <span class="mt-1 block">{{ __('You currently have no borrowed equipment checked out.') }}</span>
                </div>
            </div>
        @endforelse
    </div>
</div>
