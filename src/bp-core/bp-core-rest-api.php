<?php
/**
 * Core REST API functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is the BP REST plugin active?
 *
 * @since 5.0.0
 *
 * @return bool True if the BP REST plugin is active. False otherwise.
 */
function bp_rest_is_plugin_active() {
	return (bool) has_action( 'bp_rest_api_init', 'bp_rest' );
}

/**
 * Should we use BuddyPress core REST Endpoints?
 *
 * If the BP REST plugin is active, it overrides BuddyPress REST endpoints.
 *
 * @since 5.0.0
 *
 * @return bool Whether to use BuddyPress core REST endpoints.
 */
function bp_rest_in_buddypress() {
	return ! bp_rest_is_plugin_active();
}

/**
 * Check the availability of the BP REST API.
 *
 * @since 5.0.0
 *
 * @return bool True if the BP REST API is available. False otherwise.
 */
function bp_rest_api_is_available() {

	/**
	 * Filter here to disable the BP REST API.
	 *
	 * The BP REST API requires at least WordPress 4.7.0.
	 *
	 * @since 5.0.0
	 *
	 * @param bool $api_is_available True if the BP REST API is available. False otherwise.
	 */
	return apply_filters( 'bp_rest_api_is_available', ( bp_rest_in_buddypress() || bp_rest_is_plugin_active() ) );
}

/**
 * BuddyPress REST API namespace.
 *
 * @since 5.0.0
 *
 * @return string
 */
function bp_rest_namespace() {

	/**
	 * Filter API namespace.
	 *
	 * @since 5.0.0
	 *
	 * @param string $namespace BuddyPress core namespace.
	 */
	return apply_filters( 'bp_rest_namespace', 'buddypress' );
}

/**
 * BuddyPress REST API version.
 *
 * @since 5.0.0
 * @since 15.0.0 Version is now v2.
 *
 * @return string
 */
function bp_rest_version() {

	/**
	 * Filter API version.
	 *
	 * @since 5.0.0
	 * @since 15.0.0 Default version is now v2.
	 *
	 * @param string $bp_version BuddyPress REST API version.
	 */
	return apply_filters( 'bp_rest_version', 'v2' );
}

/**
 * Get a REST API object URL from a component.
 *
 * @since 9.0.0
 *
 * @param integer $object_id   Object ID.
 * @param string  $object_path Path of the component endpoint.
 * @return string
 */
function bp_rest_get_object_url( $object_id, $object_path ) {
	return rest_url(
		sprintf(
			'/%1$s/%2$s/%3$s/%4$d',
			bp_rest_namespace(),
			bp_rest_version(),
			$object_path,
			$object_id
		)
	);
}

/**
 * Set headers to let the Client Script be aware of the pagination.
 *
 * @since 5.0.0
 *
 * @param  WP_REST_Response $response The response data.
 * @param  integer          $total    The total number of found items.
 * @param  integer          $per_page The number of items per page of results.
 * @return WP_REST_Response $response The response data.
 */
function bp_rest_response_add_total_headers( WP_REST_Response $response, $total = 0, $per_page = 0 ) {
	if ( ! $total || ! $per_page ) {
		return $response;
	}

	$total_items = (int) $total;
	$max_pages   = ceil( $total_items / (int) $per_page );

	$response->header( 'X-WP-Total', $total_items );
	$response->header( 'X-WP-TotalPages', (int) $max_pages );

	return $response;
}

/**
 * Convert the input date to RFC3339 format.
 *
 * @since 5.0.0
 *
 * @param string      $date_gmt Date GMT format.
 * @param string|null $date     Optional. Date object.
 * @return string|null ISO8601/RFC3339 formatted datetime.
 */
function bp_rest_prepare_date_response( $date_gmt, $date = null ) {
	if ( isset( $date ) ) {
		return mysql_to_rfc3339( $date );
	}

	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	return mysql_to_rfc3339( $date_gmt );
}

/**
 * Clean up member_type input.
 *
 * @since 5.0.0
 *
 * @param string $value Comma-separated list of group types.
 * @return array|null|string
 */
function bp_rest_sanitize_member_types( $value ) {
	if ( empty( $value ) ) {
		return null;
	}

	$types              = explode( ',', $value );
	$registered_types   = bp_get_member_types();
	$registered_types[] = 'any';
	$valid_types        = array_intersect( $types, $registered_types );

	return ! empty( $valid_types ) ? $valid_types : null;
}

/**
 * Validate member_type input.
 *
 * @since 5.0.0
 *
 * @param  mixed $value Mixed value.
 * @return WP_Error|true
 */
function bp_rest_validate_member_types( $value ) {
	if ( empty( $value ) ) {
		return true;
	}

	$types            = explode( ',', $value );
	$registered_types = bp_get_member_types();

	// Add the special value.
	$registered_types[] = 'any';
	foreach ( $types as $type ) {
		if ( ! in_array( $type, $registered_types, true ) ) {
			return new WP_Error(
				'bp_rest_invalid_member_type',
				sprintf(
					/* translators: %1$s and %2$s is replaced with the registered type(s) */
					__( 'The member type you provided, %1$s, is not one of %2$s.', 'buddypress' ),
					$type,
					implode( ', ', $registered_types )
				)
			);
		}
	}

	return true;
}

/**
 * Clean up group_type input.
 *
 * @since 5.0.0
 *
 * @param string $group_types Comma-separated list of group types.
 * @return array|null
 */
function bp_rest_sanitize_group_types( $group_types ) {
	if ( empty( $group_types ) ) {
		return null;
	}

	$types       = explode( ',', $group_types );
	$valid_types = array_intersect( $types, bp_groups_get_group_types() );

	return empty( $valid_types ) ? null : $valid_types;
}

/**
 * Validate group_type input.
 *
 * @since 5.0.0
 *
 * @param  mixed $group_types Mixed value.
 * @return WP_Error|bool
 */
function bp_rest_validate_group_types( $group_types ) {
	if ( empty( $group_types ) ) {
		return true;
	}

	$types            = explode( ',', $group_types );
	$registered_types = bp_groups_get_group_types();
	foreach ( $types as $type ) {
		if ( ! in_array( $type, $registered_types, true ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_type',
				sprintf(
					/* translators: %1$s and %2$s is replaced with the registered types */
					__( 'The group type you provided, %1$s, is not one of %2$s.', 'buddypress' ),
					$type,
					implode( ', ', $registered_types )
				)
			);
		}
	}

	return true;
}

/**
 * Clean up an array, comma- or space-separated list of strings.
 *
 * @since 5.0.0
 *
 * @param array|string $collection List of strings.
 * @return array Sanitized array of strings.
 */
function bp_rest_sanitize_string_list( $collection ) {
	if ( ! is_array( $collection ) ) {
		$collection = preg_split( '/[\s,]+/', $collection );
	}

	return array_unique( array_map( 'sanitize_text_field', $collection ) );
}

/**
 * Get the user object, if the ID is valid.
 *
 * @since 5.0.0
 *
 * @param int $user_id Supplied user ID.
 * @return WP_User|bool
 */
function bp_rest_get_user( $user_id ) {
	if ( (int) $user_id <= 0 ) {
		return false;
	}

	$user = get_userdata( (int) $user_id );
	if ( empty( $user ) || ! $user->exists() ) {
		return false;
	}

	return $user;
}

/**
 * Registers a new field on an existing BuddyPress object.
 *
 * @since 5.0.0
 *
 * @param string $component_id The name of the *active* component (eg: `activity`, `groups`, `xprofile`).
 *                             Required.
 * @param string $attribute    The attribute name. Required.
 * @param array  $args {
 *     Optional. An array of arguments used to handle the registered field.
 *     @see `register_rest_field()` for a full description.
 * }
 * @param string $object_type  The xProfile object type to get. This parameter is only required for
 *                             the Extended Profiles component. Not used for all other components.
 *                             Possible values are `data`, `field` or `group`.
 * @return bool                True if the field has been registered successfully. False otherwise.
 */
function bp_rest_register_field( $component_id, $attribute, $args = array(), $object_type = '' ) {
	$registered_fields = false;

	if ( ! $component_id || ! bp_is_active( $component_id ) || ! $attribute ) {
		return $registered_fields;
	}

	// Use the `bp_` prefix as we're using a WordPress global used for Post Types.
	$field_name = 'bp_' . $component_id;

	// Use the meta type as a suffix for the field name.
	if ( 'xprofile' === $component_id ) {
		if ( ! in_array( $object_type, array( 'data', 'field', 'group' ), true ) ) {
			return $registered_fields;
		}

		$field_name .= '_' . $object_type;
	}

	$args = bp_parse_args(
		$args,
		array(
			'get_callback'    => null,
			'update_callback' => null,
			'schema'          => null,
		),
		'rest_register_field'
	);

	// Register the field.
	register_rest_field( $field_name, $attribute, $args );

	if ( isset( $GLOBALS['wp_rest_additional_fields'][ $field_name ] ) ) {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'][ $field_name ];
	}

	// Check it has been registered.
	return isset( $registered_fields[ $attribute ] );
}

/**
 * Filter the WP REST API response to return a 404 if the request is for the V1 of the BP REST API.
 *
 * @param mixed           $result Response to replace the requested version with. Can be anything
 *                                a normal endpoint can return, or null to not hijack the request.
 * @param WP_REST_Server  $server Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 *
 * @return mixed
 */
function bp_rest_api_v1_dispatch_error( $result, $server, $request ) {

	// Bail early if the BP REST plugin is active.
	if ( bp_rest_is_plugin_active() ) {
		return $result;
	}

	$route = $request->get_route();

	if ( empty( $route ) || ! str_contains( $route, 'buddypress/v1' ) ) {
		return $result;
	}

	return new WP_Error(
		'rest_no_route',
		__( 'The V1 of the BuddyPress REST API is no longer supported, use the V2 instead.', 'buddypress' ),
		array( 'status' => 404 )
	);
}
add_filter( 'rest_pre_dispatch', 'bp_rest_api_v1_dispatch_error', 10, 3 );
