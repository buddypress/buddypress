<?php
/**
 * BuddyPress Notices functions.
 *
 * @package buddypress\bp-members\bp-members-notices
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the Commmunity Notices Admin.
add_action( bp_core_admin_hook(), array( 'BP_Members_Notices_Admin', 'register_notices_admin' ), 9 );

/**
 * Send a notice.
 *
 * @since 14.0.0
 *
 * @param array $args {
 *     Array of parameters.
 *     @type string $title   The subject of the notice. Required. Defaults to ''.
 *     @type string $content The content to be noticed. Required. Defaults to ''.
 *     @type string $target  The targeted audience. Optional. Defaults to "community".
 *     @type string $link    The action link of the notice. Optional. Defaults to ''.
 * }
 * @return integer|WP_Error The notice ID on success, a WP Error on failure.
 */
function bp_members_send_notice( $args = array() ) {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return new WP_Error( 'bp_notices_unallowed', __( 'You are not allowed to send community notices.', 'buddypress' ) );
	}

	$r     = bp_parse_args(
		$args,
		array(
			'title'   => '',
			'content' => '',
			'target'  => 'community',
			'link'    => '',
		)
	);
	$attrs = array();

	if ( ! $r['subject'] || ! $r['content'] ) {
		return new WP_Error( 'bp_notices_missing_data', __( 'The notice subject and content are required fields.', 'buddypress' ) );
	}

	// Sanitize data.
	$subject = sanitize_text_field( $r['subject'] );
	$content = sanitize_textarea_field( $r['content'] );

	$attrs['target'] = 'community';
	if ( in_array( $r['target'], array( 'community', 'admins', 'writers' ), true ) ) {
		$attrs['target'] = $r['target'];
	}

	if ( $r['link'] ) {
		$attrs['link'] = sanitize_url( $r['link'] );
	}


	// Use the block grammar to save content.
	$message = serialize_block(
		array(
			'blockName'    => 'bp/member-notice',
			'innerContent' => array( $content ),
			'attrs'        => $attrs,
		)
	);

	$notice            = new BP_Members_Notice();
	$notice->subject   = sanitize_text_field( $subject );
	$notice->message   = $message;
	$notice->date_sent = bp_core_current_time();
	$notice->is_active = 1;

	// Send it.
	$notice_id = $notice->save();

	/**
	 * Fires after a notice has been successfully sent.
	 *
	 * Please stop using this hook.
	 *
	 * @since 1.0.0
	 * @deprecated 14.0.0
	 *
	 * @param string            $subject Subject of the notice.
	 * @param string            $content Content of the notice.
	 * @param BP_Members_Notice $notice  Notice object sent.
	 */
	do_action_deprecated( 'messages_send_notice', array( $subject, $content, $notice ), '14.0.0', 'bp_members_notice_sent' );

	$saved_values = get_object_vars( $notice );

	if ( $notice_id ) {
		/**
		 * Fires after a notice has been successfully added to the sending queue.
		 *
		 * @since 14.0.0
		 *
		 * @param integer $notice_id    The notice ID.
		 * @param array   $saved_values The list of the saved values keyed by object properties.
		 */
		do_action( 'bp_members_notice_sent', $notice_id, $saved_values );
	}

	return $notice_id;
}

/**
 * Handle user dismissal of sitewide notices.
 *
 * @since 14.0.0
 *
 * @return bool False on failure.
 */
function bp_members_dismiss_notice() {

	/**
	 *
	 *
	 *
	 *
	 * @todo check to see is we still need this code.
	 *
	 */
	return false;

	// Bail if the current user isn't logged in.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Check the nonce.
	check_admin_referer( 'messages_dismiss_notice' );

	// Dismiss the notice.
	$success = bp_messages_dismiss_sitewide_notice();

	// User feedback.
	if ( $success ) {
		$feedback = __( 'Notice has been dismissed.', 'buddypress' );
		$type     = 'success';
	} else {
		$feedback = __( 'There was a problem dismissing the notice.', 'buddypress');
		$type     = 'error';
	}

	// Add feedback message.
	bp_core_add_message( $feedback, $type );

	// Redirect.
	$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_messages_slug() ) ) );

	bp_core_redirect( $redirect_to );
}

/**
 * Handle editing of sitewide notices.
 *
 * @since 2.4.0 This function was split from messages_screen_notices(). See #6505.
 *
 * @return bool
 */
function bp_members_edit_notice() {

	/**
	 *
	 *
	 *
	 *
	 * @todo check to see is we still need this code.
	 *
	 */
	return false;

	// Get the notice ID (1|2|3).
	$notice_id = bp_action_variable( 1 );

	// Bail if notice ID is not numeric.
	if ( empty( $notice_id ) || ! is_numeric( $notice_id ) ) {
		return false;
	}

	// Bail if the current user doesn't have administrator privileges.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Get the action (deactivate|activate|delete).
	$action = sanitize_key( bp_action_variable( 0 ) );

	// Check the nonce.
	check_admin_referer( "messages_{$action}_notice" );

	// Get the notice from database.
	$notice   = new BP_Members_Notice( $notice_id );
	$success  = false;
	$feedback = '';

	// Take action.
	switch ( $action ) {

		// Deactivate.
		case 'deactivate' :
			$success  = $notice->deactivate();
			$feedback = true === $success
				? __( 'Notice deactivated successfully.',              'buddypress' )
				: __( 'There was a problem deactivating that notice.', 'buddypress' );
			break;

		// Activate.
		case 'activate' :
			$success  = $notice->activate();
			$feedback = true === $success
				? __( 'Notice activated successfully.',              'buddypress' )
				: __( 'There was a problem activating that notice.', 'buddypress' );
			break;

		// Delete.
		case 'delete' :
			$success  = $notice->delete();
			$feedback = true === $success
				? __( 'Notice deleted successfully.',              'buddypress' )
				: __( 'There was a problem deleting that notice.', 'buddypress' );
			break;
	}

	// Feedback.
	if ( ! empty( $feedback ) ) {

		// Determine message type.
		$type = ( true === $success )
			? 'success'
			: 'error';

		// Add feedback message.
		bp_core_add_message( $feedback, $type );
	}

	// Redirect.
	$redirect_to = bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_messages_slug(), 'notices' ) ) );

	bp_core_redirect( $redirect_to );
}


/**
 * Prepend a notification about the active Sitewide notice.
 *
 * @since 14.0.0
 *
 * @param false|array $notifications False if there are no items, an array of notification items otherwise.
 * @param int         $user_id       The user ID.
 * @return false|array               False if there are no items, an array of notification items otherwise.
 */
function bp_members_get_notice_for_user( $notifications, $user_id ) {
	if ( ! doing_action( 'admin_bar_menu' ) ) {
		return $notifications;
	}

	$notice = BP_Members_Notice::get_active();
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
		'component_name'    => 'members',
		'component_action'  => 'new_notice',
		'date_notified'     => $notice->date_sent,
		'is_new'            => 1,
		'total_count'       => 1,
		'content'           => $notice->message,
		'href'              => bp_loggedin_user_url(),
	);

	if ( ! is_array( $notifications ) ) {
		$notifications = array( $notice_notification );
	} else {
		array_unshift( $notifications, $notice_notification );
	}

	return $notifications;
}
add_filter( 'bp_core_get_notifications_for_user', 'bp_members_get_notice_for_user', 10, 2 );

/**
 * Output the title of a notice.
 *
 * @since 14.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_title( $notice = null ) {
	// Escaping is made in `bp-members/bp-members-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_notice_title( $notice );
}

/**
 * Get the title of a notice.
 *
 * @since 14.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice title.
 */
function bp_get_notice_title( $notice = null ) {
	$notice_title = '';

	if ( ! empty( $notice->subject ) ) {
		/**
		 * Stop using this filter, use `bp_get_notice_title` instead.
		 *
		 * @since 1.0.0
		 * @deprecated 14.0.0
		 *
		 * @param string $subject Subject of the current notice in the loop.
		 */
		$notice_title = apply_filters_deprecated( 'bp_get_message_notice_subject', array( $notice->subject ), '14.0.0', 'bp_get_notice_title' );
	}

	/**
	 * Filter the notice title.
	 *
	 * @since 14.0.0
	 *
	 * @param string                 $notice_title The notice title.
	 * @param BP_Members_Notice|null $notice       The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_title', $notice_title, $notice );
}

/**
 * Output the content of a notice.
 *
 * @since 14.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 */
function bp_notice_content( $notice = null ) {
	// Escaping is made in `bp-messages/bp-messages-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_notice_content( $notice );
}

/**
 * Get the content of a notice.
 *
 * @since 14.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice content.
 */
function bp_get_notice_content( $notice = null ) {
	$notice_content = '';

	if ( ! empty( $notice->message ) ) {
		$notice_data = parse_blocks( $notice->message );

		if ( isset( $notice_data[0]['innerHTML'] ) ) {
			$notice_content = $notice_data[0]['innerHTML'];
		} else {
			$notice_content = $notice->message;
		}

		$notice_content = apply_filters_deprecated( 'bp_get_message_notice_text', array( $notice_content ), '14.0.0', 'bp_get_notice_content' );
	}

	/**
	 * Filters the notice content.
	 *
	 * @since 14.0.0
	 *
	 * @param string                 $notice_content The content of the notice.
	 * @param BP_Members_Notice|null $notice         The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_content', $notice_content, $notice );
}

/**
 * Get the type of a notice.
 *
 * @since 14.0.0
 *
 * @param BP_Members_Notice|null $notice The notice object.
 * @return string The notice content.
 */
function bp_get_notice_type( $notice = null ) {
	$notice_type = 'dashicons-buddicons-community';

	if ( empty( $notice->message ) ) {
		return;
	}

	$notice_data = parse_blocks( $notice->message );

	if ( isset( $notice_data[0]['attrs']['target'] ) ) {
		$target = $notice_data[0]['attrs']['target'];

		if ( 'admins' === $target ) {
			$notice_type = 'dashicons-dashboard';
		} elseif ( 'writers' === $target ) {
			$notice_type = 'dashicons-edit';
		}
	}

	/**
	 * Filters the notice type.
	 *
	 * @since 14.0.0
	 *
	 * @param string                 $notice_type The type of the notice.
	 * @param BP_Members_Notice|null $notice      The notice object if it exists. Null otherwise.
	 */
	return apply_filters( 'bp_get_notice_type', $notice_type, $notice );
}

/**
 * Used to render the active notice after the WP Admin Bar.
 *
 * @since 14.0.0
 */
function bp_render_active_notice() {
	$notice = BP_Members_Notice::get_active();

	if ( empty( $notice->id ) ) {
		return;
	}
	?>
	<aside popover="auto" id="bp-notices-container" role="complementary" tabindex="-1">
		<section>
			<header class="bp-notice-header">
				<h2><?php bp_notice_title( $notice ); ?></h2>
			</header>
			<div class="bp-notice-body">
				<div class="bp-notice-type dashicons <?php echo esc_attr( bp_get_notice_type( $notice ) ); ?>" ></div>
				<div class="bp-notice-content">
					<?php bp_notice_content( $notice ); ?>
				</div>
			</div>
			<footer class="bp-notice-footer">
			</footer>
		</section>
	</aside>
	<?php
}
