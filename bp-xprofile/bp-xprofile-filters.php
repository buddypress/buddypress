<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_the_profile_field_value', 'wp_filter_kses', 1 );
add_filter( 'xprofile_get_field_data', 'wp_filter_kses', 1 );
add_filter( 'xprofile_field_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'xprofile_field_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_the_profile_field_value', 'wptexturize' );
add_filter( 'bp_get_the_profile_field_value', 'convert_smilies', 2 );
add_filter( 'bp_get_the_profile_field_value', 'convert_chars' );
add_filter( 'bp_get_the_profile_field_value', 'wpautop' );
add_filter( 'bp_get_the_profile_field_value', 'make_clickable' );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 2, 2 );

/* Custom BuddyPress filters */

function xprofile_filter_format_field_value( $field_value, $field_type = '' ) {
	if ( !isset($field_value) || empty( $field_value ) )
		return false;

	if ( 'datebox' == $field_type ) {
		$field_value = bp_format_time( $field_value, true );
	} else {
		$field_value = str_replace(']]>', ']]&gt;', $field_value );
	}
	
	return stripslashes( stripslashes( $field_value ) );
}

function xprofile_filter_link_profile_data( $field_value, $field_type = 'textbox' ) {
	if ( 'datebox' == $field_type )
		return $field_value;
	
	if ( !strpos( $field_value, ',' ) && ( count( explode( ' ', $field_value ) ) > 5 ) )
		return $field_value;
	
	$values = explode( ',', $field_value );

	if ( $values ) {
		foreach ( $values as $value ) {
			$value = trim( $value );
			
			/* If the value is a URL, skip it and just make it clickable. */
			if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
				$new_values[] = make_clickable( $value );
			} else {
				if ( count( explode( ' ', $value ) ) > 5 )
					$new_values[] = $value;
				else
					$new_values[] = '<a href="' . site_url( BP_MEMBERS_SLUG ) . '/?s=' . $value . '">' . $value . '</a>';
			}
		}
		
		$values = implode( ', ', $new_values );
	}
	
	return $values;
}

function xprofile_sync_wp_profile() {
	global $bp, $wpdb;
	
	if ( (int)get_site_option( 'bp-disable-profile-sync' ) )
		return true;
	
	$fullname = xprofile_get_field_data( BP_XPROFILE_FULLNAME_FIELD_NAME, $bp->loggedin_user->id );
	$space = strpos( $fullname, ' ' );
	
	if ( false === $space ) {
		$firstname = $fullname;
		$lastname = '';
	} else {
		$firstname = substr( $fullname, 0, $space );
		$lastname = trim( substr( $fullname, $space, strlen($fullname) ) );		
	}
	
	update_usermeta( $bp->loggedin_user->id, 'nickname', $fullname );
	update_usermeta( $bp->loggedin_user->id, 'first_name', $firstname );
	update_usermeta( $bp->loggedin_user->id, 'last_name', $lastname );

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET display_name = %s WHERE ID = %d", $fullname, $bp->loggedin_user->id ) );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_url = %s WHERE ID = %d", bp_core_get_user_domain( $bp->loggedin_user->id ), $bp->loggedin_user->id ) );
}
add_action( 'xprofile_updated_profile', 'xprofile_sync_wp_profile' );


?>