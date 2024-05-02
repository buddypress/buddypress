<?php
/**
 * BuddyPress Groups Template Functions.
 *
 * @package BuddyPress
 * @subpackage GroupsTemplates
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the groups component slug.
 *
 * @since 1.5.0
 */
function bp_groups_slug() {
	echo esc_attr( bp_get_groups_slug() );
}
	/**
	 * Return the groups component slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_groups_slug() {

		/**
		 * Filters the groups component slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $slug Groups component slug.
		 */
		return apply_filters( 'bp_get_groups_slug', buddypress()->groups->slug );
	}

/**
 * Output the groups component root slug.
 *
 * @since 1.5.0
 */
function bp_groups_root_slug() {
	echo esc_attr( bp_get_groups_root_slug() );
}
	/**
	 * Return the groups component root slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_groups_root_slug() {

		/**
		 * Filters the groups component root slug.
		 *
		 * @since 1.5.0
		 *
		 * @param string $root_slug Groups component root slug.
		 */
		return apply_filters( 'bp_get_groups_root_slug', buddypress()->groups->root_slug );
	}

/**
 * Output the group type base slug.
 *
 * @since 2.7.0
 */
function bp_groups_group_type_base() {
	echo esc_attr( bp_get_groups_group_type_base() );
}
	/**
	 * Get the group type base slug.
	 *
	 * The base slug is the string used as the base prefix when generating group
	 * type directory URLs. For example, in example.com/groups/type/foo/, 'foo' is
	 * the group type and 'type' is the base slug.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
	function bp_get_groups_group_type_base() {
		/**
		 * Filters the group type URL base.
		 *
		 * @since 2.7.0
		 *
		 * @param string $base Base slug of the group type.
		 */
		return apply_filters( 'bp_groups_group_type_base', _x( 'type', 'group type URL base', 'buddypress' ) );
	}

/**
 * Output Groups directory's URL.
 *
 * @since 12.0.0
 */
function bp_groups_directory_url() {
	echo esc_url( bp_get_groups_directory_url() );
}

/**
 * Returns the Groups directory's URL.
 *
 * @since 12.0.0
 *
 * @param array $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type int   $create_single_item `1` to get the create a group URL.
 *     @type array $directory_type     The group type slug.
 * }
 * @return string The URL built for the BP Rewrites URL parser.
 */
function bp_get_groups_directory_url( $path_chunks = array() ) {
	$supported_chunks = array_fill_keys( array( 'create_single_item', 'create_single_item_variables', 'directory_type' ), true );

	$path_chunks = bp_parse_args(
		array_intersect_key( $path_chunks, $supported_chunks ),
		array(
			'component_id' => 'groups'
		)
	);

	$url = bp_rewrites_get_url( $path_chunks );

	/**
	 * Filters the Groups directory's URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string  $url The Groups directory's URL.
	 * @param array   $path_chunks {
	 *     An array of arguments. Optional.
	 *
	 *      @type int   $create_single_item `1` to get the create a group URL.
	 *      @type array $directory_type     The group type slug.
	 * }
	 */
	return apply_filters( 'bp_get_groups_directory_url', $url, $path_chunks );
}

/**
 * Returns a group create URL accoding to requested path chunks.
 *
 * @since 12.0.0
 *
 * @param array $chunks array A list of create action variables.
 * @return string The group create URL.
 */
function bp_groups_get_create_url( $action_variables = array() ) {
	$path_chunks = array();

	if ( is_array( $action_variables ) && $action_variables ) {
		$path_chunks = bp_groups_get_path_chunks( $action_variables, 'create' );
	} else {
		$path_chunks = array(
			'create_single_item' => 1,
		);
	}

	return bp_get_groups_directory_url( $path_chunks );
}

/**
 * Output group type directory permalink.
 *
 * @since 2.7.0
 *
 * @param string $group_type Optional. Group type.
 */
function bp_group_type_directory_permalink( $group_type = '' ) {
	echo esc_url( bp_get_group_type_directory_permalink( $group_type ) );
}
	/**
	 * Return group type directory permalink.
	 *
	 * @since 2.7.0
	 *
	 * @param string $group_type Optional. Group type. Defaults to current group type.
	 * @return string Group type directory URL on success, an empty string on failure.
	 */
	function bp_get_group_type_directory_permalink( $group_type = '' ) {

		if ( $group_type ) {
			$_group_type = $group_type;
		} else {
			// Fall back on the current group type.
			$_group_type = bp_get_current_group_directory_type();
		}

		$type = bp_groups_get_group_type_object( $_group_type );

		// Bail when member type is not found or has no directory.
		if ( ! $type || ! $type->has_directory ) {
			return '';
		}

		$url = bp_get_groups_directory_url(
			array(
				'directory_type' => $type->directory_slug,
			)
		);

		/**
		 * Filters the group type directory permalink.
		 *
		 * @since 2.7.0
		 *
		 * @param string $url         Group type directory permalink.
		 * @param object $type        Group type object.
		 * @param string $member_type Group type name, as passed to the function.
		 */
		return apply_filters( 'bp_get_group_type_directory_permalink', $url, $type, $group_type );
	}

/**
 * Output group type directory link.
 *
 * @since 2.7.0
 *
 * @param string $group_type Unique group type identifier as used in bp_groups_register_group_type().
 */
function bp_group_type_directory_link( $group_type = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_type_directory_link( $group_type );
}
	/**
	 * Return group type directory link.
	 *
	 * @since 2.7.0
	 *
	 * @param string $group_type Unique group type identifier as used in bp_groups_register_group_type().
	 * @return string
	 */
	function bp_get_group_type_directory_link( $group_type = '' ) {
		if ( empty( $group_type ) ) {
			return '';
		}

		$group_type_object = bp_groups_get_group_type_object( $group_type );

		if ( ! isset( $group_type_object->labels['name'] ) ) {
			return '';
		}

		$group_type_text = $group_type_object->labels['name'];
		if ( isset( $group_type_object->labels['singular_name'] ) && $group_type_object->labels['singular_name'] ) {
			$group_type_text = $group_type_object->labels['singular_name'];
		}

		if ( empty( $group_type_object->has_directory ) ) {
			return esc_html( $group_type_text );
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( bp_get_group_type_directory_permalink( $group_type ) ),
			esc_html( $group_type_text )
		);
	}

/**
 * Output a comma-delimited list of group types.
 *
 * @since 2.7.0
 * @see   bp_get_group_type_list() for parameter documentation.
 *
 * @param integer $group_id The group ID.
 * @param array   $r        List parameters.
 */
function bp_group_type_list( $group_id = 0, $r = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_type_list( $group_id, $r );
}
	/**
	 * Return a comma-delimited list of group types.
	 *
	 * @since 2.7.0
	 * @since 7.0.0 The `$r['label']` argument now also accept an array containing the
	 *              plural & singular labels to use according to the Group's number of
	 *              group types it is assigned to.
	 *
	 * @param int $group_id Group ID. Defaults to current group ID if on a group page.
	 * @param array|string $r {
	 *     Array of parameters. All items are optional.
	 *     @type string       $parent_element Element to wrap around the list. Defaults to 'p'.
	 *     @type array        $parent_attr    Element attributes for parent element. Defaults to
	 *                                        array( 'class' => 'bp-group-type-list' ).
	 *     @type string|array $label          Plural and singular labels to add before the list. Defaults to
	 *                                        array( 'plural' => 'Group Types:', 'singular' => 'Group Type:' ).
	 *     @type string       $label_element  Element to wrap around the label. Defaults to 'strong'.
	 *     @type array        $label_attr     Element attributes for label element. Defaults to array().
	 *     @type bool         $show_all       Whether to show all registered group types. Defaults to 'false'. If
	 *                                       'false', only shows group types with the 'show_in_list' parameter set to
	 *                                        true. See bp_groups_register_group_type() for more info.
	 * }
	 * @return string
	 */
	function bp_get_group_type_list( $group_id = 0, $r = array() ) {
		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		$r = bp_parse_args(
			$r,
			array(
				'parent_element'    => 'p',
				'parent_attr'       => array(
					'class' => 'bp-group-type-list',
				),
				'label'             => array(),
				'label_element'     => 'strong',
				'label_attr'        => array(),
				'show_all'          => false,
				'list_element'      => '',
				'list_element_attr' => array(),
			),
			'group_type_list'
		);

		// Should the label be output?
		$has_label = ! empty( $r['label'] );

		// Ensure backward compatibility in case developers are still using a string.
		if ( ! is_array( $r['label'] ) ) {
			$r['label'] = array(
				'plural' => __( 'Group Types:', 'buddypress' ),
			);
		}

		$labels = bp_parse_args(
			$r['label'],
			array(
				'plural'   => __( 'Group Types:', 'buddypress' ),
				'singular' => __( 'Group Type:', 'buddypress' ),
			)
		);

		$retval = '';

		if ( $types = bp_groups_get_group_type( $group_id, false ) ) {
			// Make sure we can show the type in the list.
			if ( false === $r['show_all'] ) {
				$types = array_intersect( bp_groups_get_group_types( array( 'show_in_list' => true ) ), $types );
				if ( empty( $types ) ) {
					return $retval;
				}
			}

			$before = $after = $label = '';
			$count  = count( $types );

			if ( 1 === $count ) {
				$label_text = $labels['singular'];
			} else {
				$label_text = $labels['plural'];
			}

			// Render parent element.
			if ( ! empty( $r['parent_element'] ) ) {
				$parent_elem = new BP_Core_HTML_Element( array(
					'element' => $r['parent_element'],
					'attr'    => $r['parent_attr'],
				) );

				// Set before and after.
				$before = $parent_elem->get( 'open_tag' );
				$after  = $parent_elem->get( 'close_tag' );
			}

			// Render label element.
			if ( ! empty( $r['label_element'] ) ) {
				$label = new BP_Core_HTML_Element( array(
					'element'    => $r['label_element'],
					'attr'       => $r['label_attr'],
					'inner_html' => esc_html( $label_text ),
				) );
				$label = $label->contents() . ' ';

			// No element, just the label.
			} elseif ( $has_label ) {
				$label = esc_html( $label_text );
			}

			// The list of types.
			$list = implode( ', ', array_map( 'bp_get_group_type_directory_link', $types ) );

			// Render the list of types element.
			if ( ! empty( $r['list_element'] ) ) {
				$list_element = new BP_Core_HTML_Element( array(
					'element'    => $r['list_element'],
					'attr'       => $r['list_element_attr'],
					'inner_html' => $list,
				) );

				$list = $list_element->contents();
			}

			// Comma-delimit each type into the group type directory link.
			$label .= $list;

			// Retval time!
			$retval = $before . $label . $after;
		}

		return $retval;
	}

/**
 * Start the Groups Template Loop.
 *
 * @since 1.0.0
 * @since 2.6.0 Added `$group_type`, `$group_type__in`, and `$group_type__not_in` parameters.
 * @since 2.7.0 Added `$update_admin_cache` parameter.
 * @since 7.0.0 Added `$status` parameter.
 * @since 10.0.0 Added `$date_query` parameter.
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @param array|string $args {
 *     Array of parameters. All items are optional.
 *     @type string       $type               Shorthand for certain orderby/order combinations. 'newest', 'active',
 *                                            'popular', 'alphabetical', 'random'. When present, will override
 *                                            orderby and order params. Default: null.
 *     @type string       $order              Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
 *     @type string       $orderby            Property to sort by. 'date_created', 'last_activity',
 *                                            'total_member_count', 'name', 'random'. Default: 'last_activity'.
 *     @type int          $page               Page offset of results to return. Default: 1 (first page of results).
 *     @type int          $per_page           Number of items to return per page of results. Default: 20.
 *     @type int          $max                Does NOT affect query. May change the reported number of total groups
 *                                            found, but not the actual number of found groups. Default: false.
 *     @type bool         $show_hidden        Whether to include hidden groups in results. Default: false.
 *     @type string       $page_arg           Query argument used for pagination. Default: 'grpage'.
 *     @type int          $user_id            If provided, results will be limited to groups of which the specified
 *                                            user is a member. Default: value of bp_displayed_user_id().
 *     @type string       $slug               If provided, only the group with the matching slug will be returned.
 *                                            Default: false.
 *     @type string       $search_terms       If provided, only groups whose names or descriptions match the search
 *                                            terms will be returned. Default: value of `$_REQUEST['groups_search']` or
 *                                            `$_REQUEST['s']`, if present. Otherwise false.
 *     @type array|string $group_type         Array or comma-separated list of group types to limit results to.
 *     @type array|string $group_type__in     Array or comma-separated list of group types to limit results to.
 *     @type array|string $group_type__not_in Array or comma-separated list of group types that will be
 *                                            excluded from results.
 *     @type array|string $status             Array or comma-separated list of group statuses to limit results to.
 *     @type array        $meta_query         An array of meta_query conditions.
 *                                            See {@link WP_Meta_Query::queries} for description.
 *     @type array        $date_query         Filter results by group last activity date. See first parameter of
 *                                            {@link WP_Date_Query::__construct()} for syntax. Only applicable if
 *                                            $type is either 'newest' or 'active'.
 *     @type array|string $include            Array or comma-separated list of group IDs. Results will be limited
 *                                            to groups within the list. Default: false.
 *     @type array|string $exclude            Array or comma-separated list of group IDs. Results will exclude
 *                                            the listed groups. Default: false.
 *     @type array|string $parent_id          Array or comma-separated list of group IDs. Results will include only
 *                                            child groups of the listed groups. Default: null.
 *     @type bool         $update_meta_cache  Whether to fetch groupmeta for queried groups. Default: true.
 *     @type bool         $update_admin_cache Whether to pre-fetch group admins for queried groups.
 *                                            Defaults to true when on a group directory, where this
 *                                            information is needed in the loop. Otherwise false.
 * }
 * @return bool True if there are groups to display that match the params
 */
function bp_has_groups( $args = '' ) {
	global $groups_template;

	/*
	 * Defaults based on the current page & overridden by parsed $args
	 */
	$slug         = false;
	$type         = '';
	$search_terms = false;

	// When looking your own groups, check for two action variables.
	if ( bp_is_current_action( 'my-groups' ) ) {
		if ( bp_is_action_variable( 'most-popular', 0 ) ) {
			$type = 'popular';
		} elseif ( bp_is_action_variable( 'alphabetically', 0 ) ) {
			$type = 'alphabetical';
		}

	// When looking at invites, set type to invites.
	} elseif ( bp_is_current_action( 'invites' ) ) {
		$type = 'invites';

	// When looking at a single group, set the type and slug.
	} elseif ( bp_get_current_group_slug() ) {
		$type = 'single-group';
		$slug = bp_get_current_group_slug();
	}

	$group_type = bp_get_current_group_directory_type();
	if ( ! $group_type && ! empty( $_GET['group_type'] ) ) {
		if ( is_array( $_GET['group_type'] ) ) {
			$group_type = $_GET['group_type'];
		} else {
			// Can be a comma-separated list.
			$group_type = explode( ',', $_GET['group_type'] );
		}
	}

	$status = array();
	if ( ! empty( $_GET['status'] ) ) {
		if ( is_array( $_GET['status'] ) ) {
			$status = $_GET['status'];
		} else {
			// Can be a comma-separated list.
			$status = explode( ',', $_GET['status'] );
		}
	}

	// Default search string (too soon to escape here).
	$search_query_arg = bp_core_get_component_search_query_arg( 'groups' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms = stripslashes( $_REQUEST[ $search_query_arg ] );
	} elseif ( ! empty( $_REQUEST['group-filter-box'] ) ) {
		$search_terms = $_REQUEST['group-filter-box'];
	} elseif ( !empty( $_REQUEST['s'] ) ) {
		$search_terms = $_REQUEST['s'];
	}

	// Parse defaults and requested arguments.
	$r = bp_parse_args(
		$args,
		array(
			'type'               => $type,
			'order'              => 'DESC',
			'orderby'            => 'last_activity',
			'page'               => 1,
			'per_page'           => 20,
			'max'                => false,
			'show_hidden'        => false,
			'page_arg'           => 'grpage',
			'user_id'            => bp_displayed_user_id(),
			'slug'               => $slug,
			'search_terms'       => $search_terms,
			'group_type'         => $group_type,
			'group_type__in'     => '',
			'group_type__not_in' => '',
			'status'             => $status,
			'meta_query'         => false,
			'date_query'         => false,
			'include'            => false,
			'exclude'            => false,
			'parent_id'          => null,
			'update_meta_cache'  => true,
			'update_admin_cache' => bp_is_groups_directory() || bp_is_user_groups(),
		),
		'has_groups'
	);

	// Setup the Groups template global.
	$groups_template = new BP_Groups_Template( array(
		'type'               => $r['type'],
		'order'              => $r['order'],
		'orderby'            => $r['orderby'],
		'page'               => (int) $r['page'],
		'per_page'           => (int) $r['per_page'],
		'max'                => (int) $r['max'],
		'show_hidden'        => $r['show_hidden'],
		'page_arg'           => $r['page_arg'],
		'user_id'            => (int) $r['user_id'],
		'slug'               => $r['slug'],
		'search_terms'       => $r['search_terms'],
		'group_type'         => $r['group_type'],
		'group_type__in'     => $r['group_type__in'],
		'group_type__not_in' => $r['group_type__not_in'],
		'status'             => $r['status'],
		'meta_query'         => $r['meta_query'],
		'date_query'         => $r['date_query'],
		'include'            => $r['include'],
		'exclude'            => $r['exclude'],
		'parent_id'          => $r['parent_id'],
		'update_meta_cache'  => (bool) $r['update_meta_cache'],
		'update_admin_cache' => (bool) $r['update_admin_cache'],
	) );

	/**
	 * Filters whether or not there are groups to iterate over for the groups loop.
	 *
	 * @since 1.1.0
	 *
	 * @param bool               $value           Whether or not there are groups to iterate over.
	 * @param BP_Groups_Template $groups_template BP_Groups_Template object based on parsed arguments.
	 * @param array              $r               Array of parsed arguments for the query.
	 */
	return apply_filters( 'bp_has_groups', $groups_template->has_groups(), $groups_template, $r );
}

/**
 * Check whether there are more groups to iterate over.
 *
 * @since 1.0.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @return bool
 */
function bp_groups() {
	global $groups_template;
	return $groups_template->groups();
}

/**
 * Set up the current group inside the loop.
 *
 * @since 1.0.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @return BP_Groups_Group
 */
function bp_the_group() {
	global $groups_template;
	return $groups_template->the_group();
}

/**
 * Is the group accessible to a user?
 * Despite the name of the function, it has historically checked
 * whether a user has access to a group.
 * In BP 2.9, a property was added to the BP_Groups_Group class,
 * `is_visible`, that describes whether a user can know the group exists.
 * If you wish to check that property, use the check:
 * bp_current_user_can( 'groups_see_group' ).
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group` and added the `$user_id` parameter.
 *
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 * @param int                              $user_id ID of the User.
 *                                                  Default: current logged in user ID.
 * @return bool                                     True if the Group is accessible to the user. False otherwise.
 */
function bp_group_is_visible( $group = false, $user_id = 0 ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return (bool) ( bp_current_user_can( 'bp_moderate' ) || bp_user_can( $user_id, 'groups_access_group', array( 'group_id' => $group->id ) ) );
}

/**
 * Output the ID of the group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                 Default: false.
 */
function bp_group_id( $group = false ) {
	echo intval( bp_get_group_id( $group ) );
}
	/**
	 * Get the ID of the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                   Default: false.
	 * @return int
	 */
	function bp_get_group_id( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return 0;
		}

		/**
		 * Filters the ID of the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param int             $id    ID of the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_id', $group->id, $group );
	}

/**
 * Output the row class of the current group in the loop.
 *
 * @since 1.7.0
 *
 * @param array $classes Array of custom classes.
 */
function bp_group_class( $classes = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_class( $classes );
}
	/**
	 * Get the row class of the current group in the loop.
	 *
	 * @since 1.7.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param array $classes Array of custom classes.
	 * @return string Row class of the group.
	 */
	function bp_get_group_class( $classes = array() ) {
		global $groups_template;

		// Add even/odd classes, but only if there's more than 1 group.
		if ( $groups_template->group_count > 1 ) {
			$pos_in_loop = (int) $groups_template->current_group;
			$classes[]   = ( $pos_in_loop % 2 ) ? 'even' : 'odd';

		// If we've only one group in the loop, don't bother with odd and even.
		} else {
			$classes[] = 'bp-single-group';
		}

		// Group type - public, private, hidden.
		$classes[] = sanitize_key( $groups_template->group->status );

		// Add current group types.
		if ( $group_types = bp_groups_get_group_type( bp_get_group_id(), false ) ) {
			foreach ( $group_types as $group_type ) {
				$classes[] = sprintf( 'group-type-%s', esc_attr( $group_type ) );
			}
		}

		// User's group role.
		if ( bp_is_user_active() ) {

			// Admin.
			if ( bp_group_is_admin() ) {
				$classes[] = 'is-admin';
			}

			// Moderator.
			if ( bp_group_is_mod() ) {
				$classes[] = 'is-mod';
			}

			// Member.
			if ( bp_group_is_member() ) {
				$classes[] = 'is-member';
			}
		}

		// Whether a group avatar will appear.
		if ( bp_disable_group_avatar_uploads() || ! buddypress()->avatar->show_avatars ) {
			$classes[] = 'group-no-avatar';
		} else {
			$classes[] = 'group-has-avatar';
		}

		/**
		 * Filters classes that will be applied to row class of the current group in the loop.
		 *
		 * @since 1.7.0
		 *
		 * @param array $classes Array of determined classes for the row.
		 */
		$classes = array_map( 'sanitize_html_class', apply_filters( 'bp_get_group_class', $classes ) );
		$classes = array_merge( $classes, array() );
		$retval = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output the name of the group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_name( $group = false ) {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_name( $group );
}
	/**
	 * Get the name of the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: current group in loop.
	 * @return string
 	 */
	function bp_get_group_name( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the name of the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $name  Name of the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_name', $group->name, $group );
	}

/**
 * Output the type of the group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_type( $group = false ) {
	echo esc_html( bp_get_group_type( $group ) );
}
	/**
	 * Get the type of the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_type( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		if ( 'public' === $group->status ) {
			$type = __( "Public Group", 'buddypress' );
		} elseif ( 'hidden' === $group->status ) {
			$type = __( "Hidden Group", 'buddypress' );
		} elseif ( 'private' === $group->status ) {
			$type = __( "Private Group", 'buddypress' );
		} else {
			$type = ucwords( $group->status ) . ' ' . __( 'Group', 'buddypress' );
		}

		/**
		 * Filters the type for the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $type  Type for the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_type', $type, $group );
	}

/**
 * Output the status of the group.
 *
 * @since 1.1.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                 Default: false.
 */
function bp_group_status( $group = false ) {
	echo esc_html( bp_get_group_status( $group ) );
}
	/**
	 * Get the status of the group.
	 *
	 * @since 1.1.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_status( $group = false ) {
		$group  = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the status of the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $status Status of the group.
		 * @param BP_Groups_Group $group  The group object.
		 */
		return apply_filters( 'bp_get_group_status', $group->status, $group );
	}

/**
 * Output the group avatar.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` parameter.
 *
 * @param array|string $args {
 *      See {@link bp_get_group_avatar()} for description of arguments.
 * }
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_avatar( $args = '', $group = false ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_avatar( $args, $group );
}
	/**
	 * Get a group's avatar.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Added the `$group` parameter.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and return values.
	 *
	 * @param array|string                     $args {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see {@link bp_core_fetch_avatar()}.
	 *
	 *     @type string       $type    Default: 'full'.
	 *     @type int|bool     $width   Default: false.
	 *     @type int|bool     $height  Default: false.
	 *     @type string       $class   Default: 'avatar'.
	 *     @type bool         $no_grav Default: false.
	 *     @type bool         $html    Default: true.
	 *     @type string|bool  $id      Passed to `$css_id` parameter. Default: false.
	 *     @type string       $alt     Default: 'Group logo of [group name]'.
	 * }
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string|bool                            HTML output for the Group Avatar. or false if avatar uploads is disabled.
	 */
	function bp_get_group_avatar( $args = '', $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		// Bail if avatars are turned off.
		if ( bp_disable_group_avatar_uploads() || ! buddypress()->avatar->show_avatars ) {
			return false;
		}

		// Parse the arguments.
		$r = bp_parse_args(
			$args,
			array(
				'type'    => 'full',
				'width'   => false,
				'height'  => false,
				'class'   => 'avatar',
				'no_grav' => false,
				'html'    => true,
				'id'      => false,
				// translators: %1$s is the name of the group.
				'alt'     => sprintf( __( 'Group logo of %1$s', 'buddypress' ), $group->name ),
			),
			'get_group_avatar'
		);

		// Fetch the avatar from the folder.
		$avatar = bp_core_fetch_avatar(
			array(
				'item_id'    => $group->id,
				'avatar_dir' => 'group-avatars',
				'object'     => 'group',
				'type'       => $r['type'],
				'html'       => $r['html'],
				'alt'        => $r['alt'],
				'no_grav'    => $r['no_grav'],
				'css_id'     => $r['id'],
				'class'      => $r['class'],
				'width'      => $r['width'],
				'height'     => $r['height'],
			)
		);

		// If no avatar is found, provide some backwards compatibility.
		if ( empty( $avatar ) ) {
			$avatar = sprintf(
				'<img src"%1$s" class="avatar" alt="%2$s" />',
				esc_url( bp_get_group_avatar_thumb( $group ) ),
				esc_attr( $group->name )
			);
		}

		/**
		 * Filters the group avatar.
		 *
		 * @since 1.0.0
		 * @since 10.0.0 Added the `$group` paremeter.
		 *
		 * @param string          $avatar HTML image element holding the group avatar.
		 * @param array           $r      Array of parsed arguments for the group avatar.
		 * @param BP_Groups_Group $group  The group object.
		 */
		return apply_filters( 'bp_get_group_avatar', $avatar, $r, $group );
	}

/**
 * Output the group avatar thumbnail.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_avatar_thumb( $group = false ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_avatar_thumb( $group );
}
	/**
	 * Return the group avatar thumbnail.
	 *
	 * @since 1.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 * @return string                                 HTML output for the Group Avatar.
	 */
	function bp_get_group_avatar_thumb( $group = false ) {
		return bp_get_group_avatar(
			array(
				'type' => 'thumb',
				'id'   => ! empty( $group->id ) ? $group->id : false,
			),
			$group
		);
	}

/**
 * Output the miniature group avatar thumbnail.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_avatar_mini( $group = false ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_avatar_mini( $group );
}
	/**
	 * Return the miniature group avatar thumbnail.
	 *
	 * @since 1.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 * @return string                                 HTML output for the Group Avatar.
	 */
	function bp_get_group_avatar_mini( $group = false ) {
		return bp_get_group_avatar(
			array(
				'type'   => 'thumb',
				'width'  => 30,
				'height' => 30,
				'id'     => ! empty( $group->id ) ? $group->id : false,
			),
			$group
		);
	}

/**
 * Output the group avatar URL.
 *
 * @since 10.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param string                           $type  Optional. The type of the avatar ('full' or 'thumb').
 *                                                Default 'full'.
 */
function bp_group_avatar_url( $group = false, $type = 'full' ) {
	echo esc_url( bp_get_group_avatar_url( $group, $type ) );
}
	/**
	 * Returns the group avatar URL.
	 *
	 * @since 5.0.0
	 * @since 10.0.0 Updated to use `bp_get_group_avatar`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
	 *                                                Default: false.
	 * @param string                                  $type  Optional. The type of the avatar ('full' or 'thumb').
	 *                                                Default 'full'.
	 * @return string
	 */
	function bp_get_group_avatar_url( $group = false, $type = 'full' ) {
		return bp_get_group_avatar(
			array(
				'type' => $type,
				'html' => false,
			),
			$group
		);
	}

/** Group cover image *********************************************************/

/**
 * Check if the group's cover image header enabled/active.
 *
 * @since 2.4.0
 *
 * @return bool True if the cover image header is enabled, false otherwise.
 */
function bp_group_use_cover_image_header() {
	return (bool) bp_is_active( 'groups', 'cover_image' ) && ! bp_disable_group_cover_image_uploads();
}

/**
 * Returns the group cover image URL.
 *
 * @since 5.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return string                                 The cover image URL or empty string if not found.
 */
function bp_get_group_cover_url( $group = false ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return '';
	}

	$cover_url = bp_attachments_get_attachment(
		'url',
		array(
			'object_dir' => 'groups',
			'item_id'    => $group->id,
		)
	);

	if ( ! $cover_url ) {
		return '';
	}

	return $cover_url;
}

/**
 * Output the 'last active' string for the group.
 *
 * @since 1.0.0
 * @since 2.7.0 Added `$args` as a parameter.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param array|string                     $args  Optional. {@see bp_get_group_last_active()}.
 */
function bp_group_last_active( $group = false, $args = array() ) {
	echo esc_html( bp_get_group_last_active( $group, $args ) );
}
	/**
	 * Return the 'last active' string for the group.
	 *
	 * @since 1.0.0
	 * @since 2.7.0  Added `$args` as a parameter.
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @param array|string $args {
	 *     Array of optional parameters.
	 *
	 *     @type bool $relative Optional. If true, returns relative activity date. eg. active 5 months ago.
	 *                          If false, returns active date value from database. Default: true.
	 * }
	 * @return string
	 */
	function bp_get_group_last_active( $group = false, $args = array() ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		$r = bp_parse_args(
			$args,
			array(
				'relative' => true,
			),
			'group_last_active'
		);

		$last_active = $group->last_activity;
		if ( ! $last_active ) {
			$last_active = groups_get_groupmeta( $group->id, 'last_activity' );
		}

		// We do not want relative time, so return now.
		// @todo Should the 'bp_get_group_last_active' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $last_active );
		}

		if ( empty( $last_active ) ) {
			return __( 'not yet active', 'buddypress' );
		} else {

			/**
			 * Filters the 'last active' string for the current group in the loop.
			 *
			 * @since 1.0.0
			 * @since 2.5.0 Added the `$group` parameter.
			 *
			 * @param string          $value Determined last active value for the current group.
			 * @param BP_Groups_Group $group The group object.
			 */
			return apply_filters( 'bp_get_group_last_active', bp_core_time_since( $last_active ), $group );
		}
	}

/**
 * Output the URL for the group.
 *
 * @since 12.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param array                            $chunk (Optional) A list of slugs to append to the URL.
 */
function bp_group_url( $group = false, $chunks = array() ) {
	$path_chunks = array();
	$chunks      = (array) $chunks;

	if ( $chunks ) {
		$path_chunks = bp_groups_get_path_chunks( $chunks );
	}

	echo esc_url( bp_get_group_url( $group, $path_chunks ) );
}

/**
 * Returns the Groups single item's URL.
 *
 * @since 12.0.0
 *
 * @param integer|BP_Groups_Group $group The group ID or the Group object.
 * @param array                   $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type string $single_item_action           The slug of the action to perform.
 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
 * }
 * @return string The URL built for the BP Rewrites URL parser.
 */
function bp_get_group_url( $group = 0, $path_chunks = array() ) {
	$url  = '';
	$slug = groups_get_slug( $group );

	if ( $group instanceof BP_Groups_Group || ( is_object( $group ) && isset( $group->id, $group->name, $group->slug ) ) ) {
		$group_id = (int) $group->id;
	} else {
		$group_id = (int) $group;
	}

	if ( $slug ) {
		$supported_chunks = array_fill_keys( array( 'single_item_action', 'single_item_action_variables' ), true );
		$path_chunks      = bp_parse_args(
			array_intersect_key( $path_chunks, $supported_chunks ),
			array(
				'component_id' => 'groups',
				'single_item'  => $slug,
			)
		);

		$url = bp_rewrites_get_url( $path_chunks );
	}

	/**
	 * Filters the URL for the passed group.
	 *
	 * @since 12.0.0
	 *
	 * @param string  $url      The group url.
	 * @param integer $group_id The group ID.
	 * @param string  $slug     The group slug.
	 * @param array   $path_chunks {
	 *     An array of arguments. Optional.
	 *
	 *     @type string $single_item_component        The component slug the action is relative to.
	 *     @type string $single_item_action           The slug of the action to perform.
	 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
	 * }
	 */
	return apply_filters( 'bp_get_group_url', $url, $group_id, $slug, $path_chunks );
}

/**
 * Output an HTML-formatted link for the group.
 *
 * @since 2.9.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_link( $group = false ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_link( $group );
}
	/**
	 * Return an HTML-formatted link for the group.
	 *
	 * @since 2.9.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_link( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		$link = sprintf(
			'<a href="%s" class="bp-group-home-link %s-home-link">%s</a>',
			esc_url( bp_get_group_url( $group ) ),
			esc_attr( bp_get_group_slug( $group ) ),
			esc_html( bp_get_group_name( $group ) )
		);

		/**
		 * Filters the HTML-formatted link for the group.
		 *
		 * @since 2.9.0
		 *
		 * @param string          $link  HTML-formatted link for the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_link', $link, $group );
	}

/**
 * Outputs the requested group's manage URL.
 *
 * @since 12.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                 Default: false.
 * @param array                            $chunks (Optional) A list of slugs to append to the URL.
 */
function bp_group_manage_url( $group = false, $chunks = array() ) {
	$path_chunks = array();
	$chunks      = (array) $chunks;

	if ( $chunks ) {
		$path_chunks = bp_groups_get_path_chunks( $chunks, 'manage' );
	}

	echo esc_url( bp_get_group_manage_url( $group, $path_chunks ) );
}

/**
 * Gets the requested group's manage URL.
 *
 * @since 12.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                 Default: false.
 * @param array                            $path_chunks {
 *     An array of arguments. Optional.
 *
 *     @type array  $single_item_action_variables An array of additional informations about the action to perform.
 * }
 * @return string The requested group's manage URL.
 */
function bp_get_group_manage_url( $group = false, $path_chunks = array() ) {
	$group = bp_get_group( $group );
	$url   = '';

	if ( empty( $group->id ) ) {
		return $url;
	}

	$admin_chunks = array(
		'single_item_action' => bp_rewrites_get_slug( 'groups', 'bp_group_read_admin', 'admin' ),
	);

	if ( isset( $path_chunks['single_item_action_variables'] ) ) {
		$admin_chunks['single_item_action_variables'] = (array) $path_chunks['single_item_action_variables'];
	}

	$url = bp_get_group_url( $group, $admin_chunks );

	/**
	 * Filters the group's manage URL.
	 *
	 * @since 12.0.0
	 *
	 * @param string          $url         Permalink for the admin section of the group.
	 * @param BP_Groups_Group $group       The group object.
	 * @param array           $path_chunks BP Rewrites path chunks.
	 */
	return apply_filters( 'bp_get_group_manage_url', $url, $group, $path_chunks );
}

/**
 * Output the slug for the group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_slug( $group = false ) {
	echo esc_attr( bp_get_group_slug( $group ) );
}
	/**
	 * Return the slug for the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_slug( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the slug for the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $slug  Slug for the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_slug', $group->slug, $group );
	}

/**
 * Output the description for the group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_description( $group = false ) {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_description( $group );
}
	/**
	 * Return the description for the group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_description( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the description for the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $description Description for the group.
		 * @param BP_Groups_Group $group       The group object.
		 */
		return apply_filters( 'bp_get_group_description', stripslashes( $group->description ), $group );
	}

/**
 * Output the description for the group, for use in a textarea.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_description_editable( $group = false ) {
	echo esc_textarea( bp_get_group_description_editable( $group ) );
}
	/**
	 * Return the permalink for the group, for use in a textarea.
	 *
	 * 'bp_get_group_description_editable' does not have the formatting
	 * filters that 'bp_get_group_description' has, which makes it
	 * appropriate for "raw" editing.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_description_editable( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the permalink for the group, for use in a textarea.
		 *
		 * 'bp_get_group_description_editable' does not have the formatting filters that
		 * 'bp_get_group_description' has, which makes it appropriate for "raw" editing.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $description Description for the group.
		 * @param BP_Groups_Group $group       The group object.
		 */
		return apply_filters( 'bp_get_group_description_editable', $group->description, $group );
	}

/**
 * Output an excerpt of the group description.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group  (Optional) The Group ID, the Group Slug or the Group object.
 *                                                 Default:false.
 * @param int                              $length (Optional) Length of returned string, including ellipsis.
 *                                                 Default: 225.
 */
function bp_group_description_excerpt( $group = false, $length = 225 ) {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_description_excerpt( $group, $length );
}
	/**
	 * Get an excerpt of a group description.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group  (Optional) The Group ID, the Group Slug or the Group object.
     *                                                 Default: false.
	 * @param int                              $length (Optional) Length of returned string, including ellipsis.
	 *                                                 Default: 225.
	 * @return string
	 */
	function bp_get_group_description_excerpt( $group = false, $length = 225 ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the excerpt of a group description.
		 *
		 * @since 1.0.0
		 *
		 * @param string          $description Excerpt of a group description.
		 * @param BP_Groups_Group $group       The group object.
		 */
		return apply_filters( 'bp_get_group_description_excerpt', bp_create_excerpt( $group->description, $length ), $group );
	}

/**
 * Output the created date of the group.
 *
 * @since 1.0.0
 * @since 2.7.0 Added `$args` as a parameter.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param array|string                     $args  {@see bp_get_group_date_created()}.
 */
function bp_group_date_created( $group = false, $args = array() ) {
	echo esc_html( bp_get_group_date_created( $group, $args ) );
}
	/**
	 * Return the created date of the group.
	 *
	 * @since 1.0.0
	 * @since 2.7.0  Added `$args` as a parameter.
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @param array|string                     $args {
	 *     Array of optional parameters.
	 *
	 *     @type bool $relative Optional. If true, returns relative created date. eg. active 5 months ago.
	 *                          If false, returns created date value from database. Default: true.
	 * }
	 * @return string
	 */
	function bp_get_group_date_created( $group = false, $args = array() ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		$r = bp_parse_args(
			$args,
			array( 'relative' => true ),
			'group_date_created'
		);

		// We do not want relative time, so return now.
		// @todo Should the 'bp_get_group_date_created' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $group->date_created );
		}

		/**
		 * Filters the created date of the group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $date  Created date for the group.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_date_created', bp_core_time_since( $group->date_created ), $group );
	}

/**
 * Output the username of the creator of the group.
 *
 * @since 1.7.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_creator_username( $group = false ) {
	// Escaping is made in `bp-members/bp-members-functions.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_creator_username( $group );
}
	/**
	 * Return the username of the creator of the group.
	 *
	 * @since 1.7.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_creator_username( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the username of the creator of the group.
		 *
		 * @since 1.7.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $creator_id Username of the group creator.
		 * @param BP_Groups_Group $group      The group object.
		 */
		return apply_filters( 'bp_get_group_creator_username', bp_core_get_user_displayname( $group->creator_id ), $group );
	}

/**
 * Output the user ID of the creator of the group.
 *
 * @since 1.7.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_creator_id( $group = false ) {
	echo intval( bp_get_group_creator_id( $group ) );
}
	/**
	 * Return the user ID of the creator of the group.
	 *
	 * @since 1.7.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return int
	 */
	function bp_get_group_creator_id( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return 0;
		}

		/**
		 * Filters the user ID of the creator of the group.
		 *
		 * @since 1.7.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param int             $creator_id User ID of the group creator.
		 * @param BP_Groups_Group $group      The group object.
		 */
		return apply_filters( 'bp_get_group_creator_id', $group->creator_id, $group );
	}

/**
 * Output the permalink of the creator of the group.
 *
 * @since 1.7.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_creator_permalink( $group = false ) {
	echo esc_url( bp_get_group_creator_permalink( $group ) );
}
	/**
	 * Return the permalink of the creator of the group.
	 *
	 * @since 1.7.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_creator_permalink( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		/**
		 * Filters the permalink of the creator of the group.
		 *
		 * @since 1.7.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $permalink Permalink of the group creator.
		 * @param BP_Groups_Group $group     The group object.
		 */
		return apply_filters( 'bp_get_group_creator_permalink', bp_members_get_user_url( $group->creator_id ), $group );
	}

/**
 * Determine whether a user is the creator of the group.
 *
 * @since 1.7.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 * @param int                              $user_id ID of the user.
 *                                                  Default: current logged in user.
 * @return bool
 */
function bp_is_group_creator( $group = false, $user_id = 0 ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return (bool) ( $group->creator_id === $user_id );
}

/**
 * Output the avatar of the creator of the group.
 *
 * @since 1.7.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param array                            $args  {
 *     Array of optional arguments. See {@link bp_get_group_creator_avatar()}
 *     for description.
 * }
 */
function bp_group_creator_avatar( $group = false, $args = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_creator_avatar( $group, $args );
}
	/**
	 * Return the avatar of the creator of the group.
	 *
	 * @since 1.7.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @param array                            $args  {
	 *     Array of optional arguments. See {@link bp_core_fetch_avatar()}
	 *     for detailed description of arguments.
	 *     @type string $type   Default: 'full'.
	 *     @type int    $width  Default: false.
	 *     @type int    $height Default: false.
	 *     @type int    $class  Default: 'avatar'.
	 *     @type string $id     Passed to 'css_id'. Default: false.
	 *     @type string $alt    Alt text. Default: 'Group creator profile
	 *                          photo of [user display name]'.
	 * }
	 * @return string
	 */
	function bp_get_group_creator_avatar( $group = false, $args = array() ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		$r = bp_parse_args(
			$args,
			array(
				'type'   => 'full',
				'width'  => false,
				'height' => false,
				'class'  => 'avatar',
				'id'     => false,
				'alt'    => sprintf(
					/* translators: %s: group creator name */
					__( 'Group creator profile photo of %s', 'buddypress' ),
					bp_core_get_user_displayname( $group->creator_id )
				),
			),
			'group_creator_avatar'
		);

		$avatar = bp_core_fetch_avatar(
			array(
				'item_id' => $group->creator_id,
				'type'    => $r['type'],
				'css_id'  => $r['id'],
				'class'   => $r['class'],
				'width'   => $r['width'],
				'height'  => $r['height'],
				'alt'     => $r['alt'],
			)
		);

		/**
		 * Filters the avatar of the creator of the group.
		 *
		 * @since 1.7.0
		 * @since 2.5.0  Added the `$group` parameter.
		 * @since 10.0.0 Added the `$r` parameter.
		 *
		 * @param string          $avatar Avatar of the group creator.
		 * @param BP_Groups_Group $group  The group object.
		 * @param array           $r      Array of parsed arguments for the group creator avatar.
		 */
		return apply_filters( 'bp_get_group_creator_avatar', $avatar, $group, $r );
	}

/**
 * Determine whether the current user is the admin of the current group.
 *
 * Alias of {@link bp_is_item_admin()}.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function bp_group_is_admin() {
	return bp_is_item_admin();
}

/**
 * Determine whether the current user is a mod of the current group.
 *
 * Alias of {@link bp_is_item_mod()}.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function bp_group_is_mod() {
	return bp_is_item_mod();
}

/**
 * Output markup listing group admins.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_list_admins( $group = false ) {
	$group = bp_get_group( $group );

	// phpcs:disable WordPress.Security.EscapeOutput
	if ( ! empty( $group->admins ) ) { ?>
		<ul id="group-admins">
			<?php foreach ( (array) $group->admins as $admin ) { ?>
				<li>
					<a
						href="<?php echo esc_url( bp_members_get_user_url( $admin->user_id ) ); ?>"
						class="bp-tooltip"
						data-bp-tooltip="<?php printf( ( '%s' ), bp_core_get_user_displayname( $admin->user_id ) ); ?>"
					>
						<?php
						echo bp_core_fetch_avatar(
							array(
								'item_id' => $admin->user_id,
								'email'   => $admin->user_email,
								'alt'     => sprintf(
									/* translators: %s: member name */
									__( 'Profile picture of %s', 'buddypress' ),
									bp_core_get_user_displayname( $admin->user_id )
								),
							)
						);
						?>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<span class="activity">
			<?php esc_html_e( 'No Admins', 'buddypress' ); ?>
		</span>
	<?php } ?>
	<?php
	// phpcs:enable
}

/**
 * Output markup listing group mod.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_list_mods( $group = false ) {
	$group = bp_get_group( $group );

	// phpcs:disable WordPress.Security.EscapeOutput
	if ( ! empty( $group->mods ) ) :
		?>
		<ul id="group-mods">
			<?php foreach ( (array) $group->mods as $mod ) { ?>
				<li>
					<a
						href="<?php echo esc_url( bp_members_get_user_url( $mod->user_id ) ); ?>"
						class="bp-tooltip"
						data-bp-tooltip="<?php printf( ( '%s' ), bp_core_get_user_displayname( $mod->user_id ) ); ?>">
						<?php
						echo bp_core_fetch_avatar(
							array(
								'item_id' => $mod->user_id,
								'email'   => $mod->user_email,
								'alt'     => sprintf(
									/* translators: %s: member name */
									__( 'Profile picture of %s', 'buddypress' ),
									bp_core_get_user_displayname( $mod->user_id )
								),
							)
						);
						?>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php else : ?>
		<span class="activity">
			<?php esc_html_e( 'No Mods', 'buddypress' ); ?>
		</span>
		<?php
	endif;
	// phpcs:enable
}

/**
 * Return a list of user IDs for a group's admins.
 *
 * @since 1.5.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param string                           $format (Optional) 'string' to get a comma-separated string,
 *                                                 'array' to get an array.
 * @return string|array|false A string or an array of user IDs, false if group does not exist.
 */
function bp_group_admin_ids( $group = false, $format = 'string' ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return false;
	}

	$admin_ids = array();

	if ( ! empty( $group->admins ) ) {
		foreach ( $group->admins as $admin ) {
			$admin_ids[] = $admin->user_id;
		}
	}

	if ( 'string' == $format && ! empty( $admin_ids ) ) {
		$admin_ids = implode( ',', $admin_ids );
	}

	/**
	 * Filters a list of user IDs for a group's admins.
	 *
	 * This filter may return either an array or a comma separated string.
	 *
	 * @since 1.5.0
	 * @since 2.5.0  Added the `$group` parameter.
	 * @since 10.0.0 Added the `$format` parameter.
	 *
	 * @param array|string     $admin_ids List of user IDs for a group's admins.
	 * @param BP_Groups_Group  $group     The group object.
	 * @param string           $format    The filter used to format the results.
	 */
	return apply_filters( 'bp_group_admin_ids', $admin_ids, $group, $format );
}

/**
 * Return a list of user IDs for a group's moderators.
 *
 * @since 1.5.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @param string                           $format (Optional) 'string' to get a comma-separated string,
 *                                                 'array' to get an array.
 * @return string|array|false A string or an array of user IDs, false if group does not exist.
 */
function bp_group_mod_ids( $group = false, $format = 'string' ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return false;
	}

	$mod_ids = array();

	if ( ! empty( $group->mods ) ) {
		foreach ( $group->mods as $mod ) {
			$mod_ids[] = $mod->user_id;
		}
	}

	if ( 'string' == $format && ! empty( $mod_ids ) ) {
		$mod_ids = implode( ',', $mod_ids );
	}

	/**
	 * Filters a list of user IDs for a group's moderators.
	 *
	 * This filter may return either an array or a comma separated string.
	 *
	 * @since 1.5.0
	 * @since 2.5.0  Added the `$group` parameter.
	 * @since 10.0.0 Added the `$format` parameter.
	 *
	 * @param array|string     $mod_ids List of user IDs for a group's moderators.
	 * @param BP_Groups_Group  $group   The group object.
	 * @param string           $format  The filter used to format the results.
	 */
	return apply_filters( 'bp_group_mod_ids', $mod_ids, $group, $format );
}

/**
 * Output the pagination HTML for a group loop.
 *
 * @since 1.2.0
 */
function bp_groups_pagination_links() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_groups_pagination_links();
}
	/**
	 * Get the pagination HTML for a group loop.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @return string
	 */
	function bp_get_groups_pagination_links() {
		global $groups_template;

		/**
		 * Filters the pagination HTML for a group loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $pag_links HTML markup for the pagination links.
		 */
		return apply_filters( 'bp_get_groups_pagination_links', $groups_template->pag_links );
	}

/**
 * Output the "Viewing x-y of z groups" pagination message.
 *
 * @since 1.2.0
 */
function bp_groups_pagination_count() {
	echo esc_html( bp_get_groups_pagination_count() );
}
	/**
	 * Generate the "Viewing x-y of z groups" pagination message.
	 *
	 * @since 1.5.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @return string
	 */
	function bp_get_groups_pagination_count() {
		global $groups_template;

		$start_num = intval( ( $groups_template->pag_page - 1 ) * $groups_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $groups_template->pag_num - 1 ) > $groups_template->total_group_count ) ? $groups_template->total_group_count : $start_num + ( $groups_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $groups_template->total_group_count );

		if ( 1 == $groups_template->total_group_count ) {
			$message = __( 'Viewing 1 group', 'buddypress' );
		} else {
			/* translators: 1: group from number. 2: group to number. 3: total groups. */
			$message = sprintf( _n( 'Viewing %1$s - %2$s of %3$s group', 'Viewing %1$s - %2$s of %3$s groups', $groups_template->total_group_count, 'buddypress' ), $from_num, $to_num, $total );
		}

		/**
		 * Filters the "Viewing x-y of z groups" pagination message.
		 *
		 * @since 1.5.0
		 *
		 * @param string $message  "Viewing x-y of z groups" text.
		 * @param string $from_num Total amount for the low value in the range.
		 * @param string $to_num   Total amount for the high value in the range.
		 * @param string $total    Total amount of groups found.
		 */
		return apply_filters( 'bp_get_groups_pagination_count', $message, $from_num, $to_num, $total );
	}

/**
 * Determine whether groups auto-join is enabled.
 *
 * "Auto-join" is the toggle that determines whether users are joined to a
 * public group automatically when creating content in that group.
 *
 * @since 1.2.6
 *
 * @return bool
 */
function bp_groups_auto_join() {

	/**
	 * Filters whether groups auto-join is enabled.
	 *
	 * @since 1.2.6
	 *
	 * @param bool $value Enabled status.
	 */
	return apply_filters( 'bp_groups_auto_join', (bool) buddypress()->groups->auto_join );
}

/**
 * Output the total member count for a group.
 *
 * @since 1.0.0
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_total_members( $group = false ) {
	echo intval( bp_get_group_total_members( $group ) );
}
	/**
	 * Get the total member count for a group.
	 *
	 * @since 1.0.0
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return int
	 */
	function bp_get_group_total_members( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return 0;
		}

		/**
		 * Filters the total member count for a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param int             $total_member_count Total member count for a group.
		 * @param BP_Groups_Group $group              The group object.
		 */
		return apply_filters( 'bp_get_group_total_members', (int) $group->total_member_count, $group );
	}

/**
 * Output the "x members" count string for a group.
 *
 * @since 1.2.0
 * @since 7.0.0 Adds the `$group` optional parameter.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_member_count( $group = false ) {
	echo esc_html( bp_get_group_member_count( $group ) );
}
	/**
	 * Generate the "x members" count string for a group.
	 *
	 * @since 1.2.0
	 * @since 7.0.0  Adds the `$group` optional parameter.
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_member_count( $group = false ) {
		$group = bp_get_group( $group );

		if ( empty( $group->id ) ) {
			return '';
		}

		$count        = (int) $group->total_member_count;
		$count_string = sprintf(
			/* translators: %s is the number of Group members */
			_n( '%s member', '%s members', $count, 'buddypress' ),
			bp_core_number_format( $count )
		);

		/**
		 * Filters the "x members" count string for a group.
		 *
		 * @since 1.2.0
		 * @since 10.0.0 Added the `$group` paremeter.
		 *
		 * @param string          $count_string The "x members" count string for a group.
		 * @param BP_Groups_Group $group        The group object.
		 */
		return apply_filters( 'bp_get_group_member_count', $count_string, $group );
	}

/**
 * Output the URL of the Forum page of a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Adds the `$group` optional parameter.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_forum_permalink( $group = false ) {
	echo esc_url( bp_get_group_forum_permalink( $group ) );
}
	/**
	 * Generate the URL of the Forum page of a group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_forum_permalink( $group = false ) {
		$path_chunks = bp_groups_get_path_chunks( array( 'forum' ) );
		$url         = bp_get_group_url( $group, $path_chunks );

		/**
		 * Filters the URL of the Forum page of a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $value URL permalink for the Forum Page.
		 * @param BP_Groups_Group $group The group object.
		 */
		return apply_filters( 'bp_get_group_forum_permalink', $url, $group );
	}

/**
 * Determine whether forums are enabled for a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return bool
 */
function bp_group_is_forum_enabled( $group = false ) {
	$group = bp_get_group( $group );

	return ! empty( $group->enable_forum );
}

/**
 * Output the 'checked' attribute for the group forums settings UI.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_group_is_forum_enabled`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_show_forum_setting( $group = false ) {
	if ( bp_group_is_forum_enabled( $group ) ) {
		echo ' checked="checked"';
	}
}

/**
 * Output the 'checked' attribute for a given status in the settings UI.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param string                           $setting Group status: 'public', 'private', 'hidden'.
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_show_status_setting( $setting, $group = false ) {
	$group = bp_get_group( $group );

	if ( ! empty( $group->status ) && $setting === $group->status ) {
		echo ' checked="checked"';
	}
}

/**
 * Output the 'checked' value, if needed, for a given invite_status on the group create/admin screens
 *
 * @since 1.5.0
 *
 * @param string                           $setting The setting you want to check against ('members', 'mods', or 'admins').
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                   Default: false.
 */
function bp_group_show_invite_status_setting( $setting, $group = false ) {
	$invite_status = bp_group_get_invite_status( $group );

	if ( ! empty( $invite_status ) && $setting === $invite_status ) {
		echo ' checked="checked"';
	}
}

/**
 * Get the invite status of a group.
 *
 * 'invite_status' became part of BuddyPress in BP 1.5. In order to provide
 * backward compatibility with earlier installations, groups without a status
 * set will default to 'members', ie all members in a group can send
 * invitations. Filter 'bp_group_invite_status_fallback' to change this
 * fallback behavior.
 *
 * This function can be used either in or out of the loop.
 *
 * @since 1.5.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return bool|string Returns false when no group can be found. Otherwise
 *                     returns the group invite status, from among 'members',
 *                     'mods', and 'admins'.
 */
function bp_group_get_invite_status( $group = false ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		$bp = buddypress();

		// Default to the current group first.
		if ( ! empty( $bp->groups->current_group->id ) ) {
			$group = $bp->groups->current_group;
		} else {
			return false;
		}
	}

	$invite_status = groups_get_groupmeta( $group->id, 'invite_status' );

	// Backward compatibility. When 'invite_status' is not set, fall back to a default value.
	if ( ! $invite_status ) {
		$invite_status = apply_filters( 'bp_group_invite_status_fallback', 'members' );
	}

	/**
	 * Filters the invite status of a group.
	 *
	 * Invite status in this case means who from the group can send invites.
	 *
	 * @since 1.5.0
	 * @since 10.0.0 Added the `$group` paremeter.
	 *
	 * @param string          $invite_status Membership level needed to send an invite.
	 * @param int             $group_id      ID of the group whose status is being checked.
	 * @param BP_Groups_Group $group         The group object.
	 */
	return apply_filters( 'bp_group_get_invite_status', $invite_status, $group->id, $group );
}

/**
 * Can a user send invitations in the specified group?
 *
 * @since 1.5.0
 * @since 2.2.0 Added the $user_id parameter.
 *
 * @param int $group_id The group ID to check.
 * @param int $user_id  The user ID to check.
 * @return bool
 */
function bp_groups_user_can_send_invites( $group_id = 0, $user_id = 0 ) {
	$can_send_invites = false;
	$invite_status    = false;

	// If $user_id isn't specified, we check against the logged-in user.
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	// If $group_id isn't specified, use existing one if available.
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	if ( $user_id ) {
		$can_send_invites = bp_user_can( $user_id, 'groups_send_invitation', array( 'group_id' => $group_id ) );
	}

	/**
	 * Filters whether a user can send invites in a group.
	 *
	 * @since 1.5.0
	 * @since 2.2.0 Added the $user_id parameter.
	 *
	 * @param bool $can_send_invites Whether the user can send invites
	 * @param int  $group_id         The group ID being checked
	 * @param bool $invite_status    The group's current invite status
	 * @param int  $user_id          The user ID being checked
	 */
	return apply_filters( 'bp_groups_user_can_send_invites', $can_send_invites, $group_id, $invite_status, $user_id );
}

/**
 * Determine whether a group has moderators.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return array Info about group admins (user_id + date_modified).
 */
function bp_group_has_moderators( $group = false ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return array();
	}

	/**
	 * Filters whether a group has moderators.
	 *
	 * @since 1.0.0
	 * @since 2.5.0 Added the `$group` parameter.
	 *
	 * @param array           $value Array of user IDs who are a moderator of the provided group.
	 * @param BP_Groups_Group $group The group object.
	 */
	return apply_filters( 'bp_group_has_moderators', groups_get_group_mods( $group->id ), $group );
}

/**
 * Output a URL for promoting a user to moderator.
 *
 * @since 1.1.0
 *
 * @param array|string $args See {@link bp_get_group_member_promote_mod_link()}.
 */
function bp_group_member_promote_mod_link( $args = '' ) {
	echo esc_url( bp_get_group_member_promote_mod_link( $args ) );
}
	/**
	 * Generate a URL for promoting a user to moderator.
	 *
	 * @since 1.1.0
	 *
	 * @global BP_Groups_Template       $groups_template  The Groups template loop class.
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param array|string $args {
	 *     @type int    $user_id ID of the member to promote. Default:
	 *                           current member in a group member loop.
	 *     @type object $group   Group object. Default: current group.
	 * }
	 * @return string
	 */
	function bp_get_group_member_promote_mod_link( $args = '' ) {
		global $members_template, $groups_template;

		$r = bp_parse_args(
			$args,
			array(
				'user_id' => $members_template->member->user_id,
				'group'   => &$groups_template->group,
			),
			'group_member_promote_mod_link'
		);

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$r['group'],
				bp_groups_get_path_chunks( array( 'manage-members', 'promote', 'mod', $r['user_id'] ), 'manage' )
			),
			'groups_promote_member'
		);

		/**
		 * Filters a URL for promoting a user to moderator.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url URL to use for promoting a user to moderator.
		 */
		return apply_filters( 'bp_get_group_member_promote_mod_link', $url );
	}

/**
 * Output a URL for promoting a user to admin.
 *
 * @since 1.1.0
 *
 * @param array|string $args See {@link bp_get_group_member_promote_admin_link()}.
 */
function bp_group_member_promote_admin_link( $args = '' ) {
	echo esc_url( bp_get_group_member_promote_admin_link( $args ) );
}
	/**
	 * Generate a URL for promoting a user to admin.
	 *
	 * @since 1.1.0
	 *
	 * @global BP_Groups_Template       $groups_template  The Groups template loop class.
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param array|string $args {
	 *     @type int    $user_id ID of the member to promote. Default:
	 *                           current member in a group member loop.
	 *     @type object $group   Group object. Default: current group.
	 * }
	 * @return string
	 */
	function bp_get_group_member_promote_admin_link( $args = '' ) {
		global $members_template, $groups_template;

		$r = bp_parse_args(
			$args,
			array(
				'user_id' => ! empty( $members_template->member->user_id ) ? $members_template->member->user_id : false,
				'group'   => &$groups_template->group,
			),
			'group_member_promote_admin_link'
		);

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$r['group'],
				bp_groups_get_path_chunks( array( 'manage-members', 'promote', 'admin', $r['user_id'] ), 'manage' )
			),
			'groups_promote_member'
		);

		/**
		 * Filters a URL for promoting a user to admin.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url URL to use for promoting a user to admin.
		 */
		return apply_filters( 'bp_get_group_member_promote_admin_link', $url );
	}

/**
 * Output a URL for demoting a user to member.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param int                              $user_id ID of the member to demote. Default: 0.
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 */
function bp_group_member_demote_link( $user_id = 0, $group = false ) {
	echo esc_url( bp_get_group_member_demote_link( $user_id, $group ) );
}
	/**
	 * Generate a URL for demoting a user to member.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param int                              $user_id ID of the member to demote. Default: 0.
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_member_demote_link( $user_id = 0, $group = false ) {
		global $members_template;

		if ( ! $user_id ) {
			$user_id = $members_template->member->user_id;
		}

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( 'manage-members', 'demote', $user_id ), 'manage' )
			),
			'groups_demote_member'
		);

		/**
		 * Filters a URL for demoting a user to member.
		 *
		 * @since 1.0.0
		 * @since 2.5.0  Added the `$group` parameter.
		 * @since 10.0.0 Added the `$user_id` parameter.
		 *
		 * @param string          $url     URL to use for demoting a user to member.
		 * @param BP_Groups_Group $group   The group object.
		 * @param int             $user_id The user ID.
		 */
		return apply_filters( 'bp_get_group_member_demote_link', $url, $group, $user_id );
	}

/**
 * Output a URL for banning a member from a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param int                              $user_id ID of the member. Default: 0.
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 */
function bp_group_member_ban_link( $user_id = 0, $group = false ) {
	echo esc_url( bp_get_group_member_ban_link( $user_id, $group ) );
}
	/**
	 * Generate a URL for banning a member from a group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param int                              $user_id ID of the member to ban. Default: 0.
	 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
     *                                                  Default: false.
	 * @return string
	 */
	function bp_get_group_member_ban_link( $user_id = 0, $group = false ) {
		global $members_template;

		if ( ! $user_id ) {
			$user_id = $members_template->member->user_id;
		}

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( 'manage-members', 'ban', $user_id ), 'manage' )
			),
			'groups_ban_member'
		);

		/**
		 * Filters a URL for banning a member from a group.
		 *
		 * @since 1.0.0
		 * @since 10.0.0 Added the `$group`and `$user_id` parameter.
		 *
		 * @param string          $value   URL to use for banning a member.
		 * @param BP_Groups_Group $group   The group object.
		 * @param int             $user_id The user ID.
		 */
		return apply_filters( 'bp_get_group_member_ban_link', $url, $group, $user_id );
	}

/**
 * Output a URL for unbanning a member from a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param int                              $user_id ID of the member to unban. Default: 0.
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 */
function bp_group_member_unban_link( $user_id = 0, $group = false ) {
	echo esc_url( bp_get_group_member_unban_link( $user_id, $group ) );
}
	/**
	 * Generate a URL for unbanning a member from a group.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param int                              $user_id ID of the member to unban. Default: 0.
	 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
     *                                                  Default: false.
	 * @return string
	 */
	function bp_get_group_member_unban_link( $user_id = 0, $group = false ) {
		global $members_template;

		if ( ! $user_id ) {
			$user_id = $members_template->member->user_id;
		}

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( 'manage-members', 'unban', $user_id ), 'manage' )
			),
			'groups_unban_member'
		);

		/**
		 * Filters a URL for unbanning a member from a group.
		 *
		 * @since 1.0.0
		 * @since 10.0.0 Added the `$group`and `$user_id` parameter.
		 *
		 * @param string          $value   URL to use for unbanning a member.
		 * @param BP_Groups_Group $group   The group object.
		 * @param int             $user_id The user ID.
		 */
		return apply_filters( 'bp_get_group_member_unban_link', $url, $group, $user_id );
	}

/**
 * Output a URL for removing a member from a group.
 *
 * @since 1.2.6
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param int                              $user_id ID of the member to remove. Default: 0.
 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
 *                                                  Default: false.
 */
function bp_group_member_remove_link( $user_id = 0, $group = false ) {
	echo esc_url( bp_get_group_member_remove_link( $user_id, $group ) );
}
	/**
	 * Generate a URL for removing a member from a group.
	 *
	 * @since 1.2.6
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param int                              $user_id ID of the member to remove. Default: 0.
	 * @param false|int|string|BP_Groups_Group $group   (Optional) The Group ID, the Group Slug or the Group object.
     *                                                  Default: false.
	 * @return string
	 */
	function bp_get_group_member_remove_link( $user_id = 0, $group = false ) {
		global $members_template;

		if ( ! $user_id ) {
			$user_id = $members_template->member->user_id;
		}

		$url = wp_nonce_url(
			bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( 'manage-members', 'remove', $user_id ), 'manage' )
			),
			'groups_remove_member'
		);

		/**
		 * Filters a URL for removing a member from a group.
		 *
		 * @since 1.2.6
		 * @since 2.5.0  Added the `$group` parameter.
		 * @since 10.0.0 Added the `$user_id` parameter.
		 *
		 * @param string          $url     URL to use for removing a member.
		 * @param BP_Groups_Group $group   The group object.
		 * @param int             $user_id The user ID.
		 */
		return apply_filters( 'bp_get_group_member_remove_link', $url, $group, $user_id );
	}

/**
 * HTML admin subnav items for group pages.
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in the loop.
 */
function bp_group_admin_tabs( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group = ( $groups_template->group ) ? $groups_template->group : groups_get_current_group();
	}

	$css_id = 'manage-members';

	if ( 'private' == $group->status ) {
		$css_id = 'membership-requests';
	}

	add_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10, 3 );

	bp_get_options_nav( $group->slug . '_manage' );

	remove_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10 );
}

/**
 * BackCompat for plugins/themes directly hooking groups_admin_tabs
 * without using the Groups Extension API.
 *
 * @since 2.2.0
 *
 * @param  string $subnav_output Subnav item output.
 * @param  string $subnav_item   subnav item params.
 * @param  string $selected_item Surrent selected tab.
 * @return string HTML output
 */
function bp_group_admin_tabs_backcompat( $subnav_output = '', $subnav_item = '', $selected_item = '' ) {
	if ( ! has_action( 'groups_admin_tabs' ) ) {
		return $subnav_output;
	}

	$group = groups_get_current_group();

	ob_start();

	do_action( 'groups_admin_tabs', $selected_item, $group->slug );

	$admin_tabs_backcompat = trim( ob_get_contents() );
	ob_end_clean();

	if ( ! empty( $admin_tabs_backcompat ) ) {
		_doing_it_wrong( "do_action( 'groups_admin_tabs' )", esc_html__( 'This action should not be used directly. Please use the BuddyPress Group Extension API to generate Manage tabs.', 'buddypress' ), '2.2.0' );
		$subnav_output .= $admin_tabs_backcompat;
	}

	return $subnav_output;
}

/**
 * Output the group count for the displayed user.
 *
 * @since 1.1.0
 */
function bp_group_total_for_member() {
	echo intval( bp_get_group_total_for_member() );
}
	/**
	 * Get the group count for the displayed user.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_group_total_for_member() {

		/**
		 * FIlters the group count for a displayed user.
		 *
		 * @since 1.1.0
		 *
		 * @param int $value Total group count for a displayed user.
		 */
		return apply_filters( 'bp_get_group_total_for_member', BP_Groups_Member::total_group_count() );
	}

/**
 * Output the 'action' attribute for a group form.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param string                           $page  Page slug.
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_form_action( $page, $group = false ) {
	echo esc_url( bp_get_group_form_action( $page, $group ) );
}
	/**
	 * Generate the 'action' attribute for a group form.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param string                           $page  Page slug.
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_form_action( $page, $group = false ) {
		$group = bp_get_group( $group );
		$url   = '';

		if ( empty( $group->id ) || empty( $page ) ) {
			return $url;
		}

		$screens = bp_get_group_screens( 'read' );
		if ( isset( $screens[ $page ]['rewrite_id'] ) ) {
			$url = bp_get_group_url(
				$group,
				bp_groups_get_path_chunks( array( $page ) )
			);
		}

		/**
		 * Filters the 'action' attribute for a group form.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string          $url   Action attribute for a group form.
		 * @param BP_Groups_Group $group The group object.
		 * @param int|string|bool $page  Page slug.
		 */
		return apply_filters( 'bp_group_form_action', $url, $group, $page );
	}

/**
 * Output the 'action' attribute for a group admin form.
 *
 * @since 1.0.0
 * @since 10.0.0 Added the `$group` paremeter.
 *
 * @param false|string|bool                $page  (Optional). Page slug. Default: false.
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 */
function bp_group_admin_form_action( $page = false, $group = false ) {
	echo esc_url( bp_get_group_admin_form_action( $page, $group ) );
}
	/**
	 * Generate the 'action' attribute for a group admin form.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated to use `bp_get_group`.
	 *
	 * @param false|string|bool                $page  (Optional). Page slug. Default: false.
	 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
     *                                                Default: false.
	 * @return string
	 */
	function bp_get_group_admin_form_action( $page = false, $group = false ) {
		$group = bp_get_group( $group );
		$url   = '';

		if ( empty( $group->id ) ) {
			return $url;
		}

		if ( empty( $page ) ) {
			$page = bp_action_variable( 0 );
		}

		$screens = bp_get_group_screens( 'manage' );
		if ( isset( $screens[ $page ]['rewrite_id'] ) ) {
			$url = bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( $page ), 'manage' )
			);
		}

		/**
		 * Filters the 'action' attribute for a group admin form.
		 *
		 * @since 1.0.0
		 * @since 2.5.0  Added the `$group` parameter.
		 * @since 10.0.0 Added the `$page` parameter.
		 *
		 * @param string          $url   Action attribute for a group admin form.
		 * @param BP_Groups_Group $group The group object.
		 * @param int|string|bool $page  Page slug.
		 */
		return apply_filters( 'bp_group_admin_form_action', $url, $group, $page );
	}

/**
 * Determine whether the logged-in user has requested membership to a group.
 *
 * @since 1.0.0
 * @since 10.0.0 Updated to use `bp_get_group`.
 *
 * @param false|int|string|BP_Groups_Group $group (Optional) The Group ID, the Group Slug or the Group object.
 *                                                Default: false.
 * @return bool
 */
function bp_group_has_requested_membership( $group = false ) {
	$group = bp_get_group( $group );

	if ( empty( $group->id ) ) {
		return false;
	}

	return ( groups_check_for_membership_request( bp_loggedin_user_id(), $group->id ) );
}

/**
 * Check if current user is member of a group.
 *
 * @since 1.0.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @param object|bool $group Optional. Group to check is_member.
 *                           Default: current group in the loop.
 * @return bool If user is member of group or not.
 */
function bp_group_is_member( $group = false ) {
	global $groups_template;

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	/**
	 * Filters whether current user is member of a group.
	 *
	 * @since 1.2.4
	 * @since 2.5.0 Added the `$group` parameter.
	 *
	 * @param bool   $is_member If user is a member of group or not.
	 * @param object $group     Group object.
	 */
	return apply_filters( 'bp_group_is_member', ! empty( $group->is_member ), $group );
}

/**
 * Check whether the current user has an outstanding invite to the current group in the loop.
 *
 * @since 2.1.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @param object|bool $group Optional. Group data object.
 *                           Default: the current group in the groups loop.
 * @return bool True if the user has an outstanding invite, otherwise false.
 */
function bp_group_is_invited( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	/**
	 * Filters whether current user has an outstanding invite to current group in loop.
	 *
	 * @since 2.1.0
	 * @since 2.5.0 Added the `$group` parameter.
	 *
	 * @param bool   $is_invited If user has an outstanding group invite.
	 * @param object $group      Group object.
	 */
	return apply_filters( 'bp_group_is_invited', ! empty( $group->is_invited ), $group );
}

/**
 * Check if a user is banned from a group.
 *
 * If this function is invoked inside the groups template loop, then we check
 * $groups_template->group->is_banned instead of using {@link groups_is_user_banned()}
 * and making another SQL query.
 *
 * In BuddyPress 2.1, to standardize this function, we are defaulting the
 * return value to a boolean.  In previous versions, using this function would
 * return either a string of the integer (0 or 1) or null if a result couldn't
 * be found from the database.  If the logged-in user had the 'bp_moderate'
 * capability, the return value would be boolean false.
 *
 * @since 1.5.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @param BP_Groups_Group|bool $group   Group to check if user is banned.
 * @param int                  $user_id The user ID to check.
 * @return bool True if user is banned.  False if user isn't banned.
 */
function bp_group_is_user_banned( $group = false, $user_id = 0 ) {
	global $groups_template;

	// Site admins always have access.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Check groups loop first
	// @see BP_Groups_Group::get_group_extras().
	if ( ! empty( $groups_template->in_the_loop ) && isset( $groups_template->group->is_banned ) ) {
		$retval = $groups_template->group->is_banned;

	// Not in loop.
	} else {
		// Default to not banned.
		$retval = false;

		if ( empty( $group ) ) {
			$group = $groups_template->group;
		}

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		if ( ! empty( $user_id ) && ! empty( $group->id ) ) {
			$retval = groups_is_user_banned( $user_id, $group->id );
		}
	}

	/**
	 * Filters whether current user has been banned from current group in loop.
	 *
	 * @since 1.5.0
	 * @since 2.5.0 Added the `$group` parameter.
	 *
	 * @param bool   $is_invited If user has been from current group.
	 * @param object $group      Group object.
	 */
	return (bool) apply_filters( 'bp_group_is_user_banned', $retval, $group );
}

/**
 * Output the URL for accepting an invitation to the current group in the loop.
 *
 * @since 1.0.0
 */
function bp_group_accept_invite_link() {
	echo esc_url( bp_get_group_accept_invite_link() );
}
	/**
	 * Generate the URL for accepting an invitation to a group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: Current group in the loop.
	 * @return string
	 */
	function bp_get_group_accept_invite_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		$path_chunks = bp_members_get_path_chunks( array( bp_get_groups_slug(), 'invites', array( 'accept', $group->id ) ) );

		if ( bp_is_user() ) {
			$user_domain = bp_displayed_user_url( $path_chunks );
		} else {
			$user_domain = bp_loggedin_user_url( $path_chunks );
		}

		$url = wp_nonce_url( $user_domain, 'groups_accept_invite' );

		/**
		 * Filters the URL for accepting an invitation to a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string $url   URL for accepting an invitation to a group.
		 * @param object $group Group object.
		 */
		return apply_filters( 'bp_get_group_accept_invite_link', $url, $group );
	}

/**
 * Output the URL for accepting an invitation to the current group in the loop.
 *
 * @since 1.0.0
 */
function bp_group_reject_invite_link() {
	echo esc_url( bp_get_group_reject_invite_link() );
}
	/**
	 * Generate the URL for rejecting an invitation to a group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: Current group in the loop.
	 * @return string
	 */
	function bp_get_group_reject_invite_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		$path_chunks = bp_members_get_path_chunks( array( bp_get_groups_slug(), 'invites', array( 'reject', $group->id ) ) );

		if ( bp_is_user() ) {
			$user_domain = bp_displayed_user_url( $path_chunks );
		} else {
			$user_domain = bp_loggedin_user_url( $path_chunks );
		}

		$url = wp_nonce_url( $user_domain, 'groups_reject_invite' );

		/**
		 * Filters the URL for rejecting an invitation to a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string $url   URL for rejecting an invitation to a group.
		 * @param object $group Group object.
		 */
		return apply_filters( 'bp_get_group_reject_invite_link', $url, $group );
	}

/**
 * Output the URL for confirming a request to leave a group.
 *
 * @since 1.0.0
 */
function bp_group_leave_confirm_link() {
	echo esc_url( bp_get_group_leave_confirm_link() );
}
	/**
	 * Generate the URL for confirming a request to leave a group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: Current group in the loop.
	 * @return string
	 */
	function bp_get_group_leave_confirm_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		$url = wp_nonce_url(
			bp_get_group_url(
				$group,
				bp_groups_get_path_chunks( array( 'leave-group', 'yes' ) )
			),
			'groups_leave_group'
		);

		/**
		 * Filters the URL for confirming a request to leave a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string $url   URL for confirming a request to leave a group.
		 * @param object $group Group object.
		 */
		return apply_filters( 'bp_group_leave_confirm_link', $url, $group );
	}

/**
 * Output the URL for rejecting a request to leave a group.
 *
 * @since 1.0.0
 */
function bp_group_leave_reject_link() {
	echo esc_url( bp_get_group_leave_reject_link() );
}
	/**
	 * Generate the URL for rejecting a request to leave a group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: Current group in the loop.
	 * @return string
	 */
	function bp_get_group_leave_reject_link( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		/**
		 * Filters the URL for rejecting a request to leave a group.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string $value URL for rejecting a request to leave a group.
		 * @param object $group Group object.
		 */
		return apply_filters( 'bp_get_group_leave_reject_link', bp_get_group_url( $group ), $group );
	}

/**
 * Output the 'action' attribute for a group send invite form.
 *
 * @since 1.0.0
 */
function bp_group_send_invite_form_action() {
	echo esc_url( bp_get_group_send_invite_form_action() );
}
	/**
	 * Output the 'action' attribute for a group send invite form.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Optional. Group object.
	 *                           Default: current group in the loop.
	 * @return string
	 */
	function bp_get_group_send_invite_form_action( $group = false ) {
		global $groups_template;

		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		$url = bp_get_group_url(
			$group,
			bp_groups_get_path_chunks( array( 'send-invites', 'send' ) )
		);

		/**
		 * Filters the 'action' attribute for a group send invite form.
		 *
		 * @since 1.0.0
		 * @since 2.5.0 Added the `$group` parameter.
		 *
		 * @param string $value Action attribute for a group send invite form.
		 * @param object $group Group object.
		 */
		return apply_filters( 'bp_group_send_invite_form_action', $url, $group );
	}

/**
 * Determine whether the current user has friends to invite to a group.
 *
 * @since 1.0.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in the loop.
 * @return bool
 */
function bp_has_friends_to_invite( $group = false ) {
	global $groups_template;

	if ( ! bp_is_active( 'friends' ) ) {
		return false;
	}

	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	if ( !friends_check_user_has_friends( bp_loggedin_user_id() ) || !friends_count_invitable_friends( bp_loggedin_user_id(), $group->id ) ) {
		return false;
	}

	return true;
}

/**
 * Output button to join a group.
 *
 * @since 1.0.0
 *
 * @param object|bool $group Single group object.
 */
function bp_group_join_button( $group = false ) {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_join_button( $group );
}

	/**
	 * Get the arguments for the Join button group
	 *
	 * @since 11.0.0
	 *
	 * @param BP_Groups_Group $group The group object.
	 * @return Array The arguments for the Join button group
	 */
	function bp_groups_get_group_join_button_args( $group = null ) {
		$button_args = array();

		if ( empty( $group->id ) ) {
			return $button_args;
		}

		// Don't show button if not logged in or previously banned.
		if ( ! is_user_logged_in() || bp_group_is_user_banned( $group ) ) {
			return $button_args;
		}

		// Group creation was not completed or status is unknown.
		if ( empty( $group->status ) ) {
			return $button_args;
		}

		// Already a member.
		if ( ! empty( $group->is_member ) ) {

			// Stop sole admins from abandoning their group.
			$group_admins = groups_get_group_admins( $group->id );
			if ( ( 1 == count( $group_admins ) ) && ( bp_loggedin_user_id() === (int) $group_admins[0]->user_id ) ) {
				return $button_args;
			}

			$url = wp_nonce_url(
				bp_get_group_url(
					$group,
					bp_groups_get_path_chunks( array( 'leave-group' ) )
				),
				'groups_leave_group'
			);

			// Setup button attributes.
			$button_args = array(
				'id'                => 'leave_group',
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'group-button ' . $group->status,
				'wrapper_id'        => 'groupbutton-' . $group->id,
				'link_href'         => $url,
				'link_text'         => __( 'Leave Group', 'buddypress' ),
				'link_title'        => __( 'Leave Group', 'buddypress' ),
				'link_class'        => 'group-button leave-group',
			);

		// Not a member.
		} else {

			// Show different buttons based on group status.
			switch ( $group->status ) {
				case 'hidden' :
					return $button_args;

				case 'public':
					$url = wp_nonce_url(
						bp_get_group_url(
							$group,
							bp_groups_get_path_chunks( array( 'join' ) )
						),
						'groups_join_group'
					);

					$button_args = array(
						'id'                => 'join_group',
						'component'         => 'groups',
						'must_be_logged_in' => true,
						'block_self'        => false,
						'wrapper_class'     => 'group-button ' . $group->status,
						'wrapper_id'        => 'groupbutton-' . $group->id,
						'link_href'         => $url,
						'link_text'         => __( 'Join Group', 'buddypress' ),
						'link_title'        => __( 'Join Group', 'buddypress' ),
						'link_class'        => 'group-button join-group',
					);
					break;

				case 'private' :

					// Member has outstanding invitation -
					// show an "Accept Invitation" button.
					if ( $group->is_invited ) {
						$button_args = array(
							'id'                => 'accept_invite',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => add_query_arg( 'redirect_to', bp_get_group_url( $group ), bp_get_group_accept_invite_link( $group ) ),
							'link_text'         => __( 'Accept Invitation', 'buddypress' ),
							'link_title'        => __( 'Accept Invitation', 'buddypress' ),
							'link_class'        => 'group-button accept-invite',
						);

					// Member has requested membership but request is pending -
					// show a "Request Sent" button.
					} elseif ( $group->is_pending ) {
						$button_args = array(
							'id'                => 'membership_requested',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button pending ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => bp_get_group_url( $group ),
							'link_text'         => __( 'Request Sent', 'buddypress' ),
							'link_title'        => __( 'Request Sent', 'buddypress' ),
							'link_class'        => 'group-button pending membership-requested',
						);

					// Member has not requested membership yet -
					// show a "Request Membership" button.
					} else {
						$url = wp_nonce_url(
							bp_get_group_url(
								$group,
								bp_groups_get_path_chunks( array( 'request-membership' ) )
							),
							'groups_request_membership'
						);

						$button_args = array(
							'id'                => 'request_membership',
							'component'         => 'groups',
							'must_be_logged_in' => true,
							'block_self'        => false,
							'wrapper_class'     => 'group-button ' . $group->status,
							'wrapper_id'        => 'groupbutton-' . $group->id,
							'link_href'         => $url,
							'link_text'         => __( 'Request Membership', 'buddypress' ),
							'link_title'        => __( 'Request Membership', 'buddypress' ),
							'link_class'        => 'group-button request-membership',
						);
					}

					break;
			}
		}

		/**
		 * Filters the arguments of the button for joining a group.
		 *
		 * @since 1.2.6
		 * @since 2.4.0 Added $group parameter to filter args.
		 *
		 * @param array  $button_args The arguments for the button.
		 * @param object $group       BuddyPress group object
		 */
		return (array) apply_filters( 'bp_get_group_join_button', $button_args, $group );
	}
	/**
	 * Return button to join a group.
	 *
	 * @since 1.0.0
	 * @since 11.0.0 uses `bp_groups_get_group_join_button_args()`.
	 *
	 * @global BP_Groups_Template $groups_template The Groups template loop class.
	 *
	 * @param object|bool $group Single group object.
	 * @return false|string
	 */
	function bp_get_group_join_button( $group = false ) {
		global $groups_template;

		// Set group to current loop group if none passed.
		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}

		$button_args = bp_groups_get_group_join_button_args( $group );

		if ( ! array_filter( $button_args ) ) {
			return false;
		}

		return bp_get_button( $button_args );
	}

/**
 * Output the Create a Group button.
 *
 * @since 2.0.0
 */
function bp_group_create_button() {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_create_button();
}
	/**
	 * Get the Create a Group button.
	 *
	 * @since 2.0.0
	 *
	 * @return false|string
	 */
	function bp_get_group_create_button() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! bp_user_can_create_groups() ) {
			return false;
		}

		$button_args = array(
			'id'         => 'create_group',
			'component'  => 'groups',
			'link_text'  => __( 'Create a Group', 'buddypress' ),
			'link_class' => 'group-create no-ajax',
			'link_href'  => bp_groups_get_create_url(),
			'wrapper'    => false,
			'block_self' => false,
		);

		/**
		 * Filters the HTML button for creating a group.
		 *
		 * @since 2.0.0
		 *
		 * @param array $button_args HTML button for creating a group.
		 */
		$button_args = apply_filters( 'bp_get_group_create_button', $button_args );

		return bp_get_button( $button_args );
	}

/**
 * Output the Create a Group nav item.
 *
 * @since 2.2.0
 */
function bp_group_create_nav_item() {
	// Escaping is done in `BP_Core_HTML_Element()`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_create_nav_item();
}
	/**
	 * Get the Create a Group nav item.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	function bp_get_group_create_nav_item() {
		// Get the create a group button.
		$create_group_button = bp_get_group_create_button();

		// Make sure the button is available.
		if ( empty( $create_group_button ) ) {
			return;
		}

		$output = '<li id="group-create-nav">' . $create_group_button . '</li>';

		/**
		 * Filters the Create a Group nav item.
		 *
		 * @since 2.2.0
		 *
		 * @param string $output HTML output for nav item.
		 */
		return apply_filters( 'bp_get_group_create_nav_item', $output );
	}

/**
 * Checks if a specific theme is still filtering the Groups directory title
 * if so, transform the title button into a Groups directory nav item.
 *
 * @since 2.2.0
 *
 * @return string|null HTML Output
 */
function bp_group_backcompat_create_nav_item() {
	// Bail if the Groups nav item is already used by bp-legacy.
	if ( has_action( 'bp_groups_directory_group_filter', 'bp_legacy_theme_group_create_nav' ) ) {
		return;
	}

	// Bail if the theme is not filtering the Groups directory title.
	if ( ! has_filter( 'bp_groups_directory_header' ) ) {
		return;
	}

	bp_group_create_nav_item();
}
add_action( 'bp_groups_directory_group_filter', 'bp_group_backcompat_create_nav_item', 1000 );

/**
 * Prints a message if the group is not visible to the current user (it is a
 * hidden or private group, and the user does not have access).
 *
 * @since 1.0.0
 *
 * @global BP_Groups_Template $groups_template The Groups template loop class.
 *
 * @param object|null $group Group to get status message for. Optional; defaults to current group.
 */
function bp_group_status_message( $group = null ) {
	global $groups_template;

	// Group not passed so look for loop.
	if ( empty( $group ) ) {
		$group =& $groups_template->group;
	}

	// Group status is not set (maybe outside of group loop?).
	if ( empty( $group->status ) ) {
		$message = __( 'This group is not currently accessible.', 'buddypress' );

	// Group has a status.
	} else {
		switch( $group->status ) {

			// Private group.
			case 'private' :
				if ( ! bp_group_has_requested_membership( $group ) ) {
					if ( is_user_logged_in() ) {
						if ( bp_group_is_invited( $group ) ) {
							$message = __( 'You must accept your pending invitation before you can access this private group.', 'buddypress' );
						} else {
							$message = __( 'This is a private group and you must request group membership in order to join.', 'buddypress' );
						}
					} else {
						$message = __( 'This is a private group. To join you must be a registered site member and request group membership.', 'buddypress' );
					}
				} else {
					$message = __( 'This is a private group. Your membership request is awaiting approval from the group administrator.', 'buddypress' );
				}

				break;

			// Hidden group.
			case 'hidden' :
			default :
				$message = __( 'This is a hidden group and only invited members can join.', 'buddypress' );
				break;
		}
	}

	/**
	 * Filters a message if the group is not visible to the current user.
	 *
	 * This will be true if it is a hidden or private group, and the user does not have access.
	 *
	 * @since 1.6.0
	 *
	 * @param string $message Message to display to the current user.
	 * @param object $group   Group to get status message for.
	 */
	echo esc_html( apply_filters( 'bp_group_status_message', $message, $group ) );
}

/**
 * Output hidden form fields for group.
 *
 * This function is no longer used, but may still be used by older themes.
 *
 * @since 1.0.0
 */
function bp_group_hidden_fields() {
	$query_arg = bp_core_get_component_search_query_arg( 'groups' );

	if ( isset( $_REQUEST[ $query_arg ] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST[ $query_arg ] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['groups_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['groups_search'] ) . '" name="search_terms" />';
	}
}

/**
 * Output the total number of groups.
 *
 * @since 1.0.0
 */
function bp_total_group_count() {
	echo intval( bp_get_total_group_count() );
}
	/**
	 * Return the total number of groups.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	function bp_get_total_group_count() {

		/**
		 * Filters the total number of groups.
		 *
		 * @since 1.0.0
		 *
		 * @param int $value Total number of groups found.
		 */
		return apply_filters( 'bp_get_total_group_count', (int) groups_get_total_group_count() );
	}

/**
 * Output the total number of groups a user belongs to.
 *
 * @since 1.0.0
 *
 * @param int $user_id User ID to get group membership count.
 */
function bp_total_group_count_for_user( $user_id = 0 ) {
	echo intval( bp_get_total_group_count_for_user( $user_id ) );
}
	/**
	 * Return the total number of groups a user belongs to.
	 *
	 * Filtered by `bp_core_number_format()` by default
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID to get group membership count.
	 * @return string
	 */
	function bp_get_total_group_count_for_user( $user_id = 0 ) {
		$count = groups_total_groups_for_user( $user_id );

		/**
		 * Filters the total number of groups a user belongs to.
		 *
		 * @since 1.2.0
		 *
		 * @param int $count   Total number of groups for the user.
		 * @param int $user_id ID of the user being checked.
		 */
		return apply_filters( 'bp_get_total_group_count_for_user', $count, $user_id );
	}

/* Group Members *************************************************************/

/**
 * Initialize a group member query loop.
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @param array|string $args {
 *     An array of optional arguments.
 *     @type int      $group_id           ID of the group whose members are being queried.
 *                                        Default: current group ID.
 *     @type int      $page               Page of results to be queried. Default: 1.
 *     @type int      $per_page           Number of items to return per page of results.
 *                                        Default: 20.
 *     @type int      $max                Optional. Max number of items to return.
 *     @type array    $exclude            Optional. Array of user IDs to exclude.
 *     @type bool|int $exclude_admin_mods True (or 1) to exclude admins and mods from results.
 *                                        Default: 1.
 *     @type bool|int $exclude_banned     True (or 1) to exclude banned users from results.
 *                                        Default: 1.
 *     @type array    $group_role         Optional. Array of group roles to include.
 *     @type string   $type               Optional. Sort order of results. 'last_joined',
 *                                        'first_joined', or any of the $type params available in
 *                                        {@link BP_User_Query}. Default: 'last_joined'.
 *     @type string   $search_terms       Optional. Search terms to match. Pass an
 *                                        empty string to force-disable search, even in
 *                                        the presence of $_REQUEST['s']. Default: false.
 * }
 *
 * @return bool
 */
function bp_group_has_members( $args = '' ) {
	global $members_template;

	$exclude_admins_mods = 1;

	if ( bp_is_group_members() ) {
		$exclude_admins_mods = 0;
	}

	/*
	 * Use false as the search_terms default so that BP_User_Query
	 * doesn't add a search clause.
	 */
	$search_terms_default = false;
	$search_query_arg = bp_core_get_component_search_query_arg( 'members' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	$r = bp_parse_args(
		$args,
		array(
			'group_id'            => bp_get_current_group_id(),
			'page'                => 1,
			'per_page'            => 20,
			'max'                 => false,
			'exclude'             => false,
			'exclude_admins_mods' => $exclude_admins_mods,
			'exclude_banned'      => 1,
			'group_role'          => false,
			'search_terms'        => $search_terms_default,
			'type'                => 'last_joined',
		),
		'group_has_members'
	);

	/*
	 * If an empty search_terms string has been passed,
	 * the developer is force-disabling search.
	 */
	if ( '' === $r['search_terms'] ) {
		// Set the search_terms to false for BP_User_Query efficiency.
		$r['search_terms'] = false;
	} elseif ( ! empty( $_REQUEST['s'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	$members_template = new BP_Groups_Group_Members_Template( $r );

	/**
	 * Filters whether or not a group member query has members to display.
	 *
	 * @since 1.1.0
	 *
	 * @param bool                             $value            Whether there are members to display.
	 * @param BP_Groups_Group_Members_Template $members_template Object holding the member query results.
	 */
	return apply_filters( 'bp_group_has_members', $members_template->has_members(), $members_template );
}

/**
 * The list of group members.
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @return mixed
 */
function bp_group_members() {
	global $members_template;

	return $members_template->members();
}

/**
 * The current Member being iterated on.
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @return mixed
 */
function bp_group_the_member() {
	global $members_template;

	return $members_template->the_member();
}

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since 1.0.0
 *
 * @param array|string $args {@see bp_core_fetch_avatar()}.
 */
function bp_group_member_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_avatar( $args );
}
	/**
	 * Return the group member avatar while in the groups members loop.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param array|string $args {@see bp_core_fetch_avatar()}.
	 * @return string
	 */
	function bp_get_group_member_avatar( $args = '' ) {
		global $members_template;

		$r = bp_parse_args(
			$args,
			array(
				'item_id' => $members_template->member->user_id,
				'type'    => 'full',
				'email'   => $members_template->member->user_email,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name )
			)
		);

		/**
		 * Filters the group member avatar while in the groups members loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value HTML markup for group member avatar.
		 * @param array  $r     Parsed args used for the avatar query.
		 */
		return apply_filters( 'bp_get_group_member_avatar', bp_core_fetch_avatar( $r ), $r );
	}

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since 1.0.0
 *
 * @param array|string $args {@see bp_core_fetch_avatar()}.
 */
function bp_group_member_avatar_thumb( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_avatar_thumb( $args );
}
	/**
	 * Return the group member avatar while in the groups members loop.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param array|string $args {@see bp_core_fetch_avatar()}.
	 * @return string
	 */
	function bp_get_group_member_avatar_thumb( $args = '' ) {
		global $members_template;

		$r = bp_parse_args(
			$args,
			array(
				'item_id' => $members_template->member->user_id,
				'type'    => 'thumb',
				'email'   => $members_template->member->user_email,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name )
			)
		);

		/**
		 * Filters the group member avatar thumb while in the groups members loop.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value HTML markup for group member avatar thumb.
		 * @param array  $r     Parsed args used for the avatar query.
		 */
		return apply_filters( 'bp_get_group_member_avatar_thumb', bp_core_fetch_avatar( $r ), $r );
	}

/**
 * Output the group member avatar while in the groups members loop.
 *
 * @since 1.0.0
 *
 * @param int $width  Width of avatar to fetch.
 * @param int $height Height of avatar to fetch.
 */
function bp_group_member_avatar_mini( $width = 30, $height = 30 ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_avatar_mini( $width, $height );
}
	/**
	 * Output the group member avatar while in the groups members loop.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param int $width  Width of avatar to fetch.
	 * @param int $height Height of avatar to fetch.
	 * @return string
	 */
	function bp_get_group_member_avatar_mini( $width = 30, $height = 30 ) {
		global $members_template;

		$r = bp_parse_args(
			array(),
			array(
				'item_id' => $members_template->member->user_id,
				'type'    => 'thumb',
				'email'   => $members_template->member->user_email,
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $members_template->member->display_name ),
				'width'   => absint( $width ),
				'height'  => absint( $height ),
			)
		);

		/**
		 * Filters the group member avatar mini while in the groups members loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value HTML markup for group member avatar mini.
		 * @param array  $r     Parsed args used for the avatar query.
		 */
		return apply_filters( 'bp_get_group_member_avatar_mini', bp_core_fetch_avatar( $r ), $r );
	}

/**
 * Outputs the group member name.
 *
 * @since 1.0.0
 */
function bp_group_member_name() {
	echo esc_html( bp_get_group_member_name() );
}
	/**
	 * Returns the group member's name.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_name() {
		global $members_template;

		/**
		 * Filters the group member display name of the current user in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $display_name Display name of the current user.
		 */
		return apply_filters( 'bp_get_group_member_name', $members_template->member->display_name );
	}

/**
 * Outputs the group member's URL.
 *
 * @since 1.0.0
 */
function bp_group_member_url() {
	echo esc_url( bp_get_group_member_url() );
}
	/**
	 * Returns the group member's URL.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_url() {
		global $members_template;

		/**
		 * Filters the group member url for the current user in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value URL for the current user.
		 */
		return apply_filters( 'bp_get_group_member_url', bp_members_get_user_url( $members_template->member->user_id ) );
	}

/**
 * Outputs the group member's link.
 *
 * @since 1.0.0
 */
function bp_group_member_link() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_link();
}
	/**
	 * Returns the group member's link.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_link() {
		global $members_template;

		/**
		 * Filters the group member HTML link for the current user in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value HTML link for the current user.
		 */
		return apply_filters( 'bp_get_group_member_link', '<a href="' . esc_url( bp_members_get_user_url( $members_template->member->user_id ) ) . '">' . esc_html( $members_template->member->display_name ) . '</a>' );
	}

/**
 * Outputs the group member's domain.
 *
 * @since 1.2.0
 */
function bp_group_member_domain() {
	echo esc_url( bp_get_group_member_domain() );
}
	/**
	 * Returns the group member's domain.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_domain() {
		global $members_template;

		/**
		 * Filters the group member domain for the current user in the loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Domain for the current user.
		 */
		return apply_filters( 'bp_get_group_member_domain', bp_members_get_user_url( $members_template->member->user_id ) );
	}

/**
 * Outputs the group member's friendship status with logged in user.
 *
 * @since 1.2.0
 */
function bp_group_member_is_friend() {
	echo esc_html( bp_get_group_member_is_friend() );
}
	/**
	 * Retruns the group member's friendship status with logged in user.
	 *
	 * @since 1.2.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_is_friend() {
		global $members_template;

		if ( !isset( $members_template->member->is_friend ) ) {
			$friend_status = 'not_friends';
		} else {
			$friend_status = ( 0 == $members_template->member->is_friend )
				? 'pending'
				: 'is_friend';
		}

		/**
		 * Filters the friendship status between current user and displayed user in group member loop.
		 *
		 * @since 1.2.0
		 *
		 * @param string $friend_status Current status of the friendship.
		 */
		return apply_filters( 'bp_get_group_member_is_friend', $friend_status );
	}

/**
 * Check whether the member is banned from the current group.
 *
 * @since 1.0.0
 */
function bp_group_member_is_banned() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_is_banned();
}
	/**
	 * Check whether the member is banned from the current group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return bool
	 */
	function bp_get_group_member_is_banned() {
		global $members_template;

		if ( ! isset( $members_template->member->is_banned ) ) {
			return false;
		}

		/**
		 * Filters whether the member is banned from the current group.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $is_banned Whether or not the member is banned.
		 */
		return apply_filters( 'bp_get_group_member_is_banned', (bool) $members_template->member->is_banned );
	}

/**
 * Output CSS if group member is banned.
 *
 * @since 1.2.6
 * @since 10.0.0 Updated to use `bp_get_group_member_is_banned`.
 */
function bp_group_member_css_class() {
	if ( bp_get_group_member_is_banned() ) {

		/**
		 * Filters the class to add to the HTML if member is banned.
		 *
		 * @since 1.2.6
		 *
		 * @param string $value HTML class to add.
		 */
		echo esc_attr( apply_filters( 'bp_group_member_css_class', 'banned-user' ) );
	}
}

/**
 * Output the joined date for the current member in the group member loop.
 *
 * @since 1.0.0
 * @since 2.7.0 Added $args as a parameter.
 *
 * @param array|string $args {@see bp_get_group_member_joined_since()}
 * @return string|null
 */
function bp_group_member_joined_since( $args = array() ) {
	echo esc_html( bp_get_group_member_joined_since( $args ) );
}
	/**
	 * Return the joined date for the current member in the group member loop.
	 *
	 * @since 1.0.0
	 * @since 2.7.0 Added $args as a parameter.
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @param array|string $args {
	 *     Array of optional parameters.
	 *
	 *     @type bool $relative Optional. If true, returns relative joined date. eg. joined 5 months ago.
	 *                          If false, returns joined date value from database. Default: true.
	 * }
	 * @return string
	 */
	function bp_get_group_member_joined_since( $args = array() ) {
		global $members_template;

		$r = bp_parse_args(
			$args,
			array(
				'relative' => true,
			),
			'group_member_joined_since'
		);

		// We do not want relative time, so return now.
		// @todo Should the 'bp_get_group_member_joined_since' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $members_template->member->date_modified );
		}

		/**
		 * Filters the joined since time for the current member in the loop.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Joined since time.
		 */
		return apply_filters(
			'bp_get_group_member_joined_since',
			bp_core_get_last_activity(
				$members_template->member->date_modified,
				/* translators: %s: human time diff */
				__( 'joined %s', 'buddypress')
			)
		);
	}

/**
 * Get group member from current group.
 *
 * @since 1.0.0
 */
function bp_group_member_id() {
	echo intval( bp_get_group_member_id() );
}
	/**
	 * Get group member from current group.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return int
	 */
	function bp_get_group_member_id() {
		global $members_template;

		if ( ! isset( $members_template->member->user_id ) ) {
			return 0;
		}

		/**
		 * Filters the member's user ID for group members loop.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id User ID of the member.
		 */
		return apply_filters( 'bp_get_group_member_id', (int) $members_template->member->user_id );
	}

/**
 * Do the list of group members needs a pagination?
 *
 * @since 1.0.0
 *
 * @global BP_Core_Members_Template $members_template The Members template loop class.
 *
 * @return bool
 */
function bp_group_member_needs_pagination() {
	global $members_template;

	return ( $members_template->total_member_count > $members_template->pag_num );
}

/**
 * @since 1.0.0
 */
function bp_group_pag_id() {
	echo esc_attr( bp_get_group_pag_id() );
}
	/**
	 * @since 1.0.0
	 *
	 * @return mixed|void
	 */
	function bp_get_group_pag_id() {

		/**
		 * Filters the string to be used as the group pag id.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Value to use for the pag id.
		 */
		return apply_filters( 'bp_get_group_pag_id', 'pag' );
	}

/**
 * Outputs the group members list pagination links.
 *
 * @since 1.0.0
 */
function bp_group_member_pagination() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_pagination();
	wp_nonce_field( 'bp_groups_member_list', '_member_pag_nonce' );
}
	/**
	 * Returns the group members list pagination links.
	 *
	 *  @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_pagination() {
		global $members_template;

		/**
		 * Filters the HTML markup to be used for group member listing pagination.
		 *
		 * @since 1.0.0
		 *
		 * @param string $pag_links HTML markup for the pagination.
		 */
		return apply_filters( 'bp_get_group_member_pagination', $members_template->pag_links );
	}

/**
 * Outputs the group members list pagination count.
 *
 * @since 1.0.0
 */
function bp_group_member_pagination_count() {
	echo esc_html( bp_get_group_member_pagination_count() );
}
	/**
	 * Returns the group members list pagination count.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed|void
	 */
	function bp_get_group_member_pagination_count() {
		global $members_template;

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 1 == $members_template->total_member_count ) {
			$message = __( 'Viewing 1 member', 'buddypress' );
		} else {
			/* translators: 1: group member from number. 2: group member to number. 3: total group members. */
			$message = sprintf( _nx( 'Viewing %1$s - %2$s of %3$s member', 'Viewing %1$s - %2$s of %3$s members', $members_template->total_member_count, 'group members pagination', 'buddypress' ), $from_num, $to_num, $total );
		}

		/**
		 * Filters the "Viewing x-y of z members" pagination message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value    "Viewing x-y of z members" text.
		 * @param string $from_num Total amount for the low value in the range.
		 * @param string $to_num   Total amount for the high value in the range.
		 * @param string $total    Total amount of members found.
		 */
		return apply_filters( 'bp_get_group_member_pagination_count', $message, $from_num, $to_num, $total );
	}

/**
 * Outputs the group members list pagination links inside the Group's Manage screen.
 *
 * @since 1.0.0
 */
function bp_group_member_admin_pagination() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_member_admin_pagination();
	wp_nonce_field( 'bp_groups_member_admin_list', '_member_admin_pag_nonce' );
}
	/**
	 * Returns the group members list pagination links inside the Group's Manage screen.
	 *
	 * @since 1.0.0
	 *
	 * @global BP_Core_Members_Template $members_template The Members template loop class.
	 *
	 * @return mixed
	 */
	function bp_get_group_member_admin_pagination() {
		global $members_template;

		return $members_template->pag_links;
	}

/**
 * Output the contents of the current group's home page.
 *
 * You should only use this when on a single group page.
 *
 * @since 2.4.0
 */
function bp_groups_front_template_part() {
	$located = bp_groups_get_front_template();

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, false );

		load_template( $located, true );

	} else if ( bp_is_active( 'activity' ) ) {
		bp_get_template_part( 'groups/single/activity' );

	} else if ( bp_is_active( 'members'  ) ) {
		bp_groups_members_template_part();
	}

	return $located;
}

/**
 * Locate a custom group front template if it exists.
 *
 * @since 2.4.0
 * @since 2.6.0 Adds the Group Type to the front template hierarchy.
 *
 * @param  BP_Groups_Group|null $group Optional. Falls back to current group if not passed.
 * @return string|bool                 Path to front template on success; boolean false on failure.
 */
function bp_groups_get_front_template( $group = null ) {
	if ( ! is_a( $group, 'BP_Groups_Group' ) ) {
		$group = groups_get_current_group();
	}

	if ( ! isset( $group->id ) ) {
		return false;
	}

	if ( isset( $group->front_template ) ) {
		return $group->front_template;
	}

	$template_names = array(
		'groups/single/front-id-'     . (int) $group->id . '.php',
		'groups/single/front-slug-'   . sanitize_file_name( $group->slug )   . '.php',
	);

	if ( bp_groups_get_group_types() ) {
		$group_type = bp_groups_get_group_type( $group->id );
		if ( ! $group_type ) {
			$group_type = 'none';
		}

		$template_names[] = 'groups/single/front-group-type-' . sanitize_file_name( $group_type )   . '.php';
	}

	$template_names = array_merge( $template_names, array(
		'groups/single/front-status-' . sanitize_file_name( $group->status ) . '.php',
		'groups/single/front.php'
	) );

	/**
	 * Filters the hierarchy of group front templates corresponding to a specific group.
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Added the `$group` parameter.
	 *
	 * @param array  $template_names Array of template paths.
	 * @param object $group          Group object.
	 */
	return bp_locate_template( apply_filters( 'bp_groups_get_front_template', $template_names, $group ), false, true );
}

/**
 * Output the Group members template
 *
 * @since 2.0.0
 */
function bp_groups_members_template_part() {
	?>
	<div class="item-list-tabs" id="subnav" aria-label="<?php esc_attr_e( 'Group secondary navigation', 'buddypress' ); ?>" role="navigation">
		<ul>
			<li class="groups-members-search" role="search">
				<?php bp_directory_members_search_form(); ?>
			</li>

			<?php bp_groups_members_filter(); ?>
			<?php

			/**
			 * Fires at the end of the group members search unordered list.
			 *
			 * Part of bp_groups_members_template_part().
			 *
			 * @since 1.5.0
			 */
			do_action( 'bp_members_directory_member_sub_types' ); ?>

		</ul>
	</div>

	<h2 class="bp-screen-reader-text">
		<?php
			/* translators: accessibility text */
			esc_html_e( 'Members', 'buddypress' );
		?>
	</h2>

	<div id="members-group-list" class="group_members dir-list">

		<?php bp_get_template_part( 'groups/single/members' ); ?>

	</div>
	<?php
}

/**
 * Output the Group members filters
 *
 * @since 2.0.0
 */
function bp_groups_members_filter() {
	?>
	<li id="group_members-order-select" class="last filter">
		<label for="group_members-order-by"><?php esc_html_e( 'Order By:', 'buddypress' ); ?></label>
		<select id="group_members-order-by">
			<option value="last_joined"><?php esc_html_e( 'Newest', 'buddypress' ); ?></option>
			<option value="first_joined"><?php esc_html_e( 'Oldest', 'buddypress' ); ?></option>

			<?php if ( bp_is_active( 'activity' ) ) : ?>
				<option value="group_activity"><?php esc_html_e( 'Group Activity', 'buddypress' ); ?></option>
			<?php endif; ?>

			<option value="alphabetical"><?php esc_html_e( 'Alphabetical', 'buddypress' ); ?></option>

			<?php

			/**
			 * Fires at the end of the Group members filters select input.
			 *
			 * Useful for plugins to add more filter options.
			 *
			 * @since 2.0.0
			 */
			do_action( 'bp_groups_members_order_options' ); ?>

		</select>
	</li>
	<?php
}

/*
 * Group Creation Process Template Tags
 */

/**
 * Determine if the current logged in user can create groups.
 *
 * @since 1.5.0
 *
 * @return bool True if user can create groups. False otherwise.
 */
function bp_user_can_create_groups() {

	// Super admin can always create groups.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	// Get group creation option, default to 0 (allowed).
	$restricted = (int) bp_get_option( 'bp_restrict_group_creation', 0 );

	// Allow by default.
	$can_create = true;

	// Are regular users restricted?
	if ( $restricted ) {
		$can_create = false;
	}

	/**
	 * Filters if the current logged in user can create groups.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $can_create Whether the person can create groups.
	 * @param int  $restricted Whether or not group creation is restricted.
	 */
	return apply_filters( 'bp_user_can_create_groups', $can_create, $restricted );
}

/**
 * Outputs the Group creation tabs.
 *
 * @since 1.0.0
 */
function bp_group_creation_tabs() {
	$bp           = buddypress();
	$create_steps = $bp->groups->group_creation_steps;

	if ( ! is_array( $create_steps ) ) {
		return false;
	}

	if ( ! bp_get_groups_current_create_step() ) {
		$keys                            = array_keys( $create_steps );
		$bp->groups->current_create_step = array_shift( $keys );
	}

	$counter = 1;

	foreach ( (array) $create_steps as $create_step => $step ) {
		$is_enabled    = bp_are_previous_group_creation_steps_complete( $create_step );
		$current_class = '';
		$step_name     = $step['name'];

		if ( bp_get_groups_current_create_step() === $create_step ) {
			$current_class = ' class="current"';
		}

		if ( $is_enabled && isset( $create_steps[ $create_step ]['rewrite_id'], $create_steps[ $create_step ]['default_slug'] ) ) {
			$url = bp_groups_get_create_url( array( $create_steps[ $create_step ]['default_slug'] ) );

			$step_name = sprintf( '<a href="%1$s">%2$s. %3$s</a>', esc_url( $url ), absint( $counter ), esc_html( $step_name ) );
		} else {
			$step_name = sprintf( '<span>%1$s. %2$s</span>', absint( $counter ), esc_html( $step_name ) );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput
		printf( '<li%1$s>%2$s</li>', $current_class, $step_name );
		$counter++;
		unset( $is_enabled );
	}

	/**
	 * Fires at the end of the creation of the group tabs.
	 *
	 * @since 1.0.0
	 */
	do_action( 'groups_creation_tabs' );
}

/**
 * Output the group creation step's title.
 *
 * @since 1.0.0
 */
function bp_group_creation_stage_title() {
	$bp = buddypress();

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters the group creation stage title.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value HTML markup for the group creation stage title.
		 */
		'bp_group_creation_stage_title',
		'<span>&mdash; ' . esc_html( $bp->groups->group_creation_steps[bp_get_groups_current_create_step()]['name'] ) . '</span>'
	);
}

/**
 * Output the group's creation form action URL.
 *
 * @since 1.1.0
 */
function bp_group_creation_form_action() {
	echo esc_url( bp_get_group_creation_form_action() );
}

/**
 * Get the group's creation form action URL.
 *
 * @since 1.1.0
 *
 * @return string The group's creation form action URL.
 */
	function bp_get_group_creation_form_action() {
		$bp           = buddypress();
		$create_steps = $bp->groups->group_creation_steps;
		$url          = '';

		if ( ! bp_action_variable( 1 ) ) {
			$keys = array_keys( $create_steps );
			$bp->action_variables[1] = array_shift( $keys );
		}

		$create_step  = bp_action_variable( 1 );
		if ( $create_step && isset( $create_steps[ $create_step ]['rewrite_id'], $create_steps[ $create_step ]['default_slug'] ) ) {
			$url = bp_groups_get_create_url( array( $create_steps[ $create_step ]['default_slug'] ) );
		}

		/**
		 * Filters the group creation form action.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url Action to be used with group creation form.
		 */
		return apply_filters( 'bp_get_group_creation_form_action', $url );
	}

/**
 * Check the requested creation step is the current one.
 *
 * @since 1.1.0
 *
 * @param string $step_slug The group creation step's slug.
 *
 * @return bool
 */
function bp_is_group_creation_step( $step_slug ) {

	// Make sure we are in the groups component.
	if ( ! bp_is_groups_component() || ! bp_is_current_action( 'create' ) ) {
		return false;
	}

	$bp = buddypress();

	// If this the first step, we can just accept and return true.
	$keys = array_keys( $bp->groups->group_creation_steps );
	if ( ! bp_action_variable( 1 ) && array_shift( $keys ) == $step_slug ) {
		return true;
	}

	// Before allowing a user to see a group creation step we must make sure
	// previous steps are completed.
	if ( ! bp_is_first_group_creation_step() ) {
		if ( ! bp_are_previous_group_creation_steps_complete( $step_slug ) ) {
			return false;
		}
	}

	// Check the current step against the step parameter.
	if ( bp_is_action_variable( $step_slug ) ) {
		return true;
	}

	return false;
}

/**
 * Check the requested creation step is completed.
 *
 * @since 1.1.0
 *
 * @param array $step_slugs The list of group creation step slugs.
 *
 * @return bool
 */
function bp_is_group_creation_step_complete( $step_slugs ) {
	$bp = buddypress();

	if ( ! isset( $bp->groups->completed_create_steps ) ) {
		return false;
	}

	if ( is_array( $step_slugs ) ) {
		$found = true;

		foreach ( (array) $step_slugs as $step_slug ) {
			if ( ! in_array( $step_slug, $bp->groups->completed_create_steps ) ) {
				$found = false;
			}
		}

		return $found;
	} else {
		return in_array( $step_slugs, $bp->groups->completed_create_steps );
	}

	return true;
}

/**
 * Check previous steps compared to the requested creation step are completed.
 *
 * @since 1.1.0
 *
 * @param string $step_slug The group creation step's slug.
 *
 * @return bool
 */
function bp_are_previous_group_creation_steps_complete( $step_slug ) {
	$bp = buddypress();

	// If this is the first group creation step, return true.
	$keys = array_keys( $bp->groups->group_creation_steps );
	if ( array_shift( $keys ) == $step_slug ) {
		return true;
	}

	reset( $bp->groups->group_creation_steps );

	$previous_steps = array();

	// Get previous steps.
	foreach ( (array) $bp->groups->group_creation_steps as $slug => $name ) {
		if ( $slug === $step_slug ) {
			break;
		}

		$previous_steps[] = $slug;
	}

	return bp_is_group_creation_step_complete( $previous_steps );
}

/**
 * Outputs the new group ID.
 *
 * @since 1.1.0
 */
function bp_new_group_id() {
	echo intval( bp_get_new_group_id() );
}

	/**
	 * @since 1.1.0
	 *
	 * @return int
	 */
	function bp_get_new_group_id() {
		$bp           = buddypress();
		$new_group_id = isset( $bp->groups->new_group_id )
			? $bp->groups->new_group_id
			: 0;

		/**
		 * Filters the new group ID.
		 *
		 * @since 1.1.0
		 *
		 * @param int $new_group_id ID of the new group.
		 */
		return (int) apply_filters( 'bp_get_new_group_id', $new_group_id );
	}

/**
 * Output the new group's name.
 *
 * @since 1.1.0
 */
function bp_new_group_name() {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_new_group_name();
}

	/**
	 * Get the new group's name.
	 *
	 * @since 1.1.0
	 *
	 * @return string The new group's name.
	 */
	function bp_get_new_group_name() {
		$bp   = buddypress();
		$name = isset( $bp->groups->current_group->name )
			? $bp->groups->current_group->name
			: '';

		/**
		 * Filters the new group name.
		 *
		 * @since 1.1.0
		 *
		 * @param string $name Name of the new group.
		 */
		return apply_filters( 'bp_get_new_group_name', $name );
	}

/**
 * Output the new group's description.
 *
 * @since 1.1.0
 */
function bp_new_group_description() {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_new_group_description();
}

	/**
	 * Get the new group's description.
	 *
	 * @since 1.1.0
	 *
	 * @return string The new group's description.
	 */
	function bp_get_new_group_description() {
		$bp          = buddypress();
		$description = isset( $bp->groups->current_group->description )
			? $bp->groups->current_group->description
			: '';

		/**
		 * Filters the new group description.
		 *
		 * @since 1.1.0
		 *
		 * @param string $name Description of the new group.
		 */
		return apply_filters( 'bp_get_new_group_description', $description );
	}

/**
 * Outputs 1 if the new group has a forum.
 *
 * @todo deprecate
 * @since 1.1.0
 */
function bp_new_group_enable_forum() {
	echo intval( bp_get_new_group_enable_forum() );
}

	/**
	 * Checks whether a new group has a forum or not.
	 *
	 * @todo deprecate
	 * @since 1.1.0
	 *
	 * @return int 1 if the new group has a forum. O otherwise.
	 */
	function bp_get_new_group_enable_forum() {
		$bp    = buddypress();
		$forum = isset( $bp->groups->current_group->enable_forum )
			? $bp->groups->current_group->enable_forum
			: false;

		/**
		 * Filters whether or not to enable forums for the new group.
		 *
		 * @since 1.1.0
		 *
		 * @param int $forum Whether or not to enable forums.
		 */
		return (int) apply_filters( 'bp_get_new_group_enable_forum', $forum );
	}

/**
 * Outputs the new group's status.
 *
 * @since 1.1.0
 */
function bp_new_group_status() {
	echo esc_html( bp_get_new_group_status() );
}

	/**
	 * Gets the new group's status.
	 *
	 * @since 1.1.0
	 *
	 * @return string The new group's status.
	 */
	function bp_get_new_group_status() {
		$bp     = buddypress();
		$status = isset( $bp->groups->current_group->status )
			? $bp->groups->current_group->status
			: 'public';

		/**
		 * Filters the new group status.
		 *
		 * @since 1.1.0
		 *
		 * @param string $status Status for the new group.
		 */
		return apply_filters( 'bp_get_new_group_status', $status );
	}

/**
 * Output the avatar for the group currently being created
 *
 * @since 1.1.0
 *
 * @see bp_core_fetch_avatar() For more information on accepted arguments
 *
 * @param array|string $args See bp_core_fetch_avatar().
 */
function bp_new_group_avatar( $args = '' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_new_group_avatar( $args );
}
	/**
	 * Return the avatar for the group currently being created
	 *
	 * @since 1.1.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and return values.
	 *
	 * @param array|string $args {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see {@link bp_core_fetch_avatar()}.
	 *
	 *     @type string   $alt     Default: 'Group photo'.
	 *     @type string   $class   Default: 'avatar'.
	 *     @type string   $type    Default: 'full'.
	 *     @type int|bool $width   Default: false.
	 *     @type int|bool $height  Default: false.
	 *     @type string   $id      Passed to $css_id parameter. Default: 'avatar-crop-preview'.
	 * }
	 * @return string       The avatar for the group being created
	 */
	function bp_get_new_group_avatar( $args = '' ) {

		// Parse arguments.
		$r = bp_parse_args(
			$args,
			array(
				'type'    => 'full',
				'width'   => false,
				'height'  => false,
				'class'   => 'avatar',
				'id'      => 'avatar-crop-preview',
				'alt'     => __( 'Group photo', 'buddypress' ),
			),
			'get_new_group_avatar'
		);

		// Merge parsed arguments with object specific data.
		$r = array_merge( $r, array(
			'item_id'    => bp_get_current_group_id(),
			'object'     => 'group',
			'avatar_dir' => 'group-avatars',
		) );

		// Get the avatar.
		$avatar = bp_core_fetch_avatar( $r );

		/**
		 * Filters the new group avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $avatar HTML markup for the new group avatar.
		 * @param array  $r      Array of parsed arguments for the group avatar.
		 * @param array  $args   Array of original arguments passed to the function.
		 */
		return apply_filters( 'bp_get_new_group_avatar', $avatar, $r, $args );
	}

/**
 * Escape & output the URL to the previous group creation step
 *
 * @since 1.1.0
 */
function bp_group_creation_previous_link() {
	echo esc_url( bp_get_group_creation_previous_link() );
}
	/**
	 * Return the URL to the previous group creation step
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_group_creation_previous_link() {
		$create_steps = buddypress()->groups->group_creation_steps;
		$steps        = array_keys( $create_steps );
		$url          = '';

		// Loop through steps.
		foreach ( $steps as $slug ) {

			// Break when the current step is found.
			if ( bp_is_action_variable( $slug ) ) {
				break;
			}

			// Add slug to previous steps.
			$previous_steps[] = $slug;
		}

		// Generate the URL for the previous step.
		$previous_step = array_pop( $previous_steps );

		if ( isset( $create_steps[ $previous_step ]['rewrite_id'], $create_steps[ $previous_step ]['default_slug'] ) ) {
			$url = bp_groups_get_create_url( array( $create_steps[ $previous_step ]['default_slug'] ) );
		}

		/**
		 * Filters the permalink for the previous step with the group creation process.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url Permalink for the previous step.
		 */
		return apply_filters( 'bp_get_group_creation_previous_link', $url );
	}

/**
 * Echoes the current group creation step.
 *
 * @since 1.6.0
 */
function bp_groups_current_create_step() {
	echo esc_html( bp_get_groups_current_create_step() );
}
	/**
	 * Returns the current group creation step. If none is found, returns an empty string.
	 *
	 * @since 1.6.0
	 *
	 *
	 * @return string $current_create_step
	 */
	function bp_get_groups_current_create_step() {
		$bp = buddypress();

		if ( !empty( $bp->groups->current_create_step ) ) {
			$current_create_step = $bp->groups->current_create_step;
		} else {
			$current_create_step = '';
		}

		/**
		 * Filters the current group creation step.
		 *
		 * If none is found, returns an empty string.
		 *
		 * @since 1.6.0
		 *
		 * @param string $current_create_step Current step in the group creation process.
		 */
		return apply_filters( 'bp_get_groups_current_create_step', $current_create_step );
	}

/**
 * Is the user looking at the last step in the group creation process.
 *
 * @since 1.1.0
 *
 * @param string $step Step to compare.
 * @return bool True if yes, False if no
 */
function bp_is_last_group_creation_step( $step = '' ) {

	// Use current step, if no step passed.
	if ( empty( $step ) ) {
		$step = bp_get_groups_current_create_step();
	}

	// Get the last step.
	$bp     = buddypress();
	$steps  = array_keys( $bp->groups->group_creation_steps );
	$l_step = array_pop( $steps );

	// Compare last step to step.
	$retval = ( $l_step === $step );

	/**
	 * Filters whether or not user is looking at last step in group creation process.
	 *
	 * @since 2.4.0
	 *
	 * @param bool   $retval Whether or not we are looking at last step.
	 * @param array  $steps  Array of steps from the group creation process.
	 * @param string $step   Step to compare.
	 */
	return (bool) apply_filters( 'bp_is_last_group_creation_step', $retval, $steps, $step );
}

/**
 * Is the user looking at the first step in the group creation process
 *
 * @since 1.1.0
 *
 * @param string $step Step to compare.
 * @return bool True if yes, False if no
 */
function bp_is_first_group_creation_step( $step = '' ) {

	// Use current step, if no step passed.
	if ( empty( $step ) ) {
		$step = bp_get_groups_current_create_step();
	}

	// Get the first step.
	$bp     = buddypress();
	$steps  = array_keys( $bp->groups->group_creation_steps );
	$f_step = array_shift( $steps );

	// Compare first step to step.
	$retval = ( $f_step === $step );

	/**
	 * Filters whether or not user is looking at first step in group creation process.
	 *
	 * @since 2.4.0
	 *
	 * @param bool   $retval Whether or not we are looking at first step.
	 * @param array  $steps  Array of steps from the group creation process.
	 * @param string $step   Step to compare.
	 */
	return (bool) apply_filters( 'bp_is_first_group_creation_step', $retval, $steps, $step );
}

/**
 * Output a list of friends who can be invited to a group
 *
 * @since 1.0.0
 *
 * @param array $args Array of arguments for friends list output.
 */
function bp_new_group_invite_friend_list( $args = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_new_group_invite_friend_list( $args );
}
	/**
	 * Return a list of friends who can be invited to a group
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of arguments for friends list output.
	 * @return false|string HTML list of checkboxes, or false
	 */
	function bp_get_new_group_invite_friend_list( $args = array() ) {

		// Bail if no friends component.
		if ( ! bp_is_active( 'friends' ) ) {
			return false;
		}

		// Parse arguments.
		$r = bp_parse_args(
			$args,
			array(
				'user_id'   => bp_loggedin_user_id(),
				'group_id'  => false,
				'before'    => '',
				'separator' => 'li',
				'after'     => '',
			),
			'group_invite_friend_list'
		);

		// No group passed, so look for new or current group ID's.
		if ( empty( $r['group_id'] ) ) {
			$bp            = buddypress();
			$r['group_id'] = ! empty( $bp->groups->new_group_id )
				? $bp->groups->new_group_id
				: $bp->groups->current_group->id;
		}

		// Setup empty items array.
		$items = array();

		// Build list markup parent elements.
		$before = '';
		if ( ! empty( $r['before'] ) ) {
			$before = $r['before'];
		}

		$after = '';
		if ( ! empty( $r['after'] ) ) {
			$after = $r['after'];
		}

		// Get user's friends who are not in this group already.
		$friends = friends_get_friends_invite_list( $r['user_id'], $r['group_id'] );

		if ( ! empty( $friends ) ) {

			// Get already invited users.
			$invites = groups_get_invites_for_group( $r['user_id'], $r['group_id'] );

			for ( $i = 0, $count = count( $friends ); $i < $count; ++$i ) {
				$checked = in_array( (int) $friends[ $i ]['id'], (array) $invites );
				$items[] = '<' . $r['separator'] . '><label for="f-' . esc_attr( $friends[ $i ]['id'] ) . '"><input' . checked( $checked, true, false ) . ' type="checkbox" name="friends[]" id="f-' . esc_attr( $friends[ $i ]['id'] ) . '" value="' . esc_attr( $friends[ $i ]['id'] ) . '" /> ' . esc_html( $friends[ $i ]['full_name'] ) . '</label></' . $r['separator'] . '>';
			}
		}

		/**
		 * Filters the array of friends who can be invited to a group.
		 *
		 * @since 2.4.0
		 *
		 * @param array $items Array of friends.
		 * @param array $r     Parsed arguments from bp_get_new_group_invite_friend_list()
		 * @param array $args  Unparsed arguments from bp_get_new_group_invite_friend_list()
		 */
		$invitable_friends = apply_filters( 'bp_get_new_group_invite_friend_list', $items, $r, $args );

		if ( ! empty( $invitable_friends ) && is_array( $invitable_friends ) ) {
			$retval = $before . implode( "\n", $invitable_friends ) . $after;
		} else {
			$retval = false;
		}

		return $retval;
	}

/**
 * Outputs a search form for the Groups directory.
 *
 * @since 1.0.0
 */
function bp_directory_groups_search_form() {

	$query_arg = bp_core_get_component_search_query_arg( 'groups' );

	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'groups' );
	}

	$search_form_html = '<form action="" method="get" id="search-groups-form">
		<label for="groups_search"><input type="text" name="' . esc_attr( $query_arg ) . '" id="groups_search" placeholder="'. esc_attr( $search_value ) .'" /></label>
		<input type="submit" id="groups_search_submit" name="groups_search_submit" value="'. esc_html__( 'Search', 'buddypress' ) .'" />
	</form>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters the HTML markup for the groups search form.
		 *
		 * @since 1.9.0
		 *
		 * @param string $search_form_html HTML markup for the search form.
		 */
		'bp_directory_groups_search_form',
		$search_form_html
	);
}

/**
 * Echo the current group type message.
 *
 * @since 2.7.0
 */
function bp_current_group_directory_type_message() {
	echo wp_kses( bp_get_current_group_directory_type_message(), array( 'strong' =>  true ) );
}
	/**
	 * Generate the current group type message.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
	function bp_get_current_group_directory_type_message() {
		$type_object = bp_groups_get_group_type_object( bp_get_current_group_directory_type() );

		/* translators: %s: group type singular name */
		$message = sprintf( __( 'Viewing groups of the type: %s', 'buddypress' ), '<strong>' . $type_object->labels['singular_name'] . '</strong>' );

		/**
		 * Filters the current group type message.
		 *
		 * @since 2.7.0
		 *
		 * @param string $message Message to filter.
		 */
		return apply_filters( 'bp_get_current_group_type_message', $message );
	}

/**
 * Is the current page a specific group admin screen?
 *
 * @since 1.1.0
 *
 * @param string $slug Admin screen slug.
 * @return bool
 */
function bp_is_group_admin_screen( $slug = '' ) {
	return (bool) ( bp_is_group_admin_page() && bp_is_action_variable( $slug ) );
}

/**
 * Echoes the current group admin tab slug.
 *
 * @since 1.6.0
 */
function bp_group_current_admin_tab() {
	echo esc_html( bp_get_group_current_admin_tab() );
}
	/**
	 * Returns the current group admin tab slug.
	 *
	 * @since 1.6.0
	 *
	 *
	 * @return string $tab The current tab's slug.
	 */
	function bp_get_group_current_admin_tab() {
		if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) ) {
			$tab = bp_action_variable( 0 );
		} else {
			$tab = '';
		}

		/**
		 * Filters the current group admin tab slug.
		 *
		 * @since 1.6.0
		 *
		 * @param string $tab Current group admin tab slug.
		 */
		return apply_filters( 'bp_get_current_group_admin_tab', $tab );
	}

/** Group Avatar Template Tags ************************************************/

/**
 * Outputs the current group avatar.
 *
 * @since 1.0.0
 *
 * @param string $type Thumb or full.
 */
function bp_group_current_avatar( $type = 'thumb' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_current_avatar( $type );
}
	/**
	 * Returns the current group avatar.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Thumb or full.
	 * @return string $tab The current tab's slug.
	 */
	function bp_get_group_current_avatar( $type = 'thumb' ) {

		$group_avatar = bp_core_fetch_avatar( array(
			'item_id'    => bp_get_current_group_id(),
			'object'     => 'group',
			'type'       => $type,
			'avatar_dir' => 'group-avatars',
			'alt'        => __( 'Group avatar', 'buddypress' ),
			'class'      => 'avatar'
		) );

		/**
		 * Filters the current group avatar.
		 *
		 * @since 2.0.0
		 *
		 * @param string $group_avatar HTML markup for current group avatar.
		 */
		return apply_filters( 'bp_get_group_current_avatar', $group_avatar );
	}

/**
 * Return whether a group has an avatar.
 *
 * @since 1.1.0
 * @since 10.0.0 Updated to use `bp_get_group_avatar`
 *
 * @param int|bool $group_id Group ID to check.
 * @return bool
 */
function bp_get_group_has_avatar( $group_id = false ) {

	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	$avatar_args = array(
		'no_grav' => true,
		'html'    => false,
		'type'    => 'thumb',
	);

	$group_avatar = bp_get_group_avatar( $avatar_args, $group_id );
	$avatar_args  = array_merge(
		$avatar_args,
		array(
			'item_id' => $group_id,
			'object'  => 'group',
		)
	);

	return ( bp_core_avatar_default( 'local', $avatar_args ) !== $group_avatar );
}

/**
 * Outputs the URL to delete a group avatar.
 *
 * @since 1.1.0
 */
function bp_group_avatar_delete_link() {
	echo esc_url( bp_get_group_avatar_delete_link() );
}

	/**
	 * Gets the URL to delete a group avatar.
	 *
	 * @since 1.1.0
	 *
	 * @return string The URL to delete a group avatar.
	 */
	function bp_get_group_avatar_delete_link() {
		$group = groups_get_current_group();
		$url   = wp_nonce_url(
			bp_get_group_manage_url(
				$group,
				bp_groups_get_path_chunks( array( 'group-avatar', 'delete' ), 'manage' )
			),
			'bp_group_avatar_delete'
		);

		/**
		 * Filters the URL to delete the group avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url URL to delete the group avatar.
		 */
		return apply_filters( 'bp_get_group_avatar_delete_link', $url );
	}

/**
 * Fires a hook to let 3rd party plugins add some html content to group's home page.
 *
 * @since 1.0.0
 */
function bp_custom_group_boxes() {
	do_action( 'groups_custom_group_boxes' );
}

/**
 * Fires a hook to let 3rd party plugins add custom group admin tabs.
 *
 * @todo deprecate.
 * @since 1.0.0
 */
function bp_custom_group_admin_tabs() {
	do_action( 'groups_custom_group_admin_tabs' );
}

/**
 * Fires a hook to let 3rd party plugins add custom group editable fields.
 *
 * @todo deprecate.
 * @since 1.0.0
 */
function bp_custom_group_fields_editable() {
	do_action( 'groups_custom_group_fields_editable' );
}

/**
 * Fires a hook to let 3rd party plugins add custom group fields.
 *
 * @todo deprecate.
 * @since 1.0.0
 */
function bp_custom_group_fields() {
	do_action( 'groups_custom_group_fields' );
}

/* Group Membership Requests *************************************************/

/**
 * Initialize a group membership request template loop.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *     @type int $group_id ID of the group. Defaults to current group.
 *     @type int $per_page Number of records to return per page. Default: 10.
 *     @type int $page     Page of results to return. Default: 1.
 *     @type int $max      Max number of items to return. Default: false.
 * }
 * @return bool True if there are requests, otherwise false.
 */
function bp_group_has_membership_requests( $args = '' ) {
	global $requests_template;

	$r = bp_parse_args(
		$args,
		array(
			'group_id' => bp_get_current_group_id(),
			'per_page' => 10,
			'page'     => 1,
			'max'      => false,
		),
		'group_has_membership_requests'
	);

	$requests_template = new BP_Groups_Membership_Requests_Template( $r );

	/**
	 * Filters whether or not a group membership query has requests to display.
	 *
	 * @since 1.1.0
	 *
	 * @param bool                                   $value             Whether there are requests to display.
	 * @param BP_Groups_Membership_Requests_Template $requests_template Object holding the requests query results.
	 */
	return apply_filters( 'bp_group_has_membership_requests', $requests_template->has_requests(), $requests_template );
}

/**
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_group_membership_requests() {
	global $requests_template;

	return $requests_template->requests();
}

/**
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_group_the_membership_request() {
	global $requests_template;

	return $requests_template->the_request();
}

/**
 * @since 1.0.0
 */
function bp_group_request_user_avatar_thumb() {
	global $requests_template;

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/**
		 * Filters the requesting user's avatar thumbnail.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value HTML markup for the user's avatar thumbnail.
		 */
		'bp_group_request_user_avatar_thumb',
		bp_core_fetch_avatar(
			array(
				'item_id' => $requests_template->request->user_id,
				'type'    => 'thumb',
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_core_get_user_displayname( $requests_template->request->user_id ) )
			)
		)
	);
}

/**
 * Outputs the URL to reject a group membership request.
 *
 * @since 1.0.0
 */
function bp_group_request_reject_link() {
	echo esc_url( bp_get_group_request_reject_link() );
}

	/**
	 * Gets the URL to reject a group membership request.
	 *
	 * @since 1.2.6
	 *
	 * @return string The URL to reject a group membership request.
	 */
	function bp_get_group_request_reject_link() {
		global $requests_template;

		$link = add_query_arg(
			array(
				'_wpnonce' => wp_create_nonce( 'groups_reject_membership_request' ),
				'user_id'  => $requests_template->request->user_id,
				'action'   => 'reject'
			),
			bp_get_group_manage_url(
				groups_get_current_group(),
				bp_groups_get_path_chunks( array( 'membership-requests' ), 'manage' )
			)
		);

		/**
		 * Filters the URL to use to reject a membership request.
		 *
		 * @since 1.2.6
		 *
		 * @param string $link URL to use to reject a membership request.
		 */
		return apply_filters( 'bp_get_group_request_reject_link', $link );
	}

/**
 * Outputs the URL to accept a group membership request.
 *
 * @since 1.0.0
 */
function bp_group_request_accept_link() {
	echo esc_url( bp_get_group_request_accept_link() );
}

	/**
	 * Gets the URL to reject a group membership request.
	 *
	 * @since 1.2.6
	 *
	 * @return string The URL to reject a group membership request.
	 */
	function bp_get_group_request_accept_link() {
		global $requests_template;

		$link = add_query_arg(
			array(
				'_wpnonce' => wp_create_nonce( 'groups_accept_membership_request' ),
				'user_id'  => $requests_template->request->user_id,
				'action'   => 'accept'
			),
			bp_get_group_manage_url(
				groups_get_current_group(),
				bp_groups_get_path_chunks( array( 'membership-requests' ), 'manage' )
			)
		);

		/**
		 * Filters the URL to use to accept a membership request.
		 *
		 * @since 1.2.6
		 *
		 * @param string $value URL to use to accept a membership request.
		 */
		return apply_filters( 'bp_get_group_request_accept_link', $link );
	}

/**
 * Outputs the link to reach the requesting user's profile page.
 *
 * @since 1.0.0
 */
function bp_group_request_user_link() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_request_user_link();
}

	/**
	 * Gets the link to reach the requesting user's profile page.
	 *
	 * @since 1.2.6
	 *
	 * @return string HTML output.
	 */
	function bp_get_group_request_user_link() {
		global $requests_template;

		/**
		 * Filters the URL for the user requesting membership.
		 *
		 * @since 1.2.6
		 *
		 * @param string $value URL for the user requestion membership.
		 */
		return apply_filters( 'bp_get_group_request_user_link', bp_core_get_userlink( $requests_template->request->user_id ) );
	}

/**
 * Outputs the elapsed time since the group membership request was made.
 *
 * @since 1.0.0
 */
function bp_group_request_time_since_requested() {
	global $requests_template;

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo esc_html(
		/**
		 * Filters the formatted time since membership was requested.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Formatted time since membership was requested.
		 */
		apply_filters(
			'bp_group_request_time_since_requested',
			/* translators: %s: human time diff */
			sprintf( __( 'requested %s', 'buddypress' ), bp_core_time_since( $requests_template->request->date_modified ) )
		)
	);
}

/**
 * Outputs the comment a member sent with their membership request.
 *
 * @since 1.0.0
 */
function bp_group_request_comment() {
	global $requests_template;

	/**
	 * Filters the membership request comment left by user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Membership request comment left by user.
	 */
	echo esc_html( apply_filters( 'bp_group_request_comment', stripslashes( $requests_template->request->comments ) ) );
}

/**
 * Output pagination links for group membership requests.
 *
 * @since 2.0.0
 */
function bp_group_requests_pagination_links() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_requests_pagination_links();
}
	/**
	 * Get pagination links for group membership requests.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	function bp_get_group_requests_pagination_links() {
		global $requests_template;

		/**
		 * Filters pagination links for group membership requests.
		 *
		 * @since 2.0.0
		 *
		 * @param string $value Pagination links for group membership requests.
		 */
		return apply_filters( 'bp_get_group_requests_pagination_links', $requests_template->pag_links );
	}

/**
 * Output pagination count text for group membership requests.
 *
 * @since 2.0.0
 */
function bp_group_requests_pagination_count() {
	echo esc_html( bp_get_group_requests_pagination_count() );
}
	/**
	 * Get pagination count text for group membership requests.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	function bp_get_group_requests_pagination_count() {
		global $requests_template;

		$start_num = intval( ( $requests_template->pag_page - 1 ) * $requests_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $requests_template->pag_num - 1 ) > $requests_template->total_request_count ) ? $requests_template->total_request_count : $start_num + ( $requests_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $requests_template->total_request_count );

		if ( 1 == $requests_template->total_request_count ) {
			$message = __( 'Viewing 1 request', 'buddypress' );
		} else {
			/* translators: 1: group request from number. 2: group request to number. 3: total group requests. */
			$message = sprintf( _n( 'Viewing %1$s - %2$s of %3$s request', 'Viewing %1$s - %2$s of %3$s requests', $requests_template->total_request_count, 'buddypress' ), $from_num, $to_num, $total );
		}

		/**
		 * Filters pagination count text for group membership requests.
		 *
		 * @since 2.0.0
		 *
		 * @param string $message  Pagination count text for group membership requests.
		 * @param string $from_num Total amount for the low value in the range.
		 * @param string $to_num   Total amount for the high value in the range.
		 * @param string $total    Total amount of members found.
		 */
		return apply_filters( 'bp_get_group_requests_pagination_count', $message, $from_num, $to_num, $total );
	}

/** Group Invitations *********************************************************/

/**
 * Whether or not there are invites.
 *
 * @since 1.1.0
 *
 * @param string $args
 * @return bool|mixed|void
 */
function bp_group_has_invites( $args = '' ) {
	global $invites_template, $group_id;

	$r = bp_parse_args(
		$args,
		array(
			'group_id' => false,
			'user_id'  => bp_loggedin_user_id(),
			'per_page' => false,
			'page'     => 1,
		),
		'group_has_invites'
	);

	if ( empty( $r['group_id'] ) ) {
		if ( groups_get_current_group() ) {
			$r['group_id'] = bp_get_current_group_id();
		} elseif ( isset( buddypress()->groups->new_group_id ) && buddypress()->groups->new_group_id ) {
			$r['group_id'] = buddypress()->groups->new_group_id;
		}
	}

	// Set the global (for use in BP_Groups_Invite_Template::the_invite()).
	if ( empty( $group_id ) ) {
		$group_id = $r['group_id'];
	}

	if ( ! $group_id ) {
		return false;
	}

	$invites_template = new BP_Groups_Invite_Template( $r );

	/**
	 * Filters whether or not a group invites query has invites to display.
	 *
	 * @since 1.1.0
	 *
	 * @param bool                      $value            Whether there are requests to display.
	 * @param BP_Groups_Invite_Template $invites_template Object holding the invites query results.
	 */
	return apply_filters( 'bp_group_has_invites', $invites_template->has_invites(), $invites_template );
}

/**
 * @since 1.1.0
 *
 * @return mixed
 */
function bp_group_invites() {
	global $invites_template;

	return $invites_template->invites();
}

/**
 * @since 1.1.0
 *
 * @return mixed
 */
function bp_group_the_invite() {
	global $invites_template;

	return $invites_template->the_invite();
}

/**
 * @since 1.1.0
 */
function bp_group_invite_item_id() {
	echo esc_attr( bp_get_group_invite_item_id() );
}

	/**
	 * @since 1.1.0
	 *
	 * @return mixed|void
	 */
	function bp_get_group_invite_item_id() {
		global $invites_template;

		/**
		 * Filters the group invite item ID.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Group invite item ID.
		 */
		return apply_filters( 'bp_get_group_invite_item_id', 'uid-' . $invites_template->invite->user->id );
	}

/**
 * @since 1.1.0
 */
function bp_group_invite_user_avatar() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_invite_user_avatar();
}

	/**
	 * @since 1.1.0
	 *
	 * @return mixed|void
	 */
	function bp_get_group_invite_user_avatar() {
		global $invites_template;

		/**
		 * Filters the group invite user avatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Group invite user avatar.
		 */
		return apply_filters( 'bp_get_group_invite_user_avatar', $invites_template->invite->user->avatar_thumb );
	}

/**
 * @since 1.1.0
 */
function bp_group_invite_user_link() {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_invite_user_link();
}

	/**
	 * @since 1.1.0
	 *
	 * @return mixed|void
	 */
	function bp_get_group_invite_user_link() {
		global $invites_template;

		/**
		 * Filters the group invite user link.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Group invite user link.
		 */
		return apply_filters( 'bp_get_group_invite_user_link', bp_core_get_userlink( $invites_template->invite->user->id ) );
	}

/**
 * @since 1.1.0
 */
function bp_group_invite_user_last_active() {
	echo esc_html( bp_get_group_invite_user_last_active() );
}

	/**
	 * @since 1.1.0
	 *
	 * @return mixed|void
	 */
	function bp_get_group_invite_user_last_active() {
		global $invites_template;

		/**
		 * Filters the group invite user's last active time.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Group invite user's last active time.
		 */
		return apply_filters( 'bp_get_group_invite_user_last_active', $invites_template->invite->user->last_active );
	}

/**
 * @since 1.1.0
 */
function bp_group_invite_user_remove_invite_url() {
	echo esc_url( bp_get_group_invite_user_remove_invite_url() );
}

	/**
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_group_invite_user_remove_invite_url() {
		global $invites_template;

		$user_id = intval( $invites_template->invite->user->id );

		if ( bp_is_current_action( 'create' ) ) {
			$uninvite_url = add_query_arg(
				'user_id',
				$user_id,
				bp_get_groups_directory_url( bp_groups_get_path_chunks( array( 'group-invites' ), 'create' ) )
			);
		} else {
			$uninvite_url = bp_get_group_url(
				groups_get_current_group(),
				bp_groups_get_path_chunks( array( 'send-invites', 'remove', $user_id ) )
			);
		}

		return wp_nonce_url( $uninvite_url, 'groups_invite_uninvite_user' );
	}

/**
 * Output pagination links for group invitations.
 *
 * @since 2.0.0
 */
function bp_group_invite_pagination_links() {
	// Escaping is done in WordPress's `paginate_links()` function.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_group_invite_pagination_links();
}

	/**
	 * Get pagination links for group invitations.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	function bp_get_group_invite_pagination_links() {
		global $invites_template;

		/**
		 * Filters the pagination links for group invitations.
		 *
		 * @since 2.0.0
		 *
		 * @param string $value Pagination links for group invitations.
		 */
		return apply_filters( 'bp_get_group_invite_pagination_links', $invites_template->pag_links );
	}

/**
 * Output pagination count text for group invitations.
 *
 * @since 2.0.0
 */
function bp_group_invite_pagination_count() {
	echo esc_html( bp_get_group_invite_pagination_count() );
}
	/**
	 * Get pagination count text for group invitations.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	function bp_get_group_invite_pagination_count() {
		global $invites_template;

		$start_num = intval( ( $invites_template->pag_page - 1 ) * $invites_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $invites_template->pag_num - 1 ) > $invites_template->total_invite_count ) ? $invites_template->total_invite_count : $start_num + ( $invites_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $invites_template->total_invite_count );

		if ( 1 == $invites_template->total_invite_count ) {
			$message = __( 'Viewing 1 invitation', 'buddypress' );
		} else {
			/* translators: 1: Invitations from number. 2: Invitations to number. 3: Total invitations. */
			$message = sprintf( _nx( 'Viewing %1$s - %2$s of %3$s invitation', 'Viewing %1$s - %2$s of %3$s invitations', $invites_template->total_invite_count, 'Group invites pagination', 'buddypress' ), $from_num, $to_num, $total );
		}

		/** This filter is documented in bp-groups/bp-groups-template.php */
		return apply_filters( 'bp_get_groups_pagination_count', $message, $from_num, $to_num, $total );
	}

/** Group RSS *****************************************************************/

/**
 * Hook group activity feed to <head>.
 *
 * @since 1.5.0
 */
function bp_groups_activity_feed() {

	// Bail if not viewing a single group or activity is not active.
	if ( ! bp_is_active( 'groups' ) || ! bp_is_active( 'activity' ) || ! bp_is_group() ) {
		return;
	}
	?>
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php echo esc_attr( bp_get_current_group_name() ); ?> | <?php esc_html_e( 'Group Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_group_activity_feed_link(); ?>" />
	<?php
}
add_action( 'bp_head', 'bp_groups_activity_feed' );

/**
 * Output the current group activity-stream RSS URL.
 *
 * @since 1.5.0
 */
function bp_group_activity_feed_link() {
	echo esc_url( bp_get_group_activity_feed_link() );
}
	/**
	 * Return the current group activity-stream RSS URL.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	function bp_get_group_activity_feed_link() {
		$feed_link = bp_get_group_url(
			groups_get_current_group(),
			bp_groups_get_path_chunks( array( 'feed' ) )
		);

		/**
		 * Filters the current group activity-stream RSS URL.
		 *
		 * @since 1.2.0
		 *
		 * @param string $feed_link Current group activity-stream RSS URL.
		 */
		return apply_filters( 'bp_get_group_activity_feed_link', $feed_link );
	}

/** Current Group *************************************************************/

/**
 * Echoes the output of bp_get_current_group_id().
 *
 * @since 1.5.0
 */
function bp_current_group_id() {
	echo intval( bp_get_current_group_id() );
}
	/**
	 * Returns the ID of the current group.
	 *
	 * @since 1.5.0
	 *
	 * @return int $current_group_id The id of the current group, if there is one.
	 */
	function bp_get_current_group_id() {
		$current_group    = groups_get_current_group();
		$current_group_id = isset( $current_group->id ) ? (int) $current_group->id : 0;

		/**
		 * Filters the ID of the current group.
		 *
		 * @since 1.5.0
		 *
		 * @param int    $current_group_id ID of the current group.
		 * @param object $current_group    Instance holding the current group.
		 */
		return apply_filters( 'bp_get_current_group_id', $current_group_id, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_slug().
 *
 * @since 1.5.0
 */
function bp_current_group_slug() {
	echo esc_attr( bp_get_current_group_slug() );
}
	/**
	 * Returns the slug of the current group.
	 *
	 * @since 1.5.0
	 *
	 * @return string $current_group_slug The slug of the current group, if there is one.
	 */
	function bp_get_current_group_slug() {
		$current_group      = groups_get_current_group();
		$current_group_slug = isset( $current_group->slug ) ? $current_group->slug : '';

		/**
		 * Filters the slug of the current group.
		 *
		 * @since 1.5.0
		 *
		 * @param string $current_group_slug Slug of the current group.
		 * @param object $current_group      Instance holding the current group.
		 */
		return apply_filters( 'bp_get_current_group_slug', $current_group_slug, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_name().
 *
 * @since 1.5.0
 */
function bp_current_group_name() {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_current_group_name();
}
	/**
	 * Returns the name of the current group.
	 *
	 * @since 1.5.0
	 *
	 * @return string The name of the current group, if there is one.
	 */
	function bp_get_current_group_name() {
		$current_group = groups_get_current_group();
		$current_name  = bp_get_group_name( $current_group );

		/**
		 * Filters the name of the current group.
		 *
		 * @since 1.2.0
		 *
		 * @param string $current_name  Name of the current group.
		 * @param object $current_group Instance holding the current group.
		 */
		return apply_filters( 'bp_get_current_group_name', $current_name, $current_group );
	}

/**
 * Echoes the output of bp_get_current_group_description().
 *
 * @since 2.1.0
 */
function bp_current_group_description() {
	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_current_group_description();
}
	/**
	 * Returns the description of the current group.
	 *
	 * @since 2.1.0
	 *                       this output.
	 *
	 * @return string The description of the current group, if there is one.
	 */
	function bp_get_current_group_description() {
		$current_group      = groups_get_current_group();
		$current_group_desc = isset( $current_group->description ) ? $current_group->description : '';

		/**
		 * Filters the description of the current group.
		 *
		 * This filter is used to apply extra filters related to formatting.
		 *
		 * @since 1.0.0
		 *
		 * @param string $current_group_desc Description of the current group.
		 */
		$desc = apply_filters( 'bp_get_group_description', $current_group_desc );

		/**
		 * Filters the description of the current group.
		 *
		 * @since 2.1.0
		 *
		 * @param string $desc Description of the current group.
		 */
		return apply_filters( 'bp_get_current_group_description', $desc );
	}

/**
 * Output a URL for a group component action.
 *
 * @since 1.2.0
 *
 * @param string $action
 * @param string $query_args
 * @param bool $nonce
 * @return string|null
 */
function bp_groups_action_link( $action = '', $query_args = '', $nonce = false ) {
	echo esc_url( bp_get_groups_action_link( $action, $query_args, $nonce ) );
}
	/**
	 * Get a URL for a group component action.
	 *
	 * @since 1.2.0
	 *
	 * @param string $action
	 * @param string $query_args
	 * @param bool $nonce
	 * @return string
	 */
	function bp_get_groups_action_link( $action = '', $query_args = '', $nonce = false ) {

		$current_group = groups_get_current_group();
		$url           = '';

		// Must be a group.
		if ( ! empty( $current_group->id ) ) {

			// Append $action to $url if provided
			if ( ! empty( $action ) ) {
				$url = bp_get_group_url(
					$current_group,
					bp_groups_get_path_chunks( array( $action ) )
				);
			} else {
				$url = bp_get_group_url( $current_group );
			}

			// Add a slash at the end of our user url.
			$url = trailingslashit( $url );

			// Add possible query args.
			if ( !empty( $query_args ) && is_array( $query_args ) ) {
				$url = add_query_arg( $query_args, $url );
			}

			// To nonce, or not to nonce...
			if ( true === $nonce ) {
				$url = wp_nonce_url( $url );
			} elseif ( is_string( $nonce ) ) {
				$url = wp_nonce_url( $url, $nonce );
			}
		}

		/**
		 * Filters a URL for a group component action.
		 *
		 * @since 2.1.0
		 *
		 * @param string $url        URL for a group component action.
		 * @param string $action     Action being taken for the group.
		 * @param string $query_args Query arguments being passed.
		 * @param bool   $nonce      Whether or not to add a nonce.
		 */
		return apply_filters( 'bp_get_groups_action_link', $url, $action, $query_args, $nonce );
	}

/** Stats **********************************************************************/

/**
 * Display the number of groups in user's profile.
 *
 * @since 2.0.0
 *
 * @param array|string $args before|after|user_id
 *
 */
function bp_groups_profile_stats( $args = '' ) {
	echo wp_kses(
		bp_groups_get_profile_stats( $args ),
		array(
			'li'     => array( 'class' => true ),
			'div'    => array( 'class' => true ),
			'strong' => true,
			'a'      => array( 'href' => true ),
		)
	);
}
add_action( 'bp_members_admin_user_stats', 'bp_groups_profile_stats', 8, 1 );

/**
 * Return the number of groups in user's profile.
 *
 * @since 2.0.0
 *
 * @param array|string $args before|after|user_id
 * @return string HTML for stats output.
 */
function bp_groups_get_profile_stats( $args = '' ) {

	// Parse the args
	$r = bp_parse_args(
		$args,
		array(
			'before'  => '<li class="bp-groups-profile-stats">',
			'after'   => '</li>',
			'user_id' => bp_displayed_user_id(),
			'groups'  => 0,
			'output'  => '',
		),
		'groups_get_profile_stats'
	);

	// Allow completely overloaded output
	if ( empty( $r['output'] ) ) {

		// Only proceed if a user ID was passed
		if ( ! empty( $r['user_id'] ) ) {

			// Get the user groups
			if ( empty( $r['groups'] ) ) {
				$r['groups'] = absint( bp_get_total_group_count_for_user( $r['user_id'] ) );
			}

			// If groups exist, show some formatted output
			$r['output'] = $r['before'];

			/* translators: %s: number of groups */
			$r['output'] .= sprintf( _n( '%s group', '%s groups', $r['groups'], 'buddypress' ), '<strong>' . $r['groups'] . '</strong>' );
			$r['output'] .= $r['after'];
		}
	}

	/**
	 * Filters the number of groups in user's profile.
	 *
	 * @since 2.0.0
	 *
	 * @param string $value HTML for stats output.
	 * @param array  $r     Array of parsed arguments for query.
	 */
	return apply_filters( 'bp_groups_get_profile_stats', $r['output'], $r );
}

/**
 * Check if the active template pack includes the Group Membership management UI templates.
 *
 * @since 5.0.0
 *
 * @return boolean True if the active template pack includes the Group Membership management UI templates.
 *                 False otherwise.
 */
function bp_groups_has_manage_group_members_templates() {
	return file_exists( bp_locate_template( 'common/js-templates/group-members/index.php' ) );
}

/**
 * Prints the JS Templates to manage the Group's members.
 *
 * @since 10.0.0
 */
function bp_groups_print_manage_group_members_templates() {
	bp_get_template_part( 'common/js-templates/group-members/index' );
}

/**
 * Prints the HTML placeholders to manage the Group's members.
 *
 * @since 10.0.0
 */
function bp_groups_print_manage_group_members_placeholders() {
	?>
	<div id="group-manage-members-ui" class="standard-form">
		<ul class="subnav-filters">
			<li id="group-roles-filter" class="last filter"><?php // Placeholder for the Group Role Tabs ?></li>
			<li id="group-members-pagination" class="left-menu"><?php // Placeholder for paginate links ?></li>
			<li id="group-members-search-form" class="bp-search"><?php // Placeholder for search form ?></li>
		</ul>
		<table id="group-members-list-table" class="<?php echo is_admin() ? 'widefat bp-group-members' : 'bp-list'; ?>"><?php // Placeholder to list members ?></table>
	</div>
	<?php
}

/**
 * Outputs the Manage Group Members Backbone UI.
 *
 * @since 10.0.0
 *
 * @param string $hook The hook to use to inject the JS Templates.
 */
function bp_groups_manage_group_members_interface( $hook = 'wp_footer' ) {
	/**
	 * Get the templates to manage Group Members using the BP REST API.
	 *
	 * @since 5.0.0
	 * @since 10.0.0 Hook to the `wp_footer` action to print the JS templates.
	 */
	add_action( $hook, 'bp_groups_print_manage_group_members_templates' );
	bp_groups_print_manage_group_members_placeholders();

	/**
	 * Private hook to preserve backward compatibility with plugins needing the above placeholders to be located
	 * into: `bp-templates/bp-nouveau/buddypress/common/js-templates/group-members/index.php`.
	 *
	 * @since 10.0.0
	 */
	do_action( '_bp_groups_print_manage_group_members_placeholders' );
}
