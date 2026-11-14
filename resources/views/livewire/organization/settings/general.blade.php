<div>
    <div class="border-b border-base-200 pb-3 mb-5">
        <h3 class="text-sm font-bold text-base-content flex items-center gap-2">
            <x-heroicon-o-building-office class="w-4 h-4 text-primary shrink-0" />
            <span>{{ __('Organization Profile') }}</span>
        </h3>
        <p class="text-xs text-base-content/60 mt-0.5">
            {{ __('Basic information and contact details for this organization.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <fieldset class="fieldset w-full md:col-span-8">
            <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                <x-heroicon-o-identification class="w-3.5 h-3.5 text-base-content/60" />
                <span>{{ __('Organization Name') }} *</span>
            </legend>
            <input type="text" wire:model="name" required placeholder="{{ __('e.g. College of Computer Studies') }}"
                class="input w-full @error('name') input-error @enderror" />
            @error('name')
                <p class="label text-error text-xs">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset w-full md:col-span-4">
            <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                <x-heroicon-o-hashtag class="w-3.5 h-3.5 text-base-content/60" />
                <span>{{ __('Code / Identifier') }}</span>
            </legend>
            <input type="text" wire:model="code" placeholder="{{ __('e.g. CCS') }}"
                class="input w-full uppercase font-mono @error('code') input-error @enderror" />
            @error('code')
                <p class="label text-error text-xs">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset w-full md:col-span-6">
            <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                <x-heroicon-o-envelope class="w-3.5 h-3.5 text-base-content/60" />
                <span>{{ __('Email Address') }}</span>
            </legend>
            <input type="email" wire:model="email" placeholder="{{ __('ccs@equilend.test') }}"
                class="input w-full @error('email') input-error @enderror" />
            @error('email')
                <p class="label text-error text-xs">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset w-full md:col-span-6">
            <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                <x-heroicon-o-phone class="w-3.5 h-3.5 text-base-content/60" />
                <span>{{ __('Phone Number') }}</span>
            </legend>
            <input type="text" wire:model="phone" placeholder="{{ __('09171234567') }}"
                class="input w-full @error('phone') input-error @enderror" />
            @error('phone')
                <p class="label text-error text-xs">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset w-full md:col-span-12">
            <legend class="fieldset-legend text-xs font-semibold flex items-center gap-1.5">
                <x-heroicon-o-document-text class="w-3.5 h-3.5 text-base-content/60" />
                <span>{{ __('Description / Notes') }}</span>
            </legend>
            <textarea wire:model="description" rows="3" placeholder="{{ __('Add details, office hours, or operating notes...') }}"
                class="textarea w-full @error('description') textarea-error @enderror"></textarea>
            @error('description')
                <p class="label text-error text-xs">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>
</div>
