<?php

function selectProcessing($site = '') {
    global $BD, $CONFIG;

    if ($site) {
        $time = time();

        //Определение количества запусков каждого прокси за пршедший час ================
        $requestBD = "
            SELECT COUNT(p.id) AS amount, pl.id
            FROM proxy_list pl JOIN processing p ON pl.id = p.proxy_id
            WHERE p.site='$site' AND p.date>".($time - 3600)." AND pl.enabled='1'
            GROUP BY p.proxy_id";

        $resultBD = mysqli_query($BD, $requestBD);

        $processing = array(); $ids = array();

        while($row = mysqli_fetch_assoc($resultBD)) {
            $processing[$row['id']] = $row['amount'];
            $ids[] = $row['id'];
        }

        $requestBD = "
            SELECT id 
            FROM proxy_list
            WHERE enabled='1' ".(count($ids) ? "AND id NOT IN ('".implode("','",$ids)."')" : "");

        $resultBD = mysqli_query($BD, $requestBD);
        while($row = mysqli_fetch_assoc($resultBD)) {
            $processing[$row['id']] = 0;
        }
        asort($processing, true);

        foreach ($processing as $index=>$item) if ($item > $CONFIG -> maxConnections) unset($processing[$index]);
        //===============================================================================

        //Исключить из списка прокси, которые работали последние $minPeriodRequest сек (в config.php)
        //и прокси, которые не работают с данным сайтом из таблицы proxy_disabled
        $requestBD = "
            SELECT * 
            FROM processing
            WHERE 
            (date>".($time - $CONFIG -> minPeriodRequest)." AND proxy_id IN ('".implode("','", array_keys($processing))."'))
            OR proxy_id IN (SELECT proxy_id FROM proxy_disabled WHERE site='$site')
        ";
        $resultBD = mysqli_query($BD, $requestBD);
        while($row = mysqli_fetch_assoc($resultBD)) {
            if (isset($processing[$row['proxy_id']])) unset($processing[$row['proxy_id']]);
        }
        //===============================================================================

        if (count($processing)) {
            reset($processing);

            $proxyId = key($processing);
            $proxyId = $proxyId ? $proxyId : false;

            if ($proxyId) {
                $requestBD = "SELECT * FROM processing WHERE site='$site' AND date<".(time() - $CONFIG -> minPeriodInfo)." ORDER BY date LIMIT 1";
                $resultBD = mysqli_query($BD, $requestBD);
                if (mysqli_num_rows($resultBD)) {
                    $processing = mysqli_fetch_assoc($resultBD);
                    if ($processing['proxy_id'] != $proxyId) {
                        $processing['proxy_id'] = $proxyId;
                        $r = "UPDATE processing SET proxy_id=$proxyId WHERE id='".$processing['id']."'";
                        mysqli_query($BD, $r);
                    }
                    $proxy_list = mysqli_fetch_assoc(mysqli_query($BD, "SELECT * FROM proxy_list WHERE id=".$proxyId));
                    $processing['proxy_id'] = $proxy_list;

                    return $processing;
                }
            }
        }
    }
    return false;
}

function addNewPageBD($site = "", $url = "", $type = "") {
    global $BD;
    $url = urlencode($url);
    $requestBD = "SELECT * FROM processing WHERE site='$site' AND url='$url' AND type_page='$type'";
    $resultBD = mysqli_query($BD, $requestBD);
    if (!mysqli_num_rows($resultBD)) {
        $requestBD = "INSERT INTO processing (`site`,`url`) VALUES ('$site', '$url')";
        mysqli_query($BD, $requestBD);
    }
}