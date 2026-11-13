<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Item;
use App\Models\Loan;
use Livewire\Component;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReport extends Component
{
    public string $selectedOrganizationId = 'all';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to inventory reports.');
        }

        if (! $user->hasRole('superadmin')) {
            $this->selectedOrganizationId = $user->organization ? (string) $user->organization->id : 'all';
        }
    }

    /**
     * List active colleges for superadmin filter dropdown.
     *
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Compute inventory health and utilization KPIs.
     *
     * @return array<string, int|float>
     */
    #[Computed]
    public function summaryStats(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $unitQuery = ItemUnit::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId));

        $totalUnits       = (clone $unitQuery)->count();
        $availableUnits   = (clone $unitQuery)->where('status', ItemUnitStatus::Available)->count();
        $borrowedUnits    = (clone $unitQuery)->where('status', ItemUnitStatus::Borrowed)->count();
        $reservedUnits    = (clone $unitQuery)->where('status', ItemUnitStatus::Reserved)->count();
        $maintenanceUnits = (clone $unitQuery)->where('status', ItemUnitStatus::UnderMaintenance)->count();
        $damagedLostUnits = (clone $unitQuery)->whereIn('status', [ItemUnitStatus::Damaged, ItemUnitStatus::Lost])->count();

        $totalCatalogItems = Item::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->count();

        $totalHistoricalCheckouts = Loan::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->count();

        $activeUtilizationRate = $totalUnits > 0
            ? round((($borrowedUnits + $reservedUnits) / $totalUnits) * 100, 1)
            : 0.0;

        return [
            'total_units'       => $totalUnits,
            'available_units'   => $availableUnits,
            'borrowed_units'    => $borrowedUnits,
            'reserved_units'    => $reservedUnits,
            'maintenance_units' => $maintenanceUnits,
            'damaged_lost'      => $damagedLostUnits,
            'total_items'       => $totalCatalogItems,
            'total_checkouts'   => $totalHistoricalCheckouts,
            'utilization_rate'  => $activeUtilizationRate,
        ];
    }

    /**
     * Breakdown of unit statuses for visual distribution bars.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function statusBreakdown(): array
    {
        $orgId = $this->getEffectiveOrganizationId();
        $total = max(1, $this->summaryStats['total_units']);

        $counts = ItemUnit::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rows = [];

        foreach (ItemUnitStatus::cases() as $status) {
            $count  = (int) ($counts[$status->value] ?? 0);
            $rows[] = [
                'label'       => $status->label(),
                'value'       => $status->value,
                'count'       => $count,
                'percentage'  => round(($count / $total) * 100, 1),
                'badge_class' => $status->badgeClass(),
            ];
        }

        return $rows;
    }

    /**
     * Breakdown of physical unit conditions.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function conditionBreakdown(): array
    {
        $orgId = $this->getEffectiveOrganizationId();
        $total = max(1, $this->summaryStats['total_units']);

        $counts = ItemUnit::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->selectRaw('`condition`, count(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $rows = [];

        foreach (ItemUnitCondition::cases() as $condition) {
            $count  = (int) ($counts[$condition->value] ?? 0);
            $rows[] = [
                'label'       => $condition->label(),
                'value'       => $condition->value,
                'count'       => $count,
                'percentage'  => round(($count / $total) * 100, 1),
                'badge_class' => $condition->badgeClass(),
            ];
        }

        return $rows;
    }

    /**
     * Top most frequently borrowed equipment items.
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function topBorrowedItems(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Item::query()
            ->with(['category', 'organization'])
            ->withCount([
                'units',
                'loans',
                'loans as active_loans_count' => fn ($q) => $q->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue]),
            ])
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->orderByDesc('loans_count')
            ->limit(15)
            ->get();
    }

    /**
     * Stream a CSV export of the inventory health and top borrowed items report.
     */
    public function exportCsv(): StreamedResponse
    {
        $items    = $this->topBorrowedItems;
        $filename = 'inventory-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($items): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'College',
                'Item Name',
                'Category',
                'Total Units',
                'Total Checkouts',
                'Active Loans',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->organization?->code ?? 'N/A',
                    $item->name,
                    $item->category?->name ?? 'Uncategorized',
                    $item->units_count,
                    $item->loans_count,
                    $item->active_loans_count,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(): View
    {
        return view('livewire.reports.inventory-report');
    }

    /**
     * Resolve the effective organization ID based on user role and filter.
     */
    protected function getEffectiveOrganizationId(): ?int
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            return $user->organization?->id;
        }

        if ($this->selectedOrganizationId !== 'all' && is_numeric($this->selectedOrganizationId)) {
            return (int) $this->selectedOrganizationId;
        }

        return null;
    }
}
