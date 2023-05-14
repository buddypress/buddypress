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

// These functions has been moved to the BP Classic plugin.
if ( ! function_exists( 'bp_classic' ) ) {
	/**
	 * Analyze the URI and break it down into BuddyPress-usable chunks.
	 *
	 * BuddyPress can use complete custom friendly URIs without the user having to
	 * add new rewrite rules. Custom components are able to use their own custom
	 * URI structures with very little work.
	 *
	 * The URIs are broken down as follows:
	 *   - http:// example.com / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
	 *   - OUTSIDE ROOT: http:// example.com / sites / buddypress / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
	 *
	 * Example:
	 *    - http://example.com/members/andy/profile/edit/group/5/
	 *    - $bp->current_component: string 'xprofile'
	 *    - $bp->current_action: string 'edit'
	 *    - $bp->action_variables: array ['group', 5]
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 */
	function bp_core_set_uri_globals() {
		_deprecated_function( __FUNCTION__, '12.0.0' );
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
	 * Return the domain for the root blog.
	 *
	 * Eg: http://example.com OR https://example.com
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 *
	 * @return string The domain URL for the blog.
	 */
	function bp_core_get_root_domain() {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_rewrites_get_root_url()' );
		$domain = bp_rewrites_get_root_url();

		/**
		 * Filters the domain for the root blog.
		 *
		 * @since 1.0.1
		 * @deprecated 12.0.0 Use {@see 'bp_rewrites_get_root_url'} instead.
		 *
		 * @param string $domain The domain URL for the blog.
		 */
		return apply_filters_deprecated( 'bp_core_get_root_domain', array( $domain ), '12.0.0', 'bp_rewrites_get_root_url' );
	}

	/**
	 * Return the "root domain", the URL of the BP root blog.
	 *
	 * @since 1.1.0
	 * @deprecated 12.0.0
	 *
	 * @return string URL of the BP root blog.
	 */
	function bp_get_root_domain() {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_get_root_url()' );
		$domain = bp_get_root_url();

		/**
		 *  Filters the "root domain", the URL of the BP root blog.
		 *
		 * @since 1.2.4
		 * @deprecated 12.0.0 Use {@see 'bp_get_root_url'} instead.
		 *
		 * @param string $domain URL of the BP root blog.
		 */
		return apply_filters_deprecated( 'bp_get_root_domain', array( $domain ), '12.0.0', 'bp_get_root_url' );
	}

	/**
	 * Output the "root domain", the URL of the BP root blog.
	 *
	 * @since 1.1.0
	 * @deprecated 12.0.0
	 */
	function bp_root_domain() {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_root_url()' );
		bp_root_url();
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
		$domain = apply_filters_deprecated( 'bp_core_get_user_domain_pre_cache', array( $domain, $user_id, $user_nicename, $user_login ), '12.0.0' );

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
		return apply_filters_deprecated( 'bp_core_get_user_domain', array( $domain, $user_id, $user_nicename, $user_login ), '12.0.0', 'bp_members_get_user_url' );
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
	 * Output group directory permalink.
	 *
	 * @since 1.5.0
	 * @deprecated 12.0.0
	 */
	function bp_groups_directory_permalink() {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_groups_directory_url()' );
		bp_groups_directory_url();
	}

	/**
	 * Return group directory permalink.
	 *
	 * @since 1.5.0
	 * @deprecated 12.0.0
	 *
	 * @return string
	 */
	function bp_get_groups_directory_permalink() {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_get_groups_directory_url()' );

		$url = bp_get_groups_directory_url();

		/**
		 * Filters the group directory permalink.
		 *
		 * @since 1.5.0
		 * @deprecated 12.0.0
		 *
		 * @param string $url Permalink for the group directory.
		 */
		return apply_filters_deprecated( 'bp_get_groups_directory_permalink', array( $url ), '12.0.0', 'bp_get_groups_directory_url' );
	}

	/**
	 * Output the permalink for the group.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 */
	function bp_group_permalink( $group = false ) {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_group_url()' );
		bp_group_url( $group );
	}

	/**
	 * Return the permalink for the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 * @deprecated 12.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_permalink( $group = false ) {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_get_group_url()' );
		$url = bp_get_group_url( $group );

		/**
		 * Filters the permalink for the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 * @deprecated 12.0.0
		 *
		 * @param string          $url   Permalink for the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters_deprecated( 'bp_get_group_permalink', array( $url, $group ), '12.0.0', 'bp_get_group_url' );
	}

	/**
	 * Output the permalink for the admin section of the group.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 */
	function bp_group_admin_permalink( $group = false ) {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_group_manage_url()' );
		bp_group_manage_url( $group );
	}

	/**
	 * Return the permalink for the admin section of the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 * @deprecated 12.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_admin_permalink( $group = false ) {
		_deprecated_function( __FUNCTION__, '12.0.0', 'bp_get_group_manage_url()' );
		$permalink = bp_get_group_manage_url( $group );

		/**
		 * Filters the permalink for the admin section of the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 * @deprecated 12.0.0
		 *
		 * @param string          $permalink Permalink for the admin section of the group.
		 * @param BP_Groups_Group $group     The group object.
		 */
		return apply_filters_deprecated( 'bp_get_group_admin_permalink', array( $permalink, $group ), '12.0.0', 'bp_get_group_manage_url' );
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
	 * Returns the upper limit on the "max" item count, for widgets that support it.
	 *
	 * @since 5.0.0
	 * @deprecated 12.0.0
	 *
	 * @param string $widget_class Optional. Class name of the calling widget.
	 * @return int
	 */
	function bp_get_widget_max_count_limit( $widget_class = '' ) {
		_deprecated_function( __FUNCTION__, '12.0.0' );
		/**
		 * Filters the upper limit on the "max" item count, for widgets that support it.
		 *
		 * @since 5.0.0
		 * @deprecated 12.0.0
		 *
		 * @param int    $count        Defaults to 50.
		 * @param string $widget_class Class name of the calling widget.
		 */
		return apply_filters_deprecated( 'bp_get_widget_max_count_limit', array( 50, $widget_class ), '12.0.0' );
	}
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
 * Outputs the group creation numbered steps navbar
 *
 * @since 3.0.0
 * @deprecated 12.0.0
 */
function bp_nouveau_group_creation_tabs() {
	_deprecated_function( __FUNCTION__, '12.0.0', 'bp_group_creation_tabs()' );
	bp_group_creation_tabs();
}

/**
 * Displays group header tabs.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_groups_header_tabs() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$user_groups = bp_displayed_user_url() . bp_get_groups_slug(); ?>

	<li<?php if ( !bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/recently-active' ); ?>"><?php _e( 'Recently Active', 'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'recently-joined', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/recently-joined' ); ?>"><?php _e( 'Recently Joined',  'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'most-popular',    0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/most-popular'    ); ?>"><?php _e( 'Most Popular',     'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'admin-of',        0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/admin-of'        ); ?>"><?php _e( 'Administrator Of', 'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'mod-of',          0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/mod-of'          ); ?>"><?php _e( 'Moderator Of',     'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'alphabetically'     ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo trailingslashit( $user_groups . '/my-groups/alphabetically'  ); ?>"><?php _e( 'Alphabetically',   'buddypress' ); ?></a></li>

<?php
	/**
	 * Fires after the markup for the navigation tabs for a user Groups page.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 */
	do_action_deprecated( 'groups_header_tabs', array(), '12.0.0' );
}

/**
 * Output navigation tabs for a user Blogs page.
 *
 * Currently unused by BuddyPress.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_blogs_blog_tabs() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	// Don't show these tabs on a user's own profile.
	if ( bp_is_my_profile() ) {
		return false;
	} ?>

	<ul class="content-header-nav">
		<li<?php if ( bp_is_current_action( 'my-blogs' ) || !bp_current_action() ) : ?> class="current"<?php endif; ?>>
			<a href="<?php bp_displayed_user_link( array( bp_get_blogs_slug(), 'my-blogs' ) ); ?>">
				<?php
				/* translators: %s: the User Display Name */
				printf( esc_html__( "%s's Sites", 'buddypress' ), bp_get_displayed_user_fullname() );
				?>
			</a>
		</li>
		<li<?php if ( bp_is_current_action( 'recent-posts' ) ) : ?> class="current"<?php endif; ?>>
			<a href="<?php bp_displayed_user_link( array( bp_get_blogs_slug(), 'recent-posts' ) ); ?>">
				<?php
				/* translators: %s: the User Display Name */
				printf( esc_html__( "%s's Recent Posts", 'buddypress' ), bp_get_displayed_user_fullname() );
				?>
			</a>
		</li>
		<li<?php if ( bp_is_current_action( 'recent-comments' ) ) : ?> class="current"<?php endif; ?>>
			<a href="<?php bp_displayed_user_link( array( bp_get_blogs_slug(), 'recent-comments' ) ); ?>">
				<?php
				/* translators: %s: the User Display Name */
				printf( esc_html__( "%s's Recent Comments", 'buddypress' ), bp_get_displayed_user_fullname() );
				?>
			</a>
		</li>
	</ul>

<?php

	/**
	 * Fires after the markup for the navigation tabs for a user Blogs page.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 */
	do_action_deprecated( 'bp_blogs_blog_tabs', array(), '12.0.0' );
}

/**
 * Dedicated filter to inform about BP components directory page states.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 *
 * @param string[] $post_states An array of post display states.
 * @param WP_Post  $post        The current post object.
 */
function bp_admin_display_directory_states( $post_states = array(), $post = null ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$states = array();

	/**
	 * Filter here to add BP Directory pages.
	 *
	 * Used internaly by BP_Component->admin_directory_states(). Please use the dynamic
	 * filter in BP_Component->admin_directory_states() to edit the directory state
	 * according to the component's ID.
	 *
	 * @since 10.0.0
	 * @deprecated 12.0.0
	 *
	 * @param array    $states An empty array.
	 * @param WP_Post  $post   The current post object.
	 */
	$directory_page_states = apply_filters_deprecated( 'bp_admin_display_directory_states', array( $states, $post ), '12.0.0' );

	if ( $directory_page_states ) {
		$post_states = array_merge( $post_states, $directory_page_states );
	}

	return $post_states;
}

/**
 * Should BuddyPress load Legacy Widgets?
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 *
 * @return bool False if BuddyPress shouldn't load Legacy Widgets. True otherwise.
 */
function bp_core_retain_legacy_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	return false;
}

/**
 * Checks whether BuddyPress should unhook Legacy Widget registrations.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_core_maybe_unhook_legacy_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Login widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_core_register_login_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register bp-core widgets.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_core_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Recent Posts Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_blogs_register_recent_posts_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register the widgets for the Blogs component.
 *
 * @deprecated 12.0.0
 */
function bp_blogs_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Friends Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_friends_register_friends_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register the friends widget.
 *
 * @since 1.9.0
 * @deprecated 12.0.0
 */
function bp_friends_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Process AJAX pagination or filtering for the Friends widget.
 *
 * @since 1.9.0
 * @deprecated 12.0.0
 */
function bp_core_ajax_widget_friends() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Injects specific BuddyPress CSS classes into a widget sidebar.
 *
 * Helps to standardize styling of BuddyPress widgets within a theme that
 * does not use dynamic CSS classes in their widget sidebar's 'before_widget'
 * call.
 *
 * @since 2.4.0
 * @deprecated 12.0.0
 * @access private
 *
 * @global array $wp_registered_widgets Current registered widgets.
 *
 * @param array $params Current sidebar params.
 * @return array
 */
function _bp_core_inject_bp_widget_css_class( $params ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Groups Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_groups_register_groups_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register widgets for groups component.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function groups_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * AJAX callback for the Groups List widget.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function groups_ajax_widget_groups_list() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Members Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_members_register_members_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the "Who's online?" Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_members_register_whos_online_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the "Recently Active" Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_members_register_recently_active_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register bp-members widgets.
 *
 * Previously, these widgets were registered in bp-core.
 *
 * @since 2.2.0
 * @deprecated 12.0.0
 */
function bp_members_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * AJAX request handler for Members widgets.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @see BP_Core_Members_Widget
 */
function bp_core_ajax_widget_members() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Registers the Sitewide Notices Legacy Widget.
 *
 * @since 10.0.0
 * @deprecated 12.0.0
 */
function bp_messages_register_sitewide_notices_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Register widgets for the Messages component.
 *
 * @since 1.9.0
 * @deprecated 12.0.0
 */
function bp_messages_register_widgets() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}
