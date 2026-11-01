<div>
    <button type="button" class="btn btn-primary rounded-full" wire:click="$toggle('modal')">
        {{ __('Create New User') }}
    </button>

    <dialog class="modal" @if ($modal) open @endif>
        <div class="modal-box">
            <h3 class="text-lg font-bold mb-4">{{ __('Create New User') }}</h3>

            <form id="user-create" wire:submit="save" class="space-y-4">
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
                    <input type="password" wire:model="password" required
                        class="input w-full @error('password') input-error @enderror" />
                    @error('password')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Confirm Password') }} *</legend>
                    <input type="password" wire:model="password_confirmation" required
                        class="input w-full" />
                </fieldset>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost rounded-full" wire:click="$set('modal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="user-create" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="save">
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
