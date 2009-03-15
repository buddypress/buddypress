<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp;

	if ( $bp->current_component == $bp->friends->slug )
		wp_enqueue_script( 'bp-friends-js', WPMU_PLUGIN_URL . '/bp-friends/js/general.js' );
}
add_action( 'template_redirect', 'friends_add_js', 1 );

function friends_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-friends-structure', WPMU_PLUGIN_URL . '/bp-friends/css/structure.css' );	
}
add_action( 'bp_styles', 'friends_add_structure_css' );

?>