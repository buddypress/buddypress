<?php
/**
 * BuddyPress Groups Invitation template loop class.
 *
 * @package BuddyPress
 * @since 1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Group invitation template loop class.
 *
 * @since 1.1.0
 */
class BP_Groups_Invite_Template {

	/**
	 * @since 1.1.0
	 * @var int
	 */
	public $current_invite = -1;

	/**
	 * @since 1.1.0
	 * @var int
	 */
	public $invite_count;

	/**
	 * @since 1.1.0
	 * @var array
	 */
	public $invites;

	/**
	 * @since 1.1.0
	 * @var object
	 */
	public $invite;

	/**
	 * @since 1.1.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * @since 1.1.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * @since 1.1.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * @since 1.1.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * @since 1.1.0
	 * @var int
	 */
	public $total_invite_count;

	/**
	 * BP_Groups_Invite_Template constructor.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.0.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0  => 'user_id',
				1  => 'group_id',
			);

			$args = bp_core_parse_args_array( $old_args_keys, func_get_args() );
		}

		$r = bp_parse_args( $args, array(
			'page'     => 1,
			'per_page' => 10,
			'page_arg' => 'invitepage',
			'user_id'  => bp_loggedin_user_id(),
			'group_id' => bp_get_current_group_id(),
		), 'groups_invite_template' );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page']     );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num',          $r['per_page'] );

		$iquery = new BP_Group_Member_Query( array(
			'group_id' => $r['group_id'],
			'type'     => 'first_joined',
			'per_page' => $this->pag_num,
			'page'     => $this->pag_page,

			// These filters ensure we get only pending invites.
			'is_confirmed' => false,
			'inviter_id'   => $r['user_id'],
		) );

		$this->invite_data        = $iquery->results;
		$this->total_invite_count = $iquery->total_users;
		$this->invites            = array_values( wp_list_pluck( $this->invite_data, 'ID' ) );
		$this->invite_count       = count( $this->invites );

		// If per_page is set to 0 (show all results), don't generate
		// pag_links.
		if ( ! empty( $this->pag_num ) ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $this->pag_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( $this->total_invite_count / $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1,
				'add_args'  => array(),
			) );
		} else {
			$this->pag_links = '';
		}
	}

	/**
	 * Whether or not there are invites to show.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function has_invites() {
		if ( ! empty( $this->invite_count ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Increments up to the next invite to show.
	 *
	 * @since 1.1.0
	 *
	 * @return object
	 */
	public function next_invite() {
		$this->current_invite++;
		$this->invite = $this->invites[ $this->current_invite ];

		return $this->invite;
	}

	/**
	 * Rewinds to the first invite to show.
	 *
	 * @since 1.1.0
	 */
	public function rewind_invites() {
		$this->current_invite = -1;
		if ( $this->invite_count > 0 ) {
			$this->invite = $this->invites[0];
		}
	}

	/**
	 * Finishes up the invites to show.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function invites() {
		$tick = intval( $this->current_invite + 1 );
		if ( $tick < $this->invite_count ) {
			return true;
		} elseif ( $tick == $this->invite_count ) {

			/**
			 * Fires right before the rewinding of invites list.
			 *
			 * @since 1.1.0
			 * @since 2.3.0 `$this` parameter added.
			 * @since 2.7.0 Action renamed from `loop_start`.
			 *
			 * @param BP_Groups_Invite_Template $this Instance of the current Invites template.
			 */
			do_action( 'group_invitation_loop_end', $this );

			// Do some cleaning up after the loop
			$this->rewind_invites();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Sets up the invite to show.
	 *
	 * @since 1.1.0
	 */
	public function the_invite() {
		global $group_id;

		$this->in_the_loop  = true;
		$user_id            = $this->next_invite();

		$this->invite       = new stdClass;
		$this->invite->user = $this->invite_data[ $user_id ];

		// This method previously populated the user object with
		// BP_Core_User. We manually configure BP_Core_User data for
		// backward compatibility.
		if ( bp_is_active( 'xprofile' ) ) {
			$this->invite->user->profile_data = BP_XProfile_ProfileData::get_all_for_user( $user_id );
		}

		$this->invite->user->avatar       = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full',  'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_thumb = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ) ) );
		$this->invite->user->avatar_mini  = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->invite->user->fullname ), 'width' => 30, 'height' => 30 ) );
		$this->invite->user->email        = $this->invite->user->user_email;
		$this->invite->user->user_url     = bp_core_get_user_domain( $user_id, $this->invite->user->user_nicename, $this->invite->user->user_login );
		$this->invite->user->user_link    = "<a href='{$this->invite->user->user_url}'>{$this->invite->user->fullname}</a>";
		$this->invite->user->last_active  = bp_core_get_last_activity( $this->invite->user->last_activity, __( 'active %s', 'buddypress' ) );

		if ( bp_is_active( 'groups' ) ) {
			$total_groups = BP_Groups_Member::total_group_count( $user_id );
			$this->invite->user->total_groups = sprintf( _n( '%d group', '%d groups', $total_groups, 'buddypress' ), $total_groups );
		}

		if ( bp_is_active( 'friends' ) ) {
			$this->invite->user->total_friends = BP_Friends_Friendship::total_friend_count( $user_id );
		}

		$this->invite->user->total_blogs = null;

		// Global'ed in bp_group_has_invites()
		$this->invite->group_id = $group_id;

		// loop has just started
		if ( 0 == $this->current_invite ) {

			/**
			 * Fires if the current invite item is the first in the loop.
			 *
			 * @since 1.1.0
			 * @since 2.3.0 `$this` parameter added.
			 * @since 2.7.0 Action renamed from `loop_start`.
			 *
			 * @param BP_Groups_Invite_Template $this Instance of the current Invites template.
			 */
			do_action( 'group_invitation_loop_start', $this );
		}
	}
}
