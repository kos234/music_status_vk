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
    window.onSpotifyWebPlaybackSDKReady = () => {
        const token = 'BQDyp6WnRe9LnbRCMU1ATGF3Rngzk1dqI_oo_PLav9YcnBVx8Gvl8O5-wKdL0yp77wfnIPdTHk5QCmhokwbFBZ6QojFwvYnpPBeC-fznUfqNvftEngcaYXEXfHetiI1kGpTkIfpnGLdUs93mqViMKr10D2gx9r-YzOz_UtYMyNDRL-I0Tsw';
        const player = new Spotify.Player({
            name: 'Web Playback SDK Quick Start Player',
            getOAuthToken: cb => { cb(token); }
        });

        // Error handling
        player.addListener('initialization_error', ({ message }) => { console.error(message); });
        player.addListener('authentication_error', ({ message }) => { console.error(message); });
        player.addListener('account_error', ({ message }) => { console.error(message); });
        player.addListener('playback_error', ({ message }) => { console.error(message); });

        // Playback status updates

        player.addListener('player_state_changed', ({
                                                        position,
                                                        duration,
                                                        track_window: { current_track }
                                                    }) => {
            console.log('Currently Playing', current_track);
            console.log('Position in Song', position);
            console.log('Duration of Song', duration);
        });

        // Ready
        player.addListener('ready', ({ device_id }) => {
            console.log('Ready with Device ID', device_id);
        });

        // Not Ready
        player.addListener('not_ready', ({ device_id }) => {
            console.log('Device ID has gone offline', device_id);
        });

        // Connect to the player!
        player.connect();
    };
</script>
</body>
</html>