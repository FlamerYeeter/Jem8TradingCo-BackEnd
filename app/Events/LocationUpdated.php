<?php

namespace App\Events;

use App\Models\Location;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Location $location;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|
     */
    public function broadcastOn()
    {
        return new Channel('trip.' . $this->location->trip_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->location->id,
            'trip_id' => $this->location->trip_id,
            'user_id' => $this->location->user_id,
            'lat' => (float) $this->location->lat,
            'lng' => (float) $this->location->lng,
            'accuracy' => $this->location->accuracy,
            'speed' => $this->location->speed,
            'bearing' => $this->location->bearing,
            'created_at' => $this->location->created_at->toIso8601String(),
        ];
    }
}
