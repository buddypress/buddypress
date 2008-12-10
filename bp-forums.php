<?php

/*
BP-Forums component is based on the bbPress Live 
plugin created by Sam Bauers - http://unlettered.org/
http://wordpress.org/extend/plugins/bbpress-live/
*/

require_once( 'bp-core.php' );

define ( 'BP_FORUMS_VERSION', '0.1.1' );

include_once( 'bp-forums/bp-forums-admin.php' );
include_once( 'bp-forums/bp-forums-bbpress-live.php' );
include_once( 'bp-forums/bp-forums-templatetags.php' );
include_once( 'bp-forums/bp-forums-filters.php' );

function bp_forums_setup() {
	global $bp, $bbpress_live;

	if ( get_usermeta( $bp['loggedin_userid'], 'bb_capabilities' ) == '' )
		bp_forums_make_user_active_member( $bp['loggedin_userid'] );
	
	if ( $bp['current_component'] == $bp['groups']['slug'] || $_GET['page'] == 'bp_forums_settings' ) { 
		if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) return;
		if ( defined('DOING_AJAX') && DOING_AJAX ) return;
		
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
	
		$bbpress_live = new bbPress_Live();
	}
}
add_action( 'wp', 'bp_forums_setup', 3 );
add_action( 'admin_head', 'bp_forums_setup', 3 );

function bp_forums_get_forum( $parent = 0, $depth = 0 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $forum = $bbpress_live->get_forums( $parent, $depth ) ) {
		do_action( 'bp_forums_get_forum', $forum );
		return $forum;
	}
	
	return false;
}

function bp_forums_get_topics( $forum_id = 0, $number = 0, $page = 1 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $topics = $bbpress_live->get_topics( $forum_id, $number, $page ) ) {
		do_action( 'bp_forums_get_topics', $topics );
		return $topics;
	}
	
	return false;
}

function bp_forums_get_topic_details( $topic_id = 0 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $topic = $bbpress_live->get_topic_details( $topic_id ) ) {
		do_action( 'bp_forums_get_topic_details', $topic );
		return $topic;
	}
	
	return false;
}

function bp_forums_get_posts( $topic_id = 0, $number = 0, $page = 1 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $posts = $bbpress_live->get_posts( $topic_id, $number, $page ) ) {
		do_action( 'bp_forums_get_posts', $posts );
		return $posts;
	}

	return false;
}

function bp_forums_get_post( $post_id = 0 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $post = $bbpress_live->get_post( $post_id ) ) {
		do_action( 'bp_forums_get_post', $post );
		return $post;
	}

	return false;
}

function bp_forums_new_forum( $name = '', $desc = '', $parent = 0, $order = 0, $is_category = false ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	if ( $forum = $bbpress_live->new_forum( $name, $desc, $parent, $order, $is_category ) ) {
		do_action( 'bp_forums_new_forum', $forum );
		return $forum;
	}
	
	return false;
}

function bp_forums_new_topic( $title = '', $topic_text = '', $topic_tags = '', $forum_id = 0 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	$topic_text = apply_filters( 'bp_forums_new_post_text', $topic_text );
	$title = apply_filters( 'bp_forums_new_post_title', $title );
	$topic_tags = apply_filters( 'bp_forums_new_post_tags', $topic_tags );
	
	if ( $topic = $bbpress_live->new_topic( $title, $topic_text, $topic_tags, (int)$forum_id ) ) {
		do_action( 'bp_forums_new_topic', $topic );
		return $topic; 
	}
	
	return false;
}

function bp_forums_new_post( $post_text = '', $topic_id = 0 ) {
	global $bbpress_live;
	
	if ( !is_object( $bbpress_live ) ) {
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		$bbpress_live = new bbPress_Live();
	}
	
	$post_text = apply_filters( 'bp_forums_new_post_text', $post_text );
	
	if ( $post = $bbpress_live->new_post( $post_text, (int)$topic_id ) ) {
		do_action( 'bp_forums_new_post', $post );
		return $post;
	}
	
	return false;
}

function bp_forums_make_user_active_member( $user_id ) {
	update_usermeta( $user_id, 'bb_capabilities', array( 'member' => true ) );
}
add_action( 'wpmu_new_user', 'bp_forums_make_user_active_member' );

function bp_forums_get_keymaster() {
	global $wpdb;
	
	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}usermeta WHERE meta_key = 'bb_capabilities' AND meta_value LIKE '%%keymaster%%'" ) );
	
	return get_userdata( $user_id );
}


?>
