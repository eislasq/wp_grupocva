<?php

/*
  Plugin Name: Inksumos
  Plugin URI:  http://www.inksumos.com.mx/
  Description: Herramienta para importar productos de Grupo CVA
  Version:     1.0.0
  Author:      Eliut Islas
  Author URI:  https://eliutislas.wordpress.com/
  License:     Inksumos
  License URI: http://www.inksumos.com.mx/
  Text Domain: ink
  Domain Path: /languages

 */

ob_start();
setlocale(LC_ALL, 'es_MX');
date_default_timezone_set('America/Mexico_City');
define('INK_PATH', plugin_dir_path(__FILE__));
define('INK_URL', plugins_url('', __FILE__));


spl_autoload_register(function ($nombre_clase) {
    $ruta_clase = str_replace('\\', '/', $nombre_clase);
    $pathParts = explode('/', $ruta_clase);
    $ink = array_shift($pathParts);
    if ('Ink' === $ink) {
        if (file_exists(__DIR__ . "/$ruta_clase.php")) {
            include __DIR__ . "/$ruta_clase.php";
        }
    }
});

/**
 * Since WPDB->query cannot handle multiple querys
 * This function reads the SQL file and splits it into querys
 * Then executes each Query at a time.
 * */
function hcwp_run_sql($file) {
    error_log(PHP_EOL . "###" . $file, 3, INK_PATH . '/query.txt');
    global $wpdb;
    $queries = explode(";", file_get_contents($file));
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            error_log(PHP_EOL . "###" . $query, 3, INK_PATH . '/query.txt');
            $return = $wpdb->query($query);
            if (false === $return) {
                error_log(PHP_EOL . "ERROR" . $wpdb->last_error . PHP_EOL . PHP_EOL, 3, INK_PATH . '/query.txt');
                error_log($wpdb->last_error . "\nQuery: " . $wpdb->last_query . "\n\n", 3, INK_PATH . '/query.log');
            }
        }
    }
}

//function isa_add_cron_recurrence_interval( $schedules ) {
// 
//    $schedules['every_ten_seconds'] = array(
//            'interval'  => 10,
//            'display'   => __( 'Every 10 Seconds', 'textdomain' )
//    );
//     
//    return $schedules;
//}
//add_filter( 'cron_schedules', 'isa_add_cron_recurrence_interval' );
#############


add_action('ink_daily_import', 'Ink\\Catalog::import');

//add_action('ink_hourly_test', 'Ink\\Catalog::testCron');

function hcwp_activate() {
    if (!wp_next_scheduled('ink_daily_import')) {
        wp_schedule_event(time(), 'daily', 'ink_daily_import');
    }
//    if (!wp_next_scheduled('ink_hourly_test')) {
//        wp_schedule_event(time(), 'every_ten_seconds', 'ink_hourly_test');
//    }
    hcwp_run_sql(INK_PATH . '/activate.sql');
    /** if (WP_DEBUG) {hcwp_run_sql(INK_PATH . '/test-data.sql');} * */
}

function hcwp_deactivate() {
    wp_clear_scheduled_hook('ink_daily_import');
//    wp_clear_scheduled_hook('ink_hourly_test');
    hcwp_run_sql(INK_PATH . '/deactivate.sql');
}

function ink_add_admin_menu() {
    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position )
    $icon_url = INK_URL . '/img/slogo.png';
    // Add a new top-level menu (ill-advised):
//        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    add_menu_page("Inksumos", "Inksumos", 'manage_options', 'inksumos-admin-menu', 'Ink\\Main::config', $icon_url);
    // Add a submenu to the custom top-level menu:
    //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function)
    add_submenu_page('inksumos-admin-menu', "Grupos", "Grupos", 'manage_options', 'ink-groups', 'Ink\\Main::groups');
    add_submenu_page('inksumos-admin-menu', "Importar", "Importar", 'manage_options', 'ink-import', 'Ink\\Catalog::formImport');
}

/** === Ajax User functions === * */
//require_once INK_PATH . '/ajax_user.php';
//new InkUsersApp();
/** === Ajax User functions END === * */
/** ----------------------------------------------------------------------- * */
// Hook for adding admin menus
add_action('admin_menu', 'ink_add_admin_menu');
/** Run this functions when the plugins is activated or deactivated * */
register_activation_hook(__FILE__, 'hcwp_activate');
register_deactivation_hook(__FILE__, 'hcwp_deactivate');

//To execute shortcode on widgets remove comment from below
//add_filter('widget_text', 'do_shortcode', 11);
//[myFunction]
//add_shortcode( 'myFunction', 'my_funcion1' );


function ink_validate_add_cart_item($passed, $product_id, $quantity, $variation_id = '', $variations = '') {

    // do your validation, if not met switch $passed to false
    Ink\Catalog::updateProduct($product_id);
    $product = wc_get_product($product_id);
    $stockAvailable = $product->get_stock_quantity();

    if ($stockAvailable < $quantity) {
        $passed = false;
        if ($quantity > 1) {
            wc_add_notice(__('Disculpa, No hay stock suficiente, intenta añadir solo ' . $stockAvailable, 'ink'), 'error');
        } else {
            wc_add_notice(__('Disculpa, se agotó este artículo', 'ink'), 'error');
        }
    }
    return $passed;
}

add_filter('woocommerce_add_to_cart_validation', 'ink_validate_add_cart_item', 10, 5);
