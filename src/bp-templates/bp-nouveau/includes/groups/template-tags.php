<?php
/**
 * Groups Template tags
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Template tag to wrap all Legacy actions that was used
 * before the groups directory content
 *
 * @since 3.0.0
 */
function bp_nouveau_before_groups_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_before_directory_groups_page' );

	/**
	 * Fires before the display of the groups.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_groups' );

	/**
	 * Fires before the display of the groups content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_groups_content' );
}

/**
 * Template tag to wrap all Legacy actions that was used
 * after the groups directory content
 *
 * @since 3.0.0
 */
function bp_nouveau_after_groups_directory_content() {
	/**
	 * Fires and displays the group content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_directory_groups_content' );

	/**
	 * Fires after the display of the groups content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_groups_content' );

	/**
	 * Fires after the display of the groups.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_groups' );
}

/**
 * Fire specific hooks into the groups create template.
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_groups_create_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a create group hook
	$hook[] = 'create_group';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Fire specific hooks into the single groups templates.
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_group_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a group hook
	$hook[] = 'group';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Fire an isolated hook inside the groups loop
 *
 * @since 3.0.0
 */
function bp_nouveau_groups_loop_item() {
	/**
	 * Fires inside the listing of an individual group listing item.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_directory_groups_item' );
}

/**
 * Display the current group activity post form if needed
 *
 * @since 3.0.0
 */
function bp_nouveau_groups_activity_post_form() {
	/**
	 * Fires before the display of the group activity post form.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_group_activity_post_form' );

	if ( is_user_logged_in() && bp_group_is_member() ) {
		bp_get_template_part( 'activity/post-form' );
	}

	/**
	 * Fires after the display of the group activity post form.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_group_activity_post_form' );
}

/**
 * Prints the JS Templates to invite new members to join the Group.
 *
 * @since 10.0.0
 */
function bp_nouveau_group_print_invites_templates() {
	bp_get_template_part( 'common/js-templates/invites/index' );
}

/**
 * Prints the HTML placeholders to invite new members to join the Group.
 *
 * @since 10.0.0
 */
function bp_nouveau_group_print_invites_placeholders() {
	if ( bp_is_group_create() ) : ?>

		<h3 class="bp-screen-title creation-step-name">
			<?php esc_html_e( 'Invite Members', 'buddypress' ); ?>
		</h3>

	<?php else : ?>

		<h2 class="bp-screen-title">
			<?php esc_html_e( 'Invite Members', 'buddypress' ); ?>
		</h2>

	<?php endif; ?>

	<div id="group-invites-container">
		<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group invitations menu', 'buddypress' ); ?>"></nav>
		<div class="group-invites-column">
			<div class="subnav-filters group-subnav-filters bp-invites-filters"></div>
			<div class="bp-invites-feedback"></div>
			<div class="members bp-invites-content"></div>
		</div>
	</div>
	<?php
}

/**
 * Load the Group Invites UI.
 *
 * @since 3.0.0
 *
 * @return string HTML Output.
 */
function bp_nouveau_group_invites_interface() {
	/**
	 * Fires before the send invites content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_group_send_invites_content' );

	/**
	 * Get the templates to manage Group Members using the BP REST API.
	 *
	 * @since 10.0.0 Hook to the `wp_footer` action to print the JS templates.
	 */
	add_action( 'wp_footer', 'bp_nouveau_group_print_invites_templates' );
	bp_nouveau_group_print_invites_placeholders();

	/**
	 * Private hook to preserve backward compatibility with plugins needing the above placeholders to be located
	 * into: `bp-templates/bp-nouveau/buddypress/common/js-templates/invites/index.php`.
	 *
	 * @since 10.0.0
	 */
	do_action( '_bp_nouveau_group_print_invites_placeholders' );

	/**
	 * Fires after the send invites content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_group_send_invites_content' );
}

/**
 * Gets the displayed user group invites preferences
 *
 * @since 3.0.0
 * @since 4.4.0
 *
 * @param  int $user_id The user ID to check group invites preference for.
 * @return int          Returns 1 if user chose to restrict to friends, 0 otherwise.
 */
function bp_nouveau_groups_get_group_invites_setting( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	return (int) bp_get_user_meta( $user_id, '_bp_nouveau_restrict_invites_to_friends' );
}

/**
 * Load the requested Create Screen for the new group.
 *
 * @since 3.0.0
 */
function bp_nouveau_group_creation_screen() {
	return bp_nouveau_group_manage_screen();
}

/**
 * Load the requested Manage Screen for the current group.
 *
 * @since 3.0.0
 */

function bp_nouveau_group_manage_screen() {
	$action          = bp_action_variable( 0 );
	$is_group_create = bp_is_group_create();
	$output          = '';

	if ( $is_group_create ) {
		$action = bp_action_variable( 1 );
	}

	$screen_id = sanitize_file_name( $action );
	if ( ! bp_is_group_admin_screen( $screen_id ) && ! bp_is_group_creation_step( $screen_id ) ) {
		return;
	}

	if ( ! $is_group_create ) {
		/**
		 * Fires inside the group admin form and before the content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_before_group_admin_content' );

		$core_screen = bp_nouveau_group_get_core_manage_screens( $screen_id );

	// It's a group step, get the creation screens.
	} else {
		$core_screen = bp_nouveau_group_get_core_create_screens( $screen_id );
	}

	if ( ! $core_screen ) {
		if ( ! $is_group_create ) {
			/**
			 * Fires inside the group admin template.
			 *
			 * Allows plugins to add custom group edit screens.
			 *
			 * @since 1.1.0
			 */
			do_action( 'groups_custom_edit_steps' );

		// Else use the group create hook
		} else {
			/**
			 * Fires inside the group admin template.
			 *
			 * Allows plugins to add custom group creation steps.
			 *
			 * @since 1.1.0
			 */
			do_action( 'groups_custom_create_steps' );
		}

	// Else we load the core screen.
	} else {
		if ( ! empty( $core_screen['hook'] ) ) {
			/**
			 * Fires before the display of group delete admin.
			 *
			 * @since 1.1.0 For most hooks.
			 * @since 2.4.0 For the cover image hook.
			 */
			do_action( 'bp_before_' . $core_screen['hook'] );
		}

		$template = 'groups/single/admin/' . $screen_id;

		if ( ! empty( $core_screen['template'] ) ) {
			$template = $core_screen['template'];
		}

		bp_get_template_part( $template );

		if ( ! empty( $core_screen['hook'] ) ) {

			// Group's "Manage > Details" page.
			if ( 'group_details_admin' === $core_screen['hook'] ) {
				/**
				 * Fires after the group description admin details.
				 *
				 * @since 1.0.0
				 */
				do_action( 'groups_custom_group_fields_editable' );
			}

			/**
			 * Fires before the display of group delete admin.
			 *
			 * @since 1.1.0 For most hooks.
			 * @since 2.4.0 For the cover image hook.
			 */
			do_action( 'bp_after_' . $core_screen['hook'] );
		}

		if ( ! empty( $core_screen['nonce'] ) ) {
			if ( ! $is_group_create ) {
				$output = sprintf( '<p><input type="submit" value="%s" id="save" name="save" /></p>', esc_attr__( 'Save Changes', 'buddypress' ) );

				// Specific case for the delete group screen
				if ( 'delete-group' === $screen_id ) {
					$output = sprintf(
						'<div class="submit">
							<input type="submit" disabled="disabled" value="%s" id="delete-group-button" name="delete-group-button" />
						</div>',
						esc_attr__( 'Delete Group', 'buddypress' )
					);
				}
			}
		}
	}

	if ( $is_group_create ) {
		/**
		 * Fires before the display of the group creation step buttons.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_before_group_creation_step_buttons' );

		if ( 'crop-image' !== bp_get_avatar_admin_step() ) {
			$creation_step_buttons = '';

			if ( ! bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="button" value="%1$s" id="group-creation-previous" name="previous" onclick="%2$s" />',
					esc_attr__( 'Back to Previous Step', 'buddypress' ),
					"location.href='" . esc_js( esc_url_raw( bp_get_group_creation_previous_link() ) ) . "'"
				);
			}

			if ( ! bp_is_last_group_creation_step() && ! bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-next" name="save" />',
					esc_attr__( 'Next Step', 'buddypress' )
				);
			}

			if ( bp_is_first_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-create" name="save" />',
					esc_attr__( 'Create Group and Continue', 'buddypress' )
				);
			}

			if ( bp_is_last_group_creation_step() ) {
				$creation_step_buttons .= sprintf(
					'<input type="submit" value="%s" id="group-creation-finish" name="save" />',
					esc_attr__( 'Finish', 'buddypress' )
				);
			}

			// Set the output for the buttons
			$output = sprintf( '<div class="submit" id="previous-next">%s</div>', $creation_step_buttons );
		}

		/**
		 * Fires after the display of the group creation step buttons.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_group_creation_step_buttons' );
	}

	/**
	 * Avoid nested forms with the Backbone views for the group invites step.
	 */
	if ( 'group-invites' === bp_get_groups_current_create_step() ) {
		printf(
			'<form action="%s" method="post" enctype="multipart/form-data">',
			esc_url( bp_get_group_creation_form_action() )
		);
	}

	if ( ! empty( $core_screen['nonce'] ) ) {
		wp_nonce_field( $core_screen['nonce'] );
	}

	printf(
		'<input type="hidden" name="group-id" id="group-id" value="%s" />',
		$is_group_create ? esc_attr( bp_get_new_group_id() ) : esc_attr( bp_get_group_id() )
	);

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo $output;

	if ( ! $is_group_create ) {
		/**
		 * Fires inside the group admin form and after the content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_group_admin_content' );

	} else {
		/**
		 * Fires and displays the groups directory content.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_directory_groups_content' );
	}

	/**
	 * Avoid nested forms with the Backbone views for the group invites step.
	 */
	if ( 'group-invites' === bp_get_groups_current_create_step() ) {
		echo '</form>';
	}
}

/**
 * Output the action buttons for the displayed group
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_group_header_buttons( $args = array() ) {
	$bp_nouveau = bp_nouveau();

	$output = join( ' ', bp_nouveau_get_groups_buttons( $args ) );

	// On the group's header we need to reset the group button's global.
	if ( ! empty( $bp_nouveau->groups->group_buttons ) ) {
		unset( $bp_nouveau->groups->group_buttons );
	}

	ob_start();
	/**
	 * Fires in the group header actions section.
	 *
	 * @since 1.2.6
	 */
	do_action( 'bp_group_header_actions' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'classes' => array( 'item-buttons' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons inside the groups loop.
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_groups_loop_buttons( $args = array() ) {
	if ( empty( $GLOBALS['groups_template'] ) ) {
		return;
	}

	$args['type'] = 'loop';

	$output = join( ' ', bp_nouveau_get_groups_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the action section of an individual group listing item.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_directory_groups_actions' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons inside the invites loop of the displayed user.
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_groups_invite_buttons( $args = array() ) {
	if ( empty( $GLOBALS['groups_template'] ) ) {
		return;
	}

	$args['type'] = 'invite';

	$output = join( ' ', bp_nouveau_get_groups_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the member group item action markup.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_group_invites_item_action' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons inside the requests loop of the group's manage screen.
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_groups_request_buttons( $args = array() ) {
	if ( empty( $GLOBALS['requests_template'] ) ) {
		return;
	}

	$args['type'] = 'request';

	$output = join( ' ', bp_nouveau_get_groups_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the list of membership request actions.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_group_membership_requests_admin_item_action' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

/**
 * Output the action buttons inside the manage members loop of the group's manage screen.
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_groups_manage_members_buttons( $args = array() ) {
	if ( empty( $GLOBALS['members_template'] ) ) {
		return;
	}

	$args['type'] = 'manage_members';

	$output = join( ' ', bp_nouveau_get_groups_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the display of a member admin item in group management area.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_group_manage_members_admin_item' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	if ( ! $args ) {
		$args = array(
			'wrapper' => 'span',
			'classes' => array( 'small' ),
		);
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

	/**
	 * Get the action buttons for the current group in the loop,
	 * or the current displayed group.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
	 */
	function bp_nouveau_get_groups_buttons( $args = array() ) {
		$type = ( ! empty( $args['type'] ) ) ? $args['type'] : 'group';

		// @todo Not really sure why BP Legacy needed to do this...
		if ( 'group' === $type && is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$buttons = array();

		if ( ( 'loop' === $type || 'invite' === $type ) && isset( $GLOBALS['groups_template']->group ) ) {
			$group = $GLOBALS['groups_template']->group;
		} else {
			$group = groups_get_current_group();
		}

		if ( empty( $group->id ) ) {
			return $buttons;
		}

		/*
		 * If the 'container' is set to 'ul' set $parent_element to li,
		 * otherwise simply pass any value found in $args or set var false.
		 */
		if ( ! empty( $args['container'] ) && 'ul' === $args['container']  ) {
			$parent_element = 'li';
		} elseif ( ! empty( $args['parent_element'] ) ) {
			$parent_element = $args['parent_element'];
		} else {
			$parent_element = false;
		}

		if ( ! empty( $args['button_element'] ) ) {
			$button_element = $args['button_element'] ;
		} else {
			$button_element = 'a';
		}

		// If we pass through parent classes add them to $button array
		$parent_class = '';
		if ( ! empty( $args['parent_attr']['class'] ) ) {
			$parent_class = $args['parent_attr']['class'];
		}

		// Invite buttons on member's invites screen
		if ( 'invite' === $type ) {
			// Don't show button if not logged in or previously banned
			if ( ! is_user_logged_in() || bp_group_is_user_banned( $group ) || empty( $group->status ) ) {
				return $buttons;
			}

			// Setup Accept button attributes
			$buttons['accept_invite'] =  array(
				'id'                => 'accept_invite',
				'position'          => 5,
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'link_text'         => esc_html__( 'Accept', 'buddypress' ),
				'button_element'    => $button_element,
				'parent_attr'       => array(
					'id'    => '',
					'class' => $parent_class . ' ' . 'accept',
				),
				'button_attr'       => array(
					'id'    => '',
					'class' => 'button accept group-button accept-invite',
					'rel'   => '',
				),
			);

			// If button element set add nonce link to data-attr attr
			if ( 'button' === $button_element ) {
				$buttons['accept_invite']['button_attr']['data-bp-nonce'] = esc_url( bp_get_group_accept_invite_link() );
			} else {
				$buttons['accept_invite']['button_attr']['href'] = esc_url( bp_get_group_accept_invite_link() );
			}

			// Setup Reject button attributes
			$buttons['reject_invite'] = array(
				'id'                => 'reject_invite',
				'position'          => 15,
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'link_text'         => __( 'Reject', 'buddypress' ),
				'parent_attr'       => array(
					'id'    => '',
					'class' => $parent_class . ' ' . 'reject',
				),
				'button_element'    => $button_element,
				'button_attr'       => array(
					'id'    => '',
					'class' => 'button reject group-button reject-invite',
					'rel'   => '',
				),
			);

			// If button element set add nonce link to formaction attr
			if ( 'button' === $button_element ) {
				$buttons['reject_invite']['button_attr']['data-bp-nonce'] = esc_url( bp_get_group_reject_invite_link() );
			} else {
				$buttons['reject_invite']['button_attr']['href'] = esc_url( bp_get_group_reject_invite_link() );
			}

		// Request button for the group's manage screen
		} elseif ( 'request' === $type ) {
			// Setup Accept button attributes
			$buttons['group_membership_accept'] =  array(
				'id'                => 'group_membership_accept',
				'position'          => 5,
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'link_text'         => esc_html__( 'Accept', 'buddypress' ),
				'button_element'    => $button_element,
				'parent_attr'       => array(
					'id'    => '',
					'class' => $parent_class,
				),
				'button_attr'       => array(
					'id'    => '',
					'class' => 'button accept',
					'rel'   => '',
				),
			);

			// If button element set add nonce link to data-attr attr
			if ( 'button' === $button_element ) {
				$buttons['group_membership_accept']['button_attr']['data-bp-nonce'] = esc_url( bp_get_group_request_accept_link() );
			} else {
				$buttons['group_membership_accept']['button_attr']['href'] = esc_url( bp_get_group_request_accept_link() );
			}

			$buttons['group_membership_reject'] = array(
				'id'                => 'group_membership_reject',
				'position'          => 15,
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'button_element'    => $button_element,
				'link_text'         => __( 'Reject', 'buddypress' ),
				'parent_attr'       => array(
					'id'    => '',
					'class' => $parent_class,
				),
				'button_attr'       => array(
					'id'    => '',
					'class' => 'button reject',
					'rel'   => '',
				),
			);

			// If button element set add nonce link to data-attr attr
			if ( 'button' === $button_element ) {
				$buttons['group_membership_reject']['button_attr']['data-bp-nonce'] = esc_url( bp_get_group_request_reject_link() );
			} else {
				$buttons['group_membership_reject']['button_attr']['href'] = esc_url( bp_get_group_request_reject_link() );
			}

		/*
		 * Manage group members for the group's manage screen.
		 * The 'button_attr' keys 'href' & 'formaction' are set at the end of this array block
		 */
		} elseif ( 'manage_members' === $type && isset( $GLOBALS['members_template']->member->user_id ) ) {
			$user_id = $GLOBALS['members_template']->member->user_id;

			$buttons = array(
				'unban_member' => array(
					'id'                => 'unban_member',
					'position'          => 5,
					'component'         => 'groups',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'button_element'    => $button_element,
					'link_text'         => __( 'Remove Ban', 'buddypress' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_attr'       => array(
						'id'    => '',
						'class' => 'button confirm member-unban',
						'rel'   => '',
						'title' => '',
					),
				),
				'ban_member' => array(
					'id'                => 'ban_member',
					'position'          => 15,
					'component'         => 'groups',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'button_element'    => $button_element,
					'link_text'         => __( 'Kick &amp; Ban', 'buddypress' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_attr'       => array(
						'id'    => '',
						'class' => 'button confirm member-ban',
						'rel'   => '',
						'title' => '',
					),
				),
				'promote_mod' => array(
					'id'                => 'promote_mod',
					'position'          => 25,
					'component'         => 'groups',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_element'    => $button_element,
					'button_attr'       => array(
						'id'               => '',
						'class'            => 'button confirm member-promote-to-mod',
						'rel'              => '',
						'title'            => '',
					),
					'link_text'         => __( 'Promote to Mod', 'buddypress' ),
				),
				'promote_admin' => array(
					'id'                => 'promote_admin',
					'position'          => 35,
					'component'         => 'groups',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'button_element'    => $button_element,
					'link_text'         => __( 'Promote to Admin', 'buddypress' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_attr'       => array(
						'href'  => esc_url( bp_get_group_member_promote_admin_link() ),
						'id'    => '',
						'class' => 'button confirm member-promote-to-admin',
						'rel'   => '',
						'title' => '',
					),
				),
				'remove_member' => array(
					'id'                => 'remove_member',
					'position'          => 45,
					'component'         => 'groups',
					'must_be_logged_in' => true,
					'parent_element'    => $parent_element,
					'button_element'    => $button_element,
					'link_text'         => __( 'Remove from group', 'buddypress' ),
					'parent_attr'       => array(
						'id'    => '',
						'class' => $parent_class,
					),
					'button_attr'       => array(
						'id'    => '',
						'class' => 'button confirm',
						'rel'   => '',
						'title' => '',
					),
				),
			);

			// If 'button' element is set add the nonce link to data-attr attr, else add it to the href.
			if ( 'button' === $button_element ) {
				$buttons['unban_member']['button_attr']['data-bp-nonce'] = bp_get_group_member_unban_link( $user_id );
				$buttons['ban_member']['button_attr']['data-bp-nonce'] = bp_get_group_member_ban_link( $user_id );
				$buttons['promote_mod']['button_attr']['data-bp-nonce'] = bp_get_group_member_promote_mod_link();
				$buttons['promote_admin']['button_attr']['data-bp-nonce'] = bp_get_group_member_promote_admin_link();
				$buttons['remove_member']['button_attr']['data-bp-nonce'] = bp_get_group_member_remove_link( $user_id );
			} else {
				$buttons['unban_member']['button_attr']['href'] = bp_get_group_member_unban_link( $user_id );
				$buttons['ban_member']['button_attr']['href'] = bp_get_group_member_ban_link( $user_id );
				$buttons['promote_mod']['button_attr']['href'] = bp_get_group_member_promote_mod_link();
				$buttons['promote_admin']['button_attr']['href'] = bp_get_group_member_promote_admin_link();
				$buttons['remove_member']['button_attr']['href'] = bp_get_group_member_remove_link( $user_id );
			}

		// Membership button on groups loop or single group's header
		} else {
			$button_args = bp_groups_get_group_join_button_args( $group );

			if ( $button_args ) {
				// If we pass through parent classes merge those into the existing ones.
				if ( $parent_class ) {
					$parent_class .= ' ' . $button_args['wrapper_class'];
				}

				// The join or leave group header button should default to 'button'.
				// Reverse the earlier button var to set default as 'button' not 'a'.
				if ( empty( $args['button_element'] ) ) {
					$button_element = 'button';
				}

				$buttons['group_membership'] = array(
					'id'                => 'group_membership',
					'position'          => 5,
					'component'         => $button_args['component'],
					'must_be_logged_in' => $button_args['must_be_logged_in'],
					'block_self'        => $button_args['block_self'],
					'parent_element'    => $parent_element,
					'button_element'    => $button_element,
					'link_text'         => $button_args['link_text'],
					'link_title'        => $button_args['link_title'],
					'parent_attr'       => array(
							'id'    => $button_args['wrapper_id'],
							'class' => $parent_class,
					),
					'button_attr'       => array(
						'id'    => ! empty( $button_args['link_id'] ) ? $button_args['link_id'] : '',
						'class' => $button_args['link_class'] . ' button',
						'rel'   => ! empty( $button_args['link_rel'] ) ? $button_args['link_rel'] : '',
						'title' => '',
					),
				);

				// If button element set add nonce 'href' link to data-attr attr.
				if ( 'button' === $button_element ) {
					$buttons['group_membership']['button_attr']['data-bp-nonce'] = $button_args['link_href'];
				} else {
					// Else this is an anchor so use an 'href' attr.
					$buttons['group_membership']['button_attr']['href'] = $button_args['link_href'];
				}
			}
		}

		/**
		 * Filter to add your buttons, use the position argument to choose where to insert it.
		 *
		 * @since 3.0.0
		 * @since 9.0.0 Adds the `$args` parameter to the filter.
		 *
		 * @param array  $buttons The list of buttons.
		 * @param int    $group   The current group object.
		 * @param string $type    Whether we're displaying a groups loop or a groups single item.
		 * @param array  $args    Button arguments.
		 */
		$buttons_group = apply_filters( 'bp_nouveau_get_groups_buttons', $buttons, $group, $type, $args );
		if ( ! $buttons_group ) {
			return array();
		}

		// It's the first entry of the loop, so build the Group and sort it
		if ( ! isset( bp_nouveau()->groups->group_buttons ) || ! is_a( bp_nouveau()->groups->group_buttons, 'BP_Buttons_Group' ) ) {
			$sort = true;
			bp_nouveau()->groups->group_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group
		} else {
			$sort = false;
			bp_nouveau()->groups->group_buttons->update( $buttons_group );
		}

		$return = bp_nouveau()->groups->group_buttons->get( $sort );

		if ( ! $return ) {
			return array();
		}

		// Remove buttons according to the user's membership type.
		if ( 'manage_members' === $type && isset( $GLOBALS['members_template'] ) ) {
			if ( bp_get_group_member_is_banned() ) {
				unset( $return['ban_member'], $return['promote_mod'], $return['promote_admin'] );
			} else {
				unset( $return['unban_member'] );
			}
		}

		/**
		 * Leave a chance to adjust the $return
		 *
		 * @since 3.0.0
		 *
		 * @param array  $return  The list of buttons.
		 * @param int    $group   The current group object.
		 * @parem string $type    Whether we're displaying a groups loop or a groups single item.
		 */
		do_action_ref_array( 'bp_nouveau_return_groups_buttons', array( &$return, $group, $type ) );

		return $return;
	}

/**
 * Does the group has metas or a specific meta value.
 *
 * @since 3.0.0
 * @since 3.2.0 Adds the $meta_key argument.
 *
 * @param  string $meta_key The key of the meta to check the value for.
 * @return bool             True if the group has meta. False otherwise.
 */
function bp_nouveau_group_has_meta( $meta_key = '' ) {
	if ( ! $meta_key ) {
		$meta_keys = array( 'status', 'count' );
	} else {
		$meta_keys = array( $meta_key );
	}

	$group_meta = bp_nouveau_get_group_meta( $meta_keys );
	$group_meta = array_filter( $group_meta );

	return ! empty( $group_meta );
}

/**
 * Does the group have extra meta?
 *
 * @since 3.0.0
 *
 * @return bool True if the group has meta. False otherwise.
 */
function bp_nouveau_group_has_meta_extra() {
	return false !== bp_nouveau_get_hooked_group_meta();
}

/**
 * Display the group meta.
 *
 * @since 3.0.0
 * @deprecated 7.0.0 Use bp_nouveau_the_group_meta()
 * @see bp_nouveau_the_group_meta()
 *
 * @return string HTML Output.
 */
function bp_nouveau_group_meta() {
	_deprecated_function( __FUNCTION__, '7.0.0', 'bp_nouveau_the_group_meta()' );
	$group_meta = new BP_Nouveau_Group_Meta();

	if ( ! bp_is_group() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $group_meta->meta;
	} else {
		return $group_meta;
	}
}

/**
 * Outputs or returns the group meta(s).
 *
 * @since 7.0.0
 *
 * @param array $args {
 *     Optional. An array of arguments.
 *
 *     @type array   $keys      The list of template meta keys.
 *     @type string  $delimeter The delimeter to use in case there is more than
 *                              one key to output.
 *     @type boolean $echo      True to output the template meta value. False otherwise.
 * }
 * @return string HTML Output.
 */
function bp_nouveau_the_group_meta( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'keys'      => array(),
			'delimeter' => '/',
			'echo'      => true,
		),
		'nouveau_the_group_meta'
	);

	$group_meta = (array) bp_nouveau_get_group_meta( $r['keys'] );

	if ( ! $group_meta ) {
		return;
	}

	$meta = '';
	if ( 1 < count( $group_meta ) ) {
		$group_meta = array_filter( $group_meta );
		$meta       = join( ' ' . $r['delimeter'] . ' ', array_map( 'esc_html', $group_meta ) );
	} else {
		$meta = reset( $group_meta );
	}

	if ( ! $r['echo'] ) {
		return $meta;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo $meta;
}

	/**
	 * Get the group template meta.
	 *
	 * @since 3.0.0
	 * @since 7.0.0 Adds the `$keys` parameter.
	 *
	 * @param array $keys One or more template meta keys to populate with their values.
	 *                    Possible keys are `status`, `count`, `group_type_list`, `description`, `extra`.
	 * @return array      The corresponding group template meta values.
	 */
	function bp_nouveau_get_group_meta( $keys = array() ) {
		$keys       = (array) $keys;
		$group      = false;
		$group_meta = array();
		$is_group   = bp_is_group();

		if ( isset( $GLOBALS['groups_template']->group ) ) {
			$group = $GLOBALS['groups_template']->group;
		} else {
			$group = groups_get_current_group();
		}

		if ( ! $group ) {
			return '';
		}

		if ( ! $keys && ! $is_group ) {
			$keys = array( 'status', 'count' );
		}

		foreach ( $keys as $key ) {
			switch ( $key ) {
				case 'status' :
					$group_meta['status'] = bp_get_group_type( $group );
					break;

				case 'count' :
					$group_meta['count'] = bp_get_group_member_count( $group );
					break;

				case 'group_type_list' :
					$group_meta['group_type_list'] = bp_get_group_type_list( $group->id );
					break;

				case 'description' :
					$group_meta['description'] = bp_get_group_description( $group );
					break;

				case 'extra' :
					$group_meta['extra'] = '';

					if ( $is_group ) {
						$group_meta['extra'] = bp_nouveau_get_hooked_group_meta();
					}
					break;
			}
		}

		/**
		 * Filter to add/remove Group template meta.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $group_meta The list of meta to output.
		 * @param object $group      The current Group of the loop object.
		 * @param bool   $is_group   True if a single group is displayed. False otherwise.
		 */
		return apply_filters( 'bp_nouveau_get_group_meta', $group_meta, $group, $is_group );
	}

/**
 * Load the appropriate content for the single group pages
 *
 * @since 3.0.0
 */
function bp_nouveau_group_template_part() {
	/**
	 * Fires before the display of the group home body.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_group_body' );

	$bp_is_group_home = bp_is_group_home();

	if ( $bp_is_group_home && ! bp_current_user_can( 'groups_access_group' ) ) {
		/**
		 * Fires before the display of the group status message.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_before_group_status_message' );
		?>

		<div id="message" class="info">
			<p><?php bp_group_status_message(); ?></p>
		</div>

		<?php

		/**
		 * Fires after the display of the group status message.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bp_after_group_status_message' );

	// We have a front template, Use BuddyPress function to load it.
	} elseif ( $bp_is_group_home && false !== bp_groups_get_front_template() ) {
		bp_groups_front_template_part();

	// Otherwise use BP_Nouveau template hierarchy
	} else {
		$template = 'plugins';

		// the home page
		if ( $bp_is_group_home ) {
			if ( bp_is_active( 'activity' ) ) {
				$template = 'activity';
			} else {
				$template = 'members';
			}

		// Not the home page
		} elseif ( bp_is_group_admin_page() ) {
			$template = 'admin';
		} elseif ( bp_is_group_activity() ) {
			$template = 'activity';
		} elseif ( bp_is_group_members() ) {
			$template = 'members';
		} elseif ( bp_is_group_invites() ) {
			$template = 'send-invites';
		} elseif ( bp_is_group_membership_request() ) {
			$template = 'request-membership';
		}

		bp_nouveau_group_get_template_part( $template );
	}

	/**
	 * Fires after the display of the group home body.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_group_body' );
}

/**
 * Use the appropriate Group header and enjoy a template hierarchy
 *
 * @since 3.0.0
 */
function bp_nouveau_group_header_template_part() {
	$template = 'group-header';

	if ( bp_group_use_cover_image_header() ) {
		$template = 'cover-image-header';
	}

	/**
	 * Fires before the display of a group's header.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_group_header' );

	// Get the template part for the header
	bp_nouveau_group_get_template_part( $template );

	/**
	 * Fires after the display of a group's header.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_group_header' );

	bp_nouveau_template_notices();
}

/**
 * Get a link to set the Group's default front page and directly
 * reach the Customizer section where it's possible to do it.
 *
 * @since 3.0.0
 *
 * @return string HTML Output
 */
function bp_nouveau_groups_get_customizer_option_link() {
	return bp_nouveau_get_customizer_link(
		array(
			'object'    => 'group',
			'autofocus' => 'bp_nouveau_group_front_page',
			'text'      => __( 'Groups default front page', 'buddypress' ),
		)
	);
}

/**
 * Get a link to set the Group's front page widgets and directly
 * reach the Customizer section where it's possible to do it.
 *
 * @since 3.0.0
 *
 * @return string HTML Output
 */
function bp_nouveau_groups_get_customizer_widgets_link() {
	return bp_nouveau_get_customizer_link(
		array(
			'object'    => 'group',
			'autofocus' => 'sidebar-widgets-sidebar-buddypress-groups',
			'text'      => __( '(BuddyPress) Widgets', 'buddypress' ),
		)
	);
}

/**
 * Output the group description excerpt
 *
 * @since 3.0.0
 *
 * @param object $group Optional. The group being referenced.
 *                      Defaults to the group currently being iterated on in the groups loop.
 * @param int $length   Optional. Length of returned string, including ellipsis. Default: 100.
 */
function bp_nouveau_group_description_excerpt( $group = null, $length = null ) {
	$group = bp_get_group( $group );

	// Escaping is made in `bp-groups/bp-groups-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters(
		/** This filter is documented in bp-groups/bp-groups-template.php. */
		'bp_get_group_description_excerpt',
		bp_nouveau_get_group_description_excerpt( $group, $length ),
		$group
	);
}

/**
 * Filters the excerpt of a group description.
 *
 * Checks if the group loop is set as a 'Grid' layout and returns a reduced excerpt.
 *
 * @since 3.0.0
 *
 * @param object $group Optional. The group being referenced. Defaults to the group currently being
 *                      iterated on in the groups loop.
 * @param int $length   Optional. Length of returned string, including ellipsis. Default: 100.
 *
 * @return string Excerpt.
 */
function bp_nouveau_get_group_description_excerpt( $group = null, $length = null ) {
	global $groups_template;

	if ( ! $group ) {
		$group =& $groups_template->group;
	}

	/**
	 * If this is a grid layout but no length is passed in set a shorter
	 * default value otherwise use the passed in value.
	 * If not a grid then the BP core default is used or passed in value.
	 */
	if ( bp_nouveau_loop_is_grid() && 'groups' === bp_current_component() ) {
		if ( ! $length ) {
			$length = 100;
		} else {
			$length = $length;
		}
	}

	if ( $length ) {
		$excerpt = bp_create_excerpt( $group->description, $length );
	} else {
		$excerpt = bp_create_excerpt( $group->description );
	}

	/**
	 * Filters the excerpt of a group description.
	 *
	 * @since 3.0.0
	 *
	 * @param string $excerpt Excerpt of a group description.
	 * @param object $group   Object for group whose description is made into an excerpt.
	 */
	return apply_filters( 'bp_nouveau_get_group_description_excerpt', $excerpt, $group );
}

/**
 * Output "checked" attribute to determine if the group type should be checked.
 *
 * @since 3.2.0
 *
 * @param object $type Group type object. See bp_groups_get_group_type_object().
 */
function bp_nouveau_group_type_checked( $type = null ) {
	if ( ! is_object( $type ) ) {
		return;
	}

	// Group creation screen requires a different check.
	if ( bp_is_group_create() ) {
		checked( true, ! empty( $type->create_screen_checked ) );
	} elseif ( bp_is_group() ) {
		checked( bp_groups_has_group_type( bp_get_current_group_id(), $type->name ) );
	}
}

/**
 * Adds the "Notify group members of these changes" checkbox to the Manage > Details panel.
 *
 * See #7837 for background on why this technique is required.
 *
 * @since 4.0.0
 */
function bp_nouveau_add_notify_group_members_checkbox() {
	printf( '<p class="bp-controls-wrap">
		<label for="group-notify-members" class="bp-label-text">
			<input type="checkbox" name="group-notify-members" id="group-notify-members" value="1" /> %s
		</label>
	</p>', esc_html__( 'Notify group members of these changes via email', 'buddypress' ) );
}
add_action( 'groups_custom_group_fields_editable', 'bp_nouveau_add_notify_group_members_checkbox', 20 );
