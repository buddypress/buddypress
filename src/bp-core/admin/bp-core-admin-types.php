<?php
/**
 * BuddyPress Types Admin functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 7.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get default values for the taxonomy registered metadata.
 *
 * @since 7.0.0
 *
 * @param string $type_taxonomy The type's taxonomy name.
 * @return array                Default values for the taxonomy registered metadata.
 */
function bp_core_admin_get_type_default_meta_values( $type_taxonomy ) {
	$metadata_schema = bp_get_type_metadata_schema( false, $type_taxonomy );
	$metadata        = wp_list_pluck( $metadata_schema, 'type' );

	// Set default values according to their schema type.
	foreach ( $metadata as $meta_key => $meta_value ) {
		if ( in_array( $meta_value, array( 'boolean', 'integer' ), true ) ) {
			$metadata[ $meta_key ] = 0;
		} else {
			$metadata[ $meta_key ] = '';
		}
	}

	return $metadata;
}

/**
 * Insert a new type into the database.
 *
 * @since 7.0.0
 *
 * @param array  $args {
 *     Array of arguments describing the object type.
 *
 *     @type string $taxonomy   The Type's taxonomy. Required.
 *     @type string $bp_type_id Unique string identifier for the member type. Required.
 *     @see keys of the array returned by bp_get_type_metadata_schema() for the other arguments.
 * }
 * @return integer|WP_Error The Type's term ID on success. A WP_Error object otherwise.
 */
function bp_core_admin_insert_type( $args = array() ) {
	$default_args = array(
		'taxonomy'   => '',
		'bp_type_id' => '',
	);

	$args = array_map( 'wp_unslash', $args );
	$args = bp_parse_args(
		$args,
		$default_args,
		'admin_insert_type'
	);

	if ( ! $args['bp_type_id'] || ! $args['taxonomy'] ) {
		 return new WP_Error(
			 'invalid_type_taxonomy',
			 __( 'The Type ID value is missing', 'buddypress' ),
			 array(
				'message' => 1,
			 )
		);
	}

	$type_id       = sanitize_title( $args['bp_type_id'] );
	$type_taxonomy = sanitize_key( $args['taxonomy'] );

	/**
	 * Filter here to check for an already existing type.
	 *
	 * @since 7.0.0
	 *
	 * @param boolean $value   True if the type exists. False otherwise.
	 * @param string  $type_id The Type's ID.
	 */
	$type_exists = apply_filters( "{$type_taxonomy}_check_existing_type", false, $type_id );

	if ( false !== $type_exists ) {
		return new WP_Error(
			'type_already_exists',
			__( 'The Type already exists', 'buddypress' ),
			array(
			   'message' => 5,
			)
	   );
	}

	// Get defaulte values for metadata.
	$metadata = bp_core_admin_get_type_default_meta_values( $type_taxonomy );

	// Validate metadata
	$metas = array_filter( array_intersect_key( $args, $metadata ) );

	// Insert the Type into the database.
	$type_term_id = bp_insert_term(
		$type_id,
		$type_taxonomy,
		array(
			'slug'  => $type_id,
			'metas' => $metas,
		)
	);

	if ( is_wp_error( $type_term_id ) ) {
		$type_term_id->add_data(
			array(
				'message' => 3,
			)
		);

		return $type_term_id;
	}

	/**
	 * Hook here to add code once the type has been inserted.
	 *
	 * @since 7.0.0
	 *
	 * @param integer $type_term_id  The Type's term_ID.
	 * @param string  $type_taxonomy The Type's taxonomy name.
	 * @param string  $type_id       The Type's ID.
	 */
	do_action( 'bp_type_inserted', $type_term_id, $type_taxonomy, $type_id );

	// Finally return the inserted Type's term ID.
	return $type_term_id;
}

/**
 * Update a type into the database.
 *
 * @since 7.0.0
 *
 * @param array  $args {
 *     Array of arguments describing the object type.
 *
 *     @type string  $taxonomy     The Type's taxonomy. Required.
 *     @type integer $type_term_id The Type's term ID. Required.
 *     @see keys of the array returned by bp_get_type_metadata_schema() for the other arguments.
 * }
 * @return boolean|WP_Error True on success. A WP_Error object otherwise.
 */
function bp_core_admin_update_type( $args = array() ) {
	$default_args = array(
		'taxonomy'     => '',
		'type_term_id' => 0,
	);

	$args = array_map( 'wp_unslash', $args );
	$args = bp_parse_args(
		$args,
		$default_args,
		'admin_update_type'
	);

	if ( ! $args['type_term_id'] || ! $args['taxonomy'] ) {
		 return new WP_Error(
			 'invalid_type_taxonomy',
			 __( 'The Term Type ID value is missing', 'buddypress' ),
			 array(
				'message' => 10,
			)
		);
	}

	$type_term_id  = (int) $args['type_term_id'];
	$type_taxonomy = sanitize_key( $args['taxonomy'] );

	// Get defaulte values for metadata.
	$metadata  = bp_core_admin_get_type_default_meta_values( $type_taxonomy );

	// Merge customs with defaults.
	$metas = bp_parse_args(
		$args,
		$metadata
	);

	// Validate metadata.
	$metas = array_intersect_key( $metas, $metadata );

	foreach ( $metas as $meta_key => $meta_value ) {
		if ( '' === $meta_value ) {
			delete_term_meta( $type_term_id, $meta_key );
		} else {
			update_term_meta( $type_term_id, $meta_key, $meta_value );
		}
	}

	/**
	 * Hook here to add code once the type has been updated.
	 *
	 * @since 7.0.0
	 *
	 * @param integer $type_term_id  The Type's term_ID.
	 * @param string  $type_taxonomy The Type's taxonomy name.
	 */
	do_action( 'bp_type_updated', $type_term_id, $type_taxonomy );

	// Finally informs about the successfull update.
	return true;
}

/**
 * Delete a type from the database.
 *
 * @since 7.0.0
 *
 * @param array  $args {
 *     Array of arguments describing the object type.
 *
 *     @type string  $taxonomy     The Type's taxonomy. Required.
 *     @type integer $type_term_id The Type's term ID. Required.
 * }
 * @return boolean|WP_Error True on success. A WP_Error object otherwise.
 */
function bp_core_admin_delete_type( $args = array() ) {
	$default_args = array(
		'taxonomy'     => '',
		'type_term_id' => 0,
	);

	$args = array_map( 'wp_unslash', $args );
	$args = bp_parse_args(
		$args,
		$default_args,
		'admin_delete_type'
	);

	if ( ! $args['type_term_id'] || ! $args['taxonomy'] ) {
		 return new WP_Error(
			 'invalid_type_taxonomy',
			 __( 'The Term Type ID value is missing', 'buddypress' ),
			 array(
				'message' => 10,
			)
		);
	}

	$type_term_id  = (int) $args['type_term_id'];
	$type_taxonomy = sanitize_key( $args['taxonomy'] );
	$type_term     = bp_get_term_by( 'id', $type_term_id, $type_taxonomy );

	if ( ! $type_term ) {
		return new WP_Error(
			'type_doesnotexist',
			__( 'The type was not deleted: it does not exist.', 'buddypress' ),
			array(
			   'message' => 6,
			)
		);
	}

	/** This filter is documented in bp-core/classes/class-bp-admin-types.php */
	$registered_by_code_types = apply_filters( "{$type_taxonomy}_registered_by_code", array() );

	if ( isset( $registered_by_code_types[ $type_term->name ] ) ) {
		return new WP_Error(
			'type_register_by_code',
			__( 'This type is registered using code, deactivate the plugin or remove the custom code before trying to delete it again.', 'buddypress' ),
			array(
			   'message' => 7,
			)
		);
	}

	$deleted = bp_delete_term( $type_term_id, $type_taxonomy );

	if ( true !== $deleted ) {
		return new WP_Error(
			'type_not_deleted',
			__( 'There was an error while trying to delete this type.', 'buddypress' ),
			array(
			   'message' => 8,
			)
		);
	}

	/**
	 * Hook here to add code once the type has been deleted.
	 *
	 * @since 7.0.0
	 *
	 * @param integer $type_term_id  The Type's term_ID.
	 * @param string  $type_taxonomy The Type's taxonomy name.
	 */
	do_action( 'bp_type_deleted', $type_term_id, $type_taxonomy );

	// Finally informs about the successfull delete.
	return true;
}
