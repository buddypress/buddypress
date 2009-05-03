<?php

function xprofile_add_signup_css() {
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' )
		echo '<link rel="stylesheet" href="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/css/signup.css" type="text/css" />';
}
add_action( 'wp_head', 'xprofile_add_signup_css' );


function xprofile_add_admin_css() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false ) {
		echo '<link rel="stylesheet" href="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/css/admin.css" type="text/css" />';
	}
}
add_action( 'admin_head', 'xprofile_add_admin_css' );


function xprofile_add_admin_js() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false ) {
		wp_enqueue_script( 'bp-xprofile-admin-js', get_option('siteurl') . "/wp-content/mu-plugins/bp-xprofile/js/admin.js" );
		add_action( 'admin_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'admin_menu', 'xprofile_add_admin_js' );

?>
