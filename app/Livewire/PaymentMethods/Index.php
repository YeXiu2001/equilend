<?php

declare(strict_types=1);

namespace App\Livewire\PaymentMethods;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PaymentMethod;
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
        ['index' => 'payment_code', 'label' => 'Payment Code'],
        ['index' => 'name', 'label' => 'Name'],
        ['index' => 'description', 'label' => 'Description'],
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
        return view('livewire.payment-methods.index');
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return PaymentMethod::query()
            ->when($this->search !== null && mb_trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.mb_trim($this->search).'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('payment_code', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->orderBy(...array_values($this->sort))
            ->paginate($this->quantity)
            ->withQueryString();
    }
}
