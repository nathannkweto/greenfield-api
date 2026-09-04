<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // <-- ADD THIS
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RealtimeTestNotification extends Notification implements ShouldBroadcastNow // <-- ADD THIS
{
    use Queueable;

    public function __construct(public string $title, public string $body) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => '/dashboard',
            'type' => 'test',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => '/dashboard',
            'type' => 'test',
        ]);
    }
}
