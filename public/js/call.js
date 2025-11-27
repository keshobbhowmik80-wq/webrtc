let client = null;
let localAudioTrack = null;
let localVideoTrack = null;
let joined = false;
let uid = null;

// ----------------------
// GET TOKEN
// ----------------------
async function getToken(channel, uid) {
    const response = await fetch(`/agora/token?channel=${channel}&uid=${uid}`);
    return await response.json();
}

// ----------------------
// JOIN CALL (VIDEO / AUDIO)
// ----------------------
async function initCall(channel, mode) {
    if (joined) return;

    joined = true;

    document.getElementById("local-player").innerHTML = "";
    document.getElementById("remote-playerlist").innerHTML = "";

    uid = Math.floor(Math.random() * 1000000);
    const { appId, token } = await getToken(channel, uid);

    client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    await client.join(appId, channel, token, uid);

    if (mode === "audio") {
        localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        await client.publish([localAudioTrack]);
        document.getElementById("local-player").innerHTML =
            "<div class='audio-label'>Audio Call Connected</div>";
    } else {
        localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        localVideoTrack = await AgoraRTC.createCameraVideoTrack();

        localVideoTrack.play("local-player");
        await client.publish([localAudioTrack, localVideoTrack]);
    }

    client.on("user-published", async (user, mediaType) => {
        await client.subscribe(user, mediaType);

        if (mediaType === "video") {
            const id = `remote-player-${user.uid}`;
            if (!document.getElementById(id)) {
                const div = document.createElement("div");
                div.id = id;
                div.className = "remote-video";
                document.getElementById("remote-playerlist").appendChild(div);
            }
            user.videoTrack.play(id);
        }

        if (mediaType === "audio") {
            user.audioTrack.play();
        }
    });

    client.on("user-unpublished", (user) => {
        const el = document.getElementById(`remote-player-${user.uid}`);
        if (el) el.remove();
    });
}

// ----------------------
// LEAVE CALL
// ----------------------
async function leaveCall() {
    if (!joined || !client) return;

    if (localAudioTrack) {
        localAudioTrack.stop();
        localAudioTrack.close();
    }

    if (localVideoTrack) {
        localVideoTrack.stop();
        localVideoTrack.close();
    }

    await client.leave();

    document.getElementById("local-player").innerHTML = "";
    document.getElementById("remote-playerlist").innerHTML = "";

    client = null;
    localAudioTrack = null;
    localVideoTrack = null;
    uid = null;
    joined = false;

    console.log("Left the call.");
}
