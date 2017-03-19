<?php
function planetavtoParse($processing = array()) {
    global $BD, $CONFIG;

    $urlSite = $CONFIG -> sites[$processing['site']]['url'];

    $url = $processing['url'] ? urldecode($processing['url']) : $CONFIG -> sites[$processing['site']]['entry'];
$url = "http://webcars.u-charged.pro/test.txt";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://planetavto.com.ua/change_lang");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array("lang"=>"ru"));
    $response = curl_exec($ch);
    curl_close ($ch);


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    $content = curl_exec($ch);
    curl_close($ch);

    $time = time();
    $r = "UPDATE processing SET date='$time', url='".urlencode($url)."' WHERE id='".$processing['id']."'";
//    mysqli_query($BD, $r);

    $type_page = $processing['type_page'] ? $processing['type_page'] : 0;

    $listCars = array();

    switch ($type_page){

        //Категория товаров
        case 0:
            $html = str_get_html($content);
            foreach($html->find('#listObj table tbody') as $item){
                $car = array();
                $images = array();
                $element = str_get_html($item -> innertext);
                $image = $urlSite.$element->find('.parentTR img',0)->src;
                $images[] = strtr($image, array("thumb"=>"popup"));
                $car['name'] = $element->find('.parentTR td',1)->find('strong a',0)->innertext;
                $url = $element->find('.parentTR td',1)->find('strong a',0)->href;
                $car['url'] = urlencode($urlSite.$url);
                $car['avto_id'] = strtr($url, array("/car/"=>""));
                $element->find('.parentTR td',1)->find('strong a',0) -> outertext = "";
                $car['year'] = preg_replace("/[^0-9]/", '', trim(strip_tags($element->find('.parentTR td',1)->find('strong',0))));
                $price = $element->find('.parentTR td',1)->find('p',0)->find('strong',0)->innertext;
                $car['price'] = preg_replace("/[^0-9]/", '', strtr($price, array(" "=>"")));
                $car['city'] = strip_tags($element->find('.parentTR th',0)->find('a',0) -> innertext);
                $params1 = explode("<br />", $element->find('tr',1) -> find('td',0)->innertext);
                $car['engine_volume'] = isset($params1[0]) ? (((float) trim($params1[0])) * 1000) : "";
                $car['fuel'] = isset($params1[1]) ? trim($params1[1]) : "";
                $car['transmission'] = isset($params1[2]) ? trim($params1[2]) : "";
                $params2 = explode("<br />", $element->find('tr',1) -> find('td',1)->innertext);
                $car['mileage'] = isset($params2[0]) ? preg_replace("/[^0-9]/", '', trim($params2[0])) : "";
                $car['body'] = isset($params2[1]) ? trim($params2[1]) : "";
                $car['images'] = json_encode($images);
                $car['date_parse'] = time();
                $car['proxy'] = $processing['proxy_id']['id'];
                $car['site'] = $processing['site'];
                $listCars[] = $car;
            }

            $pagesIds = array();
            $pages = $html->find('.paging',0);
            $pages -> find("#perpage", 0) -> outertext = "";
            $element = str_get_html($pages -> innertext);
            foreach($element->find('a') as $page) $pagesIds[] = trim($page->innertext);
            $end = (int) end($pagesIds);

            $pagesUrls = array();
            for($index = 2; $index <= $end; $index++)  $pagesUrls[] = "http://planetavto.com.ua/?perpage=48&page=".$index;
            $html->clear();
            unset($html);

            if (count($pagesUrls)) foreach ($pagesUrls as $pageUrl) addNewPageBD($processing['site'],$pageUrl, 0);
            break;
    }
    return $listCars;
}


