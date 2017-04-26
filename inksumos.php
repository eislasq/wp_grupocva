<?php

/*
  Plugin Name: Inksumos
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

function hcwp_activate() {
    hcwp_run_sql(INK_PATH . '/activate.sql');
    /** if (WP_DEBUG) {hcwp_run_sql(INK_PATH . '/test-data.sql');} * */
}

function hcwp_deactivate() {
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
    add_submenu_page('inksumos-admin-menu', "Test Import", "Test Import", 'manage_options', 'ink-test-import', 'Ink\\Catalog::test');
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