<?php
class Config {
    //Доступ к базе данных
    public $host = 'ucharged.mysql.ukraine.com.ua'; //Хост
    public $user = 'ucharged_webcars';              //Логин
    public $password = 'ndzdt84p';                  //Пароль
    public $db = 'ucharged_webcars';                //База данных

    public $maxConnections = 5;                     //Максимальное количество запросов к каждому отдельному сайту в час
    public $minPeriodRequest = 60;                  //Минимальный период между запросами к сайту, сек
    public $minPeriodInfo = 3600*24;                //Минимальный период обновления информации по кажому авто, сек

    public $assoc = array(
        'transmission' => array(
            'Механическая' => array('Механіка','Механика'),
            'Автоматическая' => array('Автомат'),
            'Типтроник' => array()
        ),
        'body' => array(
            "Внедорожник" => array(),
            "Кабриолет" => array(),
            "Кроссовер" => array(),
            "Купе" => array(),
            "Лимузин" => array(),
            "Лифтбэк" => array(),
            "Микроавтобус" => array(),
            "Микровэн" => array(),
            "Минивэн" => array(),
            "Пикап" => array("Пікап"),
            "Родстер" => array(),
            "Седан" => array(),
            "Сити-кар" => array(),
            "Универсал" => array("Універсал"),
            "Фастбэк" => array(),
            "Фаэтон" => array(),
            "Фургон" => array(),
            "Хэтчбек" => array("Хетчбек")
        )
    );

    public $sites = array(
        "planetavto" => array(
            "url" => "http://planetavto.com.ua",
            "entry" => "http://planetavto.com.ua/?perpage=48",
        ),
        "infocar" => array(
            "url" => "",
            "entry" => "",
        ),
        "avtobazar" => array(
            "url" => "",
            "entry" => "",
        ),
        "rst" => array(
            "url" => "",
            "entry" => "",
        ),
        "ria" => array(
            "url" => "",
            "entry" => "",
        ),
        "etachki" => array(
            "url" => "",
            "entry" => "",
        ),
        "getcar" => array(

        )
    );

}


