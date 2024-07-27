<?php
/**
 * Common functions
 *
 * @since 3.0.0
 * @version 14.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This function looks scarier than it actually is. :)
 * Each object loop (activity/members/groups/blogs/forums) contains default
 * parameters to show specific information based on the page we are currently
 * looking at.
 *
 * The following function will take into account any cookies set in the JS and
 * allow us to override the parameters sent. That way we can change the results
 * returned without reloading the page.
 *
 * By using cookies we can also make sure that user settings are retained
 * across page loads.
 *
 * @since 3.0.0
 *
 * @param string $query_string Query string for the current request.
 * @param string $object       Object for cookie.
 *
 * @return string Query string for the component loops
 */
function bp_nouveau_ajax_querystring( $query_string, $object ) {
	if ( empty( $object ) ) {
		return '';
	}

	// Default query
	$post_query = array(
		'filter'       => '',
		'scope'        => 'all',
		'page'         => 1,
		'search_terms' => '',
		'extras'       => '',
	);

	if ( ! empty( $_POST ) ) {
		$post_query = bp_parse_args(
			$_POST,
			$post_query,
			'nouveau_ajax_querystring'
		);

		// Make sure to transport the scope, filter etc.. in HeartBeat Requests
		if ( ! empty( $post_query['data']['bp_heartbeat'] ) ) {
			$bp_heartbeat = $post_query['data']['bp_heartbeat'];

			// Remove heartbeat specific vars
			$post_query = array_diff_key(
				bp_parse_args(
					$bp_heartbeat,
					$post_query,
					'nouveau_ajax_querystring_heartbeat'
				),
				array(
					'data'      => false,
					'interval'  => false,
					'_nonce'    => false,
					'action'    => false,
					'screen_id' => false,
					'has_focus' => false,
				)
			);
		}
	}

	// Init the query string
	$qs = array();

	// Activity stream filtering on action.
	if ( ! empty( $post_query['filter'] ) && '-1' !== $post_query['filter'] ) {
		if ( 'notifications' === $object ) {
			$qs[] = 'component_action=' . $post_query['filter'];
		} else {
			$qs[] = 'type=' . $post_query['filter'];
			$qs[] = 'action=' . $post_query['filter'];
		}
	}

	// Sort the notifications if needed
	if ( ! empty( $post_query['extras'] ) && 'notifications' === $object ) {
		$qs[] = 'sort_order=' . $post_query['extras'];
	}

	if ( 'personal' === $post_query['scope'] ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
		$qs[]    = 'user_id=' . $user_id;
	}

	// Activity stream scope only on activity directory.
	if ( 'all' !== $post_query['scope'] && ! bp_displayed_user_id() && ! bp_is_single_item() ) {
		$qs[] = 'scope=' . $post_query['scope'];
	}

	// If page have been passed via the AJAX post request, use those.
	if ( '-1' != $post_query['page'] ) {
		$qs[] = 'page=' . absint( $post_query['page'] );
	}

	// Excludes activity just posted and avoids duplicate ids.
	if ( ! empty( $post_query['exclude_just_posted'] ) ) {
		$just_posted = wp_parse_id_list( $post_query['exclude_just_posted'] );
		$qs[]        = 'exclude=' . implode( ',', $just_posted );
	}

	// To get newest activities.
	if ( ! empty( $post_query['offset'] ) ) {
		$qs[] = 'offset=' . intval( $post_query['offset'] );
	}

	if ( ! empty( $post_query['offset_lower'] ) ) {
		$qs[] = 'offset_lower=' . intval( $post_query['offset_lower'] );
	}

	$object_search_text = bp_get_search_default_text( $object );
	if ( ! empty( $post_query['search_terms'] ) && $object_search_text != $post_query['search_terms'] && 'false' != $post_query['search_terms'] && 'undefined' != $post_query['search_terms'] ) {
		$qs[] = 'search_terms=' . urlencode( $_POST['search_terms'] );
	}

	// Specific to messages
	if ( 'messages' === $object ) {
		if ( ! empty( $post_query['box'] ) ) {
			$qs[] = 'box=' . $post_query['box'];
		}
	}

	// Single activity.
	if ( bp_is_single_activity() && 'activity' === $object ) {
		$qs = array(
			'display_comments=threaded',
			'show_hidden=true',
			'include=' . bp_current_action(),
		);
	}

	// Now pass the querystring to override default values.
	$query_string = empty( $qs ) ? '' : join( '&', (array) $qs );

	// List the variables for the filter
	list( $filter, $scope, $page, $search_terms, $extras ) = array_values( $post_query );

	/**
	 * Filters the AJAX query string for the component loops.
	 *
	 * @since 3.0.0
	 *
	 * @param string $query_string The query string we are working with.
	 * @param string $object       The type of page we are on.
	 * @param string $filter       The current object filter.
	 * @param string $scope        The current object scope.
	 * @param string $page         The current object page.
	 * @param string $search_terms The current object search terms.
	 * @param string $extras       The current object extras.
	 */
	return apply_filters( 'bp_nouveau_ajax_querystring', $query_string, $object, $filter, $scope, $page, $search_terms, $extras );
}

/**
 * @since 3.0.0
 *
 * @return string
 */
function bp_nouveau_ajax_button( $output = '', $button = null, $before = '', $after = '', $r = array() ) {
	if ( empty( $button->component ) ) {
		return $output;
	}

	// Custom data attribute.
	$r['button_attr']['data-bp-btn-action'] = $button->id;

	$reset_ids = array(
		'member_friendship' => true,
		'group_membership'  => true,
	);

	if ( ! empty( $reset_ids[ $button->id ] ) )  {
		$parse_class = array_map( 'sanitize_html_class', explode( ' ', $r['button_attr']['class'] ) );
		if ( false === $parse_class ) {
			return $output;
		}

		$find_id = array_intersect( $parse_class, array(
			'pending_friend',
			'is_friend',
			'not_friends',
			'leave-group',
			'join-group',
			'accept-invite',
			'membership-requested',
			'request-membership',
		) );

		if ( 1 !== count( $find_id ) ) {
			return $output;
		}

		$data_attribute = reset( $find_id );
		if ( 'pending_friend' === $data_attribute ) {
			$data_attribute = str_replace( '_friend', '', $data_attribute );
		} elseif ( 'group_membership' === $button->id ) {
			$data_attribute = str_replace( '-', '_', $data_attribute );
		}

		$r['button_attr']['data-bp-btn-action'] = $data_attribute;
	}

	// Re-render the button with our custom data attribute.
	$output = new BP_Core_HTML_Element( array(
		'element'    => $r['button_element'],
		'attr'       => $r['button_attr'],
		'inner_html' => ! empty( $r['link_text'] ) ? $r['link_text'] : ''
	) );
	$output = $output->contents();

	// Add span bp-screen-reader-text class
	return $before . $output . $after;
}

/**
 * Output HTML content into a wrapper.
 *
 * @since 3.0.0
 *
 * @param array $args {
 *     Optional arguments.
 *
 *     @type string $container         String HTML container type that should wrap
 *                                     the items as a group: 'div', 'ul', or 'p'. Required.
 *     @type string $container_id      The group wrapping container element ID
 *     @type string $container_classes The group wrapping container elements class
 *     @type string $output            The HTML to output. Required.
 * }
 */
function bp_nouveau_wrapper( $args = array() ) {
	/**
	 * Classes need to be determined & set by component to a certain degree.
	 *
	 * Check the component to find a default container_class based on the component ID to add.
	 * We need to to this because bp_current_component() is using the component slugs which can differ
	 * from the component ID.
	 */
	$current_component_id = bp_core_get_active_components( array( 'id' => bp_current_component() ) );
	if ( $current_component_id && 1 === count( $current_component_id ) ) {
		$current_component_id = reset( $current_component_id );
	} else {
		$current_component_id = bp_current_component();
	}

	$current_component_class = $current_component_id . '-meta';

	if ( bp_is_group_activity() ) {
		$generic_class = ' activity-meta ';
	} else {
		$generic_class = '';
	}

	$r = bp_parse_args(
		$args,
		array(
			'container'         => 'div',
			'container_id'      => '',
			'container_classes' => array( $generic_class, $current_component_class ),
			'output'            => '',
		),
		'nouveau_wrapper'
	);

	$valid_containers = array(
		'div'  => true,
		'ul'   => true,
		'ol'   => true,
		'span' => true,
		'p'    => true,
	);

	// Actually merge some classes defaults and $args
	// @todo This is temp, we need certain classes but maybe improve this approach.
	$default_classes        = array( 'action' );
	$r['container_classes'] = array_merge( $r['container_classes'], $default_classes );

	if ( empty( $r['container'] ) || ! isset( $valid_containers[ $r['container'] ] ) || empty( $r['output'] ) ) {
		return;
	}

	$container         = $r['container'];
	$container_id      = '';
	$container_classes = '';
	$output            = $r['output'];

	if ( ! empty( $r['container_id'] ) ) {
		$container_id = ' id="' . esc_attr( $r['container_id'] ) . '"';
	}

	if ( ! empty( $r['container_classes'] ) && is_array( $r['container_classes'] ) ) {
		$container_classes = ' class="' . join( ' ', array_map( 'sanitize_html_class', $r['container_classes'] ) ) . '"';
	}

	// phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<%1$s%2$s%3$s>%4$s</%1$s>', $container, $container_id, $container_classes, $output );
}

/**
 * Register the 2 sidebars for the Group & User default front page
 *
 * @since 3.0.0
 */
function bp_nouveau_register_sidebars() {
	$default_fronts      = bp_nouveau_get_appearance_settings();
	$default_user_front  = 0;
	$default_group_front = 0;
	$is_active_groups    = bp_is_active( 'groups' );

	if ( isset( $default_fronts['user_front_page'] ) ) {
		$default_user_front = $default_fronts['user_front_page'];
	}

	if ( $is_active_groups ) {
		if ( isset( $default_fronts['group_front_page'] ) ) {
			$default_group_front = $default_fronts['group_front_page'];
		}
	}

	// Setting the front template happens too early, so we need this!
	if ( is_customize_preview() ) {
		$default_user_front = bp_nouveau_get_temporary_setting( 'user_front_page', $default_user_front );

		if ( $is_active_groups ) {
			$default_group_front = bp_nouveau_get_temporary_setting( 'group_front_page', $default_group_front );
		}
	}

	$sidebars = array();
	if ( $default_user_front ) {
		$sidebars[] = array(
			'name'          => __( 'BuddyPress Member\'s Home', 'buddypress' ),
			'id'            => 'sidebar-buddypress-members',
			'description'   => __( 'Add widgets here to appear in the front page of each member of your community.', 'buddypress' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		);
	}

	if ( $default_group_front ) {
		$sidebars[] = array(
			'name'          => __( 'BuddyPress Group\'s Home', 'buddypress' ),
			'id'            => 'sidebar-buddypress-groups',
			'description'   => __( 'Add widgets here to appear in the front page of each group of your community.', 'buddypress' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		);
	}

	if ( empty( $sidebars ) ) {
		return;
	}

	// Register the sidebars if needed.
	foreach ( $sidebars as $sidebar ) {
		register_sidebar( $sidebar );
	}
}

/**
 * @since 3.0.0
 *
 * @return bool
 */
function bp_nouveau_is_object_nav_in_sidebar() {
	return bp_is_widget_block_active( 'bp/primary-nav', 'bp_nouveau_sidebar_object_nav_widget' );
}

/**
 * @since 3.0.0
 *
 * @return bool
 */
function bp_nouveau_current_user_can( $capability = '' ) {
	/**
	 * Filters whether or not the current user can perform an action for BuddyPress Nouveau.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $value      Whether or not the user is logged in.
	 * @param string $capability Current capability being checked.
	 * @param int    $value      Current logged in user ID.
	 */
	return apply_filters( 'bp_nouveau_current_user_can', is_user_logged_in(), $capability, bp_loggedin_user_id() );
}

/**
 * Parse an html output to a list of component's directory nav item.
 *
 * @since 3.0.0
 *
 * @param string $hook      The hook to fire.
 * @param string $component The component nav belongs to.
 * @param int    $position  The position of the nav item.
 *
 * @return array A list of component's dir nav items
 */
function bp_nouveau_parse_hooked_dir_nav( $hook = '', $component = '', $position = 99 ) {
	$extra_nav_items = array();

	if ( empty( $hook ) || empty( $component ) || ! has_action( $hook ) ) {
		return $extra_nav_items;
	}

	// Get the hook output.
	ob_start();

	/**
	 * Fires at the start of the output for `bp_nouveau_parse_hooked_dir_nav()`.
	 *
	 * This hook is variable and depends on the hook parameter passed in.
	 *
	 * @since 3.0.0
	 */
	do_action( $hook );
	$output = ob_get_clean();

	if ( empty( $output ) ) {
		return $extra_nav_items;
	}

	preg_match_all( "/<li\sid=\"{$component}\-(.*)\"[^>]*>/siU", $output, $lis );
	if ( empty( $lis[1] ) ) {
		return $extra_nav_items;
	}

	$extra_nav_items = array_fill_keys( $lis[1], array( 'component' => $component, 'position' => $position ) );
	preg_match_all( '/<a\s[^>]*>(.*)<\/a>/siU', $output, $as );

	if ( ! empty( $as[0] ) ) {
		foreach ( $as[0] as $ka => $a ) {
			$extra_nav_items[ $lis[1][ $ka ] ]['slug'] = $lis[1][ $ka ];
			$extra_nav_items[ $lis[1][ $ka ] ]['text'] = $as[1][ $ka ];
			preg_match_all( '/([\w\-]+)=([^"\'> ]+|([\'"]?)(?:[^\3]|\3+)+?\3)/', $a, $attrs );

			if ( ! empty( $attrs[1] ) ) {
				foreach ( $attrs[1] as $katt => $att ) {
					if ( 'href' === $att ) {
						$extra_nav_items[ $lis[1][ $ka ] ]['link'] = trim( $attrs[2][ $katt ], '"' );
					} else {
						$extra_nav_items[ $lis[1][ $ka ] ][ $att ] = trim( $attrs[2][ $katt ], '"' );
					}
				}
			}
		}
	}

	if ( ! empty( $as[1] ) ) {
		foreach ( $as[1] as $ks => $s ) {
			preg_match_all( '/<span>(.*)<\/span>/siU', $s, $spans );

			if ( empty( $spans[0] ) ) {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = false;
			} elseif ( ! empty( $spans[1][0] ) ) {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = (int) $spans[1][0];
			} else {
				$extra_nav_items[ $lis[1][ $ks ] ]['count'] = '';
			}
		}
	}

	return $extra_nav_items;
}

/**
 * Run specific "select filter" hooks to catch the options and build an array out of them
 *
 * @since 3.0.0
 *
 * @param string $hook
 * @param array  $filters
 *
 * @return array
 */
function bp_nouveau_parse_hooked_options( $hook = '', $filters = array() ) {
	if ( empty( $hook ) ) {
		return $filters;
	}

	ob_start();

	/**
	 * Fires at the start of the output for `bp_nouveau_parse_hooked_options()`.
	 *
	 * This hook is variable and depends on the hook parameter passed in.
	 *
	 * @since 3.0.0
	 */
	do_action( $hook );

	$output = ob_get_clean();

	preg_match_all( '/<option value="(.*?)"\s*>(.*?)<\/option>/', $output, $matches );

	if ( ! empty( $matches[1] ) && ! empty( $matches[2] ) ) {
		foreach ( $matches[1] as $ik => $key_action ) {
			if ( ! empty( $matches[2][ $ik ] ) && ! isset( $filters[ $key_action ] ) ) {
				$filters[ $key_action ] = $matches[2][ $ik ];
			}
		}
	}

	return $filters;
}

/**
 * Get Dropdown filters for the current component of the one passed in params.
 *
 * @since 3.0.0
 *
 * @param string $context   'directory', 'user' or 'group'.
 * @param string $component The BuddyPress component ID.
 *
 * @return array the dropdown filters.
 */
function bp_nouveau_get_component_filters( $context = '', $component = '' ) {
	$filters = array();

	if ( empty( $context ) ) {
		if ( bp_is_user() ) {
			$context = 'user';
		} elseif ( bp_is_group() ) {
			$context = 'group';

		// Defaults to directory
		} else {
			$context = 'directory';
		}
	}

	if ( empty( $component ) ) {
		if ( 'user' === $context ) {
			$component = bp_core_get_active_components( array( 'slug' => bp_current_component() ) );
			$component = reset( $component );

			if ( 'friends' === $component ) {
				$context   = 'friends';
				$component = 'members';
			}
		} elseif ( 'group' === $context && bp_is_group_activity() ) {
			$component = 'activity';
		} elseif ( 'group' === $context && bp_is_group_members() ) {
			$component = 'members';
		} else {
			$component = bp_current_component();
		}
	}

	if ( ! bp_is_active( $component ) ) {
		return $filters;
	}

	if ( 'members' === $component ) {
		$filters = bp_nouveau_get_members_filters( $context );
	} elseif ( 'activity' === $component ) {
		$filters = bp_nouveau_get_activity_filters();

		// Specific case for the activity dropdown
		$filters = array_merge( array( '-1' => __( '&mdash; Everything &mdash;', 'buddypress' ) ), $filters );
	} elseif ( 'groups' === $component ) {
		$filters = bp_nouveau_get_groups_filters( $context );
	} elseif ( 'blogs' === $component ) {
		$filters = bp_nouveau_get_blogs_filters( $context );
	}

	return $filters;
}

/**
 * When previewing make sure to get the temporary setting of the customizer.
 * This is necessary when we need to get these very early.
 *
 * @since 3.0.0
 *
 * @param string $option the index of the setting to get.
 * @param mixed  $retval the value to use as default.
 *
 * @return mixed The value for the requested option.
 */
function bp_nouveau_get_temporary_setting( $option = '', $retval = false ) {
	if ( empty( $option ) || ! isset( $_POST['customized'] ) ) {
		return $retval;
	}

	$temporary_setting = wp_unslash( $_POST['customized'] );
	if ( ! is_array( $temporary_setting ) ) {
		$temporary_setting = json_decode( $temporary_setting, true );
	}

	// This is used to transport the customizer settings into Ajax requests.
	if ( 'any' === $option ) {
		$retval = array();

		foreach ( $temporary_setting as $key => $setting ) {
			if ( 0 !== strpos( $key, 'bp_nouveau_appearance' ) ) {
				continue;
			}

			$k            = str_replace( array( '[', ']' ), array( '_', '' ), $key );
			$retval[ $k ] = $setting;
		}

	// Used when it's an early regular request
	} elseif ( isset( $temporary_setting[ 'bp_nouveau_appearance[' . $option . ']' ] ) ) {
		$retval = $temporary_setting[ 'bp_nouveau_appearance[' . $option . ']' ];

	// Used when it's an ajax request
	} elseif ( isset( $_POST['customized'][ 'bp_nouveau_appearance_' . $option ] ) ) {
		$retval = $_POST['customized'][ 'bp_nouveau_appearance_' . $option ];
	}

	return $retval;
}

/**
 * Get the BP Nouveau Appearance settings.
 *
 * @since 3.0.0
 *
 * @param string $option Leave empty to get all settings, specify a value for a specific one.
 * @param mixed          An array of settings, the value of the requested setting.
 *
 * @return array|false|mixed
 */
function bp_nouveau_get_appearance_settings( $option = '' ) {
	$default_args = array(
		'global_alignment'   => 'alignwide',
		'user_front_page'    => 0,
		'user_front_bio'     => 0,
		'user_nav_display'   => 0, // O is default (horizontally). 1 is vertically.
		'user_nav_tabs'      => 0,
		'user_subnav_tabs'   => 0,
		'user_nav_order'     => array(),
		'members_layout'     => 1,
		'members_dir_tabs'   => 0,
		'members_dir_layout' => 0,
	);

	if ( bp_is_active( 'friends' ) ) {
		$default_args['members_friends_layout'] = 1;
	}

	if ( bp_is_active( 'activity' ) ) {
		$default_args['activity_dir_layout'] = 0;
		$default_args['activity_dir_tabs']   = 0; // default = no tabs
	}

	if ( bp_is_active( 'groups' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'group_front_page'        => 0,
				'group_front_boxes'       => 0,
				'group_front_description' => 0,
				'group_nav_display'       => 0, // O is default (horizontally). 1 is vertically.
				'group_nav_order'         => array(),
				'group_nav_tabs'          => 0,
				'group_subnav_tabs'       => 0,
				'groups_create_tabs'      => 1,
				'groups_layout'           => 1,
				'members_group_layout'    => 1,
				'groups_dir_layout'       => 0,
				'groups_dir_tabs'         => 0,
			)
		);
	}

	if ( is_multisite() && bp_is_active( 'blogs' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'sites_dir_layout' => 0,
				'sites_dir_tabs'   => 0,
			)
		);
	}

	$settings = bp_parse_args(
		bp_get_option( 'bp_nouveau_appearance', array() ),
		$default_args,
		'nouveau_appearance_settings'
	);

	// Override some settings to better suits block themes.
	if ( bp_nouveau()->is_block_theme ) {
		$settings['global_alignment'] = 'alignnone';

		if ( isset( $settings['groups_create_tabs'] ) ) {
			$settings['groups_create_tabs'] = 0;
		}
	}

	if ( ! empty( $option ) ) {
		if ( isset( $settings[ $option ] ) ) {
			return $settings[ $option ];
		} else {
			return false;
		}
	}

	return $settings;
}

/**
 * Returns the choices for the Layout option of the customizer
 * or the list of corresponding css classes.
 *
 * @since 3.0.0
 *
 * @param string $type 'option' to get the labels, 'classes' to get the classes
 *
 * @return array The list of labels or classes preserving keys.
 */
function bp_nouveau_customizer_grid_choices( $type = 'option' ) {
	$columns = array(
		array( 'key' => '1', 'label' => __( 'One column', 'buddypress'    ), 'class' => ''      ),
		array( 'key' => '2', 'label' => __( 'Two columns', 'buddypress'   ), 'class' => 'two'   ),
		array( 'key' => '3', 'label' => __( 'Three columns', 'buddypress' ), 'class' => 'three' ),
		array( 'key' => '4', 'label' => __( 'Four columns', 'buddypress'  ), 'class' => 'four'  ),
	);

	if ( 'option' === $type ) {
		return wp_list_pluck( $columns, 'label', 'key' );
	}

	return wp_list_pluck( $columns, 'class', 'key' );
}

/**
 * Sanitize a list of slugs to save it as an array
 *
 * @since 3.0.0
 *
 * @param  string $option A comma separated list of nav items slugs.
 *
 * @return array An array of nav items slugs.
 */
function bp_nouveau_sanitize_nav_order( $option = '' ) {
	if ( ! is_array( $option ) ) {
		$option = explode( ',', $option );
	}

	return array_map( 'sanitize_key', $option );
}

/**
 * BP Nouveau's callback for the cover image feature.
 *
 * @since 3.0.0
 *
 * @param array $params Optional. The current component's feature parameters.
 *
 * @return string
 */
function bp_nouveau_theme_cover_image( $params = array() ) {
	if ( empty( $params ) ) {
		return '';
	}

	// Avatar height - padding - 1/2 avatar height.
	$avatar_offset = $params['height'] - 5 - round( (int) bp_core_avatar_full_height() / 2 );

	// Header content offset + spacing.
	$top_offset  = bp_core_avatar_full_height() - 10;
	$left_offset = bp_core_avatar_full_width() + 20;

	if ( ! bp_is_active( 'activity' ) || ! bp_activity_do_mentions() ) {
		$top_offset -= 40;
	}

	$cover_image = isset( $params['cover_image'] ) ? 'background-image: url( ' . $params['cover_image'] . ' );' : '';
	$hide_avatar_style = '';

	// Adjust the cover image header, in case avatars are completely disabled.
	if ( ! buddypress()->avatar->show_avatars ) {
		$hide_avatar_style = '
			#buddypress #item-header-cover-image #item-header-avatar {
				display:  none;
			}
		';

		if ( bp_is_user() ) {
			$hide_avatar_style = '
				#buddypress #item-header-cover-image #item-header-avatar a {
					display: block;
					height: ' . $top_offset . 'px;
					margin: 0 15px 19px 0;
				}

				#buddypress div#item-header #item-header-cover-image #item-header-content {
					margin-left:auto;
				}
			';
		}
	}

	return '
		/* Cover image */
		#buddypress #item-header-cover-image {
			min-height: ' . $params['height'] . 'px;
			margin-bottom: 1em;
		}

		#buddypress #item-header-cover-image:after {
			clear: both;
			content: "";
			display: table;
		}

		#buddypress #header-cover-image {
			height: ' . $params['height'] . 'px;
			' . $cover_image . '
		}

		#buddypress #create-group-form #header-cover-image {
			position: relative;
			margin: 1em 0;
		}

		.bp-user #buddypress #item-header {
			padding-top: 0;
		}

		#buddypress #item-header-cover-image #item-header-avatar {
			margin-top: ' . $avatar_offset . 'px;
			float: left;
			overflow: visible;
			width:auto;
		}

		#buddypress div#item-header #item-header-cover-image #item-header-content {
			clear: both;
			float: left;
			margin-left: ' . $left_offset . 'px;
			margin-top: -' . $top_offset . 'px;
			width:auto;
		}

		body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
		body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
			margin-top: ' . $params['height'] . 'px;
			margin-left: 0;
			clear: none;
			max-width: 50%;
		}

		body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
			padding-top: 20px;
			max-width: 20%;
		}

		' . $hide_avatar_style . '

		#buddypress div#item-header-cover-image h2 a,
		#buddypress div#item-header-cover-image h2 {
			color: #FFF;
			text-rendering: optimizelegibility;
			text-shadow: 0px 0px 3px rgba( 0, 0, 0, 0.8 );
			margin: 0 0 .6em;
			font-size:200%;
		}

		#buddypress #item-header-cover-image #item-header-avatar img.avatar {
			border: solid 2px #FFF;
			background: rgba( 255, 255, 255, 0.8 );
		}

		#buddypress #item-header-cover-image #item-header-avatar a {
			border: none;
			text-decoration: none;
		}

		#buddypress #item-header-cover-image #item-buttons {
			margin: 0 0 10px;
			padding: 0 0 5px;
		}

		#buddypress #item-header-cover-image #item-buttons:after {
			clear: both;
			content: "";
			display: table;
		}

		@media screen and (max-width: 782px) {
			#buddypress #item-header-cover-image #item-header-avatar,
			.bp-user #buddypress #item-header #item-header-cover-image #item-header-avatar,
			#buddypress div#item-header #item-header-cover-image #item-header-content {
				width:100%;
				text-align:center;
			}

			#buddypress #item-header-cover-image #item-header-avatar a {
				display:inline-block;
			}

			#buddypress #item-header-cover-image #item-header-avatar img {
				margin:0;
			}

			#buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				margin:0;
			}

			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				max-width: 100%;
			}

			#buddypress div#item-header-cover-image h2 a,
			#buddypress div#item-header-cover-image h2 {
				color: inherit;
				text-shadow: none;
				margin:25px 0 0;
				font-size:200%;
			}

			#buddypress #item-header-cover-image #item-buttons div {
				float:none;
				display:inline-block;
			}

			#buddypress #item-header-cover-image #item-buttons:before {
				content:"";
			}

			#buddypress #item-header-cover-image #item-buttons {
				margin: 5px 0;
			}
		}
	';
}

/**
 * All user feedback messages are available here
 *
 * @since 3.0.0
 * @since 8.0.0 Adds the 'member-invites-none' feedback.
 *
 * @param string $feedback_id The ID of the message.
 *
 * @return string|false The list of parameters for the message
 */
function bp_nouveau_get_user_feedback( $feedback_id = '' ) {
	/**
	 * Filters the BuddyPress Nouveau feedback messages.
	 *
	 * Use this filter to add your custom feedback messages.
	 *
	 * @since 3.0.0
	 * @since 8.0.0 Adds the 'member-invites-none' feedback.
	 *
	 * @param array $value The list of feedback messages.
	 */
	$feedback_messages = apply_filters(
		'bp_nouveau_feedback_messages',
		array(
			'registration-disabled'             => array(
				'type'    => 'info',
				'message' => __( 'Member registration is currently not allowed.', 'buddypress' ),
				'before'  => 'bp_before_registration_disabled',
				'after'   => 'bp_after_registration_disabled'
			),
			'request-details'                   => array(
				'type'    => 'info',
				'message' => __( 'Registering for this site is easy. Just fill in the fields below, and we\'ll get a new account set up for you in no time.', 'buddypress' ),
				'before'  => false,
				'after'   => false,
			),
			'completed-confirmation'            => array(
				'type'    => 'info',
				'message' => __( 'You have successfully created your account! Please log in using the username and password you have just created.', 'buddypress' ),
				'before'  => 'bp_before_registration_confirmed',
				'after'   => 'bp_after_registration_confirmed',
			),
			'directory-activity-loading'        => array(
				'type'    => 'loading',
				'message' => __( 'Loading the community updates. Please wait.', 'buddypress' ),
			),
			'single-activity-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading the update. Please wait.', 'buddypress' ),
			),
			'activity-loop-none'                => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there was no activity found. Please try a different filter.', 'buddypress' ),
			),
			'blogs-loop-none'                   => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there were no sites found.', 'buddypress' ),
			),
			'blogs-no-signup'                   => array(
				'type'    => 'info',
				'message' => __( 'Site registration is currently disabled.', 'buddypress' ),
			),
			'directory-blogs-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading the sites of the network. Please wait.', 'buddypress' ),
			),
			'directory-groups-loading'          => array(
				'type'    => 'loading',
				'message' => __( 'Loading the groups of the community. Please wait.', 'buddypress' ),
			),
			'groups-loop-none'                  => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there were no groups found.', 'buddypress' ),
			),
			'group-activity-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading the group updates. Please wait.', 'buddypress' ),
			),
			'group-members-loading'             => array(
				'type'    => 'loading',
				'message' => __( 'Requesting the group members. Please wait.', 'buddypress' ),
			),
			'group-members-none'                => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there were no group members found.', 'buddypress' ),
			),
			'group-members-search-none'         => array(
				'type'    => 'info',
				'message' => __( 'Sorry, there was no member of that name found in this group.', 'buddypress' ),
			),
			'group-manage-members-none'         => array(
				'type'    => 'info',
				'message' => __( 'This group has no members.', 'buddypress' ),
			),
			'group-requests-none'               => array(
				'type'    => 'info',
				'message' => __( 'There are no pending membership requests.', 'buddypress' ),
			),
			'group-requests-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading the members who requested to join the group. Please wait.', 'buddypress' ),
			),
			'group-delete-warning'              => array(
				'type'    => 'warning',
				'message' => __( 'WARNING: Deleting this group will completely remove ALL content associated with it. There is no way back. Please be careful with this option.', 'buddypress' ),
			),
			'group-avatar-delete-info'          => array(
				'type'    => 'info',
				'message' => __( 'If you\'d like to remove the existing group profile photo but not upload a new one, please use the delete group profile photo button.', 'buddypress' ),
			),
			'directory-members-loading'         => array(
				'type'    => 'loading',
				'message' => __( 'Loading the members of your community. Please wait.', 'buddypress' ),
			),
			'members-loop-none'                 => array(
				'type'    => 'info',
				'message' => __( 'Sorry, no members were found.', 'buddypress' ),
			),
			'member-requests-none'              => array(
				'type'    => 'info',
				'message' => __( 'You have no pending friendship requests.', 'buddypress' ),
			),
			'member-invites-none'               => array(
				'type'    => 'info',
				'message' => __( 'You have no outstanding group invites.', 'buddypress' ),
			),
			'member-notifications-none'         => array(
				'type'    => 'info',
				'message' => __( 'This member has no notifications.', 'buddypress' ),
			),
			'member-wp-profile-none'            => array(
				'type'    => 'info',
				/* translators: %s: member name */
				'message' => __( '%s did not save any profile information yet.', 'buddypress' ),
			),
			'member-delete-account'             => array(
				'type'    => 'warning',
				'message' => __( 'Deleting this account will delete all of the content it has created. It will be completely unrecoverable.', 'buddypress' ),
			),
			'member-activity-loading'           => array(
				'type'    => 'loading',
				'message' => __( 'Loading the member\'s updates. Please wait.', 'buddypress' ),
			),
			'member-blogs-loading'              => array(
				'type'    => 'loading',
				'message' => __( 'Loading the member\'s blogs. Please wait.', 'buddypress' ),
			),
			'member-friends-loading'            => array(
				'type'    => 'loading',
				'message' => __( 'Loading the member\'s friends. Please wait.', 'buddypress' ),
			),
			'member-groups-loading'             => array(
				'type'    => 'loading',
				'message' => __( 'Loading the member\'s groups. Please wait.', 'buddypress' ),
			),
			'member-notifications-loading'      => array(
				'type'    => 'loading',
				'message' => __( 'Loading notifications. Please wait.', 'buddypress' ),
			),
			'member-group-invites-all'          => array(
				'type'    => 'info',
				'message' => __( 'Currently every member of the community can invite you to join their groups. If you are not comfortable with it, you can always restrict group invites to your friends only.', 'buddypress' ),
			),
			'member-group-invites-friends-only' => array(
				'type'    => 'info',
				'message' => __( 'Currently only your friends can invite you to groups. Uncheck the box to allow any member to send invites.', 'buddypress' ),
			),
			'member-invitations-help'           => array(
				'type'    => 'info',
				'message' => __( 'Fill out the form below to invite a new user to join this site. Upon submission of the form, an email will be sent to the invitee containing a link to accept your invitation. You may also add a custom message to the email.', 'buddypress' ),
			),
			'member-invitations-none'           => array(
				'type'    => 'info',
				'message' => __( 'There are no invitations to display.', 'buddypress' ),
			),
			'member-invitations-not-allowed'    => array(
				'type'    => 'error',
				/**
				 * Use this filter to edit the restricted feedback message displayed into the Send invitation form.
				 *
				 * @since 8.0.0
				 *
				 * @param string $value The restricted feedback message displayed into the Send invitation form.
				 */
				'message' => apply_filters(
					'members_invitations_form_access_restricted',
					__( 'Sorry, you are not allowed to send invitations.', 'buddypress' )
				),
			),
		)
	);

	if ( ! isset( $feedback_messages[ $feedback_id ] ) ) {
		return false;
	}

	/*
	 * Adjust some messages to the context.
	 */
	if ( 'completed-confirmation' === $feedback_id && bp_get_membership_requests_required() ) {
		$feedback_messages['completed-confirmation']['message'] = __( 'You have successfully submitted your membership request! Our site moderators will review your submission and send you an activation email if your request is approved.', 'buddypress' );
	} elseif ( 'completed-confirmation' === $feedback_id && bp_registration_needs_activation() ) {
		$feedback_messages['completed-confirmation']['message'] = __( 'You have successfully created your account! To begin using this site you will need to activate your account via the email we have just sent to your address.', 'buddypress' );
	} elseif ( 'member-notifications-none' === $feedback_id ) {
		$is_myprofile = bp_is_my_profile();

		if ( bp_is_current_action( 'unread' ) ) {
			$feedback_messages['member-notifications-none']['message'] = __( 'This member has no unread notifications.', 'buddypress' );

			if ( $is_myprofile ) {
				$feedback_messages['member-notifications-none']['message'] = __( 'You have no unread notifications.', 'buddypress' );
			}
		} elseif ( $is_myprofile ) {
			$feedback_messages['member-notifications-none']['message'] = __( 'You have no notifications.', 'buddypress' );
		}
	} elseif ( 'member-wp-profile-none' === $feedback_id && bp_is_user_profile() ) {
		$feedback_messages['member-wp-profile-none']['message'] = sprintf( $feedback_messages['member-wp-profile-none']['message'], bp_get_displayed_user_fullname() );
	} elseif ( 'member-delete-account' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-delete-account']['message'] = __( 'Deleting your account will delete all of the content you have created. It will be completely irrecoverable.', 'buddypress' );
	} elseif ( 'member-activity-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-activity-loading']['message'] = __( 'Loading your updates. Please wait.', 'buddypress' );
	} elseif ( 'member-blogs-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-blogs-loading']['message'] = __( 'Loading your blogs. Please wait.', 'buddypress' );
	} elseif ( 'member-friends-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-friends-loading']['message'] = __( 'Loading your friends. Please wait.', 'buddypress' );
	} elseif ( 'member-groups-loading' === $feedback_id && bp_is_my_profile() ) {
		$feedback_messages['member-groups-loading']['message'] = __( 'Loading your groups. Please wait.', 'buddypress' );
	}

	/**
	 * Filter here if you wish to edit the message just before being displayed
	 *
	 * @since 3.0.0
	 *
	 * @param array $feedback_messages
	 */
	return apply_filters( 'bp_nouveau_get_user_feedback', $feedback_messages[ $feedback_id ] );
}

/**
 * Get the signup fields for the requested section
 *
 * @since 3.0.0
 *
 * @param string $section Optional. The section of fields to get 'account_details' or 'blog_details'.
 *
 * @return array|false The list of signup fields for the requested section. False if not found.
 */
function bp_nouveau_get_signup_fields( $section = '' ) {
	if ( empty( $section ) ) {
		return false;
	}

	/**
	 * Filter to add your specific 'text' or 'password' inputs
	 *
	 * If you need to use other types of field, please use the
	 * do_action( 'bp_account_details_fields' ) or do_action( 'blog_details' ) hooks instead.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value The list of fields organized into sections.
	 */
	$fields = apply_filters( 'bp_nouveau_get_signup_fields', array(
		'account_details' => array(
			'signup_username' => array(
				'label'          => __( 'Username', 'buddypress' ),
				'required'       => true,
				'value'          => 'bp_get_signup_username_value',
				'attribute_type' => 'username',
				'type'           => 'text',
				'class'          => '',
			),
			'signup_email' => array(
				'label'          => __( 'Email Address', 'buddypress' ),
				'required'       => true,
				'value'          => 'bp_get_signup_email_value',
				'attribute_type' => 'email',
				'type'           => 'email',
				'class'          => '',
			),
			'signup_password' => array(),
			'signup_password_confirm' => array(),
		),
		'blog_details' => array(
			'signup_blog_url' => array(
				'label'          => __( 'Site URL', 'buddypress' ),
				'required'       => true,
				'value'          => 'bp_get_signup_blog_url_value',
				'attribute_type' => 'slug',
				'type'           => 'text',
				'class'          => '',
			),
			'signup_blog_title' => array(
				'label'          => __( 'Site Title', 'buddypress' ),
				'required'       => true,
				'value'          => 'bp_get_signup_blog_title_value',
				'attribute_type' => 'title',
				'type'           => 'text',
				'class'          => '',
			),
			'signup_blog_privacy_public' => array(
				'label'          => __( 'Yes', 'buddypress' ),
				'required'       => false,
				'value'          => 'public',
				'attribute_type' => '',
				'type'           => 'radio',
				'class'          => '',
			),
			'signup_blog_privacy_private' => array(
				'label'          => __( 'No', 'buddypress' ),
				'required'       => false,
				'value'          => 'private',
				'attribute_type' => '',
				'type'           => 'radio',
				'class'          => '',
			),
		),
	) );

	if ( ! bp_get_blog_signup_allowed() ) {
		unset( $fields['blog_details'] );
	}

	if ( isset( $fields[ $section ] ) ) {
		return $fields[ $section ];
	}

	return false;
}

/**
 * Get Some submit buttons data.
 *
 * @since 3.0.0
 * @since 8.0.0 Adds the 'member-send-invite' button.
 *
 * @param string $action The action requested.
 *
 * @return array|false The list of the submit button parameters for the requested action
 *                     False if no actions were found.
 */
function bp_nouveau_get_submit_button( $action = '' ) {
	if ( empty( $action ) ) {
		return false;
	}

	/**
	 * Filter the Submit buttons to add your own.
	 *
	 * @since 3.0.0
	 * @since 8.0.0 Adds the 'member-send-invite' button.
	 *
	 * @param array $value The list of submit buttons.
	 *
	 * @return array|false
	 */
	$actions = apply_filters(
		'bp_nouveau_get_submit_button',
		array(
			'register'                      => array(
				'before'     => 'bp_before_registration_submit_buttons',
				'after'      => 'bp_after_registration_submit_buttons',
				'nonce'      => 'bp_new_signup',
				'attributes' => array(
					'name'  => 'signup_submit',
					'id'    => 'submit',
					'value' => __( 'Complete Sign Up', 'buddypress' ),
				),
			),
			'member-profile-edit'           => array(
				'before'     => '',
				'after'      => '',
				'nonce'      => 'bp_xprofile_edit',
				'attributes' => array(
					'name'  => 'profile-group-edit-submit',
					'id'    => 'profile-group-edit-submit',
					'value' => __( 'Save Changes', 'buddypress' ),
				),
			),
			'member-capabilities'           => array(
				'before'     => 'bp_members_capabilities_account_before_submit',
				'after'      => 'bp_members_capabilities_account_after_submit',
				'nonce'      => 'capabilities',
				'attributes' => array(
					'name'  => 'capabilities-submit',
					'id'    => 'capabilities-submit',
					'value' => __( 'Save', 'buddypress' ),
				),
			),
			'member-delete-account'         => array(
				'before'     => 'bp_members_delete_account_before_submit',
				'after'      => 'bp_members_delete_account_after_submit',
				'nonce'      => 'delete-account',
				'attributes' => array(
					'disabled' => 'disabled',
					'name'     => 'delete-account-button',
					'id'       => 'delete-account-button',
					'value'    => __( 'Delete Account', 'buddypress' ),
				),
			),
			'members-general-settings'      => array(
				'before'     => 'bp_core_general_settings_before_submit',
				'after'      => 'bp_core_general_settings_after_submit',
				'nonce'      => 'bp_settings_general',
				'attributes' => array(
					'name'  => 'submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddypress' ),
					'class' => 'auto',
				),
			),
			'member-notifications-settings' => array(
				'before'     => 'bp_members_notification_settings_before_submit',
				'after'      => 'bp_members_notification_settings_after_submit',
				'nonce'      => 'bp_settings_notifications',
				'attributes' => array(
					'name'  => 'submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddypress' ),
					'class' => 'auto',
				),
			),
			'members-profile-settings'      => array(
				'before'     => 'bp_core_xprofile_settings_before_submit',
				'after'      => 'bp_core_xprofile_settings_after_submit',
				'nonce'      => 'bp_xprofile_settings',
				'attributes' => array(
					'name'  => 'xprofile-settings-submit',
					'id'    => 'submit',
					'value' => __( 'Save Changes', 'buddypress' ),
					'class' => 'auto',
				),
			),
			'member-group-invites'          => array(
				'nonce'      => 'bp_nouveau_group_invites_settings',
				'attributes' => array(
					'name'  => 'member-group-invites-submit',
					'id'    => 'submit',
					'value' => __( 'Save', 'buddypress' ),
					'class' => 'auto',
				),
			),
			'member-send-invite'            => array(
				'nonce'                   => 'bp_members_invitation_send_%d',
				'nonce_placeholder_value' => bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id(),
				'attributes'              => array(
					'name'  => 'member-send-invite-submit',
					'id'    => 'submit',
					'value' => __( 'Send', 'buddypress' ),
					'class' => 'auto',
				),
			),
			'activity-new-comment'          => array(
				'after'      => 'bp_activity_entry_comments',
				'nonce'      => 'new_activity_comment',
				'nonce_key'  => '_wpnonce_new_activity_comment',
				'wrapper'    => false,
				'attributes' => array(
					'name'  => 'ac_form_submit',
					'value' => _x( 'Post', 'button', 'buddypress' ),
				),
			),
		)
	);

	if ( isset( $actions[ $action ] ) ) {
		return $actions[ $action ];
	}

	return false;
}

/**
 * Reorder a BuddyPress item nav according to a given list of nav item slugs
 *
 * @since 3.0.0
 *
 * @param object $nav         The BuddyPress Item Nav object to reorder
 * @param array  $order       A list of slugs ordered (eg: array( 'profile', 'activity', etc..) )
 * @param string $parent_slug A parent slug if it's a secondary nav we are reordering (case of the Groups single item)
 *
 * @return bool False otherwise.
 */
function bp_nouveau_set_nav_item_order( $nav = null, $order = array(), $parent_slug = '' ) {
	if ( ! is_object( $nav ) || empty( $order ) || ! is_array( $order ) ) {
		return false;
	}

	$position = 0;

	foreach ( $order as $slug ) {
		$position += 10;

		$key = $slug;
		if ( ! empty( $parent_slug ) ) {
			$key = $parent_slug . '/' . $key;
		}

		$item_nav = $nav->get( $key );

		if ( ! $item_nav ) {
			continue;
		}

		if ( (int) $item_nav->position !== (int) $position ) {
			$nav->edit_nav( array( 'position' => $position ), $slug, $parent_slug );
		}
	}

	return true;
}

/**
 * Gets the component's slug thanks to its ID.
 *
 * @since 8.0.0
 *
 * @param string $component_id The component ID.
 * @return string The slug for the requested component ID.
 */
function bp_nouveau_get_component_slug( $component_id = '' ) {
	$slug = '';

	if ( bp_is_active( $component_id ) ) {
		switch ( $component_id ) {
			case 'activity':
				$slug = bp_get_activity_slug();
				break;
			case 'blogs':
				$slug = bp_get_blogs_slug();
				break;
			case 'friends':
				$slug = bp_get_friends_slug();
				break;
			case 'groups':
				$slug = bp_get_groups_slug();
				break;
			case 'messages':
				$slug = bp_get_messages_slug();
				break;
			case 'notifications':
				$slug = bp_get_notifications_slug();
				break;
			case 'settings':
				$slug = bp_get_settings_slug();
				break;
			case 'xprofile':
				$slug = bp_get_profile_slug();
				break;
		}
	}

	// Defaults to the component ID.
	if ( ! $slug ) {
		$slug = $component_id;
	}

	/**
	 * Filter here to edit the slug for the requested component ID.
	 *
	 * @since 8.0.0
	 *
	 * @param string $slug         The slug for the requested component ID.
	 * @param string $component_id The component ID.
	 */
	return apply_filters( 'bp_nouveau_get_component_slug', $slug, $component_id );
}

/**
 * Registers the 'bp/primary-nav' Widget Block.
 *
 * @since 9.0.0
 * @since 12.0.0 Use the WP Blocks API v2.
 *
 * @param array $blocks The Core Blocks list.
 * @return array The Core Blocks list.
 */
function bp_nouveau_register_primary_nav_widget_block( $blocks = array() ) {
	$blocks['bp/primary-nav'] = array(
		'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-core/blocks/primary-nav',
		'render_callback' => 'bp_nouveau_render_primary_nav_block',
	);

	return $blocks;
}
add_filter( 'bp_core_register_blocks', 'bp_nouveau_register_primary_nav_widget_block', 20, 1 );

/**
 * Registers the 'bp/primary-nav' Widget Block classnames.
 *
 * @since 9.0.0
 *
 * @param array $block_globals The list of global properties for Core blocks.
 * @return array               The list of global properties for Core blocks.
 */
function bp_nouveau_register_core_block_globals( $block_globals = array() ) {
	$block_globals['bp/primary-nav'] = array(
		'widget_classnames' => array( 'widget_nav_menu', 'buddypress_object_nav', 'buddypress' ),
	);

	return $block_globals;
}
add_filter( 'bp_core_block_globals', 'bp_nouveau_register_core_block_globals', 10, 1 );

/**
 * Unregister the 'bp/primary-nav' Block from the post context.
 *
 * @since 9.0.0
 */
function bp_nouveau_unregister_blocks_for_post_context() {
	$is_registered = WP_Block_Type_Registry::get_instance()->is_registered( 'bp/primary-nav' );

	if ( $is_registered ) {
		unregister_block_type( 'bp/primary-nav' );
	}
}
add_action( 'load-post.php', 'bp_nouveau_unregister_blocks_for_post_context' );
add_action( 'load-post-new.php', 'bp_nouveau_unregister_blocks_for_post_context' );

/**
 * Callback function to render the BP Primary Nav Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_nouveau_render_primary_nav_block( $attributes = array() ) {
	$widget_content = '';
	$widget_title   = '';
	$block_args     = bp_parse_args(
		$attributes,
		array(
			'displayTitle' => true,
		),
		'widget_object_nav'
	);

	// Previewing the Block inside the editor.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$widget_title = bp_get_loggedin_user_fullname();

		ob_start();

		// Temporary override the displayed user by the logged in one.
		add_filter( 'bp_displayed_user_id', 'bp_loggedin_user_id' );

		bp_get_template_part( 'members/single/parts/item-nav' );
		$widget_content = ob_get_clean();

		// Remove the temporary override.
		remove_filter( 'bp_displayed_user_id', 'bp_loggedin_user_id' );
	} else {
		ob_start();

		if ( bp_is_user() ) {
			$widget_title = bp_get_displayed_user_fullname();
			bp_get_template_part( 'members/single/parts/item-nav' );
		} elseif ( bp_is_group() ) {
			$widget_title = bp_get_current_group_name();
			bp_get_template_part( 'groups/single/parts/item-nav' );
		} elseif ( bp_is_directory() ) {
			$widget_title = bp_get_directory_title( bp_current_component() );
			bp_get_template_part( 'common/nav/directory-nav' );
		}

		$widget_content = ob_get_clean();
	}

	if ( ! $widget_content ) {
		return '';
	}

	// Set the Block's title.
	if ( true === $block_args['displayTitle'] ) {
		$widget_content = sprintf(
			'<h2 class="widget-title">%1$s</h2>
			%2$s',
			esc_html( $widget_title ),
			$widget_content
		);
	}

	// Only add a block wrapper if not loaded into a Widgets sidebar.
	if ( ! did_action( 'dynamic_sidebar_before' ) ) {
		$classnames         = 'widget_nav_menu buddypress_object_nav buddypress widget';
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$widget_content
		);
	}

	return $widget_content;
}

/**
 * Retuns the theme layout available widths.
 *
 * @since 10.0.0
 *
 * @return array The available theme layout widths.
 */
function bp_nouveau_get_theme_layout_widths() {
	$layout_widths = array();

	if ( current_theme_supports( 'align-wide' ) ) {
		$layout_widths = array(
			'alignnone' => __( 'Default width', 'buddypress' ),
			'alignwide' => __( 'Wide width', 'buddypress' ),
			'alignfull' => __( 'Full width', 'buddypress' ),
		);
	}

	// Use Block Theme global settings for Block Themes.
	if ( wp_is_block_theme() ) {
		$theme_layouts = wp_get_global_settings( array( 'layout' ) );

		if ( isset( $theme_layouts['wideSize'] ) && $theme_layouts['wideSize'] ) {
			$layout_widths = array(
				'alignnone' => __( 'Content width', 'buddypress' ),
				'alignwide' => __( 'Wide width', 'buddypress' ),
			);
		}
	}

	/**
	 * Filter here to edit the available theme layout widths.
	 *
	 * @since 10.0.0
	 *
	 * @param array $layout_widths The available theme layout widths.
	 */
	return apply_filters( 'bp_nouveau_get_theme_layout_widths', $layout_widths );
}

/**
 * Get the current displayed object for the priority nav.
 *
 * @since 12.0.0
 *
 * @return string The current displayed object (`member` or `group`).
 */
function bp_nouveau_get_current_priority_nav_object() {
	$object = '';

	if ( bp_is_user() ) {
		$object = 'member';
	} elseif ( bp_is_group() ) {
		$object = 'group';
	}

	return $object;
}

/**
 * Checks whether a single item supports priority nav.
 *
 * @since 12.0.0
 *
 * @param string $single_item The single item object name. Possible valuers are 'member' or 'group'.
 * @return bool True if the single item supports priority nav. False otherwise.
 */
function bp_nouveau_single_item_supports_priority_nav( $single_item = '' ) {
	$retval  = false;
	$feature = bp_get_theme_compat_feature( 'priority_item_nav' );

	if ( isset( $feature->single_items ) && is_array( $feature->single_items ) ) {
		$retval = ! empty( $feature->single_items );

		if ( $single_item ) {
			$retval = in_array( $single_item, $feature->single_items, true );
		}
	}

	/**
	 * Use this filter to disallow/allow the Priority nav support.
	 *
	 * @since 12.0.0
	 *
	 * @param bool   $retval      True if the single item supports priority nav. False otherwise.
	 * @param string $single_item The single item object name. Possible valuers are 'member' or 'group'.
	 */
	return apply_filters( 'bp_nouveau_single_item_supports_priority_nav', $retval, $single_item );
}
