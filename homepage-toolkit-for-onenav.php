<?php
/**
 * Plugin Name:       YWDJDH Homepage Toolkit for OneNav
 * Plugin URI:        https://ywdjdh.com/homepage-toolkit-for-onenav
 * Description:       Adds configurable dropdown menus and an optional desktop dock to the OneNav theme homepage.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            一网打尽导航
 * Author URI:        https://ywdjdh.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ywdjdh-homepage-toolkit-for-onenav
 *
 * @package Homepage_Toolkit_For_OneNav
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HTFO_VERSION', '1.0.0' );
define( 'HTFO_FILE', __FILE__ );
define( 'HTFO_DIR', plugin_dir_path( __FILE__ ) );
define( 'HTFO_URL', plugin_dir_url( __FILE__ ) );

require_once HTFO_DIR . 'includes/class-homepage-toolkit-for-onenav.php';

HTFO_Plugin::get_instance();
