<?php

if(isset($_GET['code'])){
    $token = json_decode(file_get_contents("https://oauth.vk.com/access_token?client_id=7445793&client_secret=Wo2hagteHrHp6VxjHMcK&redirect_uri=https://music-statuc-by-kos.herokuapp.com/callback&code=" . $_GET['code']));
    print_r($token);
}

?>