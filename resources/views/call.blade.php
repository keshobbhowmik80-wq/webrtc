<!DOCTYPE html>
<html>
<head>
    <title>Agora Video Call</title>
    <script src="https://cdn.agora.io/sdk/release/AgoraRTC_N.js"></script>
</head>
<body>
    <h1>Agora Video Call</h1>

    <button onclick="initCall('testRoom')">Join Call</button>
    <button onclick="leaveCall()">Leave Call</button>

    <div id="local-player" style="width:400px; height:300px; border:1px solid #000; margin-top:10px;"></div>
    <div id="remote-playerlist" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;"></div>

    <script src="{{ asset('js/call.js') }}"></script>
</body>
</html>
