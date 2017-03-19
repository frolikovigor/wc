<?php
ini_set("display_errors",1);
error_reporting(E_ALL);

require_once "config.php";
require_once "functions.php";
require_once "libs/simplehtmldom/simple_html_dom.php";

$CONFIG = new Config();

if (!($BD = mysqli_connect($CONFIG -> host, $CONFIG -> user, $CONFIG -> password, $CONFIG -> db))) {
    echo "error BD";
    exit;
}
mysqli_set_charset($BD,'utf8');

//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `proxy_list` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `ip` varchar(15) NOT NULL DEFAULT '',
              `port` varchar(5) NOT NULL DEFAULT '',
              `login` varchar(25) NOT NULL DEFAULT '',
              `pass` varchar(25) NOT NULL DEFAULT '',
              `date_reg` int(11) NOT NULL DEFAULT '0',
              `enabled` tinyint(1) NOT NULL DEFAULT '0',
              `errors` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);

$requestBD = "INSERT INTO proxy_list (`id`,`ip`,`enabled`) VALUES (1, 'localhost',1) ON DUPLICATE KEY UPDATE ip='localhost'";
$resultBD = mysqli_query($BD, $requestBD);
//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `cars` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL DEFAULT '',
              `year` int(4) NOT NULL DEFAULT '0',
              `price` int(7) NOT NULL DEFAULT '0',
              `fuel` tinyint(1) NOT NULL DEFAULT '0',
              `mileage` int(6) NOT NULL DEFAULT '0',
              `transmission` int(2) NOT NULL DEFAULT '0',
              `drive` tinyint(1) NOT NULL DEFAULT '0',
              `images` varchar(8192) NOT NULL DEFAULT '',
              `color` int(4) NOT NULL DEFAULT '0',
              `engine_volume` int(5) NOT NULL DEFAULT '0',
              `body` int(2) NOT NULL DEFAULT '0',
              `proxy` int(11) NOT NULL DEFAULT '0',
              `site` varchar(20) NOT NULL DEFAULT '',
              `url` varchar(4096) NOT NULL DEFAULT '',
              `avto_id` varchar(20) NOT NULL DEFAULT '',
              `date_add` int(11) NOT NULL DEFAULT '0',
              `date_parse` int(11) NOT NULL DEFAULT '0',
              `city` varchar(20) NOT NULL DEFAULT '',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);
//drive - привод: передний - 1, задний - 2
//mileage - пробег, км
//transmission - коробка передач: авто - 1, ручн - 2
//images - ссылки на изображения в JSON
//engine_volume - объем двигателя, см3
//fuel - тип топлива: Бензин - 1, ДТ - 2, Газ/Бензин - 3, Электро - 4, Гибрид - 5
//color - цвет - id из таблицы БД colors
//body - тип кузова id из таблицы БД body
//site - сайт, с которого выполнялся парсинг
//date_add - дата первого парсинга (добавление авто в БД)
//date_parse - дата последнего парсинга
//avto_id - уникальные идентификатор авто на сайте
//city - город
//url - адрес страницы авто
//print_r($CONFIG -> assoc['body']); die;
//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `processing` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `proxy_id` int(11) NOT NULL DEFAULT '1',
              `site` varchar(20) NOT NULL DEFAULT '',
              `url` varchar(4096) NOT NULL DEFAULT '',
              `type_page` int(2) NOT NULL DEFAULT '0',
              `date` int(11) NOT NULL DEFAULT '0',
              `status` tinyint(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);
//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `proxy_disabled` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `proxy_id` int(11) NOT NULL DEFAULT '1',
              `site` varchar(20) NOT NULL DEFAULT '',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);
//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `body` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(20) NOT NULL DEFAULT '',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);
$requestBD = "SELECT * FROM body";
$resultBD = mysqli_query($BD, $requestBD);
if (!mysqli_num_rows($resultBD)) {
    $requestBD = "INSERT IGNORE INTO body (`id`,`name`) VALUES
                    (1,'Внедорожник'), (2,'Кабриолет'), (3,'Кроссовер'), (4,'Купе'),
                    (5,'Лимузин'), (6,'Лифтбэк'), (7,'Микроавтобус'), (8,'Микровэн'),
                    (9,'Минивэн'), (10,'Пикап'), (11,'Родстер'), (12,'Седан'), (13,'Сити-кар'),
                    (14,'Универсал'), (15,'Фастбэк'), (16,'Фаэтон'), (17,'Фургон'), (18,'Хэтчбек')
                 ";
    $resultBD = mysqli_query($BD, $requestBD);
}
//=============================================================================

$requestBD = "CREATE TABLE IF NOT EXISTS `colors` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `color` varchar(25) NOT NULL DEFAULT '',
              PRIMARY KEY (`id`)
        )";
$resultBD = mysqli_query($BD, $requestBD);
//=============================================================================

//Initial BD ==================================================================
//Filling table 'processing' with proxy and site
$requestBD = "INSERT INTO processing (`site`) SELECT sites.site FROM (";
foreach ($CONFIG->sites as $name => $site)
    $requestBD .= "SELECT '".$name."' AS site UNION ";

$requestBD = trim($requestBD);
if (strrpos($requestBD, "UNION") == (strlen($requestBD) - 5)) $requestBD = substr($requestBD, 0, -5);

$requestBD .=") sites WHERE not exists (SELECT * from processing where sites.site = processing.site)";
$resultBD = mysqli_query($BD, $requestBD);
//=============================================================================

//Parsing =====================================================================
$fieldsCar = array();
$requestBD = "SHOW COLUMNS FROM cars";
$resultBD = mysqli_query($BD, $requestBD);
while($row = mysqli_fetch_assoc($resultBD)) if ($row['Field'] != 'id') $fieldsCar[] = $row['Field'];

foreach ($CONFIG -> sites as $name => $site) {
    if (file_exists("parsers/".$name.".php"))
        if (is_array($processing = selectProcessing($name))) {
            require_once "parsers/".$name.".php";

            if (isset($processing['id']) && isset($processing['proxy_id']['id']) && isset($processing['url']) && isset($processing['type_page'])) {
                $func = $name."Parse";
                $listCars = $func($processing);

                //Сохранение авто в БД
                if (count($listCars)) {
                    foreach ($listCars as $index => $car) {

                        //Преобразование ассоциативн. слов ==============================
                        foreach ($car as $field => $value)
                            if (isset($CONFIG -> assoc[$field])) {
                                $assoc = $CONFIG -> assoc[$field];
                                if (isset($assoc[$field])) continue;
                                foreach ($assoc as $gField=>$arr) if (in_array($value, $arr)) $listCars[$index][$field] = $gField;
                            }
                        //===============================================================

                        //Подстановка id body из БД =====================================
                        if ($listCars[$index]['body']) {
                            $requestBD = "SELECT * FROM body WHERE name='".$listCars[$index]['body']."'";

                            $resultBD = mysqli_query($BD, $requestBD);
                            if (mysqli_num_rows($resultBD)) {
                                $row = mysqli_fetch_assoc($resultBD);
                                $listCars[$index]['body'] = isset($row['id']) ? $row['id'] : $listCars[$index]['body'];
                                $car = $listCars[$index];
                            }
                        }
                        //===============================================================



                        $requestBD = "SELECT * FROM cars WHERE site='$name' AND avto_id='".$car["avto_id"]."'";
                        $resultBD = mysqli_query($BD, $requestBD);
                        if (!mysqli_num_rows($resultBD)) {
                            $sql = "INSERT INTO cars (`".implode("`,`",$fieldsCar)."`) VALUES (";
                            $fList = "";
                            foreach ($fieldsCar as $field) {
                                if (($field == "date_add") && isset($car['date_parse'])) {
                                    $fList .= "'".$car['date_parse']."',";
                                } else {
                                    $fList .= isset($car[$field]) ? "'".$car[$field]."'," : "'',";
                                }
                            }
                            $fList = trim($fList, ",");
                            $sql .= $fList.")";

                            mysqli_query($BD, $sql);
                        } else {
                            $row = mysqli_fetch_assoc($resultBD);
                            $sql = "UPDATE cars SET ";
                            $fList = "";
                            foreach ($fieldsCar as $field) {
                                if (($field == "date_add") && isset($car['date_parse'])) {
                                    $fList .= $field."='".$car['date_parse']."',";
                                } else {
                                    $fList .= $field."='".(isset($car[$field]) ? $car[$field] : "")."',";
                                }
                            }
                            $fList = trim($fList, ",");
                            $sql .= $fList." WHERE id='".$row['id']."'";
                            mysqli_query($BD, $sql);
                        }
                    }
                }
            }
        }
}
//=============================================================================
exit();