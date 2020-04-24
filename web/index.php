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
        const token = "BQAEVxpVHWfol0mzW-a46tKZPfDRn-ViV8i0UP7M71YnXNzOcg1ANQkOCabX-5jTzoyVsdu-Q4BkJHeKYGJICGp-U0QfSj-D1-9QcCXQkv_FCYH9BFxZFVQ7kg9KbXmxdnDyxKFOZMzCSNuhhx6hvJWBWVCmnqRszaSo6gb5iFQTA6cBvK0NCBmgNlgHjbHEqUmX2iUsHXjBfAaDKleHVfFc0XLoMFE3Ul7BETyc2svOA0wGtdd-_J4gzIszPyTkV1v9I3oy-IFBAQeP9e3cSAUPUIXU3BgQBjRQHuEYuaE";
        const player = new Spotify.Player({
            name: 'Web Playback SDK Quick Start Player',
            getOAuthToken: cb => { cb(token); }
        });

        // Error handling
        player.addEventListener('initialization_error', ({ message }) => { console.error(message); });
        player.addEventListener('authentication_error', ({ message }) => { console.error(message); });
        player.addEventListener('account_error', ({ message }) => { console.error(message); });
        player.addEventListener('playback_error', ({ message }) => { console.error(message); });

        // Playback status updates
        player.addEventListener('player_state_changed', state => { console.log(state); });

        // Ready
        player.addEventListener('ready', ({ device_id }) => {
            console.log('Ready with Device ID', device_id);
        });

        // Not Ready
        player.addEventListener('not_ready', ({ device_id }) => {
            console.log('Device ID has gone offline', device_id);
        });

        // Connect to the player!
        player.connect();
    };
</script>
</body>
</html>