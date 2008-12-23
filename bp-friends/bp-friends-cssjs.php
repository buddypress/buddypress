<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp;

	if ( $bp['current_component'] == $bp['friends']['slug'] )
		wp_enqueue_script( 'bp-friends-js', site_url( MUPLUGINDIR . '/bp-friends/js/general.js' ) );
}
add_action( 'template_redirect', 'friends_add_js' );

function friends_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-friends-structure', site_url( MUPLUGINDIR . '/bp-friends/css/structure.css' ) );	
}
add_action( 'bp_styles', 'friends_add_structure_css' );

?>