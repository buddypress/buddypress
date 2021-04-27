<?php
/**
 * Activity: RSS feed actions
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 * @since 3.0.0
 */

/**
 * Load the sitewide activity feed.
 *
 * @since 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_sitewide_feed() {
	$bp = buddypress();

	if ( ! bp_is_activity_component() || ! bp_is_current_action( 'feed' ) || bp_is_user() || ! empty( $bp->groups->current_group ) ) {
		return false;
	}

	$link = bp_get_activity_directory_permalink();

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'sitewide',

		/* translators: %s Site Name */
		'title'         => sprintf( __( '%s | Site-Wide Activity', 'buddypress' ), bp_get_site_name() ),
		'link'          => $link,
		'description'   => __( 'Activity feed for the entire site.', 'buddypress' ),
		'activity_args' => 'display_comments=threaded'
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_sitewide_feed' );

/**
 * Load a user's personal activity feed.
 *
 * @since 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_personal_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'feed' ) ) {
		return false;
	}

	$link = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'personal',

		/* translators: 1: Site Name. 2: User Display Name. */
		'title'         => sprintf( _x( '%1$s | %2$s | Activity', 'Personal activity feed title', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),
		'link'          => $link,

		/* translators: %s: User Display Name */
		'description'   => sprintf( __( 'Activity feed for %s.', 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'user_id=' . bp_displayed_user_id()
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_personal_feed' );

/**
 * Load a user's friends' activity feed.
 *
 * @since 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_friends_feed() {
	if ( ! bp_is_active( 'friends' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_friends_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	$link = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'friends',

		/* translators: 1: Site Name 2: User Display Name */
		'title'         => sprintf( __( '%1$s | %2$s | Friends Activity', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),
		'link'          => $link,

		/* translators: %s: User Display Name */
		'description'   => sprintf( __( "Activity feed for %s's friends.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'scope=friends'
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_friends_feed' );

/**
 * Load the activity feed for a user's groups.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_my_groups_feed() {
	if ( ! bp_is_active( 'groups' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_groups_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Get displayed user's group IDs.
	$groups    = groups_get_user_groups();
	$group_ids = implode( ',', $groups['groups'] );
	$link      = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'mygroups',

		/* translators: 1: Site Name 2: User Display Name */
		'title'         => sprintf( __( '%1$s | %2$s | Group Activity', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),
		'link'          => $link,

		/* translators: %s: User Display Name */
		'description'   => sprintf( __( "Public group activity feed of which %s is a member.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => array(
			'object'           => buddypress()->groups->id,
			'primary_id'       => $group_ids,
			'display_comments' => 'threaded'
		)
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_my_groups_feed' );

/**
 * Load a user's @mentions feed.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_mentions_feed() {
	if ( ! bp_activity_do_mentions() ) {
		return false;
	}

	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'mentions' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	$link = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/mentions' );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'mentions',

		/* translators: 1: Site Name 2: User Display Name */
		'title'         => sprintf( __( '%1$s | %2$s | Mentions', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),
		'link'          => $link,

		/* translators: %s: User Display Name */
		'description'   => sprintf( __( "Activity feed mentioning %s.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => array(
			'search_terms' => '@' . bp_core_get_username( bp_displayed_user_id() )
		)
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_mentions_feed' );

/**
 * Load a user's favorites feed.
 *
 * @since 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_favorites_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'favorites' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Get displayed user's favorite activity IDs.
	$favs    = bp_activity_get_user_favorites( bp_displayed_user_id() );
	$fav_ids = implode( ',', (array) $favs );
	$link    = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/favorites' );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'favorites',

		/* translators: 1: Site Name 2: User Display Name */
		'title'         => sprintf( __( '%1$s | %2$s | Favorites', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),
		'link'          => $link,

		/* translators: %s: User Display Name */
		'description'   => sprintf( __( "Activity feed of %s's favorites.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'include=' . $fav_ids
	) );

	if ( ! buddypress()->activity->feed->enabled ) {
		bp_core_redirect( $link );
	}
}
add_action( 'bp_actions', 'bp_activity_action_favorites_feed' );
