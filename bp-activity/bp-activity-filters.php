<?php

/**
 * The Activity filters
 *
 * @package BuddyPress
 * @subpackage ActivityFilters
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Filters *******************************************************************/

// Apply WordPress defined filters
add_filter( 'bp_get_activity_action',                'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_content_body',          'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_content',               'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_parent_content',        'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_latest_update',         'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_feed_item_description', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_content_before_save',       'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_action_before_save',        'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_latest_update_content',     'wp_filter_kses', 1 );

add_filter( 'bp_get_activity_action',                'force_balance_tags' );
add_filter( 'bp_get_activity_content_body',          'force_balance_tags' );
add_filter( 'bp_get_activity_content',               'force_balance_tags' );
add_filter( 'bp_get_activity_latest_update',         'force_balance_tags' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'force_balance_tags' );
add_filter( 'bp_get_activity_feed_item_description', 'force_balance_tags' );
add_filter( 'bp_activity_content_before_save',       'force_balance_tags' );
add_filter( 'bp_activity_action_before_save',        'force_balance_tags' );

add_filter( 'bp_get_activity_action',                'wptexturize' );
add_filter( 'bp_get_activity_content_body',          'wptexturize' );
add_filter( 'bp_get_activity_content',               'wptexturize' );
add_filter( 'bp_get_activity_parent_content',        'wptexturize' );
add_filter( 'bp_get_activity_latest_update',         'wptexturize' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'wptexturize' );

add_filter( 'bp_get_activity_action',                'convert_smilies' );
add_filter( 'bp_get_activity_content_body',          'convert_smilies' );
add_filter( 'bp_get_activity_content',               'convert_smilies' );
add_filter( 'bp_get_activity_parent_content',        'convert_smilies' );
add_filter( 'bp_get_activity_latest_update',         'convert_smilies' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'convert_smilies' );

add_filter( 'bp_get_activity_action',                'convert_chars' );
add_filter( 'bp_get_activity_content_body',          'convert_chars' );
add_filter( 'bp_get_activity_content',               'convert_chars' );
add_filter( 'bp_get_activity_parent_content',        'convert_chars' );
add_filter( 'bp_get_activity_latest_update',         'convert_chars' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'convert_chars' );

add_filter( 'bp_get_activity_action',                'wpautop' );
add_filter( 'bp_get_activity_content_body',          'wpautop' );
add_filter( 'bp_get_activity_content',               'wpautop' );
add_filter( 'bp_get_activity_feed_item_description', 'wpautop' );

add_filter( 'bp_get_activity_action',                'make_clickable', 9 );
add_filter( 'bp_get_activity_content_body',          'make_clickable', 9 );
add_filter( 'bp_get_activity_content',               'make_clickable', 9 );
add_filter( 'bp_get_activity_parent_content',        'make_clickable', 9 );
add_filter( 'bp_get_activity_latest_update',         'make_clickable', 9 );
add_filter( 'bp_get_activity_latest_update_excerpt', 'make_clickable', 9 );
add_filter( 'bp_get_activity_feed_item_description', 'make_clickable', 9 );

add_filter( 'bp_acomment_name',                      'stripslashes_deep' );
add_filter( 'bp_get_activity_action',                'stripslashes_deep' );
add_filter( 'bp_get_activity_content',               'stripslashes_deep' );
add_filter( 'bp_get_activity_content_body',          'stripslashes_deep' );
add_filter( 'bp_get_activity_parent_content',        'stripslashes_deep' );
add_filter( 'bp_get_activity_latest_update',         'stripslashes_deep' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'stripslashes_deep' );
add_filter( 'bp_get_activity_feed_item_description', 'stripslashes_deep' );

add_filter( 'bp_activity_primary_link_before_save',  'esc_url_raw' );

// Apply BuddyPress defined filters
add_filter( 'bp_get_activity_content',               'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_content_body',          'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_parent_content',        'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update',         'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_feed_item_description', 'bp_activity_make_nofollow_filter' );

add_filter( 'pre_comment_content',                   'bp_activity_at_name_filter' );
add_filter( 'group_forum_topic_text_before_save',    'bp_activity_at_name_filter' );
add_filter( 'group_forum_post_text_before_save',     'bp_activity_at_name_filter' );
add_filter( 'the_content',                           'bp_activity_at_name_filter' );

add_filter( 'bp_get_activity_parent_content',        'bp_create_excerpt' );

add_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
add_filter( 'bp_get_activity_content',      'bp_activity_truncate_entry', 5 );

/** Actions *******************************************************************/

// At-name filter
add_action( 'bp_activity_before_save', 'bp_activity_at_name_filter_updates' );

// Activity stream moderation
add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2, 1 );
add_action( 'bp_activity_before_save', 'bp_activity_check_blacklist_keys',  2, 1 );

/** Functions *****************************************************************/

/**
 * Types of activity stream items to check against
 *
 * @since BuddyPress (1.6)
 */
function bp_activity_get_moderated_activity_types() {
	$types = array(
		'activity_comment',
		'activity_update'
	);
	return apply_filters( 'bp_activity_check_activity_types', $types );
}

/**
 * Check activity stream for moderation keys
 *
 * @since BuddyPress (1.6)
 * @param BP_Activity_Activity $activity
 * @return If activity type is not an update or comment
 */
function bp_activity_check_moderation_keys( $activity ) {

	// Only check specific types of activity updates
	if ( !in_array( $activity->type, bp_activity_get_moderated_activity_types() ) )
		return;

	// Unset the activity component so activity stream update fails
	// @todo This is temporary until some kind of moderation is built
	if ( !bp_core_check_for_moderation( $activity->user_id, '', $activity->content ) )
		$activity->component = false;
}

/**
 * Check activity stream for blacklisted keys
 *
 * @since BuddyPress (1.6)
 * @param BP_Activity_Activity $activity
 * @return If activity type is not an update or comment
 */
function bp_activity_check_blacklist_keys( $activity ) {

	// Only check specific types of activity updates
	if ( ! in_array( $activity->type, bp_activity_get_moderated_activity_types() ) )
		return;

	// Mark as spam
	if ( ! bp_core_check_for_blacklist( $activity->user_id, '', $activity->content ) )
		bp_activity_mark_as_spam( $activity, 'by_blacklist' );
}

/**
 * Custom kses filtering for activity content
 *
 * @since BuddyPress (1.1)
 *
 * @param string $content The activity content
 *
 * @uses apply_filters() To call the 'bp_activity_allowed_tags' hook.
 * @uses wp_kses()
 *
 * @return string $content Filtered activity content
 */
function bp_activity_filter_kses( $content ) {
	global $allowedtags;

	$activity_allowedtags = $allowedtags;
	$activity_allowedtags['span']          = array();
	$activity_allowedtags['span']['class'] = array();
	$activity_allowedtags['a']['class']    = array();
	$activity_allowedtags['a']['id']       = array();
	$activity_allowedtags['a']['rel']      = array();
	$activity_allowedtags['img']           = array();
	$activity_allowedtags['img']['src']    = array();
	$activity_allowedtags['img']['alt']    = array();
	$activity_allowedtags['img']['class']  = array();
	$activity_allowedtags['img']['width']  = array();
	$activity_allowedtags['img']['height'] = array();
	$activity_allowedtags['img']['class']  = array();
	$activity_allowedtags['img']['id']     = array();
	$activity_allowedtags['img']['title']  = array();
	$activity_allowedtags['code']          = array();

	$activity_allowedtags = apply_filters( 'bp_activity_allowed_tags', $activity_allowedtags );
	return wp_kses( $content, $activity_allowedtags );
}

/**
 * Finds and links @-mentioned users in the contents of a given item.
 *
 * @since BuddyPress (1.2)
 *
 * @param string $content The contents of a given item.
 * @param int $activity_id The activity id. Deprecated.
 *
 * @uses bp_activity_find_mentions()
 * @uses bp_core_get_user_domain()
 *
 * @return string $content Content filtered for mentions
 */
function bp_activity_at_name_filter( $content, $activity_id = 0 ) {

	// Try to find mentions
	$usernames = bp_activity_find_mentions( $content );

	// No mentions? Stop now!
	if ( empty( $usernames ) )
		return $content;

	// Linkify the mentions with the username
	foreach( (array) $usernames as $user_id => $username ) {
		$content = preg_replace( '/(@' . $username . '\b)/', "<a href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $content );
	}

	// Return the content
	return $content;
}

/**
 * Catch mentions in activity items before they are saved into the database.
 *
 * If mentions are found, replace @mention text with user links and add our
 * hook to send mentions after the activity item is saved.
 *
 * @since BuddyPress (1.5)
 *
 * @param obj $activity
 *
 * @uses bp_activity_find_mentions()
 */
function bp_activity_at_name_filter_updates( $activity ) {
	// If activity was marked as spam, stop the rest of this function.
	if ( ! empty( $activity->is_spam ) )
		return;

	// Try to find mentions
	$usernames = bp_activity_find_mentions( $activity->content );

	// We have mentions!
	if ( ! empty( $usernames ) ) {
		// Replace @mention text with userlinks
		foreach( (array) $usernames as $user_id => $username ) {
			$activity->content = preg_replace( '/(@' . $username . '\b)/', "<a href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $activity->content );
		}

		// Add our hook to send @mention emails after the activity item is saved
		add_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		// temporary variable to avoid having to run bp_activity_find_mentions() again
		buddypress()->activity->mentioned_users = $usernames;
	}
}

/**
 * Sends emails and BP notifications for @-mentioned users in the contents of
 * an activity item.
 *
 * @since BuddyPress (1.7)
 *
 * @param obj $activity The BP_Activity_Activity object
 *
 * @uses bp_activity_at_message_notification()
 * @uses bp_activity_update_mention_count_for_user()
 */
function bp_activity_at_name_send_emails( $activity ) {
	// If our temporary variable doesn't exist, stop now.
	if ( empty( buddypress()->activity->mentioned_users ) )
		return;

	// Grab our temporary variable from bp_activity_at_name_filter_updates()
	$usernames = buddypress()->activity->mentioned_users;

	// Get rid of temporary variable
	unset( buddypress()->activity->mentioned_users );

	// Send @mentions and setup BP notifications
	foreach( (array) $usernames as $user_id => $username ) {
		// If you want to disable notifications, you can use this filter to stop email sending
		if ( apply_filters( 'bp_activity_at_name_do_notifications', true, $usernames ) ) {
			bp_activity_at_message_notification( $activity->id, $user_id );
		}

		// Updates mention count for the user
		bp_activity_update_mention_count_for_user( $user_id, $activity->id );
	}
}

/**
 * Catches links in activity text so rel=nofollow can be added
 *
 * @since BuddyPress (1.2)
 *
 * @param string $text Activity text
 *
 * @return string $text Text with rel=nofollow added to any links
 */
function bp_activity_make_nofollow_filter( $text ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bp_activity_make_nofollow_filter_callback', $text );
}

	/**
	 * Adds rel=nofollow to a link
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @param array $matches
	 *
	 * @return string $text Link with rel=nofollow added
	 */
	function bp_activity_make_nofollow_filter_callback( $matches ) {
		$text = $matches[1];
		$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'"), '', $text );
		return "<a $text rel=\"nofollow\">";
	}

/**
 * Truncates long activity entries when viewed in activity streams
 *
 * @since BuddyPress (1.5)
 *
 * @param $text The original activity entry text
 *
 * @uses bp_is_single_activity()
 * @uses apply_filters() To call the 'bp_activity_excerpt_append_text' hook
 * @uses apply_filters() To call the 'bp_activity_excerpt_length' hook
 * @uses bp_create_excerpt()
 * @uses bp_get_activity_id()
 * @uses bp_get_activity_thread_permalink()
 * @uses apply_filters() To call the 'bp_activity_truncate_entry' hook
 *
 * @return string $excerpt The truncated text
 */
function bp_activity_truncate_entry( $text ) {
	global $activities_template;

	// The full text of the activity update should always show on the single activity screen
	if ( bp_is_single_activity() )
		return $text;

	$append_text    = apply_filters( 'bp_activity_excerpt_append_text', __( '[Read more]', 'buddypress' ) );
	$excerpt_length = apply_filters( 'bp_activity_excerpt_length', 358 );

	// Run the text through the excerpt function. If it's too short, the original text will be
	// returned.
	$excerpt        = bp_create_excerpt( $text, $excerpt_length, array( 'ending' => __( '&hellip;', 'buddypress' ) ) );

	// If the text returned by bp_create_excerpt() is different from the original text (ie it's
	// been truncated), add the "Read More" link. Note that bp_create_excerpt() is stripping
	// shortcodes, so we have strip them from the $text before the comparison
	if ( $excerpt != strip_shortcodes( $text ) ) {
		$id = !empty( $activities_template->activity->current_comment->id ) ? 'acomment-read-more-' . $activities_template->activity->current_comment->id : 'activity-read-more-' . bp_get_activity_id();

		$excerpt = sprintf( '%1$s<span class="activity-read-more" id="%2$s"><a href="%3$s" rel="nofollow">%4$s</a></span>', $excerpt, $id, bp_get_activity_thread_permalink(), $append_text );
	}

	return apply_filters( 'bp_activity_truncate_entry', $excerpt, $text, $append_text );
}
