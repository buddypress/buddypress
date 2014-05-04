<?php

/**
 * BuddyPress XProfile CSS and JS
 *
 * @package BuddyPress
 * @subpackage XProfileScripts
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue the CSS for XProfile admin styling
 *
 * @since BuddyPress (1.1)
 */
function xprofile_add_admin_css() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'xprofile-admin-css', buddypress()->plugin_url . "bp-xprofile/admin/css/admin{$min}.css", array(), bp_get_version() );
	}
}
add_action( 'admin_enqueue_scripts', 'xprofile_add_admin_css' );

/**
 * Enqueue the jQuery libraries for handling drag/drop/sort
 *
 * @since BuddyPress (1.5)
 */
function xprofile_add_admin_js() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core'      );
		wp_enqueue_script( 'jquery-ui-tabs'      );
		wp_enqueue_script( 'jquery-ui-mouse'     );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable'  );

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'xprofile-admin-js', buddypress()->plugin_url . "bp-xprofile/admin/js/admin{$min}.js", array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );
	}
}
add_action( 'admin_enqueue_scripts', 'xprofile_add_admin_js', 1 );
