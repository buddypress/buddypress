<?php

function messages_add_js() {
	global $bp;

	if ( $bp->current_component == $bp->messages->slug )
		wp_enqueue_script( 'bp-messages-js', site_url( MUPLUGINDIR . '/bp-messages/js/general.php' ) );

	// Include the autocomplete JS for composing a message.
	if ( $bp->current_component == $bp->messages->slug && $bp->current_action == 'compose') {
		wp_enqueue_script( 'bp-jquery-autocomplete', site_url( MUPLUGINDIR . '/bp-messages/js/autocomplete/jquery.autocomplete.js' ), 'jquery' );
		wp_enqueue_script( 'bp-jquery-autocomplete-fb', site_url( MUPLUGINDIR . '/bp-messages/js/autocomplete/jquery.autocompletefb.js' ), 'jquery' );
		wp_enqueue_script( 'bp-jquery-bgiframe', site_url( MUPLUGINDIR . '/bp-messages/js/autocomplete/jquery.bgiframe.min.js' ), 'jquery' );
		wp_enqueue_script( 'bp-jquery-dimensions', site_url( MUPLUGINDIR . '/bp-messages/js/autocomplete/jquery.dimensions.js' ), 'jquery' );	
		wp_enqueue_script( 'bp-autocomplete-init', site_url( MUPLUGINDIR . '/bp-messages/js/autocomplete/init.php' ), 'jquery' );	

	}

}
add_action( 'template_redirect', 'messages_add_js', 1 );

function messages_add_css() {
	global $bp;
	
	if ( $bp->current_component == $bp->messages->slug && $bp->current_action == 'compose') {
		wp_enqueue_style( 'bp-messages-autocomplete', site_url( MUPLUGINDIR . '/bp-messages/css/autocomplete/jquery.autocompletefb.css' ) );	
		wp_print_styles();
	}
}
add_action( 'wp_head', 'messages_add_css' );


function messages_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-messages-structure', site_url( MUPLUGINDIR . '/bp-messages/css/structure.css' ) );	
}
add_action( 'bp_styles', 'messages_add_structure_css' );


?>