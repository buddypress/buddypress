<?php
/**
 * Activity Template tags
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Before Activity's directory content legacy do_action hooks wrapper
 *
 * @since 3.0.0
 */
function bp_nouveau_before_activity_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_before_directory_activity' );

	/**
	 * Fires before the activity directory display content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_directory_activity_content' );
}

/**
 * After Activity's directory content legacy do_action hooks wrapper
 *
 * @since 3.0.0
 */
function bp_nouveau_after_activity_directory_content() {
	/**
	 * Fires after the display of the activity list.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_after_directory_activity_list' );

	/**
	 * Fires inside and displays the activity directory display content.
	 */
	do_action( 'bp_directory_activity_content' );

	/**
	 * Fires after the activity directory display content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_directory_activity_content' );

	/**
	 * Fires after the activity directory listing.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_after_directory_activity' );
}

/**
 * Prints the JS Templates used to render the Activity Post Form.
 *
 * @since 10.0.0
 */
function bp_nouveau_activity_print_post_form_templates() {
	bp_get_template_part( 'common/js-templates/activity/form' );
}

/**
 * Enqueue needed scripts for the Activity Post Form
 *
 * @since 3.0.0
 * @since 5.0.0 Move the `bp_before_activity_post_form` hook inside the Activity post form.
 */
function bp_nouveau_before_activity_post_form() {
	if ( bp_nouveau_current_user_can( 'publish_activity' ) ) {
		wp_enqueue_script( 'bp-nouveau-activity-post-form' );

		/**
		 * Get the templates to manage Group Members using the BP REST API.
		 *
		 * @since 10.0.0 Hook to the `wp_footer` action to print the JS templates.
		 */
		add_action( 'wp_footer', 'bp_nouveau_activity_print_post_form_templates' );
	}
}

/**
 * Load JS Templates for the Activity Post Form
 *
 * @since 3.0.0
 */
function bp_nouveau_after_activity_post_form() {
	/**
	 * Fires after the activity post form.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_activity_post_form' );
}

/**
 * Display the displayed user activity post form if needed
 *
 * @since 3.0.0
 *
 * @return string HTML.
 */
function bp_nouveau_activity_member_post_form() {

	/**
	 * Fires before the display of the member activity post form.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_member_activity_post_form' );

	if ( is_user_logged_in() && bp_is_my_profile() && ( ! bp_current_action() || bp_is_current_action( 'just-me' ) ) ) {
		bp_get_template_part( 'activity/post-form' );
	}

	/**
	 * Fires after the display of the member activity post form.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_member_activity_post_form' );
}

/**
 * Fire specific hooks into the activity entry template
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_activity_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a activity entry hook
	$hook[] = 'activity';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the `data-bp-activity-id` attribute.
 *
 * @since 10.0.0
 */
function bp_nouveau_activity_data_attribute_id() {
	printf( 'data-bp-activity-id="%d"', (int) bp_get_activity_id() );
}

/**
 * Output the `data-bp-activity-comment-id` attribute.
 *
 * @since 12.0.0
 */
function bp_nouveau_activity_comment_data_attribute_id() {
	printf( 'data-bp-activity-comment-id="%d"', (int) bp_get_activity_comment_id() );
}

/**
 * Checks if an activity of the loop has some content.
 *
 * @since 3.0.0
 *
 * @return bool True if the activity has some content. False Otherwise.
 */
function bp_nouveau_activity_has_content() {
	return bp_activity_has_content() || (bool) has_action( 'bp_activity_entry_content' );
}

/**
 * Output the Activity content into the loop.
 *
 * @since 3.0.0
 */
function bp_nouveau_activity_content() {
	if ( bp_activity_has_content() ) {
		bp_activity_content_body();
	}

	/**
	 * Fires after the display of an activity entry content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_entry_content' );
}

/**
 * Output the Activity timestamp into the bp-timestamp attribute.
 *
 * @since 3.0.0
 */
function bp_nouveau_activity_timestamp() {
	echo esc_attr( bp_nouveau_get_activity_timestamp() );
}

	/**
	 * Get the Activity timestamp.
	 *
	 * @since 3.0.0
	 *
	 * @return integer The Activity timestamp.
	 */
	function bp_nouveau_get_activity_timestamp() {
		/**
		 * Filter here to edit the activity timestamp.
		 *
		 * @since 3.0.0
		 *
		 * @param integer $value The Activity timestamp.
		 */
		return apply_filters( 'bp_nouveau_get_activity_timestamp', strtotime( bp_get_activity_date_recorded() ) );
	}

/**
 * Output the action buttons inside an Activity Loop.
 *
 * @since 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_activity_entry_buttons( $args = array() ) {
	$output = join( ' ', bp_nouveau_get_activity_entry_buttons( $args ) );

	ob_start();

	/**
	 * Fires at the end of the activity entry meta data area.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_entry_meta' );

	$output .= ob_get_clean();

	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'classes' => array( 'activity-meta' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

	/**
	 * Get the action buttons inside an Activity Loop.
	 *
	 * @todo This function is too large and needs refactoring and reviewing.
	 * @since 3.0.0
	 *
	 * @global BP_Activity_Template $activities_template The Activity template loop.
	 *
	 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
	 * @return array      Activity action buttons used into an Activity Loop.
	 */
	function bp_nouveau_get_activity_entry_buttons( $args ) {
		$buttons = array();

		if ( ! isset( $GLOBALS['activities_template'] ) ) {
			return $buttons;
		}

		$activity_id    = bp_get_activity_id();
		$activity_type  = bp_get_activity_type();
		$parent_element = '';
		$button_element = 'a';

		if ( ! $activity_id ) {
			return $buttons;
		}

		/*
		 * If the container is set to 'ul' force the $parent_element to 'li',
		 * else use parent_element args if set.
		 *
		 * This will render li elements around anchors/buttons.
		 */
		if ( isset( $args['container'] ) && 'ul' === $args['container'] ) {
			$parent_element = 'li';
		} elseif ( ! empty( $args['parent_element'] ) ) {
			$parent_element = $args['parent_element'];
		}

		$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

		/*
		 * If we have a arg value for $button_element passed through
		 * use it to default all the $buttons['button_element'] values
		 * otherwise default to 'a' (anchor)
		 * Or override & hardcode the 'element' string on $buttons array.
		 *
		 */
		if ( ! empty( $args['button_element'] ) ) {
			$button_element = $args['button_element'];
		}

		/*
		 * The view conversation button and the comment one are sharing
		 * the same id because when display_comments is on stream mode,
		 * it is not possible to comment an activity comment and as we
		 * are updating the links to avoid sorting the activity buttons
		 * for each entry of the loop, it's a convenient way to make
		 * sure the right button will be displayed.
		 */
		if ( $activity_type === 'activity_comment' ) {
			$buttons['activity_conversation'] = array(
				'id'                => 'activity_conversation',
				'position'          => 5,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => false,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'class'           => 'button view bp-secondary-action bp-tooltip',
					'data-bp-tooltip' => __( 'View Conversation', 'buddypress' ),
					),
				'link_text' => sprintf(
					'<span class="bp-screen-reader-text">%1$s</span>',
					__( 'View Conversation', 'buddypress' )
				),
			);

			// If button element set add url link to data-attr
			if ( 'button' === $button_element ) {
				$buttons['activity_conversation']['button_attr']['data-bp-url'] = bp_get_activity_thread_permalink();
			} else {
				$buttons['activity_conversation']['button_attr']['href'] = bp_get_activity_thread_permalink();
				$buttons['activity_conversation']['button_attr']['role'] = 'button';
			}

		/*
		 * We always create the Button to make sure we always have the right numbers of buttons,
		 * no matter the previous activity had less.
		 */
		} else {
			$buttons['activity_conversation'] =  array(
				'id'                => 'activity_conversation',
				'position'          => 5,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => true,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'id'              => 'acomment-comment-' . $activity_id,
					'class'           => 'button acomment-reply bp-primary-action bp-tooltip',
					'data-bp-tooltip' => _x( 'Comment', 'button', 'buddypress' ),
					'aria-expanded'   => 'false',
				),
				'link_text'  => sprintf(
					'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
					_x( 'Comment', 'link', 'buddypress' ),
					esc_html( bp_activity_get_comment_count() )
				),
			);

			// If button element set add href link to data-attr
			if ( 'button' === $button_element ) {
				$buttons['activity_conversation']['button_attr']['data-bp-url'] = bp_get_activity_comment_link();
			} else {
				$buttons['activity_conversation']['button_attr']['href'] = bp_get_activity_comment_link();
				$buttons['activity_conversation']['button_attr']['role'] = 'button';
			}

		}

		if ( bp_activity_can_favorite() ) {

			// If button element set attr needs to be data-* else 'href'
			if ( 'button' === $button_element ) {
				$key = 'data-bp-nonce';
			} else {
				$key = 'href';
			}

			if ( ! bp_get_activity_is_favorite() ) {
				$fav_args = array(
					'parent_element'   => $parent_element,
					'parent_attr'      => $parent_attr,
					'button_element'   => $button_element,
					'link_class'       => 'button fav bp-secondary-action bp-tooltip',
					'data_bp_tooltip'  => __( 'Mark as Favorite', 'buddypress' ),
					'link_text'        => __( 'Mark as Favorite', 'buddypress' ),
					'aria-pressed'     => 'false',
					'link_attr'        => bp_get_activity_favorite_link(),
				);

			} else {
				$fav_args = array(
					'parent_element'  => $parent_element,
					'parent_attr'     => $parent_attr,
					'button_element'  => $button_element,
					'link_class'      => 'button unfav bp-secondary-action bp-tooltip',
					'data_bp_tooltip' => __( 'Remove Favorite', 'buddypress' ),
					'link_text'       => __( 'Remove Favorite', 'buddypress' ),
					'aria-pressed'    => 'true',
					'link_attr'       => bp_get_activity_unfavorite_link(),
				);
			}

			$buttons['activity_favorite'] =  array(
				'id'                => 'activity_favorite',
				'position'          => 15,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => true,
				'button_element'    => $fav_args['button_element'],
				'link_text'         => sprintf( '<span class="bp-screen-reader-text">%1$s</span>', esc_html( $fav_args['link_text'] ) ),
				'button_attr'       => array(
					$key              => $fav_args['link_attr'],
					'class'           => $fav_args['link_class'],
					'data-bp-tooltip' => $fav_args['data_bp_tooltip'],
					'aria-pressed'    => $fav_args['aria-pressed'],
				),
			);
		}

		if ( bp_activity_type_supports( $activity_type, 'likes' ) ) {

			if ( bp_activity_is_liked() ) {
				$like_props = array(
					'action'  => 'remove',
					'id'      => 'alike-dislike-' . $activity_id,
					'class'   => 'alike-remove',
					'tooltip' => _x( 'Dislike', 'button', 'buddypress' ),
					'text'    => _x( 'Liked', 'link', 'buddypress' ),
				);
			} else {
				$like_props = array(
					'action'  => 'add',
					'id'      => 'alike-like-' . $activity_id,
					'class'   => 'alike-add',
					'tooltip' => _x( 'Like', 'button', 'buddypress' ),
					'text'    => _x( 'Like', 'link', 'buddypress' ),
				);
			}

			$buttons['activity_like'] =  array(
				'id'                => 'activity_like',
				'position'          => 20,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => true,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'id'              => $like_props['id'],
					'class'           => sprintf( 'button %s bp-primary-action bp-tooltip', $like_props['class'] ),
					'data-bp-tooltip' => $like_props['tooltip'],
					'aria-expanded'   => 'false',
				),
				'link_text'  => sprintf(
					'<span class="bp-screen-reader-text">%1$s</span> <span class="like-count">%2$s</span>',
					$like_props['text'],
					esc_html( bp_activity_get_like_count() )
				),
			);

			// If button element set add href link to data-attr
			if ( 'button' === $button_element ) {
				$buttons['activity_like']['button_attr']['data-bp-url'] = bp_get_activity_like_link( $like_props['action'] );
			} else {
				$buttons['activity_like']['button_attr']['href'] = bp_get_activity_like_link( $like_props['action'] );
				$buttons['activity_like']['button_attr']['role'] = 'button';
			}
		}

		// The delete button is always created, and removed later on if needed.
		$delete_args = array();

		/*
		 * As the delete link is filterable we need this workaround
		 * to try to intercept the edits the filter made and build
		 * a button out of it.
		 */
		if ( has_filter( 'bp_get_activity_delete_link' ) ) {
			preg_match( '/<a\s[^>]*>(.*)<\/a>/siU', bp_get_activity_delete_link(), $link );

			if ( ! empty( $link[0] ) && ! empty( $link[1] ) ) {
				$delete_args['link_text'] = $link[1];
				$subject = str_replace( $delete_args['link_text'], '', $link[0] );
			}

			preg_match_all( '/([\w\-]+)=([^"\'> ]+|([\'"]?)(?:[^\3]|\3+)+?\3)/', $subject, $attrs );

			if ( ! empty( $attrs[1] ) && ! empty( $attrs[2] ) ) {
				foreach ( $attrs[1] as $key_attr => $key_value ) {
					$delete_args[ 'link_'. $key_value ] = trim( $attrs[2][$key_attr], '"' );
				}
			}

			$delete_args = bp_parse_args(
				$delete_args,
				array(
					'link_text'   => '',
					'button_attr' => array(
						'link_id'         => '',
						'link_href'       => '',
						'link_class'      => '',
						'link_rel'        => 'nofollow',
						'data_bp_tooltip' => '',
					),
				),
				'nouveau_get_activity_entry_buttons'
			);
		}

		if ( empty( $delete_args['link_href'] ) ) {
			$delete_args = array(
				'button_element'  => $button_element,
				'link_id'         => '',
				'link_class'      => 'button item-button bp-secondary-action bp-tooltip delete-activity confirm',
				'link_rel'        => 'nofollow',
				'data_bp_tooltip' => _x( 'Delete', 'button', 'buddypress' ),
				'link_text'       => _x( 'Delete', 'button', 'buddypress' ),
				'link_href'       => bp_get_activity_delete_url(),
			);

			// If button element set add nonce link to data-attr attr
			if ( 'button' === $button_element ) {
				$delete_args['data-attr'] = bp_get_activity_delete_url();
				$delete_args['link_href'] = '';
			} else {
				$delete_args['link_href'] = bp_get_activity_delete_url();
				$delete_args['data-attr'] = '';
			}
		}

		$buttons['activity_delete'] = array(
			'id'                => 'activity_delete',
			'position'          => 35,
			'component'         => 'activity',
			'parent_element'    => $parent_element,
			'parent_attr'       => $parent_attr,
			'must_be_logged_in' => true,
			'button_element'    => $button_element,
			'button_attr'       => array(
				'id'              => $delete_args['link_id'],
				'href'            => $delete_args['link_href'],
				'class'           => $delete_args['link_class'],
				'data-bp-tooltip' => $delete_args['data_bp_tooltip'],
				'data-bp-nonce'   => $delete_args['data-attr'] ,
			),
			'link_text'  => sprintf( '<span class="bp-screen-reader-text">%s</span>', esc_html( $delete_args['data_bp_tooltip'] ) ),
		);

		// Add the Spam Button if supported
		if ( bp_is_akismet_active() && isset( buddypress()->activity->akismet ) && bp_activity_user_can_mark_spam() ) {
			$buttons['activity_spam'] = array(
				'id'                => 'activity_spam',
				'position'          => 45,
				'component'         => 'activity',
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'must_be_logged_in' => true,
				'button_element'    => $button_element,
				'button_attr'       => array(
					'class'           => 'bp-secondary-action spam-activity confirm button item-button bp-tooltip',
					'id'              => 'activity_make_spam_' . $activity_id,
					'data-bp-tooltip' => _x( 'Spam', 'button', 'buddypress' ),
					),
				'link_text'  => sprintf(
					/** @todo: use a specific css rule for this *************************************************************/
					'<span class="dashicons dashicons-flag" style="color:#a00;vertical-align:baseline;width:18px;height:18px" aria-hidden="true"></span><span class="bp-screen-reader-text">%s</span>',
					esc_html_x( 'Spam', 'button', 'buddypress' )
				),
			);

			// If button element, add nonce link to data attribute.
			if ( 'button' === $button_element ) {
				$data_element = 'data-bp-nonce';
			} else {
				$data_element = 'href';
			}

			$buttons['activity_spam']['button_attr'][ $data_element ] = wp_nonce_url(
				bp_rewrites_get_url(
					array(
						'component_id'                 => 'activity',
						'single_item_action'           => 'spam',
						'single_item_action_variables' => array( $activity_id ),
					)
				),
				'bp_activity_akismet_spam_' . $activity_id
			);
		}

		/**
		 * Filter to add your buttons, use the position argument to choose where to insert it.
		 *
		 * @since 3.0.0
		 *
		 * @param array $buttons     The list of buttons.
		 * @param int   $activity_id The current activity ID.
		 */
		$buttons_group = apply_filters( 'bp_nouveau_get_activity_entry_buttons', $buttons, $activity_id );

		if ( ! $buttons_group ) {
			return array();
		}

		// It's the first entry of the loop, so build the Group and sort it
		if ( ! isset( bp_nouveau()->activity->entry_buttons ) || ! is_a( bp_nouveau()->activity->entry_buttons, 'BP_Buttons_Group' ) ) {
			$sort = true;
			bp_nouveau()->activity->entry_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group
		} else {
			$sort = false;
			bp_nouveau()->activity->entry_buttons->update( $buttons_group );
		}

		$return = bp_nouveau()->activity->entry_buttons->get( $sort );

		if ( ! $return ) {
			return array();
		}

		// Remove the Comment button if the user can't comment
		if ( ! bp_activity_can_comment() && $activity_type !== 'activity_comment' ) {
			unset( $return['activity_conversation'] );
		}

		// Remove the Delete button if the user can't delete
		if ( ! bp_activity_user_can_delete() ) {
			unset( $return['activity_delete'] );
		}

		if ( isset( $return['activity_spam'] ) && ! in_array( $activity_type, BP_Akismet::get_activity_types(), true ) ) {
			unset( $return['activity_spam'] );
		}

		/**
		 * Leave a chance to adjust the $return
		 *
		 * @since 3.0.0
		 *
		 * @param array $return      The list of buttons ordered.
		 * @param int   $activity_id The current activity ID.
		 */
		do_action_ref_array( 'bp_nouveau_return_activity_entry_buttons', array( &$return, $activity_id ) );

		return $return;
	}

/**
 * Output Activity Comments if any
 *
 * @since 3.0.0
 */
function bp_nouveau_activity_comments() {
	global $activities_template;

	if ( empty( $activities_template->activity->children ) ) {
		return;
	}

	bp_nouveau_activity_recurse_comments( $activities_template->activity );
}

/**
 * Loops through a level of activity comments and loads the template for each.
 *
 * Note: This is an adaptation of the bp_activity_recurse_comments() BuddyPress core function
 *
 * @since 3.0.0
 *
 * @param object $comment The activity object currently being recursed.
 */
function bp_nouveau_activity_recurse_comments( $comment ) {
	global $activities_template;

	if ( empty( $comment->children ) ) {
		return;
	}

	/**
	 * Filters the opening tag for the template that lists activity comments.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value Opening tag for the HTML markup to use.
	 */
	echo apply_filters( 'bp_activity_recurse_comments_start_ul', '<ul>' );

	foreach ( (array) $comment->children as $comment_child ) {

		// Put the comment into the global so it's available to filters.
		$activities_template->activity->current_comment = $comment_child;

		/**
		 * Fires before the display of an activity comment.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_before_activity_comment' );

		bp_get_template_part( 'activity/comment' );

		/**
		 * Fires after the display of an activity comment.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_after_activity_comment' );

		unset( $activities_template->activity->current_comment );
	}

	/**
	 * Filters the closing tag for the template that list activity comments.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value Closing tag for the HTML markup to use.
	 */
	echo apply_filters( 'bp_activity_recurse_comments_end_ul', '</ul>' );
}

/**
 * Ouptut the Activity comment action string
 *
 * @since 3.0.0
 */
function bp_nouveau_activity_comment_action() {
	echo bp_nouveau_get_activity_comment_action();
}

	/**
	 * Get the Activity comment action string
	 *
	 * @since 3.0.0
	 */
	function bp_nouveau_get_activity_comment_action() {

		/**
		 * Filter to edit the activity comment action.
		 *
		 * @since 3.0.0
		 *
		 * @param string $value HTML Output
		 */
		return apply_filters( 'bp_nouveau_get_activity_comment_action',
			/* translators: 1: user profile link, 2: user name, 3: activity permalink, 4: activity recorded date, 5: activity timestamp, 6: activity human time since */
			sprintf( __( '<a href="%1$s">%2$s</a> replied <a href="%3$s" class="activity-time-since"><time class="time-since" datetime="%4$s" data-bp-timestamp="%5$d">%6$s</time></a>', 'buddypress' ),
				esc_url( bp_get_activity_comment_user_link() ),
				esc_html( bp_get_activity_comment_name() ),
				esc_url( bp_get_activity_comment_permalink() ),
				esc_attr( bp_get_activity_comment_date_recorded_raw() ),
				esc_attr( strtotime( bp_get_activity_comment_date_recorded_raw() ) ),
				esc_attr( bp_get_activity_comment_date_recorded() )
		) );
	}

/**
 * Load the Activity comment form
 *
 * @since 3.0.0
 */
function bp_nouveau_activity_comment_form() {
	bp_get_template_part( 'activity/comment-form' );
}

/**
 * Output the action buttons for the activity comments
 *
 * @since 3.0.0
 *
 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_activity_comment_buttons( $args = array() ) {
	$output = join( ' ', bp_nouveau_get_activity_comment_buttons( $args ) );

	ob_start();
	/**
	 * Fires after the defualt comment action options display.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_activity_comment_options' );

	$output     .= ob_get_clean();
	$has_content = trim( $output, ' ' );
	if ( ! $has_content ) {
		return;
	}

	if ( ! $args ) {
		$args = array( 'classes' => array( 'acomment-options' ) );
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

	/**
	 * Get the action buttons for the activity comments
	 *
	 * @since 3.0.0
	 *
	 * @global BP_Activity_Template $activities_template The Activity template loop.
	 *
	 * @param array $args Optional. See bp_nouveau_wrapper() for the description of parameters.
	 *
	 * @return array
	 */
	function bp_nouveau_get_activity_comment_buttons($args) {
		$buttons = array();

		if ( ! isset( $GLOBALS['activities_template'] ) ) {
			return $buttons;
		}

		$activity_comment_id = bp_get_activity_comment_id();
		$activity_id         = bp_get_activity_id();

		if ( ! $activity_comment_id || ! $activity_id ) {
			return $buttons;
		}

		/*
		 * If the 'container' is set to 'ul'
		 * set a var $parent_element to li
		 * otherwise simply pass any value found in args
		 * or set var false.
		 */
		if ( 'ul' === $args['container']  ) {
			$parent_element = 'li';
		} elseif ( ! empty( $args['parent_element'] ) ) {
			$parent_element = $args['parent_element'];
		} else {
			$parent_element = false;
		}

		$parent_attr = ( ! empty( $args['parent_attr'] ) ) ? $args['parent_attr'] : array();

		/*
		 * If we have a arg value for $button_element passed through
		 * use it to default all the $buttons['button_element'] values
		 * otherwise default to 'a' (anchor).
		 */
		if ( ! empty( $args['button_element'] ) ) {
			$button_element = $args['button_element'] ;
		} else {
			$button_element = 'a';
		}

		$buttons = array(
			'activity_comment_reply' => array(
				'id'                => 'activity_comment_reply',
				'position'          => 5,
				'component'         => 'activity',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'button_element'    => $button_element,
				'link_text'         => _x( 'Reply', 'link', 'buddypress' ),
				'button_attr'       => array(
					'class' => "acomment-reply bp-primary-action",
					'id'    => sprintf( 'acomment-reply-%1$s-from-%2$s', $activity_id, $activity_comment_id ),
				),
			),
			'activity_comment_delete' => array(
				'id'                => 'activity_comment_delete',
				'position'          => 15,
				'component'         => 'activity',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'button_element'    => $button_element,
				'link_text'         => _x( 'Delete', 'link', 'buddypress' ),
				'button_attr'       => array(
					'class' => 'delete acomment-delete confirm bp-secondary-action',
					'rel'   => 'nofollow',
				),
			),
		);

		// If button element set add nonce link to data-attr attr
		if ( 'button' === $button_element ) {
			$buttons['activity_comment_reply']['button_attr']['data-bp-act-reply-nonce'] = sprintf( '#acomment-%s', $activity_comment_id );
			$buttons['activity_comment_delete']['button_attr']['data-bp-act-reply-delete-nonce'] = bp_get_activity_comment_delete_link();
		} else {
			$buttons['activity_comment_reply']['button_attr']['href'] = sprintf( '#acomment-%s', $activity_comment_id );
			$buttons['activity_comment_delete']['button_attr']['href'] = bp_get_activity_comment_delete_link();
		}

		// Add the Spam Button if supported
		if ( bp_is_akismet_active() && isset( buddypress()->activity->akismet ) && bp_activity_user_can_mark_spam() ) {
			$buttons['activity_comment_spam'] = array(
				'id'                => 'activity_comment_spam',
				'position'          => 25,
				'component'         => 'activity',
				'must_be_logged_in' => true,
				'parent_element'    => $parent_element,
				'parent_attr'       => $parent_attr,
				'button_element'    => $button_element,
				'link_text'         => _x( 'Spam', 'link', 'buddypress' ),
				'button_attr'       => array(
					'id'     => "activity_make_spam_{$activity_comment_id}",
					'class'  => 'bp-secondary-action spam-activity-comment confirm',
					'rel'    => 'nofollow',
				),
			);

			// If button element set add nonce link to data-attr attr
			if ( 'button' === $button_element ) {
				$data_element = 'data-bp-act-spam-nonce';
			} else {
				$data_element = 'href';
			}

			$buttons['activity_comment_spam']['button_attr'][ $data_element ] = wp_nonce_url(
				add_query_arg(
					'cid',
					$activity_comment_id,
					bp_rewrites_get_url(
						array(
							'component_id'                 => 'activity',
							'single_item_action'           => 'spam',
							'single_item_action_variables' => array( $activity_comment_id ),
						)
					)
				),
				'bp_activity_akismet_spam_' . $activity_comment_id
			);
		}

		/**
		 * Filter to add your buttons, use the position argument to choose where to insert it.
		 *
		 * @since 3.0.0
		 *
		 * @param array $buttons             The list of buttons.
		 * @param int   $activity_comment_id The current activity comment ID.
		 * @param int   $activity_id         The current activity ID.
		 */
		$buttons_group = apply_filters( 'bp_nouveau_get_activity_comment_buttons', $buttons, $activity_comment_id, $activity_id );

		if ( ! $buttons_group ) {
			return $buttons;
		}

		// It's the first comment of the loop, so build the Group and sort it
		if ( ! isset( bp_nouveau()->activity->comment_buttons ) || ! is_a( bp_nouveau()->activity->comment_buttons, 'BP_Buttons_Group' ) ) {
			$sort = true;
			bp_nouveau()->activity->comment_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first comment, the order is set, we simply need to update the Buttons Group
		} else {
			$sort = false;
			bp_nouveau()->activity->comment_buttons->update( $buttons_group );
		}

		$return = bp_nouveau()->activity->comment_buttons->get( $sort );

		if ( ! $return ) {
			return array();
		}

		/**
		 * If post comment / Activity comment sync is on, it's safer
		 * to unset the comment button just before returning it.
		 */
		if ( ! bp_activity_can_comment_reply( bp_activity_current_comment() ) ) {
			unset( $return['activity_comment_reply'] );
		}

		/**
		 * If there was an activity of the user before one af another
		 * user as we're updating buttons, we need to unset the delete link
		 */
		if ( ! bp_activity_user_can_delete() ) {
			unset( $return['activity_comment_delete'] );
		}

		if ( isset( $return['activity_comment_spam'] ) && ( ! bp_activity_current_comment() || ! in_array( bp_activity_current_comment()->type, BP_Akismet::get_activity_types(), true ) ) ) {
			unset( $return['activity_comment_spam'] );
		}

		/**
		 * Leave a chance to adjust the $return
		 *
		 * @since 3.0.0
		 *
		 * @param array $return              The list of buttons ordered.
		 * @param int   $activity_comment_id The current activity comment ID.
		 * @param int   $activity_id         The current activity ID.
		 */
		do_action_ref_array( 'bp_nouveau_return_activity_comment_buttons', array( &$return, $activity_comment_id, $activity_id ) );

		return $return;
	}

/**
 * Outputs the Activity RSS link.
 *
 * @since 8.0.0
 */
function bp_nouveau_activity_rss_link() {
	echo esc_url( bp_nouveau_activity_get_rss_link() );
}

	/**
	 * Returns the Activity RSS link.
	 *
	 * @since 8.0.0
	 *
	 * @return string The Activity RSS link.
	 */
	function bp_nouveau_activity_get_rss_link() {
		$bp_nouveau = bp_nouveau();
		$link       = '';

		if ( isset( $bp_nouveau->activity->current_rss_feed['link'] ) ) {
			$link = $bp_nouveau->activity->current_rss_feed['link'];
		}

		/**
		 * Filter here to edit the Activity RSS link.
		 *
		 * @since 8.0.0
		 *
		 * @param string The Activity RSS link.
		 */
		return apply_filters( 'bp_nouveau_activity_get_rss_link', $link );
	}

/**
 * Outputs the Activity RSS Tooltip.
 *
 * @since 8.0.0
 */
function bp_nouveau_activity_rss_tooltip() {
	echo esc_attr( bp_nouveau_activity_get_rss_tooltip() );
}

	/**
	 * Returns the Activity RSS Tooltip.
	 *
	 * @since 8.0.0
	 *
	 * @return string The Activity RSS Tooltip.
	 */
	function bp_nouveau_activity_get_rss_tooltip() {
		$bp_nouveau = bp_nouveau();
		$tooltip       = '';

		if ( isset( $bp_nouveau->activity->current_rss_feed['tooltip'] ) ) {
			$tooltip = $bp_nouveau->activity->current_rss_feed['tooltip'];
		}

		/**
		 * Filter here to edit the Activity RSS Tooltip.
		 *
		 * @since 8.0.0
		 *
		 * @param string The Activity RSS Tooltip.
		 */
		return apply_filters( 'bp_nouveau_activity_get_rss_tooltip', $tooltip );
	}

/**
 * Outputs the Activity RSS screen reader text.
 *
 * @since 8.0.0
 */
function bp_nouveau_activity_rss_screen_reader_text() {
	echo esc_attr( bp_nouveau_activity_get_rss_screen_reader_text() );
}

	/**
	 * Returns the Activity RSS screen reader text.
	 *
	 * @since 8.0.0
	 *
	 * @return string The Activity RSS screen reader text.
	 */
	function bp_nouveau_activity_get_rss_screen_reader_text() {
		$bp_nouveau         = bp_nouveau();
		$screen_reader_text = '';

		if ( isset( $bp_nouveau->activity->current_rss_feed['tooltip'] ) ) {
			$screen_reader_text = $bp_nouveau->activity->current_rss_feed['tooltip'];
		}

		/**
		 * Filter here to edit the Activity RSS screen reader text.
		 *
		 * @since 8.0.0
		 *
		 * @param string The Activity RSS screen reader text.
		 */
		return apply_filters( 'bp_nouveau_activity_get_rss_screen_reader_text', $screen_reader_text );
	}
