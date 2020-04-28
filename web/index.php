<?php
ini_set('max_execution_time', 900); // Чтобы наш скрипт выполнялся

$urlDB=parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

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
	`operationId` TinyInt( 255 ) NOT NULL DEFAULT 0,
	`lastStatus` Text NULL )
ENGINE = InnoDB;"); //Создаем таблицу в бд
$i = 0;
//      while ($i < 1){
//          $operationId = $mysqli->query("SELECT `operationId` FROM `data` ");
//          if($operationId == 0){continue; error_log("00000");}
//          elseif ($operationId == 1){
////              error_log("11111");
////              $statusJSON = json_decode(file_get_contents("https://api.vk.com/method/status.get?access_token=" . $tokenVk . "&user_id=". $user_id ."&v=". $versionAPI));
////              $status = $statusJSON->response->text;
////              //$mysqli->query("UPDATE dataSettings set lastStatus = $status , operationID = 2");
////              error_log($status);
//              $i = 1;
//          }elseif ($operationId == 2){
//              error_log("22222");
//            break;
//          }elseif ($operationId == 3){
//
//          }else{
//              error_log("Pizda");
//          }
//      }
  }
    ?>