<?php
/**
 * BuddyPress Messages Box Template Class.
 *
 * @package BuddyPress
 * @subpackage MessagesTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Message Box Template Class
 */
class BP_Messages_Box_Template {

	/**
	 * The loop iterator.
	 *
	 * @var int
	 */
	public $current_thread = -1;

	/**
	 * The number of threads returned by the paged query.
	 *
	 * @var int
	 */
	public $current_thread_count = 0;

	/**
	 * Total number of threads matching the query params.
	 *
	 * @var int
	 */
	public $total_thread_count = 0;

	/**
	 * Array of threads located by the query.
	 *
	 * @var array
	 */
	public $threads = array();

	/**
	 * The thread object currently being iterated on.
	 *
	 * @var object
	 */
	public $thread = false;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * User ID of the current inbox.
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * The current "box" view ('notices', 'sentbox', 'inbox').
	 *
	 * @var string
	 */
	public $box = 'inbox';

	/**
	 * The page number being requested.
	 *
	 * @var int
	 */
	public $pag_page = 1;

	/**
	 * The number of items being requested per page.
	 *
	 * @var int
	 */
	public $pag_num = 10;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @var string
	 */
	public $pag_links = '';

	/**
	 * Search terms for limiting the thread query.
	 *
	 * @var string
	 */
	public $search_terms = '';

	/**
	 * Constructor method.
	 *
	 * @param array $args {
	 *     Array of arguments. See bp_has_message_threads() for full description.
	 * }
	 */
	public function __construct( $args = array() ) {
		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
			_deprecated_argument( __METHOD__, '2.2.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'box',
				2 => 'per_page',
				3 => 'max',
				4 => 'type',
				5 => 'search_terms',
				6 => 'page_arg'
			);

			$args = bp_core_parse_args_array( $old_args_keys, $function_args );
		}

		$r = wp_parse_args( $args, array(
			'page'         => 1,
			'per_page'     => 10,
			'page_arg'     => 'mpage',
			'box'          => 'inbox',
			'type'         => 'all',
			'user_id'      => bp_loggedin_user_id(),
			'max'          => false,
			'search_terms' => '',
			'meta_query'   => array(),
		) );

		$this->pag_arg      = sanitize_key( $r['page_arg'] );
		$this->pag_page     = bp_sanitize_pagination_arg( $this->pag_arg, $r['page']     );
		$this->pag_num      = bp_sanitize_pagination_arg( 'num',          $r['per_page'] );
		$this->user_id      = $r['user_id'];
		$this->box          = $r['box'];
		$this->type         = $r['type'];
		$this->search_terms = $r['search_terms'];

		if ( 'notices' === $this->box ) {
			$this->threads = BP_Messages_Notice::get_notices( array(
				'pag_num'  => $this->pag_num,
				'pag_page' => $this->pag_page
			) );
		} else {
			$threads = BP_Messages_Thread::get_current_threads_for_user( array(
				'user_id'      => $this->user_id,
				'box'          => $this->box,
				'type'         => $this->type,
				'limit'        => $this->pag_num,
				'page'         => $this->pag_page,
				'search_terms' => $this->search_terms,
				'meta_query'   => $r['meta_query'],
			) );

			$this->threads            = isset( $threads['threads'] ) ? $threads['threads'] : array();
			$this->total_thread_count = isset( $threads['total'] ) ? $threads['total'] : 0;
		}

		if ( !$this->threads ) {
			$this->thread_count       = 0;
			$this->total_thread_count = 0;
		} else {
			$total_notice_count = BP_Messages_Notice::get_total_notice_count();

			if ( empty( $r['max'] ) || ( (int) $r['max'] >= (int) $total_notice_count ) ) {
				if ( 'notices' === $this->box ) {
					$this->total_thread_count = (int) $total_notice_count;
				}
			} else {
				$this->total_thread_count = (int) $r['max'];
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $r['max'] >= count( $this->threads ) ) {
					$this->thread_count = count( $this->threads );
				} else {
					$this->thread_count = (int) $r['max'];
				}
			} else {
				$this->thread_count = count( $this->threads );
			}
		}

		if ( (int) $this->total_thread_count && (int) $this->pag_num ) {
			$pag_args = array(
				$r['page_arg'] => '%#%',
			);

			if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
				$base = remove_query_arg( 's', wp_get_referer() );
			} else {
				$base = '';
			}

			$add_args = array();

			if ( ! empty( $this->search_terms ) ) {
				$add_args['s'] = $this->search_terms;
			}

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $pag_args, $base ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_thread_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Message pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Message pagination next text', 'buddypress' ),
				'mid_size'  => 1,
				'add_args'  => $add_args,
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

			/**
			 * Fires when at the end of threads to iterate over.
			 *
			 * @since 1.5.0
			 */
			do_action( 'messages_box_loop_end' );
			// Do some cleaning up after the loop.
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

			// Set up the last message data.
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

		// Loop has just started.
		if ( 0 == $this->current_thread ) {

			/**
			 * Fires if at the start of the message thread loop.
			 *
			 * @since 1.5.0
			 */
			do_action( 'messages_box_loop_start' );
		}
	}
}
