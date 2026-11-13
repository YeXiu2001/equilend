<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\OrganizationPaymentMethod;
use Illuminate\Database\Eloquent\Collection;

class Penalties extends Component
{
    #[Computed]
    public function penalties(): Collection
    {
        return Penalty::query()
            ->with(['organization', 'itemUnit.item', 'loan'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    #[Computed]
    public function payments(): Collection
    {
        return Payment::query()
            ->with(['organization', 'paymentMethod', 'penalty'])
            ->where('user_id', Auth::id())
            ->latest('paid_at')
            ->get();
    }

    #[Computed]
    public function paymentChannels(): Collection
    {
        $user  = Auth::user();
        $orgId = $user?->organization?->id;

        if (! $orgId) {
            $firstPenalty = $this->penalties->first();
            $orgId        = $firstPenalty?->organization_id;
        }

        if (! $orgId) {
            return new Collection();
        }

        return OrganizationPaymentMethod::query()
            ->with('paymentMethod')
            ->where('organization_id', $orgId)
            ->enabled()
            ->get();
    }

    public function render(): View
    {
        return view('livewire.borrower.penalties');
    }
}
