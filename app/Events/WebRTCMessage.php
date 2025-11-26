<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCMessage implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $room;
    public $message;

    public function __construct($room, $message)
    {
        $this->room = $room;
        $this->message = $message;
    }

    public function broadcastOn()
{
    return new \Illuminate\Broadcasting\Channel('webrtc.' . $this->room);
}

    public function broadcastAs()
    {
        return 'WebRTCMessage';
    }

    public function broadcastWith()
    {
        return $this->message;
    }
}
