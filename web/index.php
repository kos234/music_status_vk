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

$app->get('/', function () use ($app) {
    return "";
});

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
    }else echo "Неверный пароль! Ану брысь!";}
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
                if (($text[0] == '/info') || ($text[0] == '/Info') || ($text[0] == '/инфо') || ($text[0] == '/Инфо') || ($text[0] == '/') || ($text[0] == '/') || ($text[0] == '/инфа') || ($text[0] == '/Инфа')) {
                    $request_params['message'] = "&#129302;Music status for Vk by kos v1.0.0\n\n"
                        . "&#9999;Команды:\n"
                        . "&#128196;/Info|Инфо - информация о проекте\n"
                        . "&#9881;/start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} - подключение\n"
                        . "&#127773;/on|включить - включает статус\n"
                        . "&#127770;/off|выключить - выключает статус\n"
                        . "&#127763;/set operation|включить операцию {off, start, on, finish} - включает определенную операцию статуса\n\n"
                        . "&#10024;Операции статуса:\n"
                        . "&#127761;off - резкое выключить статус (то что вы слушали останется в статусе)\n"
                        . "&#127762;start - плавное включение (сохранение вашего текущего статуса и включение музыкального)\n"
                        . "&#127765;on - резкое включение статуса (не сохраняет ваш статус)\n"
                        . "&#127766;finish - плавное выключение статуса (возвращает ваш прежний статус)\n"
                        . "&#128204;p.s. Команды: /off|выключить и /on|включить плавно включают и выключают статус\n\n"
                        . "&#128214;Информация о проекте:\n"
                        . "&#128100;Создатель: https://vk.com/i_love_python\n"
                        . "&#128064;Исходные код проекта и гайд по подключению: https://github.com/kos234/music_status_vk\n"
                        . "&#129316;В разработке: вывод обложки трека в специальный альбом, подключение к YouTube и другим сервисам";

                } elseif (($text[0] == '/start') || ($text[0] == '/Start') || ($text[0] == '/начать') || ($text[0] == '/Начать')) {
                    if (isset($text[1])){
                        if (isset($text[2])) {
                        if (isset($text[3])) {

                            $explodeUrl = $text[3].explode("code=");
                            if(isset($explodeUrl[1])){

                            $dataToken = json_decode(file_get_contents("https://oauth.vk.com/access_token?" . http_build_query(array("client_id" => CLIENT_ID_VK_APP,
                                    "client_secret" => CLIENT_SECRET_VK_APP,
                                    "redirect_uri" => "https://oauth.vk.com/blank.html",
                                    "code" => $_GET['code']))));
                            if(isset($dataToken->access_token)) {
                                $mysqli->query("INSERT INTO `datasettings` (`tokenSpotify`, `tokenVK`, `user_id`, `refreshTokenSpotify`) VALUES ('" . $text[1] . "', '" . $text[3] . "', '" . $request_params["peer_id"] . "', '" . $text[2] . "')
                        ON DUPLICATE KEY UPDATE `user_id` = '" . $request_params["peer_id"] . "', `tokenSpotify` = '" . $text[1] . "', `tokenVK` = '" . $text[2] . "', `refreshTokenSpotify` = '" . $text[2] . "'");

                                $request_params['message'] = "&#9989;Настройка завершена, теперь напишите /on|включить чтобы начать использование!";
                            }else $request_params['message'] = "&#10060;Что-то не так с ссылкой, попробуйте ещё раз!";
                            }else $request_params['message'] = "&#10060;Что-то не так с ссылкой, попробуйте ещё раз!";
                        }else $request_params['message'] = "&#10060;Вы не указали ссылку с кодом VK!";
                        }else $request_params['message'] = "&#10060;Вы не указали токен смены Spotify!";
                    } else $request_params['message'] = "&#10060;Вы не указали токен Spotify!";

                } elseif (($text[0] == '/on' || $text[0] == '/On') || ($text[0] == '/включить' || $text[0] == '/Включить')) {
                    $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
                    $result = $res->fetch_assoc();
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'start'");

                        $request_params['message'] = "&#127773;Включено!";
                    } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";

                } elseif (($text[0] == '/off' || $text[0] == '/Off') || ($text[0] == '/выключить' || $text[0] == '/Выключить')) {
                    $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
                    $result = $res->fetch_assoc();
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'finish'");

                        $request_params['message'] = "&#127770;Выключено!";
                    } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";

                } elseif ((($text[0] == '/Set' || $text[0] == '/set') || ($text[0] == '/включить' || $text[0] == '/Включить')) && (($text[1] == 'operation' || $text[1] == 'операцию'))) {
                    if (isset($text[1])) {
                        $error = false;
                        $type = "";
                        $small = "";
                        switch ($text[1]) {
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

                            $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
                            $result = $res->fetch_assoc();
                            if (isset($result['user_id'])) {

                                $mysqli->query("UPDATE `datasettings` SET `operationId`= '" . $type . "'");

                                $request_params['message'] = $small . "Включено!";

                            } else $request_params['message'] = "&#10060;Не верное название операции!";
                        } else $request_params['message'] = "&#10060;Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен замены Spotify} {Ссылка с кодом VK} для привязки!";
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

    ?>