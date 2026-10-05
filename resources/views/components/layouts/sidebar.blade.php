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
    <x-side-bar.separator line>
        <span class="text-xs uppercase tracking-wider font-semibold">Main Menu</span>
    </x-side-bar.separator>
    <x-side-bar.item text="Dashboard" icon="home" :route="route('dashboard')" />

    <x-side-bar.separator line>
        <span class="text-xs uppercase tracking-wider font-semibold">Asset Management</span>
    </x-side-bar.separator>

    <x-side-bar.item text="Inventory" icon="briefcase" />

    <x-side-bar.separator line>
        <span class="text-xs uppercase tracking-wider font-semibold">Transactions</span>
    </x-side-bar.separator>

    <x-side-bar.item text="Loan History" icon="briefcase" />

    <x-side-bar.separator line>
        <span class="text-xs uppercase tracking-wider font-semibold">Developer</span>
    </x-side-bar.separator>
    <x-side-bar.item text="Users" icon="users" :route="route('users.index')" />
    <x-side-bar.item text="Roles and Permissions" icon="scale" />
    <x-side-bar.item text="Starter Page" icon="scale" />
</x-side-bar>
