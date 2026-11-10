<div>
    <x-breadcrumbs :items="[
        ['label' => __('Developer'), 'icon' => 'heroicon-o-command-line'],
        ['label' => __('Starter Page'), 'icon' => 'heroicon-o-document-text'],
    ]">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span>{{ __('Add Something') }}</span>
            </button>
        </x-slot:actions>
    </x-breadcrumbs>
</div>
