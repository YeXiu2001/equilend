<div @updated="$dispatch('name-updated', { name: $event.detail.name })" x-data="{ activeTab: 'profile' }">
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body">
            <div role="tablist" class="tabs tabs-border border-b border-base-200 mb-4 overflow-x-auto">
                <button type="button" role="tab" class="tab font-medium"
                    x-bind:class="{ 'tab-active text-primary': activeTab === 'profile' }"
                    x-on:click="activeTab = 'profile'">
                    {{ __('Profile') }}
                </button>
                <button type="button" role="tab" class="tab font-medium"
                    x-bind:class="{ 'tab-active text-primary': activeTab === 'password' }"
                    x-on:click="activeTab = 'password'">
                    {{ __('Password') }}
                </button>
                <button type="button" role="tab" class="tab font-medium"
                    x-bind:class="{ 'tab-active text-primary': activeTab === 'two-factor' }"
                    x-on:click="activeTab = 'two-factor'">
                    {{ __('Two Factor Authentication') }}
                </button>
            </div>

            <div x-show="activeTab === 'profile'">
                <livewire:user.profile.information />
            </div>

            <div x-show="activeTab === 'password'" x-cloak>
                <livewire:user.profile.password />
            </div>

            <div x-show="activeTab === 'two-factor'" x-cloak>
                <livewire:user.profile.two-factor-authentication />
            </div>
        </div>
    </div>
</div>
