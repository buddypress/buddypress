<?php
/**
 * Members functions
 *
 * @since 3.0.0
 * @version 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Members component
 *
 * @since 8.0.0
 *
 * @param array $scripts Optional. The array of scripts to register.
 * @return array The same array with the specific members scripts.
 */
function bp_nouveau_members_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) || ! bp_get_members_invitations_allowed() ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-member-invites' => array(
			'file'         => 'js/buddypress-member-invites%s.js',
			'dependencies' => array(),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the members scripts
 *
 * @since 3.0.0
 */
function bp_nouveau_members_enqueue_scripts() {
	// Neutralize Ajax when using BuddyPress Groups & member widgets on default front page
	if ( bp_is_user_front() && bp_nouveau_get_appearance_settings( 'user_front_page' ) ) {
		wp_add_inline_style(
			'bp-nouveau',
			'#member-front-widgets #groups-list-options,
			#member-front-widgets #members-list-options,
			#member-front-widgets #friends-list-options {
				display: none;
			}'
		);
	}

	if ( bp_is_user_members_invitations_list() ) {
		wp_enqueue_script( 'bp-nouveau-member-invites' );
	}
}

/**
 * Get the nav items for the Members directory
 *
 * @since 3.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_members_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'members',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array(),
		'link'      => bp_get_members_directory_permalink(),
		'text'      => __( 'All Members', 'buddypress' ),
		'count'     => bp_get_total_member_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		// If friends component is active and the user has friends
		if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
			$nav_items['personal'] = array(
				'component' => 'members',
				'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_nouveau_get_component_slug( 'friends' ) . '/my-friends/',
				'text'      => __( 'My Friends', 'buddypress' ),
				'count'     => bp_get_total_friend_count( bp_loggedin_user_id() ),
				'position'  => 15,
			);
		}
	}

	// Check for the deprecated hook :
	$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( 'bp_members_directory_member_types', 'members', 20 );
	if ( ! empty( $extra_nav_items ) ) {
		$nav_items = array_merge( $nav_items, $extra_nav_items );
	}

	/**
	 * Use this filter to introduce your custom nav items for the members directory.
	 *
	 * @since 3.0.0
	 *
	 * @param array $nav_items The list of the members directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_members_directory_nav_items', $nav_items );
}

/**
 * Get Dropdown filters for the members component
 *
 * @since 3.0.0
 *
 * @param string $context Optional.
 *
 * @return array the filters
 */
function bp_nouveau_get_members_filters( $context = '' ) {
	if ( 'group' !== $context ) {
		$filters = array(
			'active' => __( 'Last Active', 'buddypress' ),
			'newest' => __( 'Newest Registered', 'buddypress' ),
		);

		if ( bp_is_active( 'xprofile' ) ) {
			$filters['alphabetical'] = __( 'Alphabetical', 'buddypress' );
		}

		$action = 'bp_members_directory_order_options';

		if ( 'friends' === $context ) {
			$action = 'bp_member_friends_order_options';
		}
	} else {
		$filters = array(
			'last_joined'  => __( 'Newest', 'buddypress' ),
			'first_joined' => __( 'Oldest', 'buddypress' ),
		);

		if ( bp_is_active( 'activity' ) ) {
			$filters['group_activity'] = __( 'Group Activity', 'buddypress' );
		}

		$filters['alphabetical'] = __( 'Alphabetical', 'buddypress' );
		$action                  = 'bp_groups_members_order_options';
	}

	/**
	 * Recommended, filter here instead of adding an action to 'bp_members_directory_order_options'
	 *
	 * @since 3.0.0
	 *
	 * @param array  the members filters.
	 * @param string the context.
	 */
	$filters = apply_filters( 'bp_nouveau_get_members_filters', $filters, $context );

	return bp_nouveau_parse_hooked_options( $action, $filters );
}

/**
 * Catch the content hooked to the do_action hooks in single member header
 * and in the members loop.
 *
 * @since 3.0.0
 * @since 6.0.0 Replace wrongly positioned `bp_directory_members_item`
 *              with `bp_directory_members_item_meta`
 *
 * @return string|false HTML Output if hooked. False otherwise.
 */
function bp_nouveau_get_hooked_member_meta() {
	ob_start();

	if ( ! empty( $GLOBALS['members_template'] ) ) {
		/**
		 * Fires inside the display of metas in the directory member item.
		 *
		 * @since 6.0.0
		 */
		do_action( 'bp_directory_members_item_meta' );

	// It's the user's header
	} else {
		/**
		 * Fires after the group header actions section.
		 *
		 * If you'd like to show specific profile fields here use:
		 * bp_member_profile_data( 'field=About Me' ); -- Pass the name of the field
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_profile_header_meta' );
	}

	$output = ob_get_clean();

	if ( ! empty( $output ) ) {
		return $output;
	}

	return false;
}

/**
 * Add the default user front template to the front template hierarchy
 *
 * @since 3.0.0
 *
 * @param array $templates The list of templates for the front.php template part.
 *
 * @return array The same list with the default front template if needed.
 */
function bp_nouveau_member_reset_front_template( $templates = array() ) {
	$use_default_front = bp_nouveau_get_appearance_settings( 'user_front_page' );

	// Setting the front template happens too early, so we need this!
	if ( is_customize_preview() ) {
		$use_default_front = bp_nouveau_get_temporary_setting( 'user_front_page', $use_default_front );
	}

	if ( ! empty( $use_default_front ) ) {
		array_push( $templates, 'members/single/default-front.php' );
	}

	/**
	 * Filters the BuddyPress Nouveau template hierarchy after resetting front template for members.
	 *
	 * @since 3.0.0
	 *
	 * @param array $templates Array of templates.
	 */
	return apply_filters( '_bp_nouveau_member_reset_front_template', $templates );
}

/**
 * Only locate global user's front templates
 *
 * @since 3.0.0
 *
 * @param array $templates The User's front template hierarchy.
 *
 * @return array Only the global front templates.
 */
function bp_nouveau_member_restrict_user_front_templates( $templates = array() ) {
	return array_intersect( array(
		'members/single/front.php',
		'members/single/default-front.php',
	), $templates );
}

/**
 * Locate a single member template into a specific hierarchy.
 *
 * @since 3.0.0
 *
 * @param string $template The template part to get (eg: activity, groups...).
 *
 * @return string The located template.
 */
function bp_nouveau_member_locate_template_part( $template = '' ) {
	$displayed_user = bp_get_displayed_user();
	$bp_nouveau     = bp_nouveau();

	if ( ! $template || empty( $displayed_user->id ) ) {
		return '';
	}

	// Use a global to avoid requesting the hierarchy for each template
	if ( ! isset( $bp_nouveau->members->displayed_user_hierarchy ) ) {
		$bp_nouveau->members->displayed_user_hierarchy = array(
			'members/single/%s-id-' . (int) $displayed_user->id . '.php',
			'members/single/%s-nicename-' . sanitize_file_name( $displayed_user->userdata->user_nicename ) . '.php',
		);

		/*
		 * Check for member types and add it to the hierarchy
		 *
		 * Make sure to register your member
		 * type using the hook 'bp_register_member_types'
		 */
		if ( bp_get_member_types() ) {
			$displayed_user_member_type = bp_get_member_type( $displayed_user->id );
			if ( ! $displayed_user_member_type ) {
				$displayed_user_member_type = 'none';
			}

			$bp_nouveau->members->displayed_user_hierarchy[] = 'members/single/%s-member-type-' . sanitize_file_name( $displayed_user_member_type ) . '.php';
		}

		// And the regular one
		$bp_nouveau->members->displayed_user_hierarchy[] = 'members/single/%s.php';
	}

	$templates = array();

	// Loop in the hierarchy to fill it for the requested template part
	foreach ( $bp_nouveau->members->displayed_user_hierarchy as $part ) {
		$templates[] = sprintf( $part, $template );
	}

	/**
	 * Filters the found template parts for the member template part locating functionality.
	 *
	 * @since 3.0.0
	 *
	 * @param array $templates Array of found templates.
	 */
	return bp_locate_template( apply_filters( 'bp_nouveau_member_locate_template_part', $templates ), false, true );
}

/**
 * Load a single member template part
 *
 * @since 3.0.0
 *
 * @param string $template The template part to get (eg: activity, groups...).
 *
 * @return string HTML output.
 */
function bp_nouveau_member_get_template_part( $template = '' ) {
	$located = bp_nouveau_member_locate_template_part( $template );

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here.
		 *
		 * @since 3.0.0
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Display the User's WordPress bio info into the default front page?
 *
 * @since 3.0.0
 *
 * @return bool True to display. False otherwise.
 */
function bp_nouveau_members_wp_bio_info() {
	$user_settings = bp_nouveau_get_appearance_settings();

	return ! empty( $user_settings['user_front_page'] ) && ! empty( $user_settings['user_front_bio'] );
}

/**
 * Are we inside the Current user's default front page sidebar?
 *
 * @since 3.0.0
 *
 * @return bool True if in the group's home sidebar. False otherwise.
 */
function bp_nouveau_member_is_home_widgets() {
	return ( true === bp_nouveau()->members->is_user_home_sidebar );
}

/**
 * Filter the Latest activities Widget to only keep the one of displayed user
 *
 * @since 3.0.0
 *
 * @param array $args The Activities Template arguments.
 *
 * @return array The Activities Template arguments.
 */
function bp_nouveau_member_activity_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'user_id' => bp_displayed_user_id(),
	) );
}

/**
 * Filter the Groups widget to only keep the groups the displayed user is a member of.
 *
 * @since 3.0.0
 *
 * @param array $args The Groups Template arguments.
 *
 * @return array The Groups Template arguments.
 */
function bp_nouveau_member_groups_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'user_id' => bp_displayed_user_id(),
	) );
}

/**
 * Filter the Members widgets to only keep members of the displayed group.
 *
 * @since 3.0.0
 *
 * @param array $args The Members Template arguments.
 *
 * @return array The Members Template arguments.
 */
function bp_nouveau_member_members_widget_overrides( $args = array() ) {
	// Do nothing for the friends widget
	if ( ! empty( $args['user_id'] ) && (int) $args['user_id'] === (int) bp_displayed_user_id() ) {
		return $args;
	}

	return array_merge( $args, array(
		'include' => bp_displayed_user_id(),
	) );
}

/**
 * Init the Member's default front page filters as we're in the sidebar
 *
 * @since 3.0.0
 */
function bp_nouveau_members_add_home_widget_filters() {
	add_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_member_activity_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_member_groups_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_member_members_widget_overrides', 10, 1 );

	/**
	 * Fires after Nouveau adds its members home widget filters.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_nouveau_members_add_home_widget_filters' );
}

/**
 * Remove the Member's default front page filters as we're no more in the sidebar
 *
 * @since 3.0.0
 */
function bp_nouveau_members_remove_home_widget_filters() {
	remove_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_member_activity_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_member_groups_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_member_members_widget_overrides', 10, 1 );

	/**
	 * Fires after Nouveau removes its members home widget filters.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_nouveau_members_remove_home_widget_filters' );
}

/**
 * Get the WP Profile fields for all or a specific user
 *
 * @since 3.0.0
 *
 * @param WP_User $user The user object. Optional.
 *
 * @return array The list of WP Profile fields
 */
function bp_nouveau_get_wp_profile_fields( $user = null ) {
	/**
	 * Filters the contact methods to be included in the WP Profile fields for a specific user.
	 *
	 * Provide a chance for plugins to avoid showing the contact methods they're adding on front end.
	 *
	 * @since 3.0.0
	 *
	 * @param array   $value Array of user contact methods.
	 * @param WP_User $user  WordPress user to get contact methods for.
	 */
	$contact_methods = (array) apply_filters( 'bp_nouveau_get_wp_profile_field', wp_get_user_contact_methods( $user ), $user );

	$wp_fields = array(
		'display_name'     => __( 'Name', 'buddypress' ),
		'user_description' => __( 'About Me', 'buddypress' ),
		'user_url'         => __( 'Website', 'buddypress' ),
	);

	return array_merge( $wp_fields, $contact_methods );
}

/**
 * Build the Member's nav for the our customizer control.
 *
 * @since 3.0.0
 *
 * @return array The Members single item primary nav ordered.
 */
function bp_nouveau_member_customizer_nav() {
	add_filter( '_bp_nouveau_member_reset_front_template', 'bp_nouveau_member_restrict_user_front_templates', 10, 1 );

	if ( bp_displayed_user_get_front_template( buddypress()->loggedin_user ) ) {
		buddypress()->members->nav->add_nav(
			array(
				'name'     => _x( 'Home', 'Member Home page', 'buddypress' ),
				'slug'     => 'front',
				'position' => 5,
			)
		);
	}

	remove_filter( '_bp_nouveau_member_reset_front_template', 'bp_nouveau_member_restrict_user_front_templates', 10, 1 );

	$nav = buddypress()->members->nav;

	// Eventually reset the order.
	bp_nouveau_set_nav_item_order( $nav, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );

	return $nav->get_primary();
}

/**
 * Includes additional information about the Members loop Ajax response.
 *
 * @since 10.0.0
 *
 * @param array $additional_info An associative array with additional information to include in the Ajax response.
 * @param array $args            The Ajax query arguments.
 * @return array                 Additional information about the members loop.
 */
function bp_nouveau_members_loop_additional_info( $additional_info = array(), $args = array() ) {
	if ( ! isset( $GLOBALS['members_template'] ) || ! $GLOBALS['members_template'] ) {
		return $additional_info;
	}

	$members_template = $GLOBALS['members_template'];

	if ( isset( $members_template->member_count ) && 'all' === $args['scope'] ) {
		$additional_info['totalItems'] = bp_core_number_format( $members_template->member_count );
		$additional_info['navLabel']   = esc_html__( 'All Members', 'buddypress' );

		$nav_labels = array(
			'active' => esc_html__( 'Active Members', 'buddypress' ),
			'newest' => esc_html__( 'Newest Members', 'buddypress' ),
		);

		if ( isset( $nav_labels[ $args['filter'] ] ) ) {
			$additional_info['navLabel'] = $nav_labels[ $args['filter'] ];
		}
	}

	return $additional_info;
}
add_filter( 'bp_nouveau_members_ajax_object_template_response', 'bp_nouveau_members_loop_additional_info', 10, 2 );
