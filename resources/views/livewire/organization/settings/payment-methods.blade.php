<div class="card bg-base-100 card-border border-base-300 shadow-xs">
    <div class="card-body p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-2 border-b border-base-200 gap-2">
            <div>
                <h3 class="text-base font-bold text-base-content flex items-center gap-2">
                    <x-heroicon-o-credit-card class="w-5 h-5 text-primary" />
                    <span>{{ __('Payment Channels & Receiving Accounts') }}</span>
                </h3>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Enable payment channels and configure receiving account names and numbers for disbursements and repayments.') }}
                </p>
            </div>
            <div class="text-xs text-base-content/60 shrink-0">
                <span class="font-semibold text-base-content">{{ collect($paymentConfigs)->where('is_enabled', true)->count() }}</span> of {{ $this->availablePaymentMethods->count() }} enabled
            </div>
        </div>

        <div class="divide-y divide-base-200">
            @forelse ($this->availablePaymentMethods as $pm)
                @php
                    $isEnabled = ! empty($paymentConfigs[$pm->id]['is_enabled']);
                @endphp
                <div class="py-4 first:pt-2 last:pb-2" wire:key="pm-row-{{ $pm->id }}">
                    {{-- Row Header --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-sm text-base-content">{{ $pm->name }}</span>
                                    <span class="font-mono text-xs text-base-content/60">({{ $pm->payment_code }})</span>
                                    @if ($isEnabled)
                                        <span class="badge badge-success badge-sm text-success-content font-medium">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm text-base-content/60 font-medium">{{ __('Disabled') }}</span>
                                    @endif
                                </div>
                                @if ($pm->description)
                                    <p class="text-xs text-base-content/50 mt-0.5 truncate">{{ $pm->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <input type="checkbox"
                                wire:model.live="paymentConfigs.{{ $pm->id }}.is_enabled"
                                class="toggle toggle-primary toggle-sm cursor-pointer"
                                aria-label="{{ __('Toggle :name', ['name' => $pm->name]) }}" />
                        </div>
                    </div>

                    {{-- Expanded fields when enabled --}}
                    @if ($isEnabled)
                        <div class="mt-3.5 p-4 rounded-xl bg-base-200/40 border border-base-200">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                                {{-- Account Name (Left Column: 5 cols on lg) --}}
                                <div class="lg:col-span-5">
                                    <fieldset class="fieldset w-full">
                                        <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/70 flex items-center gap-1.5">
                                            <x-heroicon-o-identification class="w-3.5 h-3.5" />
                                            <span>{{ __('Account Name') }} *</span>
                                        </legend>
                                        <input type="text"
                                            wire:model="paymentConfigs.{{ $pm->id }}.account_name"
                                            placeholder="{{ __('e.g. EquiLend Lending Inc.') }}"
                                            class="input input-sm w-full rounded-lg @error('paymentConfigs.'.$pm->id.'.account_name') input-error @enderror" />
                                        <p class="text-[11px] text-base-content/50 mt-1">
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
                                        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/70 flex items-center gap-1.5">
                                            <x-heroicon-o-hashtag class="w-3.5 h-3.5" />
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
                                                    class="input input-sm font-mono w-full rounded-lg @error('paymentConfigs.'.$pm->id.'.account_numbers.'.$idx) input-error @enderror" />
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
                <div class="text-center py-8 text-base-content/50">
                    <p class="text-sm">{{ __('No master payment methods available in the system.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
