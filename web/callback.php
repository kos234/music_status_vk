<?php
$code = $_GET['code'];
var_dump($_POST);
error_log(code);
if( $curl = curl_init() ) {
    curl_setopt($curl, CURLOPT_USERPWD, "dde6a297cdc345059eda98c69ba722c0:ce45e9cbc7da47019b6540f9abe00a68");
    curl_setopt($curl, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLSSLOPT_NO_REVOKE, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=" . code . +"&redirect_uri=https://music-statuc-by-kos.herokuapp.com/callback");
    $out = curl_exec($curl);
    echo $out;
    curl_close($curl);
}
?>
