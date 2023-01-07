<?php
/**
 * BuddyPress Messages Thread Template Class.
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Message Thread Template Class
 */
class BP_Messages_Thread_Template {

	/**
	 * The loop iterator.
	 *
	 * @var int
	 */
	public $current_message = -1;

	/**
	 * Number of messages returned by the paged query.
	 *
	 * @var int
	 */
	public $message_count = 0;

	/**
	 * The message object currently being iterated on.
	 *
	 * @var object
	 */
	public $message;

	/**
	 * Thread that the current messages belong to.
	 *
	 * @var BP_Messages_Thread
	 */
	public $thread;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @var bool
	 */
	public $in_the_loop = false;

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
	 * The total number of messages matching the query.
	 *
	 * @var int
	 */
	public $total_message_count = 0;

	/**
	 * Constructor method.
	 *
	 * @see BP_Messages_Thread::populate() for full parameter info.
	 *
	 * @param int    $thread_id ID of the message thread to display.
	 * @param string $order     Optional. Order to show the thread's messages in.
	 *                          Default: 'ASC'.
	 * @param array  $args      Array of arguments for the query.
	 */
	public function __construct( $thread_id = 0, $order = 'ASC', $args = array() ) {
		$this->thread        = new BP_Messages_Thread( $thread_id, $order, $args );
		$this->message_count = count( $this->thread->messages );
	}

	/**
	 * Whether there are messages available in the loop.
	 *
	 * @see bp_thread_has_messages()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_messages() {
		return ( ! empty( $this->message_count ) );
	}

	/**
	 * Set up the next message and iterate index.
	 *
	 * @return object The next message to iterate over.
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

			/**
			 * Fires when at the end of messages to iterate over.
			 *
			 * @since 1.1.0
			 */
			do_action( 'thread_loop_end' );
			// Do some cleaning up after the loop.
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

		// Loop has just started.
		if ( 0 === $this->current_message ) {

			/**
			 * Fires if at the start of the message loop.
			 *
			 * @since 1.1.0
			 */
			do_action( 'thread_loop_start' );
		}
	}
}
