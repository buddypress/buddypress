<?php

/* Apply WordPress defined filters */
add_filter( 'bp_get_the_profile_field_value', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_name', 'wp_filter_kses', 1 );

add_filter( 'bp_get_the_site_member_profile_data', 'wp_filter_kses', 1 );
add_filter( 'xprofile_get_field_data', 'wp_filter_kses', 1 );
add_filter( 'xprofile_field_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'xprofile_field_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_description', 'wp_filter_kses', 1 );

add_filter( 'xprofile_field_name_before_save', 'force_balance_tags' );
add_filter( 'xprofile_field_description_before_save', 'force_balance_tags' );

add_filter( 'bp_get_the_profile_field_value', 'wptexturize' );
add_filter( 'bp_get_the_profile_field_value', 'convert_smilies', 2 );
add_filter( 'bp_get_the_profile_field_value', 'convert_chars' );
add_filter( 'bp_get_the_profile_field_value', 'wpautop' );
add_filter( 'bp_get_the_profile_field_value', 'make_clickable' );
add_filter( 'bp_get_the_profile_field_value', 'force_balance_tags' );

add_filter( 'bp_get_the_site_member_profile_data', 'wptexturize' );
add_filter( 'bp_get_the_site_member_profile_data', 'convert_smilies', 2 );
add_filter( 'bp_get_the_site_member_profile_data', 'convert_chars' );
add_filter( 'bp_get_the_site_member_profile_data', 'make_clickable' );
add_filter( 'bp_get_the_site_member_profile_data', 'force_balance_tags' );

add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_site_member_profile_data', 'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 50, 2 );

add_filter( 'bp_get_the_profile_field_edit_value', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_value', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_name', 'stripslashes' );
add_filter( 'xprofile_get_field_data', 'stripslashes' );
add_filter( 'bp_get_the_profile_field_description', 'stripslashes' );
add_filter( 'bp_get_the_site_member_profile_data', 'stripslashes' );

/* Custom BuddyPress filters */

function xprofile_filter_format_field_value( $field_value, $field_type = '' ) {
	if ( !isset( $field_value ) || empty( $field_value ) )
		return false;

	if ( 'datebox' == $field_type )
		$field_value = bp_format_time( $field_value, true );
	else
		$field_value = str_replace(']]>', ']]&gt;', $field_value );

	return stripslashes( stripslashes( $field_value ) );
}

function xprofile_filter_link_profile_data( $field_value, $field_type = 'textbox' ) {
	if ( 'datebox' == $field_type )
		return $field_value;

	if ( !strpos( $field_value, ',' ) && ( count( explode( ' ', $field_value ) ) > 5 ) )
		return $field_value;

	$values = explode( ',', $field_value );

	if ( $values ) {
		foreach ( (array) $values as $value ) {
			$value = trim( $value );

			/* If the value is a URL, skip it and just make it clickable. */
			if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
				$new_values[] = make_clickable( $value );
			} else {
				if ( count( explode( ' ', $value ) ) > 5 )
					$new_values[] = $value;
				else
					$new_values[] = '<a href="' . site_url( BP_MEMBERS_SLUG ) . '/?s=' . strip_tags( $value ) . '">' . $value . '</a>';
			}
		}

		$values = implode( ', ', $new_values );
	}

	return $values;
}

function xprofile_filter_comments( $comments, $post_id ) {
	foreach( (array)$comments as $comment ) {
		if ( $comment->user_id )
			$user_ids[] = $comment->user_id;
	}

	if ( empty( $user_ids ) )
		return $comments;

	if ( $fullnames = BP_XProfile_ProfileData::get_value_byid( 1, $user_ids ) ) {
		foreach( (array)$fullnames as $user ) {
			$users[$user->user_id] = trim( $user->value );
		}
	}

	foreach( (array)$comments as $i => $comment ) {
		if ( !empty( $comment->user_id ) ) {
			if ( !empty( $users[$comment->user_id] ) )
				$comments[$i]->comment_author = $users[$comment->user_id];
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'xprofile_filter_comments', 10, 2 );

?>