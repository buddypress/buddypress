<?php
/**
 * BuddyPress Opt-outs management.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the Opt-outs admin page.
 *
 * Loaded before the page is rendered, this function does all initial
 * setup, including: processing form requests, registering contextual
 * help, and setting up screen options.
 *
 * @since 8.0.0
 *
 * @global object $bp_optouts_list_table List table instance for nonmember opt-outs admin page.
 */
function bp_core_optouts_admin_load() {
	global $bp_optouts_list_table;

	// Build redirection URL.
	$redirect_to = remove_query_arg(
		array(
			'action',
			'error',
			'updated',
			'activated',
			'notactivated',
			'deleted',
			'notdeleted',
			'resent',
			'notresent',
			'do_delete',
			'do_resend',
			'do_activate',
			'_wpnonce',
			'signup_ids',
		),
		$_SERVER['REQUEST_URI']
	);

	$doaction = bp_admin_list_table_current_bulk_action();

	/**
	 * Fires at the start of the nonmember opt-outs admin load.
	 *
	 * @since 8.0.0
	 *
	 * @param string $doaction Current bulk action being processed.
	 * @param array  $_REQUEST Current $_REQUEST global.
	 */
	do_action( 'bp_optouts_admin_load', $doaction, $_REQUEST );

	/**
	 * Filters the allowed actions for use in the nonmember opt-outs admin page.
	 *
	 * @since 8.0.0
	 *
	 * @param array $value Array of allowed actions to use.
	 */
	$allowed_actions = apply_filters( 'bp_optouts_admin_allowed_actions', array( 'do_delete', 'do_resend' ) );

	if ( ! in_array( $doaction, $allowed_actions, true ) || ( -1 === $doaction ) ) {

		require_once ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php';
		$bp_optouts_list_table = new BP_Optouts_List_Table();

		// The per_page screen option.
		add_screen_option( 'per_page', array( 'label' => _x( 'Nonmember opt-outs', 'Nonmember opt-outs per page (screen options)', 'buddypress' ) ) );

		// Current screen.
		$current_screen = get_current_screen();

		$current_screen->add_help_tab(
			array(
				'id'      => 'bp-optouts-overview',
				'title'   => __( 'Overview', 'buddypress' ),
				'content' =>
					'<p>' . __( 'This is the administration screen for nonmember opt-outs on your site.', 'buddypress' ) . '</p>' .
					'<p>' . __( 'From the screen options, you can customize the displayed columns and the pagination of this screen.', 'buddypress' ) . '</p>' .
					'<p>' . __( 'You can reorder the list of opt-outs by clicking on the Email Sender, Email Type or Date Modified column headers.', 'buddypress' ) . '</p>' .
					'<p>' . __( 'Using the search form, you can search for an opt-out to a specific email address.', 'buddypress' ) . '</p>',
			)
		);

		$current_screen->add_help_tab(
			array(
				'id'      => 'bp-optouts-actions',
				'title'   => __( 'Actions', 'buddypress' ),
				'content' =>
					'<p>' . __( 'Hovering over a row in the opt-outs list will display action links that allow you to manage the opt-out. You can perform the following actions:', 'buddypress' ) . '</p>' .
					'<ul><li>' . __( '"Delete" allows you to delete the record of an opt-out. You will be asked to confirm this deletion.', 'buddypress' ) . '</li></ul>' .
					'<p>' . __( 'Bulk actions allow you to perform these actions for the selected rows.', 'buddypress' ) . '</p>',
			)
		);

		// Help panel - sidebar links.
		$current_screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
		);

		// Add accessible hidden headings and text for the Pending Users screen.
		$current_screen->set_screen_reader_content(
			array(
				/* translators: accessibility text */
				'heading_views'      => __( 'Filter opt-outs list', 'buddypress' ),
				/* translators: accessibility text */
				'heading_pagination' => __( 'Opt-out list navigation', 'buddypress' ),
				/* translators: accessibility text */
				'heading_list'       => __( 'Opt-outs list', 'buddypress' ),
			)
		);

	} else {
		if ( empty( $_REQUEST['optout_ids'] ) ) {
			return;
		}
		$optout_ids = wp_parse_id_list( $_REQUEST['optout_ids'] );

		// Handle optout deletion.
		if ( 'do_delete' === $doaction ) {

			// Nonce check.
			check_admin_referer( 'optouts_delete' );

			$success = 0;
			foreach ( $optout_ids as $optout_id ) {
				if ( bp_delete_optout_by_id( $optout_id ) ) {
					++$success;
				}
			}

			$query_arg = array( 'updated' => 'deleted' );

			if ( ! empty( $success ) ) {
				$query_arg['deleted'] = $success;
			}

			$notdeleted = count( $optout_ids ) - $success;
			if ( $notdeleted > 0 ) {
				$query_arg['notdeleted'] = $notdeleted;
			}

			$redirect_to = add_query_arg( $query_arg, $redirect_to );

			bp_core_redirect( $redirect_to );

			// Plugins can update other stuff from here.
		} else {

			/**
			 * Fires at end of opt-outs admin load
			 * if doaction does not match any actions.
			 *
			 * @since 2.0.0
			 *
			 * @param string $doaction Current bulk action being processed.
			 * @param array  $_REQUEST Current $_REQUEST global.
			 * @param string $redirect Determined redirect url to send user to.
			 */
			do_action( 'bp_core_admin_update_optouts', $doaction, $_REQUEST, $redirect_to );

			bp_core_redirect( $redirect_to );
		}
	}
}
add_action( 'load-tools_page_bp-optouts', 'bp_core_optouts_admin_load' );

/**
 * Get admin notice when viewing the optouts management page.
 *
 * @since 8.0.0
 *
 * @return array
 */
function bp_core_get_optouts_notice() {

	// Setup empty notice for return value.
	$notice = array();

	// Updates.
	if ( ! empty( $_REQUEST['updated'] ) && 'deleted' === $_REQUEST['updated'] ) {
		$notice = array(
			'class'   => 'updated',
			'message' => '',
		);

		if ( ! empty( $_REQUEST['deleted'] ) ) {
			$deleted            = absint( $_REQUEST['deleted'] );
			$notice['message'] .= sprintf(
				/* translators: %s: number of deleted optouts */
				_nx(
					'%s opt-out successfully deleted!',
					'%s opt-outs successfully deleted!',
					$deleted,
					'nonmembers opt-out deleted',
					'buddypress'
				),
				number_format_i18n( absint( $_REQUEST['deleted'] ) )
			);
		}

		if ( ! empty( $_REQUEST['notdeleted'] ) ) {
			$notdeleted         = absint( $_REQUEST['notdeleted'] );
			$notice['message'] .= sprintf(
				/* translators: %s: number of optouts that failed to be deleted */
				_nx(
					'%s opt-out was not deleted.',
					'%s opt-outs were not deleted.',
					$notdeleted,
					'nonmembers opt-out not deleted',
					'buddypress'
				),
				number_format_i18n( $notdeleted )
			);

			if ( empty( $_REQUEST['deleted'] ) ) {
				$notice['class'] = 'error';
			}
		}
	}

	// Errors.
	if ( ! empty( $_REQUEST['error'] ) && 'do_delete' === $_REQUEST['error'] ) {
		$notice = array(
			'class'   => 'error',
			'message' => esc_html__( 'There was a problem deleting opt-outs. Please try again.', 'buddypress' ),
		);
	}

	return $notice;
}

/**
 * Opt-outs admin page router.
 *
 * Depending on the context, display
 * - the list of optouts,
 * - or the delete confirmation screen,
 *
 * Also prepare the admin notices.
 *
 * @since 8.0.0
 */
function bp_core_optouts_admin() {
	$doaction = bp_admin_list_table_current_bulk_action();

	// Prepare notices for admin.
	$notice = bp_core_get_optouts_notice();

	// Display notices.
	if ( ! empty( $notice ) ) :
		if ( 'updated' === $notice['class'] ) : ?>

			<div id="message" class="<?php echo esc_attr( $notice['class'] ); ?> notice is-dismissible">

		<?php else : ?>

			<div class="<?php echo esc_attr( $notice['class'] ); ?> notice is-dismissible">

		<?php endif; ?>

			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>

		<?php
	endif;

	// Show the proper screen.
	switch ( $doaction ) {
		case 'delete':
			bp_core_optouts_admin_manage( $doaction );
			break;

		default:
			bp_core_optouts_admin_index();
			break;
	}
}

/**
 * This is the list of optouts.
 *
 * @since 8.0.0
 *
 * @global string $plugin_page
 * @global object $bp_optouts_list_table List table instance for nonmember opt-outs admin page.
 */
function bp_core_optouts_admin_index() {
	global $plugin_page, $bp_optouts_list_table;

	$usersearch = ! empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '';

	// Prepare the group items for display.
	$bp_optouts_list_table->prepare_items();

	if ( is_network_admin() ) {
		$form_url = network_admin_url( 'admin.php' );
	} else {
		$form_url = bp_get_admin_url( 'tools.php' );
	}

	$form_url = add_query_arg(
		array(
			'page' => 'bp-optouts',
		),
		$form_url
	);

	$search_form_url = remove_query_arg(
		array(
			'action',
			'deleted',
			'notdeleted',
			'error',
			'updated',
			'delete',
			'activate',
			'activated',
			'notactivated',
			'resend',
			'resent',
			'notresent',
			'do_delete',
			'do_activate',
			'do_resend',
			'action2',
			'_wpnonce',
			'optout_ids',
		),
		$_SERVER['REQUEST_URI']
	);

	bp_core_admin_tabbed_screen_header( __( 'BuddyPress tools', 'buddypress' ), __( 'Manage Opt-outs', 'buddypress' ), 'tools' );
	?>

	<div class="buddypress-body">
		<?php
		if ( $usersearch ) {
			$num_results = (int) $bp_optouts_list_table->total_items;
			printf(
				'<p><span class="subtitle">%s</span></p>',
				sprintf(
					esc_html(
						/* translators: %s: the searched email. */
						_n( 'Opt-out with an email address matching &#8220;%s&#8221;', 'Opt-outs with an email address matching &#8220;%s&#8221;', $num_results, 'buddypress' )
					),
					esc_html( $usersearch )
				)
			);
		}
		?>
		<p><?php esc_html_e( 'This table shows opt-out requests from people who are not members of this site, but have been contacted via communication from this site, and wish to receive no further communications.', 'buddypress' ); ?></p>

		<?php // Display each opt-out on its own row. ?>
		<?php $bp_optouts_list_table->views(); ?>

		<form id="bp-optouts-search-form" action="<?php echo esc_url( $search_form_url ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
			<?php $bp_optouts_list_table->search_box( esc_html__( 'Search for a specific email address', 'buddypress' ), 'bp-optouts' ); ?>
		</form>

		<form id="bp-optouts-form" action="<?php echo esc_url( $form_url ); ?>" method="post">
			<?php $bp_optouts_list_table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * This is the confirmation screen for actions.
 *
 * @since 8.0.0
 *
 * @param string $action Delete or resend optout.
 *
 * @return null|false
 */
function bp_core_optouts_admin_manage( $action = '' ) {
	$capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	if ( ! current_user_can( $capability ) || empty( $action ) ) {
		die( '-1' );
	}

	// Get the IDs from the URL.
	$ids = 0;
	if ( ! empty( $_POST['optout_ids'] ) ) {
		$ids = wp_parse_id_list( $_POST['optout_ids'] );
	} elseif ( ! empty( $_GET['optout_id'] ) ) {
		$ids = absint( $_GET['optout_id'] );
	}

	// Query for matching optouts, and filter out bad IDs.
	$args       = array(
		'id' => $ids,
	);
	$optouts    = bp_get_optouts( $args );
	$optout_ids = wp_list_pluck( $optouts, 'id' );

	// Check optout IDs and set up strings.
	switch ( $action ) {
		case 'delete':
			if ( 0 === count( $optouts ) ) {
				$helper_text = __( 'No opt-out requests were found.', 'buddypress' );
			} else {
				$helper_text = _n( 'You are about to delete the following opt-out request:', 'You are about to delete the following opt-out requests:', count( $optouts ), 'buddypress' );
			}
			break;
	}

	// These arguments are added to all URLs.
	$url_args = array( 'page' => 'bp-optouts' );

	// These arguments are only added when performing an action.
	$action_args = array(
		'action'     => 'do_' . $action,
		'optout_ids' => implode( ',', $optout_ids ),
	);

	if ( is_network_admin() ) {
		$base_url = network_admin_url( 'admin.php' );
	} else {
		$base_url = bp_get_admin_url( 'tools.php' );
	}

	$cancel_url = add_query_arg( $url_args, $base_url );
	$action_url = wp_nonce_url(
		add_query_arg(
			array_merge( $url_args, $action_args ),
			$base_url
		),
		'optouts_' . $action
	);

	bp_core_admin_tabbed_screen_header( __( 'BuddyPress tools', 'buddypress' ), __( 'Manage Opt-outs', 'buddypress' ), 'tools' );
	?>

	<div class="buddypress-body">

		<p><?php echo esc_html( $helper_text ); ?></p>

		<ol class="bp-optouts-list">
		<?php foreach ( $optouts as $optout ) : ?>

			<li>
				<strong><?php echo esc_html( $optout->email_address ); ?></strong>
				<p class="description">
					<?php
					$last_modified = mysql2date( 'Y/m/d g:i:s a', $optout->date_modified );
					/* translators: %s: modification date */
					printf( esc_html__( 'Date modified: %s', 'buddypress' ), esc_html( $last_modified ) );
					?>
				</p>
			</li>

		<?php endforeach; ?>
		</ol>

		<?php if ( 'delete' === $action && count( $optouts ) ) : ?>

			<p><strong><?php esc_html_e( 'This action cannot be undone.', 'buddypress' ); ?></strong></p>

		<?php endif; ?>

		<?php if ( count( $optouts ) ) : ?>

			<a class="button-primary" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Confirm', 'buddypress' ); ?></a>

		<?php endif; ?>

		<a class="button" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></a>
	</div>

	<?php
}
