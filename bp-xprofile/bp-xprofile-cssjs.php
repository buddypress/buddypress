<?php

function xprofile_add_js() {
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' )
		wp_enqueue_script( 'jquery' );
}
add_action( 'wp', 'xprofile_add_js' );

function xprofile_add_css() {
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' )
		wp_enqueue_style( 'bp-xprofile-signup', WPMU_PLUGIN_URL . '/bp-xprofile/css/signup.css' );	
	
	wp_print_styles();
}
add_action( 'wp_head', 'xprofile_add_css' );

function xprofile_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for xprofile pages */
	wp_enqueue_style( 'bp-xprofile-structure', WPMU_PLUGIN_URL . '/bp-xprofile/css/structure.css' );	
}
add_action( 'bp_styles', 'xprofile_add_structure_css' );

function xprofile_add_admin_css() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false ) {
		echo '<link rel="stylesheet" href="' . WPMU_PLUGIN_URL . '/bp-xprofile/css/admin.css' . '" type="text/css" />';
	}
}
add_action( 'admin_head', 'xprofile_add_admin_css' );

function xprofile_add_admin_js() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false )
		echo '<script type="text/javascript" src="' . WPMU_PLUGIN_URL . '/bp-xprofile/js/admin.js' . '"></script>';
}
add_action( 'admin_head', 'xprofile_add_admin_js' );

function xprofile_add_cropper_js() {
	global $bp;

	if ( $_SERVER['SCRIPT_NAME'] == '/wp-activate.php' || $bp->current_component == ACTIVATION_SLUG || $bp->current_action == 'change-avatar' ) {
		//wp_enqueue_script('jquery');
		//wp_enqueue_script('prototype');
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('cropper');
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
	}
	
	if ( isset($_GET['page']) && $_GET['page'] == 'bp-xprofile.php' ) {
		add_action( 'admin_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'activate_header', 'xprofile_add_cropper_js' );
add_action( 'template_redirect', 'xprofile_add_cropper_js', 1 );
add_action( 'admin_menu', 'xprofile_add_cropper_js' );

?>
