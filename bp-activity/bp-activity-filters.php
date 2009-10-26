<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_activity_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_content', 'bp_activity_filter_kses', 1 );

add_filter( 'bp_get_activity_content', 'force_balance_tags' );
add_filter( 'bp_get_activity_content', 'wptexturize' );
add_filter( 'bp_get_activity_content', 'convert_smilies' );
add_filter( 'bp_get_activity_content', 'convert_chars' );
add_filter( 'bp_get_activity_content', 'wpautop' );
add_filter( 'bp_get_activity_content', 'make_clickable' );
add_filter( 'bp_get_activity_content', 'stripslashes_deep' );

function bp_activity_filter_kses( $content ) {
	global $allowedtags;
	
	$activity_allowedtags = $allowedtags;
	$activity_allowedtags['span'] = array();
	$activity_allowedtags['span']['class'] = array();
	$activity_allowedtags['a']['class'] = array();
	$activity_allowedtags['img'] = array();	
	$activity_allowedtags['img']['src'] = array();
	$activity_allowedtags['img']['alt'] = array();
	$activity_allowedtags['img']['class'] = array();
	$activity_allowedtags['img']['width'] = array();
	$activity_allowedtags['img']['height'] = array();
	$activity_allowedtags['img']['class'] = array();
	$activity_allowedtags['img']['id'] = array();

	return wp_kses( $content, $activity_allowedtags );
}


?>