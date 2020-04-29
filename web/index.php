<?php
ini_set('max_execution_time', 31536000); // Чтобы наш скрипт выполнялся

$urlDB=parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

$tokenVk = "2816968ae753fdf5d35ed88fa6b396a219b0712f28e38d169cd0a04e8851c57eae6a29077cdfa8493a241";
$tokenSpotify = "BQC8i9bw5KEMa4xzhuF36sCIMLs4eaa6qWBu1BFDxmccEaQfuWRqzx4j26nJjNBwcnLCz8nF9_CwLYAIgnSs8I5Pk7Q8XTNfsI1kUdWaCr_tgUkq8eHPOuum1aN_jTpeDhqjKxTMr9yaLufWFDvyqPFIhPGDT0np7q3xV8R8YViJnVSznf6qiT-EzCjSU3iRnHDy9r3nCIByMW-zJ1dhtv2Brf4m1Nia5v2RoJu-AycECQv8A0RAygdTCAZKU18fag9BMinz9IvKR99jEJJsOKrOu2jS1L3yH3qm6PHaE3E";
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
              $mysqli->query("UPDATE dataSettings set lastStatus =  '$status' , operationID = 'on'");
              error_log($status);
          } elseif ($operationId['operationId'] == "on"){
            $trackJSON = json_decode(file_get_contents("https://api.spotify.com/v1/me/player/currently-playing?access_token=" . $tokenSpotify));
           // $count = 0;
            $artists = "";
            $album = "";
            //while (isset($trackJSON->item->artists[$count]->name)){
                error_log("kek -" . $artists + $trackJSON->item->artists[0]->name);
               $artists = $artists + $trackJSON->item->artists[0]->name;
                $artists = $artists + ", ";
               // $count ++;
           // }

            if(isset($trackJSON->item->album))
                if($trackJSON->item->album->type == "album")
                    $album = " , Альбом: " . $trackJSON->item->album->name;

            $status = "Слушает: " . substr($artists, -2) . " - " . $trackJSON->item->name . $album;

            error_log("Получилось -> ." .$status);

            sleep(60);

          }elseif ($operationId['operationId'] == "finish"){

          }else{
              error_log($operationId['operationId'] . " type " . gettype($operationId['operationId']));
          }
      }
      error_log("End");
  }
    ?>