<?php
ini_set('max_execution_time', 900);
require('../vendor/autoload.php');

define("CLIENT_ID_VK_APP", getenv("CLIENT_ID_VK_APP")); //Айди приложения
define("CLIENT_SECRET_VK_APP", getenv("CLIENT_SECRET_VK_APP")); //Клиентский зашифрованный ключ приложения
define("CONFIRMATION_TOKEN_VK_BOT", getenv("CONFIRMATION_TOKEN_VK_BOT")); //подтверждение
define("TOKEN_VK_BOT", getenv("TOKEN_VK_BOT")); //Ключ доступа сообщества
define("SECRET_KEY_VK_BOT", getenv("SECRET_KEY_VK_BOT")); //Secret key
define("VERSION_API_VK", 5.103); //Версия апи
define("AUTHORISATION_BASE_64_SPOTIFY", getenv("AUTHORISATION_BASE_64_SPOTIFY")); //Авторизация спотифай
define("REDIRECT_URI_SPOTIFY", getenv("REDIRECT_URI_SPOTIFY")); //callback ссылка для спотифая

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));


$app->get('/spotify', function () use ($app) {
if(isset($_GET['code'])) {

    $output = shell_exec("curl -H \"Authorization: Basic ". AUTHORISATION_BASE_64_SPOTIFY . "\" -d grant_type=authorization_code -d code=". $_GET['code'] ." -d redirect_uri=". REDIRECT_URI_SPOTIFY . " https://accounts.spotify.com/api/token --ssl-no-revoke");
    $output = json_decode($output);
    echo "Your token/Ваш токен => " . $output->access_token . "<br> Your refresh token/Ваш токен для смены => " . $output->refresh_token;
}
    return "";
});

$app->get('/getDBConf', function () use ($app) {//Если нужно узнать данные бд
    if(isset($_GET['password'])) {
        if($_GET['password'] == getenv("PASSWORD_GET_DB")){
        $urlDB = parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд
        $server = $urlDB["host"];
        $username = $urlDB["user"];
        $password = $urlDB["pass"];
        $db = substr($urlDB["path"], 1);
        echo $server. " <- сервер <br>".$username." <- имя пользователя <br>".$password." <- пароль <br>".$db." <- база данных <br>";
    }}
     return '';
});

$app->post('/bot', function () use ($app) {

    if (!isset($_REQUEST)) //проверяем получили ли мы запрос
        return;

    $urlDB = parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

    $server = $urlDB["host"];
    $username = $urlDB["user"];
    $password = $urlDB["pass"];
    $db = substr($urlDB["path"], 1);

    $mysqli = new mysqli($server, $username, $password, $db); //Подключаемся

    if ($mysqli->connect_error) {//проверка подключились ли мы
        die('Ошибка подключения (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error); //если нет выводим ошибку и выходим из кода
    } else {
        $mysqli->query("SET NAMES 'utf8'");//Устанавливаем кодировку

        $data = json_decode(file_get_contents('php://input'));

        //Проверяем secretKey
        if (strcmp($data->secret, SECRET_KEY_VK_BOT) !== 0 && strcmp($data->type, 'confirmation') !== 0)
            return;//Если не наш, выдаем ошибку серверу vk

        //Проверка события запроса
        switch ($data->type) {

            //Подтверждения адреса сервера
            case 'confirmation':
                //Отправляем код
                echo CONFIRMATION_TOKEN_VK_BOT;
                break;

            //Новое сообщение
            case 'message_new':

                //создаем  массив с сообщением
                $request_params = array(
                    'message' => "", //сообщение
                    'access_token' => TOKEN_VK_BOT, //токен для отправки от имени сообщества
                    'peer_id' => $data->object->message->from_id, //айди пользователя
                    'random_id' => 0, //0 - не рассылка
                    'read_state' => 1,
                    'user_ids' => 0, // Нет конкретного пользователя кому адресованно сообщение
                    'v' => VERSION_API_VK, //Версия API Vk
                    'payload' => 1000,
                    'attachment' => '' //Вложение
                );

                //Получаем текст сообщения и разбиваем его на массив слов
                $text = explode(' ', $data->object->message->text);

                //Проверяем массив слов
                if (mb_strcasecmp($text[0], "/info") == 0 || mb_strcasecmp($text[0], "/") == 0 || mb_strcasecmp($text[0], "/инфо") == 0 || mb_strcasecmp($text[0], "/инфа") == 0) {
                    $request_params['message'] = "&#129302;Music status for Vk by kos v1.0.0\n\n"
                        . "&#9999;Команды:\n"
                        . "&#128196;/Info|Инфо — информация о проекте\n"
                        . "&#128190;/start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} — подключение\n"
                        . "&#127773;/on|включить — включает статус\n"
                        . "&#127770;/off|выключить — выключает статус\n"
                        . "&#127770;/установить любимую музыку [количество, по умолчанию 10] - записывает в поле \"Любимая музыка\" несколько любимых треков, в зависимости от того сколько вы указали\n"
                        . "&#127763;/set operation|включить операцию {off, start, on, finish} — включает определенную операцию статуса\n"
                        . "&#9997;/guide|гайд - гайд по подключению\n"
                        . "&#128195;/FAQ - часто задаваемые вопросы\n"
                        . "&#128241;/online|онлайн — показывает, когда был последний ответ от статус-сервера\n\n"
                        . "&#9881;Настройки:\n"
                        . "&#128247;/photo status|фото статус {on|off|включить|выключить} — Создает специальный альбом, в который будут добавляться обложки треков, которые вы сейчас слушаете\n"
                        . "&#128221;/text|текст {on|off|включить|выключить} — Добавляет слово \"Слушает: \" перед статусом, по умолчанию включено\n"
                        . "&#9208;/pause|пауза {on|off|включить|выключить} — Добавляет фразу \"На паузе: \" если вы поставили трек на паузу, по умолчанию отключено\n"
                        . "&#9209;/stop|стоп {on|off|включить|выключить} — Добавляет слово \"Слушал: \" если вы прекратили слушать музыку, по умолчанию отключено\n"
                        . "&#128172;/limit|лимит {on|off|включить|выключить} — Если получившийся статус больше 140 символом, информация о альбоме не указывается, по умолчанию отключено\n\n"
                        . "&#10024;Операции статуса:\n"
                        . "&#127761;off — резкое выключить статус (то что вы слушали останется в статусе)\n"
                        . "&#127762;start — плавное включение (сохранение вашего текущего статуса и включение музыкального)\n"
                        . "&#127765;on — резкое включение статуса (не сохраняет ваш статус)\n"
                        . "&#127766;finish - плавное выключение статуса (возвращает ваш прежний статус)\n"
                        . "&#128204;p.s. Команды: /off|выключить и /on|включить плавно включают и выключают статус\n\n"
                        . "&#128214;Информация о проекте:\n"
                        . "&#128100;Создатель: https://vk.com/i_love_python\n"
                        . "&#128064;Исходные код проекта и гайд по подключению: https://github.com/kos234/music_status_vk\n"
                        . "&#129316;В разработке: вывод обложки трека в специальный альбом, подключение к YouTube и другим сервисам";

                } elseif (mb_strcasecmp($text[0], '/start') == 0 || mb_strcasecmp($text[0], '/начать') == 0) {
                    if (isset($text[1])){
                        if (isset($text[2])) {
                        if (isset($text[3])) {
                            if(!(strpos($text[2], "{") != false || strpos($text[3], "{") != false || strpos($text[2], "}") != false || strpos($text[3], "}") != false)){

                            $explodeUrl = explode("code=",  $text[3]);
                            if(isset($explodeUrl[1])){
                                error_log($explodeUrl[0] . " or ". $explodeUrl[1]);
                            $dataToken = json_decode(file_get_contents("https://oauth.vk.com/access_token?" . http_build_query(array("client_id" => CLIENT_ID_VK_APP,
                                    "client_secret" => CLIENT_SECRET_VK_APP,
                                    "redirect_uri" => "https://oauth.vk.com/blank.html",
                                    "code" => $explodeUrl[1]))));
                            if(isset($dataToken->access_token)) {
                                $mysqli->query("INSERT INTO `datasettings` (`tokenSpotify`, `tokenVK`, `user_id`, `refreshTokenSpotify`) VALUES ('" . $text[1] . "', '" . $dataToken->access_token . "', '" . $request_params["peer_id"] . "', '" . $text[2] . "')
                        ON DUPLICATE KEY UPDATE `user_id` = '" . $request_params["peer_id"] . "', `tokenSpotify` = '" . $text[1] . "', `tokenVK` = '" . $dataToken->access_token . "', `refreshTokenSpotify` = '" . $text[2] . "'");

                                $request_params['message'] = "&#9989;Настройка завершена, теперь напишите /on|включить чтобы начать использование!";
                            }else $request_params['message'] = "&#10060;Что-то не так с ссылкой, попробуйте ещё раз! Нерабочий код!";
                            }else $request_params['message'] = "&#10060;Что-то не так с ссылкой, попробуйте ещё раз! Нет кода!";
                            }else $request_params['message'] = "&#10060;Указывать значения необходимо без фигурных скобок!";
                        }else $request_params['message'] = "&#10060;Вы не указали ссылку с кодом VK!";
                        }else $request_params['message'] = "&#10060;Вы не указали токен смены Spotify!";
                    } else $request_params['message'] = "&#10060;Вы не указали токен Spotify!";

                } elseif (mb_strcasecmp($text[0], '/on') == 0 || mb_strcasecmp($text[0], '/включить')  == 0) {
                    $result = onCheak($mysqli, $data);
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'start' WHERE `user_id` = '". $result['user_id'] ."'");

                        $request_params['message'] = "&#127773;Включено!";
                    } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";

                }elseif (mb_strcasecmp($text[0] . " " . $text[1] . " " . $text[2], '/установить любимую музыку') == 0) {
                    $result = onCheak($mysqli, $data);
                    if (isset($result['user_id'])) {
                        $res = $mysqli->query("SELECT `tokenSpotify` FROM `datasettings` WHERE `user_id` = '" . $result['user_id'] . "' ");
                        $res_active = $res->fetch_assoc();
                        $num = 10;
                        if (isset($text[3]))
                            $num = $text[3];
                        $tracks = getTracks($num, $res_active["tokenSpotify"], $mysqli, $result);
                        $i = 0;
                        $string = "";
                        while (isset($tracks->items[$i])){
                            $artists = 0;
                            if($string != "")
                                $string .= ", ";
                            while (isset($tracks->items->artists[$artists])){
                                if($artists != 0)
                                    $string .= " & ";
                                $string .= $tracks->items->artists[$artists]->name;
                            }
                            $string .= " - " . $tracks->items[$i]->name;
                        }

                        $request_params['message'] = $string;
                    }

                } elseif (mb_strcasecmp($text[0], '/off') == 0 || mb_strcasecmp($text[0], '/выключить') == 0) {
                    $result = onCheak($mysqli, $data);
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'finish' WHERE `user_id` = '". $result['user_id'] ."'");

                        $request_params['message'] = "&#127770;Выключено!";
                    } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";

                }elseif(mb_strcasecmp($text[0], '/faq') == 0){

                    $request_params['message'] = "https://vk.com/@music_status_for_vk-faq-chasto-zadavaemye-voprosy";

                }elseif(mb_strcasecmp($text[0], '/guide') == 0 || mb_strcasecmp($text[0], '/гайд') == 0){

                    $request_params['message'] = "https://vk.com/@music_status_for_vk-gaid-po-podklucheniu";

                }elseif(mb_strcasecmp($text[0], '/online') == 0 || mb_strcasecmp($text[0], '/онлайн') == 0){
                    $res = $mysqli->query("SELECT `active_time` FROM `active_state`");
                    $res_active = $res->fetch_assoc();
                    $sec = time() - $res_active['active_time'];
                    $type = "";
                    if($sec <= 30){
                        $type = " Всё хорошо&#9989;";
                    }elseif (30 < $sec && $sec <= 60){
                        $type = " Подозрительно&#128529;";
                    }else{
                        $type = " Сервер был перезагружен&#9851;";
                    }

                    $sec_padej = "";
                    if(($sec >= 11 && $sec <= 19) || (endNumber($sec) >= 5 && endNumber($sec) <= 9) || endNumber($sec) == 0)
                        $sec_padej = " секунд ";
                    elseif (endNumber($sec) == 1)
                        $sec_padej = " секунду ";
                    elseif (endNumber($sec) >= 2 && endNumber($sec) <= 4)
                        $sec_padej = " секунды ";
                    else $sec_padej = " ворнинг!" . endNumber($sec) . " ";

                    $request_params['message'] = "Последний ответ был " . $sec . $sec_padej . "назад." . $type;

                }elseif (mb_strcasecmp($text[0], '/лимит') == 0 || mb_strcasecmp($text[0], '/limit') == 0){
                    $result = onCheak($mysqli, $data);
                    if(isset($result['user_id'])) {
                        if (isset($text[1])) {
                            if (mb_strcasecmp($text[1], "включить") == 0 || mb_strcasecmp($text[1], "on") == 0) {
                                $mysqli->query("UPDATE `datasettings` SET `isLength`= 1 WHERE `user_id` = '". $result['user_id'] ."'");
                                $request_params['message'] = "&#127773;Включено!";
                            } elseif (mb_strcasecmp($text[1], "выключить") == 0 || mb_strcasecmp($text[1], "off") == 0) {
                                $mysqli->query("UPDATE `datasettings` SET `isLength`= 0 WHERE `user_id` = '". $result['user_id'] ."'");
                                $request_params['message'] = "&#127770;Выключено!";
                            } else $request_params['message'] = "Неверное действие. Действия: включить, выключить, on, off";
                        } else $request_params['message'] = "Что с ним сделать? Включить или выключить";

                    }else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
                }elseif (mb_strcasecmp($text[0], '/текст') == 0 || mb_strcasecmp($text[0], '/text') == 0){
                    $result = onCheak($mysqli, $data);
                    if(isset($result['user_id'])) {
                    if(isset($text[1])){
                        if(mb_strcasecmp($text[1], "включить") == 0 || mb_strcasecmp($text[1], "on") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icText`= 1 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127773;Включено!";
                        }elseif (mb_strcasecmp($text[1], "выключить") == 0 || mb_strcasecmp($text[1], "off") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icText`= 0 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127770;Выключено!";
                        }else $request_params['message'] = "Неверное действие. Действия: включить, выключить, on, off";
                    }else $request_params['message'] = "Что с ним сделать? Включить или выключить";
                    }else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
                }elseif (mb_strcasecmp($text[0], '/пауза') == 0 || mb_strcasecmp($text[0], '/pause') == 0){
                    $result = onCheak($mysqli, $data);
                    if(isset($result['user_id'])) {
                    if(isset($text[1])){
                        if(mb_strcasecmp($text[1], "включить") == 0 || mb_strcasecmp($text[1], "on") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icPause`= 1 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127773;Включено!";
                        }elseif (mb_strcasecmp($text[1], "выключить") == 0 || mb_strcasecmp($text[1], "off") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icPause`= 0 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127770;Выключено!";
                        }else $request_params['message'] = "Неверное действие. Действия: включить, выключить, on, off";
                    }else $request_params['message'] = "Что с ним сделать? Включить или выключить";
                    }else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
                }elseif (mb_strcasecmp($text[0], '/stop') == 0 || mb_strcasecmp($text[0], '/стоп') == 0){
                    $result = onCheak($mysqli, $data);
                    if(isset($result['user_id'])) {
                    if(isset($text[1])){
                        if(mb_strcasecmp($text[1], "включить") == 0 || mb_strcasecmp($text[1], "on") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icStop`= 1 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127773;Включено!";
                        }elseif (mb_strcasecmp($text[1], "выключить") == 0 || mb_strcasecmp($text[1], "off") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icStop`= 0 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127770;Выключено!";
                        }else $request_params['message'] = "Неверное действие. Действия: включить, выключить, on, off";
                    }else $request_params['message'] = "Что с ним сделать? Включить или выключить";
                    }else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
                }elseif (mb_strcasecmp($text[0] . " " . $text[1], '/photo status') == 0 || mb_strcasecmp($text[0] . " " . $text[1], '/фото статус') == 0){
                    $result = onCheak($mysqli, $data);
                    if(isset($result['user_id'])) {
                        if(isset($text[2])){
                        if(mb_strcasecmp($text[2], "включить") == 0 || mb_strcasecmp($text[2], "on") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icPhotoMusic`= 1 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127773;Включено!";
                        }elseif (mb_strcasecmp($text[2], "выключить") == 0 || mb_strcasecmp($text[2], "off") == 0){
                            $mysqli->query("UPDATE `datasettings` SET `icPhotoMusic`= 0 WHERE `user_id` = '". $result['user_id'] ."'");
                            $request_params['message'] = "&#127770;Выключено!";
                        }else $request_params['message'] = "Неверное действие. Действия: включить, выключить, on, off";
                    }else $request_params['message'] = "Что с ним сделать? Включить или выключить";
                }else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
        }elseif ((mb_strcasecmp($text[0], '/set') == 0 || mb_strcasecmp($text[0], '/включить') == 0) && (mb_strcasecmp($text[1], 'operation') == 0 || mb_strcasecmp($text[1], 'операцию') == 0)) {
                    if (isset($text[1])) {
                        $error = false;
                        $type = "";
                        $small = "";
                        switch (strtolower($text[1])) {
                            case "off":
                                $type = "off";
                                $small = "&#127761;";
                                break;

                            case "start":
                                $type = "start";
                                $small = "&#127762;";
                                break;

                            case "on":
                                $type = "on"; // Не говно код, а проверка на то ввел ли пользователь правильные операции
                                $small = "&#127765;";
                                break;

                            case "finish":
                                $type = "finish";
                                $small = "&#127766;";
                                break;

                            default:
                                $error = true;
                                break;
                        }

                        if (!$error) {
                            $result = onCheak($mysqli, $data);
                            if (isset($result['user_id'])) {

                                $mysqli->query("UPDATE `datasettings` SET `operationId`= '" . $type . "' WHERE `user_id` = '". $result['user_id'] ."'");

                                $request_params['message'] = $small . "Включено!";

                            } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
                        } else $request_params['message'] = "&#10060;Не верное название операции!";
                    } else $request_params['message'] = "&#10060;Вы не указали операцию!";
                }

                sendPOST($request_params);
                echo "ok";

                break;
        }


        $mysqli->close();
    }

    return "";
});


$app->run();

function mb_strcasecmp($str1, $str2, $encoding = null) { //https://www.php.net/manual/en/function.mb_strcasecmp.php#107016 взято от сюда
    if (null === $encoding) { $encoding = mb_internal_encoding(); }
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
}

    function onCheak($mysqli, $data){
        $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
       return $res->fetch_assoc();
    }

    function endNumber($number){
        return round(($number/10 - intdiv($number, 10)) * 10);
    }

    function sendPOST($request_params)
    {
        $myCurl = curl_init();
        curl_setopt_array($myCurl, array(
            CURLOPT_URL => 'https://api.vk.com/method/messages.send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($request_params),
        ));
        curl_exec($myCurl);
        curl_close($myCurl);
    }

    function getTracks($limit, $tokenSpotify, $mysqli, $result)
    {
        var_dump("https://api.spotify.com/v1/me/top/tracks?time_range=long_term&limit=".$limit."&offset=0&access_token=".$tokenSpotify);
        $resultString = json_decode(file_get_contents("https://api.spotify.com/v1/me/top/tracks?time_range=long_term&limit=".$limit."&offset=0&access_token=".$tokenSpotify));
        if(isset($result->error->status))
            if($result->error->status == 401) {
                $res = $mysqli->query("SELECT `refreshTokenSpotify` FROM `datasettings` WHERE `user_id` = '" . $result['user_id'] . "' ");
                $res_active = $res->fetch_assoc();
                $output = shell_exec("curl -H \"Authorization: Basic " + AUTHORISATION_BASE_64_SPOTIFY + "\" -d grant_type=refresh_token -d refresh_token=" + $res_active["refreshTokenSpotify"] + " -d redirect_uri=https://music-statuc-by-kos.herokuapp.com/spotify https://accounts.spotify.com/api/token --ssl-no-revoke");
                $output = json_decode($output);
                $resultString = json_decode(file_get_contents("https://api.spotify.com/v1/me/top/tracks?time_range=long_term&limit=".$limit."&offset=0&access_token=".$output["access_token"]));
                $mysqli->query("UPDATE `datasettings` SET `tokenSpotify`= '" . $output["access_token"] . "' WHERE `user_id` = '". $result['user_id'] ."'");

            }
        return $resultString;
    }

    ?>