<?php

class Atl_Wp_Widget_Currency extends WP_Widget {
    
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
                            'Брест'     => __('Brest','atl-wp-kurs-widget'),
                            'Гродно'    => __('Grodno','atl-wp-kurs-widget'),
                            'Витебск'   => __('Vitebsk','atl-wp-kurs-widget'),
                            'Минск'     => __('Minsk','atl-wp-kurs-widget'),
                            'Могилев'   => __('Mogilev','atl-wp-kurs-widget'),
                            'Гомель'    => __('Gomel','atl-wp-kurs-widget'),
                        );
        /*Список валюты*/
                $cash = array (
                            'USD',
                            'EUR',
                            'RUB',
                            'PLN',
                            
                );
        /*Время обновления курсов валют*/
                $time_update = array (
                            60,
                            90,
                            120,
                            360
                );
                
            $city = isset($instance['city']) ? $instance['city']:'Минск';
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
                        foreach ($citys as $key=>$city_name){
                            if($key == $city){
                                echo '<option value ='.$key.' selected="selected">'.$city_name.'</option>';
                            }else
                                echo '<option value ='.$key.'>'.$city_name.'</option>';
                        }
                    ?> 
                </select>
            </p>
            <p>
                <?php
                    foreach ($cash as $key=>$mon){
                        if(isset($instance['val']) && is_array($instance['val']) && in_array($mon, $instance['val']))
                        {
                            echo '<input type="checkbox" id="'.$this->get_field_id('val').$key.'" name="'.$this->get_field_name('val').'[]" value ="'.$mon.'" checked>
                                  <label for="'.$this->get_field_id('val'). $key.'">'.$mon.'</label><br />';
                        }else
                            echo '<input type="checkbox" id="'.$this->get_field_id('val').$key.'" name="'.$this->get_field_name('val').'[]" value ="'.$mon.'">
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
                            }else
                                echo '<option value ='.$time.'>'.$time.'</option>';
                        }
                    ?> 
                </select>
            </p>
       <?php
    }
    
    public function widget ($args, $instance) {
        
            //Получаем курсы валют
            $data = wp_atl_getCurrency_widget($instance, $this->number);
                if(isset($data["time_update"]) && !empty($data["time_update"])){
                    $update_currency = $data["time_update"];
                    unset($data["time_update"]);
                }else {
                    $update_currency = wp_date("d.m.Y");
                }
                
                if(isset($data['city']) && !empty($data['city'])) {
                        $city = $data['city'];
                    unset ($data['city']);
                }else $city = '';
        
                    echo $args['before_widget'];
                    echo $args['before_title'].$instance['title']." ( ".$city." ) ".$args['after_title'];
                        if (isset($data) && !empty($data)) {
                                echo '<ul>';
                                        foreach ($data as $key=>$val){?>
                                                    <li><?php echo $key.' - '.$val; ?></li>
                                        <?php } ?>
                                    </ul>
                                <p><?php _e('Rait of currencies in ','atl-wp-kurs-widget');?><?php echo current_time('d.m.Y');?></p>
                                <p><?php _e('Latest update ','atl-wp-kurs-widget');?><?php  echo $update_currency; ?></p>
                                <?php } else {
                                        echo '<ul><li>'. __('No data available','atl-wp-kurs-widget').'</li></ul>';
                                        } ?>
                                <?php   echo $args['after_widget'];
    }

    public function update ($new_instance, $old_instance) {
                if (empty($new_instance['city'])){$new_instance['city'] = 'Брест';}
                if (empty($new_instance['time_update'])){$new_instance['time_update'] = 120;}
                else {$new_instance['time_update']=(int)$new_instance['time_update']?$new_instance['time_update']:120;}
                $new_instance['title']=!empty($new_instance['title'])?strip_tags($new_instance['title']):__('Exchange rate','atl-wp-kurs-widget');
            /*Обновление курсов в файле при изменении настроек виджета*/
            wp_atl_get_currency_from_API($new_instance['city'],$this->number);
        return $new_instance;
    }
}