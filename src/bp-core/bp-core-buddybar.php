<?php
/**
 * Core BuddyPress Navigational Functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.5.0
 *
 * @todo Deprecate BuddyBar functions.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add an item to the primary navigation of the specified component.
 *
 * @since 1.1.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param array|string $args {
 *     Array describing the new nav item.
 *     @type string      $name                    Display name for the nav item.
 *     @type string      $slug                    Unique URL slug for the nav item.
 *     @type bool|string $item_css_id             Optional. 'id' attribute for the nav item. Default: the value of `$slug`.
 *     @type bool        $show_for_displayed_user Optional. Whether the nav item should be visible when viewing a
 *                                                member profile other than your own. Default: true.
 *     @type bool        $site_admin_only         Optional. Whether the nav item should be visible only to site admins
 *                                                (those with the 'bp_moderate' cap). Default: false.
 *     @type int         $position                Optional. Numerical index specifying where the item should appear in
 *                                                the nav array. Default: 99.
 *     @type callable    $screen_function         The callback function that will run when the nav item is clicked.
 *     @type bool|string $default_subnav_slug     Optional. The slug of the default subnav item to select when the nav
 *                                                item is clicked.
 * }
 * @param string       $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool|null Returns false on failure.
 */
function bp_core_new_nav_item( $args, $component = 'members' ) {
	if ( ! bp_is_active( $component ) ) {
		return;
	}

	$defaults = array(
		'name'                    => false, // Display name for the nav item.
		'slug'                    => false, // URL slug for the nav item.
		'item_css_id'             => false, // The CSS ID to apply to the HTML of the nav item.
		'show_for_displayed_user' => true,  // When viewing another user does this nav item show up?
		'site_admin_only'         => false, // Can only site admins see this nav item?
		'position'                => 99,    // Index of where this nav item should be positioned.
		'screen_function'         => false, // The name of the function to run when clicked.
		'default_subnav_slug'     => false  // The slug of the default subnav item to select when clicked.
	);

	$r = wp_parse_args( $args, $defaults );

	// Validate nav link data.
	$nav_item = bp_core_create_nav_link( $r, $component );

	/*
	 * To mimic legacy behavior, if bp_core_create_nav_link() returns false, we make
	 * an early exit and don't attempt to register the screen function.
	 */
	if ( false === $nav_item ) {
		return false;
	}

	// Then, hook the screen function for the added nav item.
	$hooked = bp_core_register_nav_screen_function( $nav_item );
	if ( false === $hooked ){
		return false;
	}

	/**
	 * Fires after adding an item to the main BuddyPress navigation array.
	 * Note that, when possible, the more specific action hooks
	 * `bp_core_create_nav_link` or `bp_core_register_nav_screen_function`
	 * should be used.
	 *
	 * @since 1.5.0
	 *
	 * @param array $r        Parsed arguments for the nav item.
	 * @param array $args     Originally passed in arguments for the nav item.
	 * @param array $defaults Default arguments for a nav item.
	 */
	do_action( 'bp_core_new_nav_item', $r, $args, $defaults );
}

/**
 * Add a link to the main BuddyPress navigation.
 *
 * @since 2.4.0
 * @since 2.6.0 Introduced the `$component` parameter. Began returning a BP_Nav_Item object on success.
 *
 * @param array|string $args {
 *     Array describing the new nav item.
 *     @type string      $name                    Display name for the nav item.
 *     @type string      $slug                    Unique URL slug for the nav item.
 *     @type bool|string $item_css_id             Optional. 'id' attribute for the nav item. Default: the value of `$slug`.
 *     @type bool        $show_for_displayed_user Optional. Whether the nav item should be visible when viewing a
 *                                                member profile other than your own. Default: true.
 *     @type bool        $site_admin_only         Optional. Whether the nav item should be visible only to site admins
 *                                                (those with the 'bp_moderate' cap). Default: false.
 *     @type int         $position                Optional. Numerical index specifying where the item should appear in
 *                                                the nav array. Default: 99.
 *     @type callable    $screen_function         The callback function that will run when the nav item is clicked.
 *     @type bool|string $default_subnav_slug     Optional. The slug of the default subnav item to select when the nav
 *                                                item is clicked.
 * }
 * @param string       $component Optional. Component that the nav belongs to.
 * @return bool|BP_Nav_Item Returns false on failure, new nav item on success.
 */
function bp_core_create_nav_link( $args = '', $component = 'members' ) {
	$bp = buddypress();

	$defaults = array(
		'name'                    => false, // Display name for the nav item.
		'slug'                    => false, // URL slug for the nav item.
		'item_css_id'             => false, // The CSS ID to apply to the HTML of the nav item.
		'show_for_displayed_user' => true,  // When viewing another user does this nav item show up?
		'site_admin_only'         => false, // Can only site admins see this nav item?
		'position'                => 99,    // Index of where this nav item should be positioned.
		'screen_function'         => false, // The name of the function to run when clicked.
		'default_subnav_slug'     => false  // The slug of the default subnav item to select when clicked.
	);

	$r = wp_parse_args( $args, $defaults );

	// If we don't have the required info we need, don't create this nav item.
	if ( empty( $r['name'] ) || empty( $r['slug'] ) ) {
		return false;
	}

	// If this is for site admins only and the user is not one, don't create the nav item.
	if ( ! empty( $r['site_admin_only'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	if ( empty( $r['item_css_id'] ) ) {
		$r['item_css_id'] = $r['slug'];
	}

	$nav_item = array(
		'name'                    => $r['name'],
		'slug'                    => $r['slug'],
		'link'                    => trailingslashit( bp_loggedin_user_domain() . $r['slug'] ),
		'css_id'                  => $r['item_css_id'],
		'show_for_displayed_user' => $r['show_for_displayed_user'],
		'position'                => $r['position'],
		'screen_function'         => &$r['screen_function'],
		'default_subnav_slug'	  => $r['default_subnav_slug']
	);

	// Add the item to the nav.
	buddypress()->{$component}->nav->add_nav( $nav_item );

	/**
	 * Fires after a link is added to the main BuddyPress nav.
	 *
	 * @since 2.4.0
	 * @since 2.6.0 Added `$component` parameter.
	 *
	 * @param array  $r         Parsed arguments for the nav item.
	 * @param array  $args      Originally passed in arguments for the nav item.
	 * @param array  $defaults  Default arguments for a nav item.
	 * @param string $component Component that the nav belongs to.
	 */
	do_action( 'bp_core_create_nav_link', $r, $args, $defaults, $component );

	return $nav_item;
}

/**
 * Register a screen function for an item in the main nav array.
 *
 * @since 2.4.0
 *
 * @param array|string $args {
 *     Array describing the new nav item.
 *     @type string      $name                    Display name for the nav item.
 *     @type string      $slug                    Unique URL slug for the nav item.
 *     @type bool|string $item_css_id             Optional. 'id' attribute for the nav item. Default: the value of `$slug`.
 *     @type bool        $show_for_displayed_user Optional. Whether the nav item should be visible when viewing a
 *                                                member profile other than your own. Default: true.
 *     @type bool        $site_admin_only         Optional. Whether the nav item should be visible only to site admins
 *                                                (those with the 'bp_moderate' cap). Default: false.
 *     @type int         $position                Optional. Numerical index specifying where the item should appear in
 *                                                the nav array. Default: 99.
 *     @type callable    $screen_function         The callback function that will run when the nav item is clicked.
 *     @type bool|string $default_subnav_slug     Optional. The slug of the default subnav item to select when the nav
 *                                                item is clicked.
 * }
 * @return bool|null Returns false on failure.
 */
function bp_core_register_nav_screen_function( $args = '' ) {
	$bp = buddypress();

	$defaults = array(
		'name'                    => false, // Display name for the nav item.
		'slug'                    => false, // URL slug for the nav item.
		'item_css_id'             => false, // The CSS ID to apply to the HTML of the nav item.
		'show_for_displayed_user' => true,  // When viewing another user does this nav item show up?
		'site_admin_only'         => false, // Can only site admins see this nav item?
		'position'                => 99,    // Index of where this nav item should be positioned.
		'screen_function'         => false, // The name of the function to run when clicked.
		'default_subnav_slug'     => false  // The slug of the default subnav item to select when clicked.
	);

	$r = wp_parse_args( $args, $defaults );

	// If we don't have the required info we need, don't register this screen function.
	if ( empty( $r['slug'] ) ) {
		return false;
	}

	/**
	 * If this is for site admins only and the user is not one,
	 * don't register this screen function.
	 */
	if ( ! empty( $r['site_admin_only'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	/**
	 * If this nav item is hidden for the displayed user, and
	 * the logged in user is not the displayed user
	 * looking at their own profile, don't don't register this screen function.
	 */
	if ( empty( $r['show_for_displayed_user'] ) && ! bp_user_has_access() ) {
		return false;
	}

	/**
	 * If the nav item is visible, we are not viewing a user, and this is a root
	 * component, don't attach the default subnav function so we can display a
	 * directory or something else.
	 */
	if ( ( -1 != $r['position'] ) && bp_is_root_component( $r['slug'] ) && ! bp_displayed_user_id() ) {
		return;
	}

	// Look for current component.
	if ( bp_is_current_component( $r['slug'] ) || bp_is_current_item( $r['slug'] ) ) {

		// The requested URL has explicitly included the default subnav
		// (eg: http://example.com/members/membername/activity/just-me/)
		// The canonical version will not contain this subnav slug.
		if ( ! empty( $r['default_subnav_slug'] ) && bp_is_current_action( $r['default_subnav_slug'] ) && ! bp_action_variable( 0 ) ) {
			unset( $bp->canonical_stack['action'] );
		} elseif ( ! bp_current_action() ) {

			// Add our screen hook if screen function is callable.
			if ( is_callable( $r['screen_function'] ) ) {
				add_action( 'bp_screens', $r['screen_function'], 3 );
			}

			if ( ! empty( $r['default_subnav_slug'] ) ) {

				/**
				 * Filters the default component subnav item.
				 *
				 * @since 1.5.0
				 *
				 * @param string $value The slug of the default subnav item
				 *                      to select when clicked.
				 * @param array  $r     Parsed arguments for the nav item.
				 */
				$bp->current_action = apply_filters( 'bp_default_component_subnav', $r['default_subnav_slug'], $r );
			}
		}
	}

	/**
	 * Fires after the screen function for an item in the BuddyPress main
	 * navigation is registered.
	 *
	 * @since 2.4.0
	 *
	 * @param array $r        Parsed arguments for the nav item.
	 * @param array $args     Originally passed in arguments for the nav item.
	 * @param array $defaults Default arguments for a nav item.
	 */
	do_action( 'bp_core_register_nav_screen_function', $r, $args, $defaults );
}

/**
 * Modify the default subnav item that loads when a top level nav item is clicked.
 *
 * @since 1.1.0
 *
 * @param array|string $args {
 *     @type string   $parent_slug     The slug of the nav item whose default is being changed.
 *     @type callable $screen_function The new default callback function that will run when the nav item is clicked.
 *     @type string   $subnav_slug     The slug of the new default subnav item.
 * }
 */
function bp_core_new_nav_default( $args = '' ) {
	$bp = buddypress();

	$defaults = array(
		'parent_slug'     => false, // Slug of the parent.
		'screen_function' => false, // The name of the function to run when clicked.
		'subnav_slug'     => false  // The slug of the subnav item to select when clicked.
	);

	$r = wp_parse_args( $args, $defaults );

	// This is specific to Members - it's not available in Groups.
	$parent_nav = $bp->members->nav->get_primary( array( 'slug' => $r['parent_slug'] ), false );

	if ( ! $parent_nav ) {
		return ;
	}

	$parent_nav = reset( $parent_nav );

	if ( ! empty( $parent_nav->screen_function ) ) {
		// Remove our screen hook if screen function is callable.
		if ( is_callable( $parent_nav->screen_function ) ) {
			remove_action( 'bp_screens', $parent_nav->screen_function, 3 );
		}
	}

	// Edit the screen function for the parent nav.
	$bp->members->nav->edit_nav( array(
		'screen_function'     => &$r['screen_function'],
		'default_subnav_slug' => $r['subnav_slug'],
	), $parent_nav->slug );

	if ( bp_is_current_component( $parent_nav->slug ) ) {

		// The only way to tell whether to set the subnav is to peek at the unfiltered_uri
		// Find the component.
		$component_uri_key = array_search( $parent_nav->slug, $bp->unfiltered_uri );

		if ( false !== $component_uri_key ) {
			if ( ! empty( $bp->unfiltered_uri[$component_uri_key + 1] ) ) {
				$unfiltered_action = $bp->unfiltered_uri[$component_uri_key + 1];
			}
		}

		// No subnav item has been requested in the URL, so set a new nav default.
		if ( empty( $unfiltered_action ) ) {
			if ( ! bp_is_current_action( $r['subnav_slug'] ) ) {
				if ( is_callable( $r['screen_function'] ) ) {
					add_action( 'bp_screens', $r['screen_function'], 3 );
				}

				$bp->current_action = $r['subnav_slug'];
				unset( $bp->canonical_stack['action'] );
			}

		// The URL is explicitly requesting the new subnav item, but should be
		// directed to the canonical URL.
		} elseif ( $unfiltered_action == $r['subnav_slug'] ) {
			unset( $bp->canonical_stack['action'] );

		// In all other cases (including the case where the original subnav item
		// is explicitly called in the URL), the canonical URL will contain the
		// subnav slug.
		} else {
			$bp->canonical_stack['action'] = bp_current_action();
		}
	}

	return;
}

/**
 * Add an item to secondary navigation of the specified component.
 *
 * @since 1.1.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param array|string $args {
 *     Array describing the new subnav item.
 *     @type string      $name              Display name for the subnav item.
 *     @type string      $slug              Unique URL slug for the subnav item.
 *     @type string      $parent_slug       Slug of the top-level nav item under which the new subnav item should
 *                                          be added.
 *     @type string      $parent_url        URL of the parent nav item.
 *     @type bool|string $item_css_id       Optional. 'id' attribute for the nav item. Default: the value of `$slug`.
 *     @type bool        $user_has_access   Optional. True if the logged-in user has access to the subnav item,
 *                                          otherwise false. Can be set dynamically when registering the subnav;
 *                                          eg, use `bp_is_my_profile()` to restrict access to profile owners only.
 *                                          Default: true.
 *     @type bool        $site_admin_only   Optional. Whether the nav item should be visible only to site admins
 *                                          (those with the 'bp_moderate' cap). Default: false.
 *     @type int         $position          Optional. Numerical index specifying where the item should appear in the
 *                                          subnav array. Default: 90.
 *     @type callable    $screen_function   The callback function that will run when the nav item is clicked.
 *     @type string      $link              Optional. The URL that the subnav item should point to. Defaults to a value
 *                                          generated from the `$parent_url` + `$slug`.
 *     @type bool        $show_in_admin_bar Optional. Whether the nav item should be added into the group's "Edit"
 *                                          Admin Bar menu for group admins. Default: false.
 * }
 * @param string       $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool|null Returns false on failure.
 */
function bp_core_new_subnav_item( $args, $component = null ) {
	// Backward compatibility for plugins using `bp_core_new_subnav_item()` without `$component`
	// to add group subnav items.
	if ( null === $component && bp_is_active( 'groups' ) && bp_is_group() && isset( $args['parent_slug'] ) ) {
		/*
		 * Assume that this item is intended to belong to the current group if:
		 * a) the 'parent_slug' is the same as the slug of the current group, or
		 * b) the 'parent_slug' starts with the slug of the current group, and the members nav doesn't have
		 *    a primary item with that slug.
		 */
		$group_slug = bp_get_current_group_slug();
		if (
			$group_slug === $args['parent_slug'] ||
			( 0 === strpos( $args['parent_slug'], $group_slug ) && ! buddypress()->members->nav->get_primary( array( 'slug' => $args['parent_slug'] ), false ) )
		) {
			$component = 'groups';
		}
	}

	if ( ! $component ) {
		$component = 'members';
	}

	if ( ! bp_is_active( $component ) ) {
		return;
	}

	// First, register the subnav item in the nav.
	$subnav_item = bp_core_create_subnav_link( $args, $component );

	/*
	 * To mimic legacy behavior, if bp_core_create_subnav_link() returns false, we make an
	 * early exit and don't attempt to register the screen function.
	 */
	if ( false === $subnav_item ) {
		return false;
	}

	// Then, hook the screen function for the added subnav item.
	$hooked = bp_core_register_subnav_screen_function( $subnav_item, $component );
	if ( false === $hooked ) {
		return false;
	}
}

/**
 * Add a subnav link to the BuddyPress navigation.
 *
 * @since 2.4.0
 * @since 2.6.0 Introduced the `$component` parameter. Began returning a BP_Nav_Item object on success.
 *
 * @param array|string $args {
 *     Array describing the new subnav item.
 *     @type string      $name              Display name for the subnav item.
 *     @type string      $slug              Unique URL slug for the subnav item.
 *     @type string      $parent_slug       Slug of the top-level nav item under which the
 *                                          new subnav item should be added.
 *     @type string      $parent_url        URL of the parent nav item.
 *     @type bool|string $item_css_id       Optional. 'id' attribute for the nav
 *                                          item. Default: the value of $slug.
 *     @type bool        $user_has_access   Optional. True if the logged-in user has access to the
 *                                          subnav item, otherwise false. Can be set dynamically
 *                                          when registering the subnav; eg, use bp_is_my_profile()
 *                                          to restrict access to profile owners only. Default: true.
 *     @type bool        $site_admin_only   Optional. Whether the nav item should be visible only
 *                                          to site admins (those with the 'bp_moderate' cap).
 *                                          Default: false.
 *     @type int         $position          Optional. Numerical index specifying where the item
 *                                          should appear in the subnav array. Default: 90.
 *     @type callable    $screen_function   The callback function that will run
 *                                          when the nav item is clicked.
 *     @type string      $link              Optional. The URL that the subnav item should point
 *                                          to. Defaults to a value generated from the $parent_url + $slug.
 *     @type bool        $show_in_admin_bar Optional. Whether the nav item should be added into
 *                                          the group's "Edit" Admin Bar menu for group admins.
 *                                          Default: false.
 * }
 * @param string       $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool|object Returns false on failure, new BP_Nav_Item instance on success.
 */
function bp_core_create_subnav_link( $args = '', $component = 'members' ) {
	$bp = buddypress();

	$r = wp_parse_args( $args, array(
		'name'              => false, // Display name for the nav item.
		'slug'              => false, // URL slug for the nav item.
		'parent_slug'       => false, // URL slug of the parent nav item.
		'parent_url'        => false, // URL of the parent item.
		'item_css_id'       => false, // The CSS ID to apply to the HTML of the nav item.
		'user_has_access'   => true,  // Can the logged in user see this nav item?
		'no_access_url'     => '',
		'site_admin_only'   => false, // Can only site admins see this nav item?
		'position'          => 90,    // Index of where this nav item should be positioned.
		'screen_function'   => false, // The name of the function to run when clicked.
		'link'              => '',    // The link for the subnav item; optional, not usually required.
		'show_in_admin_bar' => false, // Show the Manage link in the current group's "Edit" Admin Bar menu.
	) );

	// If we don't have the required info we need, don't create this subnav item.
	if ( empty( $r['name'] ) || empty( $r['slug'] ) || empty( $r['parent_slug'] ) || empty( $r['parent_url'] ) || empty( $r['screen_function'] ) )
		return false;

	// Link was not forced, so create one.
	if ( empty( $r['link'] ) ) {
		$r['link'] = trailingslashit( $r['parent_url'] . $r['slug'] );

		$parent_nav = $bp->{$component}->nav->get_primary( array( 'slug' => $r['parent_slug'] ), false );

		// If this sub item is the default for its parent, skip the slug.
		if ( $parent_nav ) {
			$parent_nav_item = reset( $parent_nav );
			if ( ! empty( $parent_nav_item->default_subnav_slug ) && $r['slug'] === $parent_nav_item->default_subnav_slug ) {
				$r['link'] = trailingslashit( $r['parent_url'] );
			}
		}
	}

	// If this is for site admins only and the user is not one, don't create the subnav item.
	if ( ! empty( $r['site_admin_only'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	if ( empty( $r['item_css_id'] ) ) {
		$r['item_css_id'] = $r['slug'];
	}

	$subnav_item = array(
		'name'              => $r['name'],
		'link'              => $r['link'],
		'slug'              => $r['slug'],
		'parent_slug'       => $r['parent_slug'],
		'css_id'            => $r['item_css_id'],
		'position'          => $r['position'],
		'user_has_access'   => $r['user_has_access'],
		'no_access_url'     => $r['no_access_url'],
		'screen_function'   => &$r['screen_function'],
		'show_in_admin_bar' => (bool) $r['show_in_admin_bar'],
	);

	buddypress()->{$component}->nav->add_nav( $subnav_item );

	return $subnav_item;
}

/**
 * Register a screen function, whether or not a related subnav link exists.
 *
 * @since 2.4.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param array|string $args {
 *     Array describing the new subnav item.
 *     @type string   $slug              Unique URL slug for the subnav item.
 *     @type string   $parent_slug       Slug of the top-level nav item under which the
 *                                       new subnav item should be added.
 *     @type string   $parent_url        URL of the parent nav item.
 *     @type bool     $user_has_access   Optional. True if the logged-in user has access to the
 *                                       subnav item, otherwise false. Can be set dynamically
 *                                       when registering the subnav; eg, use bp_is_my_profile()
 *                                       to restrict access to profile owners only. Default: true.
 *     @type bool     $site_admin_only   Optional. Whether the nav item should be visible
 *                                       only to site admins (those with the 'bp_moderate' cap).
 *                                       Default: false.
 *     @type int      $position          Optional. Numerical index specifying where the item
 *                                       should appear in the subnav array. Default: 90.
 *     @type callable $screen_function   The callback function that will run
 *                                       when the nav item is clicked.
 *     @type string   $link              Optional. The URL that the subnav item should point to.
 *                                       Defaults to a value generated from the $parent_url + $slug.
 *     @type bool     $show_in_admin_bar Optional. Whether the nav item should be added into
 *                                       the group's "Edit" Admin Bar menu for group admins.
 *                                       Default: false.
 * }
 * @param string       $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool|null Returns false on failure.
 */
function bp_core_register_subnav_screen_function( $args = '', $component = 'members' ) {
	$bp = buddypress();

	$r = wp_parse_args( $args, array(
		'slug'              => false, // URL slug for the screen.
		'parent_slug'       => false, // URL slug of the parent screen.
		'user_has_access'   => true,  // Can the user visit this screen?
		'no_access_url'     => '',
		'site_admin_only'   => false, // Can only site admins visit this screen?
		'screen_function'   => false, // The name of the function to run when clicked.
	) );

	/*
	 * Hook the screen function for the added subnav item. But this only needs to
	 * be done if this subnav item is the current view, and the user has access to the
	 * subnav item. We figure out whether we're currently viewing this subnav by
	 * checking the following two conditions:
	 *   (1) Either:
	 *       (a) the parent slug matches the current_component, or
	 *       (b) the parent slug matches the current_item
	 *   (2) And either:
	 *       (a) the current_action matches $slug, or
	 *       (b) there is no current_action (ie, this is the default subnav for the parent nav)
	 *       and this subnav item is the default for the parent item (which we check by
	 *       comparing this subnav item's screen function with the screen function of the
	 *       parent nav item in the component's primary nav). This condition only arises
	 *       when viewing a user, since groups should always have an action set.
	 */

	// If we *don't* meet condition (1), return.
	if ( ! bp_is_current_component( $r['parent_slug'] ) && ! bp_is_current_item( $r['parent_slug'] ) ) {
		return;
	}

	$parent_nav = $bp->{$component}->nav->get_primary( array( 'slug' => $r['parent_slug'] ), false );

	// If we *do* meet condition (2), then the added subnav item is currently being requested.
	if ( ( bp_current_action() && bp_is_current_action( $r['slug'] ) ) || ( bp_is_user() && ! bp_current_action() && ! empty( $parent_nav->screen_function ) && $r['screen_function'] == $parent_nav->screen_function ) ) {

		// If this is for site admins only and the user is not one, don't create the subnav item.
		if ( ! empty( $r['site_admin_only'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}

		$hooked = bp_core_maybe_hook_new_subnav_screen_function( $r, $component );

		// If redirect args have been returned, perform the redirect now.
		if ( ! empty( $hooked['status'] ) && 'failure' === $hooked['status'] && isset( $hooked['redirect_args'] ) ) {
			bp_core_no_access( $hooked['redirect_args'] );
		}
	}
}

/**
 * For a given subnav item, either hook the screen function or generate redirect arguments, as necessary.
 *
 * @since 2.1.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param array  $subnav_item The subnav array added to the secondary navigation of
 *                            the component in bp_core_new_subnav_item().
 * @param string $component   The component the navigation is attached to. Defaults to 'members'.
 * @return array
 */
function bp_core_maybe_hook_new_subnav_screen_function( $subnav_item, $component = 'members' ) {
	$retval = array(
		'status' => '',
	);

	// Is this accessible by site admins only?
	$site_admin_restricted = false;
	if ( ! empty( $subnav_item['site_admin_only'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
		$site_admin_restricted = true;
	}

	// User has access, so let's try to hook the display callback.
	if ( ! empty( $subnav_item['user_has_access'] ) && ! $site_admin_restricted ) {

		// Screen function is invalid.
		if ( ! is_callable( $subnav_item['screen_function'] ) ) {
			$retval['status'] = 'failure';

		// Success - hook to bp_screens.
		} else {
			add_action( 'bp_screens', $subnav_item['screen_function'], 3 );
			$retval['status'] = 'success';
		}

	// User doesn't have access. Determine redirect arguments based on
	// user status.
	} else {
		$retval['status'] = 'failure';

		if ( is_user_logged_in() ) {

			$bp = buddypress();

			// If a redirect URL has been passed to the subnav
			// item, respect it.
			if ( ! empty( $subnav_item['no_access_url'] ) ) {
				$message     = __( 'You do not have access to this page.', 'buddypress' );
				$redirect_to = trailingslashit( $subnav_item['no_access_url'] );

			// In the case of a user page, we try to assume a
			// redirect URL.
			} elseif ( bp_is_user() ) {

				$parent_nav_default = $bp->{$component}->nav->get_primary( array( 'slug' => $bp->default_component ), false );
				if ( $parent_nav_default ) {
					$parent_nav_default_item = reset( $parent_nav_default );
				}

				// Redirect to the displayed user's default
				// component, as long as that component is
				// publicly accessible.
				if ( bp_is_my_profile() || ( isset( $parent_nav_default_item ) && $parent_nav_default_item->show_for_displayed_user ) ) {
					$message     = __( 'You do not have access to this page.', 'buddypress' );
					$redirect_to = bp_displayed_user_domain();

				// In some cases, the default tab is not accessible to
				// the logged-in user. So we fall back on a tab that we
				// know will be accessible.
				} else {
					// Try 'activity' first.
					if ( bp_is_active( 'activity' ) && isset( $bp->pages->activity ) ) {
						$redirect_to = trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() );
					// Then try 'profile'.
					} else {
						$redirect_to = trailingslashit( bp_displayed_user_domain() . ( 'xprofile' == $bp->profile->id ? 'profile' : $bp->profile->id ) );
					}

					$message     = '';
				}

			// Fall back to the home page.
			} else {
				$message     = __( 'You do not have access to this page.', 'buddypress' );
				$redirect_to = bp_get_root_domain();
			}

			$retval['redirect_args'] = array(
				'message'  => $message,
				'root'     => $redirect_to,
				'redirect' => false,
			);

		} else {
			// When the user is logged out, pass an empty array
			// This indicates that the default arguments should be
			// used in bp_core_no_access().
			$retval['redirect_args'] = array();
		}
	}

	return $retval;
}

/**
 * Check whether a given nav item has subnav items.
 *
 * @since 1.5.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param string $nav_item  The slug of the top-level nav item whose subnav items you're checking.
 *                          Default: the current component slug.
 * @param string $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool $has_subnav True if the nav item is found and has subnav items; false otherwise.
 */
function bp_nav_item_has_subnav( $nav_item = '', $component = 'members' ) {
	$bp = buddypress();

	if ( ! isset( $bp->{$component}->nav ) ) {
		return false;
	}

	if ( ! $nav_item ) {
		$nav_item = bp_current_component();

		if ( bp_is_group() ) {
			$nav_item = bp_current_item();
		}
	}

	$has_subnav = (bool) $bp->{$component}->nav->get_secondary( array( 'parent_slug' => $nav_item ), false );

	/**
	 * Filters whether or not a given nav item has subnav items.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $has_subnav Whether or not there is any subnav items.
	 * @param string $nav_item   The slug of the top-level nav item whose subnav items you're checking.
	 */
	return apply_filters( 'bp_nav_item_has_subnav', $has_subnav, $nav_item );
}

/**
 * Deletes an item from the primary navigation of the specified component.
 *
 * @since 1.0.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param string $slug      The slug of the primary navigation item.
 * @param string $component The component the navigation is attached to. Defaults to 'members'.
 * @return bool Returns false on failure, True on success.
 */
function bp_core_remove_nav_item( $slug, $component = null ) {
	$bp = buddypress();

	// Backward compatibility for removing group nav items using the group slug as `$parent_slug`.
	if ( ! $component && bp_is_active( 'groups' ) && isset( $bp->groups->nav ) ) {
		if ( $bp->groups->nav->get_primary( array( 'slug' => $slug ) ) ) {
			$component = 'groups';
		}
	}

	if ( ! $component ) {
		$component = 'members';
	}

	if ( ! isset( $bp->{$component}->nav ) ) {
		return false;
	}

	$screen_functions = $bp->{$component}->nav->delete_nav( $slug );

	// Reset backcompat nav items so that subsequent references will be correct.
	if ( buddypress()->do_nav_backcompat ) {
		$bp->bp_nav->reset();
		$bp->bp_options_nav->reset();
	}

	if ( ! is_array( $screen_functions ) ) {
		return false;
	}

	foreach ( $screen_functions as $screen_function ) {
		// Remove our screen hook if screen function is callable.
		if ( is_callable( $screen_function ) ) {
			remove_action( 'bp_screens', $screen_function, 3 );
		}
	}

	return true;
}

/**
 * Deletes an item from the secondary navigation of the specified component.
 *
 * @since 1.0.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param string $parent_slug The slug of the primary navigation item.
 * @param string $slug        The slug of the secondary item to be removed.
 * @param string $component   The component the navigation is attached to. Defaults to 'members'.
 * @return bool Returns false on failure, True on success.
 */
function bp_core_remove_subnav_item( $parent_slug, $slug, $component = null ) {
	$bp = buddypress();

	// Backward compatibility for removing group nav items using the group slug as `$parent_slug`.
	if ( ! $component && bp_is_active( 'groups' ) && isset( $bp->groups->nav ) ) {
		if ( $bp->groups->nav->get_primary( array( 'slug' => $parent_slug ) ) ) {
			$component = 'groups';
		}
	}

	if ( ! $component ) {
		$component = 'members';
	}

	if ( ! isset( $bp->{$component}->nav ) ) {
		return false;
	}

	$screen_functions = $bp->{$component}->nav->delete_nav( $slug, $parent_slug );

	// Reset backcompat nav items so that subsequent references will be correct.
	if ( buddypress()->do_nav_backcompat ) {
		$bp->bp_nav->reset();
		$bp->bp_options_nav->reset();
	}

	if ( ! is_array( $screen_functions ) ) {
		return false;
	}

	$screen_function = reset( $screen_functions );

	// Remove our screen hook if screen function is callable.
	if ( is_callable( $screen_function ) ) {
		remove_action( 'bp_screens', $screen_function, 3 );
	}

	return true;
}

/**
 * Clear all subnav items from a specific nav item.
 *
 * @since 1.0.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param string $parent_slug The slug of the parent navigation item.
 * @param string $component   The component the navigation is attached to. Defaults to 'members'.
 */
function bp_core_reset_subnav_items( $parent_slug, $component = 'members' ) {
	$bp = buddypress();

	if ( ! isset( $bp->{$component}->nav ) ) {
		return;
	}

	$subnav_items = $bp->{$component}->nav->get_secondary( array( 'parent_slug' => $parent_slug ), false );

	if ( ! $subnav_items ) {
		return;
	}

	foreach( $subnav_items as $subnav_item ) {
		$bp->{$component}->nav->delete_nav( $subnav_item->slug, $parent_slug );
	}
}


/**
 * Retrieve the Toolbar display preference of a user based on context.
 *
 * This is a direct copy of WP's private _get_admin_bar_pref()
 *
 * @since 1.5.0
 *
 * @param string $context Context of this preference check. 'admin' or 'front'.
 * @param int    $user    Optional. ID of the user to check. Default: 0 (which falls back to the logged-in user's ID).
 * @return bool True if the toolbar should be showing for this user.
 */
function bp_get_admin_bar_pref( $context, $user = 0 ) {
	$pref = get_user_option( "show_admin_bar_{$context}", $user );
	if ( false === $pref )
		return true;

	return 'true' === $pref;
}
