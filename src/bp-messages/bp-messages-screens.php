<?php

/**
 * BuddyPress Messages Screens
 *
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage MessagesScreens
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Load the Messages > Inbox screen.
 */
function messages_screen_inbox() {
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages inbox screen template file.
	 *
	 * @since BuddyPress (1.0.0)
	 */
	do_action( 'messages_screen_inbox' );

	/**
	 * Filters the template to load for the Messages inbox screen.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_inbox', 'members/single/home' ) );
}

/**
 * Load the Messages > Sent screen.
 */
function messages_screen_sentbox() {
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages sentbox screen template file.
	 *
	 * @since BuddyPress (1.0.0)
	 */
	do_action( 'messages_screen_sentbox' );

	/**
	 * Filters the template to load for the Messages sentbox screen.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_sentbox', 'members/single/home' ) );
}

/**
 * Load the Messages > Compose screen.
 */
function messages_screen_compose() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Remove any saved message data from a previous session.
	messages_remove_callback_values();

	// Check if the message form has been submitted
	if ( isset( $_POST['send'] ) ) {

		// Check the nonce
		check_admin_referer( 'messages_send_message' );

		// Check we have what we need
		if ( empty( $_POST['subject'] ) || empty( $_POST['content'] ) ) {
			bp_core_add_message( __( 'There was an error sending that message. Please try again.', 'buddypress' ), 'error' );
		} else {
			// If this is a notice, send it
			if ( isset( $_POST['send-notice'] ) ) {
				if ( messages_send_notice( $_POST['subject'], $_POST['content'] ) ) {
					bp_core_add_message( __( 'Notice sent successfully!', 'buddypress' ) );
					bp_core_redirect( bp_loggedin_user_domain() . bp_get_messages_slug() . '/inbox/' );
				} else {
					bp_core_add_message( __( 'There was an error sending that notice. Please try again.', 'buddypress' ), 'error' );
				}
			} else {
				// Filter recipients into the format we need - array( 'username/userid', 'username/userid' )
				$autocomplete_recipients = explode( ',', $_POST['send-to-input'] );
				$typed_recipients        = explode( ' ', $_POST['send_to_usernames'] );
				$recipients              = array_merge( (array) $autocomplete_recipients, (array) $typed_recipients );

				/**
				 * Filters the array of recipients to receive the composed message.
				 *
				 * @since BuddyPress (1.2.10)
				 *
				 * @param array $recipients Array of recipients to receive message.
				 */
				$recipients              = apply_filters( 'bp_messages_recipients', $recipients );
				$thread_id               = messages_new_message( array(
					'recipients' => $recipients,
					'subject'    => $_POST['subject'],
					'content'    => $_POST['content']
				) );

				// Send the message
				if ( ! empty( $thread_id ) ) {
					bp_core_add_message( __( 'Message sent successfully!', 'buddypress' ) );
					bp_core_redirect( bp_loggedin_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id . '/' );
				} else {
					bp_core_add_message( __( 'There was an error sending that message. Please try again.', 'buddypress' ), 'error' );
				}
			}
		}
	}

	/**
	 * Fires right before the loading of the Messages compose screen template file.
	 *
	 * @since BuddyPress (1.0.0)
	 */
	do_action( 'messages_screen_compose' );

	/**
	 * Filters the template to load for the Messages compose screen.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_compose', 'members/single/home' ) );
}

/**
 * Load an individual conversation screen.
 *
 * @return bool|null False on failure.
 */
function messages_screen_conversation() {

	// Bail if not viewing a single message
	if ( !bp_is_messages_component() || !bp_is_current_action( 'view' ) ) {
		return false;
	}

	$thread_id = (int) bp_action_variable( 0 );

	if ( empty( $thread_id ) || !messages_is_valid_thread( $thread_id ) || ( !messages_check_thread_access( $thread_id ) && !bp_current_user_can( 'bp_moderate' ) ) ) {
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() ) );
	}

	// Load up BuddyPress one time
	$bp = buddypress();

	// Decrease the unread count in the nav before it's rendered
	$bp->bp_nav[$bp->messages->slug]['name'] = sprintf( __( 'Messages <span>%s</span>', 'buddypress' ), bp_get_total_unread_messages_count() );

	/**
	 * Fires right before the loading of the Messages view screen template file.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	do_action( 'messages_screen_conversation' );

	/**
	 * Filters the template to load for the Messages view screen.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_view_message', 'members/single/home' ) );
}
add_action( 'bp_screens', 'messages_screen_conversation' );

/**
 * Load the Messages > Notices screen.
 *
 * @return false|null False on failure.
 */
function messages_screen_notices() {
	global $notice_id;

	if ( !bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	$notice_id = (int)bp_action_variable( 1 );

	if ( !empty( $notice_id ) && is_numeric( $notice_id ) ) {
		$notice = new BP_Messages_Notice( $notice_id );

		if ( bp_is_action_variable( 'deactivate', 0 ) ) {
			if ( !$notice->deactivate() ) {
				bp_core_add_message( __('There was a problem deactivating that notice.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Notice deactivated.', 'buddypress') );
			}
		} elseif ( bp_is_action_variable( 'activate', 0 ) ) {
			if ( !$notice->activate() ) {
				bp_core_add_message( __('There was a problem activating that notice.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Notice activated.', 'buddypress') );
			}
		} elseif ( bp_is_action_variable( 'delete' ) ) {
			if ( !$notice->delete() ) {
				bp_core_add_message( __('There was a problem deleting that notice.', 'buddypress'), 'buddypress' );
			} else {
				bp_core_add_message( __('Notice deleted.', 'buddypress') );
			}
		}
		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices' ) );
	}

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages notices screen template file.
	 *
	 * @since BuddyPress (1.0.0)
	 */
	do_action( 'messages_screen_notices' );

	/**
	 * Filters the template to load for the Messages notices screen.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_notices', 'members/single/home' ) );
}

/**
 * Render the markup for the Messages section of Settings > Notifications.
 */
function messages_screen_notification_settings() {
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( !$new_messages = bp_get_user_meta( bp_displayed_user_id(), 'notification_messages_new_message', true ) ) {
		$new_messages = 'yes';
	} ?>

	<table class="notification-settings" id="messages-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Messages', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="messages-notification-settings-new-message">
				<td></td>
				<td><?php _e( 'A member sends you a new message', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_messages_new_message]" value="yes" <?php checked( $new_messages, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_messages_new_message]" value="no" <?php checked( $new_messages, 'no', true ) ?>/></td>
			</tr>

			<?php

			/**
			 * Fires inside the closing </tbody> tag for messages screen notification settings.
			 *
			 * @since BuddyPress (1.0.0)
			 */
			do_action( 'messages_screen_notification_settings' ); ?>
		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );
