<?php
/**
 * Messages functions
 *
 * @since 3.0.0
 * @version 3.0.0
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

	$params['messages'] = array(
		'errors' => array(
			'send_to'         => __( 'Please add at least one recipient.', 'buddypress' ),
			'subject'         => __( 'Please add a subject to your message.', 'buddypress' ),
			'message_content' => __( 'Please add some content to your message.', 'buddypress' ),
		),
		'nonces' => array(
			'send' => wp_create_nonce( 'messages_send_message' ),
		),
		'loading'       => __( 'Loading messages. Please wait.', 'buddypress' ),
		'doingAction'   => array(
			'read'   => __( 'Marking message(s) as read. Please wait.', 'buddypress' ),
			'unread' => __( 'Marking message(s) as unread. Please wait.', 'buddypress' ),
			'delete' => __( 'Deleting message(s). Please wait.', 'buddypress' ),
			'star'   => __( 'Starring message(s). Please wait.', 'buddypress' ),
			'unstar' => __( 'Unstarring message(s). Please wait.', 'buddypress' ),
		),
		'bulk_actions'  => bp_nouveau_messages_get_bulk_actions(),
		'howto'         => __( 'Click on the message title to preview it in the Active conversation box below.', 'buddypress' ),
		'howtoBulk'     => __( 'Use the select box to define your bulk action and click on the &#10003; button to apply.', 'buddypress' ),
		'toOthers'      => array(
			'one'  => __( '(and 1 other)', 'buddypress' ),
			'more' => __( '(and %d others)', 'buddypress' ),
		),
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
function bp_nouveau_message_search_form() {
	$query_arg   = bp_core_get_component_search_query_arg( 'messages' );
	$placeholder = bp_get_search_default_text( 'messages' );

	$search_form_html = '<form action="" method="get" id="search-messages-form">
		<label for="messages_search"><input type="text" name="' . esc_attr( $query_arg ) . '" id="messages_search" placeholder="' . esc_attr( $placeholder ) . '" /></label>
		<input type="submit" id="messages_search_submit" name="messages_search_submit" value="' . esc_attr_e( 'Search', 'buddypress' ) . '" />
	</form>';

	/**
	 * Filters the private message component search form.
	 *
	 * @since 3.0.0
	 *
	 * @param string $search_form_html HTML markup for the message search form.
	 */
	echo apply_filters( 'bp_nouveau_message_search_form', $search_form_html );
}
add_filter( 'bp_message_search_form', 'bp_nouveau_message_search_form', 10, 1 );

/**
 * @since 3.0.0
 */
function bp_nouveau_messages_adjust_nav() {
	$bp = buddypress();

	$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => bp_get_messages_slug() ), false );

	if ( empty( $secondary_nav_items ) ) {
		return;
	}

	foreach ( $secondary_nav_items as $secondary_nav_item ) {
		if ( empty( $secondary_nav_item->slug ) ) {
			continue;
		}

		if ( 'notices' === $secondary_nav_item->slug ) {
			bp_core_remove_subnav_item( bp_get_messages_slug(), $secondary_nav_item->slug, 'members' );
		} else {
			$params = array( 'link' => '#' . $secondary_nav_item->slug );

			// Make sure Admins won't write a messages from the user's account.
			if ( 'compose' === $secondary_nav_item->slug ) {
				$params['user_has_access'] = bp_is_my_profile();
			}

			$bp->members->nav->edit_nav( $params, $secondary_nav_item->slug, bp_get_messages_slug() );
		}
	}
}

/**
 * @since 3.0.0
 */
function bp_nouveau_messages_adjust_admin_nav( $admin_nav ) {
	if ( empty( $admin_nav ) ) {
		return $admin_nav;
	}

	$user_messages_link = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

	foreach ( $admin_nav as $nav_iterator => $nav ) {
		$nav_id = str_replace( 'my-account-messages-', '', $nav['id'] );

		if ( 'my-account-messages' !== $nav_id ) {
			if ( 'notices' === $nav_id ) {
				$admin_nav[ $nav_iterator ]['href'] = esc_url( add_query_arg( array( 'page' => 'bp-notices' ), bp_get_admin_url( 'users.php' ) ) );
			} else {
				$admin_nav[ $nav_iterator ]['href'] = $user_messages_link . '#' . trim( $nav_id );
			}
		}
	}

	return $admin_nav;
}

/**
 * @since 3.0.0
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

	$notice_notification                    = new stdClass;
	$notice_notification->id                = 0;
	$notice_notification->user_id           = $user_id;
	$notice_notification->item_id           = $notice->id;
	$notice_notification->secondary_item_id = '';
	$notice_notification->component_name    = 'messages';
	$notice_notification->component_action  = 'new_notice';
	$notice_notification->date_notified     = $notice->date_sent;
	$notice_notification->is_new            = '1';

	return array_merge( $notifications, array( $notice_notification ) );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_format_notice_notification_for_user( $array ) {
	if ( ! empty( $array['text'] ) || ! doing_action( 'admin_bar_menu' ) ) {
		return $array;
	}

	return array(
		'text' => esc_html__( 'New sitewide notice', 'buddypress' ),
		'link' => bp_loggedin_user_domain(),
	);
}

/**
 * @since 3.0.0
 */
function bp_nouveau_unregister_notices_widget() {
	unregister_widget( 'BP_Messages_Sitewide_Notices_Widget' );
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

	if ( $notice->id && is_array( $closed_notices ) && ! in_array( $notice->id, $closed_notices ) ) {
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
