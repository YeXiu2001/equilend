<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Finance & Penalties'), 'icon' => 'heroicon-o-banknotes'],
        ['label' => __('Payments & Receipts'), 'icon' => 'heroicon-o-credit-card'],
    ]">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasRole('superadmin'))
                    <select wire:model.live="selectedOrganizationId" class="select select-sm select-bordered w-44 text-xs font-semibold">
                        <option value="all">{{ __('All Colleges') }}</option>
                        @foreach ($this->organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->code }} - {{ $org->name }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-base-content/80 bg-base-200 border border-base-300 px-2.5 py-1 rounded-md whitespace-nowrap">
                        <x-heroicon-o-academic-cap class="w-3.5 h-3.5" />
                        {{ auth()->user()?->organization?->code ?? 'Hub' }}
                    </span>
                @endif

                <button type="button" wire:click="openRecordModal" class="btn btn-success btn-sm font-semibold gap-1">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span>{{ __('Record Payment') }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <div class="card bg-base-100 card-border border-base-300 shadow-xs mt-2">
        <div class="card-body p-3 sm:p-4">
            <div class="flex flex-col sm:flex-row gap-2 mb-3">
                <label class="input input-sm flex items-center gap-2 flex-1">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search receipt #, reference #, or payer name...') }}"
                        class="grow text-xs" />
                </label>

                <select wire:model.live="methodFilter" class="select select-sm w-full sm:w-40 text-xs">
                    <option value="all">{{ __('All Channels') }}</option>
                    @foreach ($this->paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="statusFilter" class="select select-sm w-full sm:w-40 text-xs">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="verified">{{ __('Verified') }}</option>
                    <option value="pending_verification">{{ __('Pending Verification') }}</option>
                    <option value="voided">{{ __('Voided') }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm text-xs">
                    <thead>
                        <tr class="text-base-content/70">
                            <th>{{ __('Receipt #') }}</th>
                            <th>{{ __('Loan #') }}</th>
                            <th>{{ __('Payer') }}</th>
                            <th>{{ __('College') }}</th>
                            <th>{{ __('Channel') }}</th>
                            <th>{{ __('Reference #') }}</th>
                            <th class="text-right">{{ __('Amount Paid') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-right">{{ __('Receipt') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->payments as $payment)
                            <tr class="hover:bg-base-200/40">
                                <td class="font-mono font-bold text-primary">{{ $payment->receipt_number }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($payment->penalty?->loan_id)
                                        <button type="button" wire:click="openLoanModal({{ $payment->penalty->loan_id }})"
                                            class="link link-primary font-mono font-semibold inline-flex items-center gap-1"
                                            title="{{ __('View loan details') }}">
                                            <span>#{{ $payment->penalty->loan_id }}</span>
                                            <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                        </button>
                                        @if ($payment->penalty->loan?->item)
                                            <div class="text-[10px] text-base-content/60 truncate max-w-28">{{ $payment->penalty->loan->item->name }}</div>
                                        @endif
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-bold text-base-content">{{ $payment->user->name }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ $payment->user->email }}</div>
                                </td>
                                <td>
                                    <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $payment->organization->code }}</span>
                                </td>
                                <td>{{ $payment->paymentMethod?->name ?? __('Cash') }}</td>
                                <td class="font-mono text-[11px]">{{ $payment->reference_number ?: '—' }}</td>
                                <td class="text-right font-mono font-bold text-success">
                                    ₱{{ number_format((float) $payment->amount_paid, 2) }}
                                </td>
                                <td class="text-base-content/70">{{ $payment->paid_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <span class="badge badge-xs whitespace-nowrap {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span>
                                </td>
                                <td class="text-right">
                                    <button type="button" wire:click="viewReceipt({{ $payment->id }})"
                                        class="btn btn-xs {{ $payment->status === \App\Enums\PaymentStatus::PendingVerification ? 'btn-warning' : 'btn-ghost text-primary' }} font-semibold">
                                        {{ $payment->status === \App\Enums\PaymentStatus::PendingVerification ? __('Review') : __('View') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-8 text-base-content/60">
                                    {{ __('No payment receipts found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->payments->links() }}
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <dialog class="modal" @if ($recordModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base">{{ __('Record Penalty Collection') }}</h3>
            <div class="space-y-3 mt-3 text-xs">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Unsettled Penalty') }} *</legend>
                    <select wire:model.live="selectedPenaltyId" class="select select-sm w-full">
                        <option value="">{{ __('Select student penalty...') }}</option>
                        @foreach ($this->unsettledPenalties as $p)
                            <option value="{{ $p->id }}">
                                #{{ $p->id }} — {{ $p->user->name }} ({{ $p->organization->code }} • Bal: ₱{{ number_format((float) $p->balance_remaining, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('selectedPenaltyId') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Payment Channel') }} *</legend>
                        <select wire:model.live="paymentMethodId" class="select select-sm w-full">
                            @foreach ($this->paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('paymentMethodId') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Amount (₱)') }} *</legend>
                        <input type="number" step="0.01" min="0.01" wire:model="amountPaid" class="input input-sm w-full bg-base-200 font-mono font-bold" readonly />
                        @error('amountPaid') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Reference Number (GCash / Maya)') }}</legend>
                    <input type="text" wire:model="referenceNumber" class="input input-sm w-full" placeholder="{{ __('Required for digital wallet transfers') }}" />
                    @error('referenceNumber') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Notes') }}</legend>
                    <input type="text" wire:model="notes" class="input input-sm w-full" placeholder="{{ __('Optional collection remarks...') }}" />
                </fieldset>
            </div>
            <div class="modal-action">
                <button type="button" wire:click="$set('recordModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                <button type="button" wire:click="submitPayment" class="btn btn-sm btn-success">{{ __('Confirm & Issue Receipt') }}</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('recordModal', false)"></div>
    </dialog>

    <!-- View Official Receipt Modal -->
    <dialog class="modal" @if ($receiptModal && $this->viewingPayment) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->viewingPayment)
                @php
                    $rp = $this->viewingPayment;
                    $isPending = $rp->status === \App\Enums\PaymentStatus::PendingVerification;
                @endphp
                <div class="text-center border-b border-base-300 pb-3">
                    <span class="badge {{ $rp->status->badgeClass() }} badge-sm font-semibold mb-1 whitespace-nowrap">
                        {{ $isPending ? __('PENDING VERIFICATION') : __('OFFICIAL RECEIPT') }}
                    </span>
                    <h3 class="font-mono font-bold text-lg text-primary">{{ $rp->receipt_number }}</h3>
                    <p class="text-xs text-base-content/60">{{ $rp->organization->name }} ({{ $rp->organization->code }})</p>
                </div>

                <div class="space-y-2 text-xs py-4">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Payer') }}</span>
                        <span class="font-bold">{{ $rp->user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Penalty Reference') }}</span>
                        <span class="font-semibold">#{{ $rp->penalty_id }} ({{ $rp->penalty->type->label() }})</span>
                    </div>
                    @if ($rp->penalty?->loan_id)
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Loan #') }}</span>
                            <button type="button" wire:click="openLoanModal({{ $rp->penalty->loan_id }})" class="link link-primary font-mono font-semibold">
                                #{{ $rp->penalty->loan_id }}
                            </button>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Channel') }}</span>
                        <span>{{ $rp->paymentMethod?->name ?? __('Cash') }}</span>
                    </div>
                    @if ($rp->reference_number)
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Ref #') }}</span>
                            <span class="font-mono">{{ $rp->reference_number }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Date Paid') }}</span>
                        <span>{{ $rp->paid_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">{{ __('Received By') }}</span>
                        <span>{{ $rp->receivedBy?->name ?? ($isPending ? __('Awaiting Verification') : __('System')) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-base-300 pt-2 text-sm font-bold">
                        <span>{{ __('Amount Paid') }}</span>
                        <span class="text-success font-mono">₱{{ number_format((float) $rp->amount_paid, 2) }}</span>
                    </div>

                    @if ($rp->proof_url)
                        <div class="bg-base-200/40 rounded-box p-2 border border-base-300 text-center mt-2">
                            <img src="{{ $rp->proof_url }}" alt="{{ __('Payment Receipt') }}" class="max-h-64 mx-auto rounded object-contain" />
                            <a href="{{ $rp->proof_url }}" target="_blank" rel="noopener noreferrer" class="link link-primary text-[11px] mt-1 inline-block">
                                {{ __('Open full size image') }}
                            </a>
                        </div>
                    @endif

                    @if ($isPending)
                        <fieldset class="fieldset w-full pt-2">
                            <legend class="fieldset-legend font-semibold">{{ __('Rejection Reason (if Not Paid)') }}</legend>
                            <input type="text" wire:model="rejectReason" class="input input-sm w-full" placeholder="{{ __('e.g. Invalid or unreadable receipt') }}" />
                        </fieldset>
                    @endif
                </div>
            @endif
            <div class="modal-action flex-wrap gap-2">
                @if ($this->viewingPayment && $this->viewingPayment->status === \App\Enums\PaymentStatus::PendingVerification)
                    <button type="button" wire:click="$set('receiptModal', false)" class="btn btn-sm btn-ghost">{{ __('Close') }}</button>
                    <button type="button" wire:click="rejectPayment({{ $this->viewingPayment->id }})" class="btn btn-sm btn-error btn-outline">{{ __('Not Paid (Reject)') }}</button>
                    <button type="button" wire:click="confirmPayment({{ $this->viewingPayment->id }})" class="btn btn-sm btn-success">{{ __('Confirm Paid') }}</button>
                @else
                    <button type="button" wire:click="$set('receiptModal', false)" class="btn btn-sm btn-primary">{{ __('Close') }}</button>
                @endif
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('receiptModal', false)"></div>
    </dialog>

    <!-- Loan Details Modal -->
    <dialog class="modal" @if ($loanModal && $this->viewingLoan) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->viewingLoan)
                @php $vl = $this->viewingLoan; @endphp
                <div class="flex items-start justify-between gap-2 border-b border-base-300 pb-3">
                    <div>
                        <h3 class="font-bold text-base font-mono text-primary">{{ __('Loan #:id', ['id' => $vl->id]) }}</h3>
                        <p class="text-xs text-base-content/60">{{ $vl->organization?->name }} ({{ $vl->organization?->code }})</p>
                    </div>
                    <span class="badge badge-sm {{ $vl->status->badgeClass() }} font-semibold">{{ $vl->status->label() }}</span>
                </div>

                <div class="space-y-2 text-xs py-4">
                    <div class="flex justify-between gap-3">
                        <span class="text-base-content/60">{{ __('Borrower') }}</span>
                        <span class="font-bold text-right">{{ $vl->user?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-base-content/60">{{ __('Equipment') }}</span>
                        <span class="font-semibold text-right">{{ $vl->item?->name ?? '—' }}</span>
                    </div>
                    @if ($vl->item?->brand || $vl->item?->model_number)
                        <div class="flex justify-between gap-3">
                            <span class="text-base-content/60">{{ __('Brand / Model') }}</span>
                            <span class="text-right">{{ trim(($vl->item?->brand ?? '').' '.($vl->item?->model_number ?? '')) }}</span>
                        </div>
                    @endif
                    @if ($vl->itemUnit)
                        <div class="flex justify-between gap-3">
                            <span class="text-base-content/60">{{ __('Asset Tag') }}</span>
                            <span class="font-mono font-semibold">{{ $vl->itemUnit->asset_tag }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3 border-t border-base-200 pt-2">
                        <span class="text-base-content/60">{{ __('Borrowed At') }}</span>
                        <span>{{ $vl->borrowed_at?->format('M d, Y h:i A') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-base-content/60">{{ __('Due Date') }}</span>
                        <span class="font-semibold">{{ $vl->due_at?->format('M d, Y h:i A') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-base-content/60">{{ __('Returned At') }}</span>
                        <span>{{ $vl->returned_at?->format('M d, Y h:i A') ?? __('Not returned yet') }}</span>
                    </div>
                    @if ($vl->checkout_condition || $vl->checkin_condition)
                        <div class="flex justify-between gap-3 border-t border-base-200 pt-2">
                            <span class="text-base-content/60">{{ __('Condition (Out / In)') }}</span>
                            <span>
                                {{ $vl->checkout_condition?->label() ?? '—' }} / {{ $vl->checkin_condition?->label() ?? '—' }}
                            </span>
                        </div>
                    @endif
                    @if ($vl->issuedBy)
                        <div class="flex justify-between gap-3">
                            <span class="text-base-content/60">{{ __('Issued By') }}</span>
                            <span>{{ $vl->issuedBy->name }}</span>
                        </div>
                    @endif
                    @if ($vl->receivedBy)
                        <div class="flex justify-between gap-3">
                            <span class="text-base-content/60">{{ __('Received By') }}</span>
                            <span>{{ $vl->receivedBy->name }}</span>
                        </div>
                    @endif
                    @if ($vl->notes)
                        <div class="bg-base-200/60 p-2.5 rounded-box border border-base-300 mt-2">
                            <span class="font-semibold block text-base-content/70 mb-0.5">{{ __('Loan Notes') }}</span>
                            <p class="text-base-content/80">{{ $vl->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
            <div class="modal-action">
                <button type="button" wire:click="$set('loanModal', false)" class="btn btn-sm btn-primary">{{ __('Close') }}</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('loanModal', false)"></div>
    </dialog>
</div>
