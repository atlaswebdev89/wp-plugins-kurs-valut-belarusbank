<?php

function atl_wp_currency_active () {
   wp_atl_createTableCurrency();
   
}
function atl_wp_currency_deactive () {
    //Функция очистки таблицы при деактивации плагина
    wp_atl_deleteTable_all();
}
function atl_wp_currency_unistall () {
    //Функция удаления таблицы при удалении плагина
    wp_atl_delete_table_currency();
}

//Функция создание таблицы в БД для хранения курсов валют (при активации плагина)
function wp_atl_createTableCurrency () {
    global $wpdb;
    $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
    
    //Для доступа к финкции dbDelta
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    if($wpdb->get_var("SHOW TABLES LIKE '".CURRENCY_TABLE."'") != CURRENCY_TABLE) {
            $sql = "CREATE TABLE ".CURRENCY_TABLE." (
                id int(11) unsigned NOT NULL auto_increment,
                number int(11) unsigned NOT NULL, 
                city varchar(255) NOT NULL,
                date_modify timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                date_update int(24) unsigned NOT NULL, 
                currency json DEFAULT NULL, 
                PRIMARY KEY  (id),
                UNIQUE (number)
            ) {$charset_collate};";

        // Создать таблицу.
        dbDelta( $sql );
        }
    }
    
//Функция добавления данных в таблицу
function wp_atl_addCurrencyTable (array $data) {
    global $wpdb;
    //Отключаем показ ошибок на экране
    $wpdb->show_errors = FALSE;
    
    if(is_array($data) && !empty($data)) {
        
        $result = $wpdb->insert(CURRENCY_TABLE, 
            [
                "number"=>$data["number"],
                "city" => $data["city"],
                "currency" => $data["currency"],
                "date_update" => time(),
            ],
            [
                "%d",
                "%s",
                "%s",
                "%d",
            ]);
        if(!$wpdb->last_error) {
            return $wpdb->insert_id;
        }else {
            error_log(((strpos($wpdb->last_error,'Duplicate entry')) !== false) ? __('The specified key already exists (UNIQUE constraint)','atl-wp-kurs-widget').' ['.$wpdb->last_error .']' : $wpdb->last_error);
          return $result;
        }
    }
}

//Функция обновления курсов в таблице
function wp_atl_updateCurrency ($id, $currency, $city, $date_modity = null) {
   global $wpdb; 
   
   //Проверяем есть ли в таблице запись для данного виджета. 
   if($wpdb->get_var("SELECT COUNT(*) FROM ".CURRENCY_TABLE." WHERE number = ".$id." " ) == 1) {
        
       if(isset($currency) && isset($city)) {
                return $wpdb->update(CURRENCY_TABLE, 
                     [
                         "city" => $city,
                         "currency" => $currency,
                     ],
                     [
                         "number" => $id,
                     ],
                        ["%s", "%s"],["%d"]
                   );
       }
    }
} 

//Функция получения данных из таблицы
function wp_atl_getResult($number) {
    global $wpdb; 
        $prepare = $wpdb->prepare(
            "SELECT * FROM ".CURRENCY_TABLE." WHERE number = %d", [$number]
        );
        $result = $wpdb->get_row($prepare);
    return $result;
}

//Функция проверки наличия в таблице
function wp_atl_getVar($number) {
    global $wpdb; 
        $prepare = $wpdb->prepare(
            "SELECT COUNT(*) FROM ".CURRENCY_TABLE." WHERE number = %d", [$number]
        );
        $result = $wpdb->get_var($prepare);
    return $result;
}
//Функция получения времени изменения курса валют в банке (время обновления курсов валют)
function wp_atl_getModity ($number) {
    global $wpdb;
    $prepare = $wpdb->prepare (
                "SELECT date_modify FROM ".CURRENCY_TABLE." WHERE number = %d", [$number]
            );
        $result = $wpdb->get_var($prepare);
    return $result;
}

//Функция получения времени последнего запроса к API на получение данных курсов
function wp_atl_getUpdate_currency ($number) {
    global $wpdb;
    $prepare = $wpdb->prepare (
                "SELECT date_update FROM ".CURRENCY_TABLE." WHERE number = %d", [$number]
            );
        $result = $wpdb->get_var($prepare);
    return $result;
}

//Функция обновления времени последнего запроса к API
function wp_atl_date_update_modify($number) {
    global $wpdb;
    $date_modity = wp_atl_getModity($number);
        return $wpdb->update(CURRENCY_TABLE, 
                         [
                             "date_modify" => $date_modity,
                             "date_update" => time(),

                         ],
                         [
                             "number" => $number,
                         ],
                            ["%s", "%d"],["%d"]
                       );
}

//Функция получения курсов валют по указаному городу и ближайшему времени обновления
function wp_atl_getCurrencyCityTime ($city) {
   global $wpdb; 
   if(isset($city) && !empty($city)) {
            //Проверяем кеш
            if($cache = wp_cache_get($city.'_CurrencyTime')) {
                return $cache;
            }
            
            $prepare = $wpdb->prepare ("SELECT * FROM ".CURRENCY_TABLE." WHERE date_modify = (
                    SELECT MAX(date_modify) FROM ".CURRENCY_TABLE." WHERE city=%s)", 
                    [$city]
                );
            $result = $wpdb->get_row($prepare);
            //Добавляем в кеш
            if($result) wp_cache_add( $city.'_CurrencyTime', $result );
        return $result;
   }
}

//Функция получения названия города
function get_cityName_inBD () {
            global $wpdb;
            //Проверяем кеш
            if($cache = wp_cache_get('default_CITY')) {
                        return $cache;
            }
            
            $defaultCity = $wpdb->get_var("SELECT city FROM ".CURRENCY_TABLE."");

            //Записываем к кеш
            if($defaultCity) wp_cache_add( 'default_CITY', $defaultCity );
        return $defaultCity;
}

//Фунция удаления данных в таблице
function wp_atl_deleteDataTables ($id) {
    global $wpdb; 
    if(isset($id) && !empty($id)) {
        return $wpdb->delete(CURRENCY_TABLE,
                [
                    "number"=>$id
                ], ["%d"]
            );
    }
}

//Функция полной очистки таблицы при деактивировании плагина
function wp_atl_deleteTable_all () {
    global $wpdb;
    $wpdb->query("DELETE FROM ".CURRENCY_TABLE."");
}

//Функция удаления таблицы при удалении плагина
function wp_atl_delete_table_currency () {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS  ".CURRENCY_TABLE."");
} 

//Функция для шорткода [getCash exchange="USD" city="Брест" ]$content[/getCash]
function wp_atl_get_currency_by_atlas ($atts, $content) {
    $atts = shortcode_atts( [
		'exchange' => "USD",
		'city'  => get_cityName_inBD(),
	], $atts );
     
    //Получаем курс текущей валюты для указанного города
    if($exc = wp_atl_getCurrency_city_by_atlas($atts["exchange"], $atts["city"])){
        if(is_numeric($content = strip_tags(trim($content)))) {
            //Код получения цены с учетом текущего курса валют
            return ceil(($exc*$content));
        }else {
            return $content;
        }
    }else {
        return $content;
    }  
}

// ФУНКЦИЯ получения курсов валют через API BelarusBank и запись в БД
function wp_atl_get_currency_from_API ($city, $number) {
        
        if(!isset($number) && empty($number)) return FALSE;
        $city = !empty($city) ? $city:'Брест';
        
        $url_kurs_belarusbank_api = API.'?city='.$city;
        
        //get запрос к API Belarusbank
        $json_data = file_get_contents($url_kurs_belarusbank_api);
        //Проверка полученных данных
                    if (isset($json_data) && !empty($json_data)) {
                        $info = json_decode($json_data, true);
                        $json_currency = json_encode($info[0]);
                            //Записываем полученные данные в таблицу базы данных
                            if(wp_atl_getVar($number)) {
                                //Обновляем данные 
                                $result = wp_atl_updateCurrency ($number, $json_currency, $city);
                                //Функция обновления времени последнего запроса к API
                                wp_atl_date_update_modify($number);
                            }else {
                                //Записываем данные
                                $result = wp_atl_addCurrencyTable ([
                                                                        "number"=>$number,
                                                                        "city" => $city,
                                                                        "currency" => $json_currency,
                                                                   ]);
                            }
                        return $result;    
                        
                    }else {
                            error_log(__('Error update exchange rait','atl-wp-kurs-widget'));
                        return FALSE;
                    }
}

//Функция получения курсов валют для вывода в виджете
function wp_atl_getCurrency_widget ($instance, $number) {
    
    if(!isset($instance) || empty($instance)) return FALSE;
    if(!isset($instance['val']) || empty($instance['val'])) return FALSE;
        $city = $instance['city'];
        $time_update = $instance['time_update'];
        $set_currency = $instance['val'];
    
    //Проверяем в таблице БД наличие курсов для текущего виджета ( виджет с номером $number)
    if(isset($number) && !empty($number)) {    
        $item_widget = wp_atl_getVar($number);
            if($item_widget) {
                //Проверяем когда был последний запрос к API на получение курсов 
                $update_time = wp_atl_getUpdate_currency($number);
                $current_time = time();
                /*Время прошедшее с момента последнего запроса к API*/
                $fff = ($current_time - $update_time);
                //Если больше заданного в настойках виджета делаем запрос на обновление 
                if ($fff > ($time_update * 60)) {
                           wp_atl_get_currency_from_API($city, $number);
                        }
                return wp_atl_data_create_from_widget ($number, $set_currency);
            }else {
                
                //Если виджета нет получаем курсы для него и записывает в БД
                if(wp_atl_get_currency_from_API($city, $number)) {
                        return wp_atl_data_create_from_widget ($number, $set_currency);
                }else {
                        error_log(__('Error update exchange rait','atl-wp-kurs-widget'));
                    return FALSE;
                }
        }
    }else {
        return FALSE;
    }
}

//Функция получения и формирования массива данных для виджета
function wp_atl_data_create_from_widget ($number, $set_currency) {
    if($data  = wp_atl_getResult($number)){
    $data_currency = json_decode($data->currency, true);
    //Формируем массив курсов
            foreach ($set_currency as $val) {
                if((array_key_exists(($val.'_out'), $data_currency)) && $data_currency[$val.'_out'] > 0){
                    $cash_array[$val] = $data_currency[$val.'_out'];
                }
            }
            if(isset($cash_array) && !empty($cash_array)) {
                    $cash_array['time_update'] = date('d.m.Y H:i:s', strtotime(($data->date_modify)));
                    $cash_array['city'] = $data->city;
                return $cash_array;
            }else return FALSE;
    }else {
        return FALSE;
    }
}



