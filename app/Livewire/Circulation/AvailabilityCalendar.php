<?php

declare(strict_types=1);

namespace App\Livewire\Circulation;

use Carbon\Carbon;
use App\Models\Item;
use Livewire\Component;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ReservationStatus;
use App\Models\EquipmentSchedule;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use App\Enums\EquipmentScheduleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityCalendar extends Component
{
    public int $year;

    public int $month;

    public string $selectedOrganizationId = 'all';

    public string $selectedCategoryId = 'all';

    public string $selectedItemId = 'all';

    // Event detail modal state
    public bool $eventModal = false;

    public ?string $modalTitle = null;

    public ?string $modalType = null;

    public ?string $modalDates = null;

    public ?string $modalItem = null;

    public ?string $modalUnit = null;

    public ?string $modalBorrowerOrReason = null;

    public ?string $modalCollege = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to availability calendar.');
        }

        if (! $user->hasRole('superadmin')) {
            $userOrg                      = $user->organization;
            $this->selectedOrganizationId = $userOrg ? (string) $userOrg->id : 'all';
        }

        $now         = Carbon::now();
        $this->year  = $now->year;
        $this->month = $now->month;
    }

    public function previousMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function goToToday(): void
    {
        $now         = Carbon::now();
        $this->year  = $now->year;
        $this->month = $now->month;
    }

    public function viewEventDetails(string $type, int $id): void
    {
        if ($type === 'reservation') {
            $res = Reservation::with(['item', 'itemUnit', 'user', 'organization'])->findOrFail($id);

            $this->modalTitle            = $res->purpose;
            $this->modalType             = 'Reservation (Approved)';
            $this->modalDates            = sprintf('%s — %s', $res->start_time->format('M d, Y H:i'), $res->end_time->format('M d, Y H:i'));
            $this->modalItem             = $res->item->name;
            $this->modalUnit             = $res->itemUnit?->asset_tag ?? 'Auto-allocated upon checkout';
            $this->modalBorrowerOrReason = sprintf('Booked by: %s (%s)', $res->user->name, $res->user->email);
            $this->modalCollege          = $res->organization->name;
            $this->eventModal            = true;
        } else {
            $sched = EquipmentSchedule::with(['item', 'itemUnit', 'organization'])->findOrFail($id);

            $this->modalTitle            = $sched->title;
            $this->modalType             = $sched->type->label();
            $this->modalDates            = sprintf('%s — %s', $sched->start_time->format('M d, Y H:i'), $sched->end_time->format('M d, Y H:i'));
            $this->modalItem             = $sched->item?->name ?? 'All Equipment / College-Wide';
            $this->modalUnit             = $sched->itemUnit?->asset_tag ?? 'Campus Facility Block';
            $this->modalBorrowerOrReason = $sched->reason ?? 'Routine maintenance or academic event window.';
            $this->modalCollege          = $sched->organization->name;
            $this->eventModal            = true;
        }
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Category::availableFor($orgId)->orderBy('name')->get();
    }

    #[Computed]
    public function items(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Item::query()
            ->where('is_active', true)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->selectedCategoryId !== 'all' && ! empty($this->selectedCategoryId), function (Builder $q): void {
                $q->where('category_id', (int) $this->selectedCategoryId);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Generate calendar grid array with day numbers, month boundary flags, and events.
     *
     * @return list<array{
     *     date: string,
     *     day_number: int,
     *     is_current_month: bool,
     *     is_today: bool,
     *     events: list<array{
     *         id: int,
     *         type: string,
     *         title: string,
     *         badge_class: string,
     *         source: string,
     *     }>,
     * }>
     */
    #[Computed]
    public function calendarDays(): array
    {
        $firstOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $startOfGrid  = $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfMonth   = $firstOfMonth->copy()->endOfMonth()->endOfDay();
        $endOfGrid    = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $orgId = $this->getEffectiveOrganizationId();

        // 1. Fetch Approved Reservations in this month window
        $reservations = Reservation::query()
            ->with(['item', 'itemUnit'])
            ->whereIn('status', [ReservationStatus::Approved, ReservationStatus::CheckedOut])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->selectedItemId !== 'all' && ! empty($this->selectedItemId), function (Builder $q): void {
                $q->where('item_id', (int) $this->selectedItemId);
            })
            ->when($this->selectedCategoryId !== 'all' && ! empty($this->selectedCategoryId), function (Builder $q): void {
                $q->whereHas('item', fn (Builder $itemQ) => $itemQ->where('category_id', (int) $this->selectedCategoryId));
            })
            ->where(function (Builder $q) use ($startOfGrid, $endOfGrid): void {
                $q->whereBetween('start_time', [$startOfGrid, $endOfGrid])
                    ->orWhereBetween('end_time', [$startOfGrid, $endOfGrid])
                    ->orWhere(function (Builder $inner) use ($startOfGrid, $endOfGrid): void {
                        $inner->where('start_time', '<=', $startOfGrid)
                            ->where('end_time', '>=', $endOfGrid);
                    });
            })
            ->get();

        // 2. Fetch Schedules (Maintenance / Blackout) in this window
        $schedules = EquipmentSchedule::query()
            ->with(['item', 'itemUnit'])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->selectedItemId !== 'all' && ! empty($this->selectedItemId), function (Builder $q): void {
                $q->where(function (Builder $sub) {
                    $sub->where('item_id', (int) $this->selectedItemId)->orWhereNull('item_id');
                });
            })
            ->when($this->selectedCategoryId !== 'all' && ! empty($this->selectedCategoryId), function (Builder $q): void {
                $q->where(function (Builder $sub) {
                    $sub->whereHas('item', fn (Builder $itemQ) => $itemQ->where('category_id', (int) $this->selectedCategoryId))
                        ->orWhereNull('item_id');
                });
            })
            ->where(function (Builder $q) use ($startOfGrid, $endOfGrid): void {
                $q->whereBetween('start_time', [$startOfGrid, $endOfGrid])
                    ->orWhereBetween('end_time', [$startOfGrid, $endOfGrid])
                    ->orWhere(function (Builder $inner) use ($startOfGrid, $endOfGrid): void {
                        $inner->where('start_time', '<=', $startOfGrid)
                            ->where('end_time', '>=', $endOfGrid);
                    });
            })
            ->get();

        $days     = [];
        $cursor   = $startOfGrid->copy();
        $todayStr = Carbon::today()->toDateString();

        while ($cursor->lte($endOfGrid)) {
            $dayStart  = $cursor->copy()->startOfDay();
            $dayEnd    = $cursor->copy()->endOfDay();
            $dayEvents = [];

            // Match reservations
            foreach ($reservations as $res) {
                if ($res->start_time->lte($dayEnd) && $res->end_time->gte($dayStart)) {
                    $unitTag     = $res->itemUnit ? " ({$res->itemUnit->asset_tag})" : '';
                    $dayEvents[] = [
                        'id'          => $res->id,
                        'type'        => 'reservation',
                        'title'       => "Res: {$res->item->name}{$unitTag}",
                        'badge_class' => 'bg-info/20 text-info border border-info/40',
                        'source'      => 'reservation',
                    ];
                }
            }

            // Match schedules
            foreach ($schedules as $sched) {
                if ($sched->start_time->lte($dayEnd) && $sched->end_time->gte($dayStart)) {
                    $badgeClass = $sched->type === EquipmentScheduleType::Maintenance
                        ? 'bg-warning/20 text-warning border border-warning/40'
                        : 'bg-error/20 text-error border border-error/40';

                    $dayEvents[] = [
                        'id'          => $sched->id,
                        'type'        => $sched->type->value,
                        'title'       => $sched->title,
                        'badge_class' => $badgeClass,
                        'source'      => 'schedule',
                    ];
                }
            }

            $days[] = [
                'date'             => $cursor->toDateString(),
                'day_number'       => $cursor->day,
                'is_current_month' => $cursor->month === $this->month,
                'is_today'         => $cursor->toDateString() === $todayStr,
                'events'           => $dayEvents,
            ];

            $cursor->addDay();
        }

        return $days;
    }

    public function render(): View
    {
        return view('livewire.circulation.availability-calendar', [
            'currentMonthName' => Carbon::createFromDate($this->year, $this->month, 1)->format('F Y'),
        ]);
    }

    protected function getEffectiveOrganizationId(): ?int
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            return $user->organization?->id;
        }

        if ($this->selectedOrganizationId === 'all' || empty($this->selectedOrganizationId)) {
            return null;
        }

        return (int) $this->selectedOrganizationId;
    }
}
