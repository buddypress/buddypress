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

	/**
	 * Determine whether BuddyPress should register the bp-themes directory.
	 *
	 * @since 1.9.0
	 * @deprecated 12.0.0
	 *
	 * @return bool True if bp-themes should be registered, false otherwise.
	 */
	function bp_do_register_theme_directory() {
		_deprecated_function( __FUNCTION__, '12.0.0' );
		$register = false;

		/**
		 * Filters whether BuddyPress should register the bp-themes directory.
		 *
		 * @since 1.9.0
		 * @deprecated 12.0.0
		 *
		 * @param bool $register If bp-themes should be registered.
		 */
		return apply_filters_deprecated( 'bp_do_register_theme_directory', array( $register ), '12.0.0' );
	}

	/**
	 * Fire the 'bp_register_theme_directory' action.
	 *
	 * The main action used registering theme directories.
	 *
	 * @since 1.5.0
	 * @deprecated 12.0.0
	 */
	function bp_register_theme_directory() {
		_deprecated_function( __FUNCTION__, '12.0.0' );
		/**
		 * Fires inside the 'bp_register_theme_directory' function.
		 *
		 * The main action used registering theme directories.
		 *
		 * @since 1.7.0
		 * @deprecated 12.0.0
		 */
		do_action_deprecated( 'bp_register_theme_directory', array(), '12.0.0' );
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

	<li<?php if ( !bp_action_variable( 0 ) || bp_is_action_variable( 'recently-active', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/recently-active' ) ); ?>"><?php esc_html_e( 'Recently Active', 'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'recently-joined', 0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/recently-joined' ) ); ?>"><?php esc_html_e( 'Recently Joined',  'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'most-popular',    0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/most-popular'    ) ); ?>"><?php esc_html_e( 'Most Popular',     'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'admin-of',        0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/admin-of'        ) ); ?>"><?php esc_html_e( 'Administrator Of', 'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'mod-of',          0 ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/mod-of'          ) ); ?>"><?php esc_html_e( 'Moderator Of',     'buddypress' ); ?></a></li>
	<li<?php if ( bp_is_action_variable( 'alphabetically'     ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( trailingslashit( $user_groups . '/my-groups/alphabetically'  ) ); ?>"><?php esc_html_e( 'Alphabetically',   'buddypress' ); ?></a></li>

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
				printf( esc_html__( "%s's Sites", 'buddypress' ), esc_html( bp_get_displayed_user_fullname() ) );
				?>
			</a>
		</li>
		<li<?php if ( bp_is_current_action( 'recent-posts' ) ) : ?> class="current"<?php endif; ?>>
			<a href="<?php bp_displayed_user_link( array( bp_get_blogs_slug(), 'recent-posts' ) ); ?>">
				<?php
				/* translators: %s: the User Display Name */
				printf( esc_html__( "%s's Recent Posts", 'buddypress' ), esc_html( bp_get_displayed_user_fullname() ) );
				?>
			</a>
		</li>
		<li<?php if ( bp_is_current_action( 'recent-comments' ) ) : ?> class="current"<?php endif; ?>>
			<a href="<?php bp_displayed_user_link( array( bp_get_blogs_slug(), 'recent-comments' ) ); ?>">
				<?php
				/* translators: %s: the User Display Name */
				printf( esc_html__( "%s's Recent Comments", 'buddypress' ), esc_html( bp_get_displayed_user_fullname() ) );
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

/**
 * Generate the HTML for a list of group moderators.
 *
 * No longer used.
 *
 * @deprecated 12.0.0
 *
 * @param bool $admin_list
 * @param bool $group
 */
function bp_group_mod_memberlist( $admin_list = false, $group = false ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	if ( $group_mods = groups_get_group_mods( $group->id ) ) { ?>

		<ul id="mods-list" class="item-list<?php if ( $admin_list ) { ?> single-line<?php } ?>">

		<?php foreach ( (array) $group_mods as $mod ) { ?>

			<?php if ( !empty( $admin_list ) ) { ?>

			<li>

				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo bp_core_fetch_avatar(
					array(
						'item_id' => $mod->user_id,
						'type' => 'thumb',
						'width' => 30,
						'height' => 30,
						'alt' => sprintf(
							/* translators: %s: member name */
							__( 'Profile picture of %s', 'buddypress' ),
							bp_core_get_user_displayname( $mod->user_id )
						),
					)
				);
				?>

				<h5>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput
					echo bp_core_get_userlink( $mod->user_id );
					?>

					<span class="small">
						<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => $mod->user_id ) ) ?>" class="button confirm mod-promote-to-admin"><?php esc_html_e( 'Promote to Admin', 'buddypress' ); ?></a>
						<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link($mod->user_id) ?>"><?php esc_html_e( 'Demote to Member', 'buddypress' ) ?></a>
					</span>
				</h5>
			</li>

			<?php } else { ?>

			<li>

				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo bp_core_fetch_avatar(
					array(
						'item_id' => $mod->user_id,
						'type'    => 'thumb',
						'alt'     => sprintf(
							/* translators: %s: member name */
							__( 'Profile picture of %s', 'buddypress' ),
							bp_core_get_user_displayname( $mod->user_id )
						),
					)
				);
				?>

				<h5>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput
					echo bp_core_get_userlink( $mod->user_id );
					?>
				</h5>

				<span class="activity">
					<?php
					/* translators: %s: human time diff */
					echo esc_html( bp_core_get_last_activity( strtotime( $mod->date_modified ), esc_html__( 'joined %s', 'buddypress' ) ) );
					?>
				</span>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<div class="action">
						<?php bp_add_friend_button( $mod->user_id ) ?>
					</div>

				<?php endif; ?>

			</li>

			<?php } ?>
		<?php } ?>

		</ul>

	<?php } else { ?>

		<div id="message" class="info">
			<p><?php esc_html_e( 'This group has no moderators', 'buddypress' ); ?></p>
		</div>

	<?php }
}

/**
 * Output the activities title.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_activities_title() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	echo esc_html( bp_get_activities_title() );
}

/**
 * Return the activities title.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @global string $bp_activity_title
 *
 * @return string The activities title.
 */
function bp_get_activities_title() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $bp_activity_title;

	/**
	 * Filters the activities title for the activity template.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 *
	 * @param string $bp_activity_title The title to be displayed.
	 */
	return apply_filters_deprecated( 'bp_get_activities_title', array( $bp_activity_title ), '12.0.0' );
}

/**
 * {@internal Missing Description}
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_activities_no_activity() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	echo esc_html( bp_get_activities_no_activity() );
}

/**
 * {@internal Missing Description}
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 *
 * @global string $bp_activity_no_activity
 *
 * @return string
 */
function bp_get_activities_no_activity() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $bp_activity_no_activity;

	/**
	 * Filters the text used when there is no activity to display.
	 *
	 * @since 1.0.0
	 * @deprecated 12.0.0
	 *
	 * @param string $bp_activity_no_activity Text to display for no activity.
	 */
	return apply_filters_deprecated( 'bp_get_activities_no_activity', array( $bp_activity_no_activity ), '12.0.0' );
}

/**
 * Get the 'bp_options_title' property from the BP global.
 *
 * Not currently used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_get_options_title() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$bp = buddypress();

	if ( empty( $bp->bp_options_title ) ) {
		$bp->bp_options_title = __( 'Options', 'buddypress' );
	}

	echo esc_html( apply_filters_deprecated( 'bp_get_options_title', array( esc_attr( $bp->bp_options_title ) ), '12.0.0' ) );
}

/**
 * Check to see if there is an options avatar.
 *
 * An options avatar is an avatar for something like a group, or a friend.
 * Basically an avatar that appears in the sub nav options bar.
 *
 * @deprecated 12.0.0
 *
 * @return bool $value Returns true if an options avatar has been set, otherwise false.
 */
function bp_has_options_avatar() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	return (bool) buddypress()->bp_options_avatar;
}

/**
 * Output the options avatar.
 *
 * @deprecated 12.0.0
 */
function bp_get_options_avatar() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters_deprecated( 'bp_get_options_avatar', array( buddypress()->bp_options_avatar ), '12.0.0' );
}

/**
 * Output a comment author's avatar.
 *
 * @deprecated 12.0.0
 */
function bp_comment_author_avatar() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $comment;

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters_deprecated(
		'bp_comment_author_avatar',
		array(
			bp_core_fetch_avatar(
				array(
					'item_id' => $comment->user_id,
					'type'    => 'thumb',
					'alt'     => sprintf(
						/* translators: %s: member name */
						__( 'Profile photo of %s', 'buddypress' ),
						bp_core_get_user_displayname( $comment->user_id )
					),
				)
			)
		),
		'12.0.0'
	);
}

/**
 * Output a post author's avatar.
 *
 * @deprecated 12.0.0
 */
function bp_post_author_avatar() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $post;

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters_deprecated(
		'bp_post_author_avatar',
		array(
			bp_core_fetch_avatar(
				array(
					'item_id' => $post->post_author,
					'type'    => 'thumb',
					'alt'     => sprintf(
						/* translators: %s: member name */
						__( 'Profile photo of %s', 'buddypress' ),
						bp_core_get_user_displayname( $post->post_author )
					),
				)
			)
		),
		'12.0.0'
	);
}

/**
 * Output the avatar cropper <img> markup.
 *
 * @deprecated 12.0.0
 */
function bp_avatar_cropper() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
?>
	<img id="avatar-to-crop" class="avatar" src="<?php echo esc_url( buddypress()->avatar_admin->image ); ?>" />
<?php
}

/**
 * Do the 'bp_styles' action, and call wp_print_styles().
 *
 * @deprecated 12.0.0
 */
function bp_styles() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	do_action_deprecated( 'bp_styles', array(), '12.0.0' );
	wp_print_styles();
}

/**
 * Fire the 'bp_custom_profile_boxes' action.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_custom_profile_boxes() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	do_action( 'bp_custom_profile_boxes' );
}

/**
 * Fire the 'bp_custom_profile_sidebar_boxes' action.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_custom_profile_sidebar_boxes() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	do_action_deprecated( 'bp_custom_profile_sidebar_boxes', arrray(), '12.0.0' );
}

/**
 * Output whether blog signup is allowed.
 *
 * @deprecated 12.0.0
 */
function bp_blog_signup_allowed() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_blog_signup_allowed();
}

/**
 * Output a block of random friends.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_friends_random_friends() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	if ( !$friend_ids = wp_cache_get( 'friends_friend_ids_' . bp_displayed_user_id(), 'bp' ) ) {
		$friend_ids = BP_Friends_Friendship::get_random_friends( bp_displayed_user_id() );
		wp_cache_set( 'friends_friend_ids_' . bp_displayed_user_id(), $friend_ids, 'bp' );
	} ?>

	<div class="info-group">
		<h4>
			<?php
			/* translators: %s: member name */
			bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) );
			?>
			&nbsp;
			(<?php echo esc_html( BP_Friends_Friendship::total_friend_count( bp_displayed_user_id() ) ); ?>)
			&nbsp;
			<span>
				<a href="<?php bp_displayed_user_link( array( bp_get_friends_slug() ) ); ?>">
					<?php esc_html_e( 'See All', 'buddypress' ) ?>
				</a>
			</span>
		</h4>

		<?php if ( $friend_ids ) { ?>

			<ul class="horiz-gallery">

			<?php for ( $i = 0, $count = count( $friend_ids ); $i < $count; ++$i ) { ?>

				<li>
					<a href="<?php echo esc_url( bp_members_get_user_url( $friend_ids[$i] ) ); ?>">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput
						echo bp_core_fetch_avatar( array( 'item_id' => $friend_ids[$i], 'type' => 'thumb' ) );
						?>
					</a>
					<h5>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput
						echo bp_core_get_userlink($friend_ids[$i]);
						?>
					</h5>
				</li>

			<?php } ?>

			</ul>

		<?php } else { ?>

			<div id="message" class="info">
				<p>
					<?php
					/* translators: %s: member name */
					bp_word_or_name( __( "You haven't added any friend connections yet.", 'buddypress' ), __( "%s hasn't created any friend connections yet.", 'buddypress' ) );
					?>
				</p>
			</div>

		<?php } ?>

		<div class="clear"></div>
	</div>

<?php
}

/**
 * Pull up a group of random members, and display some profile data about them.
 *
 * This function is no longer used by BuddyPress core.
 *
 * @deprecated 12.0.0
 *
 * @param int $total_members The number of members to retrieve.
 */
function bp_friends_random_members( $total_members = 5 ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	if ( !$user_ids = wp_cache_get( 'friends_random_users', 'bp' ) ) {
		$user_ids = BP_Core_User::get_users( 'random', $total_members );
		wp_cache_set( 'friends_random_users', $user_ids, 'bp' );
	}

	?>

	<?php if ( $user_ids['users'] ) { ?>

		<ul class="item-list" id="random-members-list">

		<?php for ( $i = 0, $count = count( $user_ids['users'] ); $i < $count; ++$i ) { ?>

			<li>
				<a href="<?php echo esc_url( bp_members_get_user_url( $user_ids['users'][$i]->id ) ); ?>">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput
					echo bp_core_fetch_avatar( array( 'item_id' => $user_ids['users'][$i]->id, 'type' => 'thumb' ) );
					?>
				</a>
				<h5>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput
					echo bp_core_get_userlink( $user_ids['users'][$i]->id );
					?>
				</h5>

				<?php if ( bp_is_active( 'xprofile' ) ) { ?>

					<?php $random_data = xprofile_get_random_profile_data( $user_ids['users'][$i]->id, true ); ?>

					<div class="profile-data">
						<p class="field-name"><?php echo esc_html( $random_data[0]->name ); ?></p>

						<?php echo esc_html( $random_data[0]->value ); ?>

					</div>

				<?php } ?>

				<div class="action">

					<?php if ( bp_is_active( 'friends' ) ) { ?>

						<?php bp_add_friend_button( $user_ids['users'][$i]->id ) ?>

					<?php } ?>

				</div>
			</li>

		<?php } ?>

		</ul>

	<?php } else { ?>

		<div id="message" class="info">
			<p><?php esc_html_e( "There aren't enough site members to show a random sample just yet.", 'buddypress' ) ?></p>
		</div>

	<?php } ?>
<?php
}

/**
 * Display a Friends search form.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_friend_search_form() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$label  = __( 'Filter Friends', 'buddypress' );
	$action = bp_displayed_user_url( bp_members_get_path_chunks( array( bp_get_friends_slug(), 'my-friends', array( 'search' ) ) ) );
	?>

		<form action="<?php echo esc_url( $action ) ?>" id="friend-search-form" method="post">

			<label for="friend-search-box" id="friend-search-label"><?php echo esc_html( $label ); ?></label>
			<input type="search" name="friend-search-box" id="friend-search-box" value="" />

			<?php wp_nonce_field( 'friends_search', '_wpnonce_friend_search' ) ?>

			<input type="hidden" name="initiator" id="initiator" value="<?php echo esc_attr( bp_displayed_user_id() ) ?>" />

		</form>

	<?php
}

/**
 * Output the permalink of a group's Members page.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` parameter.
 * @deprecated 12.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_all_members_permalink( $group = false ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	echo esc_url( bp_get_group_all_members_permalink( $group ) );
}

/**
 * Return the permalink of the Members page of a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 * @deprecated 12.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return string
 */
function bp_get_group_all_members_permalink( $group = false ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$path_chunks = bp_groups_get_path_chunks( array( 'members' ) );
	$url         = bp_get_group_url( $group, $path_chunks );

	/**
	 * Filters the permalink of the Members page for a group.
	 *
	 * @since 1.0.0
	 * @since 2.5.0 Added the `$group` parameter.
	 * @deprecated 12.0.0
	 *
	 * @param string          $url   Permalink of the Members page for a group.
	 * @param BP_Groups_Group $group The group object.
	 */
	return apply_filters_deprecated( 'bp_get_group_all_members_permalink', array( $url, $group ), '12.0.0' );
}

/**
 * Display a Groups search form.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 */
function bp_group_search_form() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$label  = __('Filter Groups', 'buddypress');
	$name   = 'group-filter-box';
	$action = bp_displayed_user_url( bp_members_get_path_chunks( array( bp_get_groups_slug(), 'my-groups', array( 'search' ) ) ) );

	$search_form_html = '<form action="' . esc_url( $action ) . '" id="group-search-form" method="post">
		<label for="'. $name .'" id="'. $name .'-label">'. esc_html( $label ) .'</label>
		<input type="search" name="'. $name . '" id="'. $name .'" value=""/>

		'. wp_nonce_field( 'group-filter-box', '_wpnonce_group_filter', true, false ) .'
		</form>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters( 'bp_group_search_form', $search_form_html );
}

/**
 * Determine whether the displayed user has no groups.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 *
 * @return bool True if the displayed user has no groups, otherwise false.
 */
function bp_group_show_no_groups_message() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	if ( !groups_total_groups_for_user( bp_displayed_user_id() ) ) {
		return true;
	}

	return false;
}

/**
 * Determine whether the current page is a group activity permalink.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 *
 * @return bool True if this is a group activity permalink, otherwise false.
 */
function bp_group_is_activity_permalink() {
	_deprecated_function( __FUNCTION__, '12.0.0' );

	if ( !bp_is_single_item() || !bp_is_groups_component() || !bp_is_current_action( bp_get_activity_slug() ) ) {
		return false;
	}

	return true;
}

/**
 * Displays group filter titles.
 *
 * @since 1.0.0
 * @deprecated 12.0.0
 */
function bp_groups_filter_title() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$current_filter = bp_action_variable( 0 );

	switch ( $current_filter ) {
		case 'recently-active': default:
			esc_html_e( 'Recently Active', 'buddypress' );
			break;
		case 'recently-joined':
			esc_html_e( 'Recently Joined', 'buddypress' );
			break;
		case 'most-popular':
			esc_html_e( 'Most Popular', 'buddypress' );
			break;
		case 'admin-of':
			esc_html_e( 'Administrator Of', 'buddypress' );
			break;
		case 'mod-of':
			esc_html_e( 'Moderator Of', 'buddypress' );
			break;
		case 'alphabetically':
			esc_html_e( 'Alphabetically', 'buddypress' );
		break;
	}

	do_action_deprecated( 'bp_groups_filter_title', array(), '12.0.0' );
}


/**
 * Return the ID of a user, based on user_login.
 *
 * No longer used.
 *
 * @deprecated 12.0.0
 *
 * @param string $user_login user_login of the user being queried.
 * @return int
 */
function bp_core_get_displayed_userid( $user_login ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	$id = bp_core_get_userid( $user_login );

	return apply_filters_deprecated( 'bp_core_get_displayed_userid', array( $id ), '12.0.0' );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * No longer used in BuddyPress.
 *
 * @deprecated 12.0.0
 *
 * @param int $user_id ID of the user being queried.
 * @return array Post IDs.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '12.0.0' );
	global $wpdb;

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$all_posts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) );

	return apply_filters( 'bp_core_get_all_posts_for_user', array( $all_posts ), '12.0.0' );
}

/**
 * Repair user last_activity data.
 *
 * Re-runs the migration from usermeta introduced in BP 2.0.
 *
 * @since 2.0.0
 * @deprecated 12.4.0
 */
function bp_admin_repair_last_activity() {
	_deprecated_function( __FUNCTION__, '12.4.0' );
	return array();
}
