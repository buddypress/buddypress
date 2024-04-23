<?php
/**
 * BuddyPress Membership Invitation List Table class.
 *
 * @package BuddyPress
 * @subpackage MembersAdminClasses
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for Invitations admin page.
 *
 * @since 8.0.0
 */
class BP_Members_Invitations_List_Table extends WP_Users_List_Table {

	/**
	 * The type of view currently being displayed.
	 *
	 * E.g. "All", "Pending", "Sent", "Unsent"...
	 *
	 * @since 8.0.0
	 * @var string
	 */
	public $active_filters = array();

	/**
	 * Invitation counts.
	 *
	 * @since 8.0.0
	 * @var int
	 */
	public $total_items = 0;

	/**
	 * Constructor.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'invitations',
			'singular' => 'invitation',
			'screen'   => get_current_screen()->id,
		) );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 8.0.0
	 */
	public function prepare_items() {
		$search   = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );
		$paged    = $this->get_pagenum();

		$args = array(
			'invite_sent'  => 'all',
			'accepted'     => 'all',
			'search_terms' => $search,
			'order_by'     => 'date_modified',
			'sort_order'   => 'DESC',
			'page'         => $paged,
			'per_page'     => $per_page,
		);

		if ( isset( $_REQUEST['accepted'] ) && in_array( $_REQUEST['accepted'], array( 'pending', 'accepted' ), true ) ) {
			$args['accepted']       = $_REQUEST['accepted'];
			$this->active_filters[] = $_REQUEST['accepted'];
		}
		if ( isset( $_REQUEST['sent'] ) && in_array( $_REQUEST['sent'], array( 'draft', 'sent' ), true ) ) {
			$args['invite_sent']    = $_REQUEST['sent'];
			$this->active_filters[] = $_REQUEST['sent'];
		}

		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['order_by'] = $_REQUEST['orderby'];
		}

		if ( isset( $_REQUEST['order'] ) ) {
			$args['sort_order'] = $_REQUEST['order'];
		}

		$invites_class     = new BP_Members_Invitation_Manager();
		$this->items       = $invites_class->get_invitations( $args );
		$this->total_items = $invites_class->get_invitations_total_count( $args );

		$this->set_pagination_args( array(
			'total_items' => $this->total_items,
			'per_page'    => $per_page,
		) );
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 10.1.0
	 *
	 * @return string Name of the default primary column, in this case, 'invitee_email'.
	 */
	protected function get_default_primary_column_name() {
		return 'invitee_email';
	}

	/**
	 * Get the list of views available on this table (e.g. "all", "public").
	 *
	 * @since 8.0.0
	 */
	public function views() {
		$tools_url = bp_get_admin_url( 'tools.php' );

		if ( is_network_admin() ) {
			$tools_url = network_admin_url( 'admin.php' );
		}

		$url_base = add_query_arg(
			array(
				'page' => 'bp-members-invitations',
			),
			$tools_url
		);
		?>

		<h2 class="screen-reader-text">
			<?php
			/* translators: accessibility text */
			esc_html_e( 'Filter invitations list', 'buddypress' );
			?>
		</h2>
		<ul class="subsubsub">
			<li class="all">
				<a href="<?php echo esc_url( $url_base ); ?>" class="<?php if ( empty( $this->active_filters ) ) echo 'current'; ?>">
					<?php esc_html_e( 'All', 'buddypress' ); ?>
				</a> |
			</li>
			<li class="pending">
				<a href="<?php echo esc_url( add_query_arg( 'accepted', 'pending', $url_base ) ); ?>" class="<?php if ( in_array( 'pending', $this->active_filters, true ) ) echo 'current'; ?>">
					<?php esc_html_e( 'Pending', 'buddypress' ); ?>
				</a> |
			</li>
			<li class="accepted">
				<a href="<?php echo esc_url( add_query_arg( 'accepted', 'accepted', $url_base ) ); ?>" class="<?php if ( in_array( 'accepted', $this->active_filters, true ) ) echo 'current'; ?>">
					<?php esc_html_e( 'Accepted', 'buddypress' ); ?>
				</a> |
			</li>
			<li class="draft">
				<a href="<?php echo esc_url( add_query_arg( 'sent', 'draft', $url_base ) ); ?>" class="<?php if ( in_array( 'draft', $this->active_filters, true ) ) echo 'current'; ?>">
					<?php esc_html_e( 'Draft (Unsent)', 'buddypress' ); ?>
				</a> |
			</li>
			<li class="sent">
				<a href="<?php echo esc_url( add_query_arg( 'sent', 'sent', $url_base ) ); ?>" class="<?php if ( in_array( 'sent', $this->active_filters, true ) ) echo 'current'; ?>">
					<?php esc_html_e( 'Sent', 'buddypress' ); ?>
				</a>
			</li>

			<?php

			/**
			 * Fires inside listing of views so plugins can add their own.
			 *
			 * @since 8.0.0
			 *
			 * @param string $url_base       Current URL base for view.
			 * @param array  $active_filters Current filters being requested.
			 */
			do_action( 'bp_members_invitations_list_table_get_views', $url_base, $this->active_filters ); ?>
		</ul>
	<?php
	}

	/**
	 * Get rid of the extra nav.
	 *
	 * WP_Users_List_Table will add an extra nav to change user's role.
	 * As we're dealing with invitations, we don't need this.
	 *
	 * @since 8.0.0
	 *
	 * @param array $which Current table nav item.
	 */
	public function extra_tablenav( $which ) {
		return;
	}

	/**
	 * Specific signups columns.
	 *
	 * @since 8.0.0
	 *
	 * @return array
	 */
	public function get_columns() {

		/**
		 * Filters the single site Members signup columns.
		 *
		 * @since 8.0.0
		 *
		 * @param array $value Array of columns to display.
		 */
		return apply_filters(
			'bp_members_invitations_list_columns',
			array(
				'cb'                       => '<input type="checkbox" />',
				'invitee_email'            => __( 'Invitee', 'buddypress' ),
				'username'                 => __( 'Inviter', 'buddypress' ),
				'inviter_registered_date'  => __( 'Inviter Registered', 'buddypress' ),
				'invitation_date_modified' => __( 'Date Modified', 'buddypress' ),
				'invitation_sent'          => __( 'Email Sent', 'buddypress' ),
				'invitation_accepted'      => __( 'Accepted', 'buddypress' )
			)
		);
	}

	/**
	 * Specific bulk actions for signups.
	 *
	 * @since 8.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'resend' => _x( 'Resend Email', 'Pending invitation action', 'buddypress' ),
		);

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = _x( 'Delete', 'Pending invitation action', 'buddypress' );
		}

		return $actions;
	}

	/**
	 * The text shown when no items are found.
	 *
	 * Nice job, clean sheet!
	 *
	 * @since 8.0.0
	 */
	public function no_items() {

		if ( bp_get_members_invitations_allowed() ) {
			esc_html_e( 'No invitations found.', 'buddypress' );
		} else {
			$link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings' ), 'admin.php' ) ) ),
				esc_html__( 'Edit settings', 'buddypress' )
			);

			printf(
				/* translators: %s: url to site settings */
				esc_html__( 'Invitations are not allowed. %s', 'buddypress' ),
				// The link has been escaped at line 255.
				// phpcs:ignore WordPress.Security.EscapeOutput
				$link
			);
		}

	}

	/**
	 * The columns invitations can be reordered by.
	 *
	 * @since 8.0.0
	 */
	public function get_sortable_columns() {
		return array(
			'invitee_email'            => 'invitee_email',
			'username'                 => 'inviter_id',
			'invitation_date_modified' => 'date_modified',
			'invitation_sent'          => 'invite_sent',
			'invitation_accepted'      => 'accepted',
		);
	}

	/**
	 * Display invitation rows.
	 *
	 * @since 8.0.0
	 */
	public function display_rows() {
		$style = '';
		foreach ( $this->items as $invite ) {
			$style = 'alt' === $style ? '' : 'alt';

			// Escapes are made into `self::single_row()`.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo "\n\t" . $this->single_row( $invite, $style );
		}
	}

	/**
	 * Display an invitation row.
	 *
	 * @since 8.0.0
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 *
	 * @param BP_Invitation $invite   BP_Invitation object.
	 * @param string        $style    Styles for the row.
	 * @param string        $role     Role to be assigned to user.
	 * @param int           $numposts Number of posts.
	 * @return void
	 */
	public function single_row( $invite = null, $style = '', $role = '', $numposts = 0 ) {
		if ( '' === $style ) {
			echo '<tr id="signup-' . esc_attr( $invite->id ) . '">';
		} else {
			echo '<tr class="alternate" id="signup-' . esc_attr( $invite->id ) . '">';
		}

		// BuddyPress relies on WordPress's `WP_Users_List_Table::single_row_columns()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->single_row_columns( $invite );
		echo '</tr>';
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_cb( $invite = null ) {
		?>
		<label class="screen-reader-text" for="invitation_<?php echo intval( $invite->id ); ?>">
			<?php
				/* translators: accessibility text */
				printf( esc_html__( 'Select invitation: %s', 'buddypress' ), intval( $invite->id ) );
			?>
		</label>
		<input type="checkbox" id="invitation_<?php echo intval( $invite->id ) ?>" name="invite_ids[]" value="<?php echo esc_attr( $invite->id ) ?>" />
		<?php
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_invitee_email( $invite = null ) {
		echo esc_html( $invite->invitee_email );

		$actions = array();
		$tools_url = bp_get_admin_url( 'tools.php' );

		if ( is_network_admin() ) {
			$tools_url = network_admin_url( 'admin.php' );
		}

		// Resend action only if pending
		if ( ! $invite->accepted ) {
			// Resend invitation email link.
			$email_link = add_query_arg(
				array(
					'page'	    => 'bp-members-invitations',
					'invite_id' => $invite->id,
					'action'    => 'resend',
				),
				$tools_url
			);

			if ( ! $invite->invite_sent ) {
				$resend_label = __( 'Send', 'buddypress' );
			} else {
				$resend_label = __( 'Resend', 'buddypress' );
			}

			$actions['resend'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $email_link ), esc_html( $resend_label ) );
		}

		// Delete link. Could be cleanup or revoking the invitation.
		$delete_link = add_query_arg(
			array(
				'page'      => 'bp-members-invitations',
				'invite_id' => $invite->id,
				'action'    => 'delete',
			),
			$tools_url
		);

		// Two cases: unsent and accepted (cleanup), and pending (cancels invite).
		if ( ! $invite->invite_sent || $invite->accepted ) {
			$actions['delete'] = sprintf( '<a href="%1$s" class="delete">%2$s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'buddypress' ) );
		} else {
			$actions['delete'] = sprintf( '<a href="%1$s" class="delete">%2$s</a>', esc_url( $delete_link ), esc_html__( 'Cancel', 'buddypress' ) );
		}

		/**
		 * Filters the row actions for each invitation in list.
		 *
		 * @since 8.0.0
		 *
		 * @param array  $actions Array of actions and corresponding links.
		 * @param object $invite  The BP_Invitation.
		 */
		$actions = apply_filters( 'bp_members_invitations_management_row_actions', $actions, $invite );

		// BuddyPress relies on WordPress's `WP_Users_List_Table::row_actions()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->row_actions( $actions );
	}

	/**
	 * Display invited user's email address.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_email( $invite = null ) {
		printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $invite->user_email ), esc_html( $invite->user_email ) );
	}

	/**
	 * The inviter.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_username( $invite = null ) {
		$avatar  = get_avatar( $invite->inviter_id, 32 );
		$inviter = get_user_by( 'id', $invite->inviter_id );
		if ( ! $inviter ) {
			return;
		}

		$user_link = bp_members_get_user_url( $invite->inviter_id );

		printf(
			'%1$s <strong><a href="%2$s" class="edit">%3$s</a></strong><br/>',
			wp_kses(
				$avatar,
				array(
					'img' => array(
						'alt'    => true,
						'src'    => true,
						'srcset' => true,
						'class'  => true,
						'height' => true,
						'width'  => true,
					)
				)
			),
			esc_url( $user_link ),
			esc_html( $inviter->user_login )
		);
	}

	/**
	 * Display invitation date.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_inviter_registered_date( $invite = null ) {
		$inviter = get_user_by( 'id', $invite->inviter_id );
		if ( ! $inviter ) {
			return;
		}
		echo esc_html( $inviter->user_registered );
	}

	/**
	 * Display invitation date.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_invitation_date_modified( $invite = null ) {
		echo esc_html( $invite->date_modified );
	}

	/**
	 * Display invitation date.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_invitation_sent( $invite = null ) {
		if ( $invite->invite_sent) {
			esc_html_e( 'Yes', 'buddypress' );
		} else {
			esc_html_e( 'No', 'buddypress' );
		}
	}

	/**
	 * Display invitation acceptance status.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite BP_Invitation object.
	 */
	public function column_invitation_accepted( $invite = null ) {
		if ( $invite->accepted ) {
			esc_html_e( 'Yes', 'buddypress' );
		} else {
			esc_html_e( 'No', 'buddypress' );
		}
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_Invitation $invite      BP_Invitation object.
	 * @param string        $column_name The column name.
	 * @return string
	 */
	function column_default( $invite = null, $column_name = '' ) {

		/**
		 * Filters the single site custom columns for plugins.
		 *
		 * @since 8.0.0
		 *
		 * @param string $column_name The column name.
		 * @param object $invite      The BP_Invitation object..
		 */
		return apply_filters( 'bp_members_invitations_management_custom_column', '', $column_name, $invite );
	}
}
