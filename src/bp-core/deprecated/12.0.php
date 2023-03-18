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

/**
 * Return the username for a user based on their user id.
 *
 * This function is sensitive to the BP_ENABLE_USERNAME_COMPATIBILITY_MODE,
 * so it will return the user_login or user_nicename as appropriate.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @param int         $user_id       User ID to check.
 * @param string|bool $user_nicename Optional. user_nicename of user being checked.
 * @param string|bool $user_login    Optional. user_login of user being checked.
 * @return string The username of the matched user or an empty string if no user is found.
 */
function bp_core_get_username( $user_id = 0, $user_nicename = false, $user_login = false ) {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_members_get_user_slug()' );

	if ( ! $user_id ) {
		$value = $user_nicename;
		$field = 'slug';

		if ( ! $user_nicename ) {
			$value = $user_login;
			$field = 'login';
		}

		$user = get_user_by( $field, $value );

		if ( $user instanceof WP_User ) {
			$user_id = (int) $user->ID;
		}
	}

	$username = bp_members_get_user_slug( $user_id );

	/**
	 * Filters the username based on originally provided user ID.
	 *
	 * @since 1.0.1
	 * @deprecated 12.0.0
	 *
	 * @param string $username Username determined by user ID.
	 */
	return apply_filters_deprecated( 'bp_core_get_username', array( $username ), '12.0.0', 'bp_members_get_user_slug' );
}

/**
 * Return the domain for the passed user: e.g. http://example.com/members/andy/.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @param int         $user_id       The ID of the user.
 * @param string|bool $user_nicename Optional. user_nicename of the user.
 * @param string|bool $user_login    Optional. user_login of the user.
 * @return string
 */
function bp_core_get_user_domain( $user_id = 0, $user_nicename = false, $user_login = false ) {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_members_get_user_url()' );

	if ( empty( $user_id ) ) {
		return;
	}

	$domain = bp_members_get_user_url( $user_id );

	// Don't use this filter.  Subject to removal in a future release.
	// Use the 'bp_core_get_user_domain' filter instead.
	$domain = apply_filters_deprecated( 'bp_core_get_user_domain_pre_cache', array( $domain, $user_id, $user_nicename, $user_login), '12.0.0' );

	/**
	 * Filters the domain for the passed user.
	 *
	 * @since 1.0.1
	 * @deprecated 12.0.0
	 *
	 * @param string $domain        Domain for the passed user.
	 * @param int    $user_id       ID of the passed user.
	 * @param string $user_nicename User nicename of the passed user.
	 * @param string $user_login    User login of the passed user.
	 */
	return apply_filters_deprecated( 'bp_core_get_user_domain', array( $domain, $user_id, $user_nicename, $user_login), '12.0.0', 'bp_members_get_user_url' );
}

/**
 * Get the link for the logged-in user's profile.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @return string
 */
function bp_get_loggedin_user_link() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_loggedin_user_url()' );
	$url = bp_loggedin_user_url();

	/**
	 * Filters the link for the logged-in user's profile.
	 *
	 * @since 1.2.4
	 * @deprecated 12.0.0
	 *
	 * @param string $url Link for the logged-in user's profile.
	 */
	return apply_filters_deprecated( 'bp_get_loggedin_user_link', array( $url ), '12.0.0', 'bp_loggedin_user_url' );
}

/**
 * Get the link for the displayed user's profile.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @return string
 */
function bp_get_displayed_user_link() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_displayed_user_url()' );
	$url = bp_displayed_user_url();

	/**
	 * Filters the link for the displayed user's profile.
	 *
	 * @since 1.2.4
	 * @deprecated 12.0.0
	 *
	 * @param string $url Link for the displayed user's profile.
	 */
	return apply_filters_deprecated( 'bp_get_displayed_user_link', array( $url ), '12.0.0', 'bp_displayed_user_url' );
}

/**
 * Alias of {@link bp_displayed_user_domain()}.
 *
 * @deprecated 12.0.0
 */
function bp_user_link() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_displayed_user_url()' );
	bp_displayed_user_url();
}

/**
 * Output blog directory permalink.
 *
 * @since 1.5.0
 * @deprecated 12.0.0
 */
function bp_blogs_directory_permalink() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_blogs_directory_url()' );
	bp_blogs_directory_url();
}

/**
 * Return blog directory permalink.
 *
 * @since 1.5.0
 * @deprecated 12.0.0
 *
 * @return string The URL of the Blogs directory.
 */
function bp_get_blogs_directory_permalink() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_get_blogs_directory_url()' );
	$url = bp_get_blogs_directory_url();

	/**
	 * Filters the blog directory permalink.
	 *
	 * @since 1.5.0
	 * @deprecated 12.0.0
	 *
	 * @param string $url Permalink URL for the blog directory.
	 */
	return apply_filters_deprecated( 'bp_get_blogs_directory_permalink', array( $url ), '12.0.0', 'bp_get_blogs_directory_url' );
}

/**
 * Outputs the group creation numbered steps navbar
 *
 * @since 3.0.0
 * @deprecated 12.0.0
 */
function bp_nouveau_group_creation_tabs() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_group_creation_tabs()' );
	bp_group_creation_tabs();
}
