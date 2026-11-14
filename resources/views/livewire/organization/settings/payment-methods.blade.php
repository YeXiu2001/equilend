<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 mb-4 border-b border-base-200 gap-2">
        <div>
            <h3 class="text-sm font-bold text-base-content flex items-center gap-2">
                <x-heroicon-o-credit-card class="w-4 h-4 text-primary shrink-0" />
                <span>{{ __('Payment Channels & Receiving Accounts') }}</span>
            </h3>
            <p class="text-xs text-base-content/60 mt-0.5">
                {{ __('Enable payment channels and configure receiving account names and numbers for penalty settlements.') }}
            </p>
        </div>
        <div class="text-xs text-base-content/60 shrink-0 bg-base-200/70 border border-base-300 px-2.5 py-1 rounded-md self-start sm:self-auto">
            <span class="font-bold text-base-content tabular-nums">{{ collect($paymentConfigs)->where('is_enabled', true)->count() }}</span>
            <span>{{ __('of :total enabled', ['total' => $this->availablePaymentMethods->count()]) }}</span>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($this->availablePaymentMethods as $pm)
            @php
                $isCash = $pm->isCash();
                $isEnabled = $isCash || ! empty($paymentConfigs[$pm->id]['is_enabled']);
            @endphp
            <div class="rounded-xl border transition-colors {{ $isEnabled ? 'border-base-300 bg-base-100' : 'border-base-200 bg-base-200/30' }}" wire:key="pm-row-{{ $pm->id }}">
                {{-- Row Header --}}
                <div class="p-3.5 sm:p-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $isCash ? 'bg-success/10 text-success' : ($isEnabled ? 'bg-primary/10 text-primary' : 'bg-base-200 text-base-content/45') }}">
                            @if ($isCash)
                                <x-heroicon-o-banknotes class="w-4 h-4" />
                            @else
                                <x-heroicon-o-credit-card class="w-4 h-4" />
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-sm text-base-content">{{ $pm->name }}</span>
                                <span class="text-[11px] font-mono text-base-content/60 bg-base-200 px-1.5 py-0.5 rounded">{{ $pm->payment_code }}</span>
                                @if ($isCash)
                                    <span class="badge badge-success badge-xs font-semibold whitespace-nowrap">{{ __('Always Enabled') }}</span>
                                    <span class="text-[11px] text-base-content/60 font-medium whitespace-nowrap">{{ __('Over the Counter') }}</span>
                                @elseif ($isEnabled)
                                    <span class="badge badge-success badge-xs font-semibold whitespace-nowrap">{{ __('Enabled') }}</span>
                                @else
                                    <span class="badge badge-ghost badge-xs text-base-content/60 font-medium whitespace-nowrap">{{ __('Disabled') }}</span>
                                @endif
                            </div>
                            @if ($pm->description)
                                <p class="text-xs text-base-content/55 mt-0.5 truncate">{{ $pm->description }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        @if ($isCash)
                            <input type="checkbox"
                                checked
                                disabled
                                class="toggle toggle-success toggle-sm opacity-75 cursor-not-allowed"
                                aria-label="{{ __('Cash is always enabled') }}" />
                        @else
                            <input type="checkbox"
                                wire:model.live="paymentConfigs.{{ $pm->id }}.is_enabled"
                                class="toggle toggle-primary toggle-sm cursor-pointer"
                                aria-label="{{ __('Toggle :name', ['name' => $pm->name]) }}" />
                        @endif
                    </div>
                </div>

                {{-- Expanded fields when enabled (digital channels only) --}}
                @if ($isEnabled && ! $isCash)
                    <div class="border-t border-base-200 bg-base-200/35 p-3.5 sm:p-4 rounded-b-xl">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            {{-- Account Name (Left Column: 5 cols on lg) --}}
                            <div class="lg:col-span-5">
                                <fieldset class="fieldset w-full">
                                    <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                                        <x-heroicon-o-identification class="w-3.5 h-3.5 text-base-content/60" />
                                        <span>{{ __('Account Name') }} *</span>
                                    </legend>
                                    <input type="text"
                                        wire:model="paymentConfigs.{{ $pm->id }}.account_name"
                                        placeholder="{{ __('e.g. EquiLend Lending Inc.') }}"
                                        class="input input-sm w-full @error('paymentConfigs.'.$pm->id.'.account_name') input-error @enderror" />
                                    <p class="text-[11px] text-base-content/55 mt-1">
                                        {{ __('Recipient name for all account numbers.') }}
                                    </p>
                                    @error('paymentConfigs.'.$pm->id.'.account_name')
                                        <p class="label text-error text-xs">{{ $message }}</p>
                                    @enderror
                                </fieldset>
                            </div>

                            {{-- Account Numbers (Right Column: 7 cols on lg) --}}
                            <div class="lg:col-span-7">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-base-content/80 flex items-center gap-1.5">
                                        <x-heroicon-o-hashtag class="w-3.5 h-3.5 text-base-content/60" />
                                        <span>{{ __('Account Numbers') }} ({{ count($paymentConfigs[$pm->id]['account_numbers'] ?? []) }}) *</span>
                                    </label>
                                    <button type="button"
                                        wire:click="addAccountNumber({{ $pm->id }})"
                                        class="btn btn-ghost btn-xs text-primary font-semibold hover:bg-primary/10 gap-1">
                                        <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                        <span>{{ __('Add Number') }}</span>
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    @foreach ($paymentConfigs[$pm->id]['account_numbers'] ?? [''] as $idx => $accountNumber)
                                        <div class="flex items-center gap-2" wire:key="pm-{{ $pm->id }}-acc-{{ $idx }}">
                                            <span class="text-xs font-mono text-base-content/50 w-6 text-center shrink-0">
                                                #{{ $idx + 1 }}
                                            </span>
                                            <input type="text"
                                                wire:model="paymentConfigs.{{ $pm->id }}.account_numbers.{{ $idx }}"
                                                placeholder="{{ __('e.g. 09171234567 or Account #') }}"
                                                class="input input-sm font-mono w-full @error('paymentConfigs.'.$pm->id.'.account_numbers.'.$idx) input-error @enderror" />
                                            @if (count($paymentConfigs[$pm->id]['account_numbers'] ?? []) > 1)
                                                <button type="button"
                                                    wire:click="removeAccountNumber({{ $pm->id }}, {{ $idx }})"
                                                    class="btn btn-ghost btn-square btn-sm text-error/70 hover:text-error hover:bg-error/10 shrink-0"
                                                    title="{{ __('Remove this number') }}">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>
                                        @error('paymentConfigs.'.$pm->id.'.account_numbers.'.$idx)
                                            <p class="label text-error text-xs">{{ $message }}</p>
                                        @enderror
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-8">
                <div class="w-10 h-10 rounded-full bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-2">
                    <x-heroicon-o-credit-card class="w-5 h-5" />
                </div>
                <p class="text-xs font-medium text-base-content/60">{{ __('No master payment methods available in the system.') }}</p>
            </div>
        @endforelse
    </div>
</div>
