<div>
    <dialog class="modal" @if ($modal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="text-lg font-bold mb-4">{{ __('Edit Payment Method') }}</h3>

            <form id="pm-update" wire:submit="save" class="space-y-4">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Payment Code') }} *</legend>
                    <input type="text" wire:model="payment_code" required
                        class="input w-full lowercase @error('payment_code') input-error @enderror" />
                    @error('payment_code')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Method Name') }} *</legend>
                    <input type="text" wire:model="name" required
                        class="input w-full @error('name') input-error @enderror" />
                    @error('name')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Description') }}</legend>
                    <textarea wire:model="description" rows="2"
                        class="textarea w-full @error('description') textarea-error @enderror"></textarea>
                    @error('description')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex items-center gap-3 pt-1">
                    <input type="checkbox" id="pm-update-is-active" wire:model="is_active" class="toggle toggle-primary" />
                    <label for="pm-update-is-active" class="cursor-pointer text-sm font-medium">
                        {{ __('System-wide Active Status') }}
                    </label>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" wire:click="$set('modal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="pm-update" class="btn btn-primary px-6" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Save Changes') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('modal', false)">close</button>
        </form>
    </dialog>
</div>
