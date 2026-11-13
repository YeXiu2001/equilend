<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

class Bell extends Component
{
    /**
     * Get the count of unread notifications for the authenticated user.
     */
    #[Computed]
    public function unreadCount(): int
    {
        $user = Auth::user();

        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * Get the 10 most recent notifications for the authenticated user.
     *
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function recentNotifications(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return new Collection;
        }

        return $user->notifications()->latest()->limit(10)->get();
    }

    /**
     * Mark a single notification as read and redirect if an action URL exists.
     */
    public function markAsRead(string $notificationId): mixed
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification) {
            $notification->markAsRead();
            unset($this->unreadCount, $this->recentNotifications);

            $actionUrl = $notification->data['action_url'] ?? null;

            if (is_string($actionUrl) && $actionUrl !== '' && $actionUrl !== '#') {
                return $this->redirect($actionUrl, navigate: false);
            }
        }

        return null;
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
        unset($this->unreadCount, $this->recentNotifications);
    }

    /**
     * Clear all notifications for the authenticated user.
     */
    public function clearAll(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->notifications()->delete();
        unset($this->unreadCount, $this->recentNotifications);
    }

    public function render(): View
    {
        return view('livewire.notifications.bell');
    }
}
