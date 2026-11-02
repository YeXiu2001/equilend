<div>
    {{-- Uniform Breadcrumb Card --}}
    <div class="card bg-base-100 card-border border-base-300 shadow-xs mb-6">
        <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between py-4 gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ __('Organization Settings') }}</span>
                        </li>
                        <li>{{ $organization ? $organization->name : __('Settings') }}</li>
                    </ul>
                </div>

                {{-- Superadmin Organization Switcher --}}
                @if (auth()->user()->hasRole('superadmin') && $this->allOrganizations->count() > 1)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-base-content/60">{{ __('Switch Org:') }}</span>
                        <select wire:change="changeOrganization($event.target.value)" class="select select-xs select-bordered">
                            @foreach ($this->allOrganizations as $org)
                                <option value="{{ $org->id }}" @selected($organization && $organization->id === $org->id)>
                                    {{ $org->name }} ({{ $org->code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            @if ($organization)
                <div>
                    <button type="submit" form="org-settings-form" class="btn btn-primary btn-sm px-6 font-semibold" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                        <svg wire:loading.remove wire:target="save" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if (! $organization)
        {{-- Empty State when user has no assigned organization --}}
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body text-center py-12">
                <div class="w-16 h-16 rounded-full bg-warning/10 text-warning mx-auto flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-base-content">{{ __('No Organization Assigned') }}</h3>
                <p class="text-sm text-base-content/60 max-w-md mx-auto mt-1">
                    {{ __('You are not currently assigned to any organization. Please contact your system administrator to get assigned.') }}
                </p>
            </div>
        </div>
    @else
        <form id="org-settings-form" wire:submit="save" class="space-y-6">
            {{-- Section 1: Organization Profile Details --}}
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body">
                    <div class="border-b border-base-200 pb-3 mb-4">
                        <h3 class="text-base font-bold text-base-content">{{ __('Organization Details') }}</h3>
                        <p class="text-xs text-base-content/60">{{ __('Manage branch identity, official contact info, and address.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <fieldset class="fieldset w-full sm:col-span-2">
                            <legend class="fieldset-legend">{{ __('Organization Name') }} *</legend>
                            <input type="text" wire:model="name" required
                                class="input w-full @error('name') input-error @enderror" />
                            @error('name')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">{{ __('Code / Identifier') }}</legend>
                            <input type="text" wire:model="code"
                                class="input w-full uppercase @error('code') input-error @enderror" />
                            @error('code')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">{{ __('Email Address') }}</legend>
                            <input type="email" wire:model="email"
                                class="input w-full @error('email') input-error @enderror" />
                            @error('email')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">{{ __('Phone / Contact Number') }}</legend>
                            <input type="text" wire:model="phone"
                                class="input w-full @error('phone') input-error @enderror" />
                            @error('phone')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full sm:col-span-2 lg:col-span-3">
                            <legend class="fieldset-legend">{{ __('Physical Address') }}</legend>
                            <input type="text" wire:model="address"
                                class="input w-full @error('address') input-error @enderror" />
                            @error('address')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset w-full sm:col-span-2 lg:col-span-3">
                            <legend class="fieldset-legend">{{ __('Description / Notes') }}</legend>
                            <textarea wire:model="description" rows="2"
                                class="textarea w-full @error('description') textarea-error @enderror"></textarea>
                            @error('description')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>
                </div>
            </div>

            {{-- Section 2: Payment Methods Configuration --}}
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body">
                    <div class="border-b border-base-200 pb-3 mb-4">
                        <h3 class="text-base font-bold text-base-content">{{ __('Payment Methods Configuration') }}</h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Enable payment channels (e.g. GCash, Maya, GoTyme). Each channel shares a single official account name with multiple account numbers.') }}
                        </p>
                    </div>

                    <div class="space-y-4">
                        @forelse ($this->availablePaymentMethods as $pm)
                            @php
                                $isEnabled = !empty($paymentConfigs[$pm->id]['is_enabled']);
                            @endphp
                            <div class="border border-base-300/80 rounded-2xl p-4 transition-all duration-200 @if($isEnabled) bg-base-200/30 @else bg-base-100 opacity-90 @endif"
                                wire:key="pm-config-{{ $pm->id }}">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-base-200/60">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm @if($isEnabled) bg-primary/10 text-primary border border-primary/20 @else bg-base-200 text-base-content/60 @endif">
                                            {{ strtoupper(substr($pm->payment_code, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-sm text-base-content">{{ $pm->name }}</h4>
                                                <span class="badge badge-xs font-mono">{{ $pm->payment_code }}</span>
                                            </div>
                                            <p class="text-xs text-base-content/60">{{ $pm->description ?? __('Digital wallet / Bank transfer') }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 self-end sm:self-auto">
                                        <span class="text-xs font-semibold @if($isEnabled) text-success @else text-base-content/50 @endif">
                                            {{ $isEnabled ? __('Enabled') : __('Disabled') }}
                                        </span>
                                        <input type="checkbox"
                                            wire:model.live="paymentConfigs.{{ $pm->id }}.is_enabled"
                                            class="toggle toggle-primary toggle-sm" />
                                    </div>
                                </div>

                                @if ($isEnabled)
                                    <div class="pt-4 space-y-4">
                                        {{-- Single Account Name --}}
                                        <fieldset class="fieldset w-full max-w-lg">
                                            <legend class="fieldset-legend">{{ __('Account Name') }} *</legend>
                                            <input type="text"
                                                wire:model="paymentConfigs.{{ $pm->id }}.account_name"
                                                placeholder="{{ __('e.g. EquiLend Branch Inc.') }}"
                                                class="input w-full @error('paymentConfigs.'.$pm->id.'.account_name') input-error @enderror" />
                                            @error('paymentConfigs.'.$pm->id.'.account_name')
                                                <p class="label text-error text-xs">{{ $message }}</p>
                                            @enderror
                                        </fieldset>

                                        {{-- Dynamic Multiple Account Numbers --}}
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-xs font-semibold text-base-content/80">
                                                    {{ __('Account Numbers') }} ({{ count($paymentConfigs[$pm->id]['account_numbers'] ?? []) }})
                                                </label>
                                                <button type="button"
                                                    wire:click="addAccountNumber({{ $pm->id }})"
                                                    class="btn btn-outline btn-xs gap-1 rounded-lg">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    <span>{{ __('Add Number') }}</span>
                                                </button>
                                            </div>

                                            <div class="space-y-2 max-w-lg">
                                                @foreach ($paymentConfigs[$pm->id]['account_numbers'] ?? [''] as $idx => $accountNumber)
                                                    <div class="flex items-center gap-2" wire:key="pm-{{ $pm->id }}-acc-{{ $idx }}">
                                                        <input type="text"
                                                            wire:model="paymentConfigs.{{ $pm->id }}.account_numbers.{{ $idx }}"
                                                            placeholder="{{ __('e.g. 09171234567') }}"
                                                            class="input input-sm w-full font-mono @error('paymentConfigs.'.$pm->id.'.account_numbers.'.$idx) input-error @enderror" />

                                                        @if (count($paymentConfigs[$pm->id]['account_numbers'] ?? []) > 1)
                                                            <button type="button"
                                                                wire:click="removeAccountNumber({{ $pm->id }}, {{ $idx }})"
                                                                class="btn btn-ghost btn-circle btn-xs text-error hover:bg-error/10 shrink-0"
                                                                title="{{ __('Remove this number') }}">
                                                                ✕
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
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-base-content/60 py-4 text-center">
                                {{ __('No master payment methods configured in the system. Contact superadmin.') }}
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-6 pt-4 border-t border-base-200 flex justify-end">
                        <button type="submit" class="btn btn-primary px-8" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                            <span>{{ __('Save Changes') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
