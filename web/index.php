<html>
<head>
    <title>Тест</title>
</head>
<body>
<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script>
    window.onSpotifyWebPlaybackSDKReady = () => {
        var player = new Spotify.Player({
            name: 'kos',
            getOAuthToken: callback => {


                callback('BQBIFsbMgOgwNdEu1nO-e-7q8iQ6msM-RfaREdvweJO0Y_iS_ousfj3fHIPya7yADnQFmEpwU8Fujet2_rzc4DfBnOPX5Mb6acZLc4IpqzhNeGGV7Zlc2aqLh12AaDPcMfqqv6DwVJN5i5bG0KEQXk18J9VrVegTdsJjXS2Gjebm3WP0ZSP7GnUretvYclnaLRlOQj9NB_EQgD4ZnQGg__IaB5Ma79mmQ39SYgvEZSWOhFCFdYAMCXFSLwLXeJTWRHXj4CNPRvXHQKFEjmQVA2vKbcqLBeFHp6aIvkcN');
            },
            volume: 0.5
        });

        player.connect().then(success => {
            if (success) {
                console.log('The Web Playback SDK successfully connected to Spotify!');
            }
        })
    };
</script>
</body>
</html>