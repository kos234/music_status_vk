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
        const token = 'BQCKQHsp9eOOXoupRQ8nvMtCysi8DOm02AJUVCZtzNYyoM1qE6IUpejP9ML8v-MafAFmOlFrkne_GS5lJ1Q-E57JQowBLORqQ4BEGjtCVULt3mrdQQe8zAWYW4HSBfECI_1YGzu5lgjdMBpDLHmxG_ESC1wL44ra7ja3kPloUbIU0Gl\n' +
            '4rWR3AzbHnByDf5qf6K5lreHv6O25Ujd4Qze1LWHrS4AAvxhIvRielq0GoRG5YUptVc8j9BSckIN8JOnV7QaIppyJD40wdTcKPlgiqffB0qkbvunx04_vz8mJd14';
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