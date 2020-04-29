<?php
ini_set('max_execution_time', 900); // Чтобы наш скрипт выполнялся

use GuzzleHttp\Client;

$client = new GuzzleHttp\Client();
$res = $client->post('https://api.spotify.com/v1/me', [
    'headers' => [
        'Authorization' =>  ['Bearer ' . "BQAKmny4Ul7HS8I4TOueVYuHgSU8OUzCw9B_h2ZPs8STWaLLnvsYzjChPp8T0gfyD5bgeKK3Gzq1zhQYmzXrXIONZgv6_NgWDzgtG8yF7550NIqqsyLmWm5-aOwUs5vaXz4NGnrTNf5SRvQsHnhfrzhGbPWeBUOgrj7zWqjRp4Adbi-kqCXkFR5ReHbViozwRF711HlmDResIZXH9QeuQdMtzMgxfiOrfRUNexZiCA11e6_5hbTCxnfNwg5wOdPP3iD0G7A87kCZ485P2edimpVnQVzY4CI1"],
    ]
]);

echo $res->getStatusCode(); // 200
echo $res->getBody();

/*$urlDB=parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

$tokenVk = "2816968ae753fdf5d35ed88fa6b396a219b0712f28e38d169cd0a04e8851c57eae6a29077cdfa8493a241";
$versionAPI = "5.103";
$user_id = "388061716";

$server = $urlDB["host"];
$username = $urlDB["user"];
$password = $urlDB["pass"];
$db = substr($urlDB["path"],1);

//echo $server.' <- сервер '.$username.' <- имя пользователя '.$password.' <- пароль '.$db.' <- база данных'; //Если нужно узнать данные бд

$mysqli = new mysqli($server, $username, $password,$db); //Подключаемся

  if ($mysqli->connect_error) {//проверка подключились ли мы
      die('Ошибка подключения (' . $mysqli->connect_errno . ') '. $mysqli->connect_error); //если нет выводим ошибку и выходим из кода
  } else {
      $mysqli->query("SET NAMES 'utf8'");//Устанавливаем кодировку

      error_log("----------");
      $mysqli->query("CREATE TABLE IF NOT EXISTS `dataSettings` ( 
	`operationId` VarChar( 255 ) NOT NULL DEFAULT 'off',
	`lastStatus` VarChar( 255 ) NULL )
ENGINE = InnoDB;"); //Создаем таблицу в бд

      while (true){
          $result_set = $mysqli->query("SELECT `operationId` FROM `dataSettings` ");

          if ($result_set !== false) {
              $operationId = $result_set->fetch_assoc();
          } else { // обработка ошибки
              echo "error: " . $mysqli->error;
              break;
          }

          if($operationId['operationId'] == "off"){continue;}
          elseif ($operationId['operationId'] == "start"){
              $statusJSON = json_decode(file_get_contents("https://api.vk.com/method/status.get?access_token=" . $tokenVk . "&user_id=". $user_id ."&v=". $versionAPI));
              $status = $statusJSON->response->text;
              $error = $mysqli->query("UPDATE dataSettings set lastStatus =  '$status' , operationID = 'on'");
              if($error) {
                  error_log('Прокатило;');
              } else {
                  error_log('Хьюстон! У нас проблемы!'.mysqli_error($error));

              }
              error_log($status);
          } elseif ($operationId['operationId'] == "on"){



          }elseif ($operationId['operationId'] == "finish"){

          }else{
              error_log($operationId['operationId'] . " type " . gettype($operationId['operationId']));
          }
      }
      error_log("End");
  } */
    ?>