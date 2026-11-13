<div>
    <form id="update-password" wire:submit="save" class="space-y-2">
        <div class="grid gap-4 sm:grid-cols-3">
            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Current Password') }} *</legend>
                <input type="password" wire:model="current_password" autocomplete="current-password" required
                    class="input w-full @error('current_password') input-error @enderror" />
                @error('current_password')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('New Password') }} *</legend>
                <input type="password" wire:model="password" autocomplete="new-password" required
                    class="input w-full @error('password') input-error @enderror" />
                @error('password')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Confirm Password') }} *</legend>
                <input type="password" wire:model="password_confirmation" autocomplete="new-password" required
                    class="input w-full" />
            </fieldset>
        </div>
    </form>

    <div class="mt-6 flex justify-end">
        <button type="submit" form="update-password" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
            <span>{{ __('Save') }}</span>
        </button>
    </div>
</div>
