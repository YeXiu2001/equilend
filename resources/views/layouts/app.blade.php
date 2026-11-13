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

    <title>{{ config('app.name', 'Equilend') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>

<body class="font-sans antialiased bg-base-200 text-base-content min-h-screen lg:h-dvh lg:overflow-hidden flex flex-col"
    x-cloak
    x-data="{
        name: @js(auth()->user()?->name),
        email: @js(auth()->user()?->email),
        role: @js(auth()->user()?->roles->first()?->name ? ucfirst(auth()->user()->roles->first()->name) : 'Member'),
        picDir: @js(auth()->user()?->pic_url),
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
    x-on:name-updated.window="name = $event.detail.name; if ($event.detail.pic_dir !== undefined) { picDir = $event.detail.pic_dir; }"
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

    <!-- Top Navbar (Institutional Crimson Maroon Bar) -->
    <header class="navbar bg-primary text-primary-content h-16 min-h-16 shrink-0 sticky top-0 z-30 px-3 sm:px-4 shadow-sm border-b border-black/10 select-none">
        <div class="flex-none flex items-center gap-2 sm:gap-3">
            <!-- Brand Badge (Replaces reference 'myIIT' badge with Equilend) -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 bg-white text-primary px-2.5 py-1 rounded-md font-bold shadow-xs hover:bg-slate-100 transition-colors">
                <img src="{{ asset('/assets/images/tsui.png') }}" class="w-5 h-5 object-contain" alt="{{ config('app.name', 'Equilend') }}" />
                <span class="text-xs sm:text-sm font-extrabold tracking-wide uppercase">{{ config('app.name', 'Equilend') }}</span>
            </a>

            <!-- Mobile Drawer Trigger -->
            <label for="app-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost btn-sm lg:hidden text-white hover:bg-white/10">
                <x-heroicon-o-bars-3 class="w-5 h-5 stroke-current" />
            </label>

            <!-- Desktop Collapse Trigger -->
            <button type="button" x-on:click="toggleSidebar()" class="btn btn-square btn-ghost btn-sm hidden lg:inline-flex text-white hover:bg-white/10" aria-label="Toggle sidebar">
                <x-heroicon-o-bars-3-bottom-left x-show="!sidebarCollapsed" class="w-5 h-5" />
                <x-heroicon-o-bars-3 x-show="sidebarCollapsed" class="w-5 h-5" />
            </button>
        </div>

        <div class="flex-1"></div>

        <!-- Right Header Items: Strictly Notification Bell and User Name Profile Trigger -->
        <div class="flex-none flex items-center gap-2 sm:gap-3">
            <!-- Notification Bell Icon -->
            @auth
                <livewire:notifications.bell />
            @endauth

            <!-- User Profile Dropdown (Mirroring Reference Photo 2) -->
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2 px-2 text-white hover:bg-white/10 rounded-lg">
                    <div class="avatar" :class="{ 'avatar-placeholder': !picDir }">
                        <template x-if="picDir">
                            <div class="w-7 h-7 rounded-full border border-white/30 overflow-hidden">
                                <img :src="picDir" :alt="name" class="w-full h-full object-cover" />
                            </div>
                        </template>
                        <template x-if="!picDir">
                            <div class="bg-white/20 text-white border border-white/30 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center">
                                <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                            </div>
                        </template>
                    </div>
                    <span class="font-semibold text-xs sm:text-sm tracking-wide uppercase text-white truncate max-w-48 hidden sm:inline" x-text="name"></span>
                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5 opacity-80" />
                </div>

                <!-- Dropdown Card with Golden Yellow Header -->
                <div tabindex="0" class="dropdown-content bg-base-100 rounded-2xl z-50 mt-2 w-80 shadow-2xl border border-base-300 overflow-hidden p-0 text-base-content">
                    <!-- Golden Yellow Header Banner -->
                    <div class="bg-[#f4c430] dark:bg-amber-500 text-slate-900 p-6 flex flex-col items-center justify-center text-center relative select-none">
                        <div class="avatar" :class="{ 'avatar-placeholder': !picDir }">
                            <template x-if="picDir">
                                <div class="w-20 h-20 rounded-full bg-white shadow-md border-2 border-white/90 ring-4 ring-amber-300/60 overflow-hidden">
                                    <img :src="picDir" :alt="name" class="w-full h-full object-cover" />
                                </div>
                            </template>
                            <template x-if="!picDir">
                                <div class="w-20 h-20 rounded-full bg-white shadow-md flex items-center justify-center text-amber-700 font-bold text-2xl border-2 border-white/90 ring-4 ring-amber-300/60">
                                    <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                                </div>
                            </template>
                        </div>
                        <h3 class="font-bold text-sm tracking-wide uppercase text-slate-900 mt-3 truncate max-w-full px-2" x-text="name"></h3>
                        <p class="text-xs font-semibold text-slate-800/80 mt-0.5" x-text="role"></p>
                    </div>

                    <!-- Lower Card Content -->
                    <div class="p-4 space-y-3 bg-base-100">
                        <!-- Email Container with Mail Icon -->
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-base-200 border border-base-300 text-xs text-base-content/85 select-all overflow-hidden">
                            <x-heroicon-o-envelope class="w-4 h-4 text-primary shrink-0" />
                            <span class="truncate font-mono" x-text="email"></span>
                        </div>

                        <!-- Profile & Settings Action Buttons -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <a href="{{ route('user.profile') }}" class="btn btn-outline btn-sm font-medium gap-1.5 border-base-300 hover:border-primary hover:bg-primary hover:text-white">
                                <x-heroicon-o-user class="w-3.5 h-3.5" />
                                <span>{{ __('Profile') }}</span>
                            </a>
                            <a href="{{ route('organization.settings') }}" class="btn btn-outline btn-sm font-medium gap-1.5 border-base-300 hover:border-primary hover:bg-primary hover:text-white">
                                <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5" />
                                <span>{{ __('Settings') }}</span>
                            </a>
                        </div>

                        <!-- Theme Switcher & Logout Footer -->
                        <div class="flex items-center justify-between pt-2 border-t border-base-200">
                            <button type="button" x-on:click="toggleTheme()" class="btn btn-ghost btn-xs gap-1.5 text-base-content/70 hover:text-base-content">
                                <x-heroicon-o-moon x-show="theme === 'light'" class="w-3.5 h-3.5" />
                                <x-heroicon-o-sun x-show="theme === 'dark'" class="w-3.5 h-3.5" />
                                <span x-text="theme === 'dark' ? 'Light mode' : 'Dark mode'"></span>
                            </button>

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-error btn-outline btn-xs font-medium gap-1">
                                    <x-heroicon-o-arrow-right-on-rectangle class="w-3.5 h-3.5" />
                                    <span>{{ __('Logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- App Drawer Layout -->
    <div class="drawer lg:drawer-open flex-1 lg:min-h-0 lg:grid-rows-[minmax(0,1fr)]">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col flex-1 bg-base-200 min-w-0 lg:min-h-0 lg:overflow-y-auto">
            <!-- Main Content Canvas -->
            <main class="flex-1 p-1 sm:p-2 w-full min-w-0">
                {{ $slot }}
            </main>
        </div>

        <x-layouts.sidebar />
    </div>

    @livewireScripts
</body>

</html>
