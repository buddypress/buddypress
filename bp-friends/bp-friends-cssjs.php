<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp;
	
	if ( !isset($_GET['page']) )
		$_GET['page'] = null;

	if ( $bp['current_component'] == $bp['friends']['slug'] ) {
		echo '
			<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-friends/js/general.js" type="text/javascript"></script>';
	}
}
add_action( 'wp_head', 'friends_add_js' );

?>