<x-guest-layout>
    <div class="w-full max-w-md mx-auto my-8">
        <div class="card bg-base-100 card-border border-base-300 shadow-xs">
            <div class="card-body">
                <div class="py-1 gap-y-1 border-b border-base-200 pb-4 mb-2">
                    <h2 class="text-xl font-bold tracking-tight text-base-content">
                        Welcome back!
                    </h2>
                    <p class="text-xs text-base-content/70 mt-1">
                        Please sign in to your account to continue.
                    </p>
                </div>

                <!-- Login Form Section -->
                <form id="login" method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">Email *</legend>
                        <input type="email" name="email" value="{{ old('email', 'test@example.com') }}" required autofocus
                            autocomplete="username" class="input w-full text-sm @error('email') input-error @enderror" />
                        @error('email')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">Password *</legend>
                        <input type="password" name="password" required autocomplete="current-password"
                            class="input w-full text-sm @error('password') input-error @enderror" />
                        @error('password')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <!-- Remember Me & Forgot Password Section -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember_me" class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" id="remember_me" name="remember" class="checkbox checkbox-primary checkbox-sm" />
                            <span class="text-sm text-base-content/80">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="link link-hover text-xs text-base-content/70">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>
                </form>

                <!-- Footer Section -->
                <div class="card-actions flex-col w-full gap-y-4 pt-4 border-t border-base-200 mt-2">
                    <!-- Log In Button -->
                    <button type="submit" form="login" class="btn btn-primary btn-block rounded-full font-semibold">
                        Log in
                    </button>

                    <!-- Register Link -->
                    <p class="text-sm text-base-content/70 text-center w-full">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="link link-primary font-semibold hover:underline ml-1">
                            Create account!
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
