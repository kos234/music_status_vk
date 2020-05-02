<?php
ini_set('max_execution_time', 900);

if (!isset($_REQUEST)) //проверяем получили ли мы запрос
    return;

$urlDB=parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

$server = $urlDB["host"];
$username = $urlDB["user"];
$password = $urlDB["pass"];
$db = substr($urlDB["path"],1);

//echo $server.' <- сервер '.$username.' <- имя пользователя '.$password.' <- пароль '.$db.' <- база данных'; //Если нужно узнать данные бд

$confirmationToken = '13e69364'; //подтверждение

//Ключ доступа сообщества
$token = 'a9e54cee09680fb710f00732e55c39766e051a9f1dd90d81fccceb582ec6cb730ea27d8a4301cc9f170cf';

//Secret key
$secretKey = 'koc_234432_cok';
//Версия апи
$v = 5.103;

$mysqli = new mysqli($server, $username, $password,$db); //Подключаемся

  if ($mysqli->connect_error) {//проверка подключились ли мы
      die('Ошибка подключения (' . $mysqli->connect_errno . ') '. $mysqli->connect_error); //если нет выводим ошибку и выходим из кода
  } else {
      $mysqli->query("SET NAMES 'utf8'");//Устанавливаем кодировку

      $data = json_decode(file_get_contents('php://input'));

      //Проверяем secretKey
      if(strcmp($data->secret, $secretKey) !== 0 && strcmp($data->type, 'confirmation') !== 0)
          return;//Если не наш, выдаем ошибку серверу vk

      //Проверка события запроса
      switch ($data->type) {

          //Подтверждения адреса сервера
          case 'confirmation':
              //Отправляем код
              echo $confirmationToken;
              //Создаем таблицу
              CreateTab($mysqli);
              break;

          //Новое сообщение
          case 'message_new':

              //создаем  массив с сообщением
              $request_params = array(
                  'message' => "" , //сообщение
                  'access_token' => $token, //токен для отправки от имени сообщества
                  'peer_id' => $data->object->message->from_id, //айди пользователя
                  'random_id' => 0, //0 - не рассылка
                  'read_state' => 1,
                  'user_ids' => 0, // Нет конкретного пользователя кому адресованно сообщение
                  'v' => $v, //Версия API Vk
                  'payload' => 1000,
                  'attachment' => '' //Вложение
              );

              //Получаем текст сообщения и разбиваем его на массив слов
              $text = explode(' ', $data->object->message->text);

              //Проверяем массив слов
              if(($text[0] == '/info') || ($text[0] == '/Info') || ($text[0] == '/инфо') || ($text[0] == '/Инфо')){
                     $request_params['message'] = "Music status for Vk by kos v1.0.0 \n
                      Команды: \t 
                      /Info|Инфо - информация о проекте \t 
                      /start|начать {Сервер базы данных} {Имя пользователя базы данных} {Пароль базы данных} {Имя базы данных} {Токен Spotify} - настройка первого запуска \t
                      /off|выключить - выключает статус \t 
                      /on|включить - включает статус \t
                      /set operation|включить операцию {off, start, on, finish} - включает определенную операцию статуса \n
                      Операции статуса: \t
                      off - резкое выключить статус (то что вы слушали останется в статусе)\t
                      start - плавное включение (сохранение вашего текущего статуса и включение музыкального) \t
                      on - резкое включение статуса (не сохраняет ваш статус) \t
                      finish - плавное выключение статуса (возвращает ваш прежний статус) \t
                      p.s. Команды: /off|выключить и /on|включить плавно включают и выключают статус \n
                      Информация о проекте: \t
                      Создатель: https://vk.com/i_love_python \t
                      Исходные код проекта и гайд по подключению: ";

              }
              elseif (($text[0] == '/start') || ($text[0] == '/Start') || ($text[0] == '/начать') || ($text[0] == '/Начать')){
                  if(isset($text[1]) && isset($text[2]) && isset($text[3]) && isset($text[4]) && isset($text[5])){

                      $mysqli->query("INSERT INTO `usersData` (`user_id`,`server`,`user_name`,`password`,`data_base`,`spotifyToken`) 
                        VALUES ('" . $data->object->message->from_id . "' , '". $text[1] ."' , '". $text[2] ."', '". $text[3] ."', '". $text[4] ."', '". $text[5] ."')
	      		 ON DUPLICATE KEY UPDATE `user_id` = '" . $data->object->message->from_id . "', `server` = '". $text[1] ."', `user_name` = '". $text[2] ."' , `password` = '". $text[3] ."', `data_base` = '". $text[4] ."', `spotifyToken` = '". $text[5] ."'");


                      $request_params['message'] = "Настройка завершена, теперь напишите /on|включить чтобы начать использование!";
                  }
                  else $request_params['message'] = "Вы указали не все параметры!";

              }elseif(($text[0] == '/on' || $text[0] == '/On') || ($text[0] == '/включить' || $text[0] == '/Включить')){
                  $res = $mysqli->query("SELECT * FROM `usersData` WHERE `user_id` = '1'");
                  $result = $res->fetch_assoc();
                  if(isset($result['user_id'])){


                      $request_params['message'] = "Включенно!";
                  }else $request_params['message'] = "Вы не привязаны к базе данных! Напишите /start|начать {Сервер базы данных} {Имя пользователя базы данных} {Пароль базы данных} {Имя базы данных} {Токен Spotify} для привязки!";
              }

              echo sendPOST($request_params);

              exit('ok');
              break;
      }



      $mysqli->close();

  }

  function sendPOST($request_params){
      $myCurl = curl_init();
      curl_setopt_array($myCurl, array(
          CURLOPT_URL => 'https://api.vk.com/method/messages.send',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => http_build_query($request_params),
          CURLOPT_LOGIN_OPTIONS => "ok",

      ));
      $response = curl_exec($myCurl);
      curl_close($myCurl);
      return $response;
  }

  function createTab($mysqli){
      $mysqli->query("CREATE TABLE IF NOT EXISTS `usersData` ( 
	`user_id` Int( 255 ) NOT NULL,
	`server` VarChar( 255 ) NOT NULL,
	`user_name` VarChar( 255 ) NOT NULL,
	`password` VarChar( 255 ) NOT NULL,
	`data_base` VarChar( 255 ) NOT NULL,
	`spotifyToken` VarChar( 400 ) NOT NULL,
	CONSTRAINT `unique_user_id` UNIQUE( `user_id` ) )
ENGINE = InnoDB;");//Создаем таблицу в бд
  }

    ?>