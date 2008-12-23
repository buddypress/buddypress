<?php

function bp_blogs_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-blogs-structure', site_url( MUPLUGINDIR . '/bp-blogs/css/structure.css' ) );	
}
add_action( 'bp_styles', 'bp_blogs_add_structure_css' );


?>
