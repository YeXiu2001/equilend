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
                        <x-heroicon-o-bars-3 class="inline-block h-5 w-5 stroke-current" />
                    </label>

                    <!-- Desktop Collapse Trigger -->
                    <button type="button" x-on:click="toggleSidebar()" class="btn btn-square btn-ghost btn-sm hidden lg:inline-flex text-base-content/70 hover:text-base-content" aria-label="Toggle sidebar">
                        <x-heroicon-o-bars-3-bottom-left x-show="!sidebarCollapsed" class="w-5 h-5" />
                        <x-heroicon-o-bars-3 x-show="sidebarCollapsed" class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1"></div>

                <div class="flex-none flex items-center gap-2">
                    <!-- Theme Switcher -->
                    <button type="button" class="btn btn-ghost btn-circle btn-sm text-base-content/70 hover:text-base-content" x-on:click="toggleTheme()" aria-label="Toggle theme">
                        <x-heroicon-o-moon x-show="theme === 'light'" class="w-5 h-5" />
                        <x-heroicon-o-sun x-show="theme === 'dark'" class="w-5 h-5" />
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
                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5 opacity-60" />
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
                                    <x-heroicon-o-user class="w-4 h-4 opacity-70" />
                                    <span>{{ __('Profile') }}</span>
                                </a>
                            </li>
                            <div class="divider my-1"></div>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="p-0">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 text-left px-3 py-2 text-error hover:bg-error/10 rounded-lg">
                                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
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
