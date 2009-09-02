<?php

/* Apply WordPress defined filters */
add_filter( 'bp_forums_bbconfig_location', 'wp_filter_kses', 1 );
add_filter( 'bp_forums_bbconfig_location', 'attribute_escape', 1 );

add_filter( 'bp_get_the_topic_title', 'wptexturize' );
add_filter( 'bp_get_the_topic_poster_name', 'wptexturize' );
add_filter( 'bp_get_the_topic_last_poster_name', 'wptexturize' );
add_filter( 'bp_get_the_topic_post_content', 'wptexturize' );
add_filter( 'bp_get_the_topic_post_poster_name', 'wptexturize' );

add_filter( 'bp_get_the_topic_title', 'convert_smilies' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'convert_smilies' );
add_filter( 'bp_get_the_topic_post_content', 'convert_smilies' );

add_filter( 'bp_get_the_topic_title', 'convert_chars' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'convert_chars' );
add_filter( 'bp_get_the_topic_post_content', 'convert_chars' );

add_filter( 'bp_get_the_topic_post_content', 'wpautop' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'wpautop' );

add_filter( 'bp_get_the_topic_post_content', 'stripslashes_deep' );
add_filter( 'bp_get_the_topic_title', 'stripslashes_deep' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'stripslashes_deep' );

add_filter( 'bp_get_the_topic_post_content', 'make_clickable' );

function bp_forums_add_allowed_tags( $allowedtags ) {
	$allowedtags['p'] = array();
	$allowedtags['br'] = array();
	
	return $allowedtags;
}
add_filter( 'edit_allowedtags', 'bp_forums_add_allowed_tags' );

?>