import axios from 'axios';

const localPlayer = document.getElementById('local-player');
const remotePlayerList = document.getElementById('remote-playerlist');

let localStream;
let peerConnection;
let room = 'testRoom';
let mode = 'video';

// ------------------------
// Start Call (video/audio)
// ------------------------
async function startCall(callMode) {
    mode = callMode;

    // Get media
    localStream = await navigator.mediaDevices.getUserMedia({
        video: mode === 'video',
        audio: true
    });

    // Display local stream
    localPlayer.innerHTML = '';
    if (mode === 'video') {
        const videoEl = document.createElement('video');
        videoEl.autoplay = true;
        videoEl.muted = true;
        videoEl.srcObject = localStream;
        localPlayer.appendChild(videoEl);
    } else {
        localPlayer.innerHTML = "<div class='audio-label'>Audio Call Connected</div>";
    }

    // Create RTCPeerConnection
    peerConnection = new RTCPeerConnection();

    // Add local tracks
    localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

    // Remote stream
    const remoteStream = new MediaStream();
    peerConnection.ontrack = e => {
        e.streams[0].getTracks().forEach(track => remoteStream.addTrack(track));
        remotePlayerList.innerHTML = '';
        if (mode === 'video') {
            const remoteVideoEl = document.createElement('video');
            remoteVideoEl.autoplay = true;
            remoteVideoEl.srcObject = remoteStream;
            remotePlayerList.appendChild(remoteVideoEl);
        }
    };

    // ICE candidates
    peerConnection.onicecandidate = e => {
        if (e.candidate) sendMessage('candidate', { candidate: e.candidate });
    };

    // Listen for messages from Pusher
    window.Echo.join('webrtc-room.' + room)
        .listen('WebRTCMessage', e => handleMessage(e.message));

    // Create offer
    const offer = await peerConnection.createOffer();
    await peerConnection.setLocalDescription(offer);
    sendMessage('offer', { sdp: offer });
}

// ------------------------
// Handle messages
// ------------------------
async function handleMessage(message) {
    if (!peerConnection) return;

    if (message.type === 'offer') {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(message.sdp));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        sendMessage('answer', { sdp: answer });
    } else if (message.type === 'answer') {
        await peerConnection.setRemoteDescription(new RTCSessionDescription(message.sdp));
    } else if (message.type === 'candidate') {
        await peerConnection.addIceCandidate(message.candidate);
    }
}

// ------------------------
// Send message to server
// ------------------------
function sendMessage(type, data) {
    axios.post('/webrtc/message', { room, type, data });
}

// ------------------------
// Leave Call
// ------------------------
function leaveCall() {
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
    }
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    localPlayer.innerHTML = '';
    remotePlayerList.innerHTML = '';
}

// ------------------------
// Expose globally for blade
// ------------------------
window.startCallFunction = startCall;
window.leaveCallFunction = leaveCall;
