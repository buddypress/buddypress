<?php
/**
 * BuddyPress Members Invitation Template Loop Class.
 *
 * @package BuddyPress
 * @subpackage TonificationsTemplate
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main membership invitations template loop class.
 *
 * Responsible for loading a group of membership invitations into a loop for display.
 *
 * @since 8.0.0
 */
class BP_Members_Invitations_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $current_invitation = -1;

	/**
	 * The number of invitations returned by the paged query.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $current_invitation_count;

	/**
	 * Total number of invitations matching the query.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $total_invitation_count;

	/**
	 * Array of network invitations located by the query.
	 *
	 * @since 8.0.0
	 * @var array
	 */
	public $invitations;

	/**
	 * The invitation object currently being iterated on.
	 *
	 * @since 8.0.0
	 * @var object
	 */
	public $invitation;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 8.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The ID of the user to whom the displayed invitations were sent.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $user_id;

	/**
	 * The ID of the user to whom the displayed invitations belong.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $inviter_id;

	/**
	 * The page number being requested.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The $_GET argument used in URLs for determining pagination.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $pag_arg;

	/**
	 * The number of items to display per page of results.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since 8.0.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * A string to match against.
	 *
	 * @since 8.0.0
	 * @var string
	 */
	public $search_terms;

	/**
	 * A database column to order the results by.
	 *
	 * @since 8.0.0
	 * @var string
	 */
	public $order_by;

	/**
	 * The direction to sort the results (ASC or DESC).
	 *
	 * @since 8.0.0
	 * @var string
	 */
	public $sort_order;

	/**
	 * Array of variables used in this invitation query.
	 *
	 * @since 8.0.0
	 * @var array
	 */
	public $query_vars;

	/**
	 * Constructor method.
	 *
	 * @see bp_has_members_invitations() For information on the array format.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args {
	 *     An array of arguments. See {@link bp_has_members_invitations()}
	 *     for more details.
	 * }
	 */
	public function __construct( $args = array() ) {

		// Parse arguments.
		$r = bp_parse_args(
			$args,
			array(
				'id'            => false,
				'user_id'       => false,
				'inviter_id'    => false,
				'invitee_email' => false,
				'item_id'       => false,
				'type'          => 'invite',
				'invite_sent'   => 'all',
				'accepted'      => 'all',
				'search_terms'  => '',
				'order_by'      => 'date_modified',
				'sort_order'    => 'DESC',
				'page'          => 1,
				'per_page'      => 25,
				'fields'        => 'all',
				'page_arg'      => 'ipage',
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
		$this->search_terms = $r['search_terms'];
		$this->order_by     = $r['order_by'];
		$this->query_vars   = array(
			'id'            => $r['id'],
			'user_id'       => $r['user_id'],
			'inviter_id'    => $r['inviter_id'],
			'invitee_email' => $r['invitee_email'],
			'item_id'       => $r['item_id'],
			'type'          => $r['type'],
			'invite_sent'   => $r['invite_sent'],
			'accepted'      => $r['accepted'],
			'search_terms'  => $this->search_terms,
			'order_by'      => $this->order_by,
			'sort_order'    => $this->sort_order,
			'page'          => $this->pag_page,
			'per_page'      => $this->pag_num,
		);

		// Setup the invitations to loop through.
		$invites_class = new BP_Members_Invitation_Manager();

		$this->invitations              = $invites_class->get_invitations( $this->query_vars );
		$this->current_invitation_count = count( $this->invitations );
		$this->total_invitation_count   = $invites_class->get_invitations_total_count( $this->query_vars );

		if ( (int) $this->total_invitation_count && (int) $this->pag_num ) {
			$add_args = array(
				'sort_order' => $this->sort_order,
			);

			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_invitation_count / (int) $this->pag_num ),
					'current'   => $this->pag_page,
					'prev_text' => _x( '&larr;', 'Network invitation pagination previous text', 'buddypress' ),
					'next_text' => _x( '&rarr;', 'Network invitation pagination next text', 'buddypress' ),
					'mid_size'  => 1,
					'add_args'  => $add_args,
				)
			);
		}
	}

	/**
	 * Whether there are invitations available in the loop.
	 *
	 * @since 8.0.0
	 *
	 * @see bp_has_members_invitations()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_invitations() {
		return ! empty( $this->current_invitation_count );
	}

	/**
	 * Set up the next invitation and iterate index.
	 *
	 * @since 8.0.0
	 *
	 * @return object The next invitation to iterate over.
	 */
	public function next_invitation() {

		$this->current_invitation++;

		$this->invitation = $this->invitations[ $this->current_invitation ];

		return $this->invitation;
	}

	/**
	 * Rewind the blogs and reset blog index.
	 *
	 * @since 8.0.0
	 */
	public function rewind_invitations() {

		$this->current_invitation = -1;

		if ( $this->current_invitation_count > 0 ) {
			$this->invitation = $this->invitations[0];
		}
	}

	/**
	 * Whether there are invitations left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_members_invitations()} as part of the
	 * while loop that controls iteration inside the invitations loop, eg:
	 *     while ( bp_members_invitations() ) { ...
	 *
	 * @since 8.0.0
	 *
	 * @see bp_members_invitations()
	 *
	 * @return bool True if there are more invitations to show,
	 *              otherwise false.
	 */
	public function invitations() {

		if ( $this->current_invitation + 1 < $this->current_invitation_count ) {
			return true;

		} elseif ( $this->current_invitation + 1 === $this->current_invitation_count ) {

			/**
			 * Fires right before the rewinding of invitation posts.
			 *
			 * @since 8.0.0
			 */
			do_action( 'members_invitations_loop_end' );

			$this->rewind_invitations();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current invitation inside the loop.
	 *
	 * Used by {@link bp_the_invitation()} to set up the current
	 * invitation data while looping, so that template tags used during
	 * that iteration make reference to the current invitation.
	 *
	 * @since 8.0.0
	 *
	 * @see bp_the_invitation()
	 */
	public function the_invitation() {
		$this->in_the_loop = true;
		$this->invitation  = $this->next_invitation();

		// Loop has just started.
		if ( 0 === $this->current_invitation ) {

			/**
			 * Fires if the current invitation item is the first in the invitation loop.
			 *
			 * @since 8.0.0
			 */
			do_action( 'members_invitations_loop_start' );
		}
	}
}
