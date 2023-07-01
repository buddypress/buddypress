<?php
/**
 * Messages Ajax functions
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {
	$ajax_actions = array(
		array(
			'messages_send_message' => array(
				'function' => 'bp_nouveau_ajax_messages_send_message',
				'nopriv'   => false,
			),
		),
		array(
			'messages_send_reply' => array(
				'function' => 'bp_nouveau_ajax_messages_send_reply',
				'nopriv'  => false,
			),
		),
		array(
			'messages_get_user_message_threads' => array(
				'function' => 'bp_nouveau_ajax_get_user_message_threads',
				'nopriv'   => false,
			),
		),
		array(
			'messages_thread_read' => array(
				'function' => 'bp_nouveau_ajax_messages_thread_read',
				'nopriv'   => false,
			),
		),
		array(
			'messages_get_thread_messages' => array(
				'function' => 'bp_nouveau_ajax_get_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_delete' => array(
				'function' => 'bp_nouveau_ajax_delete_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_exit' => array(
				'function' => 'bp_nouveau_ajax_exit_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_unstar' => array(
				'function' => 'bp_nouveau_ajax_star_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_star' => array(
				'function' => 'bp_nouveau_ajax_star_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_unread' => array(
				'function' => 'bp_nouveau_ajax_readunread_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_read' => array(
				'function' => 'bp_nouveau_ajax_readunread_thread_messages',
				'nopriv'   => false,
			),
		),
		array(
			'messages_dismiss_sitewide_notice'  => array(
				'function' => 'bp_nouveau_ajax_dismiss_sitewide_notice',
				'nopriv' => false,
			),
		),
	);

	foreach ( $ajax_actions as $ajax_action ) {
		$action = key( $ajax_action );

		add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

		if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
		}
	}
}, 12 );

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_messages_send_message() {
	$response = array(
		'feedback' => __( 'Your message could not be sent. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	// Validate subject and message content
	if ( empty( $_POST['subject'] ) || empty( $_POST['message_content'] ) ) {
		if ( empty( $_POST['subject'] ) ) {
			$response['feedback'] = __( 'Your message was not sent. Please enter a subject line.', 'buddypress' );
		} else {
			$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddypress' );
		}

		wp_send_json_error( $response );
	}

	// Validate recipients
	if ( empty( $_POST['send_to'] ) || ! is_array( $_POST['send_to'] ) ) {
		$response['feedback'] = __( 'Your message was not sent. Please enter at least one username.', 'buddypress' );

		wp_send_json_error( $response );
	}

	// Trim @ from usernames
	/**
	 * Filters the results of trimming of `@` characters from usernames for who is set to receive a message.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value Array of trimmed usernames.
	 * @param array $value Array of un-trimmed usernames submitted.
	 */
	$recipients = apply_filters( 'bp_messages_recipients', array_map( function( $username ) {
		return trim( $username, '@' );
	}, $_POST['send_to'] ) );

	// Attempt to send the message.
	$send = messages_new_message( array(
		'recipients' => $recipients,
		'subject'    => $_POST['subject'],
		'content'    => $_POST['message_content'],
		'error_type' => 'wp_error',
	) );

	// Send the message.
	if ( true === is_int( $send ) ) {
		wp_send_json_success( array(
			'feedback' => __( 'Message successfully sent.', 'buddypress' ),
			'type'     => 'success',
		) );

	// Message could not be sent.
	} else {
		$response['feedback'] = $send->get_error_message();

		wp_send_json_error( $response );
	}
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_messages_send_reply() {
	$response = array(
		'feedback' => __( 'There was a problem sending your reply. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['content'] ) || empty( $_POST['thread_id'] ) ) {
		$response['feedback'] = __( 'Your reply was not sent. Please enter some content.', 'buddypress' );

		wp_send_json_error( $response );
	}

	$thread_id = (int) $_POST['thread_id'];

	if ( ! bp_current_user_can( 'bp_moderate' ) && ( ! messages_is_valid_thread( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) ) {
		wp_send_json_error( $response );
	}

	$new_reply = messages_new_message( array(
		'thread_id' => $thread_id,
		'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
		'content'   => $_POST['content']
	) );

	// Send the reply.
	if ( empty( $new_reply ) ) {
		wp_send_json_error( $response );
	}

	// Get the message by pretending we're in the message loop.
	global $thread_template;

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	$bp->current_action = 'view';

	bp_thread_has_messages( array( 'thread_id' => $thread_id ) );

	// Set the current message to the 2nd last.
	$thread_template->message = end( $thread_template->thread->messages );
	$thread_template->message = prev( $thread_template->thread->messages );

	// Set current message to current key.
	$thread_template->current_message = key( $thread_template->thread->messages );

	// Now manually iterate message like we're in the loop.
	bp_thread_the_message();

	// Manually call oEmbed
	// this is needed because we're not at the beginning of the loop.
	bp_messages_embed();

	// Output single message template part.
	$reply = array(
		'id'            => bp_get_the_thread_message_id(),
		'content'       => do_shortcode( bp_get_the_thread_message_content() ),
		'sender_id'     => bp_get_the_thread_message_sender_id(),
		'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
		'sender_link'   => bp_get_the_thread_message_sender_link(),
		'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
			'item_id' => bp_get_the_thread_message_sender_id(),
			'object'  => 'user',
			'type'    => 'thumb',
			'width'   => 32,
			'height'  => 32,
			'html'    => false,
		) ) ),
		'date'          => bp_get_the_thread_message_date_sent() * 1000,
		'display_date'  => bp_get_the_thread_message_time_since(),
	);

	if ( bp_is_active( 'messages', 'star' ) ) {
		$star_link = bp_get_the_message_star_action_link( array(
			'message_id' => bp_get_the_thread_message_id(),
			'url_only'  => true,
		) );

		$reply['star_link']  = $star_link;
		$reply['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
	}

	$extra_content = bp_nouveau_messages_catch_hook_content( array(
		'beforeMeta'    => 'bp_before_message_meta',
		'afterMeta'     => 'bp_after_message_meta',
		'beforeContent' => 'bp_before_message_content',
		'afterContent'  => 'bp_after_message_content',
	) );

	if ( array_filter( $extra_content ) ) {
		$reply = array_merge( $reply, $extra_content );
	}

	// Clean up the loop.
	bp_thread_messages();

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	wp_send_json_success( array(
		'messages' => array( $reply ),
		'feedback' => __( 'Your reply was sent successfully', 'buddypress' ),
		'type'     => 'success',
	) );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_get_user_message_threads() {
	global $messages_template;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( array(
			'feedback' => __( 'Unauthorized request.', 'buddypress' ),
			'type'     => 'error'
		) );
	}

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	if ( isset( $_POST['box'] ) ) {
		$bp->current_action = $_POST['box'];
	}

	// Add the message thread filter.
	if ( 'starred' === $bp->current_action ) {
		add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
	}

	// Simulate the loop.
	if ( ! bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error( array(
			'feedback' => __( 'Sorry, no messages were found.', 'buddypress' ),
			'type'     => 'info'
		) );
	}

	// remove the message thread filter.
	if ( 'starred' === $bp->current_action ) {
		remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
	}

	$threads       = new stdClass;
	$threads->meta = array(
		'total_page' => ceil( (int) $messages_template->total_thread_count / (int) $messages_template->pag_num ),
		'page'       => $messages_template->pag_page,
	);

	$threads->threads = array();
	$i                = 0;

	while ( bp_message_threads() ) : bp_message_thread();
		$last_message_id = (int) $messages_template->thread->last_message_id;

		$threads->threads[ $i ] = array(
			'id'            => bp_get_message_thread_id(),
			'message_id'    => (int) $last_message_id,
			'subject'       => bp_get_message_thread_subject(),
			'excerpt'       => bp_get_message_thread_excerpt(),
			'content'       => do_shortcode( bp_get_message_thread_content() ),
			'unread'        => bp_message_thread_has_unread(),
			'sender_name'   => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
			'sender_link'   => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
			'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
				'item_id' => $messages_template->thread->last_sender_id,
				'object'  => 'user',
				'type'    => 'thumb',
				'width'   => 32,
				'height'  => 32,
				'html'    => false,
			) ) ),
			'count'         => bp_get_message_thread_total_count(),
			'date'          => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
			'display_date'  => bp_nouveau_get_message_date( bp_get_message_thread_last_post_date_raw() ),
		);

		if ( is_array( $messages_template->thread->recipients ) ) {
			foreach ( $messages_template->thread->recipients as $recipient ) {
				$threads->threads[ $i ]['recipients'][] = array(
					'avatar' => esc_url( bp_core_fetch_avatar( array(
						'item_id' => $recipient->user_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'width'   => 28,
						'height'  => 28,
						'html'    => false,
					) ) ),
					'user_link' => bp_core_get_userlink( $recipient->user_id, false, true ),
					'user_name' => bp_members_get_user_slug( $recipient->user_id ),
				);
			}
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link( array(
				'thread_id' => bp_get_message_thread_id(),
				'url_only'  => true,
			) );

			$threads->threads[ $i ]['star_link']  = $star_link;

			$star_link_data = explode( '/', $star_link );
			$threads->threads[ $i ]['is_starred'] = array_search( 'unstar', $star_link_data );

			// Defaults to last
			$sm_id = $last_message_id;

			if ( $threads->threads[ $i ]['is_starred'] ) {
				$sm_id = (int) $star_link_data[ $threads->threads[ $i ]['is_starred'] + 1 ];
			}

			$threads->threads[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
			$threads->threads[ $i ]['starred_id'] = $sm_id;
		}

		$thread_extra_content = bp_nouveau_messages_catch_hook_content( array(
			'inboxListItem' => 'bp_messages_inbox_list_item',
			'threadOptions' => 'bp_messages_thread_options',
		) );

		if ( array_filter( $thread_extra_content ) ) {
			$threads->threads[ $i ] = array_merge( $threads->threads[ $i ], $thread_extra_content );
		}

		$i += 1;
	endwhile;

	$threads->threads = array_filter( $threads->threads );

	$extra_content = bp_nouveau_messages_catch_hook_content( array(
		'beforeLoop' => 'bp_before_member_messages_loop',
		'afterLoop'  => 'bp_after_member_messages_loop',
	) );

	if ( array_filter( $extra_content ) ) {
		$threads->extraContent = $extra_content;
	}

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// Return the successfull reply.
	wp_send_json_success( $threads );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_messages_thread_read() {
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['id'] ) || empty( $_POST['message_id'] ) ) {
		wp_send_json_error();
	}

	$thread_id  = (int) $_POST['id'];
	$message_id = (int) $_POST['message_id'];

	if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
		wp_send_json_error();
	}

	// Mark thread as read
	messages_mark_thread_read( $thread_id );

	// Mark latest message as read
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'new_message' );
	}

	wp_send_json_success();
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_get_thread_messages() {
	global $thread_template;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( array(
			'feedback' => __( 'Unauthorized request.', 'buddypress' ),
			'type'     => 'error'
		) );
	}

	$response = array(
		'feedback' => __( 'Sorry, no messages were found.', 'buddypress' ),
		'type'     => 'info'
	);

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_id = (int) $_POST['id'];

	if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
		wp_send_json_error();
	}

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	$bp->current_action = 'view';

	// Simulate the loop.
	if ( ! bp_thread_has_messages( array( 'thread_id' => $thread_id ) ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error( $response );
	}

	$thread         = new stdClass;
	$thread->thread = array();

	if ( empty( $_POST['js_thread'] ) ) {
		$thread->thread = array(
			'id'      => bp_get_the_thread_id(),
			'subject' => strip_tags( bp_get_the_thread_subject() ),
		);

		if ( is_array( $thread_template->thread->recipients ) ) {
			foreach ( $thread_template->thread->recipients as $recipient ) {
				$thread->thread['recipients'][] = array(
					'avatar' => esc_url( bp_core_fetch_avatar( array(
						'item_id' => $recipient->user_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'width'   => 28,
						'height'  => 28,
						'html'    => false,
					) ) ),
					'user_link' => bp_core_get_userlink( $recipient->user_id, false, true ),
					'user_name' => bp_members_get_user_slug( $recipient->user_id ),
				);
			}
		}
	}

	$thread->messages = array();
	$i = 0;

	while ( bp_thread_messages() ) : bp_thread_the_message();
		$thread->messages[ $i ] = array(
			'id'            => bp_get_the_thread_message_id(),
			'content'       => do_shortcode( bp_get_the_thread_message_content() ),
			'sender_id'     => bp_get_the_thread_message_sender_id(),
			'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
			'sender_link'   => bp_get_the_thread_message_sender_link(),
			'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
				'item_id' => bp_get_the_thread_message_sender_id(),
				'object'  => 'user',
				'type'    => 'thumb',
				'width'   => 32,
				'height'  => 32,
				'html'    => false,
			) ) ),
			'date'          => bp_get_the_thread_message_date_sent() * 1000,
			'display_date'  => bp_get_the_thread_message_time_since(),
		);

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link( array(
				'message_id' => bp_get_the_thread_message_id(),
				'url_only'  => true,
			) );

			$thread->messages[ $i ]['star_link']  = $star_link;
			$thread->messages[ $i ]['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
			$thread->messages[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . bp_get_the_thread_message_id() );
		}

		/**
		 * Filter here to include your meta data to one or more messages of the thread.
		 *
		 * @since 8.0.0
		 *
		 * @param array $message_meta An associative array keyed using the meta_key.
		 * @param array $message The current message in the loop.
		 */
		$message_meta = (array) apply_filters( 'bp_nouveau_ajax_get_thread_message_meta', array(), $thread->messages[ $i ] );

		if ( $message_meta ) {
			$thread->messages[ $i ]['meta'] = $message_meta;
		}

		$extra_content = bp_nouveau_messages_catch_hook_content( array(
			'beforeMeta'    => 'bp_before_message_meta',
			'afterMeta'     => 'bp_after_message_meta',
			'beforeContent' => 'bp_before_message_content',
			'afterContent'  => 'bp_after_message_content',
		) );

		if ( array_filter( $extra_content ) ) {
			$thread->messages[ $i ] = array_merge( $thread->messages[ $i ], $extra_content );
		}

		$i += 1;
	endwhile;

	$thread->messages = array_filter( $thread->messages );

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	/**
	 * Filter here to include your meta data to the main message of the thread.
	 *
	 * @since 8.0.0
	 *
	 * @param array $thread_meta An associative array keyed using the meta_key.
	 * @param array $thread The current message in the loop.
	 */
	$thread_meta = (array) apply_filters( 'bp_nouveau_ajax_get_thread_meta', array(), $thread->thread );

	if ( $thread_meta ) {
		$thread->thread['meta'] = $thread_meta;
	}

	wp_send_json_success( $thread );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_delete_thread_messages() {
	$response = array(
		'feedback' => __( 'There was a problem deleting your messages. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	foreach ( $thread_ids as $thread_id ) {
		if ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( $response );
		}

		messages_delete_thread( $thread_id );
	}

	wp_send_json_success( array(
		'feedback' => __( 'Messages deleted', 'buddypress' ),
		'type'     => 'success',
	) );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_star_thread_messages() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$action = str_replace( 'messages_', '', $_POST['action'] );

	if ( 'star' === $action ) {
		$error_message = __( 'There was a problem starring your messages. Please try again.', 'buddypress' );
	} else {
		$error_message = __( 'There was a problem unstarring your messages. Please try again.', 'buddypress' );
	}

	$response = array(
		'feedback' => esc_html( $error_message ),
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages', 'star' ) || empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bp_core_can_edit_settings() ) {
		wp_send_json_error( $response );
	}

	$ids      = wp_parse_id_list( $_POST['id'] );
	$messages = array();

	// Use global nonce for bulk actions involving more than one id
	if ( 1 !== count( $ids ) ) {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
			wp_send_json_error( $response );
		}

		foreach ( $ids as $mid ) {
			if ( 'star' === $action ) {
				bp_messages_star_set_action( array(
					'action'     => 'star',
					'message_id' => $mid,
				) );
			} else {
				$thread_id = messages_get_message_thread_id( $mid );

				bp_messages_star_set_action( array(
					'action'    => 'unstar',
					'thread_id' => $thread_id,
					'bulk'      => true
				) );
			}

			$messages[ $mid ] = array(
				'star_link' => bp_get_the_message_star_action_link( array(
					'message_id' => $mid,
					'url_only'  => true,
				) ),
				'is_starred' => 'star' === $action,
			);
		}

	// Use global star nonce for bulk actions involving one id or regular action
	} else {
		$id = reset( $ids );

		if ( empty( $_POST['star_nonce'] ) || ! wp_verify_nonce( $_POST['star_nonce'], 'bp-messages-star-' . $id ) ) {
			wp_send_json_error( $response );
		}

		bp_messages_star_set_action( array(
			'action'     => $action,
			'message_id' => $id,
		) );

		$messages[ $id ] = array(
			'star_link' => bp_get_the_message_star_action_link( array(
				'message_id' => $id,
				'url_only'  => true,
			) ),
			'is_starred' => 'star' === $action,
		);
	}

	if ( 'star' === $action ) {
		$success_message = __( 'Messages successfully starred.', 'buddypress' );
	} else {
		$success_message = __( 'Messages successfully unstarred.', 'buddypress' );
	}

	wp_send_json_success( array(
		'feedback' => esc_html( $success_message ),
		'type'     => 'success',
		'messages' => $messages,
	) );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_readunread_thread_messages() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$action = str_replace( 'messages_', '', $_POST['action'] );

	$response = array(
		'feedback' => __( 'There was a problem marking your messages as read. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	if ( 'unread' === $action ) {
		$response = array(
			'feedback' => __( 'There was a problem marking your messages as unread. Please try again.', 'buddypress' ),
			'type'     => 'error',
		);
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	$response['messages'] = array();

	if ( 'unread' === $action ) {
		$response['feedback'] = __( 'Messages marked as unread.', 'buddypress' );
	} else {
		$response['feedback'] = __( 'Messages marked as read.', 'buddypress' );
	}

	foreach ( $thread_ids as $thread_id ) {
		if ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( $response );
		}

		if ( 'unread' === $action ) {
			// Mark unread
			messages_mark_thread_unread( $thread_id );
		} else {
			// Mark read
			messages_mark_thread_read( $thread_id );
		}

		$response['messages'][ $thread_id ] = array(
			'unread' => 'unread' === $action,
		);
	}

	$response['type'] = 'success';

	wp_send_json_success( $response );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_dismiss_sitewide_notice() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem dismissing the notice. Please try again.', 'buddypress' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bp_core_can_edit_settings() ) {
		wp_send_json_error( $response );
	}

	// Mark the active notice as closed.
	$success = bp_messages_dismiss_sitewide_notice();

	if ( $success ) {
		wp_send_json_success( array(
			'feedback' => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'Sitewide notice dismissed', 'buddypress' ) . '</p></div>',
			'type'     => 'success',
		) );
	} else {
		wp_send_json_error( array(
			'feedback' => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem dismissing that sitewide notice', 'buddypress' ) . '</p></div>',
			'type'     => 'error',
		) );
	}
}

/**
 * Ajax Handler of the exit action.
 *
 * @since 10.0.0
 */
function bp_nouveau_ajax_exit_thread_messages() {
	$response = array(
		'feedback' => __( 'There was a problem exiting the conversation. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	foreach ( $thread_ids as $thread_id ) {
		if ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( $response );
		}

		bp_messages_exit_thread( $thread_id );
	}

	wp_send_json_success( array(
		'feedback' => __( 'You have left the message thread.', 'buddypress' ),
		'type'     => 'success',
	) );
}
