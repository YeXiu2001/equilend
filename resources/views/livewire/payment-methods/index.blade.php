<div>
    {{-- Uniform Breadcrumb Card --}}
    <div class="card bg-base-100 card-border border-base-300 shadow-xs mb-4">
        <div class="card-body flex flex-row items-center justify-between py-4">
            <div class="breadcrumbs text-sm">
                <ul>
                    <li class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                        <span>{{ __('Developer') }}</span>
                    </li>
                    <li>{{ __('Payment Methods') }}</li>
                </ul>
            </div>

            <div>
                <button type="button" class="btn btn-primary btn-sm px-6 font-semibold" wire:click="$dispatch('open::create-payment-method')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('Add Payment Method') }}</span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-60">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search payment methods...') }}" class="grow" />
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
                            <tr wire:key="pm-row-{{ $row->id }}">
                                <td>{{ $row->id }}</td>
                                <td>
                                    <span class="badge badge-neutral badge-sm font-mono font-bold">{{ $row->payment_code }}</span>
                                </td>
                                <td class="font-semibold text-base-content">{{ $row->name }}</td>
                                <td class="text-xs text-base-content/70">{{ $row->description ?? '-' }}</td>
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
                                            wire:click="$dispatch('load::payment-method', { 'paymentMethod' : '{{ $row->id }}' })"
                                            aria-label="{{ __('Edit payment method') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <livewire:payment-methods.delete :paymentMethod="$row" :key="'del-pm-'.$row->id" @deleted="$refresh" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center py-8 text-base-content/60">
                                    {{ __('No payment methods found.') }}
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
    <livewire:payment-methods.create @created="$refresh" />
    <livewire:payment-methods.update @updated="$refresh" />
</div>
