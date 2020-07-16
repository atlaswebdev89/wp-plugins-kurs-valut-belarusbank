<?php
/*
Plugin Name: Exchange rate Belarusbank by Atlas
Description: Creating widget for getting exchange rate of currencies  by Belarusbank
Version: 1.0.0
Author: Atlas-it
Author URI: http://atlas-it.by
Text Domain: atl-wp-kurs-widget
Domain Path: /lang/
*/

// строки для перевода заголовков плагина, чтобы они попали в .po файл.
__( 'Exchange rate Belarusbank by Atlas', 'atl-wp-kurs-widget' );
__( 'Creating widget for getting exchange rate of currencies  by Belarusbank', 'atl-wp-kurs-widget' );

/*langs file*/
$plugin_dir = basename( dirname( __FILE__ ) );
load_plugin_textdomain( 'atl-wp-kurs-widget', null, $plugin_dir.'/lang/' );

add_action('widgets_init', 'atl_kurs');

function atl_kurs () { 
    register_widget('ATL_kurs');
}

class ATL_kurs extends WP_Widget {

    protected $API = 'https://belarusbank.by/api/kursExchange?city=';
    protected $plugin_path;
    protected $fileTempkurs;
 
    public function __construct() {
    $args = array (
            'name'=>__('Exchange rate of currencies','atl-wp-kurs-widget'),
            'description'=>__('Getting exchange rate from API Belarusbank','atl-wp-kurs-widget'),
             );
        parent::__construct ('atl_kurs', '', $args);
    }
    
    public function form ($instance) {
 
        /*Список городов*/    
        $citys = array(
                            __('Brest','atl-wp-kurs-widget'),
                            __('Grodno','atl-wp-kurs-widget'),
                            __('Vitebsk','atl-wp-kurs-widget'),
                            __('Minsk','atl-wp-kurs-widget'),
                            __('Mogilev','atl-wp-kurs-widget'),
                            __('Gomel','atl-wp-kurs-widget')
                        );
        /*Список валюты*/
                $cash = array (
                            'USD',
                            'EUR',
                            'RUB',
                            'PLN'
                );
        /*Время обновления курсов валют*/
                $time_update = array (
                            30,
                            60,
                            90,
                            120,
                            360
                );
                
        $city = isset($instance['city']) ? $instance['city']:__('Brest','atl-wp-kurs-widget');
        $time_update_kurs =isset($instance['time_update'])?$instance['time_update']:120;
        $title=isset($instance['title']) ? $instance['title']:__('Exchange rate','atl-wp-kurs-widget');
       ?>
        
            <p>
                <label for = "<?php echo $this->get_field_id('title');?>"><?php _e('Title','atl-wp-kurs-widget');?></label>
                <input class="widefat title" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo $title;?>">
            </p>
            
            <p>
                <label for = "<?php echo $this->get_field_id('city');?>"><?php _e('Select city','atl-wp-kurs-widget');?></label>
                <select class = "widefat" id="<?php echo $this->get_field_id('city');?>" name="<?php echo $this->get_field_name('city');?>">
                        <option></option>
                    <?php  
                        foreach ($citys as $city_name){
                            if($city_name == $city){
                                echo '<option value ='.$city_name.' selected="selected">'.$city_name.'</option>';
                            }else echo '<option value ='.$city_name.'>'.$city_name.'</option>';
                        }
                    ?> 
                </select>
            </p>
            <p>
                <?php
                    foreach ($cash as $key=>$mon){
                        if(isset($instance['val']) && is_array($instance['val']) && in_array($mon, $instance['val'])){
                            echo '<input type="checkbox" id="'.$this->get_field_id('val').$key.'" name="'.$this->get_field_name('val').'[]" value ="'.$mon.'" checked>
                                  <label for="'.$this->get_field_id('val'). $key.'">'.$mon.'</label><br />';
                        }else echo '<input type="checkbox" id="'.$this->get_field_id('val').$key.'" name="'.$this->get_field_name('val').'[]" value ="'.$mon.'">
                                  <label for="'.$this->get_field_id('val'). $key.'">'.$mon.'</label><br />';
                    }
                ?>           
            </p>
            
            <p>
                <label for = "<?php echo $this->get_field_id('time_update');?>"><?php _e('Select update time, minutes','atl-wp-kurs-widget');?></label>
                <select class = "widefat" id="<?php echo $this->get_field_id('time_update');?>" name="<?php echo $this->get_field_name('time_update');?>">
                        <option></option>
                    <?php  
                        foreach ($time_update as $time){
                            if($time == $time_update_kurs){
                                echo '<option value ='.$time.' selected="selected">'.$time.'</option>';
                            }else echo '<option value ='.$time.'>'.$time.'</option>';
                        }
                    ?> 
                </select>
            </p>
            
            
       <?php
        
    }
    
    public function widget ($args, $instance) { 
        /*Путь к папке с плагином*/
        $dir_path = plugin_dir_path( __FILE__ );
        /*Файл для хранения курсов валют*/
        $fileTempKurs = $dir_path.'text.txt';
       
        if (file_exists($fileTempKurs) && is_readable($fileTempKurs)){
                /*Время последнего обновления файла с курсами валют (unix)*/
                $timelast = filemtime($fileTempKurs);
                /*Текущее время (unix)*/
                $currentTime = time();
               
                /*Время прошедшее с момента последнего обновления файла (unix)*/
                $fff = $currentTime - $timelast; 
                /*Обновления файла с курсами*/
                if($fff>($instance['time_update']*60)){ 
                            if (isset($instance['val']) && !empty($instance['val'])) {
                                    $city = !empty($instance['city']) ? $instance['city']:__('Brest','atl-wp-kurs-widget');
                                    $url_kurs_belarusbank_api = 'https://belarusbank.by/api/kursExchange?city='.$city;
                                    $json_data = file_get_contents($url_kurs_belarusbank_api);  
                                  //Проверка полученных данных  
                                if (isset($json_data) && !empty($json_data)) {
                                    $info = json_decode($json_data, true);
                                    if (is_array($info) && !empty($info)) {
                                        $cash_array = array();
                                        $exc = 0;
                                        foreach ($instance['val'] as $val) {
                                            foreach ($info as $data) { 
                                                if ($data[$val.'_out'] !=0  && $data[$val.'_out']>$exc)
                                                        {
                                                            $exc = $data[$val.'_out'];
                                                        }
                                            }
                                            if ($exc > 0 ) {$cash_array[$val] = $exc;}  
                                        }
                                        
                                        if (!empty($cash_array)) {
                                            /*Запись в файл*/
                                            $f = fopen($fileTempKurs, "w"); // открытие файла в режиме записи
                                                foreach ($cash_array as $key=>$output)
                                                        {
                                                               $text = $key.'-'.$output;
                                                               fwrite($f, $text."\r\n"); // запись в файл строк
                                                        }
                                            fclose($f);
                                        }else error_log(__('data null','atl-wp-kurs-widget'));
                                     }else error_log(__('Error data:', 'atl-wp-kurs-widget').$info);
                                 } else error_log(__('Error update exchange rait','atl-wp-kurs-widget'));
                            }else error_log(__('Not selected exchange rait','atl-wp-kurs-widget'));
                        }
                }
                  
                $data = file($fileTempKurs, FILE_SKIP_EMPTY_LINES); 
                $timelast2 = filemtime($fileTempKurs);
                echo $args['before_widget'];
                echo $args['before_title'].$instance['title'].$args['after_title'];   
                  if (isset($data) && !empty($data)) {
                ?>
                    <ul class = "kurs_widget">
                        <?php foreach ($data as $val) { $vals = explode( '-', $val); ?>
                             <li><?php echo $vals[0].' - '.$vals[1]; ?></li>
                        <?php }; ?>
                    </ul>
                    <p><?php _e('Rait of currencies in ','atl-wp-kurs-widget');?><?php echo current_time('d.m.Y');?></p>
                    <p><?php _e('Latest update ','atl-wp-kurs-widget');?><?php echo date('H:i:s',($timelast2+(3*3600)))?></p>

                <?php   echo $args['after_widget'];
                  }
    }  
    
    public function update ($new_instance, $old_instance) {
        
        /*Путь к папке с плагином*/
        $dir_path = plugin_dir_path( __FILE__ );
        /*Файл для хранения курсов валют*/
        $fileTempKurs = $dir_path.'text.txt';
        
        if (file_exists($fileTempKurs) && is_readable($fileTempKurs)){ 
            if (isset($new_instance['val']) && !empty($new_instance['val'])) {
                        $city = !empty($new_instance['city']) ? $new_instance['city']:'Брест';
                                $url_kurs_belarusbank_api = 'https://belarusbank.by/api/kursExchange?city='.$city;
                                if($json_data = file_get_contents($url_kurs_belarusbank_api)) {
                                $info = json_decode($json_data, true);
                                $cash_array = array();
                                $exc =0;
                                foreach ($new_instance['val'] as $val) {
                                    foreach ($info as $data) { 
                                        if ($data[$val.'_out']>$exc)
                                                {
                                                    $exc = $data[$val.'_out'];
                                                }
                                    }
                                    if ($exc > 0 ) {$cash_array[$val] = $exc;}  
                                }
                                    if (!empty($cash_array)) {
                                        /*Запись в файл*/
                                        $f = fopen($fileTempKurs, "w"); // открытие файла в режиме записи
                                            foreach ($cash_array as $key=>$output)
                                                    {
                                                           $text = $key.'-'.$output;
                                                           fwrite($f, $text."\r\n"); // запись в файл строк
                                                    }
                                        fclose($f);
                                    }else error_log(__('data null','atl-wp-kurs-widget'));
                                } else error_log(__('Error update exchange rait','atl-wp-kurs-widget'));
                    }else file_put_contents($fileTempKurs, '');
                }
                if (empty($new_instance['city'])){$new_instance['city'] = __('Brest','atl-wp-kurs-widget');}
                if (empty($new_instance['time_update'])){$new_instance['time_update'] = 120;}
                else {$new_instance['time_update']=(int)$new_instance['time_update']?$new_instance['time_update']:120;}
                $new_instance['title']=!empty($new_instance['title'])?strip_tags($new_instance['title']):__('Exchange rate','atl-wp-kurs-widget');
        return $new_instance;
    }

    protected function updatecurrencies () {

    }
}