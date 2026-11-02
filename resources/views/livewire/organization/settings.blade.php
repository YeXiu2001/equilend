<div x-data="{ activeTab: 'general' }">
    @if (! $organization)
        {{-- Empty State when user has no assigned organization --}}
        <div class="card bg-base-100 card-border border-base-300 shadow-xs max-w-xl mx-auto my-12">
            <div class="card-body text-center py-12 px-6">
                <div class="w-16 h-16 rounded-2xl bg-warning/10 text-warning mx-auto flex items-center justify-center mb-4 shadow-inner">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-bold text-base-content">{{ __('No Organization Assigned') }}</h3>
                <p class="text-sm text-base-content/60 max-w-md mx-auto mt-2 leading-relaxed">
                    {{ __('You are not currently assigned to any organization. Please contact your system administrator to get assigned.') }}
                </p>
            </div>
        </div>
    @else
        {{-- Uniform Breadcrumb & Action Card --}}
        <div class="card bg-base-100 card-border border-base-300 shadow-xs mb-4">
            <div class="card-body flex flex-col md:flex-row md:items-center md:justify-between py-4 gap-4">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li class="flex items-center gap-1.5">
                            <x-heroicon-o-building-office-2 class="w-4 h-4" />
                            <span>{{ __('Organization') }}</span>
                        </li>
                        <li>{{ __('Settings') }}</li>
                        @if ($organization)
                            <li class="font-semibold text-base-content flex items-center gap-2">
                                <span>{{ $organization->name }}</span>
                                @if ($organization->code)
                                    <span class="font-mono text-xs text-base-content/60">({{ $organization->code }})</span>
                                @endif
                                @if ($organization->is_active)
                                    <span class="badge badge-success badge-sm text-success-content font-medium">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-ghost badge-sm font-medium">{{ __('Inactive') }}</span>
                                @endif
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if (auth()->user()->hasRole('superadmin') && $this->allOrganizations->count() > 1)
                        <div class="flex items-center gap-2 bg-base-200/60 px-3 py-1.5 rounded-lg border border-base-300 text-xs">
                            <span class="font-medium text-base-content/60">{{ __('Switch Org:') }}</span>
                            <select wire:change="changeOrganization($event.target.value)" class="select select-xs select-ghost font-medium">
                                @foreach ($this->allOrganizations as $org)
                                    <option value="{{ $org->id }}" @selected($organization && $organization->id === $org->id)>
                                        {{ $org->name }} {{ $org->code ? "({$org->code})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit" form="org-settings-form" class="btn btn-primary btn-sm px-5 font-semibold" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                        <x-heroicon-o-check wire:loading.remove wire:target="save" class="w-4 h-4" />
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div role="tablist" class="tabs tabs-border border-b border-base-200 mb-6 overflow-x-auto">
            <button type="button" role="tab" class="tab font-medium gap-2"
                :class="{ 'tab-active text-primary font-semibold': activeTab === 'general' }"
                @click="activeTab = 'general'">
                <x-heroicon-o-building-office class="w-4 h-4" />
                <span>{{ __('General Profile') }}</span>
            </button>
            <button type="button" role="tab" class="tab font-medium gap-2"
                :class="{ 'tab-active text-primary font-semibold': activeTab === 'payments' }"
                @click="activeTab = 'payments'">
                <x-heroicon-o-credit-card class="w-4 h-4" />
                <span>{{ __('Payment Methods') }}</span>
                @php
                    $enabledCount = collect($paymentConfigs)->where('is_enabled', true)->count();
                @endphp
                @if ($enabledCount > 0)
                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full bg-primary/10 text-primary">
                        {{ $enabledCount }}
                    </span>
                @endif
            </button>
        </div>

        {{-- Form containing both tabs --}}
        <form id="org-settings-form" wire:submit="save" class="space-y-6">
            {{-- TAB 1: General Profile --}}
            <div x-show="activeTab === 'general'">
                @include('livewire.organization.settings.general')
            </div>

            {{-- TAB 2: Payment Methods --}}
            <div x-show="activeTab === 'payments'" x-cloak>
                @include('livewire.organization.settings.payment-methods')
            </div>

            {{-- Sticky Save CTA Bar --}}
            <div class="card bg-base-100 card-border border-base-300 shadow-xs p-4 sticky bottom-4 z-20 backdrop-blur-md bg-base-100/90">
                <div class="flex items-center justify-between gap-4">
                    <div class="text-xs text-base-content/60 hidden sm:block">
                        {{ __('Save after updating organization profile or payment methods configuration.') }}
                    </div>

                    <button type="submit" class="btn btn-primary px-8 font-semibold w-full sm:w-auto shadow-xs gap-2" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                        <x-heroicon-o-check wire:loading.remove wire:target="save" class="w-4 h-4" />
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
