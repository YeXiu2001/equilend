<x-guest-layout>
    <div x-data="{ recovery: false }">
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body">
                <h2 class="card-title border-b border-base-200 pb-3 mb-2">{{ __('Two-factor authentication') }}</h2>

                <form id="two-factor-challenge" method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
                    @csrf

                    <div x-show="!recovery">
                        <p class="mb-4 text-sm text-base-content/70">
                            {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
                        </p>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">Code *</legend>
                            <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                                class="input w-full text-center tracking-widest font-mono text-lg @error('code') input-error @enderror" />
                            @error('code')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>

                    <div x-show="recovery" x-cloak>
                        <p class="mb-4 text-sm text-base-content/70">
                            {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
                        </p>

                        <fieldset class="fieldset w-full">
                            <legend class="fieldset-legend">Recovery Code *</legend>
                            <input type="text" name="recovery_code" autocomplete="one-time-code"
                                class="input w-full @error('recovery_code') input-error @enderror" />
                            @error('recovery_code')
                                <p class="label text-error text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    </div>
                </form>

                <div class="card-actions flex w-full flex-col gap-y-2 pt-4 border-t border-base-200 mt-2">
                    <button type="submit" form="two-factor-challenge" class="btn btn-primary btn-block rounded-full">
                        {{ __('Log in') }}
                    </button>

                    <span class="text-center text-sm text-base-content/70 w-full">
                        <button type="button"
                                class="cursor-pointer font-semibold text-primary hover:underline"
                                x-on:click="recovery = !recovery"
                                x-text="recovery ? '{{ __('Use an authentication code') }}' : '{{ __('Use a recovery code') }}'">
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
