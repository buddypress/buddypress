<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp_friends_image_base;
	global $current_action, $current_component;
	global $bp_friends_slug;

	if ( strpos( $_GET['page'], 'friends' ) !== false || $current_component == $bp_friends_slug ) {
		echo '
			<script type="text/javascript">var ajaxurl = "' . get_option('siteurl') . '/wp-admin/admin-ajax.php";</script>';
		
		echo "
			<script type='text/javascript' src='" . get_option('siteurl') . "/wp-includes/js/jquery/jquery.js?ver=1.2.3'></script> 
			<script type='text/javascript' src='" . get_option('siteurl') . "/wp-content/mu-plugins/bp-core/js/jquery/jquery.livequery.pack.js'></script>";
		
		echo '
			<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-friends/js/general.js" type="text/javascript"></script>';
	}
}
add_action( 'wp_head', 'friends_add_js' );
add_action( 'admin_menu', 'friends_add_js' );

/**************************************************************************
 add_css()
  
 Inserts the CSS needed to style the friends pages.
 **************************************************************************/	

function friends_add_css()
{
	?>
	
	<?php
}

?>