@php
    $navGroups = [
        [
            'title' => 'Main Menu',
            'items' => [
                [
                    'label'  => 'Dashboard',
                    'href'   => route('dashboard'),
                    'active' => request()->routeIs('dashboard'),
                    'icon'   => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
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
                    'icon'   => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
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
                    'icon'   => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
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
                    'icon'   => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
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
                    'icon'   => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                ],
                [
                    'label'  => 'Payment Methods',
                    'href'   => route('payment-methods.index'),
                    'active' => request()->routeIs('payment-methods.*'),
                    'icon'   => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
                ],
                [
                    'label'  => 'Users',
                    'href'   => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                    'icon'   => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                ],
                [
                    'label'  => 'Roles and Permissions',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                ],
                [
                    'label'  => 'Starter Page',
                    'href'   => route('starter-page'),
                    'active' => request()->routeIs('starter-page'),
                    'icon'   => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"
                                            @class([
                                                'w-5 h-5 shrink-0 transition-transform duration-150 group-hover:scale-105',
                                                'text-primary-content' => $item['active'],
                                                'text-base-content/60 group-hover:text-primary' => ! $item['active'],
                                            ])>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                        </svg>

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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
            </div>
        </div>
    </aside>
</div>
