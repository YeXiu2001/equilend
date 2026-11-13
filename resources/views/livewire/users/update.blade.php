<div>
    <dialog class="modal" @if ($modal) open @endif>
        <div class="modal-box">
            <h3 class="text-lg font-bold mb-4">{{ __('Update User: #:id', ['id' => $user?->id]) }}</h3>

            <form id="user-update-{{ $user?->id }}" wire:submit="save" class="space-y-4">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Name') }} *</legend>
                    <input type="text" x-ref="name" wire:model="user.name" required
                        class="input w-full @error('user.name') input-error @enderror" />
                    @error('user.name')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Email') }} *</legend>
                    <input type="email" wire:model="user.email" required
                        class="input w-full @error('user.email') input-error @enderror" />
                    @error('user.email')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Password') }} *</legend>
                    <input type="password" wire:model="password"
                        class="input w-full @error('password') input-error @enderror" />
                    <p class="label text-xs text-base-content/60">The password will only be updated if you set the value of this field</p>
                    @error('password')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Confirm Password') }} *</legend>
                    <input type="password" id="confirm-password-{{ $user?->id }}" wire:model="password_confirmation"
                        class="input w-full" />
                </fieldset>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost rounded-full" wire:click="$set('modal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="user-update-{{ $user?->id }}" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Save') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('modal', false)">close</button>
        </form>
    </dialog>
</div>
