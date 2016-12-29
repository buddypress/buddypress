<?php
/**
 * BuddyPress Groups membership request template loop class.
 *
 * @package BuddyPress
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership request template loop class.
 *
 * @since 1.0.0
 */
class BP_Groups_Membership_Requests_Template {

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $current_request = -1;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $request_count;

	/**
	 * @since 1.0.0
	 * @var array
	 */
	public $requests;

	/**
	 * @since 1.0.0
	 * @var object
	 */
	public $request;

	/**
	 * @sine 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * @since 1.0.0
	 * @var array|string|void
	 */
	public $pag_links;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	public $total_request_count;

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args {
	 *     @type int $group_id ID of the group whose membership requests
	 *                         are being queried. Default: current group id.
	 *     @type int $per_page Number of records to return per page of
	 *                         results. Default: 10.
	 *     @type int $page     Page of results to show. Default: 1.
	 *     @type int $max      Max items to return. Default: false (show all)
	 * }
	 */
	public function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'group_id',
				1 => 'per_page',
				2 => 'max',
			);

			$args = bp_core_parse_args_array( $old_args_keys, func_get_args() );
		}

		$r = wp_parse_args( $args, array(
			'page'     => 1,
			'per_page' => 10,
			'page_arg' => 'mrpage',
			'max'      => false,
			'type'     => 'first_joined',
			'group_id' => bp_get_current_group_id(),
		) );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page']     );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $r['per_page'] );

		$mquery = new BP_Group_Member_Query( array(
			'group_id' => $r['group_id'],
			'type'     => $r['type'],
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we only get pending requests.
			'is_confirmed' => false,
			'inviter_id'   => 0,
		) );

		$this->requests      = array_values( $mquery->results );
		$this->request_count = count( $this->requests );

		// Compatibility with legacy format of request data objects.
		foreach ( $this->requests as $rk => $rv ) {
			// For legacy reasons, the 'id' property of each
			// request must match the membership id, not the ID of
			// the user (as it's returned by BP_Group_Member_Query).
			$this->requests[ $rk ]->user_id = $rv->ID;
			$this->requests[ $rk ]->id      = $rv->membership_id;

			// Miscellaneous values.
			$this->requests[ $rk ]->group_id   = $r['group_id'];
		}

		if ( empty( $r['max'] ) || ( $r['max'] >= (int) $mquery->total_users ) ) {
			$this->total_request_count = (int) $mquery->total_users;
		} else {
			$this->total_request_count = (int) $r['max'];
		}

		if ( empty( $r['max'] ) || ( $r['max'] >= count( $this->requests ) ) ) {
			$this->request_count = count( $this->requests );
		} else {
			$this->request_count = (int) $r['max'];
		}

		$this->pag_links = paginate_links( array(
			'base'      => add_query_arg( $this->pag_arg, '%#%' ),
			'format'    => '',
			'total'     => ceil( $this->total_request_count / $this->pag_num ),
			'current'   => $this->pag_page,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
			'mid_size'  => 1,
			'add_args'  => array(),
		) );
	}

	/**
	 * Whether or not there are requests to show.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_requests() {
		if ( ! empty( $this->request_count ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Moves up to the next request.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function next_request() {
		$this->current_request++;
		$this->request = $this->requests[ $this->current_request ];

		return $this->request;
	}

	/**
	 * Rewinds the requests to the first in the list.
	 *
	 * @since 1.0.0
	 */
	public function rewind_requests() {
		$this->current_request = -1;

		if ( $this->request_count > 0 ) {
			$this->request = $this->requests[0];
		}
	}

	/**
	 * Finishes up the requests to display.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function requests() {
		$tick = intval( $this->current_request + 1 );
		if ( $tick < $this->request_count ) {
			return true;
		} elseif ( $tick == $this->request_count ) {

			/**
			 * Fires right before the rewinding of group membership requests list.
			 *
			 * @since 1.5.0
			 */
			do_action( 'group_request_loop_end' );
			// Do some cleaning up after the loop.
			$this->rewind_requests();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Sets up the request to display.
	 *
	 * @since 1.0.0
	 */
	public function the_request() {
		$this->in_the_loop = true;
		$this->request     = $this->next_request();

		// Loop has just started.
		if ( 0 == $this->current_request ) {

			/**
			 * Fires if the current group membership request item is the first in the loop.
			 *
			 * @since 1.1.0
			 */
			do_action( 'group_request_loop_start' );
		}
	}
}
