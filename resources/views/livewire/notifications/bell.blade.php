<div class="dropdown dropdown-end">
    <button tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm text-white hover:bg-white/10 relative" aria-label="Notifications">
        <x-heroicon-o-bell class="w-5 h-5" />
        @if ($this->unreadCount > 0)
            <span class="badge badge-secondary badge-xs absolute -top-0.5 -right-0.5 px-1 min-w-4 h-4 text-[10px] font-bold">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div tabindex="0" class="dropdown-content bg-base-100 text-base-content rounded-box z-50 mt-2 w-80 sm:w-96 p-3 shadow-xl border border-base-300">
        <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-2">
            <div class="flex items-center gap-2">
                <span class="font-bold text-xs uppercase tracking-wider text-base-content/80">{{ __('Notifications') }}</span>
                <span class="badge badge-xs badge-secondary font-medium">{{ $this->unreadCount }} {{ __('Unread') }}</span>
            </div>

            @if ($this->recentNotifications->isNotEmpty())
                <div class="flex items-center gap-1">
                    @if ($this->unreadCount > 0)
                        <button type="button" wire:click="markAllAsRead" class="btn btn-ghost btn-xs text-[11px] text-primary">
                            {{ __('Mark all read') }}
                        </button>
                    @endif
                    <button type="button" wire:click="clearAll" class="btn btn-ghost btn-xs text-[11px] text-base-content/60 hover:text-error">
                        {{ __('Clear') }}
                    </button>
                </div>
            @endif
        </div>

        @if ($this->recentNotifications->isEmpty())
            <div class="py-6 text-center text-xs text-base-content/60">
                <x-heroicon-o-bell-slash class="w-7 h-7 mx-auto mb-1.5 opacity-40 text-base-content" />
                {{ __('No new notifications') }}
            </div>
        @else
            <div class="max-h-80 overflow-y-auto divide-y divide-base-200 -mx-1 px-1">
                @foreach ($this->recentNotifications as $notification)
                    @php
                        $data = $notification->data;
                        $type = $data['type'] ?? 'info';
                        $isUnread = $notification->read_at === null;
                    @endphp
                    <button
                        type="button"
                        wire:key="notif-{{ $notification->id }}"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="w-full text-left flex items-start gap-2.5 p-2 rounded-lg transition-colors hover:bg-base-200/70 {{ $isUnread ? 'bg-primary/5' : '' }}"
                    >
                        <div class="mt-0.5 shrink-0">
                            @switch($type)
                                @case('return_reminder')
                                    <span class="w-7 h-7 rounded-full bg-warning/15 text-warning flex items-center justify-center">
                                        <x-heroicon-o-clock class="w-4 h-4" />
                                    </span>
                                    @break
                                @case('loan_overdue')
                                    <span class="w-7 h-7 rounded-full bg-error/15 text-error flex items-center justify-center">
                                        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                                    </span>
                                    @break
                                @case('penalty_imposed')
                                    <span class="w-7 h-7 rounded-full bg-error/15 text-error flex items-center justify-center">
                                        <x-heroicon-o-banknotes class="w-4 h-4" />
                                    </span>
                                    @break
                                @case('reservation_status')
                                @case('reservation_request')
                                    <span class="w-7 h-7 rounded-full bg-info/15 text-info flex items-center justify-center">
                                        <x-heroicon-o-calendar-days class="w-4 h-4" />
                                    </span>
                                    @break
                                @default
                                    <span class="w-7 h-7 rounded-full bg-base-200 text-base-content/70 flex items-center justify-center">
                                        <x-heroicon-o-bell class="w-4 h-4" />
                                    </span>
                            @endswitch
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-base-content truncate">
                                    {{ $data['title'] ?? __('Notification') }}
                                </p>
                                @if ($isUnread)
                                    <span class="w-2 h-2 rounded-full bg-primary shrink-0" title="Unread"></span>
                                @endif
                            </div>
                            <p class="text-[11px] text-base-content/75 line-clamp-2 mt-0.5">
                                {{ $data['message'] ?? '' }}
                            </p>
                            <p class="text-[10px] text-base-content/50 mt-1">
                                {{ $notification->created_at?->diffForHumans() }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>
