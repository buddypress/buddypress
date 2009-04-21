<?php

function members_to_developers() {
	return 'developers';
}
add_filter( 'bp_members_slug', 'members_to_developers' );

function get_recently_active_users( $limit = null, $page = 1 ) {
	global $wpdb;
	
	if ( $limit && $page )
		$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

	$paged_users = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT um.user_id FROM " . CUSTOM_USER_META_TABLE . " um LEFT JOIN " . CUSTOM_USER_TABLE . " u ON u.ID = um.user_id WHERE um.meta_key = 'last_activity' AND u.spam = 0 AND u.deleted = 0 AND u.user_status = 0 AND DATE_ADD( FROM_UNIXTIME(um.meta_value), INTERVAL 10 HOUR ) >= NOW() ORDER BY FROM_UNIXTIME(um.meta_value) DESC{$pag_sql}" ) );

	return $paged_users;
}

function custom_avatar_size() {
	return 200;
}
add_filter( 'bp_core_avatar_v2_h', 'custom_avatar_size' );
add_filter( 'bp_core_avatar_v2_w', 'custom_avatar_size' );

function custom_forum_parent() {
	return 7;
}
add_filter( 'bp_forums_parent_forum_id', 'custom_forum_parent' );

function give_bbpress_caps() {
	global $bp;

	if ( '' == get_usermeta( $bp->loggedin_user->id, 'buddybb_capabilities' ) ) {
		update_usermeta( $bp->loggedin_user->id, 'buddybb_capabilities', array( 'member' => true ) );
	}
}
add_action( 'wp', 'give_bbpress_caps' );

function register_user_as_editor($username) {
	global $bp, $wpdb;

	$user_id = bp_core_get_userid_from_user_login( $username );
	
	if ( !$user_id ) 
		return false;

	$role = maybe_unserialize( get_usermeta( $user_id, $wpdb->base_prefix . '1_capabilities' ) );		
	
	if ( is_array($role) )
		$role = $role[0];
	
	if ( !$role || '' == $role || 'subscriber' == $role ) {
		$role['contributor'] = 1;
		
		update_usermeta( $user_id, $wpdb->base_prefix . '1_capabilities', $role );
		update_usermeta( $user_id, 'primary_blog', 1 );
		update_usermeta( $user_id, 'source_domain', 'buddypress.org' );
	}

	$role = maybe_unserialize( get_usermeta( $user_id, $wpdb->base_prefix . '15_capabilities' ) );

	if ( is_array($role) )
        	$role = $role[0];

	if ( !$role || '' == $role || 'subscriber' == $role ) {
		$role['editor'] = 1;

	        update_usermeta( $user_id, $wpdb->base_prefix . '15_capabilities', $role );
	}
}
add_action( 'wp_login', 'register_user_as_editor' );

function add_wp_profile_data() {
	global $bp;

	if ( !$bp->displayed_user->id )
		return false;
	
	if ( '' == get_usermeta( $bp->displayed_user->id, 'last_activity' ) ) {
		$ud = get_userdata( $bp->displayed_user->id );
		
		if ( '' != $ud->user_url )
			xprofile_set_field_data( 'Website URL', $bp->displayed_user->id, $ud->user_url );

		if ( '' != $ud->from )
			xprofile_set_field_data( 'Current Location', $bp->displayed_user->id, $ud->from );
		
		if ( '' != $ud->interest )
			xprofile_set_field_data( 'Interests', $bp->displayed_user->id, $ud->interest );
	}
}
add_action( 'wp', 'add_wp_profile_data', 100 );

function remove_blogs_nav_item() {
	global $bp;
	
	unset($bp->bp_nav[3]);
	unset($bp->bp_users_nav[2]);

	if ( $bp->current_component == 'blogs' ) {
		bp_core_redirect( site_url() );
	}
	remove_action( 'bp_adminbar_menus', 'bp_adminbar_blogs_menu', 6 );
	remove_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu', 12 );
}
add_action( 'wp', 'remove_blogs_nav_item' );
