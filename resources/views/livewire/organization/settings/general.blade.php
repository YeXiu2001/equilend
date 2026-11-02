<div class="card bg-base-100 card-border border-base-300 shadow-xs">
    <div class="card-body p-6">
        <div class="border-b border-base-200 pb-4 mb-6">
            <h3 class="text-base font-bold text-base-content flex items-center gap-2">
                <x-heroicon-o-building-office class="w-5 h-5 text-primary" />
                <span>{{ __('Organization Profile') }}</span>
            </h3>
            <p class="text-xs text-base-content/60 mt-0.5">
                {{ __('Basic information and contact details for this organization.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <fieldset class="fieldset w-full md:col-span-2">
                <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <x-heroicon-o-identification class="w-3.5 h-3.5" />
                    <span>{{ __('Organization Name') }} *</span>
                </legend>
                <input type="text" wire:model="name" required placeholder="{{ __('e.g. EquiLend Makati Branch') }}"
                    class="input w-full rounded-xl @error('name') input-error @enderror" />
                @error('name')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <x-heroicon-o-hashtag class="w-3.5 h-3.5" />
                    <span>{{ __('Code / Identifier') }}</span>
                </legend>
                <input type="text" wire:model="code" placeholder="{{ __('e.g. MKT') }}"
                    class="input w-full rounded-xl uppercase font-mono @error('code') input-error @enderror" />
                @error('code')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <x-heroicon-o-envelope class="w-3.5 h-3.5" />
                    <span>{{ __('Email Address') }}</span>
                </legend>
                <input type="email" wire:model="email" placeholder="{{ __('makati@equilend.test') }}"
                    class="input w-full rounded-xl @error('email') input-error @enderror" />
                @error('email')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full md:col-span-2">
                <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                    <span>{{ __('Phone Number') }}</span>
                </legend>
                <input type="text" wire:model="phone" placeholder="{{ __('09171234567') }}"
                    class="input w-full rounded-xl @error('phone') input-error @enderror" />
                @error('phone')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full md:col-span-2">
                <legend class="fieldset-legend text-xs font-semibold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <x-heroicon-o-document-text class="w-3.5 h-3.5" />
                    <span>{{ __('Description / Notes') }}</span>
                </legend>
                <textarea wire:model="description" rows="3" placeholder="{{ __('Add details, branch coverage, or operating notes...') }}"
                    class="textarea w-full rounded-xl @error('description') textarea-error @enderror"></textarea>
                @error('description')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>
        </div>
    </div>
</div>
