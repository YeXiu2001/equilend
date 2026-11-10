<x-app-layout>
    <x-breadcrumbs :items="[
        ['label' => __('Equilend'), 'icon' => 'heroicon-o-home'],
        ['label' => __('Dashboard'), 'icon' => 'heroicon-o-squares-2x2'],
    ]" />

    <div class="space-y-3">
        <!-- Top Welcome & Quote Bar (Inspired by Reference Photo 1) -->
        <div class="bg-base-100 rounded-xl p-4 shadow-xs border border-base-300">
            <h1 class="text-lg sm:text-xl font-bold tracking-tight text-base-content">
                Welcome back, <span class="text-primary">{{ auth()->user()->name }}</span>!
            </h1>
            <p class="text-xs sm:text-sm text-base-content/70 italic mt-0.5">
                "The secret of change is to focus all of your energy, not on fighting the old but on building the new." &mdash; <span class="font-medium text-base-content/85">Socrates</span>
            </p>
        </div>

        <!-- Quick Action / Shortcut Cards Grid (Inspired by Reference Grid) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-home class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Dashboard</span>
            </a>

            <a href="{{ route('inventory.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-archive-box class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Inventory</span>
            </a>

            <a href="#" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-clock class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Loan History</span>
            </a>

            <a href="{{ route('organization.settings') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Settings</span>
            </a>

            <a href="{{ route('user.profile') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-user class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Profile</span>
            </a>

            <a href="{{ route('users.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl bg-base-100 border border-base-300 hover:border-primary hover:shadow-sm transition-all group text-center">
                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                    <x-heroicon-o-users class="w-4 h-4" />
                </div>
                <span class="text-xs font-semibold text-base-content group-hover:text-primary">Users</span>
            </a>
        </div>

        <!-- 2 Column Information Panels (Inspired by Announcements & Helpful Links) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Announcements Card -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs border-t-4 border-t-primary">
                <div class="card-body p-4">
                    <div class="flex items-center gap-2 font-bold text-sm text-base-content border-b border-base-200 pb-3">
                        <x-heroicon-o-megaphone class="w-4 h-4 text-primary" />
                        <span>Announcements</span>
                    </div>
                    <ul class="space-y-3 pt-2 text-xs text-base-content/85">
                        <li class="flex items-start gap-2">
                            <span class="badge badge-xs badge-primary mt-1">New</span>
                            <div>
                                <span class="font-semibold">Equilend Design System Updated:</span>
                                <p class="text-base-content/70">Modernized UI styling, institutional crimson and amber palette, and quick navigation.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="badge badge-xs badge-neutral mt-1">Info</span>
                            <div>
                                <span class="font-semibold">Equipment Inventory Active:</span>
                                <p class="text-base-content/70">Check out the asset management section to track and manage loans.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Helpful Links & System Info -->
            <div class="card bg-base-100 card-border border-base-300 shadow-xs border-t-4 border-t-secondary">
                <div class="card-body p-4">
                    <div class="flex items-center gap-2 font-bold text-sm text-base-content border-b border-base-200 pb-3">
                        <x-heroicon-o-information-circle class="w-4 h-4 text-secondary" />
                        <span>System Quick Links</span>
                    </div>
                    <div class="space-y-2 pt-2 text-xs text-base-content/85">
                        <div class="flex items-center justify-between py-1.5 border-b border-base-200/60">
                            <span class="text-base-content/70">Framework Stack</span>
                            <span class="font-semibold">Laravel 13 • Livewire 4 • daisyUI 5</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-base-200/60">
                            <span class="text-base-content/70">Current User</span>
                            <span class="font-mono text-primary font-medium">{{ auth()->user()->email }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-base-content/70">Role Status</span>
                            <span class="badge badge-sm badge-secondary font-semibold">{{ ucfirst(auth()->user()->roles->first()?->name ?? 'Member') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
