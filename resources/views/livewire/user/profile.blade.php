<div @updated="$dispatch('name-updated', { name: $event.detail.name, pic_dir: $event.detail.pic_dir })" x-data="{ activeTab: 'profile' }">
    <x-breadcrumbs :items="[
        ['label' => __('Account'), 'icon' => 'heroicon-o-user'],
        ['label' => __('Profile & Security'), 'icon' => 'heroicon-o-shield-check'],
    ]" />

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
            </div>

            <div x-show="activeTab === 'profile'">
                <livewire:user.profile.information />
            </div>

            <div x-show="activeTab === 'password'" x-cloak>
                <livewire:user.profile.password />
            </div>
        </div>
    </div>
</div>
