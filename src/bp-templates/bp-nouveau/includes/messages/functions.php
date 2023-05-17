<?php
/**
 * Messages functions
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue styles for the Messages UI (mentions).
 *
 * @since 3.0.0
 *
 * @param array $styles Optional. The array of styles to enqueue.
 *
 * @return array The same array with the specific messages styles.
 */
function bp_nouveau_messages_enqueue_styles( $styles = array() ) {
	if ( ! bp_is_user_messages() ) {
		return $styles;
	}

	return array_merge( $styles, array(
		'bp-nouveau-messages-at' => array(
			'file'         => buddypress()->plugin_url . 'bp-activity/css/mentions%1$s%2$s.css',
			'dependencies' => array( 'bp-nouveau' ),
			'version'      => bp_get_version(),
		),
	) );
}

/**
 * Register Scripts for the Messages component
 *
 * @since 3.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific messages scripts.
 */
function bp_nouveau_messages_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-messages-at' => array(
			'file'         => buddypress()->plugin_url . 'bp-activity/js/mentions%s.js',
			'dependencies' => array( 'bp-nouveau', 'jquery', 'jquery-atwho' ),
			'version'      => bp_get_version(),
			'footer'       => true,
		),
		'bp-nouveau-messages' => array(
			'file'         => 'js/buddypress-messages%s.js',
			'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone', 'bp-nouveau-messages-at' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the messages scripts
 *
 * @since 3.0.0
 */
function bp_nouveau_messages_enqueue_scripts() {
	if ( ! bp_is_user_messages() ) {
		return;
	}

	wp_enqueue_script( 'bp-nouveau-messages' );

	// Add The tiny MCE init specific function.
	add_filter( 'tiny_mce_before_init', 'bp_nouveau_messages_at_on_tinymce_init', 10, 2 );
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_messages_localize_scripts( $params = array() ) {
	if ( ! bp_is_user_messages() ) {
		return $params;
	}

	$bp   = buddypress();
	$slug = bp_nouveau_get_component_slug( 'messages' );

	// Use the primary nav to get potential custom slugs.
	$primary_nav = $bp->members->nav->get( $slug );
	if ( isset( $primary_nav->link ) && $primary_nav->link ) {
		$root_url = $primary_nav->link;

		// Make sure to use the displayed user domain.
		if ( bp_loggedin_user_url() ) {
			$root_url = str_replace( bp_loggedin_user_url(), bp_displayed_user_url(), $root_url );
		}
	} else {
		$root_url = $primary_nav->link;
	}

	// Build default routes list.
	$routes = array(
		'inbox'   => 'inbox',
		'sentbox' => 'sentbox',
		'compose' => 'compose',
	);

	if ( bp_is_active( 'messages', 'star' ) ) {
		$routes['starred'] = 'starred';
	}

	// Use the secondary nav to get potential custom slugs.
	$secondary_nav = $bp->members->nav->get_secondary( array( 'parent_slug' => $slug ), false );

	// Resets the routes list using link slugs.
	if ( $secondary_nav ) {
		foreach ( $secondary_nav as $subnav_item ) {
			$routes[ $subnav_item->slug ] = trim( str_replace( $root_url, '', $subnav_item->link ), '/' );

			if ( ! $routes[ $subnav_item->slug ] ) {
				$routes[ $subnav_item->slug ] = $subnav_item->slug;
			}
		}
	}

	$params['messages'] = array(
		'errors'            => array(
			'send_to'         => __( 'Please add at least one recipient.', 'buddypress' ),
			'subject'         => __( 'Please add a subject to your message.', 'buddypress' ),
			'message_content' => __( 'Please add some content to your message.', 'buddypress' ),
		),
		'nonces'            => array(
			'send' => wp_create_nonce( 'messages_send_message' ),
		),
		'loading'           => __( 'Loading messages. Please wait.', 'buddypress' ),
		'doingAction'       => array(
			'read'   => __( 'Marking messages as read. Please wait.', 'buddypress' ),
			'unread' => __( 'Marking messages as unread. Please wait.', 'buddypress' ),
			'delete' => __( 'Deleting messages. Please wait.', 'buddypress' ),
			'star'   => __( 'Starring messages. Please wait.', 'buddypress' ),
			'unstar' => __( 'Unstarring messages. Please wait.', 'buddypress' ),
		),
		'bulk_actions'      => bp_nouveau_messages_get_bulk_actions(),
		'howto'             => __( 'Click on the message title to preview it in the Active conversation box below.', 'buddypress' ),
		'howtoBulk'         => __( 'Use the select box to define your bulk action and click on the &#10003; button to apply.', 'buddypress' ),
		'toOthers'          => array(
			'one'  => __( '(and 1 other)', 'buddypress' ),

			/* translators: %s: number of message recipients */
			'more' => __( '(and %d others)', 'buddypress' ),
		),
		'rootUrl'           => parse_url( $root_url, PHP_URL_PATH ),
		'supportedRoutes'   => $routes,
	);

	// Star private messages.
	if ( bp_is_active( 'messages', 'star' ) ) {
		$params['messages'] = array_merge( $params['messages'], array(
			'strings' => array(
				'text_unstar'  => __( 'Unstar', 'buddypress' ),
				'text_star'    => __( 'Star', 'buddypress' ),
				'title_unstar' => __( 'Starred', 'buddypress' ),
				'title_star'   => __( 'Not starred', 'buddypress' ),
				'title_unstar_thread' => __( 'Remove all starred messages in this thread', 'buddypress' ),
				'title_star_thread'   => __( 'Star the first message in this thread', 'buddypress' ),
			),
			'is_single_thread' => (int) bp_is_messages_conversation(),
			'star_counter'     => 0,
			'unstar_counter'   => 0
		) );
	}

	return $params;
}

/**
 * @since 3.0.0
 */
function bp_nouveau_messages_adjust_nav() {
	$bp = buddypress();

	$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => bp_nouveau_get_component_slug( 'messages' ) ), false );

	if ( empty( $secondary_nav_items ) ) {
		return;
	}

	foreach ( $secondary_nav_items as $secondary_nav_item ) {
		if ( empty( $secondary_nav_item->slug ) ) {
			continue;
		}

		if ( 'notices' === $secondary_nav_item->slug ) {
			bp_core_remove_subnav_item( bp_nouveau_get_component_slug( 'messages' ), $secondary_nav_item->slug, 'members' );
		} elseif ( 'compose' === $secondary_nav_item->slug ) {
			$bp->members->nav->edit_nav( array(
				'user_has_access' => bp_is_my_profile()
			), $secondary_nav_item->slug, bp_nouveau_get_component_slug( 'messages' ) );
		}
	}
}

/**
 * Replaces the Notices Compose URL.
 *
 * @since 3.0.0
 *
 * @param array $admin_nav The WP Admin Nav.
 */
function bp_nouveau_messages_adjust_admin_nav( $admin_nav ) {
	if ( empty( $admin_nav ) ) {
		return $admin_nav;
	}

	foreach ( $admin_nav as $nav_iterator => $nav ) {
		$nav_id = str_replace( 'my-account-messages-', '', $nav['id'] );

		if ( 'notices' === $nav_id ) {
			$admin_nav[ $nav_iterator ]['href'] = esc_url(
				add_query_arg(
					array(
						'page' => 'bp-notices',
					),
					bp_get_admin_url( 'users.php' )
				)
			);
		}
	}

	return $admin_nav;
}

/**
 * Prepend a notification about the active Sitewide notice.
 *
 * @since 3.0.0
 *
 * @param false|array $notifications False if there are no items, an array of notification items otherwise.
 * @param int         $user_id       The user ID.
 * @return false|array               False if there are no items, an array of notification items otherwise.
 */
function bp_nouveau_add_notice_notification_for_user( $notifications, $user_id ) {
	if ( ! bp_is_active( 'messages' ) || ! doing_action( 'admin_bar_menu' ) ) {
		return $notifications;
	}

	$notice = BP_Messages_Notice::get_active();
	if ( empty( $notice->id ) ) {
		return $notifications;
	}

	$closed_notices = bp_get_user_meta( $user_id, 'closed_notices', true );
	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( in_array( $notice->id, $closed_notices, true ) ) {
		return $notifications;
	}

	$notice_notification = (object) array(
		'id'                => 0,
		'user_id'           => $user_id,
		'item_id'           => $notice->id,
		'secondary_item_id' => 0,
		'component_name'    => 'messages',
		'component_action'  => 'new_notice',
		'date_notified'     => $notice->date_sent,
		'is_new'            => 1,
		'total_count'       => 1,
		'content'           => __( 'New sitewide notice', 'buddypress' ),
		'href'              => bp_loggedin_user_url(),
	);

	if ( ! is_array( $notifications ) ) {
		$notifications = array( $notice_notification );
	} else {
		array_unshift( $notifications, $notice_notification );
	}

	return $notifications;
}

/**
 * Format the notice notifications.
 *
 * @since 3.0.0
 * @deprecated 10.0.0
 *
 * @param array $array.
 */
function bp_nouveau_format_notice_notification_for_user( $array ) {
	_deprecated_function( __FUNCTION__, '10.0.0' );
}

/**
 * @since 3.0.0
 * @deprecated 12.0.0
 */
function bp_nouveau_unregister_notices_widget() {
	_deprecated_function( __FUNCTION__, '12.0.0' );
}

/**
 * Add active sitewide notices to the BP template_message global.
 *
 * @since 3.0.0
 */
function bp_nouveau_push_sitewide_notices() {
	// Do not show notices if user is not logged in.
	if ( ! is_user_logged_in() || ! bp_is_my_profile() ) {
		return;
	}

	$notice = BP_Messages_Notice::get_active();
	if ( empty( $notice ) ) {
		return;
	}

	$user_id = bp_loggedin_user_id();

	$closed_notices = bp_get_user_meta( $user_id, 'closed_notices', true );
	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( $notice->id && is_array( $closed_notices ) && ! in_array( $notice->id, $closed_notices, true ) ) {
		// Inject the notice into the template_message if no other message has priority.
		$bp = buddypress();

		if ( empty( $bp->template_message ) ) {
			$message = sprintf(
				'<strong class="subject">%s</strong>
				%s',
				stripslashes( $notice->subject ),
				stripslashes( $notice->message )
			);
			$bp->template_message      = $message;
			$bp->template_message_type = 'bp-sitewide-notice';
		}
	}
}

/**
 * Disable the WP Editor buttons not allowed in messages content.
 *
 * @since 3.0.0
 *
 * @param array $buttons The WP Editor buttons list.
 * @param array          The filtered WP Editor buttons list.
 */
function bp_nouveau_messages_mce_buttons( $buttons = array() ) {
	$remove_buttons = array(
		'wp_more',
		'spellchecker',
		'wp_adv',
		'fullscreen',
		'alignleft',
		'alignright',
		'aligncenter',
		'formatselect',
	);

	// Remove unused buttons
	$buttons = array_diff( $buttons, $remove_buttons );

	// Add the image button
	array_push( $buttons, 'image' );

	return $buttons;
}

/**
 * @since 3.0.0
 */
function bp_nouveau_messages_at_on_tinymce_init( $settings, $editor_id ) {
	// We only apply the mentions init to the visual post editor in the WP dashboard.
	if ( 'message_content' === $editor_id ) {
		$settings['init_instance_callback'] = 'window.bp.Nouveau.Messages.tinyMCEinit';
	}

	return $settings;
}

/**
 * @since 3.0.0
 */
function bp_nouveau_get_message_date( $date ) {
	$now  = bp_core_current_time( true, 'timestamp' );
	$date = strtotime( $date );

	$now_date    = getdate( $now );
	$date_date   = getdate( $date );
	$compare     = array_diff( $date_date, $now_date );
	$date_format = 'Y/m/d';

	// Use Timezone string if set.
	$timezone_string = bp_get_option( 'timezone_string' );
	if ( ! empty( $timezone_string ) ) {
		$timezone_object = timezone_open( $timezone_string );
		$datetime_object = date_create( "@{$date}" );
		$timezone_offset = timezone_offset_get( $timezone_object, $datetime_object ) / HOUR_IN_SECONDS;

	// Fall back on less reliable gmt_offset
	} else {
		$timezone_offset = bp_get_option( 'gmt_offset' );
	}

	// Calculate time based on the offset
	$calculated_time = $date + ( $timezone_offset * HOUR_IN_SECONDS );

	if ( empty( $compare['mday'] ) && empty( $compare['mon'] ) && empty( $compare['year'] ) ) {
		$date_format = 'H:i';

	} elseif ( empty( $compare['mon'] ) || empty( $compare['year'] ) ) {
		$date_format = 'M j';
	}

	/**
	 * Filters the message date for BuddyPress Nouveau display.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value           Internationalization-ready formatted date value.
	 * @param mixed  $calculated_time Calculated time.
	 * @param string $date            Date value.
	 * @param string $date_format     Format to convert the calcuated date to.
	 */
	return apply_filters( 'bp_nouveau_get_message_date', date_i18n( $date_format, $calculated_time, true ), $calculated_time, $date, $date_format );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_messages_get_bulk_actions() {
	ob_start();
	bp_messages_bulk_management_dropdown();

	$bulk_actions = array();
	$bulk_options = ob_get_clean();

	$matched = preg_match_all( '/<option value="(.*?)"\s*>(.*?)<\/option>/', $bulk_options, $matches, PREG_SET_ORDER );

	if ( $matched && is_array( $matches ) ) {
		foreach ( $matches as $i => $match ) {
			if ( 0 === $i ) {
				continue;
			}

			if ( isset( $match[1] ) && isset( $match[2] ) ) {
				$bulk_actions[] = array(
					'value' => trim( $match[1] ),
					'label' => trim( $match[2] ),
				);
			}
		}
	}

	return $bulk_actions;
}

/**
 * Register notifications filters for the messages component.
 *
 * @since 3.0.0
 */
function bp_nouveau_messages_notification_filters() {
	bp_nouveau_notifications_register_filter(
		array(
			'id'       => 'new_message',
			'label'    => __( 'New private messages', 'buddypress' ),
			'position' => 115,
		)
	);
}

/**
 * Fires Messages Legacy hooks to catch the content and add them
 * as extra keys to the JSON Messages UI reply.
 *
 * @since 3.0.1
 *
 * @param array $hooks The list of hooks to fire.
 * @return array       An associative containing the caught content.
 */
function bp_nouveau_messages_catch_hook_content( $hooks = array() ) {
	$content = array();

	ob_start();
	foreach ( $hooks as $js_key => $hook ) {
		if ( ! has_action( $hook ) ) {
			continue;
		}

		// Fire the hook.
		do_action( $hook );

		// Catch the sanitized content.
		$content[ $js_key ] = bp_strip_script_and_style_tags( ob_get_contents() );

		// Clean the buffer.
		ob_clean();
	}
	ob_end_clean();

	return $content;
}

/**
 * Register Messages Ajax actions.
 *
 * @since 12.0.0
 */
function bp_nouveau_register_messages_ajax_actions() {
	$ajax_actions = array( 'messages_send_message', 'messages_send_reply', 'messages_get_user_message_threads', 'messages_thread_read', 'messages_get_thread_messages', 'messages_delete', 'messages_exit', 'messages_unstar', 'messages_star', 'messages_unread', 'messages_read', 'messages_dismiss_sitewide_notice' );

	foreach ( $ajax_actions as $ajax_action ) {
		bp_ajax_register_action( $ajax_action );
	}
}
