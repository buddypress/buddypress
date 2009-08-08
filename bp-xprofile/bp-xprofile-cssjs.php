<?php

function xprofile_add_admin_css() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false ) {
		echo '<link rel="stylesheet" href="' . BP_PLUGIN_URL . '/bp-xprofile/css/admin.css' . '" type="text/css" />';
	}
}
add_action( 'admin_head', 'xprofile_add_admin_css' );

function xprofile_add_admin_js() {
	if ( strpos( $_GET['page'], 'xprofile' ) !== false )
		echo '<script type="text/javascript" src="' . BP_PLUGIN_URL . '/bp-xprofile/js/admin.js' . '"></script>';
}
add_action( 'admin_head', 'xprofile_add_admin_js' );

?>
