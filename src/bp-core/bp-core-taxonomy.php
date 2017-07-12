<?php
/**
 * BuddyPress taxonomy functions.
 *
 * Most BuddyPress taxonomy functions are wrappers for their WordPress counterparts.
 * Because BuddyPress can be activated in various ways in a network environment, we
 * must switch to the root blog before using the WP functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register our default taxonomies.
 *
 * @since 2.2.0
 */
function bp_register_default_taxonomies() {
	// Member Type.
	register_taxonomy( bp_get_member_type_tax_name(), 'user', array(
		'public' => false,
	) );

	// Email type.
	register_taxonomy(
		bp_get_email_tax_type(),
		bp_get_email_post_type(),
		apply_filters( 'bp_register_email_tax_type', array(
			'description'   => _x( 'BuddyPress email types', 'email type taxonomy description', 'buddypress' ),
			'labels'        => bp_get_email_tax_type_labels(),
			'meta_box_cb'   => 'bp_email_tax_type_metabox',
			'public'        => false,
			'query_var'     => false,
			'rewrite'       => false,
			'show_in_menu'  => false,
			'show_tagcloud' => false,
			'show_ui'       => bp_is_root_blog() && bp_current_user_can( 'bp_moderate' ),
		) )
	);
}
add_action( 'bp_register_taxonomies', 'bp_register_default_taxonomies' );

/**
 * Gets the ID of the site that BP should use for taxonomy term storage.
 *
 * Defaults to the root blog ID.
 *
 * @since 2.6.0
 *
 * @param string $taxonomy Taxonomy slug to check for.
 * @return int
 */
function bp_get_taxonomy_term_site_id( $taxonomy = '' ) {
	$site_id = bp_get_root_blog_id();

	/**
	 * Filters the ID of the site where BP should store taxonomy terms.
	 *
	 * @since 2.6.0
	 *
	 * @param int    $site_id  Site ID to cehck for.
	 * @param string $taxonomy Taxonomy slug to check for.
	 */
	return (int) apply_filters( 'bp_get_taxonomy_term_site_id', $site_id, $taxonomy );
}

/**
 * Set taxonomy terms on a BuddyPress object.
 *
 * @since 2.2.0
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
	$site_id = bp_get_taxonomy_term_site_id( $taxonomy );

	$switched = false;
	if ( $site_id !== get_current_blog_id() ) {
		switch_to_blog( $site_id );
		bp_register_taxonomies();
		$switched = true;
	}

	$tt_ids = wp_set_object_terms( $object_id, $terms, $taxonomy, $append );

	if ( $switched ) {
		restore_current_blog();
	}

	/**
	 * Fires when taxonomy terms have been set on BuddyPress objects.
	 *
	 * @since 2.7.0
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Term or terms to remove.
	 * @param array  $tt_ids    Array of term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy name.
	 */
	do_action( 'bp_set_object_terms', $object_id, $terms, $tt_ids, $taxonomy );

	return $tt_ids;
}

/**
 * Get taxonomy terms for a BuddyPress object.
 *
 * @since 2.2.0
 *
 * @see wp_get_object_terms() for a full description of function and parameters.
 *
 * @param int|array    $object_ids ID or IDs of objects.
 * @param string|array $taxonomies Name or names of taxonomies to match.
 * @param array        $args       See {@see wp_get_object_terms()}.
 * @return array
 */
function bp_get_object_terms( $object_ids, $taxonomies, $args = array() ) {
	// Different taxonomies must be stored on different sites.
	$taxonomy_site_map = array();
	foreach ( (array) $taxonomies as $taxonomy ) {
		$taxonomy_site_id = bp_get_taxonomy_term_site_id( $taxonomy );
		$taxonomy_site_map[ $taxonomy_site_id ][] = $taxonomy;
	}

	$retval = array();
	foreach ( $taxonomy_site_map as $taxonomy_site_id => $site_taxonomies ) {
		$switched = false;
		if ( $taxonomy_site_id !== get_current_blog_id() ) {
			switch_to_blog( $taxonomy_site_id );
			bp_register_taxonomies();
			$switched = true;
		}

		$site_terms = wp_get_object_terms( $object_ids, $site_taxonomies, $args );
		$retval     = array_merge( $retval, $site_terms );

		if ( $switched ) {
			restore_current_blog();
		}
	}

	return $retval;
}

/**
 * Remove taxonomy terms on a BuddyPress object.
 *
 * @since 2.3.0
 *
 * @see wp_remove_object_terms() for a full description of function and parameters.
 *
 * @param int          $object_id Object ID.
 * @param string|array $terms     Term or terms to remove.
 * @param string       $taxonomy  Taxonomy name.
 * @return bool|WP_Error True on success, false or WP_Error on failure.
 */
function bp_remove_object_terms( $object_id, $terms, $taxonomy ) {
	$site_id = bp_get_taxonomy_term_site_id( $taxonomy );

	$switched = false;
	if ( $site_id !== get_current_blog_id() ) {
		switch_to_blog( $site_id );
		bp_register_taxonomies();
		$switched = true;
	}

	$retval = wp_remove_object_terms( $object_id, $terms, $taxonomy );

	if ( $switched ) {
		restore_current_blog();
	}

	/**
	 * Fires when taxonomy terms have been removed from BuddyPress objects.
	 *
	 * @since 2.7.0
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Term or terms to remove.
	 * @param string $taxonomy  Taxonomy name.
	 */
	do_action( 'bp_remove_object_terms', $object_id, $terms, $taxonomy );

	return $retval;
}

/**
 * Retrieve IDs of objects in valid taxonomies and terms for BuddyPress-related taxonomies.
 *
 * Note that object IDs are from the `bp_get_taxonomy_term_site_id()`, which on some
 * multisite configurations may not be the same as the current site.
 *
 * @since 2.7.0
 *
 * @see get_objects_in_term() for a full description of function and parameters.
 *
 * @param int|array    $term_ids   Term id or array of term ids of terms that will be used.
 * @param string|array $taxonomies String of taxonomy name or Array of string values of taxonomy names.
 * @param array|string $args       Change the order of the object_ids, either ASC or DESC.
 *
 * @return WP_Error|array If the taxonomy does not exist, then WP_Error will be returned. On success,
 *                        the array can be empty, meaning that there are no $object_ids found. When
 *                        object IDs are found, an array of those IDs will be returned.
 */
function bp_get_objects_in_term( $term_ids, $taxonomies, $args = array() ) {
	// Different taxonomies may be stored on different sites.
	$taxonomy_site_map = array();
	foreach ( (array) $taxonomies as $taxonomy ) {
		$taxonomy_site_id = bp_get_taxonomy_term_site_id( $taxonomy );
		$taxonomy_site_map[ $taxonomy_site_id ][] = $taxonomy;
	}

	$retval = array();
	foreach ( $taxonomy_site_map as $taxonomy_site_id => $site_taxonomies ) {
		$switched = false;
		if ( $taxonomy_site_id !== get_current_blog_id() ) {
			switch_to_blog( $taxonomy_site_id );
			bp_register_taxonomies();
			$switched = true;
		}

		$site_objects = get_objects_in_term( $term_ids, $site_taxonomies, $args );
		$retval       = array_merge( $retval, $site_objects );

		if ( $switched ) {
			restore_current_blog();
		}
	}

	return $retval;
}

/**
 * Get term data for terms in BuddyPress taxonomies.
 *
 * Note that term data is from the `bp_get_taxonomy_term_site_id()`, which on some
 * multisite configurations may not be the same as the current site.
 *
 * @since 2.7.0
 *
 * @see get_term_by() for a full description of function and parameters.
 *
 * @param string     $field    Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'
 * @param string|int $value    Search for this term value
 * @param string     $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
 * @param string     $output   Constant OBJECT, ARRAY_A, or ARRAY_N
 * @param string     $filter   Optional, default is raw or no WordPress defined filter will applied.
 *
 * @return WP_Term|bool WP_Term instance on success. Will return false if `$taxonomy` does not exist
 *                      or `$term` was not found.
 */
function bp_get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
	$site_id = bp_get_taxonomy_term_site_id( $taxonomy );

	$switched = false;
	if ( $site_id !== get_current_blog_id() ) {
		switch_to_blog( $site_id );
		bp_register_taxonomies();
		$switched = true;
	}

	$term = get_term_by( $field, $value, $taxonomy, $output, $filter );

	if ( $switched ) {
		restore_current_blog();
	}

	return $term;
}
