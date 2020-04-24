<html>
<head>
    <title>Тест</title>
</head>
<body>
<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script>
        window.onSpotifyWebPlaybackSDKReady = listener => {
            var player = new Spotify.Player({
                name: 'kos',
                getOAuthToken: callback => {


                    callback('BQCKQHsp9eOOXoupRQ8nvMtCysi8DOm02AJUVCZtzNYyoM1qE6IUpejP9ML8v-MafAFmOlFrkne_GS5lJ1Q-E57JQowBLORqQ4BEGjtCVULt3mrdQQe8zAWYW4HSBfECI_1YGzu5lgjdMBpDLHmxG_ESC1wL44ra7ja3kPloUbIU0Gl\n' +
                        '4rWR3AzbHnByDf5qf6K5lreHv6O25Ujd4Qze1LWHrS4AAvxhIvRielq0GoRG5YUptVc8j9BSckIN8JOnV7QaIppyJD40wdTcKPlgiqffB0qkbvunx04_vz8mJd14');
                },
                volume: 0.5
            });
            console.log("sssssssssssss");

            function yourCallback(position,
                                  duration,
                                  track_window) {
                console.log("sda", position)
                console.log("sda", duration)
            }

            player.connect().then(success => {
                if (success) {
                    console.log('The Web Playback SDK successfully connected to Spotify!');

                    player.addListener('ready', ({ device_id }) => {
                        console.log('Connected with Device ID', device_id);
                    });
                }
            });

            player.addListener('player_state_changed', yourCallback(position,
                duration,
                track_window: { current_track });

            player.pause().then(() => {
                console.log('Paused!');
            });
        };

</script>
</body>
</html>