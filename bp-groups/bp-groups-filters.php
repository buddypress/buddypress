<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_group_description', 'wptexturize' );
add_filter( 'bp_get_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_group_name', 'wptexturize' );

add_filter( 'bp_get_group_description', 'convert_smilies' );
add_filter( 'bp_get_group_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_group_description', 'convert_chars' );
add_filter( 'bp_get_group_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_group_name', 'convert_chars' );

add_filter( 'bp_get_group_description', 'wpautop' );
add_filter( 'bp_get_group_description_excerpt', 'wpautop' );

add_filter( 'bp_get_group_description', 'make_clickable' );
add_filter( 'bp_get_group_description_excerpt', 'make_clickable' );

add_filter( 'bp_get_group_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_permalink', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_description', 'bp_groups_filter_kses', 1 );
add_filter( 'bp_get_group_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'groups_group_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'groups_group_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_group_description', 'stripslashes' );
add_filter( 'bp_get_group_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_group_name', 'stripslashes' );
add_filter( 'bp_get_group_member_name', 'stripslashes' );
add_filter( 'bp_get_group_member_link', 'stripslashes' );

add_filter( 'groups_new_group_forum_desc', 'bp_create_excerpt' );

add_filter( 'groups_group_name_before_save', 'force_balance_tags' );
add_filter( 'groups_group_description_before_save', 'force_balance_tags' );

add_filter( 'bp_get_total_group_count', 'bp_core_number_format' );
add_filter( 'bp_get_group_total_for_member', 'bp_core_number_format' );
add_filter( 'bp_get_group_total_members', 'bp_core_number_format' );

function bp_groups_filter_kses( $content ) {
	global $allowedtags;

	$groups_allowedtags = $allowedtags;
	$groups_allowedtags['a']['class'] = array();
	$groups_allowedtags['img'] = array();
	$groups_allowedtags['img']['src'] = array();
	$groups_allowedtags['img']['alt'] = array();
	$groups_allowedtags['img']['class'] = array();
	$groups_allowedtags['img']['width'] = array();
	$groups_allowedtags['img']['height'] = array();
	$groups_allowedtags['img']['class'] = array();
	$groups_allowedtags['img']['id'] = array();
	$groups_allowedtags['code'] = array();

	$groups_allowedtags = apply_filters( 'bp_groups_filter_kses', $groups_allowedtags );
	return wp_kses( $content, $groups_allowedtags );
}

/**** Filters for group forums ****/

function groups_add_forum_privacy_sql() {
	global $bp;

	/* Only filter the forum SQL on group pages or on the forums directory */
	if ( ( $bp->groups->current_group && 'public' == $bp->groups->current_group->status ) || !$bp->groups->current_group ) {
		add_filter( 'get_topics_fields', 'groups_add_forum_fields_sql' );
		add_filter( 'get_topics_index_hint', 'groups_add_forum_tables_sql' );
		add_filter( 'get_topics_where', 'groups_add_forum_where_sql' );
	}
}
add_filter( 'bbpress_init', 'groups_add_forum_privacy_sql' );

function groups_add_forum_fields_sql( $sql ) {
	return $sql . ', g.id as object_id, g.name as object_name, g.slug as object_slug';
}

function groups_add_forum_tables_sql( $sql ) {
	global $bp;
	return ', ' . $bp->groups->table_name . ' AS g LEFT JOIN ' . $bp->groups->table_name_groupmeta . ' AS gm ON g.id = gm.group_id ';
}

function groups_add_forum_where_sql( $sql ) {
	global $bp;

	$bp->groups->filter_sql = ' AND ' . $sql;
	return "(gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id) AND g.status = 'public' AND " . $sql;
}

function groups_filter_bbpress_caps( $value, $cap, $args ) {
	global $bp;

	if ( is_site_admin() )
		return true;

	if ( 'add_tag_to' == $cap )
		if ( $bp->groups->current_group->user_has_access ) return true;

	if ( 'manage_forums' == $cap && is_user_logged_in() )
		return true;

	return $value;
}
add_filter( 'bb_current_user_can', 'groups_filter_bbpress_caps', 10, 3 );

?>