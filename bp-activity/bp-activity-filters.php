<?php

/* Apply WordPress defined filters */
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

add_filter( 'bp_get_activity_action',                'make_clickable' );
add_filter( 'bp_get_activity_content_body',          'make_clickable' );
add_filter( 'bp_get_activity_content',               'make_clickable' );
add_filter( 'bp_get_activity_parent_content',        'make_clickable' );
add_filter( 'bp_get_activity_latest_update',         'make_clickable' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'make_clickable' );
add_filter( 'bp_get_activity_feed_item_description', 'make_clickable' );

add_filter( 'bp_acomment_name',                      'stripslashes_deep' );
add_filter( 'bp_get_activity_action',                'stripslashes_deep' );
add_filter( 'bp_get_activity_content',               'stripslashes_deep' );
add_filter( 'bp_get_activity_content_body',          'stripslashes_deep' );
add_filter( 'bp_get_activity_parent_content',        'stripslashes_deep' );
add_filter( 'bp_get_activity_latest_update',         'stripslashes_deep' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'stripslashes_deep' );
add_filter( 'bp_get_activity_feed_item_description', 'stripslashes_deep' );

/* Apply BuddyPress defined filters */
add_filter( 'bp_get_activity_content',               'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_content_body',          'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_parent_content',        'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update',         'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_feed_item_description', 'bp_activity_make_nofollow_filter' );

add_filter( 'bp_get_activity_parent_content', 'bp_create_excerpt' );

/* Allow shortcodes in activity posts */
add_filter( 'bp_get_activity_content', 'do_shortcode' );
add_filter( 'bp_get_activity_content_body', 'do_shortcode' );

function bp_activity_filter_kses( $content ) {
	global $allowedtags;

	$activity_allowedtags = $allowedtags;
	$activity_allowedtags['span']          = array();
	$activity_allowedtags['span']['class'] = array();
	$activity_allowedtags['div']           = array();
	$activity_allowedtags['div']['class']  = array();
	$activity_allowedtags['div']['id']     = array();
	$activity_allowedtags['a']['class']    = array();
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

function bp_activity_at_name_filter( $content ) {
	include_once( ABSPATH . WPINC . '/registration.php' );

	$pattern = '/[@]+([A-Za-z0-9-_\.]+)/';
	preg_match_all( $pattern, $content, $usernames );

	// Make sure there's only one instance of each username
	if ( !$usernames = array_unique( $usernames[1] ) )
		return $content;

	foreach( (array)$usernames as $username ) {
		if ( !$user_id = username_exists( $username ) )
			continue;

		// Increase the number of new @ mentions for the user
		$new_mention_count = (int)get_user_meta( $user_id, 'bp_new_mention_count', true );
		update_user_meta( $user_id, 'bp_new_mention_count', $new_mention_count + 1 );

		$content = str_replace( "@$username", "<a href='" . bp_core_get_user_domain( bp_core_get_userid( $username ) ) . "' rel='nofollow'>@$username</a>", $content );
	}

	return $content;
}
add_filter( 'bp_activity_new_update_content',     'bp_activity_at_name_filter' );
add_filter( 'groups_activity_new_update_content', 'bp_activity_at_name_filter' );
add_filter( 'pre_comment_content',                'bp_activity_at_name_filter' );
add_filter( 'group_forum_topic_text_before_save', 'bp_activity_at_name_filter' );
add_filter( 'group_forum_post_text_before_save',  'bp_activity_at_name_filter' );
add_filter( 'bp_activity_comment_content',        'bp_activity_at_name_filter' );

function bp_activity_make_nofollow_filter( $text ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bp_activity_make_nofollow_filter_callback', $text );
}
	function bp_activity_make_nofollow_filter_callback( $matches ) {
		$text = $matches[1];
		$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'"), '', $text );
		return "<a $text rel=\"nofollow\">";
	}

?>
