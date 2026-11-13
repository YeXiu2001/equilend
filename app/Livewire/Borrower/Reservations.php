<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use Livewire\Component;
use App\Models\Reservation;
use Livewire\WithPagination;
use App\Livewire\Traits\Alert;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class Reservations extends Component
{
    use Alert;
    use WithPagination;

    public string $statusFilter = 'all';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function cancelReservation(int $reservationId): void
    {
        $reservation = Reservation::where('user_id', Auth::id())->findOrFail($reservationId);

        if ($reservation->status !== ReservationStatus::Pending) {
            $this->error('Only pending reservation requests can be cancelled.');

            return;
        }

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
        ]);

        $this->success("Reservation #{$reservation->id} has been cancelled.");
    }

    #[Computed]
    public function reservations(): LengthAwarePaginator
    {
        return Reservation::query()
            ->with(['organization', 'item', 'itemUnit'])
            ->where('user_id', Auth::id())
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.borrower.reservations');
    }
}
