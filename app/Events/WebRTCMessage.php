<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
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
        return new Channel('webrtc.' . $this->room);
    }

    public function broadcastAs()
    {
        return 'webrtc.signal';
    }

    public function broadcastWith()
    {
        return [
            'type' => $this->message['type'],
            'data' => $this->message['data'],
            'fromUserId' => $this->message['userId']
        ];
    }
}
