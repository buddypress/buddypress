<?php
function xprofile_add_admin_css() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . '/bp-xprofile/admin/css/admin.dev.css' );
		else
			wp_enqueue_style( 'xprofile-admin-css', BP_PLUGIN_URL . '/bp-xprofile/admin/css/admin.css' );
	}
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'xprofile_add_admin_css' );

function xprofile_add_admin_js() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . '/bp-xprofile/admin/js/admin.dev.js', array( 'jquery', 'jquery-ui-sortable' ) );
		else
			wp_enqueue_script( 'xprofile-admin-js', BP_PLUGIN_URL . '/bp-xprofile/admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ) );
	}
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'xprofile_add_admin_js', 1 );
?>