<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_activity_content', 'wp_filter_kses', 1 );

add_filter( 'bp_get_activity_content', 'wptexturize' );

add_filter( 'bp_get_activity_content', 'convert_smilies' );

add_filter( 'bp_get_activity_content', 'convert_chars' );

add_filter( 'bp_get_activity_content', 'wpautop' );

add_filter( 'bp_get_activity_content', 'make_clickable' );

add_filter( 'bp_get_activity_content', 'stripslashes_deep' );

function bp_activity_add_allowed_tags( $allowedtags ) {
	$allowedtags['span'] = array();
	$allowedtags['span']['class'] = array();
	return $allowedtags;
}
add_filter( 'edit_allowedtags', 'bp_activity_add_allowed_tags', 1 );

?>