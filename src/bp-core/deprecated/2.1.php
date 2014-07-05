<?php
/**
 * Deprecated functions
 *
 * @package BuddyPress
 * @subpackage Core
 * @deprecated 2.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register (not enqueue) scripts that used to be used by BuddyPress.
 *
 * @since BuddyPress (2.1.0)
 */
function bp_core_register_deprecated_scripts() {
	$ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.js' : '.min.js';
	$url = buddypress()->plugin_url . 'bp-core/deprecated/js/';
	
	$scripts = apply_filters( 'bp_core_register_deprecated_scripts', array(
		// Core
		'bp-jquery-scroll-to' => 'jquery-scroll-to',

		// Messages
		'bp-jquery-autocomplete'    => 'autocomplete/jquery.autocomplete',
		'bp-jquery-autocomplete-fb' => 'autocomplete/jquery.autocompletefb',
		'bp-jquery-bgiframe'        => 'autocomplete/jquery.bgiframe',
		'bp-jquery-dimensions'      => 'autocomplete/jquery.dimensions',
	) );

	foreach ( $scripts as $id => $file ) {
		wp_register_script( $id, $url . $file . $ext, array( 'jquery' ), bp_get_version(), true );
	}
}
add_action( 'bp_enqueue_scripts', 'bp_core_register_deprecated_scripts', 1 );

/**
 * Register (not enqueue) styles that used to be used by BuddyPress.
 *
 * @since BuddyPress (2.1.0)
 */
function bp_core_register_deprecated_styles() {
	$ext = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';
	$url = buddypress()->plugin_url . 'bp-core/deprecated/css/';
	
	$styles = apply_filters( 'bp_core_register_deprecated_styles', array(
		// Messages
		'bp-messages-autocomplete' => 'autocomplete/jquery.autocompletefb',
	) );

	foreach ( $styles as $id => $file ) {
		wp_register_style( $id, $url . $file . $ext, array(), bp_get_version() );
	}
}
add_action( 'bp_enqueue_scripts', 'bp_core_register_deprecated_styles', 1 );