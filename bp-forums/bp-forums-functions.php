<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** bbPress 2.x ***************************************************************/

/**
 * Used to see if bbPress 2.x is installed and active
 *
 * @since BuddyPress (1.6)
 * @return boolean True if bbPress 2.x is active, false if not
 */
function bp_forums_is_bbpress_active() {

	// Single site
	if ( is_plugin_active( 'bbpress/bbpress.php' ) )
		return true;

	// Network active
	if ( is_plugin_active_for_network( 'bbpress/bbpress.php' ) )
		return true;

	// Nope
	return false;
}

/** bbPress 1.x ***************************************************************/

/**
 * If the bb-config-location option exists, bbPress 1.x was previously installed
 *
 * @since BuddyPress (1.2)
 * @return boolean True if option exists, false if not
 */
function bp_forums_is_installed_correctly() {
	global $bp;

	if ( isset( $bp->forums->bbconfig ) && is_file( $bp->forums->bbconfig ) )
		return true;

	return false;
}

/**
 * Checks $bp pages global and looks for directory page
 *
 * @since BuddyPress (1.5)
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @return bool True if set, False if empty
 */
function bp_forums_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->forums->id );
}

/** Forum Functions ***********************************************************/

function bp_forums_get_forum( $forum_id ) {
	do_action( 'bbpress_init' );
	return bb_get_forum( $forum_id );
}

function bp_forums_new_forum( $args = '' ) {
	do_action( 'bbpress_init' );

	$defaults = array(
		'forum_name'        => '',
		'forum_desc'        => '',
		'forum_parent_id'   => bp_forums_parent_forum_id(),
		'forum_order'       => false,
		'forum_is_category' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bb_new_forum( array( 'forum_name' => stripslashes( $forum_name ), 'forum_desc' => stripslashes( $forum_desc ), 'forum_parent' => $forum_parent_id, 'forum_order' => $forum_order, 'forum_is_category' => $forum_is_category ) );
}

function bp_forums_update_forum( $args = '' ) {
	do_action( 'bbpress_init' );

	$defaults = array(
		'forum_id'          => '',
		'forum_name'        => '',
		'forum_desc'        => '',
		'forum_slug'        => '',
		'forum_parent_id'   => bp_forums_parent_forum_id(),
		'forum_order'       => false,
		'forum_is_category' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bb_update_forum( array( 'forum_id' => (int) $forum_id, 'forum_name' => stripslashes( $forum_name ), 'forum_desc' => stripslashes( $forum_desc ), 'forum_slug' => stripslashes( $forum_slug ), 'forum_parent' => $forum_parent_id, 'forum_order' => $forum_order, 'forum_is_category' => $forum_is_category ) );
}

function bp_forums_delete_group_forum( $group_id ) {
	$forum_id = groups_get_groupmeta( $group_id, 'forum_id' );

	if ( !empty( $forum_id ) && is_int( $forum_id ) ) {
		do_action( 'bbpress_init' );
		bb_delete_forum( $forum_id );
	}
}
add_action( 'groups_delete_group', 'bp_forums_delete_group_forum' );

/** Topic Functions ***********************************************************/

function bp_forums_get_forum_topics( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'type'          => 'newest',
		'forum_id'      => false,
		'user_id'       => false,
		'page'          => 1,
		'per_page'      => 15,
		'offset'        => false,
		'number'        => false,
		'exclude'       => false,
		'show_stickies' => 'all',
		'filter'        => false // if $type = tag then filter is the tag name, otherwise it's terms to search on.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( class_exists( 'BB_Query' ) ) {
		switch ( $type ) {
			case 'newest':
				$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'per_page' => $per_page, 'page' => $page, 'number' => $per_page, 'exclude' => $exclude, 'topic_title' => $filter, 'sticky' => $show_stickies, 'offset' => $offset, 'number' => $number ), 'get_latest_topics' );
				$topics =& $query->results;
				break;

			case 'popular':
				$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_posts', 'topic_title' => $filter, 'sticky' => $show_stickies, 'offset' => $offset, 'number' => $number ) );
				$topics =& $query->results;
				break;

			case 'unreplied':
				$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'post_count' => 1, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_time', 'topic_title' => $filter, 'sticky' => $show_stickies, 'offset' => $offset, 'number' => $number ) );
				$topics =& $query->results;
				break;

			case 'tags':
				$query = new BB_Query( 'topic', array( 'forum_id' => $forum_id, 'topic_author_id' => $user_id, 'tag' => $filter, 'per_page' => $per_page, 'page' => $page, 'order_by' => 't.topic_time', 'sticky' => $show_stickies, 'offset' => $offset, 'number' => $number ) );
				$topics =& $query->results;
				break;
		}
	} else {
		$topics = array();
	}

	return apply_filters_ref_array( 'bp_forums_get_forum_topics', array( &$topics, &$r ) );
}

function bp_forums_get_topic_details( $topic_id ) {
	do_action( 'bbpress_init' );

	$query = new BB_Query( 'topic', 'topic_id=' . $topic_id . '&page=1' /* Page override so bbPress doesn't use the URI */ );

	return $query->results[0];
}

function bp_forums_get_topic_id_from_slug( $topic_slug ) {
	do_action( 'bbpress_init' );

	if ( empty( $topic_slug ) )
		return false;

	return bb_get_id_from_slug( 'topic', $topic_slug );
}

function bp_forums_new_topic( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'topic_title'            => '',
		'topic_slug'             => '',
		'topic_text'             => '',
		'topic_poster'           => bp_loggedin_user_id(),       // accepts ids
		'topic_poster_name'      => $bp->loggedin_user->fullname, // accept names
		'topic_last_poster'      => bp_loggedin_user_id(),       // accepts ids
		'topic_last_poster_name' => $bp->loggedin_user->fullname, // accept names
		'topic_start_time'       => bp_core_current_time(),
		'topic_time'             => bp_core_current_time(),
		'topic_open'             => 1,
		'topic_tags'             => false, // accepts array or comma delim
		'forum_id'               => 0      // accepts ids or slugs
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$topic_title = strip_tags( $topic_title );

	if ( empty( $topic_title ) || !strlen( trim( $topic_title ) ) )
		return false;

	if ( empty( $topic_poster ) )
		return false;

	if ( bp_is_user_inactive( $topic_poster ) )
		return false;

	if ( empty( $topic_slug ) )
		$topic_slug = sanitize_title( $topic_title );

	if ( !$topic_id = bb_insert_topic( array( 'topic_title' => stripslashes( $topic_title ), 'topic_slug' => $topic_slug, 'topic_poster' => $topic_poster, 'topic_poster_name' => $topic_poster_name, 'topic_last_poster' => $topic_last_poster, 'topic_last_poster_name' => $topic_last_poster_name, 'topic_start_time' => $topic_start_time, 'topic_time' => $topic_time, 'topic_open' => $topic_open, 'forum_id' => (int) $forum_id, 'tags' => $topic_tags ) ) )
		return false;

	// Now insert the first post.
	if ( !bp_forums_insert_post( array( 'topic_id' => $topic_id, 'post_text' => $topic_text, 'post_time' => $topic_time, 'poster_id' => $topic_poster ) ) )
		return false;

	do_action( 'bp_forums_new_topic', $topic_id );

	return $topic_id;
}

function bp_forums_update_topic( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'topic_id'    => false,
		'topic_title' => '',
		'topic_text'  => '',
		'topic_tags'  => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Check if the user is a spammer 
	if ( bp_is_user_inactive( bp_loggedin_user_id() ) ) 
		return false;

	// bb_insert_topic() will append tags, but not remove them. So we remove all existing tags.
	bb_remove_topic_tags( $topic_id );

	if ( !$topic_id = bb_insert_topic( array( 'topic_id' => $topic_id, 'topic_title' => stripslashes( $topic_title ), 'tags' => $topic_tags ) ) )
		return false;

	if ( !$post = bb_get_first_post( $topic_id ) )
		return false;

	// Update the first post
	if ( !$post = bp_forums_insert_post( array( 'post_id' => $post->post_id, 'topic_id' => $topic_id, 'post_text' => $topic_text, 'post_time' => $post->post_time, 'poster_id' => $post->poster_id, 'poster_ip' => $post->poster_ip, 'post_status' => $post->post_status, 'post_position' => $post->post_position ) ) )
		return false;

	return bp_forums_get_topic_details( $topic_id );
}

function bp_forums_sticky_topic( $args = '' ) {
	global $bp;

	do_action( 'bbpress_init' );

	$defaults = array(
		'topic_id' => false,
		'mode'     => 'stick' // stick/unstick
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
		'mode'     => 'close' // stick/unstick
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
	global $bbdb;

	do_action( 'bbpress_init' );

	if ( isset( $bbdb ) ) {
		if ( bp_is_active( 'groups' ) ) {
			$groups_table_sql = groups_add_forum_tables_sql();
			$groups_where_sql = groups_add_forum_where_sql( "t.topic_status = 0" );
		} else {
			$groups_table_sql = '';
			$groups_where_sql = "t.topic_status = 0";
		}
		$count = $bbdb->get_results( "SELECT t.topic_id FROM {$bbdb->topics} AS t {$groups_table_sql} WHERE {$groups_where_sql}" );
		$count = count( (array) $count );
	} else {
		$count = 0;
	}

	return apply_filters( 'bp_forums_total_topic_count', $count );
}

/**
 * Check to see whether a user has already left this particular reply on a given post.
 * Prevents dupes.
 *
 * @since 1.6
 *
 * @param str $text The text of the comment
 * @param int $topic_id The topic id
 * @param int $user_id The user id
 */
function bp_forums_reply_exists( $text = '', $topic_id = 0, $user_id = 0 ) {
	$reply_exists = false;
	
	if ( $text && $topic_id && $user_id ) {
		do_action( 'bbpress_init' );
		
		$args = array(
			'post_author_id' => $user_id,
			'topic_id'       => $topic_id
		);
		
		// BB_Query's post_text parameter does a MATCH, while we need exact matches
		add_filter( 'get_posts_where', create_function( '$q', 'return $q . " AND p.post_text = \'' . $text . '\'";' ) );
		
		$query = new BB_Query( 'post', $args );
		
		$reply_exists = !empty( $query->results );
	}
	
	return apply_filters( 'bp_forums_reply_exists', $reply_exists, $text, $topic_id, $user_id );
}

/**
 * Get a total "Topics Started" count for a given user
 *
 * @package BuddyPress
 *
 * @param int $user_id ID of the user being queried. Falls back on displayed user, then loggedin
 * @param str $type The current filter/sort type. 'active', 'popular', 'unreplied'
 * @return int $count The topic count
 */
function bp_forums_total_topic_count_for_user( $user_id = 0, $type = 'active' ) {
	global $bp;

	do_action( 'bbpress_init' );

	if ( !$user_id )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	if ( class_exists( 'BB_Query' ) ) {
		$args = array(
			'topic_author_id' => $user_id,
			'page' 		  => 1,
			'per_page'	  => -1,
			'count'		  => true
		);

		if ( 'unreplied' == $type )
			$args['post_count'] = 1;

		$query = new BB_Query( 'topic', $args );
		$count = $query->count;
		$query = null;
	} else {
		$count = 0;
	}

	return $count;
}

/**
 * Return the total number of topics replied to by a given user
 *
 * Uses an unfortunate technique to count unique topics, due to limitations in BB_Query.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param int $user_id Defaults to displayed user, then to logged-in user
 * @return int $count
 */
function bp_forums_total_replied_count_for_user( $user_id = 0, $type = 'active' ) {
	global $bp;

	do_action( 'bbpress_init' );

	if ( !$user_id )
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	if ( !$user_id )
		return 0;

	if ( class_exists( 'BB_Query' ) ) {
		$query = new BB_Query( 'post', array( 'post_author_id' => $user_id, 'page' => 1, 'per_page' => -1, 'count' => true ) );

		// Count the unique topics. No better way to do this in the bbPress query API
		$topics = array();
		foreach( $query->results as $result ) {
			if ( !in_array( $result->topic_id, $topics ) )
				$topics[] = $result->topic_id;
		}

		// Even more unfortunate. If this is filtered by 'unreplied', we have to requery
		if ( 'unreplied' == $type ) {
			$topic_ids = implode( ',', $topics );
			$topics_query = new BB_Query( 'topic', array( 'topic_id' => $topic_ids, 'page' => 1, 'per_page' => -1, 'post_count' => 1 ) );
			$count = count( $topics_query->results );
		} else {
			$count = count( $topics );
		}
		$query = null;
	} else {
		$count = 0;
	}

	return apply_filters( 'bp_forums_total_replied_count_for_user', $count, $user_id );
}

function bp_forums_get_topic_extras( $topics ) {
	global $bp, $wpdb, $bbdb;

	if ( empty( $topics ) )
		return $topics;

	// Get the topic ids
	foreach ( (array) $topics as $topic ) $topic_ids[] = $topic->topic_id;
	$topic_ids = $wpdb->escape( join( ',', (array) $topic_ids ) );

	// Fetch the topic's last poster details
	$poster_details = $wpdb->get_results( "SELECT t.topic_id, t.topic_last_poster, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$wpdb->users} u, {$bbdb->topics} t WHERE u.ID = t.topic_last_poster AND t.topic_id IN ( {$topic_ids} )" );
	for ( $i = 0, $count = count( $topics ); $i < $count; ++$i ) {
		foreach ( (array) $poster_details as $poster ) {
			if ( $poster->topic_id == $topics[$i]->topic_id ) {
				$topics[$i]->topic_last_poster_email       = $poster->user_email;
				$topics[$i]->topic_last_poster_nicename    = $poster->user_nicename;
				$topics[$i]->topic_last_poster_login       = $poster->user_login;
				$topics[$i]->topic_last_poster_displayname = $poster->display_name;
			}
		}
	}

	// Fetch fullname for the topic's last poster
	if ( bp_is_active( 'xprofile' ) ) {
		$poster_names = $wpdb->get_results( "SELECT t.topic_id, pd.value FROM {$bp->profile->table_name_data} pd, {$bbdb->topics} t WHERE pd.user_id = t.topic_last_poster AND pd.field_id = 1 AND t.topic_id IN ( {$topic_ids} )" );
		for ( $i = 0, $count = count( $topics ); $i < $count; ++$i ) {
			foreach ( (array) $poster_names as $name ) {
				if ( $name->topic_id == $topics[$i]->topic_id )
					$topics[$i]->topic_last_poster_displayname = $name->value;
			}
		}
	}

	// Loop through to make sure that each topic has the proper values set. This covers the
	// case of deleted users
	foreach ( (array) $topics as $key => $topic ) {
		if ( !isset( $topic->topic_last_poster_email ) )
			$topics[$key]->topic_last_poster_email = '';

		if ( !isset( $topic->topic_last_poster_nicename ) )
			$topics[$key]->topic_last_poster_nicename = '';

		if ( !isset( $topic->topic_last_poster_login ) )
			$topics[$key]->topic_last_poster_login = '';

		if ( !isset( $topic->topic_last_poster_displayname ) )
			$topics[$key]->topic_last_poster_displayname = '';
	}

	return $topics;
}

/** Post Functions ************************************************************/

function bp_forums_get_topic_posts( $args = '' ) {
	do_action( 'bbpress_init' );

	$defaults = array(
		'topic_id' => false,
		'page'     => 1,
		'per_page' => 15,
		'order'    => 'ASC'
	);

	$args  = wp_parse_args( $args, $defaults );
	$query = new BB_Query( 'post', $args, 'get_thread' );

	return bp_forums_get_post_extras( $query->results );
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
		'post_id'       => false,
		'topic_id'      => false,
		'post_text'     => '',
		'post_time'     => bp_core_current_time(),
		'poster_id'     => bp_loggedin_user_id(), // accepts ids or names
		'poster_ip'     => $_SERVER['REMOTE_ADDR'],
		'post_status'   => 0, // use bb_delete_post() instead
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

	if ( empty( $poster_id ) )
		return false;

	if ( bp_is_user_inactive( bp_loggedin_user_id() ) )
		return false;

	$post_id = bb_insert_post( array( 'post_id' => $post_id, 'topic_id' => $topic_id, 'post_text' => stripslashes( trim( $post_text ) ), 'post_time' => $post_time, 'poster_id' => $poster_id, 'poster_ip' => $poster_ip, 'post_status' => $post_status, 'post_position' => $post_position ) );

	if ( !empty( $post_id ) )
		do_action( 'bp_forums_new_post', $post_id );

	return $post_id;
}

function bp_forums_get_post_extras( $posts ) {
	global $bp, $wpdb;

	if ( empty( $posts ) )
		return $posts;

	// Get the user ids
	foreach ( (array) $posts as $post ) $user_ids[] = $post->poster_id;
	$user_ids = $wpdb->escape( join( ',', (array) $user_ids ) );

	// Fetch the poster's user_email, user_nicename and user_login
	$poster_details = $wpdb->get_results( "SELECT u.ID as user_id, u.user_login, u.user_nicename, u.user_email, u.display_name FROM {$wpdb->users} u WHERE u.ID IN ( {$user_ids} )" );

	for ( $i = 0, $count = count( $posts ); $i < $count; ++$i ) {
		foreach ( (array) $poster_details as $poster ) {
			if ( $poster->user_id == $posts[$i]->poster_id ) {
				$posts[$i]->poster_email    = $poster->user_email;
				$posts[$i]->poster_login    = $poster->user_login;
				$posts[$i]->poster_nicename = $poster->user_nicename;
				$posts[$i]->poster_name     = $poster->display_name;
			}
		}
	}

	// Fetch fullname for each poster.
	if ( bp_is_active( 'xprofile' ) ) {
		$poster_names = $wpdb->get_results( "SELECT pd.user_id, pd.value FROM {$bp->profile->table_name_data} pd WHERE pd.user_id IN ( {$user_ids} )" );
		for ( $i = 0, $count = count( $posts ); $i < $count; ++$i ) {
			foreach ( (array) $poster_names as $name ) {
				if ( isset( $topics[$i] ) && $name->user_id == $topics[$i]->user_id )
				$posts[$i]->poster_name = $poster->value;
			}
		}
	}

	return apply_filters( 'bp_forums_get_post_extras', $posts );
}

function bp_forums_get_forum_topicpost_count( $forum_id ) {
	global $wpdb, $bbdb;

	do_action( 'bbpress_init' );

	// Need to find a bbPress function that does this
	return $wpdb->get_results( $wpdb->prepare( "SELECT topics, posts from {$bbdb->forums} WHERE forum_id = %d", $forum_id ) );
}

function bp_forums_filter_caps( $allcaps ) {
	global $bp, $wp_roles, $bb_table_prefix;

	if ( !bp_loggedin_user_id() )
		return $allcaps;

	$bb_cap = bp_get_user_meta( bp_loggedin_user_id(), $bb_table_prefix . 'capabilities', true );

	if ( empty( $bb_cap ) )
		return $allcaps;

	$bb_cap = array_keys($bb_cap);
	$bb_cap = $wp_roles->get_role( $bb_cap[0] );
	$bb_cap = $bb_cap->capabilities;

	return array_merge( (array) $allcaps, (array) $bb_cap );
}
add_filter( 'user_has_cap', 'bp_forums_filter_caps' );

/**
 * Returns the parent forum id for the bbPress abstraction layer
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int
 */
function bp_forums_parent_forum_id() {
	return apply_filters( 'bp_forums_parent_forum_id', BP_FORUMS_PARENT_FORUM_ID );
}

/**
 * Should sticky topics be broken out of regular topic order on forum directories?
 *
 * Defaults to false. Define BP_FORUMS_ENABLE_GLOBAL_DIRECTORY_STICKIES, or filter
 * bp_forums_enable_global_directory_stickies, to change this behavior.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool True if stickies should be displayed at the top of the global directory, false
 *    otherwise.
 */
function bp_forums_enable_global_directory_stickies() {
	return apply_filters( 'bp_forums_enable_global_directory_stickies', defined( 'BP_FORUMS_ENABLE_GLOBAL_DIRECTORY_STICKIES' ) && BP_FORUMS_ENABLE_GLOBAL_DIRECTORY_STICKIES );
}


/********************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_forums_new_forum', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_topic', 'bp_core_clear_cache' );
add_action( 'bp_forums_new_post',  'bp_core_clear_cache' );


/** Embeds *******************************************************************/

/**
 * Grabs the topic post ID and attempts to retrieve the oEmbed cache (if it exists)
 * during the forum topic loop.  If no cache and link is embeddable, cache it.
 *
 * @see BP_Embed
 * @see bp_embed_forum_cache()
 * @see bp_embed_forum_save_cache()
 * @package BuddyPress_Forums
 * @since BuddyPress (1.5)
 */
function bp_forums_embed() {
	add_filter( 'embed_post_id',         'bp_get_the_topic_post_id'         );
	add_filter( 'bp_embed_get_cache',    'bp_embed_forum_cache',      10, 3 );
	add_action( 'bp_embed_update_cache', 'bp_embed_forum_save_cache', 10, 3 );
}
add_action( 'topic_loop_start', 'bp_forums_embed' );

/**
 * Wrapper function for {@link bb_get_postmeta()}.
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_forums_embed()}.
 *
 * @package BuddyPress_Forums
 * @since BuddyPress (1.5)
 */
function bp_embed_forum_cache( $cache, $id, $cachekey ) {
	return bb_get_postmeta( $id, $cachekey );
}

/**
 * Wrapper function for {@link bb_update_postmeta()}.
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_forums_embed()}.
 *
 * @package BuddyPress_Forums
 * @since BuddyPress (1.5)
 */
function bp_embed_forum_save_cache( $cache, $cachekey, $id ) {
	bb_update_postmeta( $id, $cachekey, $cache );
}
?>