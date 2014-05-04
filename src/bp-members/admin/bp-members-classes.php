<?php

/**
 * BuddyPress Members List Classes
 *
 * @package BuddyPress
 * @subpackage MembersAdminClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'WP_Users_List_Table') ) :

/**
 * List table class for signups admin page.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_Members_List_Table extends WP_Users_List_Table {

	/**
	 * Signup counts.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @access public
	 * @var int
	 */
	public $signup_counts = 0;

	/**
	 * Constructor.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'signups',
			'singular' => 'signup',
		) );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function prepare_items() {
		global $usersearch;

		$usersearch = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';

		$signups_per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$paged = $this->get_pagenum();

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

		$signups = BP_Signup::get( $args );

		$this->items = $signups['signups'];
		$this->signup_counts = $signups['total'];

		$this->set_pagination_args( array(
			'total_items' => $this->signup_counts,
			'per_page'    => $signups_per_page,
		) );
	}

	/**
	 * Get the views (the links above the WP List Table).
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @uses WP_Users_List_Table::get_views() to get the users views
	 */
	public function get_views() {
		$views = parent::get_views();

		// Remove the 'current' class from the 'All' link
		$views['all']        = str_replace( 'class="current"', '', $views['all'] );
		$views['registered'] = '<a href="' . add_query_arg( 'page', 'bp-signups', bp_get_admin_url( 'users.php' ) ) . '"  class="current">' . sprintf( _x( 'Pending <span class="count">(%s)</span>', 'signup users', 'buddypress' ), number_format_i18n( $this->signup_counts ) ) . '</a>';

		return $views;
	}

	/**
	 * Get rid of the extra nav.
	 *
	 * WP_Users_List_Table will add an extra nav to change user's role.
	 * As we're dealing with signups, we don't need this.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function extra_tablenav( $which ) {
		return;
	}

	/**
	 * Specific signups columns
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function get_columns() {
		return apply_filters( 'bp_members_signup_columns', array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username', 'buddypress' ),
			'name'       => __( 'Name', 'buddypress' ),
			'email'      => __( 'Email', 'buddypress' ),
			'registered' => __( 'Registered', 'buddypress' ),
			'date_sent'  => __( 'Last Sent', 'buddypress' ),
			'count_sent' => __( '# Times Emailed', 'buddypress' )
		) );
	}

	/**
	 * Specific bulk actions for signups.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function get_bulk_actions() {
		$actions = array(
			'activate' => _x( 'Activate', 'Pending signup action', 'buddypress' ),
			'resend'   => _x( 'Email', 'Pending signup action', 'buddypress' ),
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
	 * @since BuddyPress (2.0.0)
	 */
	public function no_items() {

		if ( bp_get_signup_allowed() ) {
			esc_html_e( 'No pending accounts found.', 'buddypress' );
		} else {
			$link = false;

			// Specific case when BuddyPress is not network activated
			if ( is_multisite() && current_user_can( 'manage_network_users') ) {
				$link = '<a href="' . esc_url( network_admin_url( 'settings.php' ) ) . '">' . esc_html__( 'Edit settings', 'buddypress' ) . '</a>';
			} elseif ( current_user_can( 'manage_options' ) ) {
				$link = '<a href="' . esc_url( bp_get_admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Edit settings', 'buddypress' ) . '</a>';
			}
			
			printf( __( 'Registration is disabled. %s', 'buddypress' ), $link );
		}
			
	}

	/**
	 * The columns signups can be reordered with.
	 *
	 * @since BuddyPress (2.0.0)
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
	 * @since BuddyPress (2.0.0)
	 */
	public function display_rows() {
		$style = '';
		foreach ( $this->items as $userid => $signup_object ) {
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t" . $this->single_row( $signup_object, $style );
		}
	}

	/**
	 * Display a signup row.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 */
	public function single_row( $signup_object = null, $style = '', $role = '', $numposts = 0 ) {
		echo '<tr' . $style . ' id="signup-' . esc_attr( $signup_object->id ) . '">';
		echo $this->single_row_columns( $signup_object );
		echo '</tr>';
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function column_cb( $signup_object = null ) {
		?>
		<label class="screen-reader-text" for="signup_<?php echo intval( $signup_object->id ); ?>"><?php echo esc_html( sprintf( __( 'Select %s', 'buddypress' ), $signup_object->user_login ) ); ?></label>
		<input type="checkbox" id="signup_<?php echo intval( $signup_object->id ) ?>" name="allsignups[]" value="<?php echo esc_attr( $signup_object->id ) ?>" />
		<?php
	}

	/**
	 * The row actions (delete/activate/email).
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_username( $signup_object = null ) {
		$avatar	= get_avatar( $signup_object->user_email, 32 );

		// Activation email link
		$email_link = add_query_arg(
			array(
				'page'	    => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'resend',
			),
			bp_get_admin_url( 'users.php' )
		);

		// Activate link
		$activate_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'activate',
			),
			bp_get_admin_url( 'users.php' )
		);

		// Delete link
		$delete_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'delete',
			),
			bp_get_admin_url( 'users.php' )
		);

		echo $avatar . '<strong><a href="' . $activate_link .'" class="edit" title="' . esc_attr__( 'Activate', 'buddypress' ) . '">' . $signup_object->user_login .'</a></strong><br/>';

		$actions = array();

		$actions['activate'] = '<a href="' . esc_url( $activate_link ) . '">' . __( 'Activate', 'buddypress' ) . '</a>';

		$actions['resend'] = '<a href="' . esc_url( $email_link ) . '">' . __( 'Email', 'buddypress' ) . '</a>';

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = '<a href="' . esc_url( $delete_link ) . '" class="delete">' . __( 'Delete', 'buddypress' ) . '</a>';
		}

		$actions = apply_filters( 'bp_members_ms_signup_row_actions', $actions, $signup_object );
		echo $this->row_actions( $actions );
	}

	/**
	 * Display user name, if any.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_name( $signup_object = null ) {
		echo esc_html( $signup_object->user_name );
	}

	/**
	 * Display user email.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_email( $signup_object = null ) {
		echo '<a href="mailto:' . esc_attr( $signup_object->user_email ) . '">' . esc_html( $signup_object->user_email ) .'</a>';
	}

	/**
	 * Display registration date.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_registered( $signup_object = null ) {
		echo mysql2date( 'Y/m/d', $signup_object->registered );
	}

	/**
	 * Display the last time an activation email has been sent.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_date_sent( $signup_object = null ) {
		echo mysql2date( 'Y/m/d', $signup_object->date_sent );
	}

	/**
	 * Display number of time an activation email has been sent.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function column_count_sent( $signup_object = null ) {
		echo absint( $signup_object->count_sent );
	}
}

endif;

if ( class_exists( 'WP_MS_Users_List_Table' ) ) :
/**
 * List table class for signups network admin page.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_Members_MS_List_Table extends WP_MS_Users_List_Table {

	/**
	 * Signup counts.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @access public
	 * @var int
	 */
	public $signup_counts = 0;

	/**
	 * Constructor
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'signups',
			'singular' => 'signup',
		) );
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function prepare_items() {
		global $usersearch, $wpdb, $mode;

		$usersearch = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';

		$signups_per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$paged = $this->get_pagenum();

		$args = array(
			'offset'     => ( $paged - 1 ) * $signups_per_page,
			'number'     => $signups_per_page,
			'usersearch' => $usersearch,
			'orderby'    => 'signup_id',
			'order'      => 'DESC'
		);

		if ( isset( $_REQUEST['orderby'] ) )
			$args['orderby'] = $_REQUEST['orderby'];

		if ( isset( $_REQUEST['order'] ) )
			$args['order'] = $_REQUEST['order'];

		$mode = empty( $_REQUEST['mode'] ) ? 'list' : $_REQUEST['mode'];

		$signups = BP_Signup::get( $args );

		$this->items = $signups['signups'];
		$this->signup_counts = $signups['total'];

		$this->set_pagination_args( array(
			'total_items' => $this->signup_counts,
			'per_page'    => $signups_per_page,
		) );
	}

	/**
	 * Get the views : the links above the WP List Table.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @uses WP_MS_Users_List_Table::get_views() to get the users views
	 */
	function get_views() {
		$views = parent::get_views();

		$views['all'] = str_replace( 'class="current"', '', $views['all'] );
			$class = ' class="current"';

		$views['registered'] = '<a href="' . add_query_arg( 'page', 'bp-signups', bp_get_admin_url( 'users.php' ) ) . '"  class="current">' . sprintf( _x( 'Pending <span class="count">(%s)</span>', 'signup users', 'buddypress' ), number_format_i18n( $this->signup_counts ) ) . '</a>';

		return $views;
	}

	/**
	 * Specific signups columns
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function get_columns() {
		return apply_filters( 'bp_members_ms_signup_columns', array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username', 'buddypress' ),
			'name'       => __( 'Name', 'buddypress' ),
			'email'      => __( 'Email', 'buddypress' ),
			'registered' => __( 'Registered', 'buddypress' ),
			'date_sent'  => __( 'Last Sent', 'buddypress' ),
			'count_sent' => __( '# Times Emailed', 'buddypress' )
		) );
	}

	/**
	 * Specific bulk actions for signups
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function get_bulk_actions() {
		$actions = array(
			'activate' => _x( 'Activate', 'Pending signup action', 'buddypress' ),
			'resend'   => _x( 'Email', 'Pending signup action', 'buddypress' ),
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
	 * @since BuddyPress (2.0.0)
	 */
	public function no_items() {
		if ( bp_get_signup_allowed() ) {
			esc_html_e( 'No pending accounts found.', 'buddypress' );
		} else {
			$link = false;

			if ( current_user_can( 'manage_network_users' ) ) {
				$link = '<a href="' . esc_url( network_admin_url( 'settings.php' ) ) . '">' . esc_html__( 'Edit settings', 'buddypress' ) . '</a>';
			}

			printf( __( 'Registration is disabled. %s', 'buddypress' ), $link );
		}
	}

	/**
	 * The columns signups can be reordered with
	 *
	 * @since BuddyPress (2.0.0)
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
	 * @since BuddyPress (2.0.0)
	 */
	public function display_rows() {
		$style = '';
		foreach ( $this->items as $userid => $signup_object ) {
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t" . $this->single_row( $signup_object, $style );
		}
	}

	/**
	 * Display a signup row.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 */
	public function single_row( $signup_object = null, $style = '' ) {
		echo '<tr' . $style . ' id="signup-' . esc_attr( $signup_object->id ) . '">';
		echo $this->single_row_columns( $signup_object );
		echo '</tr>';
	}

	/**
	 * Markup for the checkbox used to select items for bulk actions.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function column_cb( $signup_object = null ) {
		?>
		<label class="screen-reader-text" for="signup_<?php echo intval( $signup_object->id ); ?>"><?php echo esc_html( sprintf( __( 'Select %s', 'buddypress' ), $signup_object->user_login ) ); ?></label>
		<input type="checkbox" id="signup_<?php echo intval( $signup_object->id ) ?>" name="allsignups[]" value="<?php echo esc_attr( $signup_object->id ) ?>" />
		<?php
	}

	/**
	 * The row actions (delete/activate/email).
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_username( $signup_object = null ) {
		$avatar	= get_avatar( $signup_object->user_email, 32 );

		// Activation email link
		$email_link = add_query_arg(
			array(
				'page'	    => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'resend',
			),
			bp_get_admin_url( 'users.php' )
		);

		// Activate link
		$activate_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'activate',
			),
			bp_get_admin_url( 'users.php' )
		);

		// Delete link
		$delete_link = add_query_arg(
			array(
				'page'      => 'bp-signups',
				'signup_id' => $signup_object->id,
				'action'    => 'delete',
			),
			bp_get_admin_url( 'users.php' )
		);

		echo $avatar . '<strong><a href="' . esc_url( $activate_link ) .'" class="edit" title="' . esc_attr__( 'Activate', 'buddypress' ) . '">' . $signup_object->user_login .'</a></strong><br/>';

		$actions['activate'] = '<a href="' . esc_url( $activate_link ) . '">' . __( 'Activate', 'buddypress' ) . '</a>';

		$actions['resend'] = '<a href="' . esc_url( $email_link ) . '">' . __( 'Email', 'buddypress' ) . '</a>';

		if ( current_user_can( 'delete_users' ) ) {
			$actions['delete'] = '<a href="' . esc_url( $delete_link ) . '" class="delete">' . __( 'Delete', 'buddypress' ) . '</a>';
		}

		$actions = apply_filters( 'bp_members_ms_signup_row_actions', $actions, $signup_object );
		echo $this->row_actions( $actions );
	}

	/**
	 * Display user name, if any.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_name( $signup_object = null ) {
		echo esc_html( $signup_object->user_name );
	}

	/**
	 * Display user email.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_email( $signup_object = null ) {
		echo '<a href="mailto:' . esc_attr( $signup_object->user_email ) . '">' . esc_html( $signup_object->user_email ) .'</a>';
	}

	/**
	 * Display registration date
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param object $signup_object The signup data object.
	 */
	public function column_registered( $signup_object = null ) {
		global $mode;

		if ( 'list' == $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = 'Y/m/d \<\b\r \/\> g:i:s a';
		}

		echo mysql2date( $date, $signup_object->registered ) . "</td>";
	}

	/**
	 * Display the last time an activation email has been sent.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function column_date_sent( $signup_object = null ) {
		global $mode;

		if ( 'list' == $mode ) {
			$date = 'Y/m/d';
		} else {
			$date = 'Y/m/d \<\b\r \/\> g:i:s a';
		}

		echo mysql2date( $date, $signup_object->date_sent );
	}

	/**
	 * Display number of time an activation email has been sent.
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function column_count_sent( $signup_object = null ) {
		echo absint( $signup_object->count_sent );
	}
}

endif;
