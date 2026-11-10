@php
    $user = auth()->user();
    $isSuperAdmin = (bool) $user?->hasRole('superadmin');
    $isStaff = $isSuperAdmin || (bool) $user?->hasRole('organization_admin');

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
            'title' => 'Borrower Portal',
            'items' => [
                [
                    'label'  => 'Browse Equipment',
                    'href'   => route('inventory.index'),
                    'active' => request()->routeIs('inventory.*') && ! $isStaff,
                    'icon'   => 'heroicon-o-magnifying-glass',
                ],
                [
                    'label'  => 'My Reservations',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-calendar-days',
                ],
                [
                    'label'  => 'My Active Loans',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-arrow-path',
                ],
                [
                    'label'  => 'Borrowing History',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-clock',
                ],
                [
                    'label'  => 'My Penalties & Dues',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-receipt-percent',
                ],
            ],
        ],
    ];

    if ($isStaff) {
        $navGroups[] = [
            'title' => 'Circulation & Desk',
            'items' => [
                [
                    'label'  => 'Check-Out / Check-In',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-arrows-right-left',
                ],
                [
                    'label'  => 'Active Loans',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-clipboard-document-check',
                ],
                [
                    'label'  => 'Booking Requests',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-inbox-stack',
                ],
                [
                    'label'  => 'Overdue Tracking',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-exclamation-circle',
                ],
                [
                    'label'  => 'Availability Calendar',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-calendar',
                ],
            ],
        ];

        $navGroups[] = [
            'title' => 'Inventory & Assets',
            'items' => [
                [
                    'label'  => 'Equipment Catalog',
                    'href'   => route('inventory.index'),
                    'active' => request()->routeIs('inventory.*'),
                    'icon'   => 'heroicon-o-archive-box',
                ],
                [
                    'label'  => 'Units & Serials',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-qr-code',
                ],
                [
                    'label'  => 'Restocking Logs',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-arrow-down-tray',
                ],
                [
                    'label'  => 'Audits & Adjustments',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-clipboard-document-list',
                ],
            ],
        ];

        $navGroups[] = [
            'title' => 'Finance & Penalties',
            'items' => [
                [
                    'label'  => 'Penalties & Fines',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-banknotes',
                ],
                [
                    'label'  => 'Payments & Receipts',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-credit-card',
                ],
            ],
        ];

        $navGroups[] = [
            'title' => 'Reports & Analytics',
            'items' => [
                [
                    'label'  => 'Inventory Reports',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-chart-pie',
                ],
                [
                    'label'  => 'Circulation Metrics',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-chart-bar-square',
                ],
                [
                    'label'  => 'Penalty Collections',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-presentation-chart-line',
                ],
            ],
        ];

        $navGroups[] = [
            'title' => 'Administration',
            'items' => [
                [
                    'label'  => 'Organization Settings',
                    'href'   => route('organization.settings'),
                    'active' => request()->routeIs('organization.settings'),
                    'icon'   => 'heroicon-o-cog-6-tooth',
                ],
            ],
        ];
    }

    if ($isSuperAdmin) {
        $navGroups[] = [
            'title' => 'Developer & System',
            'items' => [
                [
                    'label'  => 'Organizations',
                    'href'   => route('organization.index'),
                    'active' => request()->routeIs('organization.index'),
                    'icon'   => 'heroicon-o-building-office-2',
                ],
                [
                    'label'  => 'Users Management',
                    'href'   => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                    'icon'   => 'heroicon-o-users',
                ],
                [
                    'label'  => 'Roles & Permissions',
                    'href'   => '#',
                    'active' => false,
                    'icon'   => 'heroicon-o-shield-check',
                ],
                [
                    'label'  => 'Payment Methods',
                    'href'   => route('payment-methods.index'),
                    'active' => request()->routeIs('payment-methods.*'),
                    'icon'   => 'heroicon-o-wallet',
                ],
                [
                    'label'  => 'Starter Page',
                    'href'   => route('starter-page'),
                    'active' => request()->routeIs('starter-page'),
                    'icon'   => 'heroicon-o-document-text',
                ],
            ],
        ];
    }
@endphp

<div class="drawer-side z-40">
    <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

    <aside class="bg-[#22272e] text-slate-200 border-r border-[#1a1e23] min-h-full flex flex-col transition-all duration-300 ease-in-out select-none shadow-xl w-64"
        x-bind:class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'">

        <!-- Mobile Header with Close Button -->
        <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-slate-700/60">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Navigation</span>
            <label for="app-drawer" aria-label="close sidebar" class="btn btn-ghost btn-square btn-xs text-slate-400 hover:text-white">
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </label>
        </div>

        <!-- Sidebar Top Profile Card -->
        <div class="border-b border-slate-700/60"
            x-bind:class="sidebarCollapsed ? 'py-4 flex flex-col items-center' : 'pt-5 pb-4 px-4 flex flex-col items-center'">

            <!-- Expanded State Profile Details -->
            <template x-if="!sidebarCollapsed">
                <a href="{{ route('user.profile') }}" class="flex flex-col items-center w-full group cursor-pointer" title="{{ __('View Profile') }}">
                    <div class="avatar avatar-placeholder mb-2" :class="{ 'avatar-placeholder': !picDir }">
                        <template x-if="picDir">
                            <div class="w-16 h-16 rounded-full border-2 border-slate-500/60 shadow-md ring-4 ring-slate-800/80 overflow-hidden group-hover:ring-amber-400/70 transition-all">
                                <img :src="picDir" :alt="name" class="w-full h-full object-cover" />
                            </div>
                        </template>
                        <template x-if="!picDir">
                            <div class="w-16 h-16 rounded-full bg-slate-700/90 border-2 border-slate-500/60 shadow-md text-slate-100 text-xl font-bold flex items-center justify-center ring-4 ring-slate-800/80 group-hover:ring-amber-400/70 transition-all">
                                <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                            </div>
                        </template>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-100 text-center truncate w-full px-2 group-hover:text-amber-400 transition-colors" x-text="name"></span>
                    <span class="text-[11px] font-semibold text-amber-400 mt-0.5 text-center truncate w-full px-2" x-text="role"></span>
                </a>
            </template>

            <!-- Collapsed State Profile Details -->
            <template x-if="sidebarCollapsed">
                <div class="hidden lg:flex items-center justify-center w-full">
                    <a href="{{ route('user.profile') }}" class="avatar avatar-placeholder group cursor-pointer" :class="{ 'avatar-placeholder': !picDir }" :title="name">
                        <template x-if="picDir">
                            <div class="w-10 h-10 rounded-full border border-slate-500/60 overflow-hidden group-hover:border-amber-400 transition-colors">
                                <img :src="picDir" :alt="name" class="w-full h-full object-cover" />
                            </div>
                        </template>
                        <template x-if="!picDir">
                            <div class="w-10 h-10 rounded-full bg-slate-700/90 border border-slate-500/60 text-slate-100 text-sm font-bold flex items-center justify-center group-hover:border-amber-400 transition-colors">
                                <span x-text="name ? name.charAt(0).toUpperCase() : 'U'"></span>
                            </div>
                        </template>
                    </a>
                </div>
            </template>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-2.5 py-3 space-y-3">
            @foreach ($navGroups as $group)
                <div class="space-y-0.5">
                    <!-- Section Header -->
                    <div class="px-2.5 mb-1 flex items-center"
                        x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <span x-show="!sidebarCollapsed" class="text-[10px] font-bold uppercase tracking-wider text-slate-400/70">
                            {{ $group['title'] }}
                        </span>
                        <span x-show="sidebarCollapsed" class="hidden lg:block w-5 h-px bg-slate-700/60 my-1" title="{{ $group['title'] }}"></span>
                    </div>

                    <!-- Group Items -->
                    <ul class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            <li>
                                <div x-bind:class="sidebarCollapsed ? 'lg:tooltip lg:tooltip-right w-full' : 'w-full'" data-tip="{{ $item['label'] }}">
                                    <a href="{{ $item['href'] }}"
                                        @if ($item['href'] === '#') onclick="return false;" @endif
                                        @class([
                                            'group relative flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-150',
                                            'text-amber-400 bg-amber-400/10 font-semibold border-l-2 border-amber-400' => $item['active'],
                                            'text-slate-300 hover:text-white hover:bg-white/5 border-l-2 border-transparent' => ! $item['active'],
                                            'opacity-85 hover:opacity-100' => $item['href'] === '#',
                                        ])
                                        x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:px-0 lg:border-l-0' : ''">
                                        <x-icon :name="$item['icon']"
                                            @class([
                                                'w-4 h-4 shrink-0 transition-transform duration-150 group-hover:scale-105',
                                                'text-amber-400' => $item['active'],
                                                'text-slate-400 group-hover:text-slate-200' => ! $item['active'],
                                            ]) />

                                        <span class="truncate" x-show="!sidebarCollapsed" x-transition.opacity>
                                            {{ $item['label'] }}
                                        </span>

                                        @if ($item['active'])
                                            <span x-show="!sidebarCollapsed" class="ml-auto w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        @endif
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <!-- Sidebar Footer Action -->
        <div class="p-2.5 border-t border-slate-700/60 bg-slate-900/40">
            <div class="flex items-center justify-between text-xs text-slate-400 px-1"
                x-bind:class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                <span class="text-[10px] font-mono opacity-60" x-show="!sidebarCollapsed">Equilend v1.0</span>
                <button type="button" x-on:click="toggleSidebar()"
                    class="btn btn-ghost btn-square btn-xs hidden lg:inline-flex text-slate-400 hover:text-white"
                    title="Toggle sidebar">
                    <x-heroicon-o-chevron-left x-show="!sidebarCollapsed" class="w-3.5 h-3.5" />
                    <x-heroicon-o-chevron-right x-show="sidebarCollapsed" class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </aside>
</div>
