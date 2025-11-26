<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebRTCController extends Controller
{
    private $signals = [];
    private $nextSignalId = 1;

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

        // Create signal with unique ID
        $signal = [
            'id' => $this->nextSignalId++,
            'type' => $data['type'],
            'data' => $data['data'],
            'fromUserId' => $data['userId'],
            'room' => $data['room'],
            'timestamp' => now()
        ];

        // Store signal in cache (persistent across requests)
        $this->storeSignal($data['room'], $signal);

        return response()->json(['success' => true, 'signalId' => $signal['id']]);
    }

    private function handleGetSignal(Request $request)
    {
        $room = $request->query('room', 'default');
        $userId = $request->query('userId');

        // Get all signals for this room that aren't from the current user
        $signals = $this->getSignals($room);
        $filteredSignals = array_filter($signals, function($signal) use ($userId) {
            return $signal['fromUserId'] !== $userId;
        });

        // Return the signals
        return response()->json([
            'signals' => array_values($filteredSignals)
        ]);
    }

    private function storeSignal($room, $signal)
    {
        $key = "webrtc_signals_{$room}";
        $signals = Cache::get($key, []);

        // Keep only last 50 signals to prevent memory issues
        $signals[] = $signal;
        if (count($signals) > 50) {
            $signals = array_slice($signals, -50);
        }

        Cache::put($key, $signals, now()->addMinutes(10)); // Store for 10 minutes
    }

    private function getSignals($room)
    {
        $key = "webrtc_signals_{$room}";
        return Cache::get($key, []);
    }
}
