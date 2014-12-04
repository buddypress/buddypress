<?php

/**
 * BuddyPress taxonomy functions.
 *
 * Most BuddyPress taxonomy functions are wrappers for their WordPress counterparts.
 * Because BuddyPress can be activated in various ways in a network environment, we
 * must switch to the root blog before using the WP functions.
 *
 * @since BuddyPress (2.2.0)
 */

/**
 * Register our default taxonomies.
 *
 * @since BuddyPress (2.2.0)
 */
function bp_register_default_taxonomies() {
	// Member Type.
	register_taxonomy( 'bp_member_type', 'user', array(
		'public' => false,
	) );
}
add_action( 'bp_register_taxonomies', 'bp_register_default_taxonomies' );

/**
 * Set taxonomy terms on a BuddyPress object.
 *
 * @since BuddyPress (2.2.0)
 *
 * @see wp_set_object_terms() for a full description of function and parameters.
 *
 * @param int          $object_id Object ID.
 * @param string|array $terms     Term or terms to set.
 * @param string       $taxonomy  Taxonomy name.
 * @param bool         $append    Optional. True to append terms to existing terms. Default: false.
 * @return array Array of term taxonomy IDs.
 */
function bp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = wp_set_object_terms( $object_id, $terms, $taxonomy, $append );

	restore_current_blog();

	return $retval;
}

/**
 * Get taxonomy terms for a BuddyPress object.
 *
 * @since BuddyPress (2.2.0)
 *
 * @see wp_get_object_terms() for a full description of function and parameters.
 *
 * @param int|array    $object_ids ID or IDs of objects.
 * @param string|array $taxonomies Name or names of taxonomies to match.
 * @param array        $args       See {@see wp_get_object_terms()}.
 * @return array
 */
function bp_get_object_terms( $object_ids, $taxonomies, $args = array() ) {
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = wp_get_object_terms( $object_ids, $taxonomies, $args );

	restore_current_blog();

	return $retval;
}
