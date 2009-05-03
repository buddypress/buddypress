<?php
/**
 * bp_core_add_js()
 *
 * Add the JS required by the core, as well as shared JS used by other components.
 * [TODO] This needs to use wp_enqueue_script()
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_js() {
	echo '<script type="text/javascript">var ajaxurl = "' . get_option('siteurl') . '/wp-admin/admin-ajax.php";</script>';
	echo "<script type='text/javascript' src='" . get_option('siteurl') . "/wp-includes/js/jquery/jquery.js?ver=1.2.3'></script>";
	echo "
		<script type='text/javascript' src='" . get_option('siteurl') . "/wp-content/mu-plugins/bp-core/js/jquery/jquery.livequery.pack.js'></script>";

	echo '<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-core/js/general.js" type="text/javascript"></script>';
}
add_action( 'wp_head', 'bp_core_add_js' );

/**
 * bp_core_add_css()
 *
 * Add the CSS required by all BP components, regardless of the current theme.
 * 
 * @package BuddyPress Core
 * @uses get_option() Selects a site setting from the DB.
 */
function bp_core_add_css() {
	if ( bp_core_user_has_home() && is_user_logged_in() )
		echo '<link rel="stylesheet" href="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-core/css/admin-bar.css" type="text/css" />';
}
add_action( 'wp_head', 'bp_core_add_css' );

function bp_core_add_admin_js() {
	if ( strpos( $_GET['page'], 'bp-core' ) !== false ) {
		wp_enqueue_script( 'bp-account-admin-js', get_option('siteurl') . '/wp-content/mu-plugins/bp-core/js/account-admin.js' );
	}
}
add_action( 'admin_menu', 'bp_core_add_admin_js' );

function bp_core_enqueue_admin_js() {
	if ( strpos( $_GET['page'], 'bp-core/admin-mods' ) !== false ) {
		wp_enqueue_script('password-strength-meter');
	}

	if ( strpos( $_GET['page'], 'bp-core/homebase-creation' ) !== false ) {
		add_action( 'admin_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'admin_menu', 'bp_core_enqueue_admin_js' );

function bp_core_enqueue_admin_css() {
	if ( strpos( $_GET['page'], 'bp-core/homebase-creation' ) !== false ) {
		wp_enqueue_style( 'bp-core-home-base-css', get_option('siteurl') . '/wp-content/mu-plugins/bp-core/css/home-base.css' );
	}
}
add_action( 'admin_menu', 'bp_core_enqueue_admin_css' );