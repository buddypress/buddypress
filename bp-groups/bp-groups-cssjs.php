<?php
/**************************************************************************
 groups_add_js()
  
 Inserts the Javascript needed for managing groups.
 **************************************************************************/	

function groups_add_js() {
	global $bp;

	if ( $bp['current_component'] == $bp['groups']['slug'] )
		wp_enqueue_script( 'bp-groups-js', site_url() . '/wp-content/mu-plugins/bp-groups/js/general.js' );
}
add_action( 'template_redirect', 'groups_add_js' );

function groups_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-groups-structure', site_url() . '/wp-content/mu-plugins/bp-groups/css/structure.css' );	
}
add_action( 'bp_styles', 'groups_add_structure_css' );

function groups_add_cropper_js() {
	global $create_group_step;
	
	if ( $create_group_step == '3' ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('prototype');
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('cropper');
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'template_redirect', 'groups_add_cropper_js' );


?>