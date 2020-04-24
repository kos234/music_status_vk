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
        const token = 'BQCWOkOUxUcqYerwmxrdrlvEiQZ2VImQI3MJDDx7GC9sGhFMhvtsFsv1ht_4OkrRKykvhb1rJpkX67x3lgDT7RmOrSBiqLTq0H5eEkTe-muJVWhHDzo-I-88R7utEt4W4fpCqrsej3vRUkJV3bIKnVcw43G7DGu2fPDN1TGDvyMxK8NQ_JQ';
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
        player.addListener('player_state_changed', state => {
            console.log("test");
            console.log(state); });

        // Ready

        player.addListener('player_state_changed', ({
                                                        position,
                                                        duration,
                                                        track_window: { current_track }
                                                    }) => {
            console.log('Currently Playing', current_track);
            console.log('Position in Song', position);
            console.log('Duration of Song', duration);
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