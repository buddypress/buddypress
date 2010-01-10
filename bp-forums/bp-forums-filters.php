<?php

/* Apply WordPress defined filters */
add_filter( 'bp_forums_bbconfig_location', 'wp_filter_kses', 1 );
add_filter( 'bp_forums_bbconfig_location', 'attribute_escape', 1 );

add_filter( 'bp_get_the_topic_title', 'bp_forums_filter_kses', 1 );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'bp_forums_filter_kses', 1 );
add_filter( 'bp_get_the_topic_post_content', 'bp_forums_filter_kses', 1 );

add_filter( 'bp_get_the_topic_title', 'wptexturize' );
add_filter( 'bp_get_the_topic_poster_name', 'wptexturize' );
add_filter( 'bp_get_the_topic_last_poster_name', 'wptexturize' );
add_filter( 'bp_get_the_topic_post_content', 'wptexturize' );
add_filter( 'bp_get_the_topic_post_poster_name', 'wptexturize' );

add_filter( 'bp_get_the_topic_title', 'convert_smilies' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'convert_smilies' );
add_filter( 'bp_get_the_topic_post_content', 'convert_smilies' );

add_filter( 'bp_get_the_topic_title', 'convert_chars' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'convert_chars' );
add_filter( 'bp_get_the_topic_post_content', 'convert_chars' );

add_filter( 'bp_get_the_topic_post_content', 'wpautop' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'wpautop' );

add_filter( 'bp_get_the_topic_post_content', 'stripslashes_deep' );
add_filter( 'bp_get_the_topic_title', 'stripslashes_deep' );
add_filter( 'bp_get_the_topic_latest_post_excerpt', 'stripslashes_deep' );

add_filter( 'bp_get_the_topic_post_content', 'make_clickable' );

add_filter( 'bp_get_forum_topic_count_for_user', 'number_format' );
add_filter( 'bp_get_forum_topic_count', 'number_format' );

function bp_forums_filter_kses( $content ) {
	global $allowedtags;

	$forums_allowedtags = $allowedtags;
	$forums_allowedtags['span'] = array();
	$forums_allowedtags['span']['class'] = array();
	$forums_allowedtags['div'] = array();
	$forums_allowedtags['div']['class'] = array();
	$forums_allowedtags['div']['id'] = array();
	$forums_allowedtags['a']['class'] = array();
	$forums_allowedtags['img'] = array();
	$forums_allowedtags['br'] = array();
	$forums_allowedtags['p'] = array();
	$forums_allowedtags['img']['src'] = array();
	$forums_allowedtags['img']['alt'] = array();
	$forums_allowedtags['img']['class'] = array();
	$forums_allowedtags['img']['width'] = array();
	$forums_allowedtags['img']['height'] = array();
	$forums_allowedtags['img']['class'] = array();
	$forums_allowedtags['img']['id'] = array();
	$forums_allowedtags['code'] = array();
	$forums_allowedtags['blockquote'] = array();

	$forums_allowedtags = apply_filters( 'bp_forums_allowed_tags', $forums_allowedtags );
	return wp_kses( $content, $forums_allowedtags );
}

function bp_forums_filter_tag_link( $link, $tag, $page, $context ) {
	global $bp;

	return apply_filters( 'bp_forums_filter_tag_link', $bp->root_domain . '/' . $bp->forums->slug . '/tag/' . $tag . '/' );
}
add_filter( 'bb_get_tag_link', 'bp_forums_filter_tag_link', 10, 4);

?>