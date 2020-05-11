<?php
ini_set('max_execution_time', 900);
require('../vendor/autoload.php');

define("CLIENT_ID_VK_APP", 7445793); //Айди приложения
define("CLIENT_SECRET_VK_APP", "Wo2hagteHrHp6VxjHMcK"); //Клиентский зашифрованный ключ приложения
define("CONFIRMATION_TOKEN_VK_BOT", "f981356a"); //подтверждение
define("TOKEN_VK_BOT", "a9e54cee09680fb710f00732e55c39766e051a9f1dd90d81fccceb582ec6cb730ea27d8a4301cc9f170cf"); //Ключ доступа сообщества
define("SECRET_KEY_VK_BOT", "koc_234432_cok"); //Secret key
define("VERSION_API_VK", 5.103); //Версия апи
define("AUTHORISATION_BASE_64_SPOTIFY", "ZGRlNmEyOTdjZGMzNDUwNTllZGE5OGM2OWJhNzIyYzA6Y2U0NWU5Y2JjN2RhNDcwMTliNjU0MGY5YWJlMDBhNjg="); //Авторизация спотифай
define("REDIRECT_URI_SPOTIFY", "https://music-statuc-by-kos.herokuapp.com/callback/spotify"); //callback ссылка для спотифая

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->get('/', function () use ($app) {
    return "";
});

$app->get('/vk', function () use ($app) {
    if(isset($_GET['code'])) {
        $url = "https://oauth.vk.com/access_token?";
        $dataToken = json_decode(file_get_contents($url . http_build_query(array("client_id" => CLIENT_ID_VK_APP,
               "client_secret" => CLIENT_SECRET_VK_APP,
                "redirect_uri" => "https://oauth.vk.com/blank.html",
                "code" => $_GET['code']))));
        echo "Ваш токен => " . $dataToken->access_token;
    }
    return "";
});

$app->get('/callback', function () use ($app) {
if(isset($_GET['code'])) {

    $output = shell_exec("curl -H \"Authorization: Basic ". AUTHORISATION_BASE_64_SPOTIFY . "\" -d grant_type=authorization_code -d code=". $_GET['code'] ." -d redirect_uri=". REDIRECT_URI_SPOTIFY . " https://accounts.spotify.com/api/token --ssl-no-revoke");
    $output = json_decode($output);
    echo "Ваш токен => " . $output->access_token . "<br> Ваш код для смены токена => " . $output->refresh_token;
}
    return "";
});

$app->get('/getDBConf', function () use ($app) {
    $urlDB = parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд
    $server = $urlDB["host"];
    $username = $urlDB["user"];
    $password = $urlDB["pass"];
    $db = substr($urlDB["path"], 1);

     return $server. " <- сервер <br>".$username." <- имя пользователя <br>".$password." <- пароль <br>".$db." <- база данных <br>"; //Если нужно узнать данные бд
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
                    $request_params['message'] = "Music status for Vk by kos v1.0.0 \r\n
                      \rКоманды: \f
                      /Info|Инфо - информация о проекте \v 
                      /start|начать {Токен Spotify} {Токен VK} - подключение \e
                      /on|включить - включает статус \n
                      /off|выключить - выключает статус \t 
                      /set operation|включить операцию {off, start, on, finish} - включает определенную операцию статуса \n
                      Операции статуса: \t
                      off - резкое выключить статус (то что вы слушали останется в статусе)\t
                      start - плавное включение (сохранение вашего текущего статуса и включение музыкального) \t
                      on - резкое включение статуса (не сохраняет ваш статус) \t
                      finish - плавное выключение статуса (возвращает ваш прежний статус) \t
                      p.s. Команды: /off|выключить и /on|включить плавно включают и выключают статус \n
                      Информация о проекте: \t
                      Создатель: https://vk.com/i_love_python \t
                      Исходные код проекта и гайд по подключению: \t
                      В разработки: вывод обложки трека в спецаильный альбом, подключение к YouTube и другим сервисам";

                } elseif (($text[0] == '/start') || ($text[0] == '/Start') || ($text[0] == '/начать') || ($text[0] == '/Начать')) {
                    if (isset($text[1])){
                        if (isset($text[2])) {

                            $mysqli->query("INSERT INTO `datasettings` (`tokenSpotify`, `tokenVK`, `user_id`) VALUES ('" . $text[1] . "', '" . $text[2] . "', '" . $request_params["peer_id"] . "')
                        ON DUPLICATE KEY UPDATE `user_id` = '" . $request_params["peer_id"] . "', `tokenSpotify` = '" . $text[1] . "', `tokenVK` = '" . $text[2] . "'");

                            $request_params['message'] = "Настройка завершена, теперь напишите /on|включить чтобы начать использование!";
                        }else $request_params['message'] = "Вы не указали токен VK!";
                    } else $request_params['message'] = "Вы не указали токен Spotify!";

                } elseif (($text[0] == '/on' || $text[0] == '/On') || ($text[0] == '/включить' || $text[0] == '/Включить')) {
                    $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
                    $result = $res->fetch_assoc();
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'start'");

                        $request_params['message'] = "Включенно!";
                    } else $request_params['message'] = "Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен VK} для привязки!";

                } elseif (($text[0] == '/off' || $text[0] == '/Off') || ($text[0] == '/выключить' || $text[0] == '/Выключить')) {
                    $res = $mysqli->query("SELECT * FROM `datasettings` WHERE `user_id` = '" . $data->object->message->from_id . "'");
                    $result = $res->fetch_assoc();
                    if (isset($result['user_id'])) {

                        $mysqli->query("UPDATE `datasettings` SET `operationId`= 'finish'");

                        $request_params['message'] = "Выключенно!";
                    } else $request_params['message'] = "Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен VK} для привязки!";

                } elseif ((($text[0] == '/Set' || $text[0] == '/set') || ($text[0] == '/включить' || $text[0] == '/Включить')) && (($text[1] == 'operation' || $text[1] == 'операцию'))) {
                    if (isset($text[1])) {
                        $error = false;
                        $type = "";
                        switch ($text[1]) {
                            case "off":
                                $type = "off";
                                break;

                            case "start":
                                $type = "start";
                                break;

                            case "on":
                                $type = "on"; // Не говно код, а проверка на то ввел ли пользователь правильные операции
                                break;

                            case "finish":
                                $type = "finish";
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

                                $request_params['message'] = "Включенно!";

                            } else $request_params['message'] = "Не верное название операции!";
                        } else $request_params['message'] = "Вы не привязаны к базе данных! Напишите /start|начать {Токен Spotify} {Токен VK} для привязки!";
                    } else $request_params['message'] = "Вы не указали операцию!";
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

    ?> */