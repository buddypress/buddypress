<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* Apply WordPress defined filters */

add_filter( 'bp_get_the_profile_group_name',          'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_group_description',   'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_value',         'xprofile_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_name',          'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_edit_value',    'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_description',   'wp_filter_kses',       1 );

add_filter( 'bp_get_the_profile_field_value',         'wptexturize'        );
add_filter( 'bp_get_the_profile_field_value',         'convert_smilies', 2 );
add_filter( 'bp_get_the_profile_field_value',         'convert_chars'      );
add_filter( 'bp_get_the_profile_field_value',         'wpautop'            );
add_filter( 'bp_get_the_profile_field_value',         'make_clickable', 8  );
add_filter( 'bp_get_the_profile_field_value',         'force_balance_tags' );

add_filter( 'bp_get_the_profile_field_edit_value',    'force_balance_tags' );
add_filter( 'bp_get_the_profile_field_edit_value',    'esc_html'           );

add_filter( 'bp_get_the_profile_group_name',          'stripslashes' );
add_filter( 'bp_get_the_profile_group_description',   'stripslashes' );
add_filter( 'bp_get_the_profile_field_value',         'stripslashes' );
add_filter( 'bp_get_the_profile_field_edit_value',    'stripslashes' );
add_filter( 'bp_get_the_profile_field_name',          'stripslashes' );
add_filter( 'bp_get_the_profile_field_description',   'stripslashes' );

add_filter( 'xprofile_get_field_data',                'wp_filter_kses', 1 );
add_filter( 'xprofile_field_name_before_save',        'wp_filter_kses', 1 );
add_filter( 'xprofile_field_description_before_save', 'wp_filter_kses', 1 );

add_filter( 'xprofile_get_field_data',                'force_balance_tags' );
add_filter( 'xprofile_field_name_before_save',        'force_balance_tags' );
add_filter( 'xprofile_field_description_before_save', 'force_balance_tags' );

add_filter( 'xprofile_get_field_data',                'stripslashes' );

/* Custom BuddyPress filters */

add_filter( 'bp_get_the_profile_field_value',         'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_site_member_profile_data',    'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_profile_field_value',         'xprofile_filter_link_profile_data', 9, 2 );

add_filter( 'xprofile_data_value_before_save',        'xprofile_sanitize_data_value_before_save', 1, 2 );
add_filter( 'xprofile_filtered_data_value_before_save', 'trim', 2 );

/**
 * xprofile_filter_kses ( $content )
 *
 * Run profile field values through kses with filterable allowed tags.
 *
 * @param string $content
 * @return string $content
 */
function xprofile_filter_kses( $content ) {
	global $allowedtags;

	$xprofile_allowedtags             = $allowedtags;
	$xprofile_allowedtags['a']['rel'] = array();

	$xprofile_allowedtags = apply_filters( 'xprofile_allowed_tags', $xprofile_allowedtags );
	return wp_kses( $content, $xprofile_allowedtags );
}

/**
 * xprofile_sanitize_data_value_before_save ( $field_value, $field_id )
 *
 * Safely runs profile field data through kses and force_balance_tags.
 *
 * @param string $field_value
 * @param int $field_id
 * @param bool $reserialize Whether to reserialize arrays before returning. Defaults to true
 * @return string
 */
function xprofile_sanitize_data_value_before_save ( $field_value, $field_id, $reserialize = true ) {

	// Return if empty
	if ( empty( $field_value ) )
		return;

	// Value might be serialized
	$field_value = maybe_unserialize( $field_value );

	// Filter single value
	if ( !is_array( $field_value ) ) {
		$kses_field_value     = xprofile_filter_kses( $field_value );
		$filtered_field_value = wp_rel_nofollow( force_balance_tags( $kses_field_value ) );
		$filtered_field_value = apply_filters( 'xprofile_filtered_data_value_before_save', $filtered_field_value, $field_value );

	// Filter each array item independently
	} else {
		$filtered_values = array();
		foreach ( (array)$field_value as $value ) {
			$kses_field_value       = xprofile_filter_kses( $value );
			$filtered_value 	= wp_rel_nofollow( force_balance_tags( $kses_field_value ) );
			$filtered_values[] = apply_filters( 'xprofile_filtered_data_value_before_save', $filtered_value, $value );

		}

		if ( $reserialize )
			$filtered_field_value = serialize( $filtered_values );
		else
			$filtered_field_value = $filtered_values;
	}

	return $filtered_field_value;
}

/**
 * xprofile_filter_format_field_value()
 *
 * Runs stripslashes on XProfile fields. If is field_type is 'datebox'
 * then the date will be formatted by bp_format_time().
 *
 * @since 1.0.0
 *
 * @param string $field_value XProfile field_value to be filtered.
 * @param string $field_type XProfile field_type to be filtered.
 *
 * @uses bp_format_time()
 *
 * @return string $field_value Filtered XProfile field_value. False on failure.
 */
function xprofile_filter_format_field_value( $field_value, $field_type = '' ) {
	if ( !isset( $field_value ) || empty( $field_value ) )
		return false;

	if ( 'datebox' == $field_type ) {

		// If Unix timestamp
		if ( is_numeric( $field_value ) )
			$field_value = bp_format_time( $field_value, true, false );

		// If MySQL timestamp
		else
			$field_value = bp_format_time( strtotime( $field_value ), true, false );

	} else {
		$field_value = str_replace(']]>', ']]&gt;', $field_value );
	}

	return stripslashes( $field_value );
}

function xprofile_filter_link_profile_data( $field_value, $field_type = 'textbox' ) {
	if ( 'datebox' == $field_type )
		return $field_value;

	if ( !strpos( $field_value, ',' ) && ( count( explode( ' ', $field_value ) ) > 5 ) )
		return $field_value;

	$values = explode( ',', $field_value );

	if ( $values ) {
		foreach ( (array)$values as $value ) {
			$value = trim( $value );

			// If the value is a URL, skip it and just make it clickable.
			if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
				$new_values[] = make_clickable( $value );
			} else {
				if ( count( explode( ' ', $value ) ) > 5 ) {
					$new_values[] = esc_html( $value );
				} else {
					$new_values[] = '<a href="' . site_url( bp_get_members_root_slug() ) . '/?s=' . esc_url( strip_tags( $value ) ) . '" rel="nofollow">' . esc_html( $value ) . '</a>';
				}
			}
		}

		$values = implode( ', ', $new_values );
	}

	return $values;
}

function xprofile_filter_comments( $comments, $post_id ) {
	foreach( (array)$comments as $comment ) {
		if ( $comment->user_id ) {
			$user_ids[] = $comment->user_id;
		}
	}

	if ( empty( $user_ids ) )
		return $comments;

	if ( $fullnames = BP_XProfile_ProfileData::get_value_byid( 1, $user_ids ) ) {
		foreach( (array)$fullnames as $user ) {
			$users[$user->user_id] = trim($user->value);
		}
	}

	foreach( (array)$comments as $i => $comment ) {
		if ( !empty( $comment->user_id ) ) {
			if ( !empty( $users[$comment->user_id] ) ) {
				$comments[$i]->comment_author = $users[$comment->user_id];
			}
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'xprofile_filter_comments', 10, 2 );

?>