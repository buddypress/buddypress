<?php
/**
 * BuddyPress Members List Table for Multisite.
 *
 * @package BuddyPress
 * @subpackage MembersAdminClasses
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for signups network admin page.
 *
 * @since 2.0.0
 */
class BP_Members_MS_List_Table extends WP_MS_Users_List_Table {

	/**
	 * Signup counts.
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	public $signup_counts = 0;

	/**
	 * Signup profile fields.
	 *
	 * @since 10.0.0
	 *
	 * @var array
	 */
	public $signup_field_labels = array();

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'signups',
			'singular' => 'signup',
			'screen'   => get_current_screen()->id,
		) );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since 2.0.0
	 *
	 * @global string $usersearch The users search terms.
	 * @global string $mode       The display mode.
	 */
	public function prepare_items() {
		global $usersearch, $mode;

		$usersearch       = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$signups_per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );
		$paged            = $this->get_pagenum();

		$args = array(
			'offset'     => ( $paged - 1 ) * $signups_per_page,
			'number'     => $signups_per_page,
			'usersearch' => $usersearch,
			'orderby'    => 'signup_id',
			'order'      => 'DESC'
		);

		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
		}

		if ( isset( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'];
		}

		$mode    = empty( $_REQUEST['mode'] ) ? 'list' : $_REQUEST['mode'];
		$signups = BP_Signup::get( $args );

		$this->items         = $signups['signups'];
		$this->signup_counts = $signups['total'];

		$this->set_pagination_args( array(
			'total_items' => $this->signup_counts,
			'per_page'    => $signups_per_page,
		) );
	}

	/**
	 * Display the users screen views
	 *
	 * @since 2.5.0
	 *
	 * @global string $role The name of role the users screens is filtered by
	 */
	public function views() {
		global $role;

		// Used to reset the role.
		$reset_role = $role;

		// Temporarly set the role to registered.
		$role = 'registered';

		// Used to reset the screen id once views are displayed.
		$reset_screen_id = $this->screen->id;

		// Temporarly set the screen id to the users one.
		$this->screen->id = 'users-network';

		// Use the parent function so that other plugins can safely add views.
		parent::views();

		// Reset the role.
		$role = $reset_role;

		// Reset the screen id.
		$this->screen->id = $reset_screen_id;

		// Use thickbox to display the extended profile information.
		if ( bp_is_active( 'xprofile' ) || bp_members_site_requests_enabled() ) {
			add_thickbox();
		}
	}

	/**
	 * Specific signups columns.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username',    'buddypress' ),
			'name'       => __( 'Name',        'buddypress' ),
			'email'      => __( 'Email',       'buddypress' ),
			'registered' => __( 'Registered',  'buddypress' ),
			'date_sent'  => __( 'Last Sent',   'buddypress' ),
			'count_sent' => __( 'Emails Sent', 'buddypress' )
		);

		/**
		 * Filters the multisite Members signup columns.
		 *
		 * @since 2.0.0
		 *
		 * @param array $value Array of columns to display.
		 */
		return apply_filters( 'bp_members_ms_signup_columns', $columns );
	}

	/**
	 * Specific bulk actions for signups.
	 *
	 * @since 2.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'activate' => _x( 'Activate', 'Pending signup action', 'buddypress' ),
			'resend'   => _x( 'Email',    'Pending signup action', 'buddypress' ),
		);

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = __( 'Delete', 'buddypress' );
		}

		/**
		 * Filters the bulk actions for signups.
		 *
		 * @since 10.0.0
		 *
		 * @param array $actions Array of actions and corresponding labels.
		 */
		return apply_filters( 'bp_members_ms_signup_bulk_actions', $actions );
	}

	/**
	 * The text shown when no items are found.
	 *
	 * Nice job, clean sheet!
	 *
	 * @since 2.0.0
	 */
	public function no_items() {
		if ( bp_get_signup_allowed() || bp_get_membership_requests_required() ) {
			esc_html_e( 'No pending accounts found.', 'buddypress' );
		} else {
			$link = false;

			if ( current_user_can( 'manage_network_users' ) ) {
				$link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( network_admin_url( 'settings.php' ) ), esc_html__( 'Edit settings', 'buddypress' ) );
			}

			printf(
				/* translators: %s: url to site settings */
				esc_html__( 'Registration is disabled. %s', 'buddypress' ),
				// The link has been escaped at line 204.
				// phpcs:ignore WordPress.Security.EscapeOutput
				$link
			);
		}
	}

	/**
	 * The columns signups can be reordered with.
	 *
	 * @since 2.0.0
	 */
	public function get_sortable_columns() {
		return array(
			'username'   => 'login',
			'email'      => 'email',
			'registered' => 'signup_id',
		);
	}

	/**
	 * Display signups rows.
	 *
	 * @since 2.0.0
	 */
	public function display_rows() {
		$style = '';
		foreach ( $this->items as $userid => $signup_object ) {

			// Avoid a notice error appearing since 4.3.0.
			if ( isset( $signup_object->id ) ) {
				$signup_object->ID = $signup_object->id;
			}

			$style = 'alt' === $style ? '' : 'alt';

			// Escapes are made into `self::single_row()`.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo "\n\t" . $this->single_row( $signup_object, $style );
		}
	}

	/**
	 * Display a signup row.
	 *
	 * @since 2.0.0
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 *
	 * @param object|null $signup_object Signup user object.
	 * @param string      $style         Styles for the row.
	 */
	public function single_row( $signup_object = null, $style = '' ) {
		if ( '' === $style ) {
			echo '<tr id="signup-' . esc_attr( $signup_object->id ) . '">';
		} else {
			echo '<tr class="alternate" id="signup-' . esc_attr( $signup_object->id ) . '">';
		}

		// BuddyPress relies on WordPress's `WP_MS_Users_List_Table::single_row_columns()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->single_row_columns( $signup_object );
		echo '</tr>';
	}

	/**
	 * Prevents regular users row actions to be output.
	 *
	 * @since 2.4.0
	 *
	 * @param object|null $signup_object Signup being acted upon.
	 * @param string      $column_name   Current column name.
	 * @param string      $primary       Primary column name.
	 * @return string
	 */
	protected function handle_row_actions( $signup_object = null, $column_name = '', $primary = '' ) {
		return '';
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_cb( $signup_object = null ) {
	?>
		<label class="screen-reader-text" for="signup_<?php echo intval( $signup_object->id ); ?>">
			<?php
			printf(
				/* translators: accessibility text */
				esc_html__( 'Select user: %s', 'buddypress' ),
				esc_html( $signup_object->user_login )
			);
			?>
		</label>
		<input type="checkbox" id="signup_<?php echo intval( $signup_object->id ) ?>" name="allsignups[]" value="<?php echo esc_attr( $signup_object->id ) ?>" />
		<?php
	}

	/**
	 * The row actions (delete/activate/email).
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_username( $signup_object = null ) {
		$avatar	= get_avatar( $signup_object->user_email, 32 );

		// Activation email link.
		$email_link = add_query_arg(
			array(
				'page'	    => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'resend',
			),
			network_admin_url( 'users.php' )
		);

		// Activate link.
		$activate_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'activate',
			),
			network_admin_url( 'users.php' )
		);

		// Delete link.
		$delete_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'delete',
			),
			network_admin_url( 'users.php' )
		);

		echo wp_kses(
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
		);
		printf( '<strong><a href="%1$s" class="edit">%2$s</a></strong><br/>', esc_url( $activate_link ), esc_html( $signup_object->user_login ) );

		$actions = array();

		$actions['activate'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $activate_link ), esc_html__( 'Activate', 'buddypress' ) );
		$actions['resend']   = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $email_link    ), esc_html__( 'Email',    'buddypress' ) );

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = sprintf( '<a href="%1$s" class="delete">%2$s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'buddypress' ) );
		}

		/** This filter is documented in bp-members/admin/bp-members-classes.php */
		$actions = apply_filters( 'bp_members_ms_signup_row_actions', $actions, $signup_object );

		// BuddyPress relies on WordPress's `WP_MS_Users_List_Table::row_actions()`.
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $this->row_actions( $actions );
	}

	/**
	 * Display user name, if any.
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_name( $signup_object = null ) {
		echo esc_html( $signup_object->user_name );

		// Insert the extended profile modal content required by thickbox.
		if ( ! bp_is_active( 'xprofile' ) && ! bp_members_site_requests_enabled() ) {
			return;
		}

		if ( bp_is_active( 'xprofile' ) ) {

			// Fetch registration field data once only.
			if ( ! $this->signup_field_labels ) {
				$field_groups = bp_xprofile_get_groups(
					array(
						'fetch_fields'       => true,
						'signup_fields_only' => true,
					)
				);

				foreach ( $field_groups as $field_group ) {
					foreach ( $field_group->fields as $field ) {
						$this->signup_field_labels[ $field->id ] = $field->name;
					}
				}
			}
		}

		bp_members_admin_preview_signup_profile_info( $this->signup_field_labels, $signup_object );
	}

	/**
	 * Display user email.
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_email( $signup_object = null ) {
		printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $signup_object->user_email ), esc_html( $signup_object->user_email ) );
	}

	/**
	 * Display registration date.
	 *
	 * @since 2.0.0
	 *
	 * @global string $mode The display mode.
	 *
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_registered( $signup_object = null ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = "Y/m/d \n g:i:s a";
		}

		echo nl2br( esc_html( mysql2date( $date, $signup_object->registered ) ) ) . "</td>";
	}

	/**
	 * Display the last time an activation email has been sent.
	 *
	 * @since 2.0.0
	 *
	 * @global string $mode The display mode.
	 *
	 * @param object|null $signup_object Signup object instance.
	 */
	public function column_date_sent( $signup_object = null ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = "Y/m/d \n g:i:s a";
		}

		if ( $signup_object->count_sent > 0 ) {
			echo nl2br( esc_html( mysql2date( $date, $signup_object->date_sent ) ) );
		} else {
			$message = esc_html__( 'Not yet notified', 'buddypress' );

			/**
			 * Filters the "not yet sent" message for "Last Sent"
			 * column in Manage Signups list table.
			 *
			 * @since 10.0.0
			 *
			 * @param string      $message       "Not yet sent" message.
			 * @param object|null $signup_object Signup object instance.
			 */
			$message = apply_filters( 'bp_members_ms_signup_date_sent_unsent_message', $message, $signup_object );

			echo esc_html( $message );
		}
	}

	/**
	 * Display number of time an activation email has been sent.
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object Signup object instance.
	 */
	public function column_count_sent( $signup_object = null ) {
		echo absint( $signup_object->count_sent );
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since 2.1.0
	 *
	 * @param object|null $signup_object The signup data object.
	 * @param string      $column_name   The column name.
	 * @return string
	 */
	function column_default( $signup_object = null, $column_name = '' ) {

		/**
		 * Filters the multisite custom columns for plugins.
		 *
		 * @since 2.1.0
		 *
		 * @param string $column_name   The column name.
		 * @param object $signup_object The signup data object.
		 */
		return apply_filters( 'bp_members_ms_signup_custom_column', '', $column_name, $signup_object );
	}
}
