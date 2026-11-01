<div>
    <dialog id="recovery-codes" class="modal" wire:ignore.self>
        <div class="modal-box">
            <h3 class="text-lg font-bold mb-2">{{ __('Recovery Codes') }}</h3>

            <p class="mb-4 text-sm text-base-content/70">
                {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your authenticator device is lost.') }}
            </p>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($this->recoveryCodes as $code)
                    <kbd class="kbd kbd-md font-mono py-2">
                        {{ $code }}
                    </kbd>
                @endforeach
            </div>

            <div class="modal-action flex justify-between items-center w-full">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-full" wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate">
                        <span wire:loading wire:target="regenerate" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Refresh Codes') }}</span>
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm rounded-full" wire:click="download" wire:loading.attr="disabled" wire:target="download">
                        <span wire:loading wire:target="download" class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Download Codes') }}</span>
                    </button>
                </div>

                <button type="button" class="btn btn-ghost rounded-full"
                    x-on:click="document.getElementById('recovery-codes')?.close()">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</div>
