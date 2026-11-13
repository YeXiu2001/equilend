<x-guest-layout>
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <h2 class="card-title border-b border-base-200 pb-3 mb-2">{{ __('Recovery your password') }}</h2>

            @if (session('status'))
                <div role="alert" class="alert alert-success mb-4">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <p class="mb-4 text-sm text-base-content/70">
                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.') }}
            </p>

            <form id="forgot-password" method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Email *</legend>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="input w-full @error('email') input-error @enderror" />
                    @error('email')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>
            </form>

            <div class="card-actions flex flex-col w-full gap-y-2 pt-4 border-t border-base-200 mt-2">
                <button type="submit" form="forgot-password" class="btn btn-primary btn-block rounded-full">
                    {{ __('Email Password Reset Link') }}
                </button>

                <span class="text-sm text-base-content/70 text-center w-full">
                    Remembered your password?
                    <a href="{{ route('login') }}" class="link link-primary font-semibold text-sm">{{ __('Back to log in') }}</a>
                </span>
            </div>
        </div>
    </div>
</x-guest-layout>
