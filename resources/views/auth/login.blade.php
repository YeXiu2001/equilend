<x-guest-layout>
    <div class="w-full max-w-md mx-auto my-8">
        <x-card shadowless bordered>
            <!-- Custom Header para mas makapal at maganda ang font style -->
            <x-slot:header>
                <div class="py-1  gap-y-1">
                    <h2 class="text-xl font-bold tracking-tight flex text-center text-gray-800">
                        {{ __('Welcome back!') }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('Please sign in to your account to continue.') }}
                    </p>
                </div>
            </x-slot:header>

            <!-- Alert / Session Status Section -->
            @if (session('status'))
                <div class="mb-5">
                    <x-alert :text="session('status')" color="green" class="text-sm font-medium" />
                </div>
            @endif

            <!-- Login Form Section -->
            <form id="login" method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input label="Email *"
                             type="email"
                             name="email"
                             :value="old('email', 'test@example.com')"
                             required
                             autofocus
                             autocomplete="username"
                             class="w-full text-sm" />
                </div>

                <div>
                    <x-password label="Password *"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full text-sm" />
                </div>

                <!-- Remember Me & Forgot Password Section -->
                <div class="flex items-center justify-between pt-1">
                    <x-checkbox label="Remember me" id="remember_me" name="remember" class="text-sm text-gray-600" />
                    
                    @if (Route::has('password.request'))
                        <x-link :href="route('password.request')" :text="__('Forgot your password?')" sm underline colorless class="text-gray-500 hover:text-gray-700 transition" />
                    @endif
                </div>
            </form>

            <!-- Footer Section -->
            <x-slot:footer>
                <div class="flex flex-col w-full gap-y-4 pt-2">
                    <!-- Log In Button -->
                    <x-button submit form="login" :text="__('Log in')" block round color="primary" class="font-semibold" />

                    <!-- Register Link -->
                    <p class="text-sm text-gray-600 text-center">
                        {{ __("Don't have an account?") }}
                        <x-link :href="route('register')" :text="__('Create account!')" sm bold class="text-primary hover:underline ml-1" />
                    </p>
                </div>
            </x-slot:footer>
        </x-card>
    </div>
</x-guest-layout>
