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
        const token = 'BQDypAZz1-0GJ7epzGTbmz6Mau9yW8BQmXBD1ShBUvdZ1OtjA623aUJiwhmvE6SZj_uW3OjH32DnZy2Rl_9V1JxXOfPzvHpyYczTkzbD3z3sa09G_-or2kVTKKgWHvQ9fhRzpusVCsZltheM_wD5UjkHAYWycUy1WZM9tkZ_GnSX1D8\n' +
            'bqJC3OF1UV2VHrRiNjuSLovR_t7q3LmBxBxJsXiBVBv4lWMIZ4NANucOja0syOoe-cBi_UQjuOdbAsFdfs0lJ5fzVUjpKxDFHFnni8Ly-3vrzRBRHw-_ZKXjNwJw';
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