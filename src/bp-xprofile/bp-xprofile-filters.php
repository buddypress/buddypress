<?php

/**
 * BuddyPress XProfile Filters
 *
 * Apply WordPress defined filters
 *
 * @package BuddyPress
 * @subpackage XProfileFilters
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

add_filter( 'bp_get_the_profile_group_name',            'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_group_description',     'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_value',           'xprofile_filter_kses', 1 );
add_filter( 'bp_get_the_profile_field_name',            'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_edit_value',      'wp_filter_kses',       1 );
add_filter( 'bp_get_the_profile_field_description',     'wp_filter_kses',       1 );

add_filter( 'bp_get_the_profile_field_value',           'wptexturize'        );
add_filter( 'bp_get_the_profile_field_value',           'convert_chars'      );
add_filter( 'bp_get_the_profile_field_value',           'wpautop'            );
add_filter( 'bp_get_the_profile_field_value',           'force_balance_tags' );
add_filter( 'bp_get_the_profile_field_value',           'make_clickable'     );
add_filter( 'bp_get_the_profile_field_value',           'esc_html',        8 );
add_filter( 'bp_get_the_profile_field_value',           'convert_smilies', 9 );

add_filter( 'bp_get_the_profile_field_edit_value',      'force_balance_tags' );
add_filter( 'bp_get_the_profile_field_edit_value',      'esc_html'           );

add_filter( 'bp_get_the_profile_group_name',            'stripslashes' );
add_filter( 'bp_get_the_profile_group_description',     'stripslashes' );
add_filter( 'bp_get_the_profile_field_value',           'stripslashes' );
add_filter( 'bp_get_the_profile_field_edit_value',      'stripslashes' );
add_filter( 'bp_get_the_profile_field_name',            'stripslashes' );
add_filter( 'bp_get_the_profile_field_description',     'stripslashes' );

add_filter( 'xprofile_get_field_data',                  'wp_filter_kses', 1 );
add_filter( 'xprofile_field_name_before_save',          'wp_filter_kses', 1 );
add_filter( 'xprofile_field_description_before_save',   'wp_filter_kses', 1 );

add_filter( 'xprofile_get_field_data',                  'force_balance_tags' );
add_filter( 'xprofile_field_name_before_save',          'force_balance_tags' );
add_filter( 'xprofile_field_description_before_save',   'force_balance_tags' );

add_filter( 'xprofile_get_field_data',                  'stripslashes' );

add_filter( 'bp_get_the_profile_field_value',           'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_site_member_profile_data',      'xprofile_filter_format_field_value', 1, 2 );
add_filter( 'bp_get_the_profile_field_value',           'xprofile_filter_link_profile_data',  9, 2 );

add_filter( 'xprofile_data_value_before_save',          'xprofile_sanitize_data_value_before_save', 1, 2 );
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
 * Safely runs profile field data through kses and force_balance_tags.
 *
 * @param string $field_value
 * @param int $field_id
 * @param bool $reserialize Whether to reserialize arrays before returning. Defaults to true
 * @return string
 */
function xprofile_sanitize_data_value_before_save ( $field_value, $field_id, $reserialize = true ) {

	// Return if empty
	if ( empty( $field_value ) ) {
		return $field_value;
	}

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
		foreach ( (array) $field_value as $value ) {
			$kses_field_value       = xprofile_filter_kses( $value );
			$filtered_value 	= wp_rel_nofollow( force_balance_tags( $kses_field_value ) );
			$filtered_values[] = apply_filters( 'xprofile_filtered_data_value_before_save', $filtered_value, $value );

		}

		if ( !empty( $reserialize ) )
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
 * @since BuddyPress (1.0)
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
		if ( is_numeric( $field_value ) ) {
			$field_value = bp_format_time( $field_value, true, false );

		// If MySQL timestamp
		} else {
			$field_value = bp_format_time( strtotime( $field_value ), true, false );
		}

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

	if ( !empty( $values ) ) {
		foreach ( (array) $values as $value ) {
			$value = trim( $value );

			// If the value is a URL, skip it and just make it clickable.
			if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
				$new_values[] = make_clickable( $value );

			// Is not clickable
			} else {

				// More than 5 spaces
				if ( count( explode( ' ', $value ) ) > 5 ) {
					$new_values[] = $value;

				// Less than 5 spaces
				} else {
					$search_url   = add_query_arg( array( 's' => urlencode( $value ) ), bp_get_members_directory_permalink() );
					$new_values[] = '<a href="' . $search_url . '" rel="nofollow">' . $value . '</a>';
				}
			}
		}

		$values = implode( ', ', $new_values );
	}

	return $values;
}

/**
 * Ensures that BP data appears in comments array
 *
 * This filter loops through the comments return by a normal WordPress request
 * and swaps out user data with BP xprofile data, where available
 *
 * @param array $comments
 * @param int $post_id
 * @return array $comments
 */
function xprofile_filter_comments( $comments, $post_id ) {
	// Locate comment authors with WP accounts
	foreach( (array) $comments as $comment ) {
		if ( $comment->user_id ) {
			$user_ids[] = $comment->user_id;
		}
	}

	// If none are found, just return the comments array
	if ( empty( $user_ids ) ) {
		return $comments;
	}

	// Pull up the xprofile fullname of each commenter
	if ( $fullnames = bp_core_get_user_displaynames( $user_ids ) ) {
		foreach( (array) $fullnames as $user_id => $user_fullname ) {
			$users[ $user_id ] = trim( stripslashes( $user_fullname ) );
		}
	}

	// Loop through and match xprofile fullname with commenters
	foreach( (array) $comments as $i => $comment ) {
		if ( ! empty( $comment->user_id ) ) {
			if ( ! empty( $users[ $comment->user_id ] ) ) {
				$comments[ $i ]->comment_author = $users[ $comment->user_id ];
			}
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'xprofile_filter_comments', 10, 2 );

/**
 * Filter BP_User_Query::populate_extras to override each queries users fullname
 *
 * @since BuddyPress (1.7)
 *
 * @global BuddyPress $bp
 * @global WPDB $wpdb
 * @param BP_User_Query $user_query
 * @param string $user_ids_sql
 */
function bp_xprofile_filter_user_query_populate_extras( BP_User_Query $user_query, $user_ids_sql ) {
	global $bp, $wpdb;

	if ( ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	$user_id_names = bp_core_get_user_displaynames( $user_query->user_ids );

	// Loop through names and override each user's fullname
	foreach ( $user_id_names as $user_id => $user_fullname ) {
		if ( isset( $user_query->results[ $user_id ] ) ) {
			$user_query->results[ $user_id ]->fullname = $user_fullname;
		}
	}
}
add_filter( 'bp_user_query_populate_extras', 'bp_xprofile_filter_user_query_populate_extras', 2, 2 );

/**
 * Filter meta queries to modify for the xprofile data schema.
 *
 * @since BuddyPress (2.0.0)
 *
 * @access private Do not use.
 *
 * @param string $q SQL query.
 * @return string
 */
function bp_xprofile_filter_meta_query( $q ) {
	global $wpdb;

	// Get the first word of the command
	preg_match( '/^(\S+)/', $q, $first_word_matches );

	if ( empty( $first_word_matches[0] ) ) {
		return $q;
	}

	// Get the field type
	preg_match( '/xprofile_(group|field|data)_id/', $q, $matches );

	if ( empty( $matches[0] ) || empty( $matches[1] ) ) {
		return $q;
	}

	switch ( $first_word_matches[0] ) {

		/**
		 * SELECT:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - ensure that 'object_id' is aliased to 'xprofile_{fieldtype}_id',
		 *   because update_meta_cache() needs the column name to parse
		 *   the query results
		 * - append the 'object type' WHERE clause
		 */
		case 'SELECT' :
			$q = str_replace(
				array(
					$matches[0],
					'SELECT object_id',
					'WHERE ',
				),
				array(
					'object_id',
					'SELECT object_id AS ' . $matches[0],
					$wpdb->prepare( 'WHERE object_type = %s AND ', $matches[1] ),
				),
				$q
			);
			break;

		/**
		 * UPDATE and DELETE:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - append the 'object type' WHERE clause
		 */
		case 'UPDATE' :
		case 'DELETE' :
			$q = str_replace(
				array(
					$matches[0],
					'WHERE ',
				),
				array(
					'object_id',
					$wpdb->prepare( 'WHERE object_type = %s AND ', $matches[1] ),
				),
				$q
			);
			break;

		/**
		 * UPDATE and DELETE:
		 * - replace 'xprofile_{fieldtype}_id' with 'object_id'
		 * - ensure that the object_type field gets filled in
		 */
		case 'INSERT' :
			$q = str_replace(
				array(
					'`' . $matches[0] . '`',
					'VALUES (',
				),
				array(
					'`object_type`,`object_id`',
					$wpdb->prepare( "VALUES (%s,", $matches[1] ),
				),
				$q
			);
			break;
	}

	return $q;
}
