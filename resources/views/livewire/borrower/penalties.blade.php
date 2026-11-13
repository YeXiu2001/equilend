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
            <span class="badge {{ $canBorrow ? 'badge-success' : 'badge-error' }} font-semibold">
                {{ $canBorrow ? __('Eligible to Borrow') : __('Suspended') }}
            </span>
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
                                    <th>{{ __('Details / Reason') }}</th>
                                    <th class="text-right">{{ __('Amount') }}</th>
                                    <th class="text-right">{{ __('Balance') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->penalties as $penalty)
                                    <tr class="hover:bg-base-200/40">
                                        <td class="whitespace-nowrap">{{ $penalty->created_at->format('M d, Y') }}</td>
                                        <td><span class="badge badge-xs {{ $penalty->type->badgeClass() }}">{{ $penalty->type->label() }}</span></td>
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
                                        <td><span class="badge badge-xs {{ $penalty->status->badgeClass() }}">{{ $penalty->status->label() }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-base-content/60">
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
                                        <td><span class="badge badge-xs {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-base-content/60">
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
                        {{ __('You may settle outstanding dues in Cash at your college circulation desk or via the official digital wallets below. Present your Reference # at the desk for receipt issuance.') }}
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
                            <div class="bg-base-200/60 p-3 rounded-box text-base-content/70">
                                {{ __('Please visit your college circulation desk for over-the-counter settlement instructions.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
