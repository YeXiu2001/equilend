<div>
    <form id="update-profile" wire:submit="save" class="space-y-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Name') }} *</legend>
                <input type="text" wire:model="user.name" required
                    class="input w-full @error('user.name') input-error @enderror" />
                @error('user.name')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Email') }}</legend>
                <input type="email" value="{{ $user->email }}" disabled class="input w-full" />
            </fieldset>
        </div>
    </form>

    <div class="mt-6 flex items-center justify-between">
        <livewire:user.profile.delete />

        <button type="submit" form="update-profile" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
            <span>{{ __('Save') }}</span>
        </button>
    </div>
</div>
