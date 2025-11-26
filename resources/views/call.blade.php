<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>WebRTC Call System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial;
            background: #f0f2f5;
            padding: 20px;
        }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            max-width: 900px;
            margin: 0 auto;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .button-group {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            color: #fff;
        }

        .btn.video {
            background: #007bff;
        }

        .btn.audio {
            background: #28a745;
        }

        .btn.leave {
            background: #dc3545;
        }

        .btn.debug {
            background: #6c757d;
        }

        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .video-area {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .video-box {
            width: 400px;
            height: 300px;
            background: #000;
            border-radius: 8px;
            position: relative;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .status {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .error {
            color: #dc3545;
            text-align: center;
            margin: 10px 0;
        }

        .debug-panel {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }

        .debug-log {
            height: 200px;
            overflow-y: auto;
            background: #000;
            color: #00ff00;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
        }

        .debug-controls {
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="title">WebRTC Calling System</h2>
        <div class="status" id="status">Ready to connect</div>
        <div class="error" id="error"></div>

        <div class="button-group">
            <button id="joinVideo" class="btn video">Join Video Call</button>
            <button id="joinAudio" class="btn audio">Join Audio Call</button>
            <button id="leaveCall" class="btn leave" disabled>Leave Call</button>
        </div>

        <!-- Debug Panel -->
        <div class="debug-panel">
            <div class="debug-controls">
                <button onclick="testSignaling()" class="btn debug">Test Signaling</button>
                <button onclick="clearDebugLog()" class="btn debug">Clear Log</button>
                <button onclick="checkBackend()" class="btn debug">Check Backend</button>
            </div>
            <div id="debug-log" class="debug-log"></div>
        </div>

        <div class="video-area">
            <div class="video-box">
                <div id="local-player"></div>
                <div style="text-align:center;margin-top:5px;">Local Video</div>
            </div>
            <div class="video-box">
                <div id="remote-player"></div>
                <div style="text-align:center;margin-top:5px;">Remote Video</div>
            </div>
        </div>
    </div>

    <!-- <script>
        // Debug functions
        function debugLog(message) {
            const logDiv = document.getElementById('debug-log');
            if (logDiv) {
                const timestamp = new Date().toLocaleTimeString();
                logDiv.innerHTML += `<span style="color: #888">[${timestamp}]</span> ${message}<br>`;
                logDiv.scrollTop = logDiv.scrollHeight;
            }
            console.log('DEBUG:', message);
        }

        function clearDebugLog() {
            const logDiv = document.getElementById('debug-log');
            if (logDiv) {
                logDiv.innerHTML = '';
            }
        }

        async function testSignaling() {
            const userId = 'test-user-' + Math.random().toString(36).substring(7);
            const room = 'test-room';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            debugLog('=== TESTING SIGNALING ===');
            debugLog(`User ID: ${userId}`);
            debugLog(`CSRF Token: ${csrfToken.substring(0, 20)}...`);
            debugLog(`Room: ${room}`);

            // Test 1: Send a POST signal
            debugLog('1. Sending POST signal...');
            try {
                const postResponse = await fetch('/webrtc/signal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        room: room,
                        type: 'join',
                        data: { message: 'Hello from ' + userId },
                        userId: userId
                    })
                });

                if (!postResponse.ok) {
                    const errorText = await postResponse.text();
                    throw new Error(`HTTP ${postResponse.status}: ${errorText}`);
                }

                const postResult = await postResponse.json();
                debugLog('✅ POST Success: ' + JSON.stringify(postResult));
            } catch (error) {
                debugLog('❌ POST Error: ' + error.message);
                return;
            }

            // Wait a bit for the signal to be stored
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Test 2: Retrieve signals (should see our own signal)
            debugLog('2. Retrieving signals as different user...');
            try {
                const getResponse = await fetch(`/webrtc/signal?room=${room}&userId=different-user-123`);
                const getResult = await getResponse.json();

                if (getResult.signals && getResult.signals.length > 0) {
                    debugLog('✅ SUCCESS: Found ' + getResult.signals.length + ' signal(s)');
                    getResult.signals.forEach(signal => {
                        debugLog(`   - ${signal.type} from ${signal.fromUserId}`);
                    });
                } else {
                    debugLog('❌ No signals found in backend');
                    debugLog('Backend response: ' + JSON.stringify(getResult));
                }
            } catch (error) {
                debugLog('GET Error: ' + error.message);
            }

            // Test 3: Check if we can see our own signal (should be filtered out)
            debugLog('3. Checking if our signal is filtered...');
            try {
                const selfResponse = await fetch(`/webrtc/signal?room=${room}&userId=${userId}`);
                const selfResult = await selfResponse.json();

                if (selfResult.signals && selfResult.signals.length > 0) {
                    debugLog('❌ ERROR: Should not see our own signals!');
                } else {
                    debugLog('✅ Good: Our own signals are properly filtered');
                }
            } catch (error) {
                debugLog('Self-check Error: ' + error.message);
            }
        }

        async function checkBackend() {
            debugLog('=== CHECKING BACKEND ===');
            try {
                const response = await fetch('/webrtc/signal?room=test-room&userId=checker');
                const result = await response.json();
                debugLog('Backend response: ' + JSON.stringify(result));

                if (result.signals && Array.isArray(result.signals)) {
                    debugLog(`Backend structure OK - supports signals array`);
                } else {
                    debugLog(`Backend response format may be incorrect`);
                }
            } catch (error) {
                debugLog('Backend check error: ' + error.message);
            }
        }

        // WebRTC App Class
        class WebRTCApp {
            constructor() {
                this.localStream = null;
                this.peerConnection = null;
                this.room = 'test-room';
                this.userId = Math.random().toString(36).substring(7);
                this.remoteUserId = null;
                this.isCallActive = false;
                this.signalingInterval = null;

                debugLog('WebRTC App initialized. User ID: ' + this.userId);
                this.setupEventListeners();
            }

            setupEventListeners() {
                document.getElementById('joinVideo').addEventListener('click', () => this.startCall('video'));
                document.getElementById('joinAudio').addEventListener('click', () => this.startCall('audio'));
                document.getElementById('leaveCall').addEventListener('click', () => this.leaveCall());
            }

            async startCall(mode) {
                try {
                    this.updateStatus('Getting media access...');
                    debugLog('Starting ' + mode + ' call...');

                    // Get user media
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: mode === 'video',
                        audio: true
                    });
                    debugLog('Got local stream: ' + this.localStream.getTracks().length + ' tracks');

                    this.displayLocalStream();
                    this.createPeerConnection();
                    this.setupSignaling();

                    // Send join message
                    await this.sendSignal('join', { userId: this.userId });
                    debugLog('Join signal sent');

                    this.updateStatus('Ready - Waiting for peer...');
                    document.getElementById('leaveCall').disabled = false;

                } catch (error) {
                    debugLog('Start call error: ' + error.message);
                    this.updateStatus('Error: ' + error.message);
                }
            }

            displayLocalStream() {
                const localPlayer = document.getElementById('local-player');
                localPlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = this.localStream;
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                localPlayer.appendChild(video);
                debugLog('Local stream displayed');
            }

            createPeerConnection() {
                debugLog('Creating peer connection');
                const config = {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' }
                    ]
                };

                this.peerConnection = new RTCPeerConnection(config);

                // Add local tracks
                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                    debugLog('Added track: ' + track.kind);
                });

                // Handle remote stream
                this.peerConnection.ontrack = (event) => {
                    debugLog('✅ Received remote stream!');
                    const remoteStream = event.streams[0];
                    this.displayRemoteStream(remoteStream);
                    this.updateStatus('✅ Call Connected!');
                    this.isCallActive = true;
                };

                // ICE candidates
                this.peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        debugLog('Generated ICE candidate');
                        this.sendSignal('ice-candidate', {
                            candidate: event.candidate,
                            targetUserId: this.remoteUserId
                        });
                    }
                };

                this.peerConnection.onconnectionstatechange = () => {
                    const state = this.peerConnection.connectionState;
                    debugLog('Connection state: ' + state);
                    this.updateStatus('Connection: ' + state);
                };

                this.peerConnection.oniceconnectionstatechange = () => {
                    debugLog('ICE connection state: ' + this.peerConnection.iceConnectionState);
                };
            }

            displayRemoteStream(remoteStream) {
                const remotePlayer = document.getElementById('remote-player');
                remotePlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = remoteStream;
                video.autoplay = true;
                video.playsInline = true;
                remotePlayer.appendChild(video);
                debugLog('Remote stream displayed');
            }

            async createOffer() {
                try {
                    debugLog('Creating offer...');
                    this.updateStatus('Creating offer...');

                    const offer = await this.peerConnection.createOffer();
                    await this.peerConnection.setLocalDescription(offer);

                    await this.sendSignal('offer', {
                        sdp: this.peerConnection.localDescription,
                        targetUserId: this.remoteUserId
                    });

                    debugLog('Offer sent');
                    this.updateStatus('Offer sent');

                } catch (error) {
                    debugLog('Create offer error: ' + error.message);
                    this.updateStatus('Offer error: ' + error.message);
                }
            }

            async handleOffer(offerSdp, fromUserId) {
                try {
                    debugLog('Handling offer from: ' + fromUserId);

                    if (this.isCallActive) {
                        debugLog('Call already active, ignoring offer');
                        return;
                    }

                    this.updateStatus('Received offer...');
                    this.remoteUserId = fromUserId;

                    await this.peerConnection.setRemoteDescription(offerSdp);
                    const answer = await this.peerConnection.createAnswer();
                    await this.peerConnection.setLocalDescription(answer);

                    await this.sendSignal('answer', {
                        sdp: this.peerConnection.localDescription,
                        targetUserId: fromUserId
                    });

                    debugLog('Answer sent');
                    this.updateStatus('Answer sent');

                } catch (error) {
                    debugLog('Handle offer error: ' + error.message);
                    this.updateStatus('Offer handling error: ' + error.message);
                }
            }

            async handleAnswer(answerSdp) {
                try {
                    debugLog('Handling answer');
                    await this.peerConnection.setRemoteDescription(answerSdp);
                    debugLog('Call established!');
                    this.updateStatus('✅ Connected!');

                } catch (error) {
                    debugLog('Handle answer error: ' + error.message);
                    this.updateStatus('Answer error: ' + error.message);
                }
            }

            async handleIceCandidate(candidate) {
                try {
                    debugLog('Adding ICE candidate');
                    await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (error) {
                    debugLog('ICE candidate error: ' + error.message);
                }
            }

            async sendSignal(type, data) {
                try {
                    debugLog('Sending signal: ' + type);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const response = await fetch('/webrtc/signal', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            room: this.room,
                            type: type,
                            data: data,
                            userId: this.userId
                        })
                    });

                    const result = await response.json();
                    debugLog('Signal sent: ' + type);

                } catch (error) {
                    debugLog('Send signal error: ' + error.message);
                }
            }

            setupSignaling() {
                if (this.signalingInterval) return;

                debugLog('Starting signaling polling');
                this.signalingInterval = setInterval(async () => {
                    try {
                        const url = `/webrtc/signal?room=${this.room}&userId=${this.userId}`;
                        const response = await fetch(url);
                        const data = await response.json();

                        if (data.signals && Array.isArray(data.signals)) {
                            debugLog(`Polling: ${data.signals.length} signal(s) found`);
                            for (const signal of data.signals) {
                                if (signal.fromUserId === this.userId) continue;
                                debugLog(`Processing: ${signal.type} from ${signal.fromUserId}`);
                                this.handleIncomingSignal(signal);
                            }
                        }

                    } catch (error) {
                        debugLog('Polling error: ' + error.message);
                    }
                }, 2000);
            }

            handleIncomingSignal(signal) {
                debugLog('Handling signal: ' + signal.type);

                switch (signal.type) {
                    case 'join':
                        debugLog('Peer joined: ' + signal.fromUserId);
                        if (!this.isCallActive && !this.remoteUserId) {
                            this.remoteUserId = signal.fromUserId;
                            this.updateStatus('Peer found - creating offer...');
                            setTimeout(() => this.createOffer(), 1000);
                        }
                        break;

                    case 'offer':
                        this.handleOffer(signal.data.sdp, signal.fromUserId);
                        break;

                    case 'answer':
                        this.handleAnswer(signal.data.sdp);
                        break;

                    case 'ice-candidate':
                        this.handleIceCandidate(signal.data.candidate);
                        break;
                }
            }

            leaveCall() {
                debugLog('Leaving call');

                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => track.stop());
                    this.localStream = null;
                }

                if (this.peerConnection) {
                    this.peerConnection.close();
                    this.peerConnection = null;
                }

                if (this.signalingInterval) {
                    clearInterval(this.signalingInterval);
                    this.signalingInterval = null;
                }

                this.isCallActive = false;
                this.remoteUserId = null;

                document.getElementById('local-player').innerHTML = '';
                document.getElementById('remote-player').innerHTML = '';
                document.getElementById('leaveCall').disabled = true;

                this.updateStatus('Disconnected');
                debugLog('Call ended');
            }

            updateStatus(message) {
                document.getElementById('status').textContent = message;
                debugLog('Status: ' + message);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new WebRTCApp();
        });
    </script> -->

    <script>
        // Debug functions
        function debugLog(message) {
            const logDiv = document.getElementById('debug-log');
            if (logDiv) {
                const timestamp = new Date().toLocaleTimeString();
                logDiv.innerHTML += `<span style="color: #888">[${timestamp}]</span> ${message}<br>`;
                logDiv.scrollTop = logDiv.scrollHeight;
            }
            console.log('DEBUG:', message);
        }

        function clearDebugLog() {
            const logDiv = document.getElementById('debug-log');
            if (logDiv) {
                logDiv.innerHTML = '';
            }
        }

        async function testSignaling() {
            const userId = 'test-user-' + Math.random().toString(36).substring(7);
            const room = 'test-room';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            debugLog('=== TESTING SIGNALING ===');
            debugLog(`User ID: ${userId}`);
            debugLog(`CSRF Token: ${csrfToken.substring(0, 20)}...`);
            debugLog(`Room: ${room}`);

            // Test 1: Send a POST signal
            debugLog('1. Sending POST signal...');
            try {
                const postResponse = await fetch('/webrtc/signal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        room: room,
                        type: 'join',
                        data: {
                            message: 'Hello from ' + userId
                        },
                        userId: userId
                    })
                });

                if (!postResponse.ok) {
                    const errorText = await postResponse.text();
                    throw new Error(`HTTP ${postResponse.status}: ${errorText}`);
                }

                const postResult = await postResponse.json();
                debugLog('✅ POST Success: ' + JSON.stringify(postResult));
            } catch (error) {
                debugLog('❌ POST Error: ' + error.message);
                return;
            }

            // Wait a bit for the signal to be stored
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Test 2: Retrieve signals (should see our own signal)
            debugLog('2. Retrieving signals as different user...');
            try {
                const getResponse = await fetch(`/webrtc/signal?room=${room}&userId=different-user-123`);
                const getResult = await getResponse.json();

                if (getResult.signals && getResult.signals.length > 0) {
                    debugLog('✅ SUCCESS: Found ' + getResult.signals.length + ' signal(s)');
                    getResult.signals.forEach(signal => {
                        debugLog(`   - ${signal.type} from ${signal.fromUserId}`);
                    });
                } else {
                    debugLog('❌ No signals found in backend');
                    debugLog('Backend response: ' + JSON.stringify(getResult));
                }
            } catch (error) {
                debugLog('GET Error: ' + error.message);
            }

            // Test 3: Check if we can see our own signal (should be filtered out)
            debugLog('3. Checking if our signal is filtered...');
            try {
                const selfResponse = await fetch(`/webrtc/signal?room=${room}&userId=${userId}`);
                const selfResult = await selfResponse.json();

                if (selfResult.signals && selfResult.signals.length > 0) {
                    debugLog('❌ ERROR: Should not see our own signals!');
                } else {
                    debugLog('✅ Good: Our own signals are properly filtered');
                }
            } catch (error) {
                debugLog('Self-check Error: ' + error.message);
            }
        }

        async function checkBackend() {
            debugLog('=== CHECKING BACKEND ===');
            try {
                const response = await fetch('/webrtc/signal?room=test-room&userId=checker');
                const result = await response.json();
                debugLog('Backend response: ' + JSON.stringify(result));

                if (result.signals && Array.isArray(result.signals)) {
                    debugLog(`Backend structure OK - supports signals array`);
                } else {
                    debugLog(`Backend response format may be incorrect`);
                }
            } catch (error) {
                debugLog('Backend check error: ' + error.message);
            }
        }

        // SDP Cleaning Function - ADD THIS
        function cleanSdp(sdp) {
            if (!sdp || !sdp.sdp) return sdp;

            debugLog('Cleaning SDP...');

            // Fix line endings and remove empty lines
            const lines = sdp.sdp.split(/\r\n|\n|\r/);
            const nonEmptyLines = lines.filter(line => line.trim() !== '');

            const cleanedSdp = {
                type: sdp.type,
                sdp: nonEmptyLines.join('\r\n') + '\r\n'
            };

            debugLog('SDP cleaned successfully');
            return cleanedSdp;
        }

        // WebRTC App Class
        class WebRTCApp {
            constructor() {
                this.localStream = null;
                this.peerConnection = null;
                this.room = 'test-room';
                this.userId = Math.random().toString(36).substring(7);
                this.remoteUserId = null;
                this.isCallActive = false;
                this.signalingInterval = null;

                debugLog('WebRTC App initialized. User ID: ' + this.userId);
                this.setupEventListeners();
            }

            setupEventListeners() {
                document.getElementById('joinVideo').addEventListener('click', () => this.startCall('video'));
                document.getElementById('joinAudio').addEventListener('click', () => this.startCall('audio'));
                document.getElementById('leaveCall').addEventListener('click', () => this.leaveCall());
            }

            async startCall(mode) {
                try {
                    this.updateStatus('Getting media access...');
                    debugLog('Starting ' + mode + ' call...');

                    // Get user media
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: mode === 'video',
                        audio: true
                    });
                    debugLog('Got local stream: ' + this.localStream.getTracks().length + ' tracks');

                    this.displayLocalStream();
                    this.createPeerConnection();
                    this.setupSignaling();

                    // Send join message
                    await this.sendSignal('join', {
                        userId: this.userId
                    });
                    debugLog('Join signal sent');

                    this.updateStatus('Ready - Waiting for peer...');
                    document.getElementById('leaveCall').disabled = false;

                } catch (error) {
                    debugLog('Start call error: ' + error.message);
                    this.updateStatus('Error: ' + error.message);
                }
            }

            displayLocalStream() {
                const localPlayer = document.getElementById('local-player');
                localPlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = this.localStream;
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                localPlayer.appendChild(video);
                debugLog('Local stream displayed');
            }

            createPeerConnection() {
                debugLog('Creating peer connection');
                const config = {
                    iceServers: [{
                        urls: 'stun:stun.l.google.com:19302'
                    }]
                };

                this.peerConnection = new RTCPeerConnection(config);

                // Add local tracks
                this.localStream.getTracks().forEach(track => {
                    this.peerConnection.addTrack(track, this.localStream);
                    debugLog('Added track: ' + track.kind);
                });

                // Handle remote stream
                this.peerConnection.ontrack = (event) => {
                    debugLog('✅ Received remote stream!');
                    const remoteStream = event.streams[0];
                    this.displayRemoteStream(remoteStream);
                    this.updateStatus('✅ Call Connected!');
                    this.isCallActive = true;
                };

                // ICE candidates
                this.peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        debugLog('Generated ICE candidate');
                        this.sendSignal('ice-candidate', {
                            candidate: event.candidate,
                            targetUserId: this.remoteUserId
                        });
                    }
                };

                this.peerConnection.onconnectionstatechange = () => {
                    const state = this.peerConnection.connectionState;
                    debugLog('Connection state: ' + state);
                    this.updateStatus('Connection: ' + state);
                };

                this.peerConnection.oniceconnectionstatechange = () => {
                    debugLog('ICE connection state: ' + this.peerConnection.iceConnectionState);
                };
            }

            displayRemoteStream(remoteStream) {
                const remotePlayer = document.getElementById('remote-player');
                remotePlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = remoteStream;
                video.autoplay = true;
                video.playsInline = true;
                remotePlayer.appendChild(video);
                debugLog('Remote stream displayed');
            }

            async createOffer() {
                try {
                    debugLog('Creating offer...');
                    this.updateStatus('Creating offer...');

                    const offer = await this.peerConnection.createOffer();
                    await this.peerConnection.setLocalDescription(offer);

                    await this.sendSignal('offer', {
                        sdp: this.peerConnection.localDescription,
                        targetUserId: this.remoteUserId
                    });

                    debugLog('Offer sent');
                    this.updateStatus('Offer sent');

                } catch (error) {
                    debugLog('Create offer error: ' + error.message);
                    this.updateStatus('Offer error: ' + error.message);
                }
            }

            async handleOffer(offerSdp, fromUserId) {
                try {
                    debugLog('Handling offer from: ' + fromUserId);

                    if (this.isCallActive) {
                        debugLog('Call already active, ignoring offer');
                        return;
                    }

                    this.updateStatus('Received offer...');
                    this.remoteUserId = fromUserId;

                    // CLEAN THE SDP BEFORE USING IT
                    const cleanedOffer = cleanSdp(offerSdp);
                    await this.peerConnection.setRemoteDescription(cleanedOffer);

                    const answer = await this.peerConnection.createAnswer();
                    await this.peerConnection.setLocalDescription(answer);

                    await this.sendSignal('answer', {
                        sdp: this.peerConnection.localDescription,
                        targetUserId: fromUserId
                    });

                    debugLog('Answer sent');
                    this.updateStatus('Answer sent');

                } catch (error) {
                    debugLog('Handle offer error: ' + error.message);
                    this.updateStatus('Offer handling error: ' + error.message);
                }
            }

            async handleAnswer(answerSdp) {
                try {
                    debugLog('Handling answer');
                    // CLEAN THE SDP BEFORE USING IT
                    const cleanedAnswer = cleanSdp(answerSdp);
                    await this.peerConnection.setRemoteDescription(cleanedAnswer);
                    debugLog('Call established!');
                    this.updateStatus('✅ Connected!');

                } catch (error) {
                    debugLog('Handle answer error: ' + error.message);
                    this.updateStatus('Answer error: ' + error.message);
                }
            }

            async handleIceCandidate(candidate) {
                try {
                    debugLog('Adding ICE candidate');
                    await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (error) {
                    debugLog('ICE candidate error: ' + error.message);
                }
            }

            async sendSignal(type, data) {
                try {
                    debugLog('Sending signal: ' + type);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // CLEAN SDP DATA IF IT EXISTS
                    let cleanedData = data;
                    if (data.sdp) {
                        cleanedData = {
                            ...data,
                            sdp: cleanSdp(data.sdp)
                        };
                    }

                    const response = await fetch('/webrtc/signal', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            room: this.room,
                            type: type,
                            data: cleanedData,
                            userId: this.userId
                        })
                    });

                    const result = await response.json();
                    debugLog('Signal sent: ' + type);

                } catch (error) {
                    debugLog('Send signal error: ' + error.message);
                }
            }

            setupSignaling() {
                if (this.signalingInterval) return;

                debugLog('Starting signaling polling');
                this.signalingInterval = setInterval(async () => {
                    try {
                        const url = `/webrtc/signal?room=${this.room}&userId=${this.userId}`;
                        const response = await fetch(url);
                        const data = await response.json();

                        if (data.signals && Array.isArray(data.signals)) {
                            debugLog(`Polling: ${data.signals.length} signal(s) found`);
                            for (const signal of data.signals) {
                                if (signal.fromUserId === this.userId) continue;
                                debugLog(`Processing: ${signal.type} from ${signal.fromUserId}`);
                                this.handleIncomingSignal(signal);
                            }
                        }

                    } catch (error) {
                        debugLog('Polling error: ' + error.message);
                    }
                }, 2000);
            }

            handleIncomingSignal(signal) {
                debugLog('Handling signal: ' + signal.type);

                switch (signal.type) {
                    case 'join':
                        debugLog('Peer joined: ' + signal.fromUserId);
                        if (!this.isCallActive && !this.remoteUserId) {
                            this.remoteUserId = signal.fromUserId;
                            this.updateStatus('Peer found - creating offer...');
                            setTimeout(() => this.createOffer(), 1000);
                        }
                        break;

                    case 'offer':
                        this.handleOffer(signal.data.sdp, signal.fromUserId);
                        break;

                    case 'answer':
                        this.handleAnswer(signal.data.sdp);
                        break;

                    case 'ice-candidate':
                        this.handleIceCandidate(signal.data.candidate);
                        break;
                }
            }

            leaveCall() {
                debugLog('Leaving call');

                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => track.stop());
                    this.localStream = null;
                }

                if (this.peerConnection) {
                    this.peerConnection.close();
                    this.peerConnection = null;
                }

                if (this.signalingInterval) {
                    clearInterval(this.signalingInterval);
                    this.signalingInterval = null;
                }

                this.isCallActive = false;
                this.remoteUserId = null;

                document.getElementById('local-player').innerHTML = '';
                document.getElementById('remote-player').innerHTML = '';
                document.getElementById('leaveCall').disabled = true;

                this.updateStatus('Disconnected');
                debugLog('Call ended');
            }

            updateStatus(message) {
                document.getElementById('status').textContent = message;
                debugLog('Status: ' + message);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new WebRTCApp();
        });
    </script>
</body>

</html>