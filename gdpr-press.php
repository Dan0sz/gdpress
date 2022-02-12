<?php
defined('ABSPATH') || exit;
/**
 * Plugin Name: GDPR Press
 * Plugin URI: https://wordpress.org/plugins/gdpr-press/
 * Description: Easily eliminate external requests with GDPRess.
 * Version: 1.0.2
 * Author: Daan from FFW.Press
 * Author URI: https://ffw.press
 * License: GPL2v2 or later
 * Text Domain: gdpr-press
 */

define('GDPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GDPRESS_PLUGIN_FILE', __FILE__);
define('GDPRESS_PLUGIN_BASENAME', plugin_basename(GDPRESS_PLUGIN_FILE));
define('GDPRESS_STATIC_VERSION', '1.0.2');
define('GDPRESS_DB_VERSION', '1.0.2');

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
function gdpress_autoload($class)
{
    $path = explode('_', $class);

    if ($path[0] != 'Gdpress') {
        return;
    }

    if (!class_exists('FFWP_Autoloader')) {
        require_once(GDPRESS_PLUGIN_DIR . 'ffwp-autoload.php');
    }

    $autoload = new FFWP_Autoloader($class);

    return include GDPRESS_PLUGIN_DIR . 'includes/' . $autoload->load();
}

spl_autoload_register('gdpress_autoload');

/**
 * All systems GO!!!
 *
 * @return Gdpress
 */
function gdpr_press_init()
{
    static $gdpress = null;

    if ($gdpress === null) {
        $gdpress = new Gdpress();
    }

    return $gdpress;
}

gdpr_press_init();
