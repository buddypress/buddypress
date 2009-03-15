<?php
/**************************************************************************
 groups_add_js()
  
 Inserts the Javascript needed for managing groups.
 **************************************************************************/	

function groups_add_js() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug )
		wp_enqueue_script( 'bp-groups-js', WPMU_PLUGIN_URL . '/bp-groups/js/general.js' );
}
add_action( 'template_redirect', 'groups_add_js', 1 );

function groups_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-groups-structure', WPMU_PLUGIN_URL . '/bp-groups/css/structure.css' );	
}
add_action( 'bp_styles', 'groups_add_structure_css' );

function groups_add_cropper_js() {
	global $bp, $create_group_step;
	
	if ( 3 == (int)$create_group_step || ( $bp->current_component == $bp->groups->slug && $bp->action_variables[0] == 'group-avatar' ) ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('prototype');
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('cropper');
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
	}
}
add_action( 'template_redirect', 'groups_add_cropper_js', 1 );


?>