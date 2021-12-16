<?php
/**
 * Common template tags
 *
 * @since 3.0.0
 * @version 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks at various places of templates
 *
 * @since 3.0.0
 *
 * @param array $pieces The list of terms of the hook to join.
 */
function bp_nouveau_hook( $pieces = array() ) {
	if ( ! $pieces ) {
		return;
	}

	$bp_prefix = reset( $pieces );
	if ( 'bp' !== $bp_prefix ) {
		array_unshift( $pieces, 'bp' );
	}

	$hook = join( '_', $pieces );

	/**
	 * Fires inside the `bp_nouveau_hook()` function.
	 *
	 * @since 3.0.0
	 */
	do_action( $hook );
}

/**
 * Fire plugin hooks in the plugins.php template (Groups and Members single items)
 *
 * @since 3.0.0
 *
 * @param string $suffix The suffix of the hook.
 */
function bp_nouveau_plugin_hook( $suffix = '' ) {
	if ( ! $suffix ) {
		return;
	}

	bp_nouveau_hook(
		array(
			'bp',
			'template',
			$suffix,
		)
	);
}

/**
 * Fire friend hooks
 *
 * @todo Move this into bp-nouveau/includes/friends/template-tags.php
 *       once we'll need other friends template tags.
 *
 * @since 3.0.0
 *
 * @param string $suffix The suffix of the hook.
 */
function bp_nouveau_friend_hook( $suffix = '' ) {
	if ( ! $suffix ) {
		return;
	}

	bp_nouveau_hook(
		array(
			'bp',
			'friend',
			$suffix,
		)
	);
}

/**
 * Add classes to style the template notice/feedback message
 *
 * @since 3.0.0
 */
function bp_nouveau_template_message_classes() {
	$bp_nouveau = bp_nouveau();
	$classes    = array( 'bp-feedback', 'bp-messages' );

	if ( ! empty( $bp_nouveau->template_message['message'] ) ) {
		$classes[] = 'bp-template-notice';
	}

	$classes[] = bp_nouveau_get_template_message_type();
	echo join( ' ', array_map( 'sanitize_html_class', $classes ) );
}
	/**
	 * Get the template notice/feedback message type
	 *
	 * @since 3.0.0
	 *
	 * @return string The type of the notice. Defaults to error.
	 */
	function bp_nouveau_get_template_message_type() {
		$bp_nouveau = bp_nouveau();
		$type       = 'error';

		if ( ! empty( $bp_nouveau->template_message['type'] ) ) {
			$type = $bp_nouveau->template_message['type'];
		} elseif ( ! empty( $bp_nouveau->user_feedback['type'] ) ) {
			$type = $bp_nouveau->user_feedback['type'];
		}

		return $type;
	}

/**
 * Checks if a template notice/feedback message is set
 *
 * @since 3.0.0
 *
 * @return bool True if a template notice is set. False otherwise.
 */
function bp_nouveau_has_template_message() {
	$bp_nouveau = bp_nouveau();

	if ( empty( $bp_nouveau->template_message['message'] ) && empty( $bp_nouveau->user_feedback ) ) {
		return false;
	}

	return true;
}

/**
 * Checks if the template notice/feedback message needs a dismiss button
 *
 * @todo Dismiss button re-worked to try and prevent buttons on general
 *       BP template notices - Nouveau user_feedback key needs review.
 *
 * @since 3.0.0
 *
 * @return bool True if a template notice needs a dismiss button. False otherwise.
 */
function bp_nouveau_has_dismiss_button() {
	$bp_nouveau = bp_nouveau();

	// BP template notices - set 'dismiss' true for a type in `bp_nouveau_template_notices()`
	if ( ! empty( $bp_nouveau->template_message['message'] ) && true === $bp_nouveau->template_message['dismiss'] ) {
		return true;
	}

	// Test for isset as value can be falsey.
	if ( isset( $bp_nouveau->user_feedback['dismiss'] ) ) {
		return true;
	}

	return false;
}

/**
 * Ouptut the dismiss type.
 *
 * $type is used to set the data-attr for the button.
 * 'clear' is tested for & used to remove cookies, if set, in buddypress-nouveau.js.
 * Currently template_notices(BP) will take $type = 'clear' if button set to true.
 *
 * @since 3.0.0
 */
function bp_nouveau_dismiss_button_type() {
	$bp_nouveau = bp_nouveau();
	$type       = 'clear';

	if ( ! empty( $bp_nouveau->user_feedback['dismiss'] ) ) {
		$type = $bp_nouveau->user_feedback['dismiss'];
	}

	echo esc_attr( $type );
}

/**
 * Displays a template notice/feedback message.
 *
 * @since 3.0.0
 */
function bp_nouveau_template_message() {
	echo bp_nouveau_get_template_message();
}

	/**
	 * Get the template notice/feedback message and make sure core filter is applied.
	 *
	 * @since 3.0.0
	 *
	 * @return string HTML Output.
	 */
	function bp_nouveau_get_template_message() {
		$bp_nouveau = bp_nouveau();

		if ( ! empty( $bp_nouveau->user_feedback['message'] ) ) {
			$user_feedback = $bp_nouveau->user_feedback['message'];

			// @TODO: why is this treated differently?
			foreach ( array( 'wp_kses_data', 'wp_unslash', 'wptexturize', 'convert_smilies', 'convert_chars' ) as $filter ) {
				$user_feedback = call_user_func( $filter, $user_feedback );
			}

			return '<p>' . $user_feedback . '</p>';

		} elseif ( ! empty( $bp_nouveau->template_message['message'] ) ) {
			/**
			 * Filters the 'template_notices' feedback message content.
			 *
			 * @since 1.5.5
			 *
			 * @param string $template_message Feedback message content.
			 * @param string $type             The type of message being displayed.
			 *                                 Either 'updated' or 'error'.
			 */
			return apply_filters( 'bp_core_render_message_content', $bp_nouveau->template_message['message'], bp_nouveau_get_template_message_type() );
		}
	}

/**
 * Template tag to display feedback notices to users, if there are to display
 *
 * @since 3.0.0
 */
function bp_nouveau_template_notices() {
	$bp         = buddypress();
	$bp_nouveau = bp_nouveau();

	if ( ! empty( $bp->template_message ) ) {
		// Clone BuddyPress template message to avoid altering it.
		$template_message = array( 'message' => $bp->template_message );

		if ( ! empty( $bp->template_message_type ) ) {
			$template_message['type'] = $bp->template_message_type;
		}

		// Adds a 'dimiss' (button) key to array - set true/false.
		$template_message['dismiss'] = false;

		// Set dismiss button true for sitewide notices
		if ( 'bp-sitewide-notice' == $template_message['type'] ) {
			$template_message['dismiss'] = true;
		}

		$bp_nouveau->template_message = $template_message;
		bp_get_template_part( 'common/notices/template-notices' );

		// Reset just after rendering it.
		$bp_nouveau->template_message = array();

		/**
		 * Fires after the display of any template_notices feedback messages.
		 *
		 * @since 3.0.0
		 */
		do_action( 'bp_core_render_message' );
	}

	/**
	 * Fires towards the top of template pages for notice display.
	 *
	 * @since 3.0.0
	 */
	do_action( 'template_notices' );
}

/**
 * Displays a feedback message to the user.
 *
 * @since 3.0.0
 *
 * @param string $feedback_id The ID of the message to display.
 */
function bp_nouveau_user_feedback( $feedback_id = '' ) {
	if ( ! isset( $feedback_id ) ) {
		return;
	}

	$bp_nouveau = bp_nouveau();
	$feedback   = bp_nouveau_get_user_feedback( $feedback_id );

	if ( ! $feedback ) {
		return;
	}

	if ( ! empty( $feedback['before'] ) ) {

		/**
		 * Fires before display of a feedback message to the user.
		 *
		 * This is a dynamic filter that is dependent on the "before" value provided by bp_nouveau_get_user_feedback().
		 *
		 * @since 3.0.0
		 */
		do_action( $feedback['before'] );
	}

	$bp_nouveau->user_feedback = $feedback;

	bp_get_template_part(

		/**
		 * Filter here if you wish to use a different templates than the notice one.
		 *
		 * @since 3.0.0
		 *
		 * @param string path to your template part.
		 */
		apply_filters( 'bp_nouveau_user_feedback_template', 'common/notices/template-notices' )
	);

	if ( ! empty( $feedback['after'] ) ) {

		/**
		 * Fires before display of a feedback message to the user.
		 *
		 * This is a dynamic filter that is dependent on the "after" value provided by bp_nouveau_get_user_feedback().
		 *
		 * @since 3.0.0
		 */
		do_action( $feedback['after'] );
	}

	// Reset the feedback message.
	$bp_nouveau->user_feedback = array();
}

/**
 * Template tag to wrap the before component loop
 *
 * @since 3.0.0
 */
function bp_nouveau_before_loop() {
	$component = bp_current_component();

	if ( bp_is_group() ) {
		$component = bp_current_action();
	}

	/**
	 * Fires before the start of the component loop.
	 *
	 * This is a variable hook that is dependent on the current component.
	 *
	 * @since 1.2.0
	 */
	do_action( "bp_before_{$component}_loop" );
}

/**
 * Template tag to wrap the after component loop
 *
 * @since 3.0.0
 */
function bp_nouveau_after_loop() {
	$component = bp_current_component();

	if ( bp_is_group() ) {
		$component = bp_current_action();
	}

	/**
	 * Fires after the finish of the component loop.
	 *
	 * This is a variable hook that is dependent on the current component.
	 *
	 * @since 1.2.0
	 */
	do_action( "bp_after_{$component}_loop" );
}

/**
 * Pagination for loops
 *
 * @since 3.0.0
 *
 * @param string $position Pagination for loops.
 */
function bp_nouveau_pagination( $position ) {
	$screen          = 'dir';
	$pagination_type = bp_current_component();

	if ( bp_is_user() ) {
		$screen = 'user';

	} elseif ( bp_is_group() ) {
		$screen          = 'group';
		$pagination_type = bp_current_action();

		if ( bp_is_group_admin_page() ) {
			$pagination_type = bp_action_variable( 0 );
		}
	}

	switch ( $pagination_type ) {
		case 'blogs':
			$pag_count   = bp_get_blogs_pagination_count();
			$pag_links   = bp_get_blogs_pagination_links();
			$top_hook    = 'bp_before_directory_blogs_list';
			$bottom_hook = 'bp_after_directory_blogs_list';
			$page_arg    = $GLOBALS['blogs_template']->pag_arg;
			break;

		case 'members':
		case 'friends':
		case 'manage-members':
			$pag_count = bp_get_members_pagination_count();
			$pag_links = bp_get_members_pagination_links();

			// Groups single items are not using these hooks
			if ( ! bp_is_group() ) {
				$top_hook    = 'bp_before_directory_members_list';
				$bottom_hook = 'bp_after_directory_members_list';
			}

			$page_arg = $GLOBALS['members_template']->pag_arg;
			break;

		case 'groups':
			$pag_count   = bp_get_groups_pagination_count();
			$pag_links   = bp_get_groups_pagination_links();
			$top_hook    = 'bp_before_directory_groups_list';
			$bottom_hook = 'bp_after_directory_groups_list';
			$page_arg    = $GLOBALS['groups_template']->pag_arg;
			break;

		case 'notifications':
			$pag_count   = bp_get_notifications_pagination_count();
			$pag_links   = bp_get_notifications_pagination_links();
			$top_hook    = '';
			$bottom_hook = '';
			$page_arg    = buddypress()->notifications->query_loop->pag_arg;
			break;

		case 'membership-requests':
			$pag_count   = bp_get_group_requests_pagination_count();
			$pag_links   = bp_get_group_requests_pagination_links();
			$top_hook    = '';
			$bottom_hook = '';
			$page_arg    = $GLOBALS['requests_template']->pag_arg;
			break;

		default:
			/**
			 * Use this filter to define your custom pagination parameters.
			 *
			 * @since 6.0.0
			 *
			 * @param array $value {
			 *     An associative array of pagination parameters.
			 *     @type string   $pag_count Information about the pagination count.
			 *                               eg: "Viewing 1 - 10 of 20 items".
			 *     @type string   $pag_links The Pagination links.
			 *     @type string   $page_arg  The argument to use to pass the page number.
			 * }
			 * @param string $pagination_type Information about the pagination type.
			 */
			$pagination_params = apply_filters( 'bp_nouveau_pagination_params',
				array(
					'pag_count' => '',
					'pag_links' => '',
					'page_arg'  => '',
				),
				$pagination_type
			);

			list( $pag_count, $pag_links, $page_arg ) = array_values( $pagination_params );
			break;
	}

	$count_class = sprintf( '%1$s-%2$s-count-%3$s', $pagination_type, $screen, $position );
	$links_class = sprintf( '%1$s-%2$s-links-%3$s', $pagination_type, $screen, $position );
	?>

	<?php
	if ( 'bottom' === $position && isset( $bottom_hook ) ) {
		/**
		 * Fires after the component directory list.
		 *
		 * @since 3.0.0
		 */
		do_action( $bottom_hook );
	};
	?>

	<div class="<?php echo esc_attr( 'bp-pagination ' . sanitize_html_class( $position ) ); ?>" data-bp-pagination="<?php echo esc_attr( $page_arg ); ?>">

		<?php if ( $pag_count ) : ?>
			<div class="<?php echo esc_attr( 'pag-count ' . sanitize_html_class( $position ) ); ?>">

				<p class="pag-data">
					<?php echo esc_html( $pag_count ); ?>
				</p>

			</div>
		<?php endif; ?>

		<?php if ( $pag_links ) : ?>
			<div class="<?php echo esc_attr( 'bp-pagination-links ' . sanitize_html_class( $position ) ); ?>">

				<p class="pag-data">
					<?php echo wp_kses_post( $pag_links ); ?>
				</p>

			</div>
		<?php endif; ?>

	</div>

	<?php
	if ( 'top' === $position && isset( $top_hook ) ) {
		/**
		 * Fires before the component directory list.
		 *
		 * @since 3.0.0
		 */
		do_action( $top_hook );
	};
}

/**
 * Display the component's loop classes
 *
 * @since 3.0.0
 */
function bp_nouveau_loop_classes() {
	echo esc_attr( bp_nouveau_get_loop_classes() );
}
	/**
	 * Get the component's loop classes
	 *
	 * @since 3.0.0
	 *
	 * @return string space separated value of classes.
	 */
	function bp_nouveau_get_loop_classes() {
		$bp_nouveau = bp_nouveau();

		// @todo: this function could do with passing args so we can pass simple strings in or array of strings
		$is_directory = bp_is_directory();

		// The $component is faked if it's the single group member loop
		if ( ! $is_directory && ( bp_is_group() && 'members' === bp_current_action() ) ) {
			$component = 'members_group';
		} elseif ( ! $is_directory && ( bp_is_user() && 'my-friends' === bp_current_action() ) ) {
			$component = 'members_friends';
		} else {
			$component = sanitize_key( bp_current_component() );
		}

		/*
		 * For the groups component, we need to take in account the
		 * Groups directory can list Groups according to a Group Type.
		 */
		if ( 'groups' === $component ) {
			$is_directory = bp_is_groups_directory();
		}

		$classes = array(
			'item-list',
			sprintf( '%s-list', str_replace( '_', '-', $component ) ),
			'bp-list',
		);

		if ( bp_is_user() && 'my-friends' === bp_current_action() ) {
			$classes[] = 'members-list';
		}

		if ( bp_is_user() && 'requests' === bp_current_action() ) {
			$classes[] = 'friends-request-list';
		}

		$available_components = array(
			'members' => true,
			'groups'  => true,
			'blogs'   => true,

			/*
			 * Technically not a component but allows us to check the single group members loop as a seperate loop.
			 */
			'members_group'   => true,
			'members_friends' => true,
		);

		// Only the available components supports custom layouts.
		if ( ! empty( $available_components[ $component ] ) && ( $is_directory || bp_is_group() || bp_is_user() ) ) {
			$customizer_option = sprintf( '%s_layout', $component );
			$layout_prefs      = bp_nouveau_get_temporary_setting(
				$customizer_option,
				bp_nouveau_get_appearance_settings( $customizer_option )
			);

			if ( $layout_prefs && (int) $layout_prefs > 1 ) {
				$grid_classes = bp_nouveau_customizer_grid_choices( 'classes' );

				if ( isset( $grid_classes[ $layout_prefs ] ) ) {
					$classes = array_merge( $classes, array(
						'grid',
						$grid_classes[ $layout_prefs ],
					) );
				}

				if ( ! isset( $bp_nouveau->{$component} ) ) {
					$bp_nouveau->{$component} = new stdClass;
				}

				// Set the global for a later use.
				$bp_nouveau->{$component}->loop_layout = $layout_prefs;
			}
		}

		/**
		 * Filter to edit/add classes.
		 *
		 * NB: you can also directly add classes into the template parts.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $classes   The list of classes.
		 * @param string $component The current component's loop.
		 */
		$class_list = (array) apply_filters( 'bp_nouveau_get_loop_classes', $classes, $component );

		return join( ' ', array_map( 'sanitize_html_class', $class_list ) );
	}


/**
 * Checks if the layout preferences is set to grid (2 or more columns).
 *
 * @since 3.0.0
 *
 * @return bool True if loop is displayed in grid mod. False otherwise.
 */
function bp_nouveau_loop_is_grid() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );

	return ! empty( $bp_nouveau->{$component}->loop_layout ) && $bp_nouveau->{$component}->loop_layout > 1;
}

/**
 * Returns the number of columns of the layout preferences.
 *
 * @since 3.0.0
 *
 * @return int The number of columns.
 */
function bp_nouveau_loop_get_grid_columns() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );
	$columns    = 1;

	if ( ! empty( $bp_nouveau->{$component}->loop_layout ) ) {
		$columns = (int) $bp_nouveau->{$component}->loop_layout;
	}

	/**
	 * Filter number of columns for this grid.
	 *
	 * @since 3.0.0
	 *
	 * @param int $columns The number of columns.
	 */
	return (int) apply_filters( 'bp_nouveau_loop_get_grid_columns', $columns );
}

/**
 * Return a bool check for component directory layout.
 *
 * Checks if activity, members, groups, blogs has the vert nav layout selected.
 *
 * @since 3.0.0
 *
 * @return bool
 */
function bp_dir_is_vert_layout() {
	$bp_nouveau = bp_nouveau();
	$component  = sanitize_key( bp_current_component() );

	return (bool) $bp_nouveau->{$component}->directory_vertical_layout;
}

/**
 * Template tag to wrap the Legacy actions that was used
 * after the components directory page.
 *
 * @since 6.0.0
 */
function bp_nouveau_after_directory_page() {
	$component = bp_current_component();

	/**
	 * Fires at the bottom of the activity, members, groups and blogs directory template file.
	 *
	 * @since 1.5.0 Added to the members, groups directory template file.
	 * @since 2.3.0 Added to the blogs directory template file.
	 * @since 6.0.0 Added to the activity directory template file.
	 */
	do_action( "bp_after_directory_{$component}_page" );
}

/**
 * Get the full size avatar args.
 *
 * @since 3.0.0
 *
 * @return array The avatar arguments.
 */
function bp_nouveau_avatar_args() {
	/**
	 * Filter arguments for full-size avatars.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args {
	 *     @param string $type   Avatar type.
	 *     @param int    $width  Avatar width value.
	 *     @param int    $height Avatar height value.
	 * }
	 */
	return apply_filters( 'bp_nouveau_avatar_args', array(
		'type'   => 'full',
		'width'  => bp_core_avatar_full_width(),
		'height' => bp_core_avatar_full_height(),
	) );
}


/** Template Tags for BuddyPress navigations **********************************/

/*
 * This is the BP Nouveau Navigation Loop.
 *
 * It can be used by any object using the
 * BP_Core_Nav API introduced in BuddyPress 2.6.0.
 */

/**
 * Init the Navigation Loop and check it has items.
 *
 * @since 3.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *
 *     @type string $type                    The type of Nav to get (primary or secondary)
 *                                           Default 'primary'. Required.
 *     @type string $object                  The object to get the nav for (eg: 'directory', 'group_manage',
 *                                           or any custom object). Default ''. Optional
 *     @type bool   $user_has_access         Used by the secondary member's & group's nav. Default true. Optional.
 *     @type bool   $show_for_displayed_user Used by the primary member's nav. Default true. Optional.
 * }
 *
 * @return bool True if the Nav contains items. False otherwise.
 */
function bp_nouveau_has_nav( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	$n = bp_parse_args(
		$args,
		array(
			'type'                    => 'primary',
			'object'                  => '',
			'user_has_access'         => true,
			'show_for_displayed_user' => true,
		),
		'nouveau_has_nav'
	);

	if ( empty( $n['type'] ) ) {
		return false;
	}

	$nav                       = array();
	$bp_nouveau->displayed_nav = '';
	$bp_nouveau->object_nav    = $n['object'];

	if ( bp_is_directory() || 'directory' === $bp_nouveau->object_nav ) {
		$bp_nouveau->displayed_nav = 'directory';
		$nav                       = $bp_nouveau->directory_nav->get_primary();

	// So far it's only possible to build a Group nav when displaying it.
	} elseif ( bp_is_group() ) {
		$bp_nouveau->displayed_nav = 'groups';
		$parent_slug               = bp_get_current_group_slug();
		$group_nav                 = buddypress()->groups->nav;

		if ( 'group_manage' === $bp_nouveau->object_nav && bp_is_group_admin_page() ) {
			$parent_slug .= '_manage';

		/**
		 * If it's not the Admin tabs, reorder the Group's nav according to the
		 * customizer setting.
		 */
		} else {
			bp_nouveau_set_nav_item_order( $group_nav, bp_nouveau_get_appearance_settings( 'group_nav_order' ), $parent_slug );
		}

		$nav = $group_nav->get_secondary(
			array(
				'parent_slug'     => $parent_slug,
				'user_has_access' => (bool) $n['user_has_access'],
			)
		);

	// Build the nav for the displayed user
	} elseif ( bp_is_user() ) {
		$bp_nouveau->displayed_nav = 'personal';
		$user_nav                  = buddypress()->members->nav;

		if ( 'secondary' === $n['type'] ) {
			$nav = $user_nav->get_secondary(
				array(
					'parent_slug'     => bp_current_component(),
					'user_has_access' => (bool) $n['user_has_access'],
				)
			);

		} else {
			$args = array();

			if ( true === (bool) $n['show_for_displayed_user'] && ! bp_is_my_profile() ) {
				$args = array( 'show_for_displayed_user' => true );
			}

			// Reorder the user's primary nav according to the customizer setting.
			bp_nouveau_set_nav_item_order( $user_nav, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );

			$nav = $user_nav->get_primary( $args );
		}

	} elseif ( ! empty( $bp_nouveau->object_nav ) ) {
		$bp_nouveau->displayed_nav = $bp_nouveau->object_nav;

		/**
		 * Use the filter to use your specific Navigation.
		 * Use the $n param to check for your custom object.
		 *
		 * @since 3.0.0
		 *
		 * @param array $nav The list of item navigations generated by the BP_Core_Nav API.
		 * @param array $n   The arguments of the Navigation loop.
		 */
		$nav = apply_filters( 'bp_nouveau_get_nav', $nav, $n );

	}

	// The navigation can be empty.
	if ( $nav === false ) {
		$nav = array();
	}

	$bp_nouveau->sorted_nav = array_values( $nav );

	if ( 0 === count( $bp_nouveau->sorted_nav ) || ! $bp_nouveau->displayed_nav ) {
		unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );

		return false;
	}

	$bp_nouveau->current_nav_index = 0;
	return true;
}

/**
 * Checks there are still nav items to display.
 *
 * @since 3.0.0
 *
 * @return bool True if there are still items to display. False otherwise.
 */
function bp_nouveau_nav_items() {
	$bp_nouveau = bp_nouveau();

	if ( isset( $bp_nouveau->sorted_nav[ $bp_nouveau->current_nav_index ] ) ) {
		return true;
	}

	$bp_nouveau->current_nav_index = 0;
	unset( $bp_nouveau->current_nav_item );

	return false;
}

/**
 * Sets the current nav item and prepare the navigation loop to iterate to next one.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_item() {
	$bp_nouveau = bp_nouveau();

	$bp_nouveau->current_nav_item   = $bp_nouveau->sorted_nav[ $bp_nouveau->current_nav_index ];
	$bp_nouveau->current_nav_index += 1;
}

/**
 * Displays the nav item ID.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_id() {
	echo esc_attr( bp_nouveau_get_nav_id() );
}
	/**
	 * Retrieve the ID attribute of the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string the ID attribute.
	 */
	function bp_nouveau_get_nav_id() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;

		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			$id = sprintf( '%1$s-%2$s', $nav_item->component, $nav_item->slug );
		} elseif ( 'groups' === $bp_nouveau->displayed_nav || 'personal' ===  $bp_nouveau->displayed_nav ) {
			$id = sprintf( '%1$s-%2$s-li', $nav_item->css_id, $bp_nouveau->displayed_nav );
		} else {
			$id = $nav_item->slug;
		}

		/**
		 * Filter to edit the ID attribute of the nav.
		 *
		 * @since 3.0.0
		 *
		 * @param string $id       The ID attribute of the nav.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return apply_filters( 'bp_nouveau_get_nav_id', $id, $nav_item, $bp_nouveau->displayed_nav );
	}

/**
 * Displays the nav item classes.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_classes() {
	echo esc_attr( bp_nouveau_get_nav_classes() );
}
	/**
	 * Retrieve a space separated list of classes for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string List of classes.
	 */
	function bp_nouveau_get_nav_classes() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$classes    = array();

		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			if ( ! empty( $nav_item->li_class ) ) {
				$classes = (array) $nav_item->li_class;
			}

			if ( bp_get_current_member_type() || ( bp_is_groups_directory() && bp_get_current_group_directory_type() ) ) {
				$classes[] = 'no-ajax';
			}
		} elseif ( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav ) {
			$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-tab' );
			$selected = bp_current_action();

			// User's primary nav
			if ( ! empty( $nav_item->primary ) ) {
				$selected = bp_current_component();

			// Group Admin Tabs.
			} elseif ( 'group_manage' === $bp_nouveau->object_nav ) {
				$selected = bp_action_variable( 0 );
				$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-admin-tab' );

			// If we are here, it's the member's subnav
			} elseif ( 'personal' === $bp_nouveau->displayed_nav ) {
				$classes  = array( 'bp-' . $bp_nouveau->displayed_nav . '-sub-tab' );
			}

			if ( $nav_item->slug === $selected ) {
				$classes = array_merge( $classes, array( 'current', 'selected' ) );
			}
		}

		if ( ! empty( $classes ) ) {
			$classes = array_map( 'sanitize_html_class', $classes );
		}

		/**
		 * Filter to edit/add classes.
		 *
		 * NB: you can also directly add classes into the template parts.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value    A space separated list of classes.
		 * @param array  $classes  The list of classes.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		$classes_list = apply_filters( 'bp_nouveau_get_classes', join( ' ', $classes ), $classes, $nav_item, $bp_nouveau->displayed_nav );
		if ( ! $classes_list ) {
			$classes_list = '';
		}

		return $classes_list;
	}

/**
 * Displays the nav item scope.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_scope() {
	echo bp_nouveau_get_nav_scope();  // Escaped by bp_get_form_field_attributes().
}
	/**
	 * Retrieve the specific scope for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string the specific scope of the nav.
	 */
	function bp_nouveau_get_nav_scope() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$scope      = array();

		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			$scope = array( 'data-bp-scope' => $nav_item->slug );

		} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->secondary ) ) {
			$scope = array( 'data-bp-user-scope' => $nav_item->slug );

		} else {
			/**
			 * Filter to add your own scope.
			 *
			 * @since 3.0.0
			 *
			 * @param array $scope     Contains the key and the value for your scope.
			 * @param object $nav_item The current nav item object.
			 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
			 */
			$scope = apply_filters( 'bp_nouveau_set_nav_scope', $scope, $nav_item, $bp_nouveau->displayed_nav );
		}

		if ( ! $scope ) {
			return '';
		}

		return bp_get_form_field_attributes( 'scope', $scope );
	}

/**
 * Displays the nav item URL.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_link() {
	echo esc_url( bp_nouveau_get_nav_link() );
}
	/**
	 * Retrieve the URL for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string The URL for the nav item.
	 */
	function bp_nouveau_get_nav_link() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;

		$link = '#';
		if ( ! empty( $nav_item->link ) ) {
			$link = $nav_item->link;
		}

		if ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
			if ( bp_loggedin_user_domain() ) {
				$link = str_replace( bp_loggedin_user_domain(), bp_displayed_user_domain(), $link );
			} else {
				$link = trailingslashit( bp_displayed_user_domain() . $link );
			}
		}

		/**
		 * Filter to edit the URL of the nav item.
		 *
		 * @since 3.0.0
		 *
		 * @param string $link     The URL for the nav item.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return apply_filters( 'bp_nouveau_get_nav_link', $link, $nav_item, $bp_nouveau->displayed_nav );
	}

/**
 * Displays the nav item link ID.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_link_id() {
	echo esc_attr( bp_nouveau_get_nav_link_id() );
}
	/**
	 * Retrieve the id attribute of the link for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string The link id for the nav item.
	 */
	function bp_nouveau_get_nav_link_id() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$link_id   = '';

		if ( ( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav ) && ! empty( $nav_item->css_id ) ) {
			$link_id = $nav_item->css_id;

			if ( ! empty( $nav_item->primary ) && 'personal' === $bp_nouveau->displayed_nav ) {
				$link_id = 'user-' . $link_id;
			}
		} else {
			$link_id = $nav_item->slug;
		}

		/**
		 * Filter to edit the link id attribute of the nav.
		 *
		 * @since 3.0.0
		 *
		 * @param string $link_id  The link id attribute for the nav item.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return apply_filters( 'bp_nouveau_get_nav_link_id', $link_id, $nav_item, $bp_nouveau->displayed_nav );
	}

/**
 * Displays the nav item link title.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_link_title() {
	echo esc_attr( bp_nouveau_get_nav_link_title() );
}
	/**
	 * Retrieve the title attribute of the link for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string The link title for the nav item.
	 */
	function bp_nouveau_get_nav_link_title() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$title      = '';

		if ( 'directory' === $bp_nouveau->displayed_nav && ! empty( $nav_item->title ) ) {
			$title = $nav_item->title;

		} elseif (
			( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav )
			&&
			! empty( $nav_item->name )
		) {
			$title = $nav_item->name;
		}

		/**
		 * Filter to edit the link title attribute of the nav.
		 *
		 * @since 3.0.0
		 *
		 * @param string $title    The link title attribute for the nav item.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return apply_filters( 'bp_nouveau_get_nav_link_title', $title, $nav_item, $bp_nouveau->displayed_nav );
	}

/**
 * Displays the nav item link html text.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_link_text() {
	echo esc_html( bp_nouveau_get_nav_link_text() );
}
	/**
	 * Retrieve the html text of the link for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return string The html text for the nav item.
	 */
	function bp_nouveau_get_nav_link_text() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$link_text  = '';

		if ( 'directory' === $bp_nouveau->displayed_nav && ! empty( $nav_item->text ) ) {
			$link_text = _bp_strip_spans_from_title( $nav_item->text );

		} elseif (
			( 'groups' === $bp_nouveau->displayed_nav || 'personal' === $bp_nouveau->displayed_nav )
			&&
			! empty( $nav_item->name )
		) {
			$link_text = _bp_strip_spans_from_title( $nav_item->name );
		}

		/**
		 * Filter to edit the html text of the nav.
		 *
		 * @since 3.0.0
		 *
		 * @param string $link_text The html text of the nav item.
		 * @param object $nav_item  The current nav item object.
		 * @param string $value     The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return apply_filters( 'bp_nouveau_get_nav_link_text', $link_text, $nav_item, $bp_nouveau->displayed_nav );
	}

/**
 * Checks if the nav item has a count attribute.
 *
 * @since 3.0.0
 *
 * @return bool
 */
function bp_nouveau_nav_has_count() {
	$bp_nouveau = bp_nouveau();
	$nav_item   = $bp_nouveau->current_nav_item;
	$count      = false;

	if ( 'directory' === $bp_nouveau->displayed_nav ) {
		$count = $nav_item->count;
	} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'members' === $nav_item->slug ) {
		$count = 0 !== (int) groups_get_current_group()->total_member_count;
	} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
		$count = (bool) strpos( $nav_item->name, '="count"' );
	}

	/**
	 * Filter to edit whether the nav has a count attribute.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $value     True if the nav has a count attribute. False otherwise
	 * @param object $nav_item  The current nav item object.
	 * @param string $value     The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
	 */
	return (bool) apply_filters( 'bp_nouveau_nav_has_count', false !== $count, $nav_item, $bp_nouveau->displayed_nav );
}

/**
 * Displays the nav item count attribute.
 *
 * @since 3.0.0
 */
function bp_nouveau_nav_count() {
	echo esc_html( number_format_i18n( bp_nouveau_get_nav_count() ) );
}
	/**
	 * Retrieve the count attribute for the current nav item.
	 *
	 * @since 3.0.0
	 *
	 * @return int The count attribute for the nav item.
	 */
	function bp_nouveau_get_nav_count() {
		$bp_nouveau = bp_nouveau();
		$nav_item   = $bp_nouveau->current_nav_item;
		$count      = 0;

		if ( 'directory' === $bp_nouveau->displayed_nav ) {
			$count = (int) $nav_item->count;

		} elseif ( 'groups' === $bp_nouveau->displayed_nav && 'members' === $nav_item->slug ) {
			$count = groups_get_current_group()->total_member_count;

		// @todo imho BuddyPress shouldn't add html tags inside Nav attributes...
		} elseif ( 'personal' === $bp_nouveau->displayed_nav && ! empty( $nav_item->primary ) ) {
			$span = strpos( $nav_item->name, '<span' );

			// Grab count out of the <span> element.
			if ( false !== $span ) {
				$count_start = strpos( $nav_item->name, '>', $span ) + 1;
				$count_end   = strpos( $nav_item->name, '<', $count_start );
				$count       = (int) substr( $nav_item->name, $count_start, $count_end - $count_start );
			}
		}

		/**
		 * Filter to edit the count attribute for the nav item.
		 *
		 * @since 3.0.0
		 *
		 * @param int $count    The count attribute for the nav item.
		 * @param object $nav_item The current nav item object.
		 * @param string $value    The current nav in use (eg: 'directory', 'groups', 'personal', etc..).
		 */
		return (int) apply_filters( 'bp_nouveau_get_nav_count', $count, $nav_item, $bp_nouveau->displayed_nav );
	}

/** Template tags specific to the Directory navs ******************************/

/**
 * Displays the directory nav class.
 *
 * @since 3.0.0
 */
function bp_nouveau_directory_type_navs_class() {
	echo esc_attr( bp_nouveau_get_directory_type_navs_class() );
}
	/**
	 * Provides default nav wrapper classes.
	 *
	 * Gets the directory component nav class.
	 * Gets user selection Customizer options.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	function bp_nouveau_get_directory_type_navs_class() {
		$component  = sanitize_key( bp_current_component() );

		// If component is 'blogs' we need to access options as 'Sites'.
		if ('blogs' === $component) {
			$component = 'sites';
		};

		$customizer_option = sprintf( '%s_dir_tabs', $component );
		$nav_style  = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );
		$tab_style = '';

		if ( 1 === $nav_style ) {
			$tab_style = $component . '-nav-tabs';
		}

		$nav_wrapper_classes = array(
			sprintf( '%s-type-navs', $component ),
			'main-navs',
			'bp-navs',
			'dir-navs',
			$tab_style
		);

		/**
		 * Filter to edit/add classes.
		 *
		 * NB: you can also directly add classes to the class attr.
		 *
		 * @since 3.0.0
		 *
		 * @param array $nav_wrapper_classes The list of classes.
		 */
		$nav_wrapper_classes = (array) apply_filters( 'bp_nouveau_get_directory_type_navs_class', $nav_wrapper_classes );

		return join( ' ', array_map( 'sanitize_html_class', $nav_wrapper_classes ) );
	}

/**
 * Displays the directory nav item list class.
 *
 * @since 3.0.0
 */
function bp_nouveau_directory_list_class() {
	echo esc_attr( bp_nouveau_get_directory_list_class() );
}
	/**
	 * Gets the directory nav item list class.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	function bp_nouveau_get_directory_list_class() {
		return sanitize_html_class( sprintf( '%s-nav', bp_current_component() ) );
	}

/**
 * Displays the directory nav item object (data-bp attribute).
 *
 * @since 3.0.0
 */
function bp_nouveau_directory_nav_object() {
	$obj = bp_nouveau_get_directory_nav_object();

	if ( ! is_null( $obj ) ) {
		echo esc_attr( $obj );
	}
}
	/**
	 * Gets the directory nav item object.
	 *
	 * @see BP_Component::setup_nav().
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	function bp_nouveau_get_directory_nav_object() {
		$nav_item = bp_nouveau()->current_nav_item;

		if ( ! $nav_item->component ) {
			return null;
		}

		return $nav_item->component;
	}


// Template tags for the single item navs.

/**
 * Output main BuddyPress container classes.
 *
 * @since 3.0.0
 *
 * @return string CSS classes
 */
function bp_nouveau_container_classes() {
	echo esc_attr( bp_nouveau_get_container_classes() );
}

	/**
	 * Returns the main BuddyPress container classes.
	 *
	 * @since 3.0.0
	 * @since 7.0.0 Add a class to inform about the active Theme.
	 *
	 * @return string CSS classes
	 */
	function bp_nouveau_get_container_classes() {
		$classes           = array( 'buddypress-wrap', get_template() );
		$component         = bp_current_component();
		$bp_nouveau        = bp_nouveau();
		$member_type_class = '';

		if ( bp_is_user() ) {
			$customizer_option = 'user_nav_display';
			$component         = 'members';
			$user_type         = bp_get_member_type( bp_displayed_user_id() );
			$member_type_class = ( $user_type )? $user_type : '';

		} elseif ( bp_is_group() ) {
			$customizer_option = 'group_nav_display';

		} elseif ( bp_is_directory() ) {
			switch ( $component ) {
				case 'activity':
					$customizer_option = 'activity_dir_layout';
					break;

				case 'members':
					$customizer_option = 'members_dir_layout';
					break;

				case 'groups':
					$customizer_option = 'groups_dir_layout';
					break;

				case 'blogs':
					$customizer_option = 'sites_dir_layout';
					break;

				default:
					$customizer_option = '';
					break;
			}

		} else {
			/**
			 * Filters the BuddyPress Nouveau single item setting ID.
			 *
			 * @since 3.0.0
			 *
			 * @param string $value Setting ID.
			 */
			$customizer_option = apply_filters( 'bp_nouveau_single_item_display_settings_id', '' );
		}

		if ( $member_type_class ) {
			$classes[] = $member_type_class;
		}

		// Provide a class token to acknowledge additional extended profile fields added to default account reg screen
		if ( 'register' === bp_current_component() && bp_is_active( 'xprofile' ) && bp_nouveau_has_signup_xprofile_fields()) {
			$classes[] = 'extended-default-reg';
		}

		// Add classes according to site owners preferences. These are options set via Customizer.

		// These are general site wide Cust options falling outside component checks
		$general_settings = bp_nouveau_get_temporary_setting( 'avatar_style', bp_nouveau_get_appearance_settings( 'avatar_style' ) );
		if ( $general_settings ) {
			$classes[] = 'round-avatars';
		}

		// Set via earlier switch for component check to provide correct option key.
		if ( $customizer_option ) {
			$layout_prefs  = bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );

			if ( $layout_prefs && (int) $layout_prefs === 1 && ( bp_is_user() || bp_is_group() ) ) {
				$classes[] = 'bp-single-vert-nav';
				$classes[] = 'bp-vertical-navs';
			}

			if ( $layout_prefs && bp_is_directory() ) {
				$classes[] = 'bp-dir-vert-nav';
				$classes[] = 'bp-vertical-navs';
				$bp_nouveau->{$component}->directory_vertical_layout = $layout_prefs;
			} else {
				$classes[] = 'bp-dir-hori-nav';
			}
		}

		$global_alignment  = bp_nouveau_get_temporary_setting( 'global_alignment', bp_nouveau_get_appearance_settings( 'global_alignment' ) );
		$layout_widths     = bp_nouveau_get_theme_layout_widths();

		if ( $global_alignment && 'alignnone' !== $global_alignment && $layout_widths ) {
			$classes[] = $global_alignment;
		}

		$class = array_map( 'sanitize_html_class', $classes );

		/**
		 * Filters the final results for BuddyPress Nouveau container classes.
		 *
		 * This filter will return a single string of concatenated classes to be used.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value   Concatenated classes.
		 * @param array  $classes Array of classes that were concatenated.
		 */
		return apply_filters( 'bp_nouveau_get_container_classes', join( ' ', $class ), $classes );
	}

/**
 * Output single item nav container classes
 *
 * @since 3.0.0
 */
function bp_nouveau_single_item_nav_classes() {
	echo esc_attr( bp_nouveau_get_single_item_nav_classes() );
}
	/**
	 * Returns the single item nav container classes
	 *
	 * @since 3.0.0
	 *
	 * @return string CSS classes.
	 */
	function bp_nouveau_get_single_item_nav_classes() {
		$classes    = array( 'main-navs', 'no-ajax', 'bp-navs', 'single-screen-navs' );
		$component  = bp_current_component();
		$bp_nouveau = bp_nouveau();

		// @todo wasn't able to get $customizer_option to pass a string to get_settings
		// this is a temp workaround but differs from earlier dir approach- bad!
		if ( bp_is_group() ) {
			$nav_tabs = (int) bp_nouveau_get_temporary_setting( 'group_nav_tabs', bp_nouveau_get_appearance_settings( 'group_nav_tabs' ) );

		} elseif ( bp_is_user() ) {
			$nav_tabs = (int) bp_nouveau_get_temporary_setting( 'user_nav_tabs', bp_nouveau_get_appearance_settings( 'user_nav_tabs' ) );
		}

		if ( bp_is_group() && 1 === $nav_tabs) {
			$classes[] = 'group-nav-tabs';
			$classes[] = 'tabbed-links';
		} elseif ( bp_is_user() && 1 === $nav_tabs ) {
			$classes[] = 'user-nav-tabs';
			$classes[] = 'tabbed-links';
		}

		if ( bp_is_user() ) {
			$component = 'members';
			$menu_type = 'users-nav';
		} else {
			$menu_type = 'groups-nav';
		}

		$customizer_option = ( bp_is_user() )? 'user_nav_display' : 'group_nav_display';

		$layout_prefs = (int) bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );

		// Set the global for a later use - this is moved from the `bp_nouveau_get_container_classes()
		// But was set as a check for this array class addition.
		$bp_nouveau->{$component}->single_primary_nav_layout = $layout_prefs;

		if ( 1 === $layout_prefs ) {
			$classes[] = 'vertical';
		} else {
			$classes[] = 'horizontal';
		}

		$classes[] = $menu_type;
		$class = array_map( 'sanitize_html_class', $classes );

		/**
		 * Filters the final results for BuddyPress Nouveau single item nav classes.
		 *
		 * This filter will return a single string of concatenated classes to be used.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value   Concatenated classes.
		 * @param array  $classes Array of classes that were concatenated.
		 */
		return apply_filters( 'bp_nouveau_get_single_item_nav_classes', join( ' ', $class ), $classes );
	}

/**
 * Output single item subnav container classes.
 *
 * @since 3.0.0
 */
function bp_nouveau_single_item_subnav_classes() {
	echo esc_attr( bp_nouveau_get_single_item_subnav_classes() );
}
	/**
	 * Returns the single item subnav container classes.
	 *
	 * @since 3.0.0
	 *
	 * @return string CSS classes.
	 */
	function bp_nouveau_get_single_item_subnav_classes() {
		$classes = array( 'bp-navs', 'bp-subnavs', 'no-ajax' );

		// Set user or group class string
		if ( bp_is_user() ) {
			$classes[] = 'user-subnav';
		}

		if ( bp_is_group() ) {
			$classes[] = 'group-subnav';
		}

		if ( ( bp_is_group() && 'send-invites' === bp_current_action() ) || ( bp_is_group_create() && 'group-invites' === bp_get_groups_current_create_step() ) ) {
			$classes[] = 'bp-invites-nav';
		}

		$customizer_option = ( bp_is_user() )? 'user_subnav_tabs' : 'group_subnav_tabs';
		$nav_tabs = (int) bp_nouveau_get_temporary_setting( $customizer_option, bp_nouveau_get_appearance_settings( $customizer_option ) );

		if ( bp_is_user() && 1 === $nav_tabs ) {
			$classes[] = 'tabbed-links';
		}

		if ( bp_is_group() && 1 === $nav_tabs ) {
			$classes[] = 'tabbed-links';
		}

		$class = array_map( 'sanitize_html_class', $classes );

		/**
		 * Filters the final results for BuddyPress Nouveau single item subnav classes.
		 *
		 * This filter will return a single string of concatenated classes to be used.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value   Concatenated classes.
		 * @param array  $classes Array of classes that were concatenated.
		 */
		return apply_filters( 'bp_nouveau_get_single_item_subnav_classes', join( ' ', $class ), $classes );
	}

/**
 * Output the groups create steps classes.
 *
 * @since 3.0.0
 */
function bp_nouveau_groups_create_steps_classes() {
	echo esc_attr( bp_nouveau_get_group_create_steps_classes() );
}
	/**
	 * Returns the groups create steps customizer option choice class.
	 *
	 * @since 3.0.0
	 *
	 * @return string CSS classes.
	 */
	function bp_nouveau_get_group_create_steps_classes() {
		$classes  = array( 'bp-navs', 'group-create-links', 'no-ajax' );
		$nav_tabs = (int) bp_nouveau_get_temporary_setting( 'groups_create_tabs', bp_nouveau_get_appearance_settings( 'groups_create_tabs' ) );

		if ( 1 === $nav_tabs ) {
			$classes[] = 'tabbed-links';
		}

		$class = array_map( 'sanitize_html_class', $classes );

		/**
		 * Filters the final results for BuddyPress Nouveau group creation step classes.
		 *
		 * This filter will return a single string of concatenated classes to be used.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value   Concatenated classes.
		 * @param array  $classes Array of classes that were concatenated.
		 */
		return apply_filters( 'bp_nouveau_get_group_create_steps_classes', join( ' ', $class ), $classes );
	}


/** Template tags for the object search **************************************/

/**
 * Get the search primary object
 *
 * @since 3.0.0
 *
 * @param string $object (Optional) The primary object.
 *
 * @return string The primary object.
 */
function bp_nouveau_get_search_primary_object( $object = '' ) {
	if ( bp_is_user() ) {
		$object = 'member';
	} elseif ( bp_is_group() ) {
		$object = 'group';
	} elseif ( bp_is_directory() ) {
		$object = 'dir';
	} else {

		/**
		 * Filters the search primary object if no other was found.
		 *
		 * @since 3.0.0
		 *
		 * @param string $object Search object.
		 */
		$object = apply_filters( 'bp_nouveau_get_search_primary_object', $object );
	}

	return $object;
}

/**
 * Get The list of search objects (primary + secondary).
 *
 * @since 3.0.0
 *
 * @param array $objects (Optional) The list of objects.
 *
 * @return array The list of objects.
 */
function bp_nouveau_get_search_objects( $objects = array() ) {
	$primary = bp_nouveau_get_search_primary_object();
	if ( ! $primary ) {
		return $objects;
	}

	$objects = array(
		'primary' => $primary,
	);

	if ( 'member' === $primary || 'dir' === $primary ) {
		$objects['secondary'] = bp_current_component();
	} elseif ( 'group' === $primary ) {
		$objects['secondary'] = bp_current_action();

		if ( bp_is_group_home() && ! bp_is_group_custom_front() ) {
			$objects['secondary'] = 'members';

			if ( bp_is_active( 'activity' ) ) {
				$objects['secondary'] = 'activity';
			}
		}
	} else {

		/**
		 * Filters the search objects if no others were found.
		 *
		 * @since 3.0.0
		 *
		 * @param array $objects Search objects.
		 */
		$objects = apply_filters( 'bp_nouveau_get_search_objects', $objects );
	}

	return $objects;
}

/**
 * Output the search form container classes.
 *
 * @since 3.0.0
 */
function bp_nouveau_search_container_class() {
	$objects = bp_nouveau_get_search_objects();
	$class   = join( '-search ', array_map( 'sanitize_html_class', $objects ) ) . '-search';

	echo esc_attr( $class );
}

/**
 * Output the search form data-bp attribute.
 *
 * @since 3.0.0
 *
 * @param  string $attr The data-bp attribute.
 * @return string The data-bp attribute.
 */
function bp_nouveau_search_object_data_attr( $attr = '' ) {
	$objects = bp_nouveau_get_search_objects();

	if ( ! isset( $objects['secondary'] ) ) {
		return $attr;
	}

	if ( bp_is_active( 'groups' ) && bp_is_group_members() ) {
		$attr = join( '_', $objects );
	} else {
		$attr = $objects['secondary'];
	}

	echo esc_attr( $attr );
}

/**
 * Output a selector ID.
 *
 * @since 3.0.0
 *
 * @param string $suffix Optional. A string to append at the end of the ID.
 * @param string $sep    Optional. The separator to use between each token.
 */
function bp_nouveau_search_selector_id( $suffix = '', $sep = '-' ) {
	$id = join( $sep, array_merge( bp_nouveau_get_search_objects(), (array) $suffix ) );
	echo esc_attr( $id );
}

/**
 * Output the name attribute of a selector.
 *
 * @since 3.0.0
 *
 * @param  string $suffix Optional. A string to append at the end of the name.
 * @param  string $sep    Optional. The separator to use between each token.
 */
function bp_nouveau_search_selector_name( $suffix = '', $sep = '_' ) {
	$objects = bp_nouveau_get_search_objects();

	if ( isset( $objects['secondary'] ) && ! $suffix ) {
		$name = bp_core_get_component_search_query_arg( $objects['secondary'] );
	} else {
		$name = join( $sep, array_merge( $objects, (array) $suffix ) );
	}

	echo esc_attr( $name );
}

/**
 * Output the default search text for the search object
 *
 * @todo 28/09/17 added  'empty( $text )' check to $object query as it wasn't returning output as expected & not returning user set params
 * This may require further examination - hnla
 *
 * @since 3.0.0
 *
 * @param  string $text    Optional. The default search text for the search object.
 * @param  string $is_attr Optional. True if it's to be output inside an attribute. False otherwise.
 */
function bp_nouveau_search_default_text( $text = '', $is_attr = true ) {
	$objects = bp_nouveau_get_search_objects();

	if ( ! empty( $objects['secondary'] ) && empty( $text ) ) {
		$text = bp_get_search_default_text( $objects['secondary'] );
	}

	if ( $is_attr ) {
		echo esc_attr( $text );
	} else {
		echo esc_html( $text );
	}
}

/**
 * Get the search form template part and fire some do_actions if needed.
 *
 * @since 3.0.0
 */
function bp_nouveau_search_form() {
	$search_form_html = bp_buffer_template_part( 'common/search/search-form', null, false );

	$objects = bp_nouveau_get_search_objects();
	if ( empty( $objects['primary'] ) || empty( $objects['secondary'] ) ) {
		echo $search_form_html;
		return;
	}

	if ( 'dir' === $objects['primary'] ) {
		/**
		 * Filter here to edit the HTML output of the directory search form.
		 *
		 * NB: This will take in charge the following BP Core Components filters
		 *     - bp_directory_members_search_form
		 *     - bp_directory_blogs_search_form
		 *     - bp_directory_groups_search_form
		 *
		 * @since 1.9.0
		 *
		 * @param string $search_form_html The HTML output for the directory search form.
		 */
		echo apply_filters( "bp_directory_{$objects['secondary']}_search_form", $search_form_html );

		if ( 'activity' === $objects['secondary'] ) {
			/**
			 * Fires before the display of the activity syndication options.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_activity_syndication_options' );

		} elseif ( 'blogs' === $objects['secondary'] ) {
			/**
			 * Fires inside the unordered list displaying blog sub-types.
			 *
			 * @since 1.5.0
			 */
			do_action( 'bp_blogs_directory_blog_sub_types' );

		} elseif ( 'groups' === $objects['secondary'] ) {
			/**
			 * Fires inside the groups directory group types.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_groups_directory_group_types' );

		} elseif ( 'members' === $objects['secondary'] ) {
			/**
			 * Fires inside the members directory member sub-types.
			 *
			 * @since 1.5.0
			 */
			do_action( 'bp_members_directory_member_sub_types' );
		}
	} elseif ( 'group' === $objects['primary'] ) {
		if ( 'members' !== $objects['secondary'] ) {
			/**
			 * Filter here to edit the HTML output of the displayed group search form.
			 *
			 * @since 3.2.0
			 *
			 * @param string $search_form_html The HTML output for the directory search form.
			 */
			echo apply_filters( "bp_group_{$objects['secondary']}_search_form", $search_form_html );

		} else {
			/**
			 * Filters the Members component search form.
			 *
			 * @since 1.9.0
			 *
			 * @param string $search_form_html HTML markup for the member search form.
			 */
			echo apply_filters( 'bp_directory_members_search_form', $search_form_html );
		}

		if ( 'members' === $objects['secondary'] ) {
			/**
			 * Fires at the end of the group members search unordered list.
			 *
			 * Part of bp_groups_members_template_part().
			 *
			 * @since 1.5.0
			 */
			do_action( 'bp_members_directory_member_sub_types' );

		} elseif ( 'activity' === $objects['secondary'] ) {
			/**
			 * Fires inside the syndication options list, after the RSS option.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_group_activity_syndication_options' );
		}
	}
}

// Template tags for the directory & user/group screen filters.

/**
 * Get the current component or action.
 *
 * If on single group screens we need to switch from component to bp_current_action() to add the correct
 * IDs/labels for group/activity & similar screens.
 *
 * @since 3.0.0
 */
function bp_nouveau_current_object() {
	/*
	 * If we're looking at groups single screens we need to factor in current action
	 * to avoid the component check adding the wrong id for the main dir e.g 'groups' instead of 'activity'.
	 * We also need to check for group screens to adjust the id's for prefixes.
	 */
	$component = array();

	if ( bp_is_group() ) {
		$component['members_select']   = 'groups_members-order-select';
		$component['members_order_by'] = 'groups_members-order-by';
		$component['object']           = bp_current_action();
		$component['data_filter']      = bp_current_action();

		if ( 'activity' !== bp_current_action() ) {
			/**
			 * If the Group's front page is not used, Activities are displayed on Group's home page.
			 * To make sure filters are behaving the right way, we need to override the component object
			 * and data filter to `activity`.
			 */
			if ( bp_is_group_activity() ) {
				$activity_id              = buddypress()->activity->id;
				$component['object']      = $activity_id;
				$component['data_filter'] = $activity_id;
			} else {
				$component['data_filter'] = 'group_' . bp_current_action();
			}
		}

	} else {
		$component_id = bp_current_component();
		if ( ! bp_is_directory() ) {
			$component_id = bp_core_get_active_components( array( 'slug' => $component_id ) );
			$component_id = reset( $component_id );
		}

		$data_filter  = $component_id;

		if ( 'friends' === $data_filter && bp_is_user_friend_requests() ) {
			$data_filter = 'friend_requests';
		}

		$component['members_select']   = 'members-order-select';
		$component['members_order_by'] = 'members-order-by';
		$component['object']           = $component_id;
		$component['data_filter']      = $data_filter;
	}

	return $component;
}

/**
 * Output data filter container's ID attribute value.
 *
 * @since 3.0.0
 */
function bp_nouveau_filter_container_id() {
	echo esc_attr( bp_nouveau_get_filter_container_id() );
}
	/**
	 * Get data filter container's ID attribute value.
	 *
	 * @since 3.0.0
	 *
	 * @param string
	 */
	function bp_nouveau_get_filter_container_id() {
		$component = bp_nouveau_current_object();

		$ids = array(
			'members'       =>  $component['members_select'],
			'friends'       => 'members-friends-select',
			'notifications' => 'notifications-filter-select',
			'activity'      => 'activity-filter-select',
			'groups'        => 'groups-order-select',
			'blogs'         => 'blogs-order-select',
		);

		if ( isset( $ids[ $component['object'] ] ) ) {

			/**
			 * Filters the container ID for BuddyPress Nouveau filters.
			 *
			 * @since 3.0.0
			 *
			 * @param string $value ID based on current component object.
			 */
			return apply_filters( 'bp_nouveau_get_filter_container_id', $ids[ $component['object'] ] );
		}

		return '';
	}

/**
 * Output data filter's ID attribute value.
 *
 * @since 3.0.0
 */
function bp_nouveau_filter_id() {
	echo esc_attr( bp_nouveau_get_filter_id() );
}
	/**
	 * Get data filter's ID attribute value.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	function bp_nouveau_get_filter_id() {
		$component = bp_nouveau_current_object();

		$ids = array(
			'members'       => $component['members_order_by'],
			'friends'       => 'members-friends',
			'notifications' => 'notifications-filter-by',
			'activity'      => 'activity-filter-by',
			'groups'        => 'groups-order-by',
			'blogs'         => 'blogs-order-by',
		);

		if ( isset( $ids[ $component['object'] ] ) ) {

			/**
			 * Filters the filter ID for BuddyPress Nouveau filters.
			 *
			 * @since 3.0.0
			 *
			 * @param string $value ID based on current component object.
			 */
			return apply_filters( 'bp_nouveau_get_filter_id', $ids[ $component['object'] ] );
		}

		return '';
	}

/**
 * Output data filter's label.
 *
 * @since 3.0.0
 */
function bp_nouveau_filter_label() {
	echo esc_html( bp_nouveau_get_filter_label() );
}
	/**
	 * Get data filter's label.
 	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	function bp_nouveau_get_filter_label() {
		$component = bp_nouveau_current_object();
		$label     = __( 'Order By:', 'buddypress' );

		if ( 'activity' === $component['object'] || 'friends' === $component['object'] ) {
			$label = __( 'Show:', 'buddypress' );
		}

		/**
		 * Filters the label for BuddyPress Nouveau filters.
		 *
		 * @since 3.0.0
		 *
		 * @param string $label     Label for BuddyPress Nouveau filter.
		 * @param array  $component The data filter's data-bp-filter attribute value.
		 */
		return apply_filters( 'bp_nouveau_get_filter_label', $label, $component );
	}

/**
 * Output data filter's data-bp-filter attribute value.
 *
 * @since 3.0.0
 */
function bp_nouveau_filter_component() {
	$component = bp_nouveau_current_object();
	echo esc_attr( $component['data_filter'] );
}

/**
 * Output the <option> of the data filter's <select> element.
 *
 * @since 3.0.0
 */
function bp_nouveau_filter_options() {
	echo bp_nouveau_get_filter_options();  // Escaped in inner functions.
}

	/**
	 * Get the <option> of the data filter's <select> element.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	function bp_nouveau_get_filter_options() {
		$output = '';

		if ( bp_nouveau_get_component_slug( 'notifications' ) === bp_current_component() ) {
			$output = bp_nouveau_get_notifications_filters();

		} else {
			$filters = bp_nouveau_get_component_filters();

			foreach ( $filters as $key => $value ) {
				$output .= sprintf( '<option value="%1$s">%2$s</option>%3$s',
					esc_attr( $key ),
					esc_html( $value ),
					PHP_EOL
				);
			}
		}

		return $output;
	}


/** Template tags for the Customizer ******************************************/

/**
 * Get a link to reach a specific section into the customizer
 *
 * @since 3.0.0
 *
 * @param array $args Optional. The argument to customize the Customizer link.
 *
 * @return string HTML.
 */
function bp_nouveau_get_customizer_link( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'capability' => 'bp_moderate',
			'object'     => 'user',
			'item_id'    => 0,
			'autofocus'  => '',
			'text'       => '',
		),
		'nouveau_get_customizer_link'
	);

	if ( empty( $r['capability'] ) || empty( $r['autofocus'] ) || empty( $r['text'] ) ) {
		return '';
	}

	if ( ! bp_current_user_can( $r['capability'] ) ) {
		return '';
	}

	$url = '';

	if ( bp_is_user() ) {
		$url = rawurlencode( bp_displayed_user_domain() );

	} elseif ( bp_is_group() ) {
		$url = rawurlencode( bp_get_group_permalink( groups_get_current_group() ) );

	} elseif ( ! empty( $r['object'] ) && ! empty( $r['item_id'] ) ) {
		if ( 'user' === $r['object'] ) {
			$url = rawurlencode( bp_core_get_user_domain( $r['item_id'] ) );

		} elseif ( 'group' === $r['object'] ) {
			$group = groups_get_group( array( 'group_id' => $r['item_id'] ) );

			if ( ! empty( $group->id ) ) {
				$url = rawurlencode( bp_get_group_permalink( $group ) );
			}
		}
	}

	if ( ! $url ) {
		return '';
	}

	$customizer_link = add_query_arg( array(
		'autofocus[section]' => $r['autofocus'],
		'url'                => $url,
	), admin_url( 'customize.php' ) );

	return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $customizer_link ), esc_html( $r['text'] ) );
}

/** Template tags for signup forms *******************************************/

/**
 * Fire specific hooks into the register template
 *
 * @since 3.0.0
 *
 * @param string $when   'before' or 'after'
 * @param string $prefix Use it to add terms before the hook name
 */
function bp_nouveau_signup_hook( $when = '', $prefix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	if ( $prefix ) {
		if ( 'page' === $prefix ) {
			$hook[] = 'register';
		} elseif ( 'steps' === $prefix ) {
			$hook[] = 'signup';
		}

		$hook[] = $prefix;
	}

	if ( 'page' !== $prefix && 'steps' !== $prefix ) {
		$hook[] = 'fields';
	}

	bp_nouveau_hook( $hook );
}

/**
 * Fire specific hooks into the activate template
 *
 * @since 3.0.0
 *
 * @param string $when   'before' or 'after'
 * @param string $prefix Use it to add terms before the hook name
 */
function bp_nouveau_activation_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	$hook[] = 'activate';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	if ( 'page' === $suffix ) {
		$hook[2] = 'activation';
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the signup form for the requested section
 *
 * @since 3.0.0
 *
 * @param string $section Optional. The section of fields to get 'account_details' or 'blog_details'.
 *                        Default: 'account_details'.
 */
function bp_nouveau_signup_form( $section = 'account_details' ) {
	$fields = bp_nouveau_get_signup_fields( $section );
	if ( ! $fields ) {
		return;
	}

	foreach ( $fields as $name => $attributes ) {
		if ( 'signup_password' === $name ) {
			?>
			<label for="pass1"><?php esc_html_e( 'Choose a Password (required)', 'buddypress' ); ?></label>
			<?php if ( isset( buddypress()->signup->errors['signup_password'] ) ) :
				nouveau_error_template( buddypress()->signup->errors['signup_password'] );
			endif; ?>

			<div class="user-pass1-wrap">
				<div class="wp-pwd">
					<div class="password-input-wrapper">
						<input type="password" data-reveal="1" name="signup_password" id="pass1" class="password-entry" size="24" value="" <?php bp_form_field_attributes( 'password', array( 'data-pw' => wp_generate_password( 12 ), 'aria-describedby' => 'pass-strength-result' ) ); ?> />
						<button type="button" class="button wp-hide-pw">
							<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
						</button>
					</div>
					<div id="pass-strength-result" aria-live="polite"><?php esc_html_e( 'Strength indicator', 'buddypress' ); ?></div>
				</div>
				<div class="pw-weak">
					<label>
						<input type="checkbox" name="pw_weak" class="pw-checkbox" />
						<?php esc_html_e( 'Confirm use of weak password', 'buddypress' ); ?>
					</label>
				</div>
			</div>
			<?php
		} elseif ( 'signup_password_confirm' === $name ) {
			?>
			<p class="user-pass2-wrap">
				<label for="pass2"><?php esc_html_e( 'Confirm new password', 'buddypress' ); ?></label><br />
				<input type="password" name="signup_password_confirm" id="pass2" class="password-entry-confirm" size="24" value="" <?php bp_form_field_attributes( 'password' ); ?> />
			</p>

			<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
			<?php
		} else {
			list( $label, $required, $value, $attribute_type, $type, $class ) = array_values( $attributes );

			// Text fields are using strings, radios are using their inputs
			$label_output = '<label for="%1$s">%2$s</label>';
			$id           = $name;
			$classes      = '';

			if ( $required ) {
				/* translators: Do not translate placeholders. 2 = form field name, 3 = "(required)". */
				$label_output = __( '<label for="%1$s">%2$s %3$s</label>', 'buddypress' );
			}

			// Output the label for regular fields
			if ( 'radio' !== $type ) {
				if ( $required ) {
					printf( $label_output, esc_attr( $name ), esc_html( $label ), __( '(required)', 'buddypress' ) );
				} else {
					printf( $label_output, esc_attr( $name ), esc_html( $label ) );
				}

				if ( ! empty( $value ) && is_callable( $value ) ) {
					$value = call_user_func( $value );
				}

			// Handle the specific case of Site's privacy differently
			} elseif ( 'signup_blog_privacy_private' !== $name ) {
				?>
					<span class="label">
						<?php esc_html_e( 'I would like my site to appear in search engines, and in public listings around this network.', 'buddypress' ); ?>
					</span>
				<?php
			}

			// Set the additional attributes
			if ( $attribute_type ) {
				$existing_attributes = array();

				if ( ! empty( $required ) ) {
					$existing_attributes = array( 'aria-required' => 'true' );

					/**
					 * The blog section is hidden, so let's avoid a browser warning
					 * and deal with the Blog section in Javascript.
					 */
					if ( $section !== 'blog_details' ) {
						$existing_attributes['required'] = 'required';
					}
				}

				$attribute_type = ' ' . bp_get_form_field_attributes( $attribute_type, $existing_attributes );
			}

			// Specific case for Site's privacy
			if ( 'signup_blog_privacy_public' === $name || 'signup_blog_privacy_private' === $name ) {
				$name      = 'signup_blog_privacy';
				$submitted = bp_get_signup_blog_privacy_value();

				if ( ! $submitted ) {
					$submitted = 'public';
				}

				$attribute_type = ' ' . checked( $value, $submitted, false );
			}

			// Do not run function to display errors for the private radio.
			if ( 'private' !== $value ) {

				/**
				 * Fetch & display any BP member registration field errors.
				 *
				 * Passes BP signup errors to Nouveau's template function to
				 * render suitable markup for error string.
				 */
				if ( isset( buddypress()->signup->errors[ $name ] ) ) {
					nouveau_error_template( buddypress()->signup->errors[ $name ] );
					$invalid = 'invalid';
				}
			}

			if ( isset( $invalid ) && isset( buddypress()->signup->errors[ $name ] ) ) {
				if ( ! empty( $class ) ) {
					$class = $class . ' ' . $invalid;
				} else {
					$class = $invalid;
				}
			}

			if ( $class ) {
				$class = sprintf(
					' class="%s"',
					esc_attr( join( ' ', array_map( 'sanitize_html_class', explode( ' ', $class ) ) ) )
				);
			}

			// Set the input.
			$field_output = sprintf(
				'<input type="%1$s" name="%2$s" id="%3$s" %4$s value="%5$s" %6$s />',
				esc_attr( $type ),
				esc_attr( $name ),
				esc_attr( $id ),
				$class,  // Constructed safely above.
				esc_attr( $value ),
				$attribute_type // Constructed safely above.
			);

			// Not a radio, let's output the field
			if ( 'radio' !== $type ) {
				if ( 'signup_blog_url' !== $name ) {
					print( $field_output );  // Constructed safely above.

				// If it's the signup blog url, it's specific to Multisite config.
				} elseif ( is_subdomain_install() ) {
					// Constructed safely above.
					printf(
						'%1$s %2$s . %3$s',
						is_ssl() ? 'https://' : 'http://',
						$field_output,
						bp_signup_get_subdomain_base()
					);

				// Subfolders!
				} else {
					printf(
						'%1$s %2$s',
						home_url( '/' ),
						$field_output  // Constructed safely above.
					);
				}

			// It's a radio, let's output the field inside the label
			} else {
				// $label_output and $field_output are constructed safely above.
				printf( $label_output, esc_attr( $name ), $field_output . ' ' . esc_html( $label ) );
			}
		}
	}

	/**
	 * Fires and displays any extra member registration details fields.
	 *
	 * This is a variable hook that depends on the current section.
	 *
	 * @since 1.9.0
	 */
	do_action( "bp_{$section}_fields" );
}

/**
 * Outputs the Privacy Policy acceptance area on the registration page.
 *
 * @since 4.0.0
 */
function bp_nouveau_signup_privacy_policy_acceptance_section() {
	$error = null;
	if ( isset( buddypress()->signup->errors['signup_privacy_policy'] ) ) {
		$error = buddypress()->signup->errors['signup_privacy_policy'];
	}

	?>

	<div class="privacy-policy-accept">
		<?php if ( $error ) : ?>
			<?php nouveau_error_template( $error ); ?>
		<?php endif; ?>

		<label for="signup-privacy-policy-accept">
			<input type="hidden" name="signup-privacy-policy-check" value="1" />

			<?php /* translators: link to Privacy Policy */ ?>
			<input type="checkbox" name="signup-privacy-policy-accept" id="signup-privacy-policy-accept" required /> <?php printf( esc_html__( 'I have read and agree to this site\'s %s.', 'buddypress' ), sprintf( '<a href="%s">%s</a>', esc_url( get_privacy_policy_url() ), esc_html__( 'Privacy Policy', 'buddypress' ) ) ); ?>
		</label>
	</div>

	<?php
}

/**
 * Output a submit button and the nonce for the requested action.
 *
 * @since 3.0.0
 *
 * @param string $action The action to get the submit button for. Required.
 */
function bp_nouveau_submit_button( $action, $object_id = 0 ) {
	$submit_data = bp_nouveau_get_submit_button( $action );
	if ( empty( $submit_data['attributes'] ) || empty( $submit_data['nonce'] ) ) {
		return;
	}

	if ( ! empty( $submit_data['before'] ) ) {

		/**
		 * Fires before display of the submit button.
		 *
		 * This is a dynamic filter that is dependent on the "before" value provided by bp_nouveau_get_submit_button().
		 *
		 * @since 3.0.0
		 */
		do_action( $submit_data['before'] );
	}

	$submit_input = sprintf( '<input type="submit" %s/>',
		bp_get_form_field_attributes( 'submit', $submit_data['attributes'] )  // Safe.
	);

	// Output the submit button.
	if ( isset( $submit_data['wrapper'] ) && false === $submit_data['wrapper'] ) {
		echo $submit_input;

	// Output the submit button into a wrapper.
	} else {
		printf( '<div class="submit">%s</div>', $submit_input );
	}

	$nonce = $submit_data['nonce'];
	if ( isset( $submit_data['nonce_placeholder_value'] ) ) {
		$nonce = sprintf( $nonce, $submit_data['nonce_placeholder_value'] );
	}

	if ( empty( $submit_data['nonce_key'] ) ) {
		wp_nonce_field( $nonce );
	} else {
		if ( $object_id ) {
			$submit_data['nonce_key'] .= '_' . (int) $object_id;
		}

		wp_nonce_field( $nonce, $submit_data['nonce_key'] );
	}

	if ( ! empty( $submit_data['after'] ) ) {

		/**
		 * Fires before display of the submit button.
		 *
		 * This is a dynamic filter that is dependent on the "after" value provided by bp_nouveau_get_submit_button().
		 *
		 * @since 3.0.0
		 */
		do_action( $submit_data['after'] );
	}
}

/**
 * Display supplemental error or feedback messages.
 *
 * This template handles in page error or feedback messages e.g signup fields
 * 'Username exists' type registration field error notices.
 *
 * @param string $message required: the message to display.
 * @param string $type optional: the type of error message e.g 'error'.
 *
 * @since 3.0.0
 */
function nouveau_error_template( $message = '', $type = '' ) {
	if ( ! $message ) {
		return;
	}

	$type = ( $type ) ? $type : 'error';
	?>

	<div class="<?php echo esc_attr( 'bp-messages bp-feedback ' . $type ); ?>">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php echo esc_html( $message ); ?></p>
	</div>

	<?php
}

/**
 * Checks whether the Activity RSS links should be output.
 *
 * @since 8.0.0
 *
 * @return bool True to output the Activity RSS link. False otherwise.
 */
function bp_nouveau_is_feed_enable() {
	$retval     = false;
	$bp_nouveau = bp_nouveau();

	if ( bp_is_active( 'activity' ) && 'activity' === bp_current_component() ) {
		if ( isset( $bp_nouveau->activity->current_rss_feed ) ) {
			$bp_nouveau->activity->current_rss_feed = array(
				'link'               => '',
				'tooltip'            => _x( 'RSS Feed', 'BP RSS Tooltip', 'buddypress' ),
				'screen_reader_text' => _x( 'RSS', 'BP RSS screen reader text', 'buddypress' ),
			);

			if ( ! bp_is_user() && ! bp_is_group() ) {
				$retval = bp_activity_is_feed_enable( 'sitewide' );

				if ( $retval ) {
					$bp_nouveau->activity->current_rss_feed['link'] = bp_get_sitewide_activity_feed_link();
				}
			} elseif ( bp_is_user_activity() ) {
				$retval = bp_activity_is_feed_enable( 'personal' );

				if ( $retval ) {
					$bp_nouveau->activity->current_rss_feed['link'] = trailingslashit( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'activity' ) . '/feed' );
				}

				if ( bp_is_active( 'friends' ) && bp_is_current_action( bp_nouveau_get_component_slug( 'friends' ) ) ) {
					$retval = bp_activity_is_feed_enable( 'friends' );

					if ( $retval ) {
						$bp_nouveau->activity->current_rss_feed['link'] = trailingslashit( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'activity' ) . '/' . bp_nouveau_get_component_slug( 'friends' ) . '/feed' );
					}
				} elseif ( bp_is_active( 'groups' ) && bp_is_current_action( bp_nouveau_get_component_slug( 'groups' ) ) ) {
					$retval = bp_activity_is_feed_enable( 'mygroups' );

					if ( $retval ) {
						$bp_nouveau->activity->current_rss_feed['link'] = trailingslashit( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'activity' ) . '/' . bp_nouveau_get_component_slug( 'groups' ) . '/feed' );
					}
				} elseif ( bp_activity_do_mentions() && bp_is_current_action( 'mentions' ) ) {
					$retval = bp_activity_is_feed_enable( 'mentions' );

					if ( $retval ) {
						$bp_nouveau->activity->current_rss_feed['link'] = trailingslashit( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'activity' ) . '/mentions/feed' );
					}
				} elseif ( bp_activity_can_favorite() && bp_is_current_action( 'favorites' ) ) {
					$retval = bp_activity_is_feed_enable( 'mentions' );

					if ( $retval ) {
						$bp_nouveau->activity->current_rss_feed['link'] = trailingslashit( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'activity' ) . '/favorites/feed' );
					}
				}
			}
		}
	}

	return $retval;
}
