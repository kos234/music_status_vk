<?php
//ini_set('max_execution_time', 31536000); // Чтобы наш скрипт выполнялся

$urlDB=parse_url(getenv("CLEARDB_DATABASE_URL")); //Подключаемся к бд

$server = $urlDB["host"];
$username = $urlDB["user"];
$password = $urlDB["pass"];
$db = substr($urlDB["path"],1)

echo $server.' <- сервер '.$username.' <- имя пользователя '.$password.' <- пароль '.$db.' <- база данных'; //Если нужно узнать данные бд

$mysqli = new mysqli($server, $username, $password,$db); //Подключаемся

  if ($mysqli->connect_error) {//проверка подключились ли мы
      die('Ошибка подключения (' . $mysqli->connect_errno . ') '. $mysqli->connect_error); //если нет выводим ошибку и выходим из кода
  } else {

  }
    ?>