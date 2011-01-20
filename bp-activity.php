<?php

class BP_Activity_Component extends BP_Component {

	/**
	 * Start the activity component creation process
	 */
	function BP_Activity_Component() {
		parent::start( 'activity', __( 'Activity Streams', 'buddypress' ) );
	}

	/**
	 * Setup globals
	 *
	 * @global obj $bp
	 */
	function _setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_ACTIVITY_SLUG' ) )
			define( 'BP_ACTIVITY_SLUG', 'activity' );

		// Do some slug checks
		$this->slug      = defined( 'BP_ACTIVITY_SLUG' )       ? BP_ACTIVITY_SLUG           : $this->id;
		$this->root_slug = isset( $bp->pages->activity->slug ) ? $bp->pages->activity->slug : $this->slug;

		// Tables
		$this->table_name      = $bp->table_prefix . 'bp_activity';
		$this->table_name_meta = $bp->table_prefix . 'bp_activity_meta';
	}
	
	/**
	 * Include files
	 *
	 * @global obj $bp
	 */
	function _includes() {
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-actions.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-filters.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-screens.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-classes.php'      );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-functions.php'    );
		require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-template.php' );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global obj $bp
	 */
	function _setup_nav() {
		global $bp;

		// Add 'Activity' to the main navigation
		bp_core_new_nav_item( array(
			'name'                => __( 'Activity', 'buddypress' ),
			'slug'                => $bp->activity->slug,
			'position'            => 10,
			'screen_function'     => 'bp_activity_screen_my_activity',
			'default_subnav_slug' => 'just-me',
			'item_css_id'         => $bp->activity->id )
		);

		// Stop if there is no user displayed or logged in
		if ( !is_user_logged_in() && !isset( $bp->displayed_user->id ) )
			return;

		// User links
		$user_domain   = ( isset( $bp->displayed_user->domain ) )               ? $bp->displayed_user->domain               : $bp->loggedin_user->domain;
		$user_login    = ( isset( $bp->displayed_user->userdata->user_login ) ) ? $bp->displayed_user->userdata->user_login : $bp->loggedin_user->userdata->user_login;
		$activity_link = $user_domain . $bp->activity->slug . '/';

		// Add the subnav items to the activity nav item if we are using a theme that supports this
		bp_core_new_subnav_item( array(
			'name'            => __( 'Personal', 'buddypress' ),
			'slug'            => 'just-me',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_my_activity',
			'position'        => 10
		) );

		// Additional menu if friends is active
		if ( bp_is_active( 'friends' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Friends', 'buddypress' ),
				'slug'            => BP_FRIENDS_SLUG,
				'parent_url'      => $activity_link,
				'parent_slug'     => $bp->activity->slug,
				'screen_function' => 'bp_activity_screen_friends',
				'position'        => 20,
				'item_css_id'     => 'activity-friends'
			) );
		}

		// Additional menu if groups is active
		if ( bp_is_active( 'groups' ) ) {
			bp_core_new_subnav_item( array(
				'name'            => __( 'Groups', 'buddypress' ),
				'slug'            => BP_GROUPS_SLUG,
				'parent_url'      => $activity_link,
				'parent_slug'     => $bp->activity->slug,
				'screen_function' => 'bp_activity_screen_groups',
				'position'        => 30,
				'item_css_id'     => 'activity-groups'
			) );
		}

		// Favorite activity items
		bp_core_new_subnav_item( array(
			'name'            => __( 'Favorites', 'buddypress' ),
			'slug'            => 'favorites',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_favorites',
			'position'        => 40,
			'item_css_id'     => 'activity-favs'
		) );

		// @ mentions
		bp_core_new_subnav_item( array(
			'name'            => sprintf( __( '@%s Mentions', 'buddypress' ), $user_login ),
			'slug'            => 'mentions',
			'parent_url'      => $activity_link,
			'parent_slug'     => $bp->activity->slug,
			'screen_function' => 'bp_activity_screen_mentions',
			'position'        => 50,
			'item_css_id'     => 'activity-mentions'
		) );

		// Adjust title based on view
		if ( bp_is_activity_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Activity', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title  = $bp->displayed_user->fullname;
			}
		}
	}
}
// Create the activity component
$bp->activity = new BP_Activity_Component();

/**
 * @todo Figure out if this is still needed
 *
 * @global obj $bp
 */
function bp_activity_directory_activity_setup() {
	global $bp;

	if ( bp_is_activity_component() && empty( $bp->current_action ) ) {
		$bp->is_directory = true;

		do_action( 'bp_activity_directory_activity_setup' );

		bp_core_load_template( apply_filters( 'bp_activity_directory_activity_setup', 'activity/index' ) );
	}
}
add_action( 'wp', 'bp_activity_directory_activity_setup', 2 );

/**
 * Searches through the content of an activity item to locate usernames, designated by an @ sign
 *
 * @package BuddyPress Activity
 * @since 1.3
 *
 * @param $content The content of the activity, usually found in $activity->content
 * @return array $usernames Array of the found usernames that match existing users
 */
function bp_activity_find_mentions( $content ) {
	$pattern = '/[@]+([A-Za-z0-9-_\.]+)/';
	preg_match_all( $pattern, $content, $usernames );

	// Make sure there's only one instance of each username
	if ( !$usernames = array_unique( $usernames[1] ) )
		return false;

	return $usernames;
}

/**
 * Reduces new mention count for mentioned users when activity items are deleted
 *
 * @package BuddyPress Activity
 * @since 1.3
 *
 * @param $activity_id The unique id for the activity item
 */
function bp_activity_reduce_mention_count( $activity_id ) {
	$activity = new BP_Activity_Activity( $activity_id );

	if ( $usernames = bp_activity_find_mentions( strip_tags( $activity->content ) ) ) {
		if ( ! function_exists( 'username_exists' ) )
			require_once( ABSPATH . WPINC . '/registration.php' );

		foreach( (array)$usernames as $username ) {
			if ( !$user_id = username_exists( $username ) )
				continue;

			// Decrease the number of new @ mentions for the user
			$new_mention_count = (int)get_user_meta( $user_id, 'bp_new_mention_count', true );
			update_user_meta( $user_id, 'bp_new_mention_count', $new_mention_count - 1 );
		}
	}
}
add_action( 'bp_activity_action_delete_activity', 'bp_activity_reduce_mention_count' );

/**
 * Formats notifications related to activity
 *
 * @package BuddyPress Activity
 * @param $action The type of activity item. Just 'new_at_mention' for now
 * @param $item_id The activity id
 * @param $secondary_item_id In the case of at-mentions, this is the mentioner's id
 * @param $total_items The total number of notifications to format
 */
function bp_activity_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	switch ( $action ) {
		case 'new_at_mention':
			$activity_id      = $item_id;
			$poster_user_id   = $secondary_item_id;
			$at_mention_link  = $bp->loggedin_user->domain . $bp->activity->slug . '/mentions/';
			$at_mention_title = sprintf( __( '@%s Mentions', 'buddypress' ), $bp->loggedin_user->userdata->user_nicename );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_activity_multiple_at_mentions_notification', '<a href="' . $at_mention_link . '" title="' . $at_mention_title . '">' . sprintf( __( 'You have %1$d new activity mentions', 'buddypress' ), (int)$total_items ) . '</a>', $at_mention_link, $total_items, $activity_id, $poster_user_id );
			} else {
				$user_fullname = bp_core_get_user_displayname( $poster_user_id );

				return apply_filters( 'bp_activity_single_at_mentions_notification', '<a href="' . $at_mention_link . '" title="' . $at_mention_title . '">' . sprintf( __( '%1$s mentioned you in an activity update', 'buddypress' ), $user_fullname ) . '</a>', $at_mention_link, $total_items, $activity_id, $poster_user_id );
			}
		break;
	}

	do_action( 'activity_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

/** Actions *******************************************************************/

function bp_activity_set_action( $component_id, $key, $value ) {
	global $bp;

	if ( empty( $component_id ) || empty( $key ) || empty( $value ) )
		return false;

	$bp->activity->actions->{$component_id}->{$key} = apply_filters( 'bp_activity_set_action', array(
		'key'   => $key,
		'value' => $value
	), $component_id, $key, $value );
}

function bp_activity_get_action( $component_id, $key ) {
	global $bp;

	if ( empty( $component_id ) || empty( $key ) )
		return false;

	return apply_filters( 'bp_activity_get_action', $bp->activity->actions->{$component_id}->{$key}, $component_id, $key );
}

/** Favorites *****************************************************************/

function bp_activity_get_user_favorites( $user_id ) {
	$my_favs = maybe_unserialize( get_user_meta( $user_id, 'bp_favorite_activities', true ) );
	$existing_favs = bp_activity_get_specific( array( 'activity_ids' => $my_favs ) );

	foreach( (array)$existing_favs['activities'] as $fav )
		$new_favs[] = $fav->id;

	$new_favs = array_unique( (array)$new_favs );
	update_user_meta( $user_id, 'bp_favorite_activities', $new_favs );

	return apply_filters( 'bp_activity_get_user_favorites', $new_favs );
}

function bp_activity_add_user_favorite( $activity_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Update the user's personal favorites
	$my_favs = maybe_unserialize( get_user_meta( $bp->loggedin_user->id, 'bp_favorite_activities', true ) );
	$my_favs[] = $activity_id;

	// Update the total number of users who have favorited this activity
	$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );

	if ( !empty( $fav_count ) )
		$fav_count = (int)$fav_count + 1;
	else
		$fav_count = 1;

	update_user_meta( $bp->loggedin_user->id, 'bp_favorite_activities', $my_favs );
	bp_activity_update_meta( $activity_id, 'favorite_count', $fav_count );

	do_action( 'bp_activity_add_user_favorite', $activity_id, $user_id );

	return true;
}

function bp_activity_remove_user_favorite( $activity_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Remove the fav from the user's favs
	$my_favs = maybe_unserialize( get_user_meta( $user_id, 'bp_favorite_activities', true ) );
	$my_favs = array_flip( (array) $my_favs );
	unset( $my_favs[$activity_id] );
	$my_favs = array_unique( array_flip( $my_favs ) );

	// Update the total number of users who have favorited this activity
	$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );

	if ( !empty( $fav_count ) ) {
		$fav_count = (int)$fav_count - 1;
		bp_activity_update_meta( $activity_id, 'favorite_count', $fav_count );
	}

	update_user_meta( $user_id, 'bp_favorite_activities', $my_favs );

	do_action( 'bp_activity_remove_user_favorite', $activity_id, $user_id );

	return true;
}

function bp_activity_check_exists_by_content( $content ) {
	return apply_filters( 'bp_activity_check_exists_by_content', BP_Activity_Activity::check_exists_by_content( $content ) );
}

function bp_activity_get_last_updated() {
	return apply_filters( 'bp_activity_get_last_updated', BP_Activity_Activity::get_last_updated() );
}

function bp_activity_total_favorites_for_user( $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	return BP_Activity_Activity::total_favorite_count( $user_id );
}

/** Meta **********************************************************************/


function bp_activity_delete_meta( $activity_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	if ( !is_numeric( $activity_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_array( $meta_value ) || is_object( $meta_value ) )
		$meta_value = serialize( $meta_value );

	$meta_value = trim( $meta_value );

	if ( !$meta_key )
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id = %d", $activity_id ) );
	else if ( $meta_value )
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id = %d AND meta_key = %s AND meta_value = %s", $activity_id, $meta_key, $meta_value ) );
	else
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id = %d AND meta_key = %s", $activity_id, $meta_key ) );

	wp_cache_delete( 'bp_activity_meta_' . $meta_key . '_' . $activity_id, 'bp' );

	return true;
}

function bp_activity_get_meta( $activity_id, $meta_key = '' ) {
	global $wpdb, $bp;

	$activity_id = (int)$activity_id;

	if ( !$activity_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

		if ( !$metas = wp_cache_get( 'bp_activity_meta_' . $meta_key . '_' . $activity_id, 'bp' ) ) {
			$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM {$bp->activity->table_name_meta} WHERE activity_id = %d AND meta_key = %s", $activity_id, $meta_key ) );
			wp_cache_set( 'bp_activity_meta_' . $meta_key . '_' . $activity_id, $metas, 'bp' );
		}
	} else
		$metas = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$bp->activity->table_name_meta} WHERE activity_id = %d", $activity_id ) );

	if ( empty($metas) )
		return false;

	$metas = array_map( 'maybe_unserialize', (array)$metas );

	if ( 1 == count($metas) )
		return $metas[0];
	else
		return $metas;
}

function bp_activity_update_meta( $activity_id, $meta_key, $meta_value ) {
	global $wpdb, $bp;

	if ( !is_numeric( $activity_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_string( $meta_value ) )
		$meta_value = stripslashes( $wpdb->escape( $meta_value ) );

	$meta_value = maybe_serialize( $meta_value );

	if ( empty( $meta_value ) )
		return bp_activity_delete_meta( $activity_id, $meta_key );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name_meta} WHERE activity_id = %d AND meta_key = %s", $activity_id, $meta_key ) );

	if ( !$cur )
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->activity->table_name_meta} ( activity_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $activity_id, $meta_key, $meta_value ) );
	else if ( $cur->meta_value != $meta_value )
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name_meta} SET meta_value = %s WHERE activity_id = %d AND meta_key = %s", $meta_value, $activity_id, $meta_key ) );
	else
		return false;

	wp_cache_set( 'bp_activity_meta_' . $meta_key . '_' . $activity_id, $meta_value, 'bp' );

	return true;
}

/** Clean up ******************************************************************/

function bp_activity_remove_data( $user_id ) {
	// Clear the user's activity from the sitewide stream and clear their activity tables
	bp_activity_delete( array( 'user_id' => $user_id ) );

	// Remove any usermeta
	delete_user_meta( $user_id, 'bp_latest_update' );
	delete_user_meta( $user_id, 'bp_favorite_activities' );

	do_action( 'bp_activity_remove_data', $user_id );
}
add_action( 'wpmu_delete_user',  'bp_activity_remove_data' );
add_action( 'delete_user',       'bp_activity_remove_data' );
add_action( 'bp_make_spam_user', 'bp_activity_remove_data' );

/**
 * Register the activity stream actions for updates
 *
 * @global array $bp
 */
function updates_register_activity_actions() {
	global $bp;

	bp_activity_set_action( $bp->activity->id, 'activity_update', __( 'Posted an update', 'buddypress' ) );

	do_action( 'updates_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'updates_register_activity_actions' );

?>
