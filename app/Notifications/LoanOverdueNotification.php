<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Loan;
use App\Models\Penalty;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoanOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Loan $loan,
        public ?Penalty $penalty = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->loan->loadMissing(['item', 'itemUnit', 'organization']);

        $itemName        = $this->loan->item?->name ?? 'Equipment';
        $assetTag        = $this->loan->itemUnit?->asset_tag ?? 'N/A';
        $deadline        = $this->loan->due_at?->format('M d, Y g:i A') ?? 'Past Due';
        $overdueDuration = $this->loan->due_at
            ? $this->loan->due_at->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE])
            : 'Overdue';

        $fineMessage = $this->penalty
            ? 'An initial overdue penalty of ₱'.number_format((float) $this->penalty->amount, 2)." has been assessed ({$this->penalty->assessment_notes})."
            : 'Overdue penalties are being computed according to college circulation policy.';

        return (new MailMessage)
            ->subject("OVERDUE NOTICE: {$itemName} ({$assetTag})")
            ->greeting("Hello {$notifiable->name},")
            ->line('Your equipment loan has passed its scheduled return deadline and is now marked **Overdue**.')
            ->line("**Item:** {$itemName} ({$assetTag})")
            ->line("**Original Deadline:** {$deadline}")
            ->line("**Overdue Duration:** {$overdueDuration}")
            ->line($fineMessage)
            ->line('**Borrowing Suspension:** Your borrowing privileges are temporarily suspended until the equipment is returned and all outstanding penalties are settled.')
            ->action('View Active Loans', route('portal.loans'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->loan->loadMissing(['item', 'itemUnit']);

        $itemName        = $this->loan->item?->name ?? 'Equipment';
        $assetTag        = $this->loan->itemUnit?->asset_tag ?? 'N/A';
        $overdueDuration = $this->loan->due_at
            ? $this->loan->due_at->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE])
            : 'Overdue';
        $fineText = $this->penalty
            ? ' Initial fine: ₱'.number_format((float) $this->penalty->amount, 2).'.'
            : '';

        return [
            'type'             => 'loan_overdue',
            'title'            => 'Equipment Loan Overdue',
            'message'          => "{$itemName} ({$assetTag}) is overdue by {$overdueDuration}. Borrowing privileges suspended.{$fineText}",
            'loan_id'          => $this->loan->id,
            'penalty_id'       => $this->penalty?->id,
            'penalty_amount'   => $this->penalty ? (float) $this->penalty->amount : null,
            'organization_id'  => $this->loan->organization_id,
            'item_name'        => $itemName,
            'asset_tag'        => $assetTag,
            'overdue_duration' => $overdueDuration,
            'action_url'       => route('portal.loans'),
        ];
    }
}
