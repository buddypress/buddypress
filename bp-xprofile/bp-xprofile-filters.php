<?php

/* Apply WordPress defined filters */
add_filter( 'bp_the_profile_field_value', 'wptexturize' );
add_filter( 'bp_the_profile_field_value', 'convert_smilies' );
add_filter( 'bp_the_profile_field_value', 'convert_chars' );
add_filter( 'bp_the_profile_field_value', 'wpautop' );
add_filter( 'bp_the_profile_field_value', 'xprofile_filter_format_field_value', 1, 2 );

add_filter( 'bp_the_profile_field_type', 'wptexturize' );
add_filter( 'bp_the_profile_field_type', 'convert_smilies' );
add_filter( 'bp_the_profile_field_type', 'convert_chars' );
add_filter( 'bp_the_profile_field_value', 'xprofile_filter_link_profile_data', 2, 3 );

/* Custom BuddyPress filters */

function xprofile_filter_format_field_value( $field_value, $field_type ) {
	if ( !isset($field_value) || $field_value == '' )
		return false;

	if ( $field_type == 'datebox' ) {
		$field_value = bp_format_time( $field_value, true );
	} else {
		$field_value = str_replace(']]>', ']]&gt;', $field_value );
	}
	
	return stripslashes( stripslashes( $field_value ) );
}


function xprofile_filter_link_profile_data( $field_value, $field_type, $field_id ) {
	if ( $field_type == 'datebox' )
		return $field_value;
	
	if ( !strpos( $field_value, ',' ) && ( count( explode( ' ', $field_value ) ) > 5 ) )
		return $field_value;
	
	$values = explode( ',', $field_value );

	if ( $values ) {
		foreach ( $values as $value ) {
			$value = trim( $value );
			
			if ( count( explode( ' ', $value ) ) > 5 )
				$new_values[] = $value;
			else
				$new_values[] = '<a href="' . site_url() . '/' . MEMBERS_SLUG . '/?s=' . $value . '">' . $value . '</a>';
		}
		
		$values = implode( ', ', $new_values );
	}
	
	return $values;
}

?>