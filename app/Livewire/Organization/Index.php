<?php

declare(strict_types=1);

namespace App\Livewire\Organization;

use Livewire\Component;
use App\Models\Organization;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class Index extends Component
{
    use WithPagination;

    public ?int $quantity = 10;

    public ?string $search = null;

    public array $sort = [
        'column'    => 'created_at',
        'direction' => 'desc',
    ];

    public array $headers = [
        ['index' => 'id', 'label' => '#'],
        ['index' => 'code', 'label' => 'Code'],
        ['index' => 'name', 'label' => 'Name'],
        ['index' => 'contact', 'label' => 'Contact', 'sortable' => false],
        ['index' => 'manager', 'label' => 'Assigned User', 'sortable' => false],
        ['index' => 'is_active', 'label' => 'Status'],
        ['index' => 'created_at', 'label' => 'Created'],
        ['index' => 'action', 'label' => 'Action', 'sortable' => false],
    ];

    public function mount(): void
    {
        if (! auth()->user()?->hasRole('superadmin')) {
            abort(403, 'Unauthorized. Superadmin access required.');
        }
    }

    public function render(): View
    {
        return view('livewire.organization.index');
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return Organization::query()
            ->with(['members'])
            ->when($this->search !== null && mb_trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.mb_trim($this->search).'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy(...array_values($this->sort))
            ->paginate($this->quantity)
            ->withQueryString();
    }
}
