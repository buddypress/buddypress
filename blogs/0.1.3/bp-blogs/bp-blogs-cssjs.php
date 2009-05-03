<?php

function bp_blogs_add_admin_css() {
	global $bp, $wpdb;

	if ( $wpdb->blogid == $bp['current_homebase_id'] ) {
		if ( strpos( $_GET['page'], 'bp-blogs' ) !== false ) {
			wp_enqueue_style('bp-blogs-admin-css', site_url() . '/wp-content/mu-plugins/bp-blogs/admin-tabs/admin.css'); 
		}
	}
}
add_action( "admin_menu", 'bp_blogs_add_admin_css' );