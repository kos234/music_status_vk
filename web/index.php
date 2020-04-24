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
        const token = "BQAirMhTosxQJ78wmBI_a6Nwdbdmzty6y3uD6i-KDiJL31bmOQD4Ox9xRR7q0iCoMC1HBu8PO4KKnT-weI6TMpSDLHOPaTVgvMT7PvIxClepuCnHpZ9DK7uQcpuoRP7Spk2_iAeg60uzz-HQy8Hvn46JlS80uWFi25Xg-B0tuMyoogY\n" +
            "9F1jaouHHEim-LX5z6panyHWFXUvlNTANSrVQmsCmVBsb0USf-FKvIUwA54GqY7ewAN29Y9VDu0Cu2e-Ayf666gPkXHbR_iK_ri_q0Rp8PDbkmjxDtAthV1wjlBY";
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
        player.addListener('player_state_changed', state => { console.log(state); });

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