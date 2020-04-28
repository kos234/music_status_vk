<!DOCTYPE html>
<html>
<head>
    <title>Spotify Web Playback SDK Quick Start Tutorial</title>
</head>
<body>
<h1>Spotify Web Playback SDK Quick Start Tutorial</h1>
<h2>Open your console log: <code>View > Developer > JavaScript Console</code></h2>

<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script>
    window.onSpotifyWebPlaybackSDKReady =  () => {
        const token = "BQBS5hI1_E2ol3UHaGUSFYctduiKx7Mjvq9eQN6C25efCE1BLMf8e1vn7zWMfS_Q7yzSQu_X6yIbK6-hTADh7vqFWtsUhkOGMBezI2Kna51pYvAQose-o6K7VXitXrlsx6kr-bzJKCpGW6pnzKZKNR2ZeCuc2aFMjN5G0rvz1NOgiC7x5Zhzu9Qb7vuOEEH67qTJh6iPvHWB5f20rInmTrNxgiZMDR5Ws3E5Uyr04eGqhK76nhrtJi-rV2NgXlaRJ0p2hM1xZYVLxddDwhw1pw-C2XF_WGO312aj0TmXbac";
        const player = new Spotify.Player({
            name: 'Web Playback SDK Quick Start Player',
            volume: 1.0,
            getOAuthToken: cb => {
                cb(token);
            }
        });

        // Error handling
        player.addListener('initialization_error', ({message}) => {
            console.error(message);
        });
        player.addListener('authentication_error', ({message}) => {
            console.error(message);
        });
        player.addListener('account_error', ({message}) => {
            console.error(message);
        });
        player.addListener('playback_error', ({message}) => {
            console.error(message);
        });


        // Ready
        player.addListener('ready', ({device_id}) => {
            console.log('Ready with Device ID', device_id);

            let state = player.getCurrentState();
            if (state == null) {
                console.error("Playback isn't on this device yet");
            } else {
                let {
                    id,
                    uri: track_uri,
                    name: track_name,
                    duration_ms,
                    artists,
                    album: {
                        name: album_name,
                        uri: album_uri,
                        images: album_images
                    }
                } = state.track_window.current_track;
                console.log(`You're listening to ${track_name} by ${artists[0].name}!`);
            }
        });

        // Not Ready
        player.addListener('not_ready', ({device_id}) => {
            console.log('Device ID has gone offline', device_id);
        });

        // Connect to the player!
        player.connect();
    };
</script>
</body>
</html>