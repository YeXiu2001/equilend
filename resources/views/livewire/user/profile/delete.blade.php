<div>
    <button type="button" class="btn btn-error btn-sm rounded-full" wire:click="$toggle('modal')">
        {{ __('Delete Profile') }}
    </button>

    <dialog id="delete-profile" class="modal" @if ($modal) open @endif>
        <div class="modal-box">
            <h3 class="text-lg font-bold mb-2">{{ __('Delete Profile') }}</h3>

            <form id="delete-profile-form" wire:submit="confirm" class="space-y-4">
                <p class="text-sm text-base-content/70">
                    {{ __('Are you sure you want to delete your profile? This action cannot be undone.') }}
                </p>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Current Password') }} *</legend>
                    <input type="password" id="password" wire:model="password" autocomplete="current-password" required
                        class="input w-full @error('password') input-error @enderror" />
                    @error('password')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm rounded-full" wire:click="$set('modal', false)">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" form="delete-profile-form" class="btn btn-error btn-sm rounded-full" wire:loading.attr="disabled" wire:target="confirm,delete">
                    <span wire:loading wire:target="confirm,delete" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Delete') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('modal', false)">close</button>
        </form>
    </dialog>
</div>
