<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp;

	if ( $bp['current_component'] == $bp['friends']['slug'] )
		wp_enqueue_script( 'bp-friends-js', site_url() . '/wp-content/mu-plugins/bp-friends/js/general.js' );
}
add_action( 'template_redirect', 'friends_add_js' );

?>