<?php
/**
 * Core Rewrite API functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets default URL chunks rewrite information.
 *
 * @since 12.0.0
 *
 * @return array Default URL chunks rewrite information.
 */
function bp_rewrites_get_default_url_chunks() {
	return array(
		'directory'                    => array(
			'regex' => '([1]{1,})',
			'order' => 100,
		),
		'single_item'                  => array(
			'regex' => '([^/]+)',
			'order' => 90,
		),
		'single_item_component'        => array(
			'regex' => '([^/]+)',
			'order' => 80,
		),
		'single_item_action'           => array(
			'regex' => '([^/]+)',
			'order' => 70,
		),
		'single_item_action_variables' => array(
			'regex' => '(.+?)',
			'order' => 60,
		),
	);
}

/**
 * Delete rewrite rules, so that they are automatically rebuilt on
 * the subsequent page load.
 *
 * @since 12.0.0
 */
function bp_delete_rewrite_rules() {
	bp_delete_option( 'rewrite_rules' );
}

/**
 * Are Pretty URLs active?
 *
 * @since 12.0.0
 *
 * @return bool True if Pretty URLs are on. False otherwise.
 */
function bp_has_pretty_urls() {
	$has_plain_urls = ! get_option( 'permalink_structure', '' );
	return ! $has_plain_urls;
}

/**
 * Returns the slug to use for the screen belonging to the requested component.
 *
 * @since 12.0.0
 *
 * @param string $component_id The BuddyPress component's ID.
 * @param string $rewrite_id   The screen rewrite ID, used to find the custom slugs.
 *                             Eg: `member_profile_edit` will try to find the xProfile edit's slug.
 * @param string $default_slug The screen default slug, used as a fallback.
 * @return string The slug to use for the screen belonging to the requested component.
 */
function bp_rewrites_get_slug( $component_id = '', $rewrite_id = '', $default_slug = '' ) {
	$using_legacy = 'legacy' === bp_core_get_query_parser();

	/**
	 * This filter is used to simply return the `$default_slug` to bypass slug customization
	 * when the query parser is the legacy one.
	 *
	 * @since 12.0.0
	 *
	 * @param boolean $using_legacy Whether the legacy URL parser is in use.
	 *                              In this case, slug customization is not supported.
	 * @param string  $default_slug The screen default slug, used as a fallback.
	 * @param string  $rewrite_id   The screen rewrite ID, used to find the custom slugs.
	 * @param string  $component_id The BuddyPress component's ID.
	 */
	$use_default_slug = apply_filters( 'bp_rewrites_pre_get_slug', $using_legacy, $default_slug, $rewrite_id, $component_id );
	if ( $use_default_slug ) {
		return $default_slug;
	}

	$directory_pages = bp_core_get_directory_pages();
	$slug            = $default_slug;

	if ( ! isset( $directory_pages->{$component_id}->custom_slugs ) || ! $rewrite_id ) {
		return $slug;
	}

	// Make sure a `bp_` prefix is used.
	$rewrite_id = 'bp_' . str_replace( 'bp_', '', sanitize_key( $rewrite_id ) );

	$custom_slugs = (array) $directory_pages->{$component_id}->custom_slugs;
	if ( isset( $custom_slugs[ $rewrite_id ] ) && $custom_slugs[ $rewrite_id ] ) {
		$slug = $custom_slugs[ $rewrite_id ];
	}

	return $slug;
}

/**
 * Returns the rewrite ID of a customized slug.
 *
 * @since 12.0.0
 *
 * @param string $component_id The component ID (eg: `activity` for the BP Activity component).
 * @param string $slug         The customized slug.
 * @param string $context      The context for the customized slug, useful when the same slug is used
 *                             for more than one rewrite ID of the same component.
 * @return string              The rewrite ID matching the customized slug.
 */
function bp_rewrites_get_custom_slug_rewrite_id( $component_id = '', $slug = '', $context = '' ) {
	$directory_pages = bp_core_get_directory_pages();

	if ( ! isset( $directory_pages->{$component_id}->custom_slugs ) || ! $slug ) {
		return null;
	}

	$custom_slugs = (array) $directory_pages->{$component_id}->custom_slugs;
	$rewrite_ids  = array_keys( $custom_slugs, $slug, true );

	if ( 1 < count( $rewrite_ids ) && isset( $context ) && $context ) {
		foreach ( $rewrite_ids as $rewrite_id_key => $rewrite_id ) {
			if ( false !== strpos( $rewrite_id, $context ) ) {
				continue;
			}

			unset( $rewrite_ids[ $rewrite_id_key ] );
		}
	}

	// Always return the first match.
	return reset( $rewrite_ids );
}

/**
 * Builds a BuddyPress link using the WP Rewrite API.
 *
 * @since 12.0.0
 *
 * @param array $args {
 *      Optional. An array of arguments.
 *
 *      @type string $component_id                The BuddyPress component ID. Defaults ''.
 *      @type string $directory_type              Whether it's an object type URL. Defaults ''.
 *                                                Accepts '' (no object type), 'members' or 'groups'.
 *      @type string $single_item                 The BuddyPress single item's URL chunk. Defaults ''.
 *                                                Eg: the member's user nicename for Members or the group's slug for Groups.
 *      @type string $single_item_component       The BuddyPress single item's component URL chunk. Defaults ''.
 *                                                Eg: the member's Activity page.
 *      @type string $single_item_action          The BuddyPress single item's action URL chunk. Defaults ''.
 *                                                Eg: the member's Activity mentions page.
 *      @type array $single_item_action_variables The list of BuddyPress single item's action variable URL chunks. Defaults [].
 * }
 * @return string The BuddyPress link.
 */
function bp_rewrites_get_url( $args = array() ) {
	$bp  = buddypress();
	$url = get_home_url( bp_get_root_blog_id() );

	$r = bp_parse_args(
		$args,
		array(
			'component_id'                 => '',
			'directory_type'               => '',
			'single_item'                  => '',
			'single_item_component'        => '',
			'single_item_action'           => '',
			'single_item_action_variables' => array(),
		)
	);

	// Define the path chunks out of parsed arguments to make them available & unchanged for the 'bp_rewrites_get_url' filter.
	$path_chunks = $r;

	if ( $r['component_id'] && isset( $bp->{$r['component_id']}->rewrite_ids ) ) {
		$component = $bp->{$r['component_id']};
		unset( $r['component_id'] );

		// Using plain links.
		if ( ! bp_has_pretty_urls() ) {
			if ( ! isset( $r['member_register'] ) && ! isset( $r['member_activate'] ) ) {
				$r['directory'] = 1;
			}

			$r  = array_filter( $r );
			$qv = array();

			foreach ( $component->rewrite_ids as $key => $rewrite_id ) {
				if ( ! isset( $r[ $key ] ) ) {
					continue;
				}

				$qv[ $rewrite_id ] = $r[ $key ];
			}

			$url = add_query_arg( $qv, trailingslashit( $url ) );

			// Using pretty URLs.
		} else {
			if ( isset( $component->rewrite_ids['directory'] ) && isset( $component->directory_permastruct ) ) {
				if ( isset( $r['member_register'] ) ) {
					$url = str_replace( '%' . $component->rewrite_ids['member_register'] . '%', '', $component->register_permastruct );
					unset( $r['member_register'] );
				} elseif ( isset( $r['member_activate'] ) ) {
					$url = str_replace( '%' . $component->rewrite_ids['member_activate'] . '%', '', $component->activate_permastruct );
					unset( $r['member_activate'] );
				} elseif ( isset( $r['create_single_item'] ) ) {
					$create_slug = 'create';
					if ( 'groups' === $component->id ) {
						$create_slug = bp_rewrites_get_slug( 'groups', 'group_create', 'create' );
					} elseif ( 'blogs' === $component->id ) {
						$create_slug = bp_rewrites_get_slug( 'blogs', 'blog_create', 'create' );
					}

					$url = str_replace( '%' . $component->rewrite_ids['directory'] . '%', $create_slug, $component->directory_permastruct );
					unset( $r['create_single_item'] );
				} else {
					$url = str_replace( '%' . $component->rewrite_ids['directory'] . '%', $r['single_item'], $component->directory_permastruct );

					// Remove the members directory slug when root profiles are on.
					if ( bp_core_enable_root_profiles() && 'members' === $component->id && isset( $r['single_item'] ) && $r['single_item'] ) {
						$url = str_replace( $bp->members->root_slug . '/', '', $url );
					}

					unset( $r['single_item'] );
				}

				$r = array_filter( $r );

				if ( isset( $r['directory_type'] ) && $r['directory_type'] ) {
					if ( 'members' === $component->id ) {
						array_unshift( $r, bp_get_members_member_type_base() );
					} elseif ( 'groups' === $component->id && bp_is_active( 'groups' ) ) {
						array_unshift( $r, bp_get_groups_group_type_base() );
					} else {
						unset( $r['directory_type'] );
					}
				}

				if ( isset( $r['single_item_action_variables'] ) && $r['single_item_action_variables'] ) {
					$r['single_item_action_variables'] = join( '/', (array) $r['single_item_action_variables'] );
				}

				if ( isset( $r['create_single_item_variables'] ) && $r['create_single_item_variables'] ) {
					$r['create_single_item_variables'] = join( '/', (array) $r['create_single_item_variables'] );
				}
			} elseif ( isset( $r['community_search'] ) && 1 === $r['community_search'] ) {
				$r   = array_filter( $r );
				$url = '';

				unset( $r['community_search'] );
				array_unshift( $r, bp_get_search_slug() );
			} else {
				return $url;
			}

			$url = get_home_url( bp_get_root_blog_id(), user_trailingslashit( '/' . rtrim( $url, '/' ) . '/' . join( '/', $r ) ) );
		}
	}

	/**
	 * Filter here to edit any BuddyPress URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string $url The BuddyPress URL.
	 * @param array  $path_chunks {
	 *      Optional. An array of arguments.
	 *
	 *      @type string $component_id                The BuddyPress component ID. Defaults ''.
	 *      @type string $directory_type              Whether it's an object type URL. Defaults ''.
	 *                                                Accepts '' (no object type), 'members' or 'groups'.
	 *      @type string $single_item                 The BuddyPress single item's URL chunk. Defaults ''.
	 *                                                Eg: the member's user nicename for Members or the group's slug for Groups.
	 *      @type string $single_item_component       The BuddyPress single item's component URL chunk. Defaults ''.
	 *                                                Eg: the member's Activity page.
	 *      @type string $single_item_action          The BuddyPress single item's action URL chunk. Defaults ''.
	 *                                                Eg: the member's Activity mentions page.
	 *      @type array $single_item_action_variables The list of BuddyPress single item's action variable URL chunks. Defaults [].
	 * }
	 * @param array  $args Original arguments used with the function.
	 */
	return apply_filters( 'bp_rewrites_get_url', $url, $path_chunks, $args );
}

/**
 * Gets the BP root site URL, using BP Rewrites.
 *
 * @since 12.0.0
 *
 * @return string The BP root site URL.
 */
function bp_rewrites_get_root_url() {
	$url = bp_rewrites_get_url( array() );

	/**
	 * Filter here to edit the BP root site URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string $url The BP root site URL.
	 */
	return apply_filters( 'bp_rewrites_get_root_url', $url );
}

/**
 * Get needed data to find a member single item from the requested URL.
 *
 * @since 12.0.0
 *
 * @param string $request The request used during parsing.
 * @return array          Data to use to find a member single item from the request.
 */
function bp_rewrites_get_member_data( $request = '' ) {
	$member_data = array( 'field' => 'slug' );

	if ( bp_is_username_compatibility_mode() ) {
		$member_data = array( 'field' => 'login' );
	}

	if ( bp_core_enable_root_profiles() ) {
		if ( ! $request ) {
			$request = $GLOBALS['wp']->request;
		}

		$request_chunks = explode( '/', ltrim( $request, '/' ) );
		$member_chunk   = reset( $request_chunks );

		// Try to get an existing member to eventually reset the WP Query.
		$member_data['object'] = get_user_by( $member_data['field'], $member_chunk );
	}

	return $member_data;
}
