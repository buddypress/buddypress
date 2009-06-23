<?php

function bp_status_ajax_show_form() {
	load_template( TEMPLATEPATH . '/status/post-form.php' );
}
add_action( 'wp_ajax_status_show_form', 'bp_status_ajax_show_form' );

function bp_status_ajax_show_status() {
	$args = apply_filters( 'bp_status_ajax_show_status_args', $args );
 	bp_the_status( $args );
}
add_action( 'wp_ajax_status_show_status', 'bp_status_ajax_show_status' );

function bp_status_ajax_new_status() {
	global $bp;
	
	if ( !check_ajax_referer( 'bp_status_add_status' ) )
		return false;
		
	if ( bp_status_add_status( $bp->loggedin_user->id, $_POST['status-update-input'] ) )
		echo "1";
	else
		echo "-1";
}
add_action( 'wp_ajax_status_new_status', 'bp_status_ajax_new_status' );

function bp_status_ajax_clear_status( $new_text = false ) {
	global $bp;
	
	bp_status_clear_status( $bp->loggedin_user->id );
	
	$args = apply_filters( 'bp_status_ajax_show_status_args', $args );
 	bp_the_status( $args );
}
add_action( 'wp_ajax_status_clear_status', 'bp_status_ajax_clear_status' );

?>