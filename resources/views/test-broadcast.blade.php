<!DOCTYPE html>
<html>
<head>
    <title>Broadcast Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Broadcast Test</h1>
    <div id="status">Ready to test broadcast</div>
    <button onclick="testBroadcast()">Test Broadcast Connection</button>

    <script src="https://js.pusher.com/8.3/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        async function testBroadcast() {
            const status = document.getElementById('status');
            status.textContent = 'Testing broadcast connection...';

            try {
                // Test if we can reach the auth endpoint
                const authTest = await axios.post('/broadcasting/auth', {
                    channel_name: 'presence-webrtc-room.test'
                });
                console.log('Auth test passed:', authTest.data);

                // Initialize Pusher
                const pusher = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
                    cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
                    forceTLS: true,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }
                });

                // Subscribe to channel
                const channel = pusher.subscribe('presence-webrtc-room.test');

                channel.bind('pusher:subscription_succeeded', (members) => {
                    status.textContent = '✅ SUCCESS! Connected to broadcast channel. Members: ' + members.count;
                    console.log('Subscription succeeded:', members);
                });

                channel.bind('pusher:subscription_error', (error) => {
                    status.textContent = '❌ Subscription failed: ' + JSON.stringify(error);
                    console.error('Subscription error:', error);
                });

            } catch (error) {
                status.textContent = '❌ Error: ' + error.message;
                console.error('Test failed:', error);
                if (error.response) {
                    console.error('Response data:', error.response.data);
                    console.error('Response status:', error.response.status);
                }
            }
        }
    </script>
</body>
</html>
