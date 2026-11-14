<div class="p-1 sm:p-2 w-full min-w-0">
    <x-breadcrumbs :items="[
        ['label' => __('Finance & Penalties'), 'icon' => 'heroicon-o-banknotes'],
        ['label' => __('Penalties & Fines'), 'icon' => 'heroicon-o-exclamation-triangle'],
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
            </div>
        </x-slot:actions>
    </x-breadcrumbs>

    <!-- Summary KPI Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Outstanding Balance') }}</span>
                <span class="text-lg font-bold text-error">₱{{ number_format($this->summaryStats['outstanding_total'], 2) }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Unsettled Cases') }}</span>
                <span class="text-lg font-bold text-warning">{{ $this->summaryStats['unsettled_count'] }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Total Collected') }}</span>
                <span class="text-lg font-bold text-success">₱{{ number_format($this->summaryStats['collected_total'], 2) }}</span>
            </div>
        </div>
        <div class="card bg-base-100 card-border border-base-300 shadow-2xs">
            <div class="card-body p-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-base-content/60">{{ __('Waived Fines') }}</span>
                <span class="text-lg font-bold text-info">{{ $this->summaryStats['waived_count'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card bg-base-100 card-border border-base-300 shadow-xs">
        <div class="card-body p-3 sm:p-4">
            <div class="flex flex-col sm:flex-row gap-2 mb-3">
                <label class="input input-sm flex items-center gap-2 flex-1">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 opacity-60" />
                    <input type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search borrower, notes, or unit asset tag...') }}"
                        class="grow text-xs" />
                </label>

                <select wire:model.live="typeFilter" class="select select-sm w-full sm:w-36 text-xs">
                    <option value="all">{{ __('All Types') }}</option>
                    <option value="overdue">{{ __('Overdue') }}</option>
                    <option value="damage">{{ __('Damage') }}</option>
                    <option value="loss">{{ __('Loss') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>

                <select wire:model.live="statusFilter" class="select select-sm w-full sm:w-40 text-xs">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="pending_approval">{{ __('Pending Approval') }}</option>
                    <option value="unpaid">{{ __('Unpaid') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="waived">{{ __('Waived') }}</option>
                    <option value="voided">{{ __('Voided') }}</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm text-xs">
                    <thead>
                        <tr class="text-base-content/70">
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('College') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Notes / Unit') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                            <th class="text-right">{{ __('Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->penalties as $penalty)
                            <tr class="hover:bg-base-200/40">
                                <td class="font-mono font-semibold">#{{ $penalty->id }}</td>
                                <td>
                                    <div class="font-bold text-base-content">{{ $penalty->user->name }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ $penalty->user->email }}</div>
                                </td>
                                <td>
                                    <span class="text-[11px] font-mono font-semibold text-base-content/75 bg-base-200 px-1.5 py-0.5 rounded whitespace-nowrap">{{ $penalty->organization->code }}</span>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold whitespace-nowrap {{ $penalty->type->textClass() }}">{{ $penalty->type->label() }}</span>
                                </td>
                                <td class="max-w-xs">
                                    <div class="line-clamp-2 text-base-content/80">{{ $penalty->assessment_notes ?: '—' }}</div>
                                    @if ($penalty->itemUnit)
                                        <span class="text-[10px] font-mono text-primary">{{ $penalty->itemUnit->asset_tag }}</span>
                                    @endif
                                    @if ($penalty->waived_reason)
                                        <div class="text-[10px] italic text-info mt-0.5">{{ __('Reason: :reason', ['reason' => $penalty->waived_reason]) }}</div>
                                    @endif
                                </td>
                                <td class="text-right font-mono">₱{{ number_format((float) $penalty->amount, 2) }}</td>
                                <td class="text-right font-mono font-bold {{ (float) $penalty->balance_remaining > 0 ? 'text-error' : 'text-success' }}">
                                    ₱{{ number_format((float) $penalty->balance_remaining, 2) }}
                                </td>
                                <td>
                                    @php
                                        $hasPendingProof = $penalty->payments->contains('status', \App\Enums\PaymentStatus::PendingVerification);
                                    @endphp
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="badge badge-xs whitespace-nowrap {{ $penalty->status->badgeClass() }}">{{ $penalty->status->label() }}</span>
                                        @if ($hasPendingProof)
                                            <span class="badge badge-xs badge-warning font-semibold whitespace-nowrap">{{ __('Proof Pending') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">
                                    @if ($penalty->status === \App\Enums\PenaltyStatus::PendingApproval)
                                        <div class="flex justify-end gap-1">
                                            <button type="button" wire:click="openApproveModal({{ $penalty->id }})"
                                                class="btn btn-xs btn-primary font-semibold">
                                                {{ __('Approve') }}
                                            </button>
                                            <button type="button" wire:click="openWaiveModal({{ $penalty->id }}, 'void')"
                                                class="btn btn-xs btn-ghost text-error font-semibold">
                                                {{ __('Void') }}
                                            </button>
                                        </div>
                                    @elseif (in_array($penalty->status, [\App\Enums\PenaltyStatus::Unpaid, \App\Enums\PenaltyStatus::PartiallyPaid], true))
                                        <div class="flex justify-end gap-1">
                                            @if ($hasPendingProof)
                                                <button type="button" wire:click="openVerifyModal({{ $penalty->id }})"
                                                    class="btn btn-xs btn-warning font-semibold">
                                                    {{ __('Verify Receipt') }}
                                                </button>
                                            @endif
                                            <button type="button" wire:click="openPaymentModal({{ $penalty->id }})"
                                                class="btn btn-xs btn-success font-semibold">
                                                {{ __('Collect') }}
                                            </button>
                                            <button type="button" wire:click="openWaiveModal({{ $penalty->id }}, 'waive')"
                                                class="btn btn-xs btn-ghost text-info font-semibold">
                                                {{ __('Waive') }}
                                            </button>
                                            <button type="button" wire:click="openWaiveModal({{ $penalty->id }}, 'void')"
                                                class="btn btn-xs btn-ghost text-error font-semibold">
                                                {{ __('Void') }}
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-base-content/50">{{ __('Closed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-8 text-base-content/60">
                                    {{ __('No penalties found matching your filters.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $this->penalties->links() }}
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <dialog class="modal" @if ($paymentModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base">{{ __('Record Penalty Payment') }}</h3>
            <div class="space-y-3 mt-3 text-xs">
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
                    <legend class="fieldset-legend font-semibold">{{ __('Amount Paid (₱)') }} *</legend>
                    <input type="number" step="0.01" min="0.01" wire:model="paymentAmount" class="input input-sm w-full bg-base-200 font-mono font-bold" readonly />
                    @error('paymentAmount') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Reference Number (GCash / Maya)') }}</legend>
                    <input type="text" wire:model="referenceNumber" class="input input-sm w-full" placeholder="{{ __('Required for digital wallet transfers') }}" />
                    @error('referenceNumber') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Collection Notes') }}</legend>
                    <input type="text" wire:model="paymentNotes" class="input input-sm w-full" placeholder="{{ __('Optional remarks...') }}" />
                </fieldset>
            </div>
            <div class="modal-action">
                <button type="button" wire:click="$set('paymentModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                <button type="button" wire:click="submitPayment" class="btn btn-sm btn-success">{{ __('Confirm & Issue Receipt') }}</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('paymentModal', false)"></div>
    </dialog>

    <!-- Waive / Void Modal -->
    <dialog class="modal" @if ($waiveModal) open @endif>
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base">
                {{ $waiveAction === 'void' ? __('Void Penalty') : __('Waive Penalty') }}
            </h3>
            <p class="text-xs text-base-content/70 mt-1">
                {{ __('Administrative justification is required and will be logged on the penalty record.') }}
            </p>
            <div class="mt-3 text-xs">
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend font-semibold">{{ __('Justification Remarks') }} *</legend>
                    <textarea wire:model="waiveReason" rows="3" class="textarea textarea-sm w-full"
                        placeholder="{{ __('e.g. Medical emergency certificate submitted / Assessed in error') }}"></textarea>
                    @error('waiveReason') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                </fieldset>
            </div>
            <div class="modal-action">
                <button type="button" wire:click="$set('waiveModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                <button type="button" wire:click="submitWaiveOrVoid"
                    class="btn btn-sm {{ $waiveAction === 'void' ? 'btn-error' : 'btn-info' }}">
                    {{ $waiveAction === 'void' ? __('Confirm Void') : __('Confirm Waiver') }}
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('waiveModal', false)"></div>
    </dialog>

    <!-- Verify Online Payment Receipt Modal -->
    <dialog class="modal" @if ($verifyModal && $this->verifyingPayment) open @endif>
        <div class="modal-box max-w-lg">
            @if ($this->verifyingPayment)
                @php
                    $vp = $this->verifyingPayment;
                    $siblings = $this->verifyingSiblings;
                    $totalSubmitted = (float) $siblings->sum('amount_paid');
                    $otherUnsettled = $this->borrowerOtherUnsettledCount;
                @endphp
                <h3 class="font-bold text-base flex items-center gap-2">
                    <span>{{ __('Verify Online Payment Receipt') }}</span>
                    <span class="badge badge-warning badge-xs font-semibold whitespace-nowrap">{{ __('Pending Verification') }}</span>
                </h3>

                <div class="space-y-3 mt-3 text-xs">
                    <div class="bg-base-200/60 p-3 rounded-box border border-base-300 space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Borrower') }}</span>
                            <span class="font-bold">{{ $vp->user->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Channel') }}</span>
                            <span class="font-semibold">{{ $vp->paymentMethod?->name ?? __('Online') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Reference #') }}</span>
                            <span class="font-mono">{{ $vp->reference_number ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Penalties Covered') }}</span>
                            <span class="font-semibold">{{ $siblings->count() }} {{ $siblings->count() === 1 ? __('penalty') : __('penalties') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-base-300 pt-1.5 font-bold text-sm">
                            <span>{{ __('Amount Submitted') }}</span>
                            <span class="font-mono text-success">₱{{ number_format($totalSubmitted, 2) }}</span>
                        </div>
                        @if ($vp->notes)
                            <div class="text-[11px] text-base-content/70 pt-1">
                                <span class="font-semibold">{{ __('Borrower Note:') }}</span> {{ $vp->notes }}
                            </div>
                        @endif
                    </div>

                    @if ($vp->proof_url)
                        <div class="bg-base-200/40 rounded-box p-2 border border-base-300 text-center">
                            <img src="{{ $vp->proof_url }}" alt="{{ __('Payment Receipt') }}" class="max-h-72 mx-auto rounded object-contain" />
                            <a href="{{ $vp->proof_url }}" target="_blank" rel="noopener noreferrer" class="link link-primary text-[11px] mt-1.5 inline-block">
                                {{ __('Open full size image') }}
                            </a>
                        </div>
                    @endif

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Rejection Reason (only if Not Paid)') }}</legend>
                        <input type="text" wire:model="rejectReason" class="input input-sm w-full" placeholder="{{ __('e.g. Receipt unreadable or funds not received...') }}" />
                    </fieldset>
                </div>

                <div class="modal-action flex-wrap gap-2">
                    <button type="button" wire:click="$set('verifyModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="rejectPendingPayment" class="btn btn-sm btn-error btn-outline">{{ __('Not Paid (Reject)') }}</button>
                    <button type="button" wire:click="confirmPendingPayment(false)" class="btn btn-sm btn-success">
                        {{ $siblings->count() > 1 ? __('Confirm Paid (All :count)', ['count' => $siblings->count()]) : __('Confirm Paid') }}
                    </button>
                    @if ($otherUnsettled > 0)
                        <button type="button" wire:click="confirmPendingPayment(true)" class="btn btn-sm btn-primary">
                            {{ __('Confirm & Clear All Penalties') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>
        <div class="modal-backdrop" wire:click="$set('verifyModal', false)"></div>
    </dialog>

    <!-- Approve Automated Penalty Modal -->
    <dialog class="modal" @if ($approveModal && $this->approvingPenalty) open @endif>
        <div class="modal-box max-w-md">
            @if ($this->approvingPenalty)
                @php $ap = $this->approvingPenalty; @endphp
                <h3 class="font-bold text-base">{{ __('Approve Automated Penalty #:id', ['id' => $ap->id]) }}</h3>
                <p class="text-xs text-base-content/60 mt-1">
                    {{ __('Review and finalize the automated overdue fee before it becomes payable by the borrower.') }}
                </p>

                <div class="space-y-3 mt-3 text-xs">
                    <div class="bg-base-200/60 p-3 rounded-box border border-base-300 space-y-1">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">{{ __('Borrower') }}</span>
                            <span class="font-bold">{{ $ap->user->name }}</span>
                        </div>
                        @if ($ap->loan)
                            <div class="flex justify-between">
                                <span class="text-base-content/60">{{ __('Linked Loan') }}</span>
                                <span class="font-mono font-semibold">#{{ $ap->loan_id }} ({{ $ap->loan->item?->name }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-base-content/60">{{ __('Item Return Status') }}</span>
                                <span class="font-semibold {{ $ap->loan->returned_at ? 'text-success' : 'text-warning' }}">
                                    {{ $ap->loan->returned_at ? __('Returned (:date)', ['date' => $ap->loan->returned_at->format('M d, Y')]) : __('Not yet returned') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Finalized Amount (₱)') }} *</legend>
                        <input type="number" step="0.01" min="0.01" wire:model="approveAmount" class="input input-sm w-full font-mono" />
                        @error('approveAmount') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend font-semibold">{{ __('Assessment Remarks') }}</legend>
                        <textarea wire:model="approveNotes" rows="3" class="textarea textarea-sm w-full"></textarea>
                        @error('approveNotes') <p class="label text-error text-xs">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="$set('approveModal', false)" class="btn btn-sm btn-ghost">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="submitApprovePenalty" class="btn btn-sm btn-primary">{{ __('Approve & Finalize') }}</button>
                </div>
            @endif
        </div>
        <div class="modal-backdrop" wire:click="$set('approveModal', false)"></div>
    </dialog>
</div>
