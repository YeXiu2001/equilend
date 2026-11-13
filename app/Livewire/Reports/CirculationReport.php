<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Loan;
use Livewire\Component;
use App\Enums\LoanStatus;
use App\Models\Organization;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CirculationReport extends Component
{
    public string $selectedOrganizationId = 'all';

    public string $periodDays = '90';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to circulation metrics.');
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
     * Fetch loans within the selected scope and timeframe.
     *
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function scopedLoans(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Loan::query()
            ->with(['organization', 'item'])
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->when($this->periodDays !== 'all' && is_numeric($this->periodDays), function ($q): void {
                $q->where('borrowed_at', '>=', now()->subDays((int) $this->periodDays));
            })
            ->orderBy('borrowed_at')
            ->get();
    }

    /**
     * Compute circulation KPIs (volume, punctuality, overdue rate, avg turnaround).
     *
     * @return array<string, int|float>
     */
    #[Computed]
    public function summaryStats(): array
    {
        $loans      = $this->scopedLoans;
        $totalLoans = $loans->count();

        $returnedLoans = $loans->filter(
            fn (Loan $loan): bool => $loan->returned_at !== null && in_array($loan->status, [LoanStatus::Returned, LoanStatus::Damaged], true)
        );

        $onTimeCount = $returnedLoans->filter(
            fn (Loan $loan): bool => $loan->returned_at->lte($loan->due_at)
        )->count();

        $lateReturnedCount = $returnedLoans->count() - $onTimeCount;
        $activeOverdue     = $loans->filter(fn (Loan $loan): bool => $loan->isOverdue())->count();

        $returnedTotal   = $returnedLoans->count();
        $punctualityRate = $returnedTotal > 0 ? round(($onTimeCount / $returnedTotal) * 100, 1) : 0.0;
        $overdueRate     = $totalLoans > 0 ? round((($lateReturnedCount + $activeOverdue) / $totalLoans) * 100, 1) : 0.0;

        $avgTurnaroundHours = $returnedTotal > 0
            ? round((float) $returnedLoans->avg(fn (Loan $loan): float => $loan->borrowed_at->diffInMinutes($loan->returned_at) / 60), 1)
            : 0.0;

        return [
            'total_loans'          => $totalLoans,
            'on_time_count'        => $onTimeCount,
            'late_count'           => $lateReturnedCount + $activeOverdue,
            'punctuality_rate'     => $punctualityRate,
            'overdue_rate'         => $overdueRate,
            'avg_turnaround_hours' => $avgTurnaroundHours,
        ];
    }

    /**
     * Monthly loan volume trend.
     *
     * @return array<int, array<string, int|string|float>>
     */
    #[Computed]
    public function monthlyTrends(): array
    {
        $grouped = $this->scopedLoans->groupBy(fn (Loan $loan): string => $loan->borrowed_at->format('Y-m'));
        $max     = max(1, $grouped->map->count()->max() ?? 1);

        $rows = [];

        foreach ($grouped as $monthKey => $monthLoans) {
            $count  = $monthLoans->count();
            $onTime = $monthLoans->filter(fn (Loan $l): bool => $l->returned_at && $l->returned_at->lte($l->due_at))->count();
            $late   = $monthLoans->filter(fn (Loan $l): bool => ($l->returned_at && $l->returned_at->gt($l->due_at)) || $l->isOverdue())->count();
            $rows[] = [
                'month'      => \Illuminate\Support\Carbon::createFromFormat('Y-m', $monthKey)?->format('M Y') ?? $monthKey,
                'month_key'  => $monthKey,
                'count'      => $count,
                'on_time'    => $onTime,
                'late'       => $late,
                'percentage' => round(($count / $max) * 100, 1),
            ];
        }

        return $rows;
    }

    /**
     * Peak borrowing days of the week (Mon-Sun).
     *
     * @return array<int, array<string, int|string|float>>
     */
    #[Computed]
    public function peakDays(): array
    {
        $days    = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $grouped = $this->scopedLoans->groupBy(fn (Loan $loan): string => $loan->borrowed_at->format('D'));
        $max     = max(1, $grouped->map->count()->max() ?? 1);

        return array_map(function (string $day) use ($grouped, $max): array {
            $count = $grouped->get($day)?->count() ?? 0;

            return [
                'day'        => $day,
                'count'      => $count,
                'percentage' => round(($count / $max) * 100, 1),
            ];
        }, $days);
    }

    /**
     * Peak borrowing hours breakdown.
     *
     * @return array<int, array<string, int|string|float>>
     */
    #[Computed]
    public function peakHours(): array
    {
        $buckets = [
            '07:00 - 09:59 (Morning)'   => [7, 9],
            '10:00 - 12:59 (Midday)'    => [10, 12],
            '13:00 - 15:59 (Afternoon)' => [13, 15],
            '16:00 - 19:59 (Late Day)'  => [16, 19],
        ];

        $loans = $this->scopedLoans;
        $rows  = [];

        foreach ($buckets as $label => [$start, $end]) {
            $count  = $loans->filter(fn (Loan $l): bool => $l->borrowed_at->hour >= $start && $l->borrowed_at->hour <= $end)->count();
            $rows[] = [
                'label' => $label,
                'count' => $count,
            ];
        }

        $max = max(1, max(array_column($rows, 'count') ?: [1]));

        return array_map(function (array $row) use ($max): array {
            $row['percentage'] = round(($row['count'] / $max) * 100, 1);

            return $row;
        }, $rows);
    }

    /**
     * Stream a CSV export of monthly circulation trends.
     */
    public function exportCsv(): StreamedResponse
    {
        $trends   = $this->monthlyTrends;
        $filename = 'circulation-metrics-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($trends): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Month',
                'Total Loans',
                'On-Time Returns',
                'Late / Overdue',
            ]);

            foreach ($trends as $row) {
                fputcsv($handle, [
                    $row['month'],
                    $row['count'],
                    $row['on_time'],
                    $row['late'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(): View
    {
        return view('livewire.reports.circulation-report');
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
