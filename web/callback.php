<?php
$code = $_GET['code'];
var_dump($_POST);
error_log($code);
$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type:application/json',
    'Authorization: Basic '. base64_encode("dde6a297cdc345059eda98c69ba722c0:ce45e9cbc7da47019b6540f9abe00a68")
));
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLSSLOPT_NO_REVOKE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, array("grant_type" => "authorization_code",
"code" => $code,
    "redirect_uri" => "https%3A%2F%2Fmusic-statuc-by-kos.herokuapp.com%2Fcallback"));
$return = curl_exec($ch);
curl_close($ch);
echo $return;
error_log("return");

