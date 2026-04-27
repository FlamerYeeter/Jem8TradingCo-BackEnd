<?php

namespace App\Events;

use App\Models\Notifications;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notifications $notification;

    public function __construct(Notifications $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        // Use the raw channel name `user.{id}` so Echo subscribes to `private-user.{id}`
        return new PrivateChannel('user.' . $this->notification->user_id);
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->notification_id,
            'type' => $this->notification->type,
            'data' => [
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'reference_type' => $this->notification->reference_type,
                'reference_id' => $this->notification->reference_id ?? null,
            ],
            'is_read' => (bool) $this->notification->is_read,
            'created_at' => optional($this->notification->created_at)->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
