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


                callback('BQBaIUrMr8GutSyQl9ijVVsVkEa5PqSnV6yFYzX2jkrZGKOf7KzlIAZ-E09waGDWSc_AzwEpPZ92P6tXmRl54mz93DlPas_HTUpee13nWgerYHdzjmt57FFqj3WwYaesvnDeLWZTniYMPNJwA5vwIVyPPOv2lkPr000hoexb3Dh6dMkKd3FB0EhOxNrjFr_Az9TsM-zzCtmN4is7G8j7NioIHsSwOPYkTdtZMCNMci7GZH2RYWQ0g2TLsx0a8oH7p73I_n7pRon_A1QOjQYZRoHB5HsVfnUGFnrH49W7g');
            },
            volume: 0.5
        });
        console.log("sssssssssssss");
        player.connect().then(success => {
            if (success) {
                console.log('The Web Playback SDK successfully connected to Spotify!');
            }
        })
    };
</script>
</body>
</html>