<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 12.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add support for a top-level ("root") component.
 *
 * This function originally (pre-1.5) let plugins add support for pages in the
 * root of the install. These root level pages are now handled by actual
 * WordPress pages and this function is now a convenience for compatibility
 * with the new method.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @param string $slug The slug of the component being added to the root list.
 */
function bp_core_add_root_component( $slug ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Create WordPress pages to be used as BP component directories.
 *
 * @since 1.5.0
 * @deprecated 12.0.0
 */
function bp_core_create_root_component_page() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Create a default component slug from a WP page root_slug.
 *
 * Since 1.5, BP components get their root_slug (the slug used immediately
 * following the root domain) from the slug of a corresponding WP page.
 *
 * E.g. if your BP installation at example.com has its members page at
 * example.com/community/people, $bp->members->root_slug will be
 * 'community/people'.
 *
 * By default, this function creates a shorter version of the root_slug for
 * use elsewhere in the URL, by returning the content after the final '/'
 * in the root_slug ('people' in the example above).
 *
 * Filter on 'bp_core_component_slug_from_root_slug' to override this method
 * in general, or define a specific component slug constant (e.g.
 * BP_MEMBERS_SLUG) to override specific component slugs.
 *
 * @since 1.5.0
 * @deprecated 12.0.0
 *
 * @param string $root_slug The root slug, which comes from $bp->pages->[component]->slug.
 * @return string The short slug for use in the middle of URLs.
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	return apply_filters_deprecated( 'bp_core_component_slug_from_root_slug', array( $root_slug, $root_slug ), '12.0.0' );
}

/**
 * Renders the page mapping admin panel.
 *
 * @since 1.6.0
 * @deprecated 12.0.0
 */
function bp_core_admin_slugs_settings() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Generate a list of directory pages, for use when building Components panel markup.
 *
 * @since 2.4.1
 * @deprecated 12.0.0
 *
 * @return array
 */
function bp_core_admin_get_directory_pages() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	$directory_pages = (array) bp_core_get_directory_pages();
	$return          =  wp_list_pluck( $directory_pages, 'name', 'id' );

	return apply_filters_deprecated( 'bp_directory_pages', array( $return ), '12.0.0' );
}

/**
 * Generate a list of static pages, for use when building Components panel markup.
 *
 * By default, this list contains 'register' and 'activate'.
 *
 * @since 2.4.1
 * @deprecated 12.0.0
 *
 * @return array
 */
function bp_core_admin_get_static_pages() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	$static_pages = array(
		'register' => __( 'Register', 'buddypress' ),
		'activate' => __( 'Activate', 'buddypress' ),
	);

	return apply_filters_deprecated( 'bp_directory_pages', array( $static_pages ), '12.0.0' );
}

/**
 * Creates reusable markup for page setup on the Components and Pages dashboard panel.
 *
 * @package BuddyPress
 * @since 1.6.0
 * @deprecated 12.0.0
 */
function bp_core_admin_slugs_options() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	do_action_deprecated( 'bp_active_external_directories', array(), '12.0.0' );
	do_action_deprecated( 'bp_active_external_pages', array(), '12.0.0' );
}

/**
 * Handle saving of the BuddyPress slugs.
 *
 * @since 1.6.0
 * @deprecated 12.0.0
 */
function bp_core_admin_slugs_setup_handler() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Define the slug constants for the Members component.
 *
 * Handles the three slug constants used in the Members component -
 * BP_MEMBERS_SLUG, BP_REGISTER_SLUG, and BP_ACTIVATION_SLUG. If these
 * constants are not overridden in wp-config.php or bp-custom.php, they are
 * defined here to match the slug of the corresponding WP pages.
 *
 * In general, fallback values are only used during initial BP page creation,
 * when no slugs have been explicitly defined.
 *
 * @since 1.5.0
 * @deprecated 12.0.0
 */
function bp_core_define_slugs() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}
