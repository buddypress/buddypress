<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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

add_filter( 'bp_get_activity_parent_content',        'bp_create_excerpt' );

function bp_activity_filter_kses( $content ) {
	global $allowedtags;

	$activity_allowedtags = $allowedtags;
	$activity_allowedtags['span']          = array();
	$activity_allowedtags['span']['class'] = array();
	$activity_allowedtags['div']           = array();
	$activity_allowedtags['div']['class']  = array();
	$activity_allowedtags['div']['id']     = array();
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
 * Finds and links @-mentioned users in the contents of activity items
 *
 * @package BuddyPress Activity
 *
 * @param string $content The activity content
 * @param int $activity_id When $adjust_mention_count is true, you must provide an $activity_id,
 *   which will be added to the list of the user's unread mentions
 */
function bp_activity_at_name_filter( $content, $activity_id = 0 ) {
	$usernames = bp_activity_find_mentions( $content );

	foreach( (array)$usernames as $username ) {
		if ( bp_is_username_compatibility_mode() )
			$user_id = username_exists( $username );
		else
			$user_id = bp_core_get_userid_from_nicename( $username );

		if ( empty( $user_id ) )
			continue;

		// If an activity_id is provided, we can send email and BP notifications
		if ( $activity_id ) {
			bp_activity_at_message_notification( $activity_id, $user_id );
		}

		$content = preg_replace( '/(@' . $username . '\b)/', "<a href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $content );
	}

	// Adjust the activity count for this item
	if ( $activity_id )
		bp_activity_adjust_mention_count( $activity_id, 'add' );

	return $content;
}

/**
 * Catch mentions in saved activity items
 *
 * @package BuddyPress
 * @since 1.5
 *
 * @param obj $activity
 */
function bp_activity_at_name_filter_updates( $activity ) {
	// Only run this function once for a given activity item
	remove_filter( 'bp_activity_after_save', 'bp_activity_at_name_filter_updates' );

	// Run the content through the linking filter, making sure to increment mention count
	$activity->content = bp_activity_at_name_filter( $activity->content, $activity->id );

	// Resave the activity with the new content
	$activity->save();
}
add_filter( 'bp_activity_after_save', 'bp_activity_at_name_filter_updates' );

function bp_activity_make_nofollow_filter( $text ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bp_activity_make_nofollow_filter_callback', $text );
}
	function bp_activity_make_nofollow_filter_callback( $matches ) {
		$text = $matches[1];
		$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'"), '', $text );
		return "<a $text rel=\"nofollow\">";
	}

/**
 * Truncates long activity entries when viewed in activity streams
 *
 * @package BuddyPress Activity
 * @since 1.5
 * @param $text The original activity entry text
 * @return $excerpt The truncated text
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
	// been truncated), add the "Read More" link.
	if ( $excerpt != $text ) {
		$id = !empty( $activities_template->activity->current_comment->id ) ? 'acomment-read-more-' . $activities_template->activity->current_comment->id : 'activity-read-more-' . bp_get_activity_id();

		$excerpt = sprintf( '%1$s<span class="activity-read-more" id="%2$s"><a href="%3$s" rel="nofollow">%4$s</a></span>', $excerpt, $id, bp_get_activity_thread_permalink(), $append_text );
	}

	return apply_filters( 'bp_activity_truncate_entry', $excerpt, $text, $append_text );
}
add_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
add_filter( 'bp_get_activity_content', 'bp_activity_truncate_entry', 5 );
?>