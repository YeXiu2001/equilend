<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdvanceReturnReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Loan $loan) {}

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

        $itemName    = $this->loan->item?->name ?? 'Equipment';
        $assetTag    = $this->loan->itemUnit?->asset_tag ?? 'N/A';
        $deadline    = $this->loan->due_at?->format('M d, Y g:i A') ?? 'Soon';
        $collegeName = $this->loan->organization?->name ?? 'College';
        $collegeAddr = $this->loan->organization?->address;

        $counterInstruction = $collegeAddr
            ? "Please return the unit to the {$collegeName} Circulation Counter ({$collegeAddr}) before the deadline to avoid overdue penalties."
            : "Please return the unit to the {$collegeName} Equipment Circulation Counter before the deadline to avoid overdue penalties.";

        return (new MailMessage)
            ->subject("Return Reminder: {$itemName} ({$assetTag}) due in 1 hour")
            ->greeting("Hello {$notifiable->name},")
            ->line('This is an automated reminder that your borrowed equipment is due for return within 1 hour.')
            ->line("**Item:** {$itemName}")
            ->line("**Asset Tag:** {$assetTag}")
            ->line("**Return Deadline:** {$deadline}")
            ->line($counterInstruction)
            ->action('View Active Loans', route('portal.loans'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->loan->loadMissing(['item', 'itemUnit', 'organization']);

        $itemName    = $this->loan->item?->name ?? 'Equipment';
        $assetTag    = $this->loan->itemUnit?->asset_tag ?? 'N/A';
        $deadline    = $this->loan->due_at?->format('M d, Y g:i A') ?? 'Soon';
        $collegeName = $this->loan->organization?->name ?? 'College';

        return [
            'type'            => 'return_reminder',
            'title'           => 'Equipment Return Reminder',
            'message'         => "{$itemName} ({$assetTag}) is due at {$deadline}. Return to {$collegeName} Circulation Counter.",
            'loan_id'         => $this->loan->id,
            'organization_id' => $this->loan->organization_id,
            'item_name'       => $itemName,
            'asset_tag'       => $assetTag,
            'due_at'          => $this->loan->due_at?->toIso8601String(),
            'action_url'      => route('portal.loans'),
        ];
    }
}
