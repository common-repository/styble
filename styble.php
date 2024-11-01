<?php
/**
 * Plugin name: Styble
 * Plugin URI:  https://styble.com/
 * Description: Styble is a WordPress Gutenberg Blocks plugin to shape your website in your way without coding knowledge.
 * Author:      ShapedPlugin LLC
 * Author URI:  https://shapedplugin.com/
 * Version:     1.3.4
 * Text Domain: styble
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Domain Path: /languages/
 *
 * @package Styble
 * */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'STYBLE_VERSION' ) ) {
	define( 'STYBLE_VERSION', '1.3.4' );
}
if ( ! defined( 'STYBLE_DIR' ) ) {
	define( 'STYBLE_DIR', __DIR__ );
}
if ( ! defined( 'STYBLE_PLUGIN_DIR' ) ) {
	define( 'STYBLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'STYBLE_PLUGIN_URL' ) ) {
	define( 'STYBLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! function_exists( 'run_styble' ) ) {
	/**
	 * Run styble plugin.
	 *
	 * @return void
	 */
	function run_styble() {
		require STYBLE_PLUGIN_DIR . 'includes/styble.php';
		$styble = new ShapedPlugin\Styble\Styble();
		$styble::instance();
	}
}

run_styble();
