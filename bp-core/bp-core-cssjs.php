<?php
function core_add_js() {
	echo '<script type="text/javascript">var ajaxurl = "' . get_option('siteurl') . '/wp-admin/admin-ajax.php";</script>';
	echo "<script type='text/javascript' src='" . get_option('siteurl') . "/wp-includes/js/jquery/jquery.js?ver=1.2.3'></script>";
	echo "
		<script type='text/javascript' src='" . get_option('siteurl') . "/wp-content/mu-plugins/bp-core/js/jquery/jquery.livequery.pack.js'></script>";

	echo '<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-core/js/general.js" type="text/javascript"></script>';
}
add_action( 'wp_head', 'core_add_js' );
//add_action( 'admin_menu', 'core_add_js' );
?>