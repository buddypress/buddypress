<?php
/*
Plugin Name: BuddyPress Support Plugin
Plugin URI: http://buddypress.org/
Description: Modifies bbPress behaviour to provide better support for integration in BuddyPress.
Author: Andy Peatling
Version: 1.0-RC1
Author URI: http://apeatling.wordpress.com/
*/

function for_buddypress_strip_tags( $_post, $post ) {
	
	// Cast to an array
	$_post = (array) $post;
	// Set the URI
	$_post['post_uri'] = get_post_link( $_post['post_id'] );
	// Set readable times
	$_post['post_time_since'] = bb_since( $_post['post_time'] );
	// Set the display names
	$_post['poster_display_name'] = get_user_display_name( $_post['poster_id'] );
	// Remove some sensitive data
	unset(
		$_post['poster_ip'],
		$_post['pingback_queued']
	);

	return $_post; 
}
add_filter( 'bb_xmlrpc_prepare_post', 'for_buddypress_strip_tags', 10, 2 );

function for_buddypress_prepare_topic( $_topic, $topic ) {
	// Cast to an array
	$_topic = (array) $topic;
	// Set the URI
	$_topic['topic_uri'] = get_topic_link( $_topic['topic_id'] );
	// Set readable times
	$_topic['topic_start_time_since'] = bb_since( $_topic['topic_start_time'] );
	$_topic['topic_time_since'] = bb_since( $_topic['topic_time'] );
	// Set the display names
	$_topic['topic_poster_display_name'] = get_user_display_name( $_topic['topic_poster'] );
	$_topic['topic_last_poster_display_name'] = get_user_display_name( $_topic['topic_last_poster'] );

	return $_topic;
}
add_filter( 'bb_xmlrpc_prepare_topic', 'for_buddypress_prepare_topic', 10, 2 );

function for_buddypress_pre_post( $post_text, $post_id, $topic_id ){
	$post_text = stripslashes( stripslashes_deep($post_text) );
	$post_text = html_entity_decode( $post_text, ENT_COMPAT, "UTF-8" );

	return $post_text;
}
add_filter( 'pre_post', 'for_buddypress_pre_post', 10, 3 );

?>
