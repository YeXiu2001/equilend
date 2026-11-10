<div>
    <x-breadcrumbs :items="[
        ['label' => __('Inventory'), 'icon' => 'heroicon-o-archive-box'],
        ['label' => __('Equipment Catalog'), 'icon' => 'heroicon-o-queue-list'],
    ]">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm px-4 font-semibold">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span>{{ __('Add Item') }}</span>
            </button>
        </x-slot:actions>
    </x-breadcrumbs>
</div>
