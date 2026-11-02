<div>
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <livewire:users.create @created="$refresh" />

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-base-content/70">{{ __('Per page') }}</span>
                        <select wire:model.live="quantity" class="select select-sm w-20">
                            @foreach ([2, 5, 15, 25] as $qty)
                                <option value="{{ $qty }}">{{ $qty }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search users...') }}" class="grow" />
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto relative">
                <div wire:loading.flex class="absolute inset-0 bg-base-100/60 z-10 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <table class="table table-zebra">
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                                @php
                                    $isSortable = $header['sortable'] ?? true;
                                    $col = $header['index'];
                                    $label = $header['label'] ?? '';
                                @endphp
                                <th>
                                    @if ($isSortable)
                                        <button type="button" class="flex items-center gap-1 cursor-pointer hover:text-primary font-semibold"
                                            wire:click="$set('sort', { column: '{{ $col }}', direction: '{{ $sort['column'] === $col && $sort['direction'] === 'asc' ? 'desc' : 'asc' }}' })">
                                            <span>{{ $label }}</span>
                                            @if ($sort['column'] === $col)
                                                <span class="text-xs">{{ $sort['direction'] === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </button>
                                    @else
                                        <span>{{ $label }}</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rows as $row)
                            <tr wire:key="user-row-{{ $row->id }}">
                                <td>{{ $row->id }}</td>
                                <td class="font-medium">{{ $row->name }}</td>
                                <td>{{ $row->email }}</td>
                                <td>{{ $row->created_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <button type="button" class="btn btn-ghost btn-circle btn-sm"
                                            wire:click="$dispatch('load::user', { 'user' : '{{ $row->id }}'})"
                                            aria-label="Edit user">
                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                        </button>
                                        <livewire:users.delete :user="$row" :key="uniqid('', true)" @deleted="$refresh" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center py-8 text-base-content/60">
                                    {{ __('No users found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $this->rows->links() }}
            </div>
        </div>
    </div>

    <livewire:users.update @updated="$refresh" />
</div>
