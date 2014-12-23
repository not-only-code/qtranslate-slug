<?php
/*
Plugin Name: qTranslate slug
Plugin URI: http://not-only-code.github.com/qtranslate-slug/
Description: Allows to define a slug for each language and some qTranslate bug fixes
Version: 1.1.8
Author: Carlos Sanz Garcia
Author URI: http://github.com/not-only-code
*/


////////////////////////////////////////////////////////////////////////////////////////

if ( !function_exists('_debug') ):
function _debug( $message ) {
	   
	if ( WP_DEBUG === true ):
		 
		if ( is_array( $message ) || is_object( $message ) ) {
			
			error_log( print_r( $message, true ) );
			
		} else {
			
			error_log( $message );
		}
			 
	 endif;
}
endif;

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Includes
 *
 * @since 1.1.8
 */
include_once(dirname(__FILE__).'/includes/class-qtranslate-slug-widget.php');
include_once(dirname(__FILE__).'/includes/class-qtranslate-slug.php');

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Define Constants
 *
 * @since 1.0
 */
if (!defined("QTS_VERSION")) 		    define("QTS_VERSION", '1.1.8');
if (!defined("QTS_PREFIX")) 		    define("QTS_PREFIX", '_qts_');
if (!defined("QTS_PAGE_BASENAME")) 		define('QTS_PAGE_BASENAME', 'qtranslate-slug-settings');
if (!defined("QTS_OPTIONS_NAME")) 		define("QTS_OPTIONS_NAME", 'qts_options');
if (!defined("PHP_EOL"))				define("PHP_EOL", "\r\n");

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Includes
 *
 * @since 1.0
 */
if ( is_admin() && !QtranslateSlug::block_activate() ) { // setting options page
	include_once(dirname(__FILE__).'/includes/qtranslate-slug-settings.php'); 
}

include_once(dirname(__FILE__).'/includes/termmeta-core.php'); // termmeta install and core functions

////////////////////////////////////////////////////////////////////////////////////////


	
/**
 * Init the plugin
 *
 * @since 1.0
 */	
global $qtranslate_slug;

$qtranslate_slug = new QtranslateSlug();

// plugin activation
register_activation_hook( __FILE__, array($qtranslate_slug, 'install') );

// plugin deactivation
register_deactivation_hook( __FILE__, array($qtranslate_slug, 'deactivate') );

// plugin uninstall
register_uninstall_hook( __FILE__, 'qts_uninstall' );

// plugin init
add_action('plugins_loaded', array($qtranslate_slug, 'init') );

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Language Selector Code for templating
 *
 * @package Qtranslate Slug
 * @subpackage Core 
 * @since 1.0
 */
function qts_language_menu ($type = "text", $args = array()) {
	global $qtranslate_slug;
	
	$qtranslate_slug->language_menu($type, $args);
}



/**
 * Adds support for old plugin function
 * 
 * @package Qtranslate Slug
 * @subpackage Core
 * @since 1.1.5
 */
function qTranslateSlug_getSelfUrl ($lang = false) { // bad naming, I'll keep just in case
	return qts_get_url($lang);
}

function qts_get_url($lang = false) {
	global $qtranslate_slug;

	return $qtranslate_slug->get_current_url($lang);
}



/**
 * Add a "Settings" link to the plugins.php page for Qtranslate Slug
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 * @return calls qts_show_msg()
 */
function qts_add_settings_link( $links, $file ) {
	
	if (QtranslateSlug::block_activate()) return $links;
	
	$this_plugin = plugin_basename( __FILE__ );
	if( $file == $this_plugin ) {
		$settings_link = "<a href=\"options-general.php?page=" . QTS_PAGE_BASENAME . "\">" . __( 'Settings' ) . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter( 'plugin_action_links', 'qts_add_settings_link', 10, 2 );



/**
 * Delete plugin stored data ( options, termmeta table and postmeta data ) ################################################ TODO: test this function
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 */
function qts_uninstall() {
	global $q_config, $wpdb;
	
	// options
	delete_option(QTS_OPTIONS_NAME);
	delete_option('qts_version');
	
	// delete termmeta table
	$wpdb->query("DROP TABLE IF EXISTS $wpdb->termmeta");
	
	// delete postmeta data
	$meta_keys = array();
	foreach ($q_config['enabled_languages'] as $lang) $meta_keys[] = sprintf("_qts_slug_%s", $lang);
	$meta_keys = "'". implode( "','", $meta_keys ) . "'";
	$wpdb->query("DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)");	
}
