<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', this.theme);
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        }
    }"
    x-bind:data-theme="theme"
    x-bind:class="{ 'dark': theme === 'dark' }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>

<body class="font-sans antialiased bg-base-200 text-base-content"
    x-cloak
    x-data="{
        name: @js(auth()->user()->name),
        email: @js(auth()->user()->email),
        toasts: [],
        dialog: {
            open: false,
            type: 'info',
            title: '',
            description: '',
            componentId: null,
            confirmMethod: null,
            confirmParams: null
        },
        handleAlert(detail) {
            if (detail.type === 'question') {
                this.dialog = {
                    open: true,
                    type: detail.type,
                    title: detail.title || 'Warning!',
                    description: detail.description || 'Are you sure?',
                    componentId: detail.componentId || null,
                    confirmMethod: detail.confirmMethod || null,
                    confirmParams: detail.confirmParams ?? null
                };
            } else {
                const id = Date.now() + Math.random();
                this.toasts.push({
                    id,
                    type: detail.type || 'info',
                    title: detail.title || '',
                    description: detail.description || ''
                });
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 4000);
            }
        },
        confirmDialog() {
            if (this.dialog.componentId && this.dialog.confirmMethod && window.Livewire) {
                const component = window.Livewire.find(this.dialog.componentId);
                if (component) {
                    if (this.dialog.confirmParams !== null && this.dialog.confirmParams !== undefined) {
                        component.$call(this.dialog.confirmMethod, this.dialog.confirmParams);
                    } else {
                        component.$call(this.dialog.confirmMethod);
                    }
                }
            }
            this.dialog.open = false;
        }
    }"
    x-on:name-updated.window="name = $event.detail.name"
    x-on:alert:dialog.window="handleAlert($event.detail)">

    <!-- Toast Notifications -->
    <div class="toast toast-top toast-end z-50">
        <template x-for="toast in toasts" :key="toast.id">
            <div role="alert" class="alert shadow-lg min-w-72"
                x-bind:class="{
                    'alert-success': toast.type === 'success',
                    'alert-error': toast.type === 'error',
                    'alert-warning': toast.type === 'warning',
                    'alert-info': toast.type === 'info'
                }">
                <div>
                    <h4 class="font-bold text-sm" x-text="toast.title"></h4>
                    <div class="text-xs" x-text="toast.description"></div>
                </div>
                <button type="button" class="btn btn-ghost btn-xs btn-circle" x-on:click="toasts = toasts.filter(t => t.id !== toast.id)">✕</button>
            </div>
        </template>
    </div>

    <!-- Confirmation Dialog Modal -->
    <dialog class="modal" x-bind:open="dialog.open">
        <div class="modal-box">
            <h3 class="text-lg font-bold" x-text="dialog.title"></h3>
            <p class="py-4 text-sm text-base-content/80" x-text="dialog.description"></p>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost btn-sm" x-on:click="dialog.open = false">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-error btn-sm" x-on:click="confirmDialog()">{{ __('Confirm') }}</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" x-on:click="dialog.open = false">close</button>
        </form>
    </dialog>

    <!-- App Drawer Layout -->
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col min-h-screen">
            <!-- Top Navbar -->
            <header class="navbar bg-base-100/90 backdrop-blur-md border-b border-base-300/80 px-4 h-16 min-h-16 sticky top-0 z-30">
                <div class="flex-none flex items-center gap-2">
                    <!-- Mobile Drawer Trigger -->
                    <label for="app-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost btn-sm lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-5 w-5 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>

                    <!-- Desktop Collapse Trigger -->
                    <button type="button" x-on:click="toggleSidebar()" class="btn btn-square btn-ghost btn-sm hidden lg:inline-flex text-base-content/70 hover:text-base-content" aria-label="Toggle sidebar">
                        <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                        </svg>
                        <svg x-show="sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1"></div>

                <div class="flex-none flex items-center gap-2">
                    <!-- Theme Switcher -->
                    <button type="button" class="btn btn-ghost btn-circle btn-sm text-base-content/70 hover:text-base-content" x-on:click="toggleTheme()" aria-label="Toggle theme">
                        <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                        <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                    </button>

                    <!-- User Dropdown -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2 px-2 rounded-full hover:bg-base-200">
                            <div class="avatar avatar-placeholder">
                                <div class="bg-primary text-primary-content w-7 rounded-full text-xs font-bold">
                                    <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                                </div>
                            </div>
                            <span class="font-medium text-sm hidden sm:inline" x-text="`Hello, ${name}!`"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-60">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                        <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-2 w-56 p-2 shadow-xl border border-base-300/80">
                            <li class="px-3 py-2 border-b border-base-200 mb-1 pointer-events-none">
                                <div class="flex flex-col gap-0.5 p-0">
                                    <span class="font-semibold text-xs text-base-content truncate" x-text="name"></span>
                                    <span class="text-[11px] text-base-content/60 truncate" x-text="email"></span>
                                </div>
                            </li>
                            <li>
                                <a href="{{ route('user.profile') }}" class="flex items-center gap-2.5 rounded-lg py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-70">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    <span>{{ __('Profile') }}</span>
                                </a>
                            </li>
                            <div class="divider my-1"></div>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="p-0">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 text-left px-3 py-2 text-error hover:bg-error/10 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                        </svg>
                                        <span>{{ __('Logout') }}</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>

        <x-layouts.sidebar />
    </div>

    @livewireScripts
</body>

</html>
