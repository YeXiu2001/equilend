<div class="space-y-6">
    @if ($this->enabled)
        <div role="alert" class="alert alert-success">
            <x-heroicon-o-shield-check class="w-5 h-5" />
            <span>{{ __('Two-factor authentication is enabled on your account.') }}</span>
        </div>

        <form id="disable-two-factor" wire:submit="disable" class="space-y-2">
            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Current Password') }} *</legend>
                <input type="password" wire:model="current_password" autocomplete="current-password" required
                    class="input w-full @error('current_password') input-error @enderror" />
                @error('current_password')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>
        </form>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <button type="button" class="btn btn-primary btn-sm rounded-full"
                x-on:click="document.getElementById('recovery-codes')?.showModal()">
                {{ __('Show Recovery Codes') }}
            </button>

            <button type="submit" form="disable-two-factor" class="btn btn-error rounded-full" wire:loading.attr="disabled" wire:target="disable">
                <span wire:loading wire:target="disable" class="loading loading-spinner loading-xs"></span>
                <span>{{ __('Disable') }}</span>
            </button>
        </div>
    @elseif ($this->pending)
        <div role="alert" class="alert alert-info">
            <x-heroicon-o-qr-code class="w-5 h-5" />
            <span>{{ __('Scan the QR code with your authenticator app, then enter the 6-digit code to finish enabling two-factor authentication.') }}</span>
        </div>

        <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
            <div class="p-4 bg-white rounded-box border border-base-300 shrink-0">
                {!! $this->user->twoFactorQrCodeSvg() !!}
            </div>

            <div class="w-full space-y-4">
                <fieldset class="fieldset w-full" x-data="{ copied: false, showSecret: false }">
                    <legend class="fieldset-legend">{{ __('Setup key') }}</legend>
                    <div class="join w-full">
                        <input x-bind:type="showSecret ? 'text' : 'password'" value="{{ $this->setupKey }}" readonly class="input join-item w-full font-mono text-sm" />
                        <button type="button" class="btn btn-neutral btn-outline join-item" x-on:click="showSecret = !showSecret" x-text="showSecret ? 'Hide' : 'Show'"></button>
                        <button type="button" class="btn btn-primary join-item"
                            x-on:click="navigator.clipboard.writeText(@js($this->setupKey)); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                </fieldset>

                <form id="confirm-two-factor" wire:submit="confirm" class="space-y-2">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Authentication Code') }} *</legend>
                        <input type="text" wire:model="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                            class="input w-full tracking-widest font-mono @error('code') input-error @enderror" />
                        @error('code')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </form>
            </div>
        </div>

        <div class="flex justify-end items-center gap-2">
            <button type="button" class="btn btn-error btn-sm rounded-full" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel">
                <span wire:loading wire:target="cancel" class="loading loading-spinner loading-xs"></span>
                <span>{{ __('Cancel') }}</span>
            </button>

            <button type="submit" form="confirm-two-factor" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="confirm">
                <span wire:loading wire:target="confirm" class="loading loading-spinner loading-xs"></span>
                <span>{{ __('Confirm') }}</span>
            </button>
        </div>
    @else
        <p class="text-sm text-base-content/80">
            {{ __('When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication. You can retrieve this token from your phone\'s Google Authenticator application.') }}
        </p>

        <form id="enable-two-factor" wire:submit="enable" class="space-y-2">
            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Current Password') }} *</legend>
                <input type="password" wire:model="current_password" autocomplete="current-password" required
                    class="input w-full @error('current_password') input-error @enderror" />
                @error('current_password')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>
        </form>

        <div class="flex justify-end">
            <button type="submit" form="enable-two-factor" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="enable">
                <span wire:loading wire:target="enable" class="loading loading-spinner loading-xs"></span>
                <span>{{ __('Enable') }}</span>
            </button>
        </div>
    @endif

    @if ($this->enabled || $this->pending)
        <livewire:user.profile.recovery-codes />
    @endif
</div>
