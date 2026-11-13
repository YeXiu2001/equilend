<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use App\Enums\ReservationStatus;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ReservationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Reservation $reservation) {}

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
        $this->reservation->loadMissing(['item', 'itemUnit', 'organization']);

        $itemName    = $this->reservation->item?->name ?? 'Equipment';
        $collegeName = $this->reservation->organization?->name ?? 'College';
        $isApproved  = $this->reservation->status === ReservationStatus::Approved;
        $decision    = $isApproved ? 'Approved' : 'Rejected';
        $timeslot    = $this->reservation->start_time?->format('M d, Y g:i A').' – '.$this->reservation->end_time?->format('M d, Y g:i A');

        $mail = (new MailMessage)
            ->subject("Reservation {$decision}: {$itemName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your equipment reservation with {$collegeName} has been **{$decision}**.")
            ->line("**Item:** {$itemName}");

        if ($isApproved) {
            $mail->line("**Approved Timeslot:** {$timeslot}");

            if ($this->reservation->itemUnit) {
                $mail->line("**Assigned Unit:** {$this->reservation->itemUnit->asset_tag}");
            }
        } elseif ($this->reservation->rejection_remarks) {
            $mail->line("**Rejection Remarks:** {$this->reservation->rejection_remarks}");
        }

        return $mail->action('View Reservations', route('portal.reservations'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->reservation->loadMissing(['item', 'organization']);

        $itemName   = $this->reservation->item?->name ?? 'Equipment';
        $isApproved = $this->reservation->status === ReservationStatus::Approved;
        $decision   = $isApproved ? 'approved' : 'rejected';
        $timeslot   = $this->reservation->start_time?->format('M d, g:i A').' – '.$this->reservation->end_time?->format('g:i A');

        $message = $isApproved
            ? "Your reservation for {$itemName} ({$timeslot}) has been approved."
            : "Your reservation for {$itemName} was rejected: {$this->reservation->rejection_remarks}";

        return [
            'type'              => 'reservation_status',
            'title'             => $isApproved ? 'Reservation Approved' : 'Reservation Rejected',
            'message'           => $message,
            'reservation_id'    => $this->reservation->id,
            'organization_id'   => $this->reservation->organization_id,
            'decision'          => $decision,
            'item_name'         => $itemName,
            'rejection_remarks' => $this->reservation->rejection_remarks,
            'action_url'        => route('portal.reservations'),
        ];
    }
}
