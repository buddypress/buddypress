<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_activity_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_parent_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_latest_update', 'bp_activity_filter_kses', 1 );

add_filter( 'bp_get_activity_content', 'force_balance_tags' );
add_filter( 'bp_get_activity_latest_update', 'force_balance_tags' );

add_filter( 'bp_get_activity_content', 'wptexturize' );
add_filter( 'bp_get_activity_parent_content', 'wptexturize' );
add_filter( 'bp_get_activity_latest_update', 'wptexturize' );

add_filter( 'bp_get_activity_content', 'convert_smilies' );
add_filter( 'bp_get_activity_parent_content', 'convert_smilies' );
add_filter( 'bp_get_activity_latest_update', 'convert_smilies' );

add_filter( 'bp_get_activity_content', 'convert_chars' );
add_filter( 'bp_get_activity_parent_content', 'convert_chars' );
add_filter( 'bp_get_activity_latest_update', 'convert_chars' );

add_filter( 'bp_get_activity_content', 'wpautop' );

add_filter( 'bp_get_activity_content', 'make_clickable' );
add_filter( 'bp_get_activity_parent_content', 'make_clickable' );
add_filter( 'bp_get_activity_latest_update', 'make_clickable' );

add_filter( 'bp_get_activity_content', 'stripslashes_deep' );
add_filter( 'bp_get_activity_parent_content', 'stripslashes_deep' );
add_filter( 'bp_get_activity_latest_update', 'stripslashes_deep' );

add_filter( 'bp_get_activity_parent_content', 'bp_create_excerpt' );

/* Allow shortcodes in activity posts */
add_filter( 'bp_get_activity_content', 'do_shortcode' );

function bp_activity_filter_kses( $content ) {
	global $allowedtags;

	$activity_allowedtags = $allowedtags;
	$activity_allowedtags['span'] = array();
	$activity_allowedtags['span']['class'] = array();
	$activity_allowedtags['div'] = array();
	$activity_allowedtags['div']['class'] = array();
	$activity_allowedtags['div']['id'] = array();
	$activity_allowedtags['a']['class'] = array();
	$activity_allowedtags['img'] = array();
	$activity_allowedtags['img']['src'] = array();
	$activity_allowedtags['img']['alt'] = array();
	$activity_allowedtags['img']['class'] = array();
	$activity_allowedtags['img']['width'] = array();
	$activity_allowedtags['img']['height'] = array();
	$activity_allowedtags['img']['class'] = array();
	$activity_allowedtags['img']['id'] = array();

	return wp_kses( $content, $activity_allowedtags );
}

function bp_activity_at_name_filter( $content ) {
	include_once( ABSPATH . WPINC . '/registration.php' );

	$pattern = '/[@]+([A-Za-z0-9-_]+)/';
	preg_match_all( $pattern, $content, $usernames );

	/* Make sure there's only one instance of each username */
	if ( !$usernames = array_unique( $usernames[1] ) )
		return $content;

	foreach( (array)$usernames as $username ) {
		if ( !username_exists( $username ) )
			continue;

		$content = str_replace( "@$username", "<a href='" . bp_core_get_user_domain( bp_core_get_userid( $username ) ) . "' rel='nofollow'>@$username</a>", $content );
	}

	return $content;
}
add_filter( 'xprofile_activity_new_update_content', 'bp_activity_at_name_filter' );
add_filter( 'groups_activity_new_update_content', 'bp_activity_at_name_filter' );

?>