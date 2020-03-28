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
	}

	/**
	 * Specific signups columns.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_columns() {

		/**
		 * Filters the multisite Members signup columns.
		 *
		 * @since 2.0.0
		 *
		 * @param array $value Array of columns to display.
		 */
		return apply_filters( 'bp_members_ms_signup_columns', array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username',    'buddypress' ),
			'name'       => __( 'Name',        'buddypress' ),
			'email'      => __( 'Email',       'buddypress' ),
			'registered' => __( 'Registered',  'buddypress' ),
			'date_sent'  => __( 'Last Sent',   'buddypress' ),
			'count_sent' => __( 'Emails Sent', 'buddypress' )
		) );
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

		return $actions;
	}

	/**
	 * The text shown when no items are found.
	 *
	 * Nice job, clean sheet!
	 *
	 * @since 2.0.0
	 */
	public function no_items() {
		if ( bp_get_signup_allowed() ) {
			esc_html_e( 'No pending accounts found.', 'buddypress' );
		} else {
			$link = false;

			if ( current_user_can( 'manage_network_users' ) ) {
				$link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( network_admin_url( 'settings.php' ) ), esc_html__( 'Edit settings', 'buddypress' ) );
			}

			/* translators: %s: url to site settings */
			printf( __( 'Registration is disabled. %s', 'buddypress' ), $link );
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

			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
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
		echo '<tr' . $style . ' id="signup-' . esc_attr( $signup_object->id ) . '">';
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
		<label class="screen-reader-text" for="signup_<?php echo intval( $signup_object->id ); ?>"><?php printf(
			/* translators: accessibility text */
			esc_html__( 'Select user: %s', 'buddypress' ), $signup_object->user_login );
		?></label>
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

		echo $avatar . sprintf( '<strong><a href="%1$s" class="edit">%2$s</a></strong><br/>', esc_url( $activate_link ), $signup_object->user_login );

		$actions = array();

		$actions['activate'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $activate_link ), __( 'Activate', 'buddypress' ) );
		$actions['resend']   = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $email_link    ), __( 'Email',    'buddypress' ) );

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = sprintf( '<a href="%1$s" class="delete">%2$s</a>', esc_url( $delete_link ), __( 'Delete', 'buddypress' ) );
		}

		/** This filter is documented in bp-members/admin/bp-members-classes.php */
		$actions = apply_filters( 'bp_members_ms_signup_row_actions', $actions, $signup_object );

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
	 * @param object|null $signup_object The signup data object.
	 */
	public function column_registered( $signup_object = null ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = 'Y/m/d \<\b\r \/\> g:i:s a';
		}

		echo mysql2date( $date, $signup_object->registered ) . "</td>";
	}

	/**
	 * Display the last time an activation email has been sent.
	 *
	 * @since 2.0.0
	 *
	 * @param object|null $signup_object Signup object instance.
	 */
	public function column_date_sent( $signup_object = null ) {
		global $mode;

		if ( 'list' === $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = 'Y/m/d \<\b\r \/\> g:i:s a';
		}

		echo mysql2date( $date, $signup_object->date_sent );
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
