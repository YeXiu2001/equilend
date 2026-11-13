<x-guest-layout>
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <h2 class="card-title border-b border-base-200 pb-3 mb-2">{{ __('Reset password') }}</h2>

            <form id="reset-password" method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Email *</legend>
                    <input type="email" name="email" value="{{ old('email', $request->email) }}" readonly
                        class="input w-full @error('email') input-error @enderror" />
                    @error('email')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Password') }} *</legend>
                    <input type="password" name="password" required autocomplete="new-password"
                        class="input w-full @error('password') input-error @enderror" />
                    @error('password')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Confirm Password') }} *</legend>
                    <input type="password" id="confirm_password" name="password_confirmation" required autocomplete="new-password"
                        class="input w-full" />
                </fieldset>
            </form>

            <div class="card-actions pt-4 border-t border-base-200 mt-2">
                <button type="submit" form="reset-password" class="btn btn-primary btn-block rounded-full">
                    {{ __('Reset Password') }}
                </button>
            </div>
        </div>
    </div>
</x-guest-layout>
