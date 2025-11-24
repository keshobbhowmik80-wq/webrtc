<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Agora\RtcTokenBuilder2;

class AgoraController extends Controller
{
    public function generateToken(Request $request)
    {
        $appID = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERTIFICATE');

        $channelName = $request->query('channel');
        if (!$channelName) {
            return response()->json(['error' => 'Channel name is required'], 400);
        }

        // Use UID from client, fallback to 0
        $uid = (int)($request->query('uid') ?? 0);
        $expireTimeInSeconds = 3600;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpireTs = $currentTimestamp + $expireTimeInSeconds;

        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appID,
            $appCertificate,
            $channelName,
            $uid,
            RtcTokenBuilder2::ROLE_PUBLISHER,
            $expireTimeInSeconds,
            $privilegeExpireTs
        );

        return response()->json([
            'appId' => $appID,
            'token' => $token,
            'uid' => $uid,
            'channel' => $channelName
        ]);
    }
}
