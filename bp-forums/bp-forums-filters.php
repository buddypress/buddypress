<?php
/* Apply WordPress defined filters */
add_filter( 'bp_the_topic_title', 'wptexturize' );
add_filter( 'bp_the_topic_poster_name', 'wptexturize' );
add_filter( 'bp_the_topic_last_poster_name', 'wptexturize' );
add_filter( 'bp_the_topic_post_content', 'wptexturize' );
add_filter( 'bp_the_topic_post_poster_name', 'wptexturize' );

add_filter( 'bp_the_topic_title', 'convert_smilies' );
add_filter( 'bp_the_topic_latest_post_excerpt', 'convert_smilies' );
add_filter( 'bp_the_topic_post_content', 'convert_smilies' );

add_filter( 'bp_the_topic_title', 'convert_chars' );
add_filter( 'bp_the_topic_latest_post_excerpt', 'convert_chars' );
add_filter( 'bp_the_topic_post_content', 'convert_chars' );

add_filter( 'bp_the_topic_post_content', 'wpautop' );

add_filter( 'bp_the_topic_post_content', 'stripslashes_deep' );
add_filter( 'bp_the_topic_title', 'stripslashes_deep' );
add_filter( 'bp_the_topic_latest_post_excerpt', 'stripslashes_deep' );

/* BuddyPress filters */
add_filter( 'bp_forums_new_post_text', 'bp_forums_filter_encode' );

add_filter( 'bp_the_topic_post_content', 'bp_forums_filter_decode' );
add_filter( 'bp_the_topic_latest_post_excerpt', 'bp_forums_filter_decode' );

function bp_forums_filter_encode( $content ) {
	$content = htmlentities($content);
	$content = str_replace( '&', '/amp/', $content );

	return $content;
}

function bp_forums_filter_decode( $content ) {
	$content = str_replace( '/amp/', '&', $content );
	$content = html_entity_decode($content);
	$content = str_replace( '[', '<', $content );
	$content = str_replace( ']', '>', $content );
	$content = wp_filter_kses( $content );
	
	return $content;
}

?>