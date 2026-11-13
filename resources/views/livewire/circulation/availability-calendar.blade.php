<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Circulation & Desk'), 'icon' => 'heroicon-o-arrows-right-left'],
        ['label' => __('Equipment Availability Calendar'), 'icon' => 'heroicon-o-calendar'],
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
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-4 sm:p-5">
            <!-- Header: Month Navigation & Filter Controls -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-3 border-b border-base-200">
                <!-- Navigation -->
                <div class="flex items-center gap-3">
                    <div class="join">
                        <button type="button" wire:click="previousMonth" class="join-item btn btn-sm btn-ghost btn-square" title="{{ __('Previous Month') }}">
                            <x-heroicon-o-chevron-left class="w-4 h-4" />
                        </button>
                        <button type="button" wire:click="goToToday" class="join-item btn btn-sm btn-ghost font-semibold text-xs">
                            {{ __('Today') }}
                        </button>
                        <button type="button" wire:click="nextMonth" class="join-item btn btn-sm btn-ghost btn-square" title="{{ __('Next Month') }}">
                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                        </button>
                    </div>

                    <h2 class="text-base sm:text-lg font-bold text-base-content tracking-tight">
                        {{ $currentMonthName }}
                    </h2>
                </div>

                <!-- Filters & Legend -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <select wire:model.live="selectedCategoryId" class="select select-sm select-bordered text-xs">
                        <option value="all">{{ __('All Categories') }}</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="selectedItemId" class="select select-sm select-bordered text-xs max-w-xs">
                        <option value="all">{{ __('All Equipment Items') }}</option>
                        @foreach ($this->items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Visual Legend -->
            <div class="flex flex-wrap items-center gap-3 py-2 text-[11px] text-base-content/70">
                <span class="font-bold text-base-content">{{ __('Legend') }}:</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-info"></span>
                    <span>{{ __('Approved Booking') }}</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-warning"></span>
                    <span>{{ __('Maintenance Window') }}</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-error"></span>
                    <span>{{ __('College Blackout Period') }}</span>
                </span>
            </div>

            <!-- Calendar Grid -->
            <div class="border border-base-300 rounded-box overflow-hidden mt-1">
                <!-- Day Name Headers (Sun to Sat) -->
                <div class="grid grid-cols-7 bg-base-200/70 border-b border-base-300 text-center font-bold text-[11px] text-base-content/80 py-2">
                    <div>{{ __('Sun') }}</div>
                    <div>{{ __('Mon') }}</div>
                    <div>{{ __('Tue') }}</div>
                    <div>{{ __('Wed') }}</div>
                    <div>{{ __('Thu') }}</div>
                    <div>{{ __('Fri') }}</div>
                    <div>{{ __('Sat') }}</div>
                </div>

                <!-- Days Cells -->
                <div class="grid grid-cols-7 divide-x divide-y divide-base-300 bg-base-100">
                    @foreach ($this->calendarDays as $day)
                        <div class="min-h-[90px] sm:min-h-[110px] p-1.5 flex flex-col justify-between transition {{ $day['is_current_month'] ? 'bg-base-100' : 'bg-base-200/30 opacity-60' }} {{ $day['is_today'] ? 'ring-1 ring-primary bg-primary/5' : '' }}">
                            <!-- Day Number -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold {{ $day['is_today'] ? 'w-6 h-6 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold' : ($day['is_current_month'] ? 'text-base-content' : 'text-base-content/40') }}">
                                    {{ $day['day_number'] }}
                                </span>
                                @if ($day['is_today'])
                                    <span class="text-[9px] font-bold text-primary uppercase tracking-wider">{{ __('Today') }}</span>
                                @endif
                            </div>

                            <!-- Events List -->
                            <div class="space-y-1 mt-1 overflow-y-auto max-h-24">
                                @foreach ($day['events'] as $event)
                                    <button type="button"
                                        wire:click="viewEventDetails('{{ $event['source'] }}', {{ $event['id'] }})"
                                        class="w-full text-left p-1 rounded text-[10px] truncate block leading-tight font-medium hover:opacity-80 transition {{ $event['badge_class'] }}"
                                        title="{{ $event['title'] }}">
                                        {{ $event['title'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- EVENT DETAILS MODAL -->
    <dialog class="modal {{ $eventModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-md bg-base-100 p-5">
            <h3 class="font-bold text-base flex items-center gap-2 text-base-content">
                <x-heroicon-o-calendar-days class="w-5 h-5 text-primary" />
                <span>{{ __('Event & Schedule Details') }}</span>
            </h3>

            <div class="bg-base-200/50 p-3.5 rounded-box space-y-2 text-xs my-3 border border-base-300">
                <div class="flex justify-between border-b border-base-200 pb-1">
                    <span class="text-base-content/60">{{ __('Type') }}:</span>
                    <span class="font-bold text-primary">{{ $modalType }}</span>
                </div>
                <div class="flex justify-between border-b border-base-200 pb-1">
                    <span class="text-base-content/60">{{ __('College Hub') }}:</span>
                    <span class="font-semibold text-base-content">{{ $modalCollege }}</span>
                </div>
                <div class="flex justify-between border-b border-base-200 pb-1">
                    <span class="text-base-content/60">{{ __('Schedule Window') }}:</span>
                    <span class="font-mono text-base-content text-[11px]">{{ $modalDates }}</span>
                </div>
                <div class="flex justify-between border-b border-base-200 pb-1">
                    <span class="text-base-content/60">{{ __('Equipment') }}:</span>
                    <span class="font-semibold text-base-content">{{ $modalItem }}</span>
                </div>
                <div class="flex justify-between border-b border-base-200 pb-1">
                    <span class="text-base-content/60">{{ __('Physical Unit') }}:</span>
                    <span class="font-mono font-bold text-primary">{{ $modalUnit }}</span>
                </div>
                <div class="pt-1">
                    <span class="text-base-content/60 block mb-0.5">{{ __('Purpose / Reason') }}:</span>
                    <p class="text-base-content font-medium leading-relaxed">{{ $modalTitle }}</p>
                    <p class="text-base-content/70 mt-1 italic text-[11px]">{{ $modalBorrowerOrReason }}</p>
                </div>
            </div>

            <div class="modal-action mt-4">
                <button type="button" wire:click="$set('eventModal', false)" class="btn btn-ghost btn-sm font-semibold">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('eventModal', false)">close</button>
        </form>
    </dialog>
</div>
