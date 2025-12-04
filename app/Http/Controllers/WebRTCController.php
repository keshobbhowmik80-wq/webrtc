<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        try {
            // Get the JSON data
            $data = $request->json()->all();

            // If json decode fails, try getting from input
            if (empty($data)) {
                $data = $request->all();
            }

            // Debug log
            \Log::info('WebRTC Signal POST:', [
                'data_received' => $data,
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method()
            ]);

            // Validate required fields
            if (empty($data['room']) || empty($data['type']) || empty($data['data']) || empty($data['userId'])) {
                \Log::error('Missing required fields:', $data);
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required fields',
                    'received' => $data
                ], 400);
            }

            // Use hardcoded Pusher credentials (from your frontend)
            $pusher = new Pusher(
                '03e08e02c65168ea4c04',  // Your frontend key
                'fa28369b184c77c34102',     // You need to get this from Pusher dashboard
                '2082746',       // You need to get this from Pusher dashboard
                [
                    'cluster' => 'ap2',
                    'useTLS' => true,
                    'encrypted' => true
                ]
            );

            // Prepare event data
            $eventData = [
                'type' => $data['type'],
                'data' => $data['data'],
                'fromUserId' => $data['userId'],
                'timestamp' => now()->toISOString()
            ];

            // Add callType if present
            if (isset($data['callType'])) {
                $eventData['data']['callType'] = $data['callType'];
            }

            // Debug before sending
            \Log::info('Sending to Pusher:', [
                'channel' => 'webrtc.' . $data['room'],
                'event' => 'webrtc.signal',
                'data' => $eventData
            ]);

            // Send the signal via Pusher
            $result = $pusher->trigger(
                'webrtc.' . $data['room'],
                'webrtc.signal',
                $eventData
            );

            \Log::info('Pusher response:', ['result' => $result]);

            return response()->json([
                'success' => true,
                'message' => 'Signal forwarded to Pusher',
                'channel' => 'webrtc.' . $data['room']
            ]);

        } catch (\Exception $e) {
            \Log::error('WebRTC Controller Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleGetSignal(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'WebRTC signaling server is running',
            'timestamp' => now()->toISOString(),
            'endpoint' => '/webrtc/signal'
        ]);
    }
}
