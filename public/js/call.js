let client = null;
let localAudioTrack = null;
let localVideoTrack = null;
let joined = false;
let uid = null;

async function getToken(channel, uid) {
    const response = await fetch(`/agora/token?channel=${channel}&uid=${uid}`);
    const data = await response.json();
    return data;
}

async function initCall(channel) {
    if (joined) return; // prevent multiple joins

    // Generate UID before requesting token
    uid = Math.floor(Math.random() * 1000000);
    const { appId, token } = await getToken(channel, uid);

    client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    await client.join(appId, channel, token, uid);

    localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
    localVideoTrack = await AgoraRTC.createCameraVideoTrack();

    // play local video
    localVideoTrack.play("local-player");

    // publish local tracks
    await client.publish([localAudioTrack, localVideoTrack]);

    client.on("user-published", async (user, mediaType) => {
        await client.subscribe(user, mediaType);

        if (mediaType === "video") {
            const remoteContainerId = `remote-player-${user.uid}`;
            let remoteContainer = document.getElementById(remoteContainerId);
            if (!remoteContainer) {
                remoteContainer = document.createElement("div");
                remoteContainer.id = remoteContainerId;
                remoteContainer.style.width = "200px";
                remoteContainer.style.height = "150px";
                remoteContainer.style.border = "1px solid #000";
                remoteContainer.style.margin = "5px";
                document.getElementById("remote-playerlist").appendChild(remoteContainer);
            }
            user.videoTrack.play(remoteContainer.id);
        }

        if (mediaType === "audio") {
            user.audioTrack.play();
        }
    });

    client.on("user-unpublished", user => {
        const el = document.getElementById(`remote-player-${user.uid}`);
        if (el) el.remove();
    });
}

async function leaveCall() {
    if (!joined) return;

    if (localAudioTrack) {
        localAudioTrack.close();
        localAudioTrack = null;
    }
    if (localVideoTrack) {
        localVideoTrack.close();
        localVideoTrack = null;
    }
    if (client) {
        await client.leave();
        client = null;
    }

    document.getElementById("local-player").innerHTML = "";
    document.getElementById("remote-playerlist").innerHTML = "";

    joined = false;
    uid = null;
}
