<div>
    {{-- Uniform Breadcrumb Card --}}
    <div class="card bg-base-100 card-border border-base-300 shadow-xs mb-4">
        <div class="card-body flex flex-row items-center justify-between py-4">
            <div class="breadcrumbs text-sm">
                <ul>
                    <li class="flex items-center gap-1.5">
                        <x-heroicon-o-building-office class="w-4 h-4" />
                        <span>{{ __('Developer') }}</span>
                    </li>
                    <li>{{ __('Organization') }}</li>
                </ul>
            </div>

            <div>
                <button type="button" class="btn btn-primary btn-sm px-6 font-semibold" wire:click="$dispatch('open::create-organization')">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span>{{ __('Add Organization') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Content Card --}}
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-base-content/70">{{ __('Per page') }}</span>
                    <select wire:model.live="quantity" class="select select-sm w-20">
                        @foreach ([5, 10, 25, 50] as $qty)
                            <option value="{{ $qty }}">{{ $qty }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="input input-sm flex items-center gap-2 w-full sm:w-64">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search organizations...') }}" class="grow" />
                </label>
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
                            <tr wire:key="org-row-{{ $row->id }}">
                                <td>{{ $row->id }}</td>
                                <td>
                                    <span class="badge badge-neutral badge-sm font-mono font-bold">{{ $row->code ?? '-' }}</span>
                                </td>
                                <td>
                                    <div class="font-semibold text-base-content">{{ $row->name }}</div>
                                    @if ($row->description)
                                        <div class="text-xs text-base-content/60 truncate max-w-xs">{{ $row->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs">{{ $row->email ?? '-' }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ $row->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    @if ($row->assignedUser)
                                        <div class="flex items-center gap-2">
                                            <div class="avatar avatar-placeholder">
                                                <div class="bg-primary/10 text-primary w-6 h-6 rounded-full text-[10px] font-bold">
                                                    <span>{{ strtoupper(substr($row->assignedUser->name, 0, 1)) }}</span>
                                                </div>
                                            </div>
                                            <span class="text-xs font-medium">{{ $row->assignedUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="badge badge-ghost badge-sm text-base-content/50">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row->is_active)
                                        <span class="badge badge-success badge-sm text-success-content font-medium">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm font-medium">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-xs text-base-content/70">
                                    {{ $row->created_at?->diffForHumans() }}
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <button type="button" class="btn btn-ghost btn-circle btn-sm"
                                            wire:click="$dispatch('load::organization', { 'organization' : '{{ $row->id }}' })"
                                            aria-label="{{ __('Edit organization') }}">
                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                        </button>
                                        <livewire:organization.delete :organization="$row" :key="'del-org-'.$row->id" @deleted="$refresh" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center py-8 text-base-content/60">
                                    {{ __('No organizations found.') }}
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

    {{-- Modals --}}
    <livewire:organization.create @created="$refresh" />
    <livewire:organization.update @updated="$refresh" />
</div>
