<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Penalty;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PenaltyImposedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Penalty $penalty) {}

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
        $this->penalty->loadMissing(['organization.organizationPaymentMethods.paymentMethod', 'itemUnit.item']);

        $typeLabel   = $this->penalty->type->label();
        $amountText  = '₱'.number_format((float) $this->penalty->amount, 2);
        $collegeName = $this->penalty->organization?->name ?? 'College';

        $channels = $this->penalty->organization?->organizationPaymentMethods
            ->where('is_enabled', true)
            ->map(function ($orgMethod): string {
                $name   = $orgMethod->paymentMethod?->name ?? 'Payment';
                $number = $orgMethod->account_numbers ? " ({$orgMethod->account_numbers})" : '';

                return "{$name}{$number}";
            })
            ->implode(', ');

        $settlementInfo = $channels !== '' && $channels !== null
            ? "You may settle this penalty via {$channels}, or in person at the {$collegeName} Circulation Counter."
            : "You may settle this penalty via GCash, Maya, or Cash at the {$collegeName} Circulation Counter.";

        $mail = (new MailMessage)
            ->subject("Penalty Assessed: {$typeLabel} ({$amountText})")
            ->greeting("Hello {$notifiable->name},")
            ->line("A penalty has been recorded on your account by {$collegeName}.")
            ->line("**Penalty Type:** {$typeLabel}")
            ->line("**Assessed Amount:** {$amountText}");

        if ($this->penalty->assessment_notes) {
            $mail->line("**Assessment Notes:** {$this->penalty->assessment_notes}");
        }

        return $mail
            ->line($settlementInfo)
            ->action('View & Settle Penalties', route('portal.penalties'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->penalty->loadMissing('organization');

        $typeLabel   = $this->penalty->type->label();
        $amountText  = '₱'.number_format((float) $this->penalty->amount, 2);
        $collegeName = $this->penalty->organization?->name ?? 'College';

        return [
            'type'            => 'penalty_imposed',
            'title'           => "{$typeLabel} Penalty Imposed",
            'message'         => "A {$typeLabel} penalty of {$amountText} has been assessed by {$collegeName}. Settle via GCash, Maya, or Cash.",
            'penalty_id'      => $this->penalty->id,
            'loan_id'         => $this->penalty->loan_id,
            'organization_id' => $this->penalty->organization_id,
            'penalty_type'    => $this->penalty->type->value,
            'amount'          => (float) $this->penalty->amount,
            'action_url'      => route('portal.penalties'),
        ];
    }
}
