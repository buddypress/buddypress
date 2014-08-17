<?php

/**
 * BuddyPress Messages Template Tags
 *
 * @package BuddyPress
 * @subpackage MessagesTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Message Box Template Class
 */
class BP_Messages_Box_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	public $current_thread = -1;

	/**
	 * The number of threads returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	public $current_thread_count = 0;

	/**
	 * Total number of threads matching the query params.
	 *
	 * @access public
	 * @var int
	 */
	public $total_thread_count = 0;

	/**
	 * Array of threads located by the query.
	 *
	 * @access public
	 * @var array
	 */
	public $threads = array();

	/**
	 * The thread object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	public $thread = false;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * User ID of the current inbox.
	 *
	 * @access public
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * The current "box" view ('notices', 'sentbox', 'inbox')
	 *
	 * @access public
	 * @var string
	 */
	public $box = 'inbox';

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_page = 1;

	/**
	 * The number of items being requested per page.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_num = 10;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	public $pag_links = '';

	/**
	 * Search terms for limiting the thread query.
	 *
	 * @access public
	 * @var string
	 */
	public $search_terms = '';

	/**
	 * Constructor method.
	 *
	 * @param int $user_id ID of the user whose Messages box is being
	 *        viewed.
	 * @param string $box Type of box being viewed ('notices', 'sentbox',
	 *        'inbox').
	 * @param int $per_page Number of thread to return per page of results.
	 * @param int $max Max number of results to return.
	 * @param string $type Type of results to return. 'unread', 'read',
	 *        or 'all'.
	 * @param string $search_terms Search terms for limiting results.
	 * @param string $page_arg Optional. URL argument for pagination
	 *        parameter. Default: 'mpage'.
	 */
	public function __construct( $user_id, $box, $per_page, $max, $type, $search_terms, $page_arg = 'mpage' ) {
		$this->pag_page = isset( $_GET[$page_arg] ) ? intval( $_GET[$page_arg] ) : 1;
		$this->pag_num  = isset( $_GET['num'] )   ? intval( $_GET['num'] )   : $per_page;

		$this->user_id      = $user_id;
		$this->box          = $box;
		$this->type         = $type;
		$this->search_terms = $search_terms;

		if ( 'notices' == $this->box ) {
			$this->threads = BP_Messages_Notice::get_notices( array(
				'pag_num'  => $this->pag_num,
				'pag_page' => $this->pag_page
			) );
		} else {
			$threads = BP_Messages_Thread::get_current_threads_for_user( $this->user_id, $this->box, $this->type, $this->pag_num, $this->pag_page, $this->search_terms );

			$this->threads            = $threads['threads'];
			$this->total_thread_count = $threads['total'];
		}

		if ( !$this->threads ) {
			$this->thread_count       = 0;
			$this->total_thread_count = 0;
		} else {
			$total_notice_count = BP_Messages_Notice::get_total_notice_count();

			if ( !$max || $max >= (int) $total_notice_count ) {
				if ( 'notices' == $this->box ) {
					$this->total_thread_count = (int) $total_notice_count;
				}
			} else {
				$this->total_thread_count = (int) $max;
			}

			if ( $max ) {
				if ( $max >= count( $this->threads ) ) {
					$this->thread_count = count( $this->threads );
				} else {
					$this->thread_count = (int) $max;
				}
			} else {
				$this->thread_count = count( $this->threads );
			}
		}

		if ( (int) $this->total_thread_count && (int) $this->pag_num ) {
			$pag_args = array(
				$page_arg => '%#%',
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			if ( ! empty( $this->search_terms ) ) {
				$pag_args['s'] = $this->search_terms;
			}

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $pag_args, $base ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_thread_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Message pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Message pagination next text', 'buddypress' ),
				'mid_size'  => 1
			) );
		}
	}

	/**
	 * Whether there are threads available in the loop.
	 *
	 * @see bp_has_message_threads()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_threads() {
		if ( $this->thread_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next member and iterate index.
	 *
	 * @return object The next member to iterate over.
	 */
	public function next_thread() {
		$this->current_thread++;
		$this->thread = $this->threads[$this->current_thread];

		return $this->thread;
	}

	/**
	 * Rewind the threads and reset thread index.
	 */
	public function rewind_threads() {
		$this->current_thread = -1;
		if ( $this->thread_count > 0 ) {
			$this->thread = $this->threads[0];
		}
	}

	/**
	 * Whether there are threads left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_message_threads()} as part of the
	 * while loop that controls iteration inside the threads loop, eg:
	 *     while ( bp_message_threads() ) { ...
	 *
	 * @see bp_message_threads()
	 *
	 * @return bool True if there are more threads to show, otherwise false.
	 */
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

	/**
	 * Set up the current thread inside the loop.
	 *
	 * Used by {@link bp_message_thread()} to set up the current thread data
	 * while looping, so that template tags used during that iteration make
	 * reference to the current thread.
	 *
	 * @see bp_message_thread()
	 */
	public function the_message_thread() {

		$this->in_the_loop = true;
		$this->thread      = $this->next_thread();

		if ( ! bp_is_current_action( 'notices' ) ) {
			$last_message_index     = count( $this->thread->messages ) - 1;
			$this->thread->messages = array_reverse( (array) $this->thread->messages );

			// Set up the last message data
			if ( count($this->thread->messages) > 1 ) {
				if ( 'inbox' == $this->box ) {
					foreach ( (array) $this->thread->messages as $key => $message ) {
						if ( bp_loggedin_user_id() != $message->sender_id ) {
							$last_message_index = $key;
							break;
						}
					}

				} elseif ( 'sentbox' == $this->box ) {
					foreach ( (array) $this->thread->messages as $key => $message ) {
						if ( bp_loggedin_user_id() == $message->sender_id ) {
							$last_message_index = $key;
							break;
						}
					}
				}
			}

			$this->thread->last_message_id      = $this->thread->messages[ $last_message_index ]->id;
			$this->thread->last_message_date    = $this->thread->messages[ $last_message_index ]->date_sent;
			$this->thread->last_sender_id       = $this->thread->messages[ $last_message_index ]->sender_id;
			$this->thread->last_message_subject = $this->thread->messages[ $last_message_index ]->subject;
			$this->thread->last_message_content = $this->thread->messages[ $last_message_index ]->message;
		}

		// loop has just started
		if ( 0 == $this->current_thread ) {
			do_action( 'messages_box_loop_start' );
		}
	}
}

/**
 * Retrieve private message threads for display in inbox/sentbox/notices.
 *
 * Similar to WordPress's have_posts() function, this function is responsible
 * for querying the database and retrieving private messages for display inside
 * the theme via individual template parts for a member's inbox/sentbox/notices.
 *
 * @since BuddyPress (1.0.0)
 *
 * @global BP_Messages_Box_Template $messages_template
 *
 * @param array $args {
 *     Array of arguments. All are optional.
 *     @type int $user_id ID of the user whose threads are being loaded.
 *           Default: ID of the logged-in user.
 *     @type string $box Current "box" view. If not provided here, the current
 *           view will be inferred from the URL.
 *     @type int $per_page Number of results to return per page. Default: 10.
 *     @type int $max Max results to return. Default: false.
 *     @type string $search_terms Terms to which to limit results. Default:
 *           the value of $_REQUEST['s'].
 *     @type string $page_arg URL argument used for the pagination param.
 *           Default: 'mpage'.
 * }
 * @return bool True if there are threads to display, otherwise false.
 */
function bp_has_message_threads( $args = '' ) {
	global $messages_template;

	// The default box the user is looking at
	if ( bp_is_current_action( 'sentbox' ) ) {
		$default_box = 'sentbox';
	} elseif ( bp_is_current_action( 'notices' ) ) {
		$default_box = 'notices';
	} else {
		$default_box = 'inbox';
	}

	// Parse the arguments
	$r = bp_parse_args( $args, array(
		'user_id'      => bp_loggedin_user_id(),
		'box'          => $default_box,
		'per_page'     => 10,
		'max'          => false,
		'type'         => 'all',
		'search_terms' => isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '',
		'page_arg'     => 'mpage', // See https://buddypress.trac.wordpress.org/ticket/3679
	), 'has_message_threads' );

	// If trying to access notices without capabilities, redirect to root domain
	if ( bp_is_current_action( 'notices' ) && !bp_current_user_can( 'bp_moderate' ) ) {
		bp_core_redirect( bp_displayed_user_domain() );
	}

	// Load the messages loop global up with messages
	$messages_template = new BP_Messages_Box_Template(
		$r['user_id'],
		$r['box'],
		$r['per_page'],
		$r['max'],
		$r['type'],
		$r['search_terms'],
		$r['page_arg']
	);

	return apply_filters( 'bp_has_message_threads', $messages_template->has_threads(), $messages_template, $r );
}

/**
 * Check whether there are more threads to iterate over.
 *
 * @return bool
 */
function bp_message_threads() {
	global $messages_template;
	return $messages_template->message_threads();
}

/**
 * Set up the current thread inside the loop.
 *
 * @return object
 */
function bp_message_thread() {
	global $messages_template;
	return $messages_template->the_message_thread();
}

/**
 * Output the ID of the current thread in the loop.
 */
function bp_message_thread_id() {
	echo bp_get_message_thread_id();
}
	/**
	 * Get the ID of the current thread in the loop.
	 *
	 * @return int
	 */
	function bp_get_message_thread_id() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_id', $messages_template->thread->thread_id );
	}

/**
 * Output the subject of the current thread in the loop.
 */
function bp_message_thread_subject() {
	echo bp_get_message_thread_subject();
}
	/**
	 * Get the subject of the current thread in the loop.
	 *
	 * @return string
	 */
	function bp_get_message_thread_subject() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_subject', stripslashes_deep( $messages_template->thread->last_message_subject ) );
	}

/**
 * Output an excerpt from the current message in the loop.
 */
function bp_message_thread_excerpt() {
	echo bp_get_message_thread_excerpt();
}
	/**
	 * Generate an excerpt from the current message in the loop.
	 *
	 * @return string
	 */
	function bp_get_message_thread_excerpt() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_excerpt', strip_tags( bp_create_excerpt( $messages_template->thread->last_message_content, 75 ) ) );
	}

/**
 * Output the thread's last message content
 *
 * When viewing your Inbox, the last message is the most recent message in
 * the thread of which you are *not* the author.
 *
 * When viewing your Sentbox, last message is the most recent message in
 * the thread of which you *are* the member.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_message_thread_content() {
	echo bp_get_message_thread_content();
}
	/**
	 * Return the thread's last message content
	 *
	 * When viewing your Inbox, the last message is the most recent message in
	 * the thread of which you are *not* the author.
	 *
	 * When viewing your Sentbox, last message is the most recent message in
	 * the thread of which you *are* the member.
	 *
	 * @since BuddyPress (2.0.0)
	 * @return string The raw content of the last message in the thread
	 */
	function bp_get_message_thread_content() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_content', $messages_template->thread->last_message_content );
	}

/**
 * Output a link to the page of the current thread's last author.
 */
function bp_message_thread_from() {
	echo bp_get_message_thread_from();
}
	/**
	 * Get a link to the page of the current thread's last author.
	 *
	 * @return string
	 */
	function bp_get_message_thread_from() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_from', bp_core_get_userlink( $messages_template->thread->last_sender_id ) );
	}

/**
 * Output links to the pages of the current thread's recipients.
 */
function bp_message_thread_to() {
	echo bp_get_message_thread_to();
}
	/**
	 * Generate HTML links to the pages of the current thread's recipients.
	 *
	 * @return string
	 */
	function bp_get_message_thread_to() {
		global $messages_template;
		return apply_filters( 'bp_message_thread_to', BP_Messages_Thread::get_recipient_links($messages_template->thread->recipients ) );
	}

/**
 * Output the permalink for a particular thread.
 *
 * @param int $thread_id Optional. ID of the thread. Default: current thread
 *        being iterated on in the loop.
 */
function bp_message_thread_view_link( $thread_id = 0 ) {
	echo bp_get_message_thread_view_link( $thread_id );
}
	/**
	 * Get the permalink of a particular thread.
	 *
	 * @param int $thread_id Optional. ID of the thread. Default: current
	 *        thread being iterated on in the loop.
	 * @return string
	 */
	function bp_get_message_thread_view_link( $thread_id = 0 ) {
		global $messages_template;

		if ( empty( $messages_template ) && (int) $thread_id > 0 ) {
			$thread_id = (int) $thread_id;
		} elseif ( ! empty( $messages_template->thread->thread_id ) ) {
			$thread_id = $messages_template->thread->thread_id;
		}

		return apply_filters( 'bp_get_message_thread_view_link', trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/view/' . $thread_id ) );
	}

/**
 * Output the URL for deleting the current thread.
 */
function bp_message_thread_delete_link() {
	echo bp_get_message_thread_delete_link();
}
	/**
	 * Generate the URL for deleting the current thread.
	 *
	 * @return string
	 */
	function bp_get_message_thread_delete_link() {
		global $messages_template;
		return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/delete/' . $messages_template->thread->thread_id ), 'messages_delete_thread' ) );
	}

/**
 * Output the CSS class for the current thread.
 */
function bp_message_css_class() {
	echo bp_get_message_css_class();
}
	/**
	 * Generate the CSS class for the current thread.
	 *
	 * @return string
	 */
	function bp_get_message_css_class() {
		global $messages_template;

		$class = false;

		if ( $messages_template->current_thread % 2 == 1 ) {
			$class .= 'alt';
		}

		return apply_filters( 'bp_get_message_css_class', trim( $class ) );
	}

/**
 * Check whether the current thread has unread items.
 *
 * @return bool True if there are unread items, otherwise false.
 */
function bp_message_thread_has_unread() {
	global $messages_template;

	$retval = ! empty( $messages_template->thread->unread_count )
		? true
		: false;

	return apply_filters( 'bp_message_thread_has_unread', $retval );
}

/**
 * Output the current thread's unread count.
 */
function bp_message_thread_unread_count() {
	echo bp_get_message_thread_unread_count();
}
	/**
	 * Get the current thread's unread count.
	 *
	 * @return int
	 */
	function bp_get_message_thread_unread_count() {
		global $messages_template;

		$count = ! empty( $messages_template->thread->unread_count )
			? (int) $messages_template->thread->unread_count
			: false;

		return apply_filters( 'bp_get_message_thread_unread_count', $count );
	}

/**
 * Output the unformatted date of the last post in the current thread.
 */
function bp_message_thread_last_post_date_raw() {
	echo bp_get_message_thread_last_post_date_raw();
}
	/**
	 * Get the unformatted date of the last post in the current thread.
	 *
	 * @return string
	 */
	function bp_get_message_thread_last_post_date_raw() {
		global $messages_template;

		return apply_filters( 'bp_get_message_thread_last_message_date', $messages_template->thread->last_message_date );
	}

/**
 * Output the nicely formatted date of the last post in the current thread.
 */
function bp_message_thread_last_post_date() {
	echo bp_get_message_thread_last_post_date();
}
	/**
	 * Get the nicely formatted date of the last post in the current thread.
	 *
	 * @return string
	 */
	function bp_get_message_thread_last_post_date() {
		return apply_filters( 'bp_get_message_thread_last_post_date', bp_format_time( strtotime( bp_get_message_thread_last_post_date_raw() ) ) );
	}

/**
 * Output the avatar for the last sender in the current message thread.
 *
 * @see bp_get_message_thread_avatar() for a description of arguments.
 *
 * @param array $args See {@link bp_get_message_thread_avatar()}.
 */
function bp_message_thread_avatar( $args = '' ) {
	echo bp_get_message_thread_avatar( $args );
}
	/**
	 * Return the avatar for the last sender in the current message thread.
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array $args {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string $type Default: 'thumb'.
	 *     @type int|bool $width Default: false.
	 *     @type int|bool $height Default: false.
	 *     @type string $class Default: 'avatar'.
	 *     @type string|bool $id Default: false.
	 *     @type string $alt Default: 'Profile picture of [display name]'.
	 * }
	 * @return User avatar string.
	 */
	function bp_get_message_thread_avatar( $args = '' ) {
		global $messages_template;

		$fullname = bp_core_get_user_displayname( $messages_template->thread->last_sender_id );
		$alt      = sprintf( __( 'Profile picture of %s', 'buddypress' ), $fullname );

		$r = bp_parse_args( $args, array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'class'  => 'avatar',
			'id'     => false,
			'alt'    => $alt
		) );

		return apply_filters( 'bp_get_message_thread_avatar', bp_core_fetch_avatar( array(
			'item_id' => $messages_template->thread->last_sender_id,
			'type'    => $r['type'],
			'alt'     => $r['alt'],
			'css_id'  => $r['id'],
			'class'   => $r['class'],
			'width'   => $r['width'],
			'height'  => $r['height'],
		) ) );
	}

/**
 * Output the unread messages count for the current inbox.
 */
function bp_total_unread_messages_count() {
	echo bp_get_total_unread_messages_count();
}
	/**
	 * Get the unread messages count for the current inbox.
	 *
	 * @return int
	 */
	function bp_get_total_unread_messages_count() {
		return apply_filters( 'bp_get_total_unread_messages_count', BP_Messages_Thread::get_inbox_count() );
	}

/**
 * Output the pagination HTML for the current thread loop.
 */
function bp_messages_pagination() {
	echo bp_get_messages_pagination();
}
	/**
	 * Get the pagination HTML for the current thread loop.
	 *
	 * @return string
	 */
	function bp_get_messages_pagination() {
		global $messages_template;
		return apply_filters( 'bp_get_messages_pagination', $messages_template->pag_links );
	}

/**
 * Generate the "Viewing message x to y (of z messages)" string for a loop.
 *
 * @return string
 */
function bp_messages_pagination_count() {
	global $messages_template;

	$start_num = intval( ( $messages_template->pag_page - 1 ) * $messages_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $messages_template->pag_num - 1 ) > $messages_template->total_thread_count ) ? $messages_template->total_thread_count : $start_num + ( $messages_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $messages_template->total_thread_count );

	echo sprintf( _n( 'Viewing 1 message', 'Viewing %1$s - %2$s of %3$s messages', $total, 'buddypress' ), $from_num, $to_num, number_format_i18n( $total ) );
}

/**
 * Output the Private Message search form.
 *
 * @since BuddyPress (1.6.0)
 */
function bp_message_search_form() {

	$default_search_value = bp_get_search_default_text( 'messages' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value; ?>

	<form action="" method="get" id="search-message-form">
		<label><input type="text" name="s" id="messages_search" <?php if ( $search_value === $default_search_value ) : ?>placeholder="<?php echo esc_html( $search_value ); ?>"<?php endif; ?> <?php if ( $search_value !== $default_search_value ) : ?>value="<?php echo esc_html( $search_value ); ?>"<?php endif; ?> /></label>
		<input type="submit" id="messages_search_submit" name="messages_search_submit" value="<?php esc_attr_e( 'Search', 'buddypress' ) ?>" />
	</form>

<?php
}

/**
 * Echo the form action for Messages HTML forms.
 */
function bp_messages_form_action() {
	echo bp_get_messages_form_action();
}
	/**
	 * Return the form action for Messages HTML forms.
	 *
	 * @return string The form action
	 */
	function bp_get_messages_form_action() {
		return apply_filters( 'bp_get_messages_form_action', trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() . '/' . bp_action_variable( 0 ) ) );
	}

/**
 * Output the default username for the recipient box.
 */
function bp_messages_username_value() {
	echo bp_get_messages_username_value();
}
	/**
	 * Get the default username for the recipient box.
	 *
	 * @return string
	 */
	function bp_get_messages_username_value() {
		if ( isset( $_COOKIE['bp_messages_send_to'] ) ) {
			return apply_filters( 'bp_get_messages_username_value', $_COOKIE['bp_messages_send_to'] );
		} else if ( isset( $_GET['r'] ) && !isset( $_COOKIE['bp_messages_send_to'] ) ) {
			return apply_filters( 'bp_get_messages_username_value', $_GET['r'] );
		}
	}

/**
 * Output the default value for the Subject field.
 */
function bp_messages_subject_value() {
	echo bp_get_messages_subject_value();
}
	/**
	 * Get the default value for the Subject field.
	 *
	 * Will get a value out of $_POST['subject'] if available (ie after a
	 * failed submission).
	 *
	 * @return string
	 */
	function bp_get_messages_subject_value() {
		$subject = ! empty( $_POST['subject'] )
			? $_POST['subject']
			: '';

		return apply_filters( 'bp_get_messages_subject_value', $subject );
	}

/**
 * Output the default value for the Compose content field.
 */
function bp_messages_content_value() {
	echo bp_get_messages_content_value();
}
	/**
	 * Get the default value fo the Compose content field.
	 *
	 * Will get a value out of $_POST['content'] if available (ie after a
	 * failed submission).
	 *
	 * @return string
	 */
	function bp_get_messages_content_value() {

		$content = !empty( $_POST['content'] )
			? $_POST['content']
			: '';

		return apply_filters( 'bp_get_messages_content_value', $content );
	}

/**
 * Output the markup for the message type dropdown.
 */
function bp_messages_options() {
?>

	<?php _e( 'Select:', 'buddypress' ) ?>

	<select name="message-type-select" id="message-type-select">
		<option value=""></option>
		<option value="read"><?php _ex('Read', 'Message dropdown filter', 'buddypress') ?></option>
		<option value="unread"><?php _ex('Unread', 'Message dropdown filter', 'buddypress') ?></option>
		<option value="all"><?php _ex('All', 'Message dropdown filter', 'buddypress') ?></option>
	</select> &nbsp;

	<?php if ( ! bp_is_current_action( 'sentbox' ) && bp_is_current_action( 'notices' ) ) : ?>

		<a href="#" id="mark_as_read"><?php _ex('Mark as Read', 'Message management markup', 'buddypress') ?></a> &nbsp;
		<a href="#" id="mark_as_unread"><?php _ex('Mark as Unread', 'Message management markup', 'buddypress') ?></a> &nbsp;

	<?php endif; ?>

	<a href="#" id="delete_<?php echo bp_current_action(); ?>_messages"><?php _e( 'Delete Selected', 'buddypress' ); ?></a> &nbsp;

<?php
}

/**
 * Return whether or not the notice is currently active.
 *
 * @since BuddyPress (1.6.0)
 */
function bp_messages_is_active_notice() {
	global $messages_template;

	$retval = ! empty( $messages_template->thread->is_active )
		? true
		: false;

	return apply_filters( 'bp_messages_is_active_notice', $retval );
}

/**
 * Output a string for the active notice.
 *
 * Since 1.6 this function has been deprecated in favor of text in the theme.
 *
 * @since BuddyPress (1.0.0)
 * @deprecated BuddyPress (1.6.0)
 * @uses bp_get_message_is_active_notice()
 */
function bp_message_is_active_notice() {
	echo bp_get_message_is_active_notice();
}
	/**
	 * Returns a string for the active notice.
	 *
	 * Since 1.6 this function has been deprecated in favor of text in the
	 * theme.
	 *
	 * @since BuddyPress (1.0.0)
	 * @deprecated BuddyPress (1.6.0)
	 * @uses bp_messages_is_active_notice()
	 */
	function bp_get_message_is_active_notice() {

		$string = bp_messages_is_active_notice()
			? __( 'Currently Active', 'buddypress' )
			: '';

		return apply_filters( 'bp_get_message_is_active_notice', $string );
	}

/**
 * Output the ID of the current notice in the loop.
 */
function bp_message_notice_id() {
	echo bp_get_message_notice_id();
}
	/**
	 * Get the ID of the current notice in the loop.
	 *
	 * @return int
	 */
	function bp_get_message_notice_id() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_id', $messages_template->thread->id );
	}

/**
 * Output the post date of the current notice in the loop.
 */
function bp_message_notice_post_date() {
	echo bp_get_message_notice_post_date();
}
	/**
	 * Get the post date of the current notice in the loop.
	 *
	 * @return string
	 */
	function bp_get_message_notice_post_date() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_post_date', bp_format_time( strtotime( $messages_template->thread->date_sent ) ) );
	}

/**
 * Output the subject of the current notice in the loop.
 */
function bp_message_notice_subject() {
	echo bp_get_message_notice_subject();
}
	/**
	 * Get the subject of the current notice in the loop.
	 *
	 * @return string
	 */
	function bp_get_message_notice_subject() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_subject', $messages_template->thread->subject );
	}

/**
 * Output the text of the current notice in the loop.
 */
function bp_message_notice_text() {
	echo bp_get_message_notice_text();
}
	/**
	 * Get the text of the current notice in the loop.
	 *
	 * @return string
	 */
	function bp_get_message_notice_text() {
		global $messages_template;
		return apply_filters( 'bp_get_message_notice_text', $messages_template->thread->message );
	}

/**
 * Output the URL for deleting the current notice.
 */
function bp_message_notice_delete_link() {
	echo bp_get_message_notice_delete_link();
}
	/**
	 * Get the URL for deleting the current notice.
	 *
	 * @return string Delete URL.
	 */
	function bp_get_message_notice_delete_link() {
		global $messages_template;

		return apply_filters( 'bp_get_message_notice_delete_link', wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/delete/' . $messages_template->thread->id, 'messages_delete_thread' ) );
	}

/**
 * Output the URL for deactivating the current notice.
 */
function bp_message_activate_deactivate_link() {
	echo bp_get_message_activate_deactivate_link();
}
	/**
	 * Get the URL for deactivating the current notice.
	 *
	 * @return string
	 */
	function bp_get_message_activate_deactivate_link() {
		global $messages_template;

		if ( 1 === (int) $messages_template->thread->is_active ) {
			$link = wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/deactivate/' . $messages_template->thread->id ), 'messages_deactivate_notice' );
		} else {
			$link = wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/notices/activate/' . $messages_template->thread->id ), 'messages_activate_notice' );
		}
		return apply_filters( 'bp_get_message_activate_deactivate_link', $link );
	}

/**
 * Output the Deactivate/Activate text for the notice action link.
 */
function bp_message_activate_deactivate_text() {
	echo bp_get_message_activate_deactivate_text();
}
	/**
	 * Generate the text ('Deactivate' or 'Activate' for the notice action link.
	 *
	 * @return string
	 */
	function bp_get_message_activate_deactivate_text() {
		global $messages_template;

		if ( 1 === (int) $messages_template->thread->is_active  ) {
			$text = __('Deactivate', 'buddypress');
		} else {
			$text = __('Activate', 'buddypress');
		}
		return apply_filters( 'bp_message_activate_deactivate_text', $text );
	}

/**
 * Output the messages component slug.
 *
 * @since BuddyPress (1.5.0)
 *
 * @uses bp_get_messages_slug()
 */
function bp_messages_slug() {
	echo bp_get_messages_slug();
}
	/**
	 * Return the messages component slug.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @return string
	 */
	function bp_get_messages_slug() {
		return apply_filters( 'bp_get_messages_slug', buddypress()->messages->slug );
	}

/**
 * Generate markup for currently active notices.
 */
function bp_message_get_notices() {
	$notice = BP_Messages_Notice::get_active();

	if ( empty( $notice ) ) {
		return false;
	}

	$closed_notices = bp_get_user_meta( bp_loggedin_user_id(), 'closed_notices', true );

	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( is_array( $closed_notices ) ) {
		if ( !in_array( $notice->id, $closed_notices ) && $notice->id ) {
			?>
			<div id="message" class="info notice" rel="n-<?php echo esc_attr( $notice->id ); ?>">
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

/**
 * Output the URL for the Private Message link in member profile headers.
 */
function bp_send_private_message_link() {
	echo bp_get_send_private_message_link();
}
	/**
	 * Generate the URL for the Private Message link in member profile headers.
	 *
	 * @return bool|string False on failure, otherwise the URL.
	 */
	function bp_get_send_private_message_link() {

		if ( bp_is_my_profile() || ! is_user_logged_in() ) {
			return false;
		}

		return apply_filters( 'bp_get_send_private_message_link', wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username( bp_displayed_user_id() ) ) );
	}

/**
 * Output the 'Private Message' button for member profile headers.
 *
 * Explicitly named function to avoid confusion with public messages.
 *
 * @since BuddyPress (1.2.6)
 *
 * @uses bp_get_send_message_button()
 */
function bp_send_private_message_button() {
	echo bp_get_send_message_button();
}

/**
 * Output the 'Private Message' button for member profile headers.
 */
function bp_send_message_button() {
	echo bp_get_send_message_button();
}
	/**
	 * Generate the 'Private Message' button for member profile headers.
	 *
	 * @return string
	 */
	function bp_get_send_message_button() {
		// Note: 'bp_get_send_message_button' is a legacy filter. Use
		// 'bp_get_send_message_button_args' instead. See #4536
		return apply_filters( 'bp_get_send_message_button',
			bp_get_button( apply_filters( 'bp_get_send_message_button_args', array(
				'id'                => 'private_message',
				'component'         => 'messages',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_id'        => 'send-private-message',
				'link_href'         => bp_get_send_private_message_link(),
				'link_title'        => __( 'Send a private message to this user.', 'buddypress' ),
				'link_text'         => __( 'Private Message', 'buddypress' ),
				'link_class'        => 'send-message',
			) ) )
		);
	}

/**
 * Output the URL of the Messages AJAX loader gif.
 */
function bp_message_loading_image_src() {
	echo bp_get_message_loading_image_src();
}
	/**
	 * Get the URL of the Messages AJAX loader gif.
	 *
	 * @return string
	 */
	function bp_get_message_loading_image_src() {
		return apply_filters( 'bp_get_message_loading_image_src', buddypress()->messages->image_base . '/ajax-loader.gif' );
	}

/**
 * Output the markup for the message recipient tabs.
 */
function bp_message_get_recipient_tabs() {
	$recipients = explode( ' ', bp_get_message_get_recipient_usernames() );

	foreach ( $recipients as $recipient ) {

		$user_id = bp_is_username_compatibility_mode()
			? bp_core_get_userid( $recipient )
			: bp_core_get_userid_from_nicename( $recipient );

		if ( ! empty( $user_id ) ) : ?>

			<li id="un-<?php echo esc_attr( $recipient ); ?>" class="friend-tab">
				<span><?php
					echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) );
					echo bp_core_get_userlink( $user_id );
				?></span>
			</li>

		<?php endif;
	}
}

/**
 * Output recipient usernames for prefilling the 'To' field on the Compose screen.
 */
function bp_message_get_recipient_usernames() {
	echo bp_get_message_get_recipient_usernames();
}
	/**
	 * Get the recipient usernames for prefilling the 'To' field on the Compose screen.
	 *
	 * @return string
	 */
	function bp_get_message_get_recipient_usernames() {
		$recipients = isset( $_GET['r'] ) ? stripslashes( $_GET['r'] ) : '';

		return apply_filters( 'bp_get_message_get_recipient_usernames', $recipients );
	}


/**
 * Message Thread Template Class
 */
class BP_Messages_Thread_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	public $current_message = -1;

	/**
	 * Number of messages returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	public $message_count = 0;

	/**
	 * The message object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	public $message;

	/**
	 * Thread that the current messages belong to.
	 *
	 * @access public
	 * @var BP_Messages_Thread
	 */
	public $thread;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_page = 1;

	/**
	 * The number of items being requested per page.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_num = 10;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	public $pag_links = '';

	/**
	 * The total number of messages matching the query.
	 *
	 * @access public
	 * @var int
	 */
	public $total_message_count = 0;

	/**
	 * Constructor method.
	 *
	 * @param int $thread_id ID of the message thread.
	 * @param string $order 'ASC' or 'DESC'.
	 */
	public function __construct( $thread_id, $order ) {
		$this->thread        = new BP_Messages_Thread( $thread_id, $order );
		$this->message_count = count( $this->thread->messages );

		$last_message_index                 = $this->message_count - 1;
		$this->thread->last_message_id      = $this->thread->messages[ $last_message_index ]->id;
		$this->thread->last_message_date    = $this->thread->messages[ $last_message_index ]->date_sent;
		$this->thread->last_sender_id       = $this->thread->messages[ $last_message_index ]->sender_id;
		$this->thread->last_message_subject = $this->thread->messages[ $last_message_index ]->subject;
		$this->thread->last_message_content = $this->thread->messages[ $last_message_index ]->message;
	}

	/**
	 * Whether there are messages available in the loop.
	 *
	 * @see bp_thread_has_messages()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_messages() {
		if ( ! empty( $this->message_count ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next member and iterate index.
	 *
	 * @return object The next member to iterate over.
	 */
	public function next_message() {
		$this->current_message++;
		$this->message = $this->thread->messages[ $this->current_message ];

		return $this->message;
	}

	/**
	 * Rewind the messages and reset message index.
	 */
	public function rewind_messages() {
		$this->current_message = -1;
		if ( $this->message_count > 0 ) {
			$this->message = $this->thread->messages[0];
		}
	}

	/**
	 * Whether there are messages left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_thread_messages()} as part of the
	 * while loop that controls iteration inside the messages loop, eg:
	 *     while ( bp_thread_messages() ) { ...
	 *
	 * @see bp_thread_messages()
	 *
	 * @return bool True if there are more messages to show, otherwise false.
	 */
	public function messages() {
		if ( ( $this->current_message + 1 ) < $this->message_count ) {
			return true;
		} elseif ( ( $this->current_message + 1 ) === $this->message_count ) {
			do_action( 'thread_loop_end' );
			// Do some cleaning up after the loop
			$this->rewind_messages();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current message inside the loop.
	 *
	 * Used by {@link bp_thread_the_message()} to set up the current
	 * message data while looping, so that template tags used during
	 * that iteration make reference to the current message.
	 *
	 * @see bp_thread_the_message()
	 */
	public function the_message() {
		$this->in_the_loop = true;
		$this->message     = $this->next_message();

		// loop has just started
		if ( 0 === $this->current_message ) {
			do_action( 'thread_loop_start' );
		}
	}
}

/**
 * Initialize the messages template loop for a specific thread.
 *
 * @param array $args {
 *     Array of arguments. All are optional.
 *     @type int $thread_id ID of the thread whose messages you are displaying.
 *           Default: if viewing a thread, the thread ID will be parsed from
 *           the URL (bp_action_variable( 0 )).
 *     @type string $order 'ASC' or 'DESC'. Default: 'ASC'.
 * }
 * @return bool True if there are messages to display, otherwise false.
 */
function bp_thread_has_messages( $args = '' ) {
	global $thread_template;

	$r = bp_parse_args( $args, array(
		'thread_id' => false,
		'order'     => 'ASC'
	), 'thread_has_messages' );

	if ( empty( $r['thread_id'] ) && bp_is_messages_component() && bp_is_current_action( 'view' ) ) {
		$r['thread_id'] = (int) bp_action_variable( 0 );
	}

	$thread_template = new BP_Messages_Thread_Template( $r['thread_id'], $r['order'] );

	return $thread_template->has_messages();
}

/**
 * Output the 'ASC' or 'DESC' messages order string for this loop.
 */
function bp_thread_messages_order() {
	echo bp_get_thread_messages_order();
}
	/**
	 * Get the 'ASC' or 'DESC' messages order string for this loop.
	 *
	 * @return string
	 */
	function bp_get_thread_messages_order() {
		global $thread_template;
		return $thread_template->thread->messages_order;
	}

/**
 * Check whether there are more messages to iterate over.
 *
 * @return bool
 */
function bp_thread_messages() {
	global $thread_template;

	return $thread_template->messages();
}

/**
 * Set up the current thread inside the loop.
 *
 * @return object
 */
function bp_thread_the_message() {
	global $thread_template;

	return $thread_template->the_message();
}

/**
 * Output the ID of the thread that the current loop belongs to.
 */
function bp_the_thread_id() {
	echo bp_get_the_thread_id();
}
	/**
	 * Get the ID of the thread that the current loop belongs to.
	 *
	 * @return int
	 */
	function bp_get_the_thread_id() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_id', $thread_template->thread->thread_id );
	}

/**
 * Output the subject of the thread currently being iterated over.
 */
function bp_the_thread_subject() {
	echo bp_get_the_thread_subject();
}
	/**
	 * Get the subject of the thread currently being iterated over.
	 *
	 * @return string
	 */
	function bp_get_the_thread_subject() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_subject', $thread_template->thread->last_message_subject );
	}

/**
 * Output HTML links to recipients in the current thread.
 */
function bp_the_thread_recipients() {
	echo bp_get_the_thread_recipients();
}
	/**
	 * Generate HTML links to the profiles of recipients in the current thread.
	 *
	 * @return string
	 */
	function bp_get_the_thread_recipients() {
		global $thread_template;

		$recipient_links = array();

		if ( count( $thread_template->thread->recipients ) >= 5 ) {
			return apply_filters( 'bp_get_the_thread_recipients', sprintf( __( '%d Recipients', 'buddypress' ), count( $thread_template->thread->recipients ) ) );
		}

		foreach( (array) $thread_template->thread->recipients as $recipient ) {
			if ( (int) $recipient->user_id !== bp_loggedin_user_id() ) {
				$recipient_link = bp_core_get_userlink( $recipient->user_id );

				if ( empty( $recipient_link ) ) {
					$recipient_link = __( 'Deleted User', 'buddypress' );
				}

				$recipient_links[] = $recipient_link;
			}
		}

		return apply_filters( 'bp_get_the_thread_recipients', implode( ', ', $recipient_links ) );
	}

/**
 * Echo the ID of the current message in the thread.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_the_thread_message_id() {
	echo bp_get_the_thread_message_id();
}
	/**
	 * Get the ID of the current message in the thread.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @return int
	 */
	function bp_get_the_thread_message_id() {
		global $thread_template;

		$thread_message_id = isset( $thread_template->message->id )
			? (int) $thread_template->message->id
			: null;

		return apply_filters( 'bp_get_the_thread_message_id', $thread_message_id );
	}

/**
 * Output the CSS classes for messages within a single thread.
 *
 * @since BuddyPress (2.1.0)
 */
function bp_the_thread_message_css_class() {
	echo bp_get_the_thread_message_css_class();
}
	/**
	 * Generate the CSS classes for messages within a single thread.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_css_class() {
		global $thread_template;

		$classes = array();

		// Zebra-striping
		$classes[] = bp_get_the_thread_message_alt_class();

		// ID of the sender
		$classes[] = 'sent-by-' . intval( $thread_template->message->sender_id );

		// Whether the sender is the same as the logged-in user
		if ( bp_loggedin_user_id() == $thread_template->message->sender_id ) {
			$classes[] = 'sent-by-me';
		}

		$classes = apply_filters( 'bp_get_the_thread_message_css_class', $classes );

		return implode( ' ', $classes );
	}

/**
 * Output the CSS class used for message zebra striping.
 */
function bp_the_thread_message_alt_class() {
	echo bp_get_the_thread_message_alt_class();
}
	/**
	 * Get the CSS class used for message zebra striping.
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_alt_class() {
		global $thread_template;

		if ( $thread_template->current_message % 2 == 1 ) {
			$class = 'even alt';
		} else {
			$class = 'odd';
		}

		return apply_filters( 'bp_get_the_thread_message_alt_class', $class );
	}

/**
 * Output the ID for message sender within a single thread
 *
 * @since BuddyPress (2.1.0)
 */
function bp_the_thread_message_sender_id() {
	echo bp_get_the_thread_message_sender_id();
}
	/**
	 * Return the ID for message sender within a single thread
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_sender_id() {
		global $thread_template;

		$user_id = ! empty( $thread_template->message->sender_id )
			? $thread_template->message->sender_id
			: 0;

		return (int) apply_filters( 'bp_get_the_thread_message_css_class', (int) $user_id );
	}

/**
 * Output the avatar for the current message sender.
 *
 * @param array $args See {@link bp_get_the_thread_message_sender_avatar_thumb()}
 *        for a description.
 */
function bp_the_thread_message_sender_avatar( $args = '' ) {
	echo bp_get_the_thread_message_sender_avatar_thumb( $args );
}
	/**
	 * Get the avatar for the current message sender.
	 *
	 * @param array $args {
	 *     Array of arguments. See {@link bp_core_fetch_avatar()} for more
	 *     complete details. All arguments are optional.
	 *     @type string $type Avatar type. Default: 'thumb'.
	 *     @type int $width Avatar width. Default: default for your $type.
	 *     @type int $height Avatar height. Default: default for your $type.
	 * }
	 * @return string <img> tag containing the avatar.
	 */
	function bp_get_the_thread_message_sender_avatar_thumb( $args = '' ) {
		global $thread_template;

		$r = bp_parse_args( $args, array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
		) );

		return apply_filters( 'bp_get_the_thread_message_sender_avatar_thumb', bp_core_fetch_avatar( array(
			'item_id' => $thread_template->message->sender_id,
			'type'    => $r['type'],
			'width'   => $r['width'],
			'height'  => $r['height'],
			'alt'     => bp_core_get_user_displayname( $thread_template->message->sender_id )
		) ) );
	}

/**
 * Output a link to the sender of the current message.
 */
function bp_the_thread_message_sender_link() {
	echo bp_get_the_thread_message_sender_link();
}
	/**
	 * Get a link to the sender of the current message.
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_sender_link() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_sender_link', bp_core_get_userlink( $thread_template->message->sender_id, false, true ) );
	}

/**
 * Output the display name of the sender of the current message.
 */
function bp_the_thread_message_sender_name() {
	echo bp_get_the_thread_message_sender_name();
}
	/**
	 * Get the display name of the sender of the current message.
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_sender_name() {
		global $thread_template;

		$display_name = bp_core_get_user_displayname( $thread_template->message->sender_id );

		if ( empty( $display_name ) ) {
			$display_name = __( 'Deleted User', 'buddypress' );
		}

		return apply_filters( 'bp_get_the_thread_message_sender_name', $display_name );
	}

/**
 * Output the URL for deleting the current thread.
 */
function bp_the_thread_delete_link() {
	echo bp_get_the_thread_delete_link();
}
	/**
	 * Get the URL for deleting the current thread.
	 *
	 * @return string URL
	 */
	function bp_get_the_thread_delete_link() {
		return apply_filters( 'bp_get_message_thread_delete_link', wp_nonce_url( bp_loggedin_user_domain() . bp_get_messages_slug() . '/inbox/delete/' . bp_get_the_thread_id(), 'messages_delete_thread' ) );
	}

/**
 * Output the 'Sent x hours ago' string for the current message.
 */
function bp_the_thread_message_time_since() {
	echo bp_get_the_thread_message_time_since();
}
	/**
	 * Generate the 'Sent x hours ago' string for the current message.
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_time_since() {
		return apply_filters( 'bp_get_the_thread_message_time_since', sprintf( __( 'Sent %s', 'buddypress' ), bp_core_time_since( bp_get_the_thread_message_date_sent() ) ) );
	}

/**
 * Output the timestamp for the current message.
 *
 * @since BuddyPress (2.1.0)
 */
function bp_the_thread_message_date_sent() {
	echo bp_get_the_thread_message_date_sent();
}
	/**
	 * Generate the 'Sent x hours ago' string for the current message.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @uses strtotime() To convert the message string into a usable timestamp
	 * @return int
	 */
	function bp_get_the_thread_message_date_sent() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_date_sent', strtotime( $thread_template->message->date_sent ) );
	}

/**
 * Output the content of the current message in the loop.
 */
function bp_the_thread_message_content() {
	echo bp_get_the_thread_message_content();
}
	/**
	 * Get the content of the current message in the loop.
	 *
	 * @return string
	 */
	function bp_get_the_thread_message_content() {
		global $thread_template;

		return apply_filters( 'bp_get_the_thread_message_content', $thread_template->message->message );
	}

/** Embeds *******************************************************************/

/**
 * Enable oEmbed support for Messages.
 *
 * There's no caching as BP 1.5 does not have a Messages meta API.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see BP_Embed
 *
 * @todo Add Messages meta?
 */
function bp_messages_embed() {
	add_filter( 'embed_post_id', 'bp_get_message_thread_id' );
}
add_action( 'messages_box_loop_start', 'bp_messages_embed' );
