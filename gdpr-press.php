<?php
/**
 * Plugin Name: GDPRess
 * Plugin URI: https://wordpress.org/plugins/gdpr-press/
 * Description: Easily eliminate external requests with GDPRess.
 * Version: 1.2.3
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: gdpr-press
 */

defined( 'ABSPATH' ) || exit;

define( 'GDPRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GDPRESS_PLUGIN_FILE', __FILE__ );
define( 'GDPRESS_PLUGIN_BASENAME', plugin_basename( GDPRESS_PLUGIN_FILE ) );
define( 'GDPRESS_STATIC_VERSION', '1.2.0' );
define( 'GDPRESS_DB_VERSION', '1.0.2' );

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
require_once GDPRESS_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * All systems GO!!!
 *
 * @return Plugin
 */
$gdpress = new GDPRess\Plugin();
