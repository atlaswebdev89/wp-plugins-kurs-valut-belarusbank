<?php
/*
Plugin Name: Exchange rate Belarusbank by Atlas
Description: Creating widget for getting exchange rate of currencies  by Belarusbank
Version: 1.3.0
Author: Atlas-it
Author URI: http://atlas-it.by
Text Domain: atl-wp-kurs-widget
Domain Path: /lang/
License:     GPL2

Copyright 2020  Atlas  (email: atlas.webdev89@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

// строки для перевода заголовков плагина, чтобы они попали в .po файл.
__( 'Exchange rate Belarusbank by Atlas', 'atl-wp-kurs-widget' );
__( 'Creating widget for getting exchange rate of currencies  by Belarusbank', 'atl-wp-kurs-widget' );

/*langs file*/
$plugin_dir = basename( dirname( __FILE__ ) );
load_plugin_textdomain( 'atl-wp-kurs-widget', null, $plugin_dir.'/lang/' );

global $wpdb;
define('ANBLOG_TEST_DIR', plugin_dir_path(__FILE__));     //полный путь к корню папки плагина (от сервера)
define('ANBLOG_TEST_URL', plugin_dir_url(__FILE__));      //путь к корню папки плагина (лучше его использовать)
define('CURRENCY_TABLE',  $wpdb->get_blog_prefix() .  'currency_table');
define('API', 'https://belarusbank.by/api/kursExchange'); // uri api belarusbank

require_once 'widgets/atl-wp-widget-currency.php';
require_once 'includes/functions.php';
require_once 'includes/getKurs.php';

/*Хук срабатывания при активации плагина*/
register_activation_hook(__FILE__, 'atl_wp_currency_active');
/*Хук срабатывает при деактивации плагина*/
register_deactivation_hook  (__FILE__, 'atl_wp_currency_deactive');
/*Хук срабатывает при удалении плагина*/
register_uninstall_hook     (__FILE__, 'atl_wp_currency_unistall');

//Register shortCode
add_shortcode( 'getCash', 'wp_atl_get_currency_by_atlas' );

add_action('widgets_init', 'atl_kurs');
function atl_kurs () { 
    register_widget('Atl_Wp_Widget_Currency');
}


