<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/*****************************************************************************
 * Message Box Template Class
 **/
Class BP_Messages_Box_Template {
	var $current_thread = -1;
	var $current_thread_count;
	var $total_thread_count;
	var $threads;
	var $thread;

	var $in_the_loop;
	var $user_id;
	var $box;

	var $pag_page;
	var $pag_num;
	var $pag_links;

	function bp_messages_box_template( $user_id, $box, $per_page, $max, $type ) {
		$this->__construct( $user_id, $box, $per_page, $max, $type );
	}

	function __construct( $user_id, $box, $per_page, $max, $type ) {
		$this->pag_page = isset( $_GET['mpage'] ) ? intval( $_GET['mpage'] ) : 1;
		$this->pag_num  = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : $per_page;

		$this->user_id  = $user_id;
		$this->box      = $box;
		$this->type     = $type;

		if ( 'notices' == $this->box ) {
			$this->threads = BP_Messages_Notice::get_notices();
		} else {
			$threads = BP_Messages_Thread::get_current_threads_for_user( $this->user_id, $this->box, $this->type, $this->pag_num, $this->pag_page );

			$this->threads            = $threads['threads'];
			$this->total_thread_count = $threads['total'];
		}

		if ( !$this->threads ) {
			$this->thread_count       = 0;
			$this->total_thread_count = 0;
		} else {
			$total_notice_count = BP_Messages_Notice::get_total_notice_count();

			if ( !$max || $max >= (int)$total_notice_count ) {
				if ( 'notices' == $this->box ) {
					$this->total_thread_count = (int)$total_notice_count;
				}
			} else {
				$this->total_thread_count = (int)$max;
			}

			if ( $max ) {
				if ( $max >= count( $this->threads ) ) {
					$this->thread_count = count( $this->threads );
				} else {
					$this->thread_count = (int)$max;
				}
			} else {
				$this->thread_count = count( $this->threads );
			}
		}

		if ( (int)$this->total_thread_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'mpage', '%#%' ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_thread_count / (int)$this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Message pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Message pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	function has_threads() {
		if ( $this->thread_count )
			return true;

		return false;
	}

	function next_thread() {
		$this->current_thread++;
		$this->thread = $this->threads[$this->current_thread];

		return $this->thread;
	}

	function rewind_threads() {
		$this->current_thread = -1;
		if ( $this->thread_count > 0 ) {
			$this->thread = $this->threads[0];
		}
	}

	function message_threads() {
		if ( $this->current_thread + 1 < $this->thread_count ) {
			return true;
		} elseif ( $this->current_thread + 1 == $this->thread_count ) {
			do_action('messages_box_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_threads();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_message_thread() {
		global $bp;

		$this->in_the_loop = true;
		$this->thread = $this->next_thread();

		if ( 'notices' != $bp->current_action ) {
			$last_message_index = count( $this->thread->messages ) - 1;
			$this->thread->messages = array_reverse( (array)$this->thread->messages );

			// Set up the last message data
			if ( count($this->thread->messages) > 1 ) {
				if ( 'inbox' == $this->box ) {
					foreach ( (array)$this->thread->messages as $key => $message ) {
						if ( $bp->loggedin_user->id != $message->sender_id ) {
							$last_message_index = $key;
							break;
						}
					}

				} elseif ( 'sentbox' == $this->box ) {
					foreach ( (array)$this->thread->messages as $key => $message ) {
						if ( $bp->loggedin_user->id == $message->sender_id ) {
							$last_message_index = $key;
							break;
						}
					}
				}
			}

			$this->thread->last_message_id = $this->thread->messages[$last_message_index]->id;
			$this->thread->last_message_date = $this->thread->messages[$last_message_index]->date_sent;
			$this->thread->last_sender_id = $this->thread->messages[$last_message_index]->sender_id;
			$this->thread->last_message_subject = $this->thread->messages[$last_message_index]->subject;
			$this->thread->last_message_content = $this->thread->messages[$last_message_index]->message;
		}

		if ( 0 == $this->current_thread ) // loop has just started
			do_action('messages_box_loop_start');
	}
}

function bp_has_message_threads( $args = '' ) {
	global $bp, $messages_template;

	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'box' => 'inbox',
		'per_page' => 10,
		'max' => false,
		'type' => 'all'
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( 'notices' == $bp->current_action && !is_super_admin() ) {
		wp_redirect( $bp->displayed_user->id );
	} else {
		if ( 'inbox' == $bp->current_action )
			bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->messages->id, 'new_message' );

		if ( 'sentbox' == $bp->current_action )
			$box = 'sentbox';

		if ( 'notices' == $bp->current_action )
			$box = 'notices';

		$messages_template = new BP_Messages_Box_Template( $user_id, $box, $per_page, $max, $type );
	}

	return apply_filters( 'bp_has_message_threads', $messages_template->has_threads(), $messages_template );
}

function bp_message_threads() {
	global $messages_template;
	return $messages_template->message_threads();
}

function bp_message_thread() {
	global $messages_template;
	return $messages_template->the_message_thread();
}

function bp_message_thread_id() {
	echo bp_get_message_thread_id();
}
	function bp_get_message_thread_id() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_id', $messages_template->thread->thread_id );
	}

function bp_message_thread_subject() {
	echo bp_get_message_thread_subject();
}
	function bp_get_message_thread_subject() {
		global $messages_template, $message_template_subject;

		return apply_filters( 'bp_get_message_thread_subject', stripslashes_deep( $messages_template->thread->last_message_subject ) );
	}

function bp_message_thread_excerpt() {
	echo bp_get_message_thread_excerpt();
}
	function bp_get_message_thread_excerpt() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_excerpt', strip_tags( bp_create_excerpt( $messages_template->thread->last_message_content, 75 ) ) );
	}

function bp_message_thread_from() {
	echo bp_get_message_thread_from();
}
	function bp_get_message_thread_from() {
		global $messages_template, $bp;

		return apply_filters( 'bp_get_message_thread_from', bp_core_get_userlink( $messages_template->thread->last_sender_id ) );
	}

function bp_message_thread_to() {
	echo bp_get_message_thread_to();
}
	function bp_get_message_thread_to() {
		global $messages_template;
		return apply_filters( 'bp_message_thread_to', BP_Messages_Thread::get_recipient_links($messages_template->thread->recipients) );
	}

function bp_message_thread_view_link() {
	echo bp_get_message_thread_view_link();
}
	function bp_get_message_thread_view_link() {
		global $messages_template, $bp;
		return apply_filters( 'bp_get_message_thread_view_link', trailingslashit( $bp->loggedin_user->domain . $bp->messages->slug . '/view/' . $messages_template->thread->thread_id ) );
	}

function bp_message_thread_delete_link() {
	echo bp_get_message_thread_delete_link();
}
	function bp_get_message_thread_delete_link() {
		global $messages_template, $bp;
		return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/' . $bp->current_action . '/delete/' . $messages_template->thread->thread_id, 'messages_delete_thread' ) );
	}

function bp_message_css_class() {
	echo bp_get_message_css_class();
}

	function bp_get_message_css_class() {
		global $messages_template;

		$class = false;

		if ( $messages_template->current_thread % 2 == 1 )
			$class .= 'alt';

		return apply_filters( 'bp_get_message_css_class', trim( $class ) );
	}

function bp_message_thread_has_unread() {
	global $messages_template;

	if ( $messages_template->thread->unread_count )
		return true;

	return false;
}

function bp_message_thread_unread_count() {
	echo bp_get_message_thread_unread_count();
}
	function bp_get_message_thread_unread_count() {
		global $messages_template;

		if ( (int)$messages_template->thread->unread_count )
			return apply_filters( 'bp_get_message_thread_unread_count', $messages_template->thread->unread_count );

		return false;
	}

function bp_message_thread_last_post_date() {
	echo bp_get_message_thread_last_post_date();
}
	function bp_get_message_thread_last_post_date() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_last_post_date', bp_format_time( strtotime( $messages_template->thread->last_message_date ) ) );
	}

function bp_message_thread_avatar() {
	echo bp_get_message_thread_avatar();
}
	function bp_get_message_thread_avatar() {
		global $messages_template, $bp;

		return apply_filters( 'bp_get_message_thread_avatar', bp_core_fetch_avatar( array( 'item_id' => $messages_template->thread->last_sender_id, 'type' => 'thumb' ) ) );
	}

function bp_message_thread_view() {
	global $thread_id;

	messages_view_thread($thread_id);
}

function bp_total_unread_messages_count() {
	echo bp_get_total_unread_messages_count();
}
	function bp_get_total_unread_messages_count() {
		return apply_filters( 'bp_get_total_unread_messages_count', BP_Messages_Thread::get_inbox_count() );
	}

function bp_messages_pagination() {
	echo bp_get_messages_pagination();
}
	function bp_get_messages_pagination() {
		global $messages_template;
		return apply_filters( 'bp_get_messages_pagination', $messages_template->pag_links );
	}

function bp_messages_pagination_count() {
	global $messages_template;

	$start_num = intval( ( $messages_template->pag_page - 1 ) * $messages_template->pag_num ) + 1;
	$from_num = bp_core_number_format( $start_num );
	$to_num = bp_core_number_format( ( $start_num + ( $messages_template->pag_num - 1 ) > $messages_template->total_thread_count ) ? $messages_template->total_thread_count : $start_num + ( $messages_template->pag_num - 1 ) );
	$total = bp_core_number_format( $messages_template->total_thread_count );

	echo sprintf( __( 'Viewing message %1$s to %2$s (of %3$s messages)', 'buddypress' ), $from_num, $to_num, $total ); ?><?php
}

/**
 * Echoes the form action for Messages HTML forms
 *
 * @package BuddyPress
 */
function bp_messages_form_action() {
	echo bp_get_messages_form_action();
}
	/**
	 * Returns the form action for Messages HTML forms
	 *
	 * @package BuddyPress
	 *
	 * @return str The form action
	 */
	function bp_get_messages_form_action() {
		return apply_filters( 'bp_get_messages_form_action', trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' . bp_action_variable( 0 ) ) );
	}

function bp_messages_username_value() {
	echo bp_get_messages_username_value();
}
	function bp_get_messages_username_value() {
		if ( isset( $_COOKIE['bp_messages_send_to'] ) ) {
			return apply_filters( 'bp_get_messages_username_value', $_COOKIE['bp_messages_send_to'] );
		} else if ( isset( $_GET['r'] ) && !isset( $_COOKIE['bp_messages_send_to'] ) ) {
			return apply_filters( 'bp_get_messages_username_value', $_GET['r'] );
		}
	}

function bp_messages_subject_value() {
	echo bp_get_messages_subject_value();
}
	function bp_get_messages_subject_value() {
		$subject = '';
		if ( !empty( $_POST['subject'] ) )
			$subject = $_POST['subject'];

		return apply_filters( 'bp_get_messages_subject_value', $subject );
	}

function bp_messages_content_value() {
	echo bp_get_messages_content_value();
}
	function bp_get_messages_content_value() {
		$content = '';
		if ( !empty( $_POST['content'] ) )
			$content = $_POST['content'];

		return apply_filters( 'bp_get_messages_content_value', $content );
	}

function bp_messages_options() {
	global $bp;
?>
	<?php _e( 'Select:', 'buddypress' ) ?>
	<select name="message-type-select" id="message-type-select">
		<option value=""></option>
		<option value="read"><?php _e('Read', 'buddypress') ?></option>
		<option value="unread"><?php _e('Unread', 'buddypress') ?></option>
		<option value="all"><?php _e('All', 'buddypress') ?></option>
	</select> &nbsp;
	<?php if ( $bp->current_action != 'sentbox' && $bp->current_action != 'notices' ) : ?>
		<a href="#" id="mark_as_read"><?php _e('Mark as Read', 'buddypress') ?></a> &nbsp;
		<a href="#" id="mark_as_unread"><?php _e('Mark as Unread', 'buddypress') ?></a> &nbsp;
	<?php endif; ?>
	<a href="#" id="delete_<?php echo $bp->current_action ?>_messages"><?php _e('Delete Selected', 'buddypress') ?></a> &nbsp;
<?php
}

function bp_message_is_active_notice() {
	global $messages_template;

	if ( $messages_template->thread->is_active ) {
		echo "<strong>";
		_e( 'Currently Active', 'buddypress' );
		echo "</strong>";
	}
}
	function bp_get_message_is_active_notice() {
		global $messages_template;

		if ( $messages_template->thread->is_active )
			return true;

		return false;
	}

function bp_message_notice_id() {
	echo bp_get_message_notice_id();
}
	function bp_get_message_notice_id() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_id', $messages_template->thread->id );
	}

function bp_message_notice_post_date() {
	echo bp_get_message_notice_post_date();
}
	function bp_get_message_notice_post_date() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_post_date', bp_format_time( strtotime($messages_template->thread->date_sent) ) );
	}

function bp_message_notice_subject() {
	echo bp_get_message_notice_subject();
}
	function bp_get_message_notice_subject() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_subject', $messages_template->thread->subject );
	}

function bp_message_notice_text() {
	echo bp_get_message_notice_text();
}
	function bp_get_message_notice_text() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_text', $messages_template->thread->message );
	}

function bp_message_notice_delete_link() {
	echo bp_get_message_notice_delete_link();
}
	function bp_get_message_notice_delete_link() {
		global $messages_template, $bp;

		return apply_filters( 'bp_get_message_notice_delete_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/notices/delete/' . $messages_template->thread->id, 'messages_delete_thread' ) );
	}

function bp_message_activate_deactivate_link() {
	echo bp_get_message_activate_deactivate_link();
}
	function bp_get_message_activate_deactivate_link() {
		global $messages_template, $bp;

		if ( 1 == (int)$messages_template->thread->is_active ) {
			$link = wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/notices/deactivate/' . $messages_template->thread->id, 'messages_deactivate_notice' );
		} else {
			$link = wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/notices/activate/' . $messages_template->thread->id, 'messages_activate_notice' );
		}
		return apply_filters( 'bp_get_message_activate_deactivate_link', $link );
	}

function bp_message_activate_deactivate_text() {
	echo bp_get_message_activate_deactivate_text();
}
	function bp_get_message_activate_deactivate_text() {
		global $messages_template;

		if ( 1 == (int)$messages_template->thread->is_active  ) {
			$text = __('Deactivate', 'buddypress');
		} else {
			$text = __('Activate', 'buddypress');
		}
		return apply_filters( 'bp_message_activate_deactivate_text', $text );
	}

/**
 * Output the messages component slug
 *
 * @package BuddyPress
 * @subpackage Messages Template
 * @since 1.5
 *
 * @uses bp_get_messages_slug()
 */
function bp_messages_slug() {
	echo bp_get_messages_slug();
}
	/**
	 * Return the messages component slug
	 *
	 * @package BuddyPress
	 * @subpackage Messages Template
	 * @since 1.5
	 */
	function bp_get_messages_slug() {
		global $bp;
		return apply_filters( 'bp_get_messages_slug', $bp->messages->slug );
	}

function bp_message_get_notices() {
	global $userdata;

	$notice = BP_Messages_Notice::get_active();

	if ( empty( $notice ) )
		return false;

	$closed_notices = bp_get_user_meta( $userdata->ID, 'closed_notices', true );

	if ( !$closed_notices )
		$closed_notices = array();

	if ( is_array($closed_notices) ) {
		if ( !in_array( $notice->id, $closed_notices ) && $notice->id ) {
			?>
			<div id="message" class="info notice" rel="n-<?php echo $notice->id ?>">
				<p>
					<strong><?php echo stripslashes( wp_filter_kses( $notice->subject ) ) ?></strong><br />
					<?php echo stripslashes( wp_filter_kses( $notice->message) ) ?>
					<a href="#" id="close-notice"><?php _e( 'Close', 'buddypress' ) ?></a>
				</p>
			</div>
			<?php
		}
	}
}

function bp_send_private_message_link() {
	echo bp_get_send_private_message_link();
}
	function bp_get_send_private_message_link() {
		global $bp;

		if ( bp_is_my_profile() || !is_user_logged_in() )
			return false;

		return apply_filters( 'bp_get_send_private_message_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/compose/?r=' . bp_core_get_username( $bp->displayed_user->id, $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login ) ) );
	}

/**
 * bp_send_private_message_button()
 *
 * Explicitly named function to avoid confusion with public messages.
 *
 * @uses bp_get_send_message_button()
 * @since 1.2.6
 */
function bp_send_private_message_button() {
	echo bp_get_send_message_button();
}

function bp_send_message_button() {
	echo bp_get_send_message_button();
}
	function bp_get_send_message_button() {
		return apply_filters( 'bp_get_send_message_button',
			bp_get_button( array(
				'id'                => 'private_message',
				'component'         => 'messages',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_id'        => 'send-private-message',
				'link_href'         => bp_get_send_private_message_link(),
				'link_title'        => __( 'Send a private message to this user.', 'buddypress' ),
				'link_text'         => __( 'Private Message', 'buddypress' ),
				'link_class'        => 'send-message',
			) )
		);
	}

function bp_message_loading_image_src() {
	echo bp_get_message_loading_image_src();
}
	function bp_get_message_loading_image_src() {
		global $bp;
		return apply_filters( 'bp_get_message_loading_image_src', $bp->messages->image_base . '/ajax-loader.gif' );
	}

function bp_message_get_recipient_tabs() {
	$recipients = explode( ' ', bp_get_message_get_recipient_usernames() );

	foreach ( $recipients as $recipient ) {
		$user_id = bp_is_username_compatibility_mode() ? bp_core_get_userid( $recipient ) : bp_core_get_userid_from_nicename( $recipient );

		if ( $user_id ) : ?>

			<li id="un-<?php echo esc_attr( $recipient ); ?>" class="friend-tab">
				<span><?php
					echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) );
					echo bp_core_get_userlink( $user_id );
				?></span>
			</li>

		<?php endif;
	}
}

function bp_message_get_recipient_usernames() {
	echo bp_get_message_get_recipient_usernames();
}
	function bp_get_message_get_recipient_usernames() {
		$recipients = isset( $_GET['r'] ) ? stripslashes( $_GET['r'] ) : '';

		return apply_filters( 'bp_get_message_get_recipient_usernames', $recipients );
	}


/*****************************************************************************
 * Message Thread Template Class
 **/

class BP_Messages_Thread_Template {
	var $current_message = -1;
	var $message_count;
	var $message;

	var $thread;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_message_count;

	function bp_messages_thread_template( $thread_id, $order ) {
		$this->__construct( $thread_id, $order );
	}

	function __construct( $thread_id, $order ) {
		global $bp;

		$this->thread = new BP_Messages_Thread( $thread_id, $order );
		$this->message_count = count( $this->thread->messages );

		$last_message_index = $this->message_count - 1;
		$this->thread->last_message_id = $this->thread->messages[$last_message_index]->id;
		$this->thread->last_message_date = $this->thread->messages[$last_message_index]->date_sent;
		$this->thread->last_sender_id = $this->thread->messages[$last_message_index]->sender_id;
		$this->thread->last_message_subject = $this->thread->messages[$last_message_index]->subject;
		$this->thread->last_message_content = $this->thread->messages[$last_message_index]->message;
	}

	function has_messages() {
		if ( $this->message_count )
			return true;

		return false;
	}

	function next_message() {
		$this->current_message++;
		$this->message = $this->thread->messages[$this->current_message];

		return $this->message;
	}

	function rewind_messages() {
		$this->current_message = -1;
		if ( $this->message_count > 0 ) {
			$this->message = $this->thread->messages[0];
		}
	}

	function messages() {
		if ( $this->current_message + 1 < $this->message_count ) {
			return true;
		} elseif ( $this->current_message + 1 == $this->message_count ) {
			do_action('thread_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_messages();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_message() {
		global $message;

		$this->in_the_loop = true;
		$this->message = $this->next_message();

		if ( 0 == $this->current_message ) // loop has just started
			do_action('thread_loop_start');
	}
}

function bp_thread_has_messages( $args = '' ) {
	global $bp, $thread_template, $group_id;

	$defaults = array(
		'thread_id' => false,
		'order' => 'ASC'
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !$thread_id && bp_is_messages_component() && bp_is_current_action( 'view' ) )
		$thread_id = (int)bp_action_variable( 0 );

	$thread_template = new BP_Messages_Thread_Template( $thread_id, $order );
	return $thread_template->has_messages();
}

function bp_thread_messages_order() {
	echo bp_get_thread_messages_order();
}

	function bp_get_thread_messages_order() {
		global $thread_template;
		return $thread_template->thread->messages_order;
	}

function bp_thread_messages() {
	global $thread_template;

	return $thread_template->messages();
}

function bp_thread_the_message() {
	global $thread_template;

	return $thread_template->the_message();
}

function bp_the_thread_id() {
	echo bp_get_the_thread_id();
}
	function bp_get_the_thread_id() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_id', $thread_template->thread->thread_id );
	}

function bp_the_thread_subject() {
	echo bp_get_the_thread_subject();
}
	function bp_get_the_thread_subject() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_subject', $thread_template->thread->last_message_subject );
	}

function bp_the_thread_recipients() {
	echo bp_get_the_thread_recipients();
}
	function bp_get_the_thread_recipients() {
		global $thread_template, $bp;

		$recipient_links = array();

		if ( count( $thread_template->thread->recipients ) >= 5 )
			return apply_filters( 'bp_get_the_thread_recipients', sprintf( __( '%d Recipients', 'buddypress' ), count($thread_template->thread->recipients) ) );

		foreach( (array)$thread_template->thread->recipients as $recipient ) {
			if ( (int) $recipient->user_id !== $bp->loggedin_user->id )
				$recipient_links[] = bp_core_get_userlink( $recipient->user_id );
		}

		return apply_filters( 'bp_get_the_thread_recipients', implode( ', ', $recipient_links ) );
	}

function bp_the_thread_message_alt_class() {
	echo bp_get_the_thread_message_alt_class();
}
	function bp_get_the_thread_message_alt_class() {
		global $thread_template;

		if ( $thread_template->current_message % 2 == 1 )
			$class = ' alt';
		else
			$class = '';

		return apply_filters( 'bp_get_the_thread_message_alt_class', $class );
	}

function bp_the_thread_message_sender_avatar( $args = '' ) {
	echo bp_get_the_thread_message_sender_avatar_thumb( $args );
}
	function bp_get_the_thread_message_sender_avatar_thumb( $args = '' ) {
		global $thread_template;

		$defaults = array(
			'type' => 'thumb',
			'width' => false,
			'height' => false,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_the_thread_message_sender_avatar_thumb', bp_core_fetch_avatar( array( 'item_id' => $thread_template->message->sender_id, 'type' => $type, 'width' => $width, 'height' => $height ) ) );
	}

function bp_the_thread_message_sender_link() {
	echo bp_get_the_thread_message_sender_link();
}
	function bp_get_the_thread_message_sender_link() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_sender_link', bp_core_get_userlink( $thread_template->message->sender_id, false, true ) );
	}

function bp_the_thread_message_sender_name() {
	echo bp_get_the_thread_message_sender_name();
}
	function bp_get_the_thread_message_sender_name() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_sender_name', bp_core_get_user_displayname( $thread_template->message->sender_id ) );
	}

function bp_the_thread_delete_link() {
	echo bp_get_the_thread_delete_link();
}
	function bp_get_the_thread_delete_link() {
		global $bp;

		return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( $bp->loggedin_user->domain . $bp->messages->slug . '/inbox/delete/' . bp_get_the_thread_id(), 'messages_delete_thread' ) );
	}

function bp_the_thread_message_time_since() {
	echo bp_get_the_thread_message_time_since();
}
	function bp_get_the_thread_message_time_since() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_time_since', sprintf( __( 'Sent %s', 'buddypress' ), bp_core_time_since( strtotime( $thread_template->message->date_sent ) ) ) );
	}

function bp_the_thread_message_content() {
	echo bp_get_the_thread_message_content();
}
	function bp_get_the_thread_message_content() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_content', $thread_template->message->message );
	}

/** Embeds *******************************************************************/

/**
 * Enable oembed support for Messages.
 *
 * There's no caching as BP 1.5 does not have a Messages meta API.
 *
 * @see BP_Embed
 * @since 1.5
 * @todo Add Messages meta?
 */
function bp_messages_embed() {
	add_filter( 'embed_post_id', 'bp_get_message_thread_id' );
}
add_action( 'messages_box_loop_start', 'bp_messages_embed' );
?>
