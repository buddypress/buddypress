<?php

function xprofile_add_admin_css() {
	// If this is WP 3.1+ and multisite is enabled, only load on the Network Admin
	if ( is_multisite() && function_exists( 'is_network_admin' ) && ! is_network_admin()  )
		return false;

	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false )
		wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . '/bp-xprofile/admin/css/admin.css' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'xprofile_add_admin_css' );

function xprofile_add_admin_js() {
	// If this is WP 3.1+ and multisite is enabled, only load on the Network Admin
	if ( is_multisite() && function_exists( 'is_network_admin' ) && ! is_network_admin()  )
		return false;

	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( array( "jquery-ui-sortable" ) );
		wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . '/bp-xprofile/admin/js/admin.js', array( 'jquery' ) );
	}
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'xprofile_add_admin_js', 1 );

?>