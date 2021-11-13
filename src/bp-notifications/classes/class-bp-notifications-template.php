<?php
/**
 * BuddyPress Notifications Template Loop Class.
 *
 * @package BuddyPress
 * @subpackage TonificationsTemplate
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main notifications template loop class.
 *
 * Responsible for loading a group of notifications into a loop for display.
 *
 * @since 1.9.0
 */
class BP_Notifications_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $current_notification = -1;

	/**
	 * The number of notifications returned by the paged query.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $current_notification_count;

	/**
	 * Total number of notifications matching the query.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $total_notification_count;

	/**
	 * Array of notifications located by the query.
	 *
	 * @since 1.9.0
	 * @var array
	 */
	public $notifications;

	/**
	 * The notification object currently being iterated on.
	 *
	 * @since 1.9.0
	 * @var object
	 */
	public $notification;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.9.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The ID of the user to whom the displayed notifications belong.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $user_id;

	/**
	 * The page number being requested.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The $_GET argument used in URLs for determining pagination.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $pag_arg;

	/**
	 * The number of items to display per page of results.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * A string to match against.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $search_terms;

	/**
	 * A database column to order the results by.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $order_by;

	/**
	 * The direction to sort the results (ASC or DESC).
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $sort_order;

	/**
	 * Array of variables used in this notification query.
	 *
	 * @since 2.2.2
	 * @var array
	 */
	public $query_vars;

	/**
	 * Constructor method.
	 *
	 * @since 1.9.0
	 *
	 * @param array $args {
	 *     An array of arguments. See {@link bp_has_notifications()}
	 *     for more details.
	 * }
	 */
	public function __construct( $args = array() ) {

		// Parse arguments.
		$r = bp_parse_args(
			$args,
			array(
				'id'                => false,
				'user_id'           => 0,
				'item_id'           => false,
				'secondary_item_id' => false,
				'component_name'    => bp_notifications_get_registered_components(),
				'component_action'  => false,
				'is_new'            => true,
				'search_terms'      => '',
				'order_by'          => 'date_notified',
				'sort_order'        => 'DESC',
				'page_arg'          => 'npage',
				'page'              => 1,
				'per_page'          => 25,
				'max'               => null,
				'meta_query'        => false,
				'date_query'        => false,
			)
		);

		// Sort order direction.
		if ( ! empty( $_GET['sort_order'] ) ) {
			$r['sort_order'] = $_GET['sort_order'];
		}

		// Setup variables.
		$this->pag_arg      = sanitize_key( $r['page_arg'] );
		$this->pag_page     = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num      = bp_sanitize_pagination_arg( 'num', $r['per_page'] );
		$this->sort_order   = bp_esc_sql_order( $r['sort_order'] );
		$this->user_id      = $r['user_id'];
		$this->is_new       = $r['is_new'];
		$this->search_terms = $r['search_terms'];
		$this->order_by     = $r['order_by'];
		$this->query_vars   = array(
			'id'                => $r['id'],
			'user_id'           => $this->user_id,
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'component_name'    => $r['component_name'],
			'component_action'  => $r['component_action'],
			'meta_query'        => $r['meta_query'],
			'date_query'        => $r['date_query'],
			'is_new'            => $this->is_new,
			'search_terms'      => $this->search_terms,
			'order_by'          => $this->order_by,
			'sort_order'        => $this->sort_order,
			'page'              => $this->pag_page,
			'per_page'          => $this->pag_num,
		);

		// Setup the notifications to loop through.
		$this->notifications            = BP_Notifications_Notification::get( $this->query_vars );
		$this->total_notification_count = BP_Notifications_Notification::get_total_count( $this->query_vars );

		if ( empty( $this->notifications ) ) {
			$this->notification_count       = 0;
			$this->total_notification_count = 0;

		} else {
			if ( ! empty( $r['max'] ) ) {
				if ( $r['max'] >= count( $this->notifications ) ) {
					$this->notification_count = count( $this->notifications );
				} else {
					$this->notification_count = (int) $r['max'];
				}
			} else {
				$this->notification_count = count( $this->notifications );
			}
		}

		if ( (int) $this->total_notification_count && (int) $this->pag_num ) {
			$add_args = array(
				'sort_order' => $this->sort_order,
			);

			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->pag_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_notification_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Notifications pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Notifications pagination next text', 'buddypress' ),
				'mid_size'  => 1,
				'add_args'  => $add_args,
			) );
		}
	}

	/**
	 * Whether there are notifications available in the loop.
	 *
	 * @since 1.9.0
	 *
	 * @see bp_has_notifications()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_notifications() {
		return ! empty( $this->notification_count );
	}

	/**
	 * Set up the next notification and iterate index.
	 *
	 * @since 1.9.0
	 *
	 * @return BP_Notifications_Notification The next notification to iterate over.
	 */
	public function next_notification() {

		$this->current_notification++;

		$this->notification = $this->notifications[ $this->current_notification ];

		return $this->notification;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 *
	 * @since 1.9.0
	 */
	public function rewind_notifications() {

		$this->current_notification = -1;

		if ( $this->notification_count > 0 ) {
			$this->notification = $this->notifications[0];
		}
	}

	/**
	 * Whether there are notifications left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_notifications()} as part of the
	 * while loop that controls iteration inside the notifications loop, eg:
	 *     while ( bp_notifications() ) { ...
	 *
	 * @since 1.9.0
	 *
	 * @see bp_notifications()
	 *
	 * @return bool True if there are more notifications to show,
	 *              otherwise false.
	 */
	public function notifications() {

		if ( $this->current_notification + 1 < $this->notification_count ) {
			return true;

		} elseif ( $this->current_notification + 1 === $this->notification_count ) {

			/**
			 * Fires right before the rewinding of notification posts.
			 *
			 * @since 1.9.0
			 */
			do_action( 'notifications_loop_end' );

			$this->rewind_notifications();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current notification inside the loop.
	 *
	 * Used by {@link bp_the_notification()} to set up the current
	 * notification data while looping, so that template tags used during
	 * that iteration make reference to the current notification.
	 *
	 * @since 1.9.0
	 *
	 * @see bp_the_notification()
	 */
	public function the_notification() {
		$this->in_the_loop  = true;
		$this->notification = $this->next_notification();

		// Loop has just started.
		if ( 0 === $this->current_notification ) {

			/**
			 * Fires if the current notification item is the first in the notification loop.
			 *
			 * @since 1.9.0
			 */
			do_action( 'notifications_loop_start' );
		}
	}
}
