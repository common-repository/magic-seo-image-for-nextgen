<?php
/**
 *  Plugin Name: Magic SEO Image For NextGen
 *  Description: A powefull wordpress plugin to bulk SEO nextGen Gallery images
 *  Author: Amin mazrouei
 *  Author URI: http://www.webpooyesh.ir
 *  Version: 1.0
 *  Text Domain: nab
 */

defined( 'ABSPATH' ) or die( 'Sorry,you are not allowd to access this file!' );
const NABTXD = 'nab';

function load_plugin_assets( $hook ) {

	if ( $hook == 'toplevel_page_magic-seo-image-for-nextgen' ) {

		$plugin_dir = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'magic-seo-image', $plugin_dir . 'assets/css/main.css' );
		wp_enqueue_style( 'jquery-ui', $plugin_dir . 'assets/css/jquery-ui.min.css' );

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'auto-complete-script', $plugin_dir . 'assets/js/jquery.googleSuggest.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'GSbootstart', $plugin_dir . 'assets/js/GSbootstart.js', array(), false, true );
		wp_enqueue_script( 'add_tags', $plugin_dir . 'assets/js/add_tags.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'show_tags', $plugin_dir . 'assets/js/show_tags.js', array( 'jquery' ), false, true );
	}
}

add_action( 'admin_enqueue_scripts', 'load_plugin_assets' );

function plugin_init() {
	load_plugin_textdomain( NABTXD, false, 'magic-seo-image-for-nextgen/languages' );
}

add_action( 'init', 'plugin_init' );

if ( is_admin() ) {
	require_once 'OptionPage.php';
}

