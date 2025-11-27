<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\WebRTCMessage;
use Pusher\Pusher;

class WebRTCController extends Controller
{
    public function signal(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->handlePostSignal($request);
        } else {
            return $this->handleGetSignal($request);
        }
    }

    private function handlePostSignal(Request $request)
{
    $data = $request->validate([
        'room' => 'required|string',
        'type' => 'required|string',
        'data' => 'required|array',
        'userId' => 'required|string'
    ]);

    // Use Pusher PHP SDK directly
    $pusher = new \Pusher\Pusher(
        config('broadcasting.connections.pusher.key'),
        config('broadcasting.connections.pusher.secret'),
        config('broadcasting.connections.pusher.app_id'),
        [
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'useTLS' => true
        ]
    );

    // Send event directly to Pusher
    $pusher->trigger(
        'webrtc.' . $data['room'],
        'webrtc.signal',
        [
            'type' => $data['type'],
            'data' => $data['data'],
            'fromUserId' => $data['userId']
        ]
    );

    return response()->json(['success' => true]);
}

    private function handleGetSignal(Request $request)
    {
        // Keep this for backward compatibility, but it won't be used anymore
        return response()->json(['signals' => []]);
    }
}
