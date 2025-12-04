<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>WebRTC Call System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .call-type-selector {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .call-type-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }

        .btn-call-type {
            padding: 10px 30px;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-call-type.private {
            background: #007bff;
            color: white;
        }

        .btn-call-type.group {
            background: #28a745;
            color: white;
        }

        .btn-call-type.active {
            border-color: #000;
            transform: scale(1.05);
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

        /* Video containers */
        .video-areas {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .video-area {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            min-height: 300px;
        }

        .video-box {
            background: #000;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            border: 2px solid #ddd;
        }

        .video-box.local {
            width: 400px;
            height: 300px;
        }

        .video-box.remote {
            width: 300px;
            height: 225px;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            display: block;
            background: #000;
        }

        .video-label {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Grid layout for group calls */
        .remote-videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            width: 100%;
            margin-top: 10px;
        }

        .participant-count {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            color: #495057;
        }

        .status {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-weight: bold;
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

        .user-id-display {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 6px;
            font-weight: bold;
        }

        .connection-status {
            text-align: center;
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }

        /* Call timer styles */
        .call-timer-container {
            text-align: center;
            margin: 15px 0;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .call-timer-container.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        .call-timer-container.warning {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
        }

        .call-timer-container.critical {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        }

        .call-timer-title {
            font-size: 14px;
            margin-bottom: 5px;
            opacity: 0.9;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .call-timer {
            font-size: 28px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .call-timer.warning {
            color: #ffc107;
        }

        .call-timer.critical {
            color: #dc3545;
            animation: blink 1s infinite;
        }

        .call-timer-subtext {
            font-size: 12px;
            margin-top: 5px;
            opacity: 0.8;
        }

        .time-limit-info {
            text-align: center;
            margin: 10px 0;
            padding: 8px;
            background: #e7f3ff;
            border-radius: 6px;
            color: #007bff;
            font-size: 14px;
            display: none;
        }

        .time-limit-info.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .time-limit-info.critical {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            animation: blinkBg 1s infinite;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        @keyframes blinkBg {

            0%,
            100% {
                background-color: #f8d7da;
            }

            50% {
                background-color: #f1b0b7;
            }
        }

        .call-timer.pulse {
            animation: pulse 2s infinite;
        }

        .time-master-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: gold;
            color: #000;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="title">WebRTC Calling System</h2>

        <div class="user-id-display">
            Your ID: <span id="userIdDisplay">Loading...</span>
            <span id="timeMasterBadge" style="display: none; margin-left: 10px; background: gold; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 12px;">⏰ Time Master</span>
        </div>

        <!-- Time Limit Info -->
        <div class="time-limit-info" id="timeLimitInfo">
            Time Limit: 2 minutes | <span id="timeRemaining">02:00</span> remaining
        </div>

        <!-- Call Timer -->
        <div class="call-timer-container" id="callTimerContainer">
            <div class="call-timer-title">Call Duration</div>
            <div class="call-timer" id="callTimer">00:00:00</div>
            <div class="call-timer-subtext" id="callTypeLabel">Private Call</div>
        </div>

        <div class="call-type-selector">
            <h3>Select Call Type</h3>
            <div class="call-type-buttons">
                <button id="selectPrivate" class="btn-call-type private active">Private Call (1-to-1)</button>
                <button id="selectGroup" class="btn-call-type group">Group Call</button>
            </div>
        </div>

        <div class="status" id="status">Ready to connect</div>
        <div class="connection-status" id="connectionStatus"></div>
        <div class="error" id="error"></div>

        <div class="button-group">
            <button id="joinVideo" class="btn video">Join Video Call</button>
            <button id="joinAudio" class="btn audio">Join Audio Call</button>
            <button id="leaveCall" class="btn leave" disabled>Leave Call</button>
        </div>

        <!-- Private Call Video Area -->
        <div id="privateVideoArea" class="video-areas">
            <div class="video-area">
                <div class="video-box local">
                    <div id="local-player"></div>
                    <div class="video-label">You</div>
                </div>
                <div class="video-box remote">
                    <div id="remote-player"></div>
                    <div class="video-label" id="remote-label">Remote</div>
                </div>
            </div>
        </div>

        <!-- Group Call Video Area -->
        <div id="groupVideoArea" class="video-areas" style="display: none;">
            <div class="participant-count">
                Participants: <span id="participantCount">1</span> |
                Connections: <span id="connectionCount">0</span>
            </div>
            <div class="video-area">
                <div class="video-box local">
                    <div id="local-player-group"></div>
                    <div class="video-label">You</div>
                    <div class="time-master-indicator" id="localTimeMasterIndicator" style="display: none;">⏰</div>
                </div>
            </div>
            <div id="remote-videos-group" class="remote-videos-grid"></div>
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
    </div>

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

            debugLog('=== TESTING PUSHER SIGNALING ===');
            debugLog(`User ID: ${userId}`);
            debugLog(`Room: ${room}`);

            try {
                const postResponse = await fetch('/webrtc/signal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        room: room,
                        type: 'test',
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
                debugLog('✅ Signal sent via Pusher - check Pusher Dashboard Debug Console');
            } catch (error) {
                debugLog('❌ POST Error: ' + error.message);
                return;
            }
        }

        async function checkBackend() {
            debugLog('=== CHECKING BACKEND ===');
            try {
                const response = await fetch('/webrtc/signal?room=test-room&userId=checker');
                const result = await response.json();
                debugLog('Backend response: ' + JSON.stringify(result));
                debugLog('Backend is running');
            } catch (error) {
                debugLog('Backend check error: ' + error.message);
            }
        }

        // SDP Cleaning Function
        function cleanSdp(sdp) {
            if (!sdp || !sdp.sdp) return sdp;

            const lines = sdp.sdp.split(/\r\n|\n|\r/);
            const nonEmptyLines = lines.filter(line => line.trim() !== '');

            const cleanedSdp = {
                type: sdp.type,
                sdp: nonEmptyLines.join('\r\n') + '\r\n'
            };

            return cleanedSdp;
        }

        // Timer Class with Synchronized Time Limit
        class CallTimer {
            constructor(timeLimitSeconds = 120) {
                this.startTime = null;
                this.timerInterval = null;
                this.elapsedTime = 0;
                this.isRunning = false;
                this.timeLimitSeconds = timeLimitSeconds;
                this.warningThreshold = 60;
                this.warningTriggered = false;
                this.endWarningTriggered = false;
                this.onTimeLimitWarning = null;
                this.onTimeLimitReached = null;
                this.timeLimitCheckInterval = null;

                // For synchronized timing
                this.callStartTime = null;
                this.isTimeMaster = false;
            }

            // Start timer based on call start time
            startWithCallStartTime(callStartTime, isTimeMaster = false) {
                if (this.isRunning) return;

                this.callStartTime = callStartTime;
                this.isTimeMaster = isTimeMaster;
                this.isRunning = true;
                this.warningTriggered = false;
                this.endWarningTriggered = false;

                // If we're the time master, we'll use local start time
                if (isTimeMaster) {
                    this.startTime = Date.now();
                }

                this.timerInterval = setInterval(() => this.updateDisplay(), 1000);
                this.startTimeLimitCheck();
                this.updateDisplay();

                debugLog(`Timer started with call start time: ${new Date(callStartTime).toLocaleTimeString()}, Master: ${isTimeMaster}`);
            }

            // Calculate remaining time based on call start time
            getSynchronizedRemainingTime() {
                if (!this.callStartTime) return this.timeLimitSeconds;

                const now = Date.now();
                const elapsed = Math.floor((now - this.callStartTime) / 1000);
                const remaining = this.timeLimitSeconds - elapsed;

                return Math.max(0, remaining);
            }

            getSynchronizedElapsedTime() {
                if (!this.callStartTime) return 0;

                const now = Date.now();
                return Math.floor((now - this.callStartTime) / 1000);
            }

            // Start timer locally (for private calls)
            start() {
                if (this.isRunning) return;

                this.startTime = Date.now();
                this.isRunning = true;
                this.warningTriggered = false;
                this.endWarningTriggered = false;
                this.timerInterval = setInterval(() => this.updateDisplay(), 1000);
                this.startTimeLimitCheck();
                this.updateDisplay();

                debugLog('Private timer started');
            }

            stop() {
                if (!this.isRunning) return;

                clearInterval(this.timerInterval);
                clearInterval(this.timeLimitCheckInterval);
                this.isRunning = false;
                this.elapsedTime += Date.now() - this.startTime;

                debugLog('Timer stopped. Total synchronized time: ' + this.formatTime(this.getSynchronizedElapsedTime()));
            }

            reset() {
                this.stop();
                this.startTime = null;
                this.callStartTime = null;
                this.elapsedTime = 0;
                this.warningTriggered = false;
                this.endWarningTriggered = false;
                this.isTimeMaster = false;
                this.updateDisplay('00:00:00');
                this.updateTimeLimitInfo('normal');

                // Hide time master badge
                const badge = document.getElementById('timeMasterBadge');
                if (badge) badge.style.display = 'none';

                const localIndicator = document.getElementById('localTimeMasterIndicator');
                if (localIndicator) localIndicator.style.display = 'none';
            }

            startTimeLimitCheck() {
                if (this.timeLimitCheckInterval) {
                    clearInterval(this.timeLimitCheckInterval);
                }

                this.timeLimitCheckInterval = setInterval(() => {
                    this.checkTimeLimit();
                }, 1000);
            }

            checkTimeLimit() {
                if (!this.isRunning) return;

                const remainingSeconds = this.getSynchronizedRemainingTime();

                // Update time remaining display
                this.updateTimeRemaining(remainingSeconds);

                // Check for 1-minute warning
                if (remainingSeconds <= 60 && remainingSeconds > 0 && !this.warningTriggered) {
                    this.warningTriggered = true;
                    if (this.onTimeLimitWarning) {
                        this.onTimeLimitWarning(remainingSeconds);
                    }
                    this.updateTimeLimitInfo('warning');
                }

                // Check for 30-second critical warning
                if (remainingSeconds <= 30 && remainingSeconds > 0 && !this.endWarningTriggered) {
                    this.endWarningTriggered = true;
                    this.updateTimeLimitInfo('critical');
                }

                // Check if time limit reached
                if (remainingSeconds <= 0) {
                    debugLog('⏰ SYNCHRONIZED TIME LIMIT REACHED - Call ending for everyone');
                    if (this.onTimeLimitReached) {
                        this.onTimeLimitReached();
                    }
                    this.stop();
                }
            }

            updateTimeRemaining(remainingSeconds) {
                const timeLimitInfo = document.getElementById('timeLimitInfo');
                const timeRemaining = document.getElementById('timeRemaining');

                if (timeRemaining) {
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;
                    timeRemaining.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }

                if (timeLimitInfo && remainingSeconds <= 0) {
                    timeLimitInfo.style.display = 'none';
                }
            }

            updateTimeLimitInfo(state) {
                const timeLimitInfo = document.getElementById('timeLimitInfo');
                const timerContainer = document.getElementById('callTimerContainer');
                const timerElement = document.getElementById('callTimer');

                if (!timeLimitInfo) return;

                timeLimitInfo.className = 'time-limit-info';

                switch (state) {
                    case 'normal':
                        timeLimitInfo.textContent = 'Time Limit: 2 minutes | ';
                        const remainingSpan = document.createElement('span');
                        remainingSpan.id = 'timeRemaining';
                        remainingSpan.textContent = '02:00';
                        timeLimitInfo.appendChild(remainingSpan);
                        timeLimitInfo.style.display = 'block';

                        if (timerContainer) {
                            timerContainer.classList.remove('warning', 'critical');
                        }
                        if (timerElement) {
                            timerElement.classList.remove('warning', 'critical');
                        }
                        break;

                    case 'warning':
                        timeLimitInfo.textContent = '⚠️ Warning: Call will end in ';
                        const warningSpan = document.createElement('span');
                        warningSpan.id = 'timeRemaining';
                        warningSpan.style.fontWeight = 'bold';
                        warningSpan.textContent = '01:00';
                        timeLimitInfo.appendChild(warningSpan);
                        timeLimitInfo.classList.add('warning');

                        if (timerContainer) {
                            timerContainer.classList.add('warning');
                            timerContainer.classList.remove('critical');
                        }
                        if (timerElement) {
                            timerElement.classList.add('warning');
                            timerElement.classList.remove('critical');
                        }
                        break;

                    case 'critical':
                        timeLimitInfo.textContent = '🚨 Call ending in ';
                        const criticalSpan = document.createElement('span');
                        criticalSpan.id = 'timeRemaining';
                        criticalSpan.style.fontWeight = 'bold';
                        timeLimitInfo.appendChild(criticalSpan);
                        timeLimitInfo.classList.add('critical');

                        if (timerContainer) {
                            timerContainer.classList.remove('warning');
                            timerContainer.classList.add('critical');
                        }
                        if (timerElement) {
                            timerElement.classList.remove('warning');
                            timerElement.classList.add('critical');
                        }
                        break;
                }
            }

            updateDisplay(customTime = null) {
                const timerElement = document.getElementById('callTimer');
                if (!timerElement) return;

                if (customTime) {
                    timerElement.textContent = customTime;
                    return;
                }

                let totalSeconds;
                if (this.callStartTime) {
                    // Use synchronized time
                    totalSeconds = this.getSynchronizedElapsedTime();
                } else {
                    // Fallback to local time (for private calls)
                    totalSeconds = this.getTotalSeconds();
                }

                // Never show negative time
                totalSeconds = Math.max(0, totalSeconds);

                timerElement.textContent = this.formatTime(totalSeconds);

                // Style based on remaining time
                if (this.callStartTime) {
                    const remaining = this.getSynchronizedRemainingTime();
                    if (remaining <= 30 && remaining > 0) {
                        timerElement.classList.add('critical');
                        timerElement.classList.remove('warning');
                    } else if (remaining <= 60 && remaining > 0) {
                        timerElement.classList.add('warning');
                        timerElement.classList.remove('critical');
                    } else {
                        timerElement.classList.remove('warning', 'critical');
                    }
                }

                // Show time master badge if applicable
                if (this.isTimeMaster) {
                    const badge = document.getElementById('timeMasterBadge');
                    if (badge) badge.style.display = 'inline-block';

                    const localIndicator = document.getElementById('localTimeMasterIndicator');
                    if (localIndicator) localIndicator.style.display = 'block';
                }

                // Add pulse animation every 60 seconds
                if (totalSeconds % 60 === 0 && totalSeconds > 0) {
                    timerElement.classList.add('pulse');
                    setTimeout(() => timerElement.classList.remove('pulse'), 2000);
                }
            }

            getTotalSeconds() {
                if (!this.isRunning) {
                    return Math.floor(this.elapsedTime / 1000);
                }
                return Math.floor((this.elapsedTime + (Date.now() - this.startTime)) / 1000);
            }

            formatTime(totalSeconds) {
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                return [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0')
                ].join(':');
            }

            getFormattedDuration() {
                const totalSeconds = this.getSynchronizedElapsedTime();
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                if (hours > 0) {
                    return `${hours}h ${minutes}m ${seconds}s`;
                } else if (minutes > 0) {
                    return `${minutes}m ${seconds}s`;
                } else {
                    return `${seconds}s`;
                }
            }

            getRemainingTime() {
                if (this.callStartTime) {
                    return this.getSynchronizedRemainingTime();
                }
                return this.timeLimitSeconds - this.getTotalSeconds();
            }

            updateTimeMasterBadge() {
                const badge = document.getElementById('timeMasterBadge');
                const localIndicator = document.getElementById('localTimeMasterIndicator');

                if (this.isTimeMaster) {
                    if (badge) badge.style.display = 'inline-block';
                    if (localIndicator) localIndicator.style.display = 'block';
                } else {
                    if (badge) badge.style.display = 'none';
                    if (localIndicator) localIndicator.style.display = 'none';
                }
            }
        }

        // WebRTC App Class with Synchronized Timer
        class WebRTCApp {
            constructor() {
                this.localStream = null;
                this.room = 'test-room';
                this.userId = 'user-' + Math.random().toString(36).substring(7);
                this.callType = 'private';
                this.callStartTime = null; // Global call start time for group calls

                // Timer instance with 2-minute limit
                this.callTimer = new CallTimer(120);

                // Set up timer callbacks
                this.callTimer.onTimeLimitWarning = (remainingSeconds) => {
                    this.handleTimeLimitWarning(remainingSeconds);
                };

                this.callTimer.onTimeLimitReached = () => {
                    this.handleTimeLimitReached();
                };

                // Private call properties
                this.privatePeerConnection = null;
                this.remoteUserId = null;
                this.pendingOffer = null;
                this.pendingIceCandidates = [];
                this.iceCandidateBuffer = [];
                this.iceCandidateTimer = null;

                // Group call properties
                this.groupPeerConnections = new Map();
                this.groupParticipants = new Map();
                this.remoteStreams = new Map();
                this.activeGroupConnections = new Set();
                this.pendingGroupOffers = new Map();
                this.groupConnectionMonitor = null;
                this.isCallActive = false;
                this.hasSentJoin = false;
                this.lastHeartbeat = Date.now();
                this.heartbeatInterval = null;

                debugLog('WebRTC App initialized. User ID: ' + this.userId);
                debugLog('Call Type: ' + this.callType);
                debugLog('Time Limit: 2 minutes (120 seconds)');

                // Initialize Pusher
                this.pusher = new Pusher('03e08e02c65168ea4c04', {
                    cluster: 'ap2',
                    forceTLS: true
                });

                this.channel = this.pusher.subscribe('webrtc.' + this.room);
                this.updateUserIdDisplay();

                setTimeout(() => {
                    this.setupEventListeners();
                    this.setupPusherListeners();
                }, 100);
            }

            handleTimeLimitWarning(remainingSeconds) {
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;

                const warningMessage = `⚠️ Call will end in ${minutes}:${seconds.toString().padStart(2, '0')}`;
                this.updateStatus(warningMessage);
                this.showError(warningMessage);

                debugLog(`⏰ TIME WARNING: ${remainingSeconds} seconds remaining`);

                // Show notification
                if (Notification.permission === "granted") {
                    new Notification("Call Time Warning", {
                        body: warningMessage,
                        icon: "https://img.icons8.com/color/96/000000/time.png"
                    });
                }
            }

            handleTimeLimitReached() {
                debugLog('⏰ TIME LIMIT REACHED - Ending call automatically');

                const endMessage = "⏰ Time's up! Call ended automatically after 2 minutes.";
                this.updateStatus(endMessage);
                this.showError(endMessage);

                // Show notification
                if (Notification.permission === "granted") {
                    new Notification("Call Ended", {
                        body: endMessage,
                        icon: "https://img.icons8.com/color/96/000000/clock.png"
                    });
                }

                // End the call
                this.leaveCall();

                // Disable join buttons temporarily
                const joinVideoBtn = document.getElementById('joinVideo');
                const joinAudioBtn = document.getElementById('joinAudio');
                const leaveCallBtn = document.getElementById('leaveCall');

                if (joinVideoBtn) joinVideoBtn.disabled = true;
                if (joinAudioBtn) joinAudioBtn.disabled = true;
                if (leaveCallBtn) leaveCallBtn.disabled = true;

                // Re-enable join buttons after 5 seconds
                setTimeout(() => {
                    if (joinVideoBtn) joinVideoBtn.disabled = false;
                    if (joinAudioBtn) joinAudioBtn.disabled = false;
                    this.updateStatus("Call ended due to time limit. Ready to start new call.");
                }, 5000);
            }

            updateUserIdDisplay() {
                const userIdDisplay = document.getElementById('userIdDisplay');
                if (userIdDisplay) {
                    userIdDisplay.textContent = this.userId;
                }
            }

            setupPusherListeners() {
                this.channel.bind('webrtc.signal', (data) => {
                    if (data.fromUserId === this.userId) return;

                    debugLog(`Pusher: Received ${data.type} from ${data.fromUserId}`);
                    this.handleIncomingSignal(data);
                });

                this.pusher.connection.bind('connected', () => {
                    debugLog('✅ Pusher connected successfully');
                });

                this.pusher.connection.bind('error', (err) => {
                    debugLog('❌ Pusher error: ' + err);
                });
            }

            setupEventListeners() {
                const selectPrivateBtn = document.getElementById('selectPrivate');
                const selectGroupBtn = document.getElementById('selectGroup');

                if (selectPrivateBtn && selectGroupBtn) {
                    selectPrivateBtn.addEventListener('click', () => this.setCallType('private'));
                    selectGroupBtn.addEventListener('click', () => this.setCallType('group'));
                }

                const joinVideoBtn = document.getElementById('joinVideo');
                const joinAudioBtn = document.getElementById('joinAudio');
                const leaveCallBtn = document.getElementById('leaveCall');

                if (joinVideoBtn) {
                    joinVideoBtn.addEventListener('click', () => this.startCall('video'));
                }
                if (joinAudioBtn) {
                    joinAudioBtn.addEventListener('click', () => this.startCall('audio'));
                }
                if (leaveCallBtn) {
                    leaveCallBtn.addEventListener('click', () => {
                        debugLog('Leave button clicked');
                        this.leaveCall();
                    });
                }

                // Request notification permission
                if ("Notification" in window && Notification.permission === "default") {
                    Notification.requestPermission();
                }
            }

            setCallType(type) {
                this.callType = type;

                const privateBtn = document.getElementById('selectPrivate');
                const groupBtn = document.getElementById('selectGroup');
                const privateArea = document.getElementById('privateVideoArea');
                const groupArea = document.getElementById('groupVideoArea');

                if (type === 'private') {
                    privateBtn.classList.add('active');
                    groupBtn.classList.remove('active');
                    privateArea.style.display = 'block';
                    groupArea.style.display = 'none';
                } else {
                    privateBtn.classList.remove('active');
                    groupBtn.classList.add('active');
                    privateArea.style.display = 'none';
                    groupArea.style.display = 'block';
                }

                debugLog('Call type changed to: ' + type);
                this.updateStatus('Call type: ' + type + ' - Ready to connect');

                if (this.isCallActive) {
                    this.leaveCall();
                }
            }

            showCallTimer() {
                const timerContainer = document.getElementById('callTimerContainer');
                const callTypeLabel = document.getElementById('callTypeLabel');
                const timeLimitInfo = document.getElementById('timeLimitInfo');

                if (timerContainer) {
                    timerContainer.classList.add('active');
                    callTypeLabel.textContent = this.callType === 'private' ? 'Private Call' : 'Group Call';
                }

                if (timeLimitInfo) {
                    this.callTimer.updateTimeLimitInfo('normal');
                }

                // Update time master badge
                this.callTimer.updateTimeMasterBadge();
            }

            hideCallTimer() {
                const timerContainer = document.getElementById('callTimerContainer');
                const timeLimitInfo = document.getElementById('timeLimitInfo');

                if (timerContainer) {
                    timerContainer.classList.remove('active', 'warning', 'critical');
                }

                if (timeLimitInfo) {
                    timeLimitInfo.style.display = 'none';
                }

                this.callTimer.reset();
            }

            // Send call start time to new participants
            async sendCallStartTime(targetUserId) {
                if (!this.callStartTime) return;

                await this.sendSignal('call-start-time', {
                    callStartTime: this.callStartTime,
                    timeLimitSeconds: 120,
                    senderUserId: this.userId
                });
            }

            // Handle call start time from other users
            handleCallStartTime(signal) {
                const {
                    callStartTime,
                    timeLimitSeconds,
                    senderUserId
                } = signal.data;

                debugLog(`Received call start time from ${senderUserId}: ${new Date(callStartTime).toLocaleTimeString()}`);

                // Only update if we don't have a start time or if this is earlier than our current one
                if (!this.callStartTime || callStartTime < this.callStartTime) {
                    this.callStartTime = callStartTime;
                    this.callTimer.callStartTime = callStartTime;
                    this.callTimer.isTimeMaster = false;

                    debugLog(`Updated call start time to: ${new Date(callStartTime).toLocaleTimeString()}`);

                    // If timer isn't running, start it with synchronized time
                    if (!this.callTimer.isRunning) {
                        this.callTimer.startWithCallStartTime(callStartTime, false);
                        this.showCallTimer();
                    }

                    // Broadcast this to other participants
                    this.broadcastCallStartTime();
                }
            }

            // Broadcast call start time to all participants
            async broadcastCallStartTime() {
                if (!this.callStartTime) return;

                Array.from(this.groupParticipants.keys()).forEach(async (participantId) => {
                    if (participantId !== this.userId) {
                        await this.sendCallStartTime(participantId);
                    }
                });
            }

            sendHeartbeat() {
                if (this.isCallActive && this.callType === 'group') {
                    const remainingTime = this.callTimer.getSynchronizedRemainingTime();

                    this.sendSignal('heartbeat', {
                        userId: this.userId,
                        timestamp: Date.now(),
                        callStartTime: this.callStartTime,
                        remainingTime: remainingTime,
                        isTimeMaster: this.callTimer.isTimeMaster
                    });
                    this.lastHeartbeat = Date.now();
                }
            }

            // Handle heartbeat with time info
            handleHeartbeat(signal) {
                const {
                    userId,
                    callStartTime,
                    remainingTime,
                    isTimeMaster
                } = signal.data;
                if (userId === this.userId) return;

                const participant = this.groupParticipants.get(userId);
                if (participant) {
                    participant.lastSeen = Date.now();

                    // Update call start time if we receive an earlier one
                    if (callStartTime && (!this.callStartTime || callStartTime < this.callStartTime)) {
                        this.callStartTime = callStartTime;
                        this.callTimer.callStartTime = callStartTime;
                        this.callTimer.isTimeMaster = false;

                        debugLog(`Updated call start time from ${userId} heartbeat: ${new Date(callStartTime).toLocaleTimeString()}`);

                        if (this.callTimer.isRunning) {
                            this.callTimer.stop();
                            this.callTimer.startWithCallStartTime(callStartTime, false);
                        } else {
                            this.callTimer.startWithCallStartTime(callStartTime, false);
                        }
                        this.showCallTimer();
                    }
                }
            }

            async startCall(mode) {
                try {
                    await this.leaveCall();

                    this.updateStatus('Getting media access...');
                    debugLog('Starting ' + this.callType + ' call...');

                    // Get media with specific constraints for better compatibility
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: mode === 'video' ? {
                            width: {
                                ideal: 640
                            },
                            height: {
                                ideal: 480
                            },
                            frameRate: {
                                ideal: 30
                            }
                        } : false,
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true
                        }
                    });

                    this.displayLocalStream();
                    this.remoteUserId = null;
                    this.pendingOffer = null;
                    this.pendingIceCandidates = [];
                    this.hasSentJoin = false;
                    this.activeGroupConnections.clear();
                    this.pendingGroupOffers.clear();
                    this.lastHeartbeat = Date.now();
                    this.callStartTime = null; // Reset for new call

                    // Reset and hide timer initially
                    this.hideCallTimer();

                    if (this.callType === 'private') {
                        this.updateStatus('Waiting for peer...');
                        this.createPrivatePeerConnection();

                        await this.sendSignal('join', {
                            userId: this.userId,
                            callType: 'private',
                            mode: mode
                        });

                        this.hasSentJoin = true;
                    } else {
                        await this.joinGroupCall(mode);
                    }

                    document.getElementById('leaveCall').disabled = false;
                    this.isCallActive = true;

                } catch (error) {
                    debugLog('Start call error: ' + error.message);
                    this.updateStatus('Error: ' + error.message);
                    this.showError('Failed to start call: ' + error.message);
                }
            }

            async joinGroupCall(mode) {
                try {
                    this.updateStatus('Joining group call...');
                    debugLog('Joining group call...');

                    const joinTimestamp = Date.now();

                    // Check if we should be time master (first to join or earliest join time)
                    let isTimeMaster = false;
                    if (this.groupParticipants.size === 0) {
                        // We're the first one in our view
                        this.callStartTime = joinTimestamp;
                        isTimeMaster = true;
                        debugLog('I am the time master. Setting call start time.');
                    }

                    this.groupParticipants.set(this.userId, {
                        userId: this.userId,
                        connected: true,
                        lastSeen: Date.now(),
                        joinTime: joinTimestamp,
                        isTimeMaster: isTimeMaster
                    });

                    await this.sendSignal('join', {
                        userId: this.userId,
                        callType: 'group',
                        mode: mode,
                        joinTimestamp: joinTimestamp
                    });

                    this.hasSentJoin = true;
                    this.isCallActive = true;
                    this.addLocalParticipant();
                    this.startGroupConnectionMonitor();

                    // Start heartbeat for group calls
                    if (this.heartbeatInterval) clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = setInterval(() => this.sendHeartbeat(), 8000);

                    this.updateStatus('✅ Group call joined! Connecting to others...');

                } catch (error) {
                    debugLog('Join group call error: ' + error.message);
                    this.showError('Failed to join group: ' + error.message);
                }
            }

            handleGroupJoin(signal) {
                const {
                    userId,
                    joinTimestamp
                } = signal.data;
                if (userId === this.userId) return;

                debugLog(`New group participant: ${userId}`);

                if (!this.groupParticipants.has(userId)) {
                    const newParticipant = {
                        userId: userId,
                        joinTime: joinTimestamp || Date.now(),
                        connected: false,
                        lastSeen: Date.now(),
                        isTimeMaster: false
                    };

                    this.groupParticipants.set(userId, newParticipant);

                    this.updateParticipantCount();

                    // Determine if we need to update call start time
                    if (!this.callStartTime || joinTimestamp < this.callStartTime) {
                        this.callStartTime = joinTimestamp;
                        this.callTimer.callStartTime = joinTimestamp;
                        this.callTimer.isTimeMaster = (userId === this.userId);

                        debugLog(`Call start time updated to: ${new Date(joinTimestamp).toLocaleTimeString()}`);

                        // If we have enough participants and a start time, start the timer
                        if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                            this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                            this.showCallTimer();

                            // If we're the time master, broadcast start time to others
                            if (this.callTimer.isTimeMaster) {
                                this.broadcastCallStartTime();
                            }
                        }
                    }

                    // Send call start time to new participant if we have it
                    if (this.callStartTime) {
                        setTimeout(() => {
                            if (this.isCallActive && this.callType === 'group') {
                                this.sendCallStartTime(userId);
                            }
                        }, 1000);
                    }

                    // Try to connect after a short delay
                    setTimeout(() => {
                        if (this.isCallActive && this.callType === 'group') {
                            this.establishConnectionToParticipant(userId);
                        }
                    }, 500 + Math.random() * 1000);
                } else {
                    const participant = this.groupParticipants.get(userId);
                    if (participant) {
                        participant.lastSeen = Date.now();
                        participant.connected = true;
                    }
                }
            }

            async establishConnectionToParticipant(remoteUserId) {
                try {
                    if (remoteUserId === this.userId) return;

                    // Check if we already have a connection or one is in progress
                    const existingConnection = this.groupPeerConnections.get(remoteUserId);
                    if (existingConnection) {
                        const state = existingConnection.connectionState;
                        if (state === 'connected' || state === 'connecting') {
                            debugLog(`Already ${state} to ${remoteUserId}, skipping`);
                            return;
                        }
                    }

                    if (this.pendingGroupOffers.has(remoteUserId)) {
                        debugLog(`Already trying to connect to ${remoteUserId}, skipping`);
                        return;
                    }

                    debugLog(`Establishing connection to: ${remoteUserId}`);
                    this.pendingGroupOffers.set(remoteUserId, Date.now());

                    const config = {
                        iceServers: [
                            // STUN servers
                            {
                                urls: 'stun:stun.l.google.com:19302'
                            },
                            {
                                urls: 'stun:stun1.l.google.com:19302'
                            },
                            {
                                urls: 'stun:stun2.l.google.com:19302'
                            },

                            // TURN servers - Add these 3 lines
                            {
                                urls: 'turn:openrelay.metered.ca:80',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            },
                            {
                                urls: 'turn:openrelay.metered.ca:443',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            },
                            {
                                urls: 'turn:openrelay.metered.ca:443?transport=tcp',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            }
                        ],
                        iceTransportPolicy: 'all',
                        bundlePolicy: 'max-bundle',
                        rtcpMuxPolicy: 'require'
                    };
                    const peerConnection = new RTCPeerConnection(config);

                    // Store connection immediately
                    this.groupPeerConnections.set(remoteUserId, peerConnection);

                    // Add local tracks to the connection
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => {
                            peerConnection.addTrack(track, this.localStream);
                        });
                    }

                    // Handle incoming remote tracks
                    peerConnection.ontrack = (event) => {
                        debugLog(`✅ Received ${event.track.kind} track from: ${remoteUserId}`);

                        // Use the first stream
                        if (event.streams && event.streams.length > 0) {
                            const remoteStream = event.streams[0];

                            // Check if we have valid tracks
                            if (remoteStream.getTracks().length > 0) {
                                debugLog(`Stream has ${remoteStream.getTracks().length} track(s)`);

                                const participant = this.groupParticipants.get(remoteUserId);
                                if (participant) {
                                    participant.connected = true;
                                    participant.lastSeen = Date.now();
                                }

                                this.addRemoteParticipant(remoteUserId, remoteStream);
                                this.activeGroupConnections.add(remoteUserId);
                                this.updateConnectionStatus();

                                // Start timer if we have enough participants and a start time
                                if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                                    this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                                    this.showCallTimer();
                                }
                            } else {
                                debugLog(`Stream from ${remoteUserId} has no tracks`);
                            }
                        }
                    };

                    // Handle ICE candidates
                    peerConnection.onicecandidate = (event) => {
                        if (event.candidate && this.isCallActive) {
                            this.sendIceCandidate(remoteUserId, event.candidate);
                        }
                    };

                    // Monitor connection state
                    peerConnection.onconnectionstatechange = () => {
                        const state = peerConnection.connectionState;
                        debugLog(`Connection with ${remoteUserId}: ${state}`);

                        if (state === 'connected') {
                            this.activeGroupConnections.add(remoteUserId);
                            const participant = this.groupParticipants.get(remoteUserId);
                            if (participant) {
                                participant.connected = true;
                                participant.lastSeen = Date.now();
                            }
                            this.updateConnectionStatus();

                            // Start timer if we have enough participants and a start time
                            if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                                this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                                this.showCallTimer();
                            }
                        } else if (state === 'disconnected' || state === 'failed') {
                            this.activeGroupConnections.delete(remoteUserId);
                            const participant = this.groupParticipants.get(remoteUserId);
                            if (participant) participant.connected = false;

                            // Try to reconnect after delay
                            setTimeout(() => {
                                if (this.isCallActive && this.callType === 'group') {
                                    debugLog(`Attempting to reconnect to ${remoteUserId}`);
                                    this.establishConnectionToParticipant(remoteUserId);
                                }
                            }, 2000);
                        } else if (state === 'closed') {
                            this.removeConnection(remoteUserId);
                        }
                    };

                    // Create and send offer with proper configuration
                    const offerOptions = {
                        offerToReceiveAudio: true,
                        offerToReceiveVideo: true
                    };

                    const offer = await peerConnection.createOffer(offerOptions);

                    // Set codec preferences for better compatibility
                    if (offer.sdp) {
                        offer.sdp = this.preferCodec(offer.sdp, 'video', 'H264');
                    }

                    await peerConnection.setLocalDescription(offer);

                    await this.sendSignal('offer', {
                        sdp: peerConnection.localDescription,
                        targetUserId: remoteUserId,
                        callType: 'group'
                    });

                    debugLog(`✅ Offer sent to: ${remoteUserId}`);

                    // Clear pending offer after timeout
                    setTimeout(() => {
                        this.pendingGroupOffers.delete(remoteUserId);
                    }, 8000);

                } catch (error) {
                    debugLog(`❌ Failed to establish connection to ${remoteUserId}: ${error.message}`);
                    this.pendingGroupOffers.delete(remoteUserId);
                    this.removeConnection(remoteUserId);
                }
            }

            // Helper to prefer specific codecs
            preferCodec(sdp, type, codec) {
                const lines = sdp.split('\n');
                let mLine = -1;
                const codecRegex = new RegExp(`^a=rtpmap:(\\d+) ${codec}`, 'i');
                let codecNumber = null;

                // Find the codec number
                for (let i = 0; i < lines.length; i++) {
                    if (lines[i].startsWith(`m=${type}`)) {
                        mLine = i;
                    } else if (codecRegex.test(lines[i])) {
                        const match = lines[i].match(codecRegex);
                        codecNumber = match[1];
                    }
                }

                if (mLine === -1 || !codecNumber) {
                    return sdp;
                }

                // Reorder codecs to prefer our chosen codec
                const mLineParts = lines[mLine].split(' ');
                if (mLineParts.length > 3) {
                    // Put preferred codec first
                    const otherCodecs = mLineParts.slice(3).filter(c => c !== codecNumber);
                    lines[mLine] = mLineParts.slice(0, 3).concat(codecNumber, ...otherCodecs).join(' ');
                }

                return lines.join('\n');
            }

            async handleGroupOffer(offerSdp, fromUserId) {
                try {
                    debugLog(`Handling group offer from: ${fromUserId}`);

                    if (fromUserId === this.userId) return;
                    if (!this.isCallActive || this.callType !== 'group') return;

                    // Check if we already have a connection
                    if (this.groupPeerConnections.has(fromUserId)) {
                        const pc = this.groupPeerConnections.get(fromUserId);
                        if (pc.connectionState === 'connected' || pc.connectionState === 'connecting') {
                            debugLog(`Already have active connection to ${fromUserId}, ignoring offer`);
                            return;
                        }
                    }

                    // Add participant if not exists
                    if (!this.groupParticipants.has(fromUserId)) {
                        this.groupParticipants.set(fromUserId, {
                            userId: fromUserId,
                            joinTime: Date.now(),
                            connected: false,
                            lastSeen: Date.now()
                        });
                        this.updateParticipantCount();
                    }

                    const config = {
                        iceServers: [
                            // STUN servers
                            {
                                urls: 'stun:stun.l.google.com:19302'
                            },
                            {
                                urls: 'stun:stun1.l.google.com:19302'
                            },
                            {
                                urls: 'stun:stun2.l.google.com:19302'
                            },

                            // TURN servers - Add these 3 lines
                            {
                                urls: 'turn:openrelay.metered.ca:80',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            },
                            {
                                urls: 'turn:openrelay.metered.ca:443',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            },
                            {
                                urls: 'turn:openrelay.metered.ca:443?transport=tcp',
                                username: 'openrelayproject',
                                credential: 'openrelayproject'
                            }
                        ],
                        iceTransportPolicy: 'all',
                        bundlePolicy: 'max-bundle',
                        rtcpMuxPolicy: 'require'
                    };
                    const peerConnection = new RTCPeerConnection(config);
                    this.groupPeerConnections.set(fromUserId, peerConnection);

                    // Add local tracks
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(track => {
                            peerConnection.addTrack(track, this.localStream);
                        });
                    }

                    // Handle remote tracks
                    peerConnection.ontrack = (event) => {
                        debugLog(`✅ Received ${event.track.kind} track from: ${fromUserId}`);

                        if (event.streams && event.streams.length > 0) {
                            const remoteStream = event.streams[0];

                            if (remoteStream.getTracks().length > 0) {
                                this.addRemoteParticipant(fromUserId, remoteStream);
                                this.activeGroupConnections.add(fromUserId);

                                const participant = this.groupParticipants.get(fromUserId);
                                if (participant) participant.connected = true;

                                this.updateConnectionStatus();

                                // Start timer if we have enough participants and a start time
                                if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                                    this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                                    this.showCallTimer();
                                }
                            }
                        }
                    };

                    peerConnection.onicecandidate = (event) => {
                        if (event.candidate) {
                            this.sendIceCandidate(fromUserId, event.candidate);
                        }
                    };

                    peerConnection.onconnectionstatechange = () => {
                        const state = peerConnection.connectionState;
                        debugLog(`Connection with ${fromUserId}: ${state}`);

                        if (state === 'connected') {
                            this.activeGroupConnections.add(fromUserId);
                            const participant = this.groupParticipants.get(fromUserId);
                            if (participant) participant.connected = true;

                            // Start timer if we have enough participants and a start time
                            if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                                this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                                this.showCallTimer();
                            }
                        } else if (state === 'disconnected' || state === 'failed') {
                            this.activeGroupConnections.delete(fromUserId);
                            const participant = this.groupParticipants.get(fromUserId);
                            if (participant) participant.connected = false;
                        } else if (state === 'closed') {
                            this.removeConnection(fromUserId);
                        }
                        this.updateConnectionStatus();
                    };

                    // Set remote description
                    const cleanedOffer = cleanSdp(offerSdp);
                    await peerConnection.setRemoteDescription(cleanedOffer);

                    // Create answer
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);

                    await this.sendSignal('answer', {
                        sdp: peerConnection.localDescription,
                        targetUserId: fromUserId,
                        callType: 'group'
                    });

                    debugLog(`✅ Group answer sent to: ${fromUserId}`);

                } catch (error) {
                    debugLog(`❌ Handle group offer from ${fromUserId} error: ${error.message}`);
                    this.removeConnection(fromUserId);
                }
            }

            async handleGroupAnswer(answerSdp, fromUserId) {
                try {
                    const peerConnection = this.groupPeerConnections.get(fromUserId);
                    if (!peerConnection) {
                        debugLog(`No group peer connection found for ${fromUserId}`);
                        return;
                    }

                    const cleanedAnswer = cleanSdp(answerSdp);
                    await peerConnection.setRemoteDescription(cleanedAnswer);

                    debugLog(`✅ Group connected with: ${fromUserId}`);
                    this.activeGroupConnections.add(fromUserId);

                    const participant = this.groupParticipants.get(fromUserId);
                    if (participant) {
                        participant.connected = true;
                        participant.lastSeen = Date.now();
                    }

                    this.updateConnectionStatus();

                    // Start timer if we have enough participants and a start time
                    if (this.groupParticipants.size >= 2 && this.callStartTime && !this.callTimer.isRunning) {
                        this.callTimer.startWithCallStartTime(this.callStartTime, this.callTimer.isTimeMaster);
                        this.showCallTimer();
                    }

                } catch (error) {
                    debugLog(`❌ Handle group answer from ${fromUserId} error: ${error.message}`);
                }
            }

            displayLocalStream() {
                const localPlayer = this.callType === 'private' ?
                    document.getElementById('local-player') :
                    document.getElementById('local-player-group');

                localPlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = this.localStream;
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;
                video.setAttribute('playsinline', 'true');

                // Ensure video plays
                video.onloadedmetadata = () => {
                    video.play().catch(e => debugLog('Local video play error: ' + e));
                };

                localPlayer.appendChild(video);
            }

            addLocalParticipant() {
                this.updateParticipantCount();
                this.updateConnectionStatus();
            }

            addRemoteParticipant(userId, stream) {
                // Skip if already added and stream is the same
                if (this.remoteStreams.has(userId)) {
                    const existing = this.remoteStreams.get(userId);
                    if (existing.videoElement.srcObject === stream) {
                        return;
                    }
                    // Update existing stream
                    existing.videoElement.srcObject = stream;
                    return;
                }

                debugLog(`Adding remote participant to UI: ${userId}`);

                const remoteVideosGrid = document.getElementById('remote-videos-group');
                const videoContainer = document.createElement('div');
                videoContainer.className = 'video-box remote';
                videoContainer.id = `participant-${userId}`;

                const video = document.createElement('video');
                video.id = `video-${userId}`;
                video.srcObject = stream;
                video.autoplay = true;
                video.playsInline = true;
                video.setAttribute('playsinline', 'true');

                // Force video to play
                video.onloadedmetadata = () => {
                    debugLog(`Video metadata loaded for ${userId}`);
                    video.play().catch(e => debugLog(`Video play error for ${userId}: ${e}`));
                };

                video.onloadeddata = () => {
                    debugLog(`Video data loaded for ${userId}`);
                };

                const label = document.createElement('div');
                label.className = 'video-label';
                label.textContent = `User: ${userId.substring(0, 8)}`;

                // Add time master indicator if this user is the time master
                if (this.groupParticipants.get(userId)?.isTimeMaster) {
                    const timeMasterIndicator = document.createElement('div');
                    timeMasterIndicator.className = 'time-master-indicator';
                    timeMasterIndicator.textContent = '⏰';
                    videoContainer.appendChild(timeMasterIndicator);
                }

                videoContainer.appendChild(video);
                videoContainer.appendChild(label);
                remoteVideosGrid.appendChild(videoContainer);

                this.remoteStreams.set(userId, {
                    stream: stream,
                    videoElement: video,
                    container: videoContainer
                });

                this.updateParticipantCount();
                this.updateConnectionStatus();

                // Force play after a delay
                setTimeout(() => {
                    if (video.paused) {
                        video.play().catch(e => debugLog(`Delayed play error for ${userId}: ${e}`));
                    }
                }, 1000);
            }

            removeRemoteParticipant(userId) {
                debugLog(`Removing participant: ${userId}`);

                // Remove from participants list
                this.groupParticipants.delete(userId);

                // Remove connection
                this.removeConnection(userId);

                // Remove stream from UI and stop tracks
                const streamInfo = this.remoteStreams.get(userId);
                if (streamInfo) {
                    // Stop all tracks in the stream
                    if (streamInfo.stream) {
                        streamInfo.stream.getTracks().forEach(track => {
                            track.stop();
                        });
                    }

                    // Remove video element from DOM
                    if (streamInfo.container && streamInfo.container.parentNode) {
                        streamInfo.container.remove();
                    }

                    this.remoteStreams.delete(userId);
                }

                // Clean up other references
                this.activeGroupConnections.delete(userId);
                this.pendingGroupOffers.delete(userId);

                this.updateParticipantCount();
                this.updateConnectionStatus();

                // Check if we need to reassign time master
                this.reassignTimeMaster();

                // Stop timer if no participants left
                if (this.groupParticipants.size <= 1 && this.callTimer.isRunning) {
                    this.callTimer.stop();
                }
            }

            // Reassign time master if current master leaves
            reassignTimeMaster() {
                if (!this.callTimer.isTimeMaster) return;

                // Check if there are other participants
                const otherParticipants = Array.from(this.groupParticipants.keys()).filter(id => id !== this.userId);
                if (otherParticipants.length === 0) {
                    // We're the only one left, keep being time master
                    return;
                }

                // Find the participant with earliest join time
                let earliestParticipant = null;
                let earliestTime = Infinity;

                this.groupParticipants.forEach((participant, participantId) => {
                    if (participant.joinTime < earliestTime) {
                        earliestTime = participant.joinTime;
                        earliestParticipant = participantId;
                    }
                });

                // If someone else has earlier join time, they should be time master
                if (earliestParticipant !== this.userId) {
                    this.callTimer.isTimeMaster = false;
                    this.callStartTime = earliestTime;
                    this.callTimer.callStartTime = earliestTime;

                    // Stop and restart timer with new master
                    if (this.callTimer.isRunning) {
                        this.callTimer.stop();
                        this.callTimer.startWithCallStartTime(earliestTime, false);
                    }

                    debugLog(`Time master reassigned to: ${earliestParticipant}`);
                }
            }

            removeConnection(userId) {
                const peerConnection = this.groupPeerConnections.get(userId);
                if (peerConnection) {
                    try {
                        peerConnection.close();
                    } catch (e) {
                        // Ignore errors during close
                    }
                    this.groupPeerConnections.delete(userId);
                }
            }

            updateParticipantCount() {
                const totalParticipants = this.groupParticipants.size;
                document.getElementById('participantCount').textContent = totalParticipants;
            }

            updateConnectionStatus() {
                const connectedCount = this.activeGroupConnections.size;
                const totalConnections = this.groupPeerConnections.size;
                document.getElementById('connectionCount').textContent = `${connectedCount}/${totalConnections}`;

                if (this.callType === 'group') {
                    let status = `Connected to: ${connectedCount}/${totalConnections} peers | Total participants: ${this.groupParticipants.size}`;

                    // Add synchronized timer info if call is active
                    if (this.callTimer.isRunning && this.callStartTime) {
                        const remaining = this.callTimer.getSynchronizedRemainingTime();
                        const duration = this.callTimer.getSynchronizedElapsedTime();
                        status += ` | Duration: ${this.callTimer.formatTime(duration)}`;

                        if (this.callTimer.isTimeMaster) {
                            status += ' ⏰';
                        }

                        if (remaining <= 60 && remaining > 0) {
                            status += ` | ⏰ ${Math.floor(remaining/60)}:${(remaining%60).toString().padStart(2, '0')} remaining`;
                        }
                    }

                    document.getElementById('connectionStatus').textContent = status;

                    if (connectedCount > 0) {
                        this.updateStatus(`✅ Group call active with ${connectedCount} connection(s)`);
                    } else if (this.isCallActive) {
                        this.updateStatus('Group call joined, waiting for connections...');
                    }
                }
            }

            startGroupConnectionMonitor() {
                if (this.groupConnectionMonitor) clearInterval(this.groupConnectionMonitor);

                this.groupConnectionMonitor = setInterval(() => {
                    if (!this.isCallActive || this.callType !== 'group') {
                        clearInterval(this.groupConnectionMonitor);
                        return;
                    }

                    // Check for missing connections
                    this.checkAndEstablishMissingConnections();

                    // Clean up old participants (60 second timeout)
                    this.cleanupOldParticipants();

                    this.updateConnectionStatus();

                }, 5000);
            }

            checkAndEstablishMissingConnections() {
                const allParticipants = Array.from(this.groupParticipants.keys());

                allParticipants.forEach(participantId => {
                    if (participantId === this.userId) return;

                    const hasConnection = this.groupPeerConnections.has(participantId);
                    const isConnecting = this.pendingGroupOffers.has(participantId);

                    if (!hasConnection && !isConnecting) {
                        this.establishConnectionToParticipant(participantId);
                    } else if (hasConnection) {
                        const peerConnection = this.groupPeerConnections.get(participantId);
                        if (peerConnection &&
                            (peerConnection.connectionState === 'disconnected' ||
                                peerConnection.connectionState === 'failed')) {
                            setTimeout(() => {
                                if (this.isCallActive && this.callType === 'group') {
                                    this.establishConnectionToParticipant(participantId);
                                }
                            }, 1000);
                        }
                    }
                });
            }

            cleanupOldParticipants() {
                const now = Date.now();
                const timeout = 60000; // 60 seconds

                this.groupParticipants.forEach((participant, participantId) => {
                    if (participantId !== this.userId &&
                        (now - participant.lastSeen) > timeout) {
                        debugLog(`Removing inactive participant: ${participantId}`);
                        this.removeRemoteParticipant(participantId);
                    }
                });
            }

            // PRIVATE CALL METHODS
            createPrivatePeerConnection() {
                if (this.privatePeerConnection) {
                    return this.privatePeerConnection;
                }

                debugLog('Creating private peer connection');
                const config = {
                    iceServers: [
                        // STUN servers
                        {
                            urls: 'stun:stun.l.google.com:19302'
                        },
                        {
                            urls: 'stun:stun1.l.google.com:19302'
                        },
                        {
                            urls: 'stun:stun2.l.google.com:19302'
                        },

                        // TURN servers - Add these 3 lines
                        {
                            urls: 'turn:openrelay.metered.ca:80',
                            username: 'openrelayproject',
                            credential: 'openrelayproject'
                        },
                        {
                            urls: 'turn:openrelay.metered.ca:443',
                            username: 'openrelayproject',
                            credential: 'openrelayproject'
                        },
                        {
                            urls: 'turn:openrelay.metered.ca:443?transport=tcp',
                            username: 'openrelayproject',
                            credential: 'openrelayproject'
                        }
                    ],
                    iceTransportPolicy: 'all',
                    bundlePolicy: 'max-bundle',
                    rtcpMuxPolicy: 'require'
                };

                this.privatePeerConnection = new RTCPeerConnection(config);

                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => {
                        this.privatePeerConnection.addTrack(track, this.localStream);
                    });
                }

                this.privatePeerConnection.ontrack = (event) => {
                    debugLog('✅ Received remote stream!');
                    const remoteStream = event.streams[0];
                    this.displayRemoteStreamPrivate(remoteStream);
                    this.updateStatus('✅ Private Call Connected!');
                    this.processPendingIceCandidates();

                    // Start timer when remote stream is received
                    if (!this.callTimer.isRunning) {
                        this.callTimer.start();
                        this.showCallTimer();
                    }
                };

                this.privatePeerConnection.onicecandidate = (event) => {
                    if (event.candidate && this.remoteUserId) {
                        this.iceCandidateBuffer.push(event.candidate);

                        if (!this.iceCandidateTimer) {
                            this.iceCandidateTimer = setTimeout(() => {
                                if (this.iceCandidateBuffer.length > 0) {
                                    debugLog(`Sending batch of ${this.iceCandidateBuffer.length} ICE candidates`);
                                    this.sendSignal('ice-candidate', {
                                        candidates: this.iceCandidateBuffer,
                                        targetUserId: this.remoteUserId
                                    });
                                    this.iceCandidateBuffer = [];
                                }
                                this.iceCandidateTimer = null;
                            }, 200);
                        }
                    }
                };

                this.privatePeerConnection.oniceconnectionstatechange = () => {
                    const state = this.privatePeerConnection.iceConnectionState;
                    if (state === 'connected' || state === 'completed') {
                        this.updateStatus('✅ Private Call Connected!');
                        // Start timer when connection is established
                        if (!this.callTimer.isRunning) {
                            this.callTimer.start();
                            this.showCallTimer();
                        }
                    } else if (state === 'disconnected' || state === 'failed') {
                        this.updateStatus('Connection lost');
                        // Don't stop timer on temporary disconnections
                    }
                };

                return this.privatePeerConnection;
            }

            displayRemoteStreamPrivate(remoteStream) {
                const remotePlayer = document.getElementById('remote-player');
                remotePlayer.innerHTML = '';
                const video = document.createElement('video');
                video.srcObject = remoteStream;
                video.autoplay = true;
                video.playsInline = true;
                video.setAttribute('playsinline', 'true');

                video.onloadedmetadata = () => {
                    video.play().catch(e => debugLog('Remote video play error: ' + e));
                };

                remotePlayer.appendChild(video);
            }

            async createPrivateOffer() {
                try {
                    if (!this.privatePeerConnection) {
                        this.createPrivatePeerConnection();
                    }

                    if (!this.remoteUserId) {
                        return;
                    }

                    debugLog('Creating private offer...');
                    this.updateStatus('Creating offer...');

                    const offer = await this.privatePeerConnection.createOffer();
                    await this.privatePeerConnection.setLocalDescription(offer);

                    await this.sendSignal('offer', {
                        sdp: this.privatePeerConnection.localDescription,
                        targetUserId: this.remoteUserId
                    });

                    debugLog('Private offer sent');
                    this.updateStatus('Offer sent');

                } catch (error) {
                    debugLog('Create private offer error: ' + error.message);
                    this.updateStatus('Offer error: ' + error.message);
                }
            }

            async handlePrivateOffer(offerSdp, fromUserId) {
                try {
                    debugLog('Handling private offer from: ' + fromUserId);

                    if (!this.isCallActive || this.callType !== 'private') {
                        this.pendingOffer = {
                            type: 'offer',
                            data: {
                                sdp: offerSdp
                            },
                            fromUserId: fromUserId
                        };
                        return;
                    }

                    if (this.remoteUserId) {
                        debugLog('Already in a private call, ignoring');
                        return;
                    }

                    this.updateStatus('Received private offer...');
                    this.remoteUserId = fromUserId;

                    if (!this.privatePeerConnection) {
                        this.createPrivatePeerConnection();
                    }

                    const cleanedOffer = cleanSdp(offerSdp);
                    await this.privatePeerConnection.setRemoteDescription(cleanedOffer);

                    const answer = await this.privatePeerConnection.createAnswer();
                    await this.privatePeerConnection.setLocalDescription(answer);

                    await this.sendSignal('answer', {
                        sdp: this.privatePeerConnection.localDescription,
                        targetUserId: fromUserId
                    });

                    debugLog('Private answer sent');
                    this.updateStatus('Answer sent');

                } catch (error) {
                    debugLog('Handle private offer error: ' + error.message);
                    this.updateStatus('Offer handling error: ' + error.message);
                }
            }

            async handlePrivateAnswer(answerSdp) {
                try {
                    debugLog('Handling private answer');
                    const cleanedAnswer = cleanSdp(answerSdp);
                    await this.privatePeerConnection.setRemoteDescription(cleanedAnswer);
                    debugLog('Private call established!');
                    this.updateStatus('✅ Private Call Connected!');

                    this.processPendingIceCandidates();

                    // Start timer when answer is received
                    if (!this.callTimer.isRunning) {
                        this.callTimer.start();
                        this.showCallTimer();
                    }

                } catch (error) {
                    debugLog('Handle private answer error: ' + error.message);
                    this.updateStatus('Answer error: ' + error.message);
                }
            }

            async processPendingIceCandidates() {
                if (this.pendingIceCandidates.length > 0 && this.privatePeerConnection && this.privatePeerConnection.remoteDescription) {
                    for (const candidate of this.pendingIceCandidates) {
                        try {
                            await this.privatePeerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                        } catch (error) {
                            // Ignore duplicate candidates
                        }
                    }
                    this.pendingIceCandidates = [];
                }
            }

            async sendSignal(type, data) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    let cleanedData = {
                        ...data
                    };

                    // Clean SDP if present
                    if (data.sdp) {
                        cleanedData.sdp = cleanSdp(data.sdp);
                    }

                    // Prepare the request body
                    const requestBody = {
                        room: this.room,
                        type: type,
                        data: cleanedData,
                        userId: this.userId
                    };

                    // Add callType if it's a group call
                    if (this.callType === 'group') {
                        requestBody.callType = 'group';
                    }

                    debugLog(`Sending ${type} signal to: ${cleanedData.targetUserId || 'all'}`);

                    const response = await fetch('/webrtc/signal', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestBody)
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`HTTP ${response.status}: ${errorText}`);
                    }

                    const result = await response.json();
                    debugLog(`✅ Signal sent successfully: ${type}`);
                    return result;

                } catch (error) {
                    debugLog(`❌ Send signal error (${type}): ${error.message}`);

                    // Don't show error for leave signals
                    if (type !== 'leave') {
                        this.showError('Signal error: ' + error.message);
                    }
                    return null;
                }
            }
            async sendIceCandidate(targetUserId, candidate) {
                if (!this.isCallActive) return;

                await this.sendSignal('ice-candidate', {
                    candidate: candidate,
                    targetUserId: targetUserId,
                    callType: this.callType
                });
            }

            handleIncomingSignal(signal) {
                debugLog(`Handling ${signal.type} from ${signal.fromUserId}`);

                const callType = signal.data.callType || this.callType;

                switch (signal.type) {
                    case 'join':
                        if (callType === 'group') {
                            this.handleGroupJoin(signal);
                        } else {
                            if (this.isCallActive && this.callType === 'private' && !this.remoteUserId) {
                                this.remoteUserId = signal.fromUserId;
                                if (this.hasSentJoin) {
                                    this.updateStatus('Peer found - creating offer...');
                                    if (!this.privatePeerConnection) {
                                        this.createPrivatePeerConnection();
                                    }
                                    clearTimeout(this.offerTimeout);
                                    this.offerTimeout = setTimeout(() => {
                                        if (this.remoteUserId === signal.fromUserId) {
                                            this.createPrivateOffer();
                                        }
                                    }, 500);
                                } else {
                                    this.updateStatus('Peer found - waiting for offer...');
                                    if (!this.privatePeerConnection) {
                                        this.createPrivatePeerConnection();
                                    }
                                }
                            }
                        }
                        break;

                    case 'call-start-time':
                        this.handleCallStartTime(signal);
                        break;

                    case 'heartbeat':
                        this.handleHeartbeat(signal);
                        break;

                    case 'offer':
                        if (callType === 'group') {
                            this.handleGroupOffer(signal.data.sdp, signal.fromUserId);
                        } else {
                            this.handlePrivateOffer(signal.data.sdp, signal.fromUserId);
                        }
                        break;

                    case 'answer':
                        if (callType === 'group') {
                            this.handleGroupAnswer(signal.data.sdp, signal.fromUserId);
                        } else {
                            this.handlePrivateAnswer(signal.data.sdp);
                        }
                        break;

                    case 'ice-candidate':
                        this.handleIceCandidate(signal.data, signal.fromUserId, callType);
                        break;

                    case 'leave':
                        if (callType === 'group') {
                            this.removeRemoteParticipant(signal.fromUserId);
                        } else {
                            if (signal.fromUserId === this.remoteUserId) {
                                this.updateStatus('Peer left the call');
                                if (this.privatePeerConnection) {
                                    this.privatePeerConnection.close();
                                    this.privatePeerConnection = null;
                                }
                                this.remoteUserId = null;
                                document.getElementById('remote-player').innerHTML = '';
                                this.callTimer.stop();
                            }
                        }
                        break;
                }
            }

            async handleIceCandidate(data, fromUserId, callType) {
                try {
                    if (callType === 'private') {
                        if (this.privatePeerConnection && this.remoteUserId === fromUserId) {
                            const candidates = data.candidates || [data.candidate];

                            for (const candidate of candidates) {
                                if (candidate) {
                                    if (this.privatePeerConnection.remoteDescription) {
                                        await this.privatePeerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                                    } else {
                                        this.pendingIceCandidates.push(candidate);
                                    }
                                }
                            }
                        }
                    } else {
                        const peerConnection = this.groupPeerConnections.get(fromUserId);
                        if (peerConnection) {
                            const candidates = data.candidates || [data.candidate];
                            for (const candidate of candidates) {
                                if (candidate) {
                                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                                }
                            }
                        }
                    }
                } catch (error) {
                    // Ignore duplicate candidate errors
                }
            }

            async leaveCall() {
                debugLog('=== LEAVING CALL ===');

                // Immediately disable UI
                document.getElementById('leaveCall').disabled = true;
                this.updateStatus('Leaving call...');

                // Send leave signal FIRST
                if (this.isCallActive) {
                    try {
                        await this.sendSignal('leave', {
                            userId: this.userId,
                            callType: this.callType,
                            callDuration: this.callTimer.getFormattedDuration()
                        });
                        debugLog('Leave signal sent');
                    } catch (e) {
                        debugLog('Leave signal error: ' + e);
                    }
                }

                // Stop timer and hide it
                this.callTimer.stop();
                this.hideCallTimer();

                // Clear all intervals and timeouts
                if (this.offerTimeout) clearTimeout(this.offerTimeout);
                if (this.iceCandidateTimer) clearTimeout(this.iceCandidateTimer);
                if (this.groupConnectionMonitor) {
                    clearInterval(this.groupConnectionMonitor);
                    this.groupConnectionMonitor = null;
                }
                if (this.heartbeatInterval) {
                    clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = null;
                }

                // Stop local stream FIRST
                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => {
                        try {
                            track.stop();
                        } catch (e) {
                            // Ignore stop errors
                        }
                    });
                    this.localStream = null;
                }

                // Clean up private call
                if (this.privatePeerConnection) {
                    try {
                        this.privatePeerConnection.close();
                    } catch (e) {
                        // Ignore close errors
                    }
                    this.privatePeerConnection = null;
                }

                // Clean up group call connections
                this.groupPeerConnections.forEach((connection, userId) => {
                    try {
                        connection.close();
                    } catch (e) {
                        // Ignore close errors
                    }
                });

                // Clean up remote streams
                this.remoteStreams.forEach((streamInfo, userId) => {
                    if (streamInfo.stream) {
                        streamInfo.stream.getTracks().forEach(track => {
                            try {
                                track.stop();
                            } catch (e) {
                                // Ignore stop errors
                            }
                        });
                    }
                    if (streamInfo.container && streamInfo.container.parentNode) {
                        streamInfo.container.remove();
                    }
                });

                // Reset all state
                this.groupPeerConnections.clear();
                this.groupParticipants.clear();
                this.remoteStreams.clear();
                this.activeGroupConnections.clear();
                this.pendingGroupOffers.clear();
                this.remoteUserId = null;
                this.pendingOffer = null;
                this.pendingIceCandidates = [];
                this.iceCandidateBuffer = [];
                this.hasSentJoin = false;
                this.isCallActive = false;
                this.callStartTime = null;

                // Clear UI immediately
                document.getElementById('local-player').innerHTML = '';
                document.getElementById('remote-player').innerHTML = '';
                document.getElementById('local-player-group').innerHTML = '';
                document.getElementById('remote-videos-group').innerHTML = '';
                document.getElementById('connectionStatus').textContent = '';
                document.getElementById('participantCount').textContent = '1';

                // Enable join buttons
                document.getElementById('joinVideo').disabled = false;
                document.getElementById('joinAudio').disabled = false;

                this.updateStatus('Disconnected');
                debugLog('✅ Call left successfully');
            }

            updateStatus(message) {
                const statusEl = document.getElementById('status');
                if (statusEl) {
                    statusEl.textContent = message;
                }
            }

            showError(message) {
                const errorEl = document.getElementById('error');
                if (errorEl) {
                    errorEl.textContent = message;
                    setTimeout(() => {
                        errorEl.textContent = '';
                    }, 5000);
                }
            }
        }

        let webrtcApp;
        document.addEventListener('DOMContentLoaded', () => {
            webrtcApp = new WebRTCApp();
            window.webrtcApp = webrtcApp;
        });
    </script>
</body>

</html>