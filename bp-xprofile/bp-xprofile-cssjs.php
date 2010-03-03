<?php

function xprofile_add_admin_css() {
	wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . '/bp-xprofile/admin/css/admin.css' );
}
add_action( 'admin_menu', 'xprofile_add_admin_css' );

function xprofile_add_admin_js() {
	if ( strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . '/bp-xprofile/admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ) );
	}
}
add_action( 'admin_menu', 'xprofile_add_admin_js', 1 );

?>