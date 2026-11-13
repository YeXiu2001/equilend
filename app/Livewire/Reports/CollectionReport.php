<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollectionReport extends Component
{
    public string $selectedOrganizationId = 'all';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to penalty collections report.');
        }

        if (! $user->hasRole('superadmin')) {
            $this->selectedOrganizationId = $user->organization ? (string) $user->organization->id : 'all';
        }
    }

    /**
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Compute penalty & collection financial summary KPIs.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function summaryStats(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $penaltyQuery = Penalty::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', '!=', PenaltyStatus::Voided);

        $totalLevied = round((float) (clone $penaltyQuery)->sum('amount'), 2);
        $totalWaived = round((float) (clone $penaltyQuery)->where('status', PenaltyStatus::Waived)->sum('amount'), 2);

        $outstandingDebt = round((float) Penalty::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->unsettled()
            ->sum('balance_remaining'), 2);

        $totalCollected = round((float) Payment::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', PaymentStatus::Verified)
            ->sum('amount_paid'), 2);

        $collectibleBase = max(0.0, $totalLevied - $totalWaived);
        $collectionRate  = $collectibleBase > 0 ? round(($totalCollected / $collectibleBase) * 100, 1) : 0.0;

        return [
            'total_levied'     => $totalLevied,
            'total_collected'  => $totalCollected,
            'outstanding_debt' => $outstandingDebt,
            'total_waived'     => $totalWaived,
            'collection_rate'  => $collectionRate,
        ];
    }

    /**
     * Revenue breakdown by payment channel (Cash, GCash, Maya, PayPal).
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function channelBreakdown(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $payments = Payment::query()
            ->with('paymentMethod')
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', PaymentStatus::Verified)
            ->get();

        $totalCollected = max(1.0, (float) $payments->sum('amount_paid'));

        $grouped = $payments->groupBy(fn (Payment $payment): string => $payment->paymentMethod?->name ?? 'Other');

        $rows = [];

        foreach ($grouped as $channelName => $channelPayments) {
            $sum    = round((float) $channelPayments->sum('amount_paid'), 2);
            $rows[] = [
                'channel'    => $channelName,
                'count'      => $channelPayments->count(),
                'amount'     => $sum,
                'percentage' => round(($sum / $totalCollected) * 100, 1),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return $rows;
    }

    /**
     * Monthly collection revenue trend.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function monthlyCollections(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $payments = Payment::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', PaymentStatus::Verified)
            ->whereNotNull('paid_at')
            ->orderBy('paid_at')
            ->get();

        $grouped = $payments->groupBy(fn (Payment $p): string => $p->paid_at->format('Y-m'));
        $max     = max(1.0, (float) ($grouped->map(fn ($g) => $g->sum('amount_paid'))->max() ?? 1.0));

        $rows = [];

        foreach ($grouped as $monthKey => $monthPayments) {
            $sum    = round((float) $monthPayments->sum('amount_paid'), 2);
            $rows[] = [
                'month'      => \Illuminate\Support\Carbon::createFromFormat('Y-m', $monthKey)?->format('M Y') ?? $monthKey,
                'count'      => $monthPayments->count(),
                'amount'     => $sum,
                'percentage' => round(($sum / $max) * 100, 1),
            ];
        }

        return $rows;
    }

    /**
     * Outstanding student debt ledger (unsettled penalties).
     *
     * @return Collection<int, Penalty>
     */
    #[Computed]
    public function outstandingLedger(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Penalty::query()
            ->with(['user', 'organization'])
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->unsettled()
            ->orderByDesc('balance_remaining')
            ->limit(25)
            ->get();
    }

    /**
     * Stream a CSV export of the outstanding student debt ledger.
     */
    public function exportCsv(): StreamedResponse
    {
        $ledger   = $this->outstandingLedger;
        $filename = 'penalty-collections-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($ledger): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Penalty ID',
                'College',
                'Borrower Name',
                'Borrower Email',
                'Type',
                'Assessed Amount',
                'Paid Amount',
                'Balance Remaining',
                'Status',
            ]);

            foreach ($ledger as $penalty) {
                fputcsv($handle, [
                    $penalty->id,
                    $penalty->organization?->code ?? 'N/A',
                    $penalty->user?->name ?? 'Unknown',
                    $penalty->user?->email ?? '',
                    $penalty->type->label(),
                    number_format((float) $penalty->amount, 2, '.', ''),
                    number_format((float) $penalty->paid_amount, 2, '.', ''),
                    number_format((float) $penalty->balance_remaining, 2, '.', ''),
                    $penalty->status->label(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(): View
    {
        return view('livewire.reports.collection-report');
    }

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
