<x-side-bar smart collapsible>
    <x-slot:brand>
        <div class="my-4 flex items-center justify-center">
            <img src="{{ asset('/assets/images/tsui.png') }}" width="40" height="40" />
        </div>
    </x-slot:brand>
    <x-slot:brand-collapsed>
        <div class="my-4 flex items-center justify-center">
            <img src="{{ asset('/assets/images/tsui.png') }}" width="20" height="20" />
        </div>
    </x-slot:brand-collapsed>
    <x-side-bar.separator text="Configurations" line />
    <x-side-bar.item text="Dashboard" icon="home" :route="route('dashboard')" />
    <x-side-bar.item text="Users" icon="users" :route="route('users.index')" />
    <x-side-bar.item text="Welcome Page" icon="arrow-uturn-left" :route="route('welcome')" />
</x-side-bar>
