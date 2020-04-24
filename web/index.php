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

        models.player.addEventListener('change', updateCurrentTrack);

        function updateCurrentTrack(){
            models.player.load('track').done(function (player) {
                if(player.track.uri != currentTrack.uri){
                    currentTrack = player.track;
                    console.log("YES FUCK")
                }
            }
        }

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