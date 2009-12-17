<?php

/* Define the parent forum ID */
if ( !defined( 'BP_FORUMS_PARENT_FORUM_ID' ) )
	define( 'BP_FORUMS_PARENT_FORUM_ID', 1 );

if ( !defined( 'BP_FORUMS_SLUG' ) )
	define( 'BP_FORUMS_SLUG', 'forums' );

if ( !defined( 'BB_PATH' ) )
	require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-bbpress.php' );

require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-filters.php' );

function bp_forums_setup() {
	global $bp;

	/* For internal identification */
	$bp->forums->id = 'forums';

	$bp->forums->image_base = BP_PLUGIN_URL . '/bp-forums/images';
	$bp->forums->bbconfig = get_site_option( 'bb-config-location' );
	$bp->forums->slug = BP_FORUMS_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->forums->slug] = $bp->forums->id;

	do_action( 'bp_forums_setup' );
}
add_action( 'plugins_loaded', 'bp_forums_setup', 5 );
add_action( 'admin_head', 'bp_forums_setup', 2 );

function bp_forums_is_installed_correctly() {
	global $bp;

	if ( file_exists( $bp->forums->bbconfig ) )
		return true;

	return false;
}

function bp_forums_setup_root_component() {
	/* Register 'forums' as a root component */
	bp_core_add_root_component( BP_FORUMS_SLUG );
}
add_action( 'plugins_loaded', 'bp_forums_setup_root_component', 2 );

function bp_forums_directory_forums_setup() {
	global $bp;

	if ( $bp->current_component == $bp->forums->slug ) {
		if ( (int) get_site_option( 'bp-disable-forum-directory' ) || !function_exists( 'groups_install' ) )
			return false;

		if ( !bp_forums_is_installed_correctly() ) {
			bp_core_add_message( __( 'The forums component has not been set up yet.', 'buddypress' ), 'error' );
			bp_core_redirect( $bp->root_domain );
		}

		$bp->is_directory = true;

		do_action( 'bbpress_init' );

		/* Check to see if the user has posted a new topic from the forums page. */
		if ( isset( $_POST['submit_topic'] ) && function_exists( 'bp_forums_new_topic' ) ) {
			/* Check the nonce */
			check_admin_referer( 'bp_forums_new_topic' );

			if ( $group = groups_get_group( array( 'group_id' => $_POST['topic_group_id'] ) ) ) {
				/* Auto join this user if they are not yet a member of this group */
				if ( !is_site_admin() && 'public' == $group->status && !groups_is_user_member( $bp->loggedin_user->id, $group->id ) )
					groups_join_group( $group->id, $group->id );

				if ( $forum_id = groups_get_groupmeta( $group->id, 'forum_id' ) ) {
					if ( !$topic = groups_new_group_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id ) )
						bp_core_add_message( __( 'There was an error when creating the topic', 'buddypress'), 'error' );
					else
						bp_core_add_message( __( 'The topic was created successfully', 'buddypress') );

					bp_core_redirect( bp_get_group_permalink( $group ) . '/forum/topic/' . $topic->topic_slug . '/' );
				}
			}
		}

		do_action( 'bp_forums_directory_forums_setup' );

		bp_core_load_template( apply_filters( 'bp_forums_template_directory_forums_setup', 'forums/index' ) );
	}
}
add_action( 'wp', 'bp_forums_directory_forums_setup', 2 );

function bp_forums_add_admin_menu() {
	global $bp;

	if ( !is_site_admin() )
		return false;

	require ( BP_PLUGIN_DIR . '/bp-forums/bp-forums-admin.php' );

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'bp-general-settings', __( 'Forums Setup', 'buddypress' ), __( 'Forums Setup', 'buddypress' ), 'manage_options', 'bb-forums-setup', "bp_forums_bbpress_admin" );
}
add_action( 'admin_menu', 'bp_forums_add_admin_menu' );

/* Forum Functions */

function bp_forums_get_forum( $forum_id ) {
	do_action( 'bbpress_init' );
	return bb_get_forum( $forum_id );
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

/* Topic Functions */

function bp_forums_get_forum_topics( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'type' => 'newest',
		'forum_id' => false,
		'user_id' => false,
		'page' => 1,
		'per_page' => 15,
		'exclude' => false,
		'show_stickies' => 'all',
		'filter' => false // if $type = tag then filter is the tag name, otherwise it's terms to search on.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	switch ( $type ) {
		case 'newest':
			$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'per_page' => $per_page, 'page' => $page, 'number' => $per_page, 'exclude' => $exclude, 'topic_title' => $filter, 'sticky' => $show_stickies ), 'get_latest_topics' );
			$topics =& $query->results;
		break;

		case 'popular':
			$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_posts', 'topic_title' => $filter, 'sticky' => $show_stickies ) );
			$topics =& $query->results;
		break;

		case 'unreplied':
			$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'post_count' => 1, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_time', 'topic_title' => $filter, 'sticky' => $show_stickies ) );
			$topics =& $query->results;
		break;

		case 'tag':
			$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'tag' => $filter, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_time', 'sticky' => $show_stickies ) );
			$topics =& $query->results;
		break;
	}

	return apply_filters( 'bp_forums_get_forum_topics', $topics, &$r );
}

function bp_forums_get_topic_details( $topic_id ) {
	do_action( 'bbpress_init' );

	$query = new BB_Query( 'topic', 'topic_id=' . $topic_id . '&page=1' /* Page override so bbPress doesn't use the URI */ );

	return $query->results[0];
}

function bp_forums_get_topic_id_from_slug( $topic_slug ) {
	do_action( 'bbpress_init' );
	return bb_get_id_from_slug( 'topic', $topic_slug );
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
		'topic_start_time' => date( 'Y-m-d H:i:s' ),
		'topic_time' => date( 'Y-m-d H:i:s' ),
		'topic_open' => 1,
		'topic_tags' => false, // accepts array or comma delim
		'forum_id' => 0 // accepts ids or slugs
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty( $topic_slug ) )
		$topic_slug = sanitize_title( $topic_title );

	if ( !$topic_id = bb_insert_topic( array( 'topic_title' => stripslashes( $topic_title ), 'topic_slug' => $topic_slug, 'topic_poster' => $topic_poster, 'topic_poster_name' => $topic_poster_name, 'topic_last_poster' => $topic_last_poster, 'topic_last_poster_name' => $topic_last_poster_name, 'topic_start_time' => $topic_start_time, 'topic_time' => $topic_time, 'topic_open' => $topic_open, 'forum_id' => (int)$forum_id, 'tags' => $topic_tags ) ) )
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

function bp_forums_total_topic_count() {
	do_action( 'bbpress_init' );

	$query = new BB_Query( 'topic', array( 'page' => 1, 'per_page' => -1, 'count' => true ) );
	$count = $query->count;
	$query = null;

	return $count;
}

function bp_forums_total_topic_count_for_user( $user_id = false ) {
	global $bp;

	do_action( 'bbpress_init' );

	if ( !$user_id )
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	$query = new BB_Query( 'topic', array( 'topic_author_id' => $user_id, 'page' => 1, 'per_page' => -1, 'count' => true ) );
	$count = $query->count;
	$query = null;

	return $count;
}

/* Post Functions */

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

function bp_forums_delete_post( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'post_id' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bb_delete_post( $post_id, 1 );
}

function bp_forums_insert_post( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'post_id' => false,
		'topic_id' => false,
		'post_text' => '',
		'post_time' => date( 'Y-m-d H:i:s' ),
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

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_forums_new_forum', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_topic', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_post', 'bp_core_clear_cache' );

function bp_forums_filter_caps( $allcaps ) {
	global $bp, $wp_roles, $bb_table_prefix;

	$bb_cap = get_usermeta( $bp->loggedin_user->id, $bb_table_prefix . 'capabilities' );

	if ( empty( $bb_cap ) )
		return $allcaps;

	$bb_cap = array_keys($bb_cap);
	$bb_cap = $wp_roles->get_role( $bb_cap[0] );
	$bb_cap = $bb_cap->capabilities;

	return array_merge( (array) $allcaps, (array) $bb_cap );
}
add_filter( 'user_has_cap', 'bp_forums_filter_caps' );

/**
 * bp_forums_filter_template_paths()
 *
 * Add fallback for the bp-sn-parent theme template locations used in BuddyPress versions
 * older than 1.2.
 *
 * @package BuddyPress Core
 */
function bp_forums_filter_template_paths() {
	if ( 'bp-sn-parent' != basename( TEMPLATEPATH ) && !defined( 'BP_CLASSIC_TEMPLATE_STRUCTURE' ) )
		return false;

	add_filter( 'bp_forums_template_directory_forums_setup', create_function( '', 'return "directories/forums/index";' ) );
}
add_action( 'init', 'bp_forums_filter_template_paths' );

?>