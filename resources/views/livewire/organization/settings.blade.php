<div class="p-1 sm:p-2 w-full min-w-0" x-data="{ activeTab: 'general' }">
    @if (! $organization)
        {{-- Empty State when user has no assigned organization --}}
        <div class="card bg-base-100 card-border border-base-300 shadow-xs max-w-xl mx-auto my-12">
            <div class="card-body text-center py-12 px-6">
                <div class="w-16 h-16 rounded-2xl bg-warning/10 text-warning mx-auto flex items-center justify-center mb-4">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-base-content">{{ __('No Organization Assigned') }}</h3>
                <p class="text-xs sm:text-sm text-base-content/60 max-w-md mx-auto mt-2 leading-relaxed">
                    {{ __('You are not currently assigned to any organization. Please contact your system administrator to get assigned.') }}
                </p>
            </div>
        </div>
    @else
        {{-- Breadcrumbs & Actions --}}
        <x-breadcrumbs :items="[
            ['label' => __('Organization'), 'icon' => 'heroicon-o-building-office-2'],
            ['label' => __('Settings'), 'icon' => 'heroicon-o-cog-6-tooth'],
        ]">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth()->user()->hasRole('superadmin') && $this->allOrganizations->count() > 1)
                        <select wire:change="changeOrganization($event.target.value)" class="select select-sm select-bordered w-48 sm:w-56 text-xs font-semibold">
                            @foreach ($this->allOrganizations as $org)
                                <option value="{{ $org->id }}" @selected($organization && $organization->id === $org->id)>
                                    {{ $org->code ? "{$org->code} - " : '' }}{{ $org->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 bg-base-200 border border-base-300 px-2.5 py-1 rounded-md whitespace-nowrap">
                            <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                            {{ $organization->code ?? 'Hub' }}
                        </span>
                    @endif

                    <button type="submit" form="org-settings-form" class="btn btn-primary btn-sm font-semibold gap-1.5" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                        <x-heroicon-o-check wire:loading.remove wire:target="save" class="w-4 h-4" />
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            </x-slot:actions>
        </x-breadcrumbs>

        @php
            $enabledCount = collect($paymentConfigs)->where('is_enabled', true)->count();
        @endphp

        <div class="space-y-3 mt-1">
            {{-- Organization Identity Summary Header --}}
            <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
                <div class="card-body p-3.5 sm:p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <x-heroicon-o-building-office-2 class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-sm sm:text-base font-bold text-base-content truncate">{{ $organization->name }}</h1>
                                    @if ($organization->code)
                                        <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $organization->code }}</span>
                                    @endif
                                    @if ($organization->is_active)
                                        <span class="badge badge-success badge-xs font-semibold whitespace-nowrap">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge badge-ghost badge-xs font-semibold whitespace-nowrap">{{ __('Inactive') }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-base-content/60 mt-0.5">
                                    @if ($organization->email)
                                        <span class="inline-flex items-center gap-1">
                                            <x-heroicon-o-envelope class="w-3.5 h-3.5 opacity-70" />
                                            <span>{{ $organization->email }}</span>
                                        </span>
                                    @endif
                                    @if ($organization->phone)
                                        <span class="inline-flex items-center gap-1">
                                            <x-heroicon-o-phone class="w-3.5 h-3.5 opacity-70" />
                                            <span>{{ $organization->phone }}</span>
                                        </span>
                                    @endif
                                    @if (! $organization->email && ! $organization->phone)
                                        <span>{{ __('Configure organization profile and receiving payment channels.') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <div class="px-2.5 py-1.5 rounded-lg bg-base-200/70 border border-base-300 text-xs flex items-center gap-1.5">
                                <x-heroicon-o-credit-card class="w-3.5 h-3.5 text-primary" />
                                <span class="font-semibold text-base-content tabular-nums">{{ $enabledCount }}</span>
                                <span class="text-base-content/60">{{ __('active channels') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Unified Settings Card --}}
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                {{-- Tabs Navigation --}}
                <div role="tablist" class="tabs tabs-border border-b border-base-200 px-4 sm:px-6 pt-2 overflow-x-auto">
                    <button type="button" role="tab" class="tab text-xs sm:text-sm font-medium gap-2"
                        :class="{ 'tab-active text-primary font-semibold': activeTab === 'general' }"
                        @click="activeTab = 'general'">
                        <x-heroicon-o-building-office class="w-4 h-4" />
                        <span>{{ __('General Profile') }}</span>
                    </button>
                    <button type="button" role="tab" class="tab text-xs sm:text-sm font-medium gap-2"
                        :class="{ 'tab-active text-primary font-semibold': activeTab === 'payments' }"
                        @click="activeTab = 'payments'">
                        <x-heroicon-o-credit-card class="w-4 h-4" />
                        <span>{{ __('Payment Methods') }}</span>
                        @if ($enabledCount > 0)
                            <span class="badge badge-xs badge-primary font-semibold tabular-nums">
                                {{ $enabledCount }}
                            </span>
                        @endif
                    </button>
                </div>

                {{-- Form containing both tabs --}}
                <form id="org-settings-form" wire:submit="save">
                    {{-- TAB 1: General Profile --}}
                    <div x-show="activeTab === 'general'" class="p-4 sm:p-6">
                        @include('livewire.organization.settings.general')
                    </div>

                    {{-- TAB 2: Payment Methods --}}
                    <div x-show="activeTab === 'payments'" x-cloak class="p-4 sm:p-6">
                        @include('livewire.organization.settings.payment-methods')
                    </div>

                    {{-- Clean Card Footer --}}
                    <div class="border-t border-base-200 bg-base-200/40 px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-b-box">
                        <p class="text-xs text-base-content/60 flex items-center gap-1.5">
                            <x-heroicon-o-information-circle class="w-4 h-4 text-base-content/50 shrink-0" />
                            <span>{{ __('Changes apply to both the organization profile and payment channels.') }}</span>
                        </p>

                        <button type="submit" class="btn btn-primary btn-sm px-5 font-semibold w-full sm:w-auto gap-1.5" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                            <x-heroicon-o-check wire:loading.remove wire:target="save" class="w-4 h-4" />
                            <span>{{ __('Save Changes') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
