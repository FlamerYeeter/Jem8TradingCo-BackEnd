<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.'.$this->message->chatroom_id);
    }

    /**
     * The data to broadcast with the event.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->message_id,
                'chatroom_id' => $this->message->chatroom_id,
                'messages' => $this->message->messages,
                'status' => $this->message->status,
                'cart_id' => $this->message->cart_id,
                'created_at' => optional($this->message->created_at)->toDateTimeString(),
            ],
        ];
    }
}
