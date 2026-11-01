<x-guest-layout>
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <h2 class="card-title border-b border-base-200 pb-3 mb-2">{{ __('Create your account') }}</h2>

            <form id="register" method="POST" action="{{ route('register.store') }}" class="space-y-3">
                @csrf

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Name *</legend>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                        class="input w-full @error('name') input-error @enderror" />
                    @error('name')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Email *</legend>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                        class="input w-full @error('email') input-error @enderror" />
                    @error('email')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Password *</legend>
                    <input type="password" name="password" required autocomplete="new-password"
                        class="input w-full @error('password') input-error @enderror" />
                    @error('password')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">Confirm Password *</legend>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                        class="input w-full" />
                </fieldset>
            </form>

            <div class="card-actions flex w-full flex-col gap-y-2 pt-4 border-t border-base-200 mt-2">
                <button type="submit" form="register" class="btn btn-primary btn-block rounded-full">
                    {{ __('Register') }}
                </button>

                <span class="text-center text-sm text-base-content/70 w-full">
                    Already have an account?
                    <a href="{{ route('login') }}" class="link link-primary font-semibold text-sm">{{ __('Log in!') }}</a>
                </span>
            </div>
        </div>
    </div>
</x-guest-layout>
