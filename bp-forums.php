<?php

/* Define the parent forum ID */
if ( !defined( 'BP_FORUMS_PARENT_FORUM_ID' ) )
	define ( 'BP_FORUMS_PARENT_FORUM_ID', 1 );

require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-bbpress.php' );
require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-filters.php' );
require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-cssjs.php' );

function bp_forums_setup() {
	global $bp;
	
	$bp->forums->image_base = BP_PLUGIN_URL . '/bp-forums/images';
	$bp->forums->bbconfig = get_site_option( 'bb-config-location' );
}
add_action( 'plugins_loaded', 'bp_forums_setup', 5 );
add_action( 'admin_head', 'bp_forums_setup', 3 );

function bp_forums_is_installed_correctly() {
	global $bp;
	
	if ( file_exists( $bp->forums->bbconfig ) )
		return true;
	
	return false;
}

function bp_forums_add_admin_menu() {
	global $bp;
	
	if ( !is_site_admin() )
		return false;

	require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-admin.php' );
	
	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'bp-core.php', __( 'Forums Setup', 'buddypress' ), __( 'Forums Setup', 'buddypress' ), 2, __FILE__, "bp_forums_bbpress_admin" );
}
add_action( 'admin_menu', 'bp_forums_add_admin_menu' );

function bp_forums_get_forum( $forum_id ) {
	do_action( 'bbpress_init' );
	return bb_get_forum( $forum_id );
}

function bp_forums_get_forum_topics( $args = '' ) {
	do_action( 'bbpress_init' );
	
	$defaults = array( 
		'forum_id' => false, 
		'page' => 1, 
		'per_page' => 15, 
		'exclude' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	return get_latest_topics( array( 'forum' => $forum_id, 'page' => $page_num, 'number' => $per_page, 'exclude' => $exclude ) );
}

function bp_forums_get_topic_details( $topic_id ) {
	do_action( 'bbpress_init' );
	return get_topic( $topic_id );
}

function bp_forums_get_topic_id_from_slug( $topic_slug ) {
	do_action( 'bbpress_init' );	
	return bb_get_id_from_slug( 'topic', $topic_slug );
}

function bp_forums_get_topic_posts( $args = '' ) {
	do_action( 'bbpress_init' );
	
	$defaults = array( 
		'topic_id' => false, 
		'page' => 1,
		'per_page' => 15,
		'order' => 'ASC'
	);

	$args = wp_parse_args( $args, $defaults );

	$query = new BB_Query( 'post', $args, 'get_thread' );
	return $query->results;
}

function bp_forums_get_post( $post_id ) {
	do_action( 'bbpress_init' );
	return bb_get_post( $post_id );
}

function bp_forums_new_forum( $args = '' ) {
	do_action( 'bbpress_init' );
	
	$defaults = array( 
		'forum_name' => '', 
		'forum_desc' => '', 
		'forum_parent_id' => BP_FORUMS_PARENT_FORUM_ID, 
		'forum_order' => false, 
		'forum_is_category' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	return bb_new_forum( array( 'forum_name' => stripslashes( $forum_name ), 'forum_desc' => stripslashes( $forum_desc ), 'forum_parent' => $forum_parent_id, 'forum_order' => $forum_order, 'forum_is_category' => $forum_is_category ) );
}

function bp_forums_new_topic( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );
	
	$defaults = array(
		'topic_title' => '',
		'topic_slug' => '',
		'topic_poster' => $bp->loggedin_user->id, // accepts ids
		'topic_poster_name' => $bp->loggedin_user->fullname, // accept names
		'topic_last_poster' => $bp->loggedin_user->id, // accepts ids
		'topic_last_poster_name' => $bp->loggedin_user->fullname, // accept names
		'topic_start_time' => bb_current_time( 'mysql' ),
		'topic_time' => bb_current_time( 'mysql' ),
		'topic_open' => 1,
		'forum_id' => 0 // accepts ids or slugs
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( empty( $topic_slug ) )
		$topic_slug = sanitize_title( $topic_title );
		
	if ( !$topic_id = bb_insert_topic( array( 'topic_title' => stripslashes( $topic_title ), 'topic_slug' => $topic_slug, 'topic_poster' => $topic_poster, 'topic_poster_name' => $topic_poster_name, 'topic_last_poster' => $topic_last_poster, 'topic_last_poster_name' => $topic_last_poster_name, 'topic_start_time' => $topic_start_time, 'topic_time' => $topic_time, 'topic_open' => $topic_open, 'forum_id' => (int)$forum_id ) ) )
		return false;
	
	/* Now insert the first post. */
	if ( !bp_forums_insert_post( array( 'topic_id' => $topic_id, 'post_text' => $topic_text, 'post_time' => $topic_time, 'poster_id' => $topic_poster ) ) )
		return false;
	
	return $topic_id;
}

function bp_forums_update_topic( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );
	
	$defaults = array(
		'topic_id' => false,
		'topic_title' => '',
		'topic_text' => ''
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$topic_id = bb_insert_topic( array( 'topic_id' => $topic_id, 'topic_title' => stripslashes( $topic_title ) ) ) )
		return false;
	
	if ( !$post = bb_get_first_post( $topic_id ) )
		return false;

	/* Update the first post */
	if ( !$post = bb_insert_post( array( 'post_id' => $post->post_id, 'topic_id' => $topic_id, 'post_text' => $topic_text, 'post_time' => $post->post_time, 'poster_id' => $post->poster_id, 'poster_ip' => $post->poster_ip, 'post_status' => $post->post_status, 'post_position' => $post->post_position ) ) )
		return false;
	
	return bp_forums_get_topic_details( $topic_id );
}

function bp_forums_sticky_topic( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );
	
	$defaults = array(
		'topic_id' => false,
		'mode' => 'stick' // stick/unstick
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( 'stick' == $mode )
		return bb_stick_topic( $topic_id );
	else if ( 'unstick' == $mode )
		return bb_unstick_topic( $topic_id );

	return false;
}

function bp_forums_openclose_topic( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );
	
	$defaults = array(
		'topic_id' => false,
		'mode' => 'close' // stick/unstick
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( 'close' == $mode )
		return bb_close_topic( $topic_id );
	else if ( 'open' == $mode )
		return bb_open_topic( $topic_id );

	return false;
}

function bp_forums_delete_topic( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );
	
	$defaults = array(
		'topic_id' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bb_delete_topic( $topic_id, 1 );
}

function bp_forums_insert_post( $args = '' ) {
	global $bp;
	
	do_action( 'bbpress_init' );

	$defaults = array(
		'post_id' => false, 
		'topic_id' => false,
		'post_text' => '',
		'post_time' => bb_current_time( 'mysql' ),
		'poster_id' => $bp->loggedin_user->id, // accepts ids or names
		'poster_ip' => $_SERVER['REMOTE_ADDR'],
		'post_status' => 0, // use bb_delete_post() instead
		'post_position' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$post = bp_forums_get_post( $post_id ) )
		$post_id = false;

	if ( !isset( $topic_id ) )
		$topic_id = $post->topic_id;
	
	if ( empty( $post_text ) )
		$post_text = $post->post_text;
	
	if ( !isset( $post_time ) )
		$post_time = $post->post_time;

	if ( !isset( $post_position ) )
		$post_position = $post->post_position;

	return bb_insert_post( array( 'post_id' => $post_id, 'topic_id' => $topic_id, 'post_text' => stripslashes( $post_text ), 'post_time' => $post_time, 'poster_id' => $poster_id, 'poster_ip' => $poster_ip, 'post_status' => $post_status, 'post_position' => $post_position ) );
}

function bp_forums_make_user_active_member( $user_id ) {
	update_usermeta( $user_id, 'bb_capabilities', array( 'member' => true ) );
}
add_action( 'wpmu_new_user', 'bp_forums_make_user_active_member' );

function bp_forums_get_keymaster() {
	global $wpdb;
	
	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM " . CUSTOM_USER_META_TABLE . " WHERE meta_key = 'bb_capabilities' AND meta_value LIKE '%%keymaster%%'" ) );
	
	return get_userdata( $user_id );
}

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_forums_new_forum', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_topic', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_post', 'bp_core_clear_cache' );

function bb_forums_filter_caps( $allcaps ) {
	global $bp, $wp_roles, $bb_table_prefix;
	
	$bb_cap = get_usermeta( $bp->loggedin_user->id, $bb_table_prefix . 'capabilities' );

	if ( empty( $bb_cap ) )
		return $allcaps;
	
	$bb_cap = array_keys($bb_cap);
	$bb_cap = $wp_roles->get_role( $bb_cap[0] );
	$bb_cap = $bb_cap->capabilities;
	
	return array_merge( (array) $allcaps, (array) $bb_cap );
}
add_filter( 'user_has_cap', 'bb_forums_filter_caps' );
?>
