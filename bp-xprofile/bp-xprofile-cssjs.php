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
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . 'bp-xprofile/admin/css/admin.dev.css', array(), bp_get_version() );
		} else {
			wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . 'bp-xprofile/admin/css/admin.css',     array(), bp_get_version() );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'xprofile_add_admin_css' );

/**
 * Enqueue the jQuery libraries for handling drag/drop/sort
 *
 * @since BuddyPres (1.5)
 */
function xprofile_add_admin_js() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core'      );
		wp_enqueue_script( 'jquery-ui-tabs'      );
		wp_enqueue_script( 'jquery-ui-mouse'     );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable'  );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . 'bp-xprofile/admin/js/admin.dev.js', array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );
		} else {
			wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . 'bp-xprofile/admin/js/admin.js',     array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'xprofile_add_admin_js', 1 );

?>
