<?php

//Функция получения курсов валют в текущем городе
//$currency ("usd", "eur", "ryb" и тд)
//$city ('Брест' ,'Гродно' ,'Витебск','Минск','Могилев','Гомель')
//$value - необязательный параметры. Сумма которую необходимо конвертировать в BIN (Беларусский рубль) 
function wp_atl_getCurrency_city_by_atlas ($currency = "usd", $city = "Брест", $value = null) {
    if(isset($city) && !empty($city)) {
        //Запрос в БД для получения курса валют по городу и валюте
        $result = wp_atl_getCurrencyCityTime($city);
            if($result) {
                    if(isset($currency) && !empty($currency)) {
                        $data_currency = json_decode($result->currency, true);
                            foreach ($data_currency as $key=>$item) {
                                if ((strtoupper($currency)."_out") == $key){
                                        $exchanges = $item;    
                                    break;
                                }
                            }
                    }
            }else {
                    error_log(__('Error request in databases','atl-wp-kurs-widget'));
                return FALSE;
            }

            if(!isset($exchanges) || empty($exchanges)) {
                    error_log(__('Not current currence in database','atl-wp-kurs-widget'));
                return FALSE;
            }
            
            if(isset($value) && !empty($value) && is_numeric(strip_tags(trim($value)))) {
                return ceil($exchanges*$value);
            }
        
        return $exchanges;
    }
}

