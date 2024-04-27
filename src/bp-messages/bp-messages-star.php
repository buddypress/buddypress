<?php
/**
 * Functions related to starring private messages.
 *
 * @package BuddyPress
 * @subpackage MessagesStar
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** UTILITY ****************************************************************/

/**
 * Return the starred messages slug. Defaults to 'starred'.
 *
 * @since 2.3.0
 *
 * @return string
 */
function bp_get_messages_starred_slug() {

	/**
	 * Filters the starred message slug.
	 *
	 * @since 2.3.0
	 *
	 * @param string
	 */
	return sanitize_title( apply_filters( 'bp_get_messages_starred_slug', 'starred' ) );
}

/**
 * Function to determine if a message ID is starred.
 *
 * @since 2.3.0
 *
 * @param  int $mid     The message ID. Please note that this isn't the message thread ID.
 * @param  int $user_id The user ID.
 * @return bool
 */
function bp_messages_is_message_starred( $mid = 0, $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $mid ) ) {
		return false;
	}

	$starred = array_flip( (array) bp_messages_get_meta( $mid, 'starred_by_user', false ) );

	return isset( $starred[ $user_id ] );
}

/**
 * Output the link or raw URL for starring or unstarring a message.
 *
 * @since 2.3.0
 *
 * @param array $args See bp_get_the_message_star_action_link() for full documentation.
 */
function bp_the_message_star_action_link( $args = array() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_the_message_star_action_link( $args );
}
	/**
	 * Return the link or raw URL for starring or unstarring a message.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int    $user_id       The user ID. Defaults to the logged-in user ID.
	 *     @type int    $thread_id     The message thread ID. Default: 0. If not zero, this takes precedence over
	 *                                 $message_id.
	 *     @type int    $message_id    The individual message ID. If on a single thread page, defaults to the
	 *                                 current message ID in the message loop.
	 *     @type bool   $url_only      Whether to return the URL only. If false, returns link with markup.
	 *                                 Default: false.
	 *     @type string $text_unstar   Link text for the 'unstar' action. Only applicable if $url_only is false.
	 *     @type string $text_star     Link text for the 'star' action. Only applicable if $url_only is false.
	 *     @type string $title_unstar  Link title for the 'unstar' action. Only applicable if $url_only is false.
	 *     @type string $title_star    Link title for the 'star' action. Only applicable if $url_only is false.
	 *     @type string $title_unstar_thread Link title for the 'unstar' action when displayed in a thread loop.
	 *                                       Only applicable if $message_id is set and if $url_only is false.
	 *     @type string $title_star_thread   Link title for the 'star' action when displayed in a thread loop.
	 *                                       Only applicable if $message_id is set and if $url_only is false.
	 * }
	 * @return string
	 */
	function bp_get_the_message_star_action_link( $args = array() ) {

		// Default user ID.
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();

		$r = bp_parse_args(
			$args,
			array(
				'user_id'             => (int) $user_id,
				'thread_id'           => 0,
				'message_id'          => (int) bp_get_the_thread_message_id(),
				'url_only'            => false,
				'text_unstar'         => __( 'Unstar', 'buddypress' ),
				'text_star'           => __( 'Star', 'buddypress' ),
				'title_unstar'        => __( 'Starred', 'buddypress' ),
				'title_star'          => __( 'Not starred', 'buddypress' ),
				'title_unstar_thread' => __( 'Remove all starred messages in this thread', 'buddypress' ),
				'title_star_thread'   => __( 'Star the first message in this thread', 'buddypress' ),
			),
			'messages_star_action_link'
		);

		// Check user ID.
		$user_id = (int) $r['user_id'];
		if ( empty( $user_id ) ) {
			return '';
		}

		// Init path chunks.
		$path_chunks = array( bp_get_messages_slug() );

		// Define local variables.
		$retval = $bulk_attr = '';

		// Thread ID.
		if ( (int) $r['thread_id'] > 0 ) {

			// See if we're in the loop.
			if ( bp_get_message_thread_id() == $r['thread_id'] ) {

				// Grab all message ids.
				$mids = wp_list_pluck( $GLOBALS['messages_template']->thread->messages, 'id' );

				// Make sure order is ASC.
				// Order is DESC when used in the thread loop by default.
				$mids = array_reverse( $mids );

			// Pull up the thread.
			} else {
				$thread = new BP_Messages_Thread( $r['thread_id'] );
				$mids   = wp_list_pluck( $thread->messages, 'id' );
			}

			$is_starred = false;
			$message_id = 0;
			foreach ( $mids as $mid ) {

				// Try to find the first msg that is starred in a thread.
				if ( true === bp_messages_is_message_starred( $mid ) ) {
					$is_starred = true;
					$message_id = $mid;
					break;
				}
			}

			// No star, so default to first message in thread.
			if ( empty( $message_id ) ) {
				$message_id = $mids[0];
			}

			$message_id = (int) $message_id;

			// Nonce.
			$nonce = wp_create_nonce( "bp-messages-star-{$message_id}" );

			if ( true === $is_starred ) {
				$action        = 'unstar';
				$bulk_attr     = ' data-star-bulk="1"';
				$path_chunks[] = $action;
				$path_chunks[] = array( $message_id, $nonce, 'all' );
			} else {
				$action        = 'star';
				$path_chunks[] = $action;
				$path_chunks[] = array( $message_id, $nonce );
			}

			$title = $r["title_{$action}_thread"];

		// Message ID.
		} else {
			$message_id = (int) $r['message_id'];
			$is_starred = bp_messages_is_message_starred( $message_id );
			$nonce      = wp_create_nonce( "bp-messages-star-{$message_id}" );

			if ( true === $is_starred ) {
				$action = 'unstar';
			} else {
				$action = 'star';
			}

			$path_chunks[] = $action;
			$path_chunks[] = array( $message_id, $nonce );
			$title         = $r["title_{$action}"];
		}

		$url = bp_members_get_user_url( $user_id, bp_members_get_path_chunks( $path_chunks ) );

		/**
		 * Filters the star action URL for starring / unstarring a message.
		 *
		 * @since 2.3.0
		 *
		 * @param string $url URL for starring / unstarring a message.
		 * @param array  $r   Parsed link arguments. See $args in bp_get_the_message_star_action_link().
		 */
		$retval = esc_url( apply_filters( 'bp_get_the_message_star_action_urlonly', $url, $r ) );
		if ( true === (bool) $r['url_only'] ) {
			return $retval;
		}

		/**
		 * Filters the star action link, including markup.
		 *
		 * @since 2.3.0
		 *
		 * @param string $retval Link for starring / unstarring a message, including markup.
		 * @param array  $r      Parsed link arguments. See $args in bp_get_the_message_star_action_link().
		 */
		return apply_filters( 'bp_get_the_message_star_action_link', '<a data-bp-tooltip="' . esc_attr( $title ) . '" class="bp-tooltip message-action-' . esc_attr( $action ) . '" data-star-status="' . esc_attr( $action ) .'" data-star-nonce="' . esc_attr( $nonce ) . '"' . $bulk_attr . ' data-message-id="' . esc_attr( (int) $message_id ) . '" href="' . $retval . '" role="button" aria-pressed="false"><span class="icon"></span> <span class="bp-screen-reader-text">' . $r['text_' . $action] . '</span></a>', $r );
	}

/**
 * Save or delete star message meta according to a message's star status.
 *
 * @since 2.3.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type string $action     The star action. Either 'star' or 'unstar'. Default: 'star'.
 *     @type int    $thread_id  The message thread ID. Default: 0. If not zero, this takes precedence over
 *                              $message_id.
 *     @type int    $message_id The individual message ID to star or unstar.  Default: 0.
 *     @type int    $user_id    The user ID. Defaults to the logged-in user ID.
 *     @type bool   $bulk       Whether to mark all messages in a thread as a certain action. Only relevant
 *                              when $action is 'unstar' at the moment. Default: false.
 * }
 * @return bool
 */
function bp_messages_star_set_action( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'action'     => 'star',
			'thread_id'  => 0,
			'message_id' => 0,
			'user_id'    => bp_displayed_user_id(),
			'bulk'       => false,
		)
	);

	// Set thread ID.
	if ( ! empty( $r['thread_id'] ) ) {
		$thread_id = (int) $r['thread_id'];
	} else {
		$thread_id = messages_get_message_thread_id( $r['message_id'] );
	}
	if ( empty( $thread_id ) ) {
		return false;
	}

	// Check if user has access to thread.
	if( ! messages_check_thread_access( $thread_id, $r['user_id'] ) ) {
		return false;
	}

	$is_starred = bp_messages_is_message_starred( $r['message_id'], $r['user_id'] );

	// Star.
	if ( 'star' == $r['action'] ) {
		if ( true === $is_starred ) {
			return true;
		} else {
			bp_messages_add_meta( $r['message_id'], 'starred_by_user', $r['user_id'] );
			return true;
		}
	// Unstar.
	} else {
		// Unstar one message.
		if ( false === $r['bulk'] ) {
			if ( false === $is_starred ) {
				return true;
			} else {
				bp_messages_delete_meta( $r['message_id'], 'starred_by_user', $r['user_id'] );
				return true;
			}

		// Unstar all messages in a thread.
		} else {
			$thread = new BP_Messages_Thread( $thread_id );
			$mids = wp_list_pluck( $thread->messages, 'id' );

			foreach ( $mids as $mid ) {
				if ( true === bp_messages_is_message_starred( $mid, $r['user_id'] ) ) {
					bp_messages_delete_meta( $mid, 'starred_by_user', $r['user_id'] );
				}
			}

			return true;
		}
	}
}

/** HOOKS ****************************************************************/

/**
 * Enqueues the dashicons font.
 *
 * The dashicons font is used for the star / unstar icon.
 *
 * @since 2.3.0
 */
function bp_messages_star_enqueue_scripts() {
	if ( ! bp_is_user_messages() ) {
		return;
	}

	wp_enqueue_style( 'dashicons' );
}
add_action( 'bp_enqueue_scripts', 'bp_messages_star_enqueue_scripts' );

/**
 * Add the "Add star" and "Remove star" options to the bulk management list.
 *
 * @since 2.3.0
 */
function bp_messages_star_bulk_management_dropdown() {
?>

	<option value="star"><?php esc_html_e( 'Add star', 'buddypress' ); ?></option>
	<option value="unstar"><?php esc_html_e( 'Remove star', 'buddypress' ); ?></option>

<?php
}
add_action( 'bp_messages_bulk_management_dropdown', 'bp_messages_star_bulk_management_dropdown', 1 );

/**
 * Add CSS class for the current message depending on starred status.
 *
 * @since 2.3.0
 *
 * @param  array $retval Current CSS classes.
 * @return array
 */
function bp_messages_star_message_css_class( $retval = array() ) {
	if ( true === bp_messages_is_message_starred( bp_get_the_thread_message_id() ) ) {
		$status = 'starred';
	} else {
		$status = 'not-starred';
	}

	// Add css class based on star status for the current message.
	$retval[] = "message-{$status}";

	return $retval;
}
add_filter( 'bp_get_the_thread_message_css_class', 'bp_messages_star_message_css_class' );

/**
 * Filter message threads by those starred by the logged-in user.
 *
 * @since 2.3.0
 *
 * @param  array $r Current message thread arguments.
 * @return array $r Array of starred message threads.
 */
function bp_messages_filter_starred_message_threads( $r = array() ) {
	$r['box'] = 'starred';
	$r['meta_query'] = array( array(
		'key'   => 'starred_by_user',
		'value' => $r['user_id']
	) );

	return $r;
}
