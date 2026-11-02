@php
    $navGroups = [
        [
            'title' => 'Main Menu',
            'items' => [
                [
                    'label'  => 'Dashboard',
                    'href'   => route('dashboard'),
                    'active' => request()->routeIs('dashboard'),
                    'icon'   => 'heroicon-o-home',
                ],
            ],
        ],
        [
            'title' => 'Asset Management',
            'items' => [
                [
                    'label'  => 'Inventory',
                    'href'   => route('inventory.index'),
                    'active' => request()->routeIs('inventory.*'),
                    'icon'   => 'heroicon-o-archive-box',
                ],
            ],
        ],
        [
            'title' => 'Transactions',
            'items' => [
                [
                    'label'  => 'Loan History',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-clock',
                ],
            ],
        ],
        [
            'title' => 'Organization Settings',
            'items' => [
                [
                    'label'  => 'Organization Settings',
                    'href'   => route('organization.settings'),
                    'active' => request()->routeIs('organization.settings'),
                    'icon'   => 'heroicon-o-cog-6-tooth',
                ],
            ],
        ],
        [
            'title' => 'Developer',
            'items' => [
                [
                    'label'  => 'Organization',
                    'href'   => route('organization.index'),
                    'active' => request()->routeIs('organization.index'),
                    'icon'   => 'heroicon-o-building-office',
                ],
                [
                    'label'  => 'Payment Methods',
                    'href'   => route('payment-methods.index'),
                    'active' => request()->routeIs('payment-methods.*'),
                    'icon'   => 'heroicon-o-credit-card',
                ],
                [
                    'label'  => 'Users',
                    'href'   => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                    'icon'   => 'heroicon-o-users',
                ],
                [
                    'label'  => 'Roles and Permissions',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-shield-check',
                ],
                [
                    'label'  => 'Starter Page',
                    'href'   => route('starter-page'),
                    'active' => request()->routeIs('starter-page'),
                    'icon'   => 'heroicon-o-document-text',
                ],
            ],
        ],
    ];

    if (! auth()->user()?->hasRole('superadmin')) {
        $navGroups = array_values(array_filter($navGroups, fn ($group) => $group['title'] !== 'Developer'));
    }
@endphp

<div class="drawer-side z-40">
    <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

    <aside class="bg-base-100 border-r border-base-300/80 min-h-full flex flex-col transition-all duration-300 ease-in-out select-none w-68"
        x-bind:class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-68'">

        <!-- Brand Header -->
        <div class="h-16 min-h-16 px-4 border-b border-base-200/80 flex items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden"
                x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:w-full' : ''">
                <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0 shadow-2xs">
                    <img src="{{ asset('/assets/images/tsui.png') }}" class="w-6 h-6 object-contain" alt="{{ config('app.name', 'Equilend') }}" />
                </div>
                <div class="flex flex-col leading-tight truncate" x-show="!sidebarCollapsed" x-transition.opacity>
                    <span class="font-bold text-base tracking-tight text-base-content">{{ config('app.name', 'Equilend') }}</span>
                    <span class="text-[11px] font-medium text-base-content/50">Equipment Portal</span>
                </div>
            </a>

            <!-- Close button on mobile -->
            <label for="app-drawer" aria-label="close sidebar" class="btn btn-ghost btn-square btn-xs lg:hidden text-base-content/60">
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </label>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-5">
            @foreach ($navGroups as $group)
                <div class="space-y-1">
                    <!-- Section Header -->
                    <div class="px-3 mb-1.5 flex items-center"
                        x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <span x-show="!sidebarCollapsed" class="text-[11px] font-semibold uppercase tracking-wider text-base-content/45">
                            {{ $group['title'] }}
                        </span>
                        <span x-show="sidebarCollapsed" class="hidden lg:block w-5 h-px bg-base-300 my-1" title="{{ $group['title'] }}"></span>
                    </div>

                    <!-- Group Items -->
                    <ul class="space-y-1">
                        @foreach ($group['items'] as $item)
                            <li>
                                <div x-bind:class="sidebarCollapsed ? 'lg:tooltip lg:tooltip-right w-full' : 'w-full'" data-tip="{{ $item['label'] }}">
                                    <a href="{{ $item['href'] }}"
                                        @class([
                                            'group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                                            'bg-primary text-primary-content shadow-xs shadow-primary/20' => $item['active'],
                                            'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' => ! $item['active'],
                                        ])
                                        x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                                        <x-icon :name="$item['icon']"
                                            @class([
                                                'w-5 h-5 shrink-0 transition-transform duration-150 group-hover:scale-105',
                                                'text-primary-content' => $item['active'],
                                                'text-base-content/60 group-hover:text-primary' => ! $item['active'],
                                            ]) />

                                        <span class="truncate" x-show="!sidebarCollapsed" x-transition.opacity>
                                            {{ $item['label'] }}
                                        </span>

                                        @if ($item['active'])
                                            <span x-show="!sidebarCollapsed" class="ml-auto w-1.5 h-1.5 rounded-full bg-primary-content/90"></span>
                                        @endif
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <!-- Sidebar Footer User Card -->
        <div class="p-3 border-t border-base-200/80 bg-base-200/30">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-base-100 border border-base-300/60 shadow-2xs"
                x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:p-1.5' : ''">
                <a href="{{ route('user.profile') }}" class="flex items-center gap-2.5 min-w-0 flex-1"
                    x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:flex-none' : ''"
                    title="{{ __('View Profile') }}">
                    <div class="avatar avatar-placeholder shrink-0">
                        <div class="bg-primary/15 text-primary border border-primary/20 w-8 h-8 rounded-lg text-xs font-bold">
                            <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                        </div>
                    </div>
                    <div class="flex flex-col min-w-0 flex-1" x-show="!sidebarCollapsed" x-transition.opacity>
                        <span class="text-xs font-semibold text-base-content truncate" x-text="name"></span>
                        <span class="text-[11px] text-base-content/55 truncate" x-text="email"></span>
                    </div>
                </a>

                <button type="button" x-on:click="toggleSidebar()"
                    class="btn btn-ghost btn-square btn-xs hidden lg:inline-flex text-base-content/50 hover:text-base-content shrink-0"
                    x-show="!sidebarCollapsed"
                    title="Collapse sidebar">
                    <x-heroicon-o-chevron-left class="w-4 h-4" />
                </button>
            </div>
        </div>
    </aside>
</div>
