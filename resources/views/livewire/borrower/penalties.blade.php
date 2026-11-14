<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Borrower Portal'), 'icon' => 'heroicon-o-user-circle'],
        ['label' => __('My Penalties & Dues'), 'icon' => 'heroicon-o-receipt-percent'],
    ]" />

    @php
        $user = auth()->user();
        $outstanding = $user ? $user->getOutstandingFinesTotal() : 0.0;
        $canBorrow = $user ? $user->canBorrow() : true;
    @endphp

    <!-- Outstanding Balance Summary Banner -->
    <div class="card {{ $outstanding > 0 ? 'bg-error/10 border-error/40' : 'bg-success/10 border-success/40' }} card-border shadow-xs mt-2">
        <div class="card-body p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider {{ $outstanding > 0 ? 'text-error' : 'text-success' }}">
                    {{ $outstanding > 0 ? __('Outstanding Penalty Balance') : __('Account Clear') }}
                </span>
                <div class="text-2xl font-bold font-mono mt-0.5 text-base-content">
                    ₱{{ number_format($outstanding, 2) }}
                </div>
                @if (! $canBorrow)
                    <p class="text-xs text-error font-semibold mt-1">
                        {{ __('Borrowing Locked:') }} {{ implode(' ', $user->getBorrowingBlockReasons()) }}
                    </p>
                @else
                    <p class="text-xs text-base-content/70 mt-1">
                        {{ __('Your account is in good standing with no blocking dues.') }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($this->unsettledPenalties->isNotEmpty() && $this->paymentChannels->isNotEmpty())
                    <button type="button" wire:click="openPayModal" class="btn btn-primary btn-sm font-semibold gap-1">
                        <x-heroicon-o-credit-card class="w-4 h-4" />
                        <span>{{ __('Pay Online') }}</span>
                    </button>
                @elseif (! empty($this->pendingPenaltyIds))
                    <span class="badge badge-warning font-semibold gap-1 whitespace-nowrap">
                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                        {{ __('Verification Pending') }}
                    </span>
                @endif
                <span class="badge {{ $canBorrow ? 'badge-success' : 'badge-error' }} font-semibold whitespace-nowrap">
                    {{ $canBorrow ? __('Eligible to Borrow') : __('Suspended') }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-3">
        <!-- Left 2 Cols: Fine Breakdown & Payment Receipts -->
        <div class="lg:col-span-2 space-y-4">
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3 sm:p-4">
                    <h2 class="font-bold text-sm text-base-content mb-2">{{ __('Penalty Breakdown') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-sm text-xs">
                            <thead>
                                <tr class="text-base-content/70">
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Loan #') }}</th>
                                    <th>{{ __('Details / Reason') }}</th>
                                    <th class="text-right">{{ __('Amount') }}</th>
                                    <th class="text-right">{{ __('Balance') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->penalties as $penalty)
                                    @php
                                        $isPayable = $penalty->isPayableByBorrower();
                                        $isAwaitingReturn = $penalty->loan_id !== null
                                            && $penalty->loan?->returned_at === null
                                            && in_array($penalty->status, [\App\Enums\PenaltyStatus::Unpaid, \App\Enums\PenaltyStatus::PartiallyPaid], true);
                                        $isPendingApproval = $penalty->status === \App\Enums\PenaltyStatus::PendingApproval;
                                        $isPendingVerification = in_array($penalty->id, $this->pendingPenaltyIds, true);
                                    @endphp
                                    <tr class="hover:bg-base-200/40">
                                        <td class="whitespace-nowrap">{{ $penalty->created_at->format('M d, Y') }}</td>
                                        <td><span class="text-xs font-semibold whitespace-nowrap {{ $penalty->type->textClass() }}">{{ $penalty->type->label() }}</span></td>
                                        <td class="whitespace-nowrap">
                                            @if ($penalty->loan)
                                                <button type="button" wire:click="openLoanModal({{ $penalty->loan_id }})"
                                                    class="link link-primary font-mono font-semibold inline-flex items-center gap-1"
                                                    title="{{ __('View loan details') }}">
                                                    <span>#{{ $penalty->loan_id }}</span>
                                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                                </button>
                                                @if ($penalty->loan->item)
                                                    <div class="text-[10px] text-base-content/60 truncate max-w-28">{{ $penalty->loan->item->name }}</div>
                                                @endif
                                            @else
                                                <span class="text-base-content/40">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-base-content/80">{{ $penalty->assessment_notes ?: '—' }}</div>
                                            @if ($penalty->itemUnit)
                                                <span class="text-[10px] font-mono text-primary">{{ $penalty->itemUnit->asset_tag }}</span>
                                            @endif
                                            @if ($penalty->waived_reason)
                                                <div class="text-[10px] italic text-info mt-0.5">{{ __('Waiver Note: :reason', ['reason' => $penalty->waived_reason]) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-right font-mono">₱{{ number_format((float) $penalty->amount, 2) }}</td>
                                        <td class="text-right font-mono font-bold {{ (float) $penalty->balance_remaining > 0 ? 'text-error' : 'text-success' }}">
                                            ₱{{ number_format((float) $penalty->balance_remaining, 2) }}
                                        </td>
                                        <td><span class="badge badge-xs whitespace-nowrap {{ $penalty->status->badgeClass() }}">{{ $penalty->status->label() }}</span></td>
                                        <td class="text-right whitespace-nowrap">
                                            @if ($isPendingVerification)
                                                <span class="text-xs font-semibold text-warning whitespace-nowrap">{{ __('Verifying') }}</span>
                                            @elseif ($isPendingApproval)
                                                <span class="text-xs font-medium text-base-content/60 whitespace-nowrap">{{ __('Awaiting Approval') }}</span>
                                            @elseif ($isAwaitingReturn)
                                                <span class="text-xs font-semibold text-warning whitespace-nowrap">{{ __('Return Item First') }}</span>
                                            @elseif ($isPayable && $this->paymentChannels->isNotEmpty())
                                                <button type="button" wire:click="openPayModal({{ $penalty->id }})" class="btn btn-xs btn-primary font-semibold">
                                                    {{ __('Pay') }}
                                                </button>
                                            @elseif ($isPayable)
                                                <span class="text-xs font-medium text-base-content/60 whitespace-nowrap">{{ __('Over the Counter') }}</span>
                                            @else
                                                <span class="text-base-content/40">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-6 text-base-content/60">
                                            {{ __('No penalties recorded on your account.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-3 sm:p-4">
                    <h2 class="font-bold text-sm text-base-content mb-2">{{ __('Settlement & Receipt History') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-sm text-xs">
                            <thead>
                                <tr class="text-base-content/70">
                                    <th>{{ __('Receipt #') }}</th>
                                    <th>{{ __('Channel') }}</th>
                                    <th>{{ __('Reference #') }}</th>
                                    <th class="text-right">{{ __('Paid') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Proof') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->payments as $payment)
                                    <tr>
                                        <td class="font-mono font-bold text-primary">{{ $payment->receipt_number }}</td>
                                        <td>{{ $payment->paymentMethod?->name ?? __('Cash') }}</td>
                                        <td class="font-mono text-[11px]">{{ $payment->reference_number ?: '—' }}</td>
                                        <td class="text-right font-mono font-bold text-success">₱{{ number_format((float) $payment->amount_paid, 2) }}</td>
                                        <td>{{ $payment->paid_at->format('M d, Y H:i') }}</td>
                                        <td><span class="badge badge-xs whitespace-nowrap {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></td>
                                        <td class="text-right">
                                            @if ($payment->proof_url)
                                                <button type="button" wire:click="openProofModal('{{ $payment->proof_url }}')" class="btn btn-xs btn-ghost text-primary font-semibold">
                                                    {{ __('View') }}
                                                </button>
                                            @else
                                                <span class="text-base-content/40">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-6 text-base-content/60">
                                            {{ __('No payment receipts recorded yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Official College Payment Instructions -->
        <div>
            <div class="card bg-base-100 card-border border-base-300 shadow-xs">
                <div class="card-body p-4 text-xs">
                    <h3 class="font-bold text-sm text-base-content flex items-center gap-1.5 border-b border-base-200 pb-2">
                        <x-heroicon-o-building-library class="w-4 h-4 text-primary" />
                        <span>{{ __('Official Settlement Guide') }}</span>
                    </h3>
                    <p class="text-base-content/70 mt-2">
                        {{ __('Pay through any of the official digital channels below, then upload your payment receipt image for admin verification.') }}
                    </p>

                    <div class="space-y-2.5 mt-3">
                        @forelse ($this->paymentChannels as $channel)
                            <div class="bg-base-200/60 p-3 rounded-box border border-base-300">
                                <div class="font-bold text-primary">{{ $channel->paymentMethod->name }}</div>
                                <div class="text-base-content/80 mt-0.5">{{ __('Account:') }} <span class="font-semibold">{{ $channel->account_name }}</span></div>
                                @if (! empty($channel->account_numbers))
                                    <div class="font-mono font-bold text-base-content mt-1">
                                        {{ implode(' / ', $channel->account_numbers) }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="bg-warning/10 border border-warning/30 p-3 rounded-box text-base-content/80 space-y-1">
                                <div class="font-semibold text-warning">{{ __('Online Payment Not Available') }}</div>
                                <p>{{ __('This organization does not allow online payment. Please settle your penalty in cash over the counter at your college circulation desk.') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($this->unsettledPenalties->isNotEmpty() && $this->paymentChannels->isNotEmpty())
                        <button type="button" wire:click="openPayModal" class="btn btn-primary btn-sm btn-block mt-4 font-semibold gap-1">
                            <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                            <span>{{ __('Pay All & Upload Receipt') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Online Payment & Receipt Upload Modal -->
    <dialog class="modal" @if ($payModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base">
                {{ $payScope === 'all' ? __('Pay All Unsettled Penalties') : __('Pay Penalty #:id', ['id' => $selectedPenaltyId]) }}
            </h3>
            <p class="text-xs text-base-content/60 mt-0.5">
                {{ __('Transfer the amount to the selected payment channel and upload a clear screenshot or photo of your receipt.') }}
            </p>

            <div class="space-y-3 mt-3 text-xs">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Payment Channel') }} *</legend>
                    <select wire:model.live="selectedChannelId" class="select select-sm w-full @error('selectedChannelId') select-error @enderror">
                        @foreach ($this->paymentChannels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->paymentMethod->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedChannelId') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                @if ($this->selectedChannel)
                    <div class="bg-base-200/70 p-3 rounded-box border border-base-300 space-y-1">
                        <div class="font-bold text-primary">{{ $this->selectedChannel->paymentMethod->name }}</div>
                        <div>{{ __('Account Name:') }} <span class="font-semibold">{{ $this->selectedChannel->account_name }}</span></div>
                        @if (! empty($this->selectedChannel->account_numbers))
                            <div>
                                {{ __('Account / Mobile #:') }}
                                <span class="font-mono font-bold">{{ implode(' / ', $this->selectedChannel->account_numbers) }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">
                        {{ $payScope === 'all' ? __('Total Amount to Pay (₱)') : __('Amount to Pay (₱)') }}
                    </legend>
                    @if ($payScope === 'all')
                        <input type="text" value="₱{{ number_format((float) $paymentAmount, 2) }} ({{ $this->unsettledPenalties->count() }} {{ __('penalties') }})" class="input input-sm w-full font-mono font-bold bg-base-200" readonly />
                    @else
                        <input type="text" value="₱{{ number_format((float) $paymentAmount, 2) }}" class="input input-sm w-full font-mono font-bold bg-base-200" readonly />
                    @endif
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Reference Number') }}</legend>
                    <input type="text" wire:model="referenceNumber" class="input input-sm w-full @error('referenceNumber') input-error @enderror" placeholder="{{ __('e.g. GCash / Maya Ref # (optional)') }}" />
                    @error('referenceNumber') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Receipt Image') }} *</legend>
                    <input type="file" wire:model="receiptImage" accept="image/*" class="file-input file-input-sm w-full @error('receiptImage') file-input-error @enderror" />
                    <div wire:loading wire:target="receiptImage" class="text-[11px] text-primary mt-1 flex items-center gap-1">
                        <span class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Uploading receipt image...') }}</span>
                    </div>
                    @error('receiptImage') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                @if ($receiptImage && ! $errors->has('receiptImage'))
                    <div class="rounded-box border border-base-300 overflow-hidden bg-base-200/40 p-2">
                        <img src="{{ $receiptImage->temporaryUrl() }}" alt="{{ __('Receipt preview') }}" class="max-h-44 mx-auto rounded object-contain" />
                    </div>
                @endif

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Notes') }}</legend>
                    <input type="text" wire:model="paymentNotes" class="input input-sm w-full" placeholder="{{ __('Optional notes for the administrator...') }}" />
                </fieldset>
            </div>

            <div class="modal-action">
                <button type="button" wire:click="$set('payModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                <button type="button" wire:click="submitOnlinePayment" wire:loading.attr="disabled" wire:target="submitOnlinePayment,receiptImage" class="btn btn-sm btn-primary gap-1">
                    <span wire:loading wire:target="submitOnlinePayment" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Submit Payment Receipt') }}</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('payModal', false)"></div>
    </dialog>

    <!-- View Receipt Proof Modal -->
    <dialog class="modal" @if ($proofModal && $viewingProofUrl) open @endif>
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-base mb-3">{{ __('Uploaded Payment Receipt') }}</h3>
            @if ($viewingProofUrl)
                <div class="bg-base-200/50 rounded-box p-2 border border-base-300">
                    <img src="{{ $viewingProofUrl }}" alt="{{ __('Payment Receipt') }}" class="max-h-96 mx-auto rounded object-contain" />
                </div>
            @endif
            <div class="modal-action">
                @if ($viewingProofUrl)
                    <a href="{{ $viewingProofUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-ghost">{{ __('Open Full Size') }}</a>
                @endif
                <button type="button" wire:click="$set('proofModal', false)" class="btn btn-sm btn-primary">{{ __('Close') }}</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('proofModal', false)"></div>
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
                    <span class="badge badge-sm whitespace-nowrap {{ $vl->status->badgeClass() }} font-semibold">{{ $vl->status->label() }}</span>
                </div>

                <div class="space-y-2 text-xs py-4">
                    <div class="flex justify-between gap-3">
                        <span class="text-base-content/60">{{ __('Equipment') }}</span>
                        <span class="font-bold text-right">{{ $vl->item?->name ?? '—' }}</span>
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
