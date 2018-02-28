<?php
/**
 * BuddyPress messages component Site-wide Notices admin screen.
 *
 *
 * @package BuddyPress
 * @subpackage Messages
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class BP_Messages_Notices_Admin {

	/**
	 * The ID returned by `add_users_page()`.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $screen_id = '';

	/**
	 * The URL of the admin screen.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $url = '';

	/**
	 * The current instance of the BP_Messages_Notices_List_Table class.
	 *
	 * @since 3.0.0
	 * @var object
	 */
	public $list_table = '';
	

	/**
     * Create a new instance or access the current instance of this class.
     *
     * @since 3.0.0
     */
	public static function register_notices_admin() {

		if ( ! is_admin() || ! bp_is_active( 'messages' ) || ! bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}

		$bp = buddypress();

		if ( empty( $bp->messages->admin ) ) {
			$bp->messages->admin = new self;
		}

		return $bp->messages->admin;
	}

	/**
	 * Constructor method.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Populate the classs variables.
	 *
	 * @since 3.0.0
	 */
	protected function setup_globals() {
		$this->url = add_query_arg( array( 'page' => 'bp-notices' ), bp_get_admin_url( 'users.php' ) );
	}

	/**
	 * Add action hooks.
	 *
	 * @since 3.0.0
	 */
	protected function setup_actions() {
		add_action( bp_core_admin_hook(), array( $this, 'admin_menu' ) );
	}

	/**
	 * Add the 'All Member Notices' admin menu item.
	 *
	 * @since 3.0.0
	 */
	public function admin_menu() {
		// Bail if current user cannot moderate community.
		if ( ! bp_current_user_can( 'bp_moderate' ) || ! bp_is_active( 'messages' ) ) {
			return false;
		}

		$this->screen_id = add_users_page(
			_x( 'All Member Notices', 'Notices admin page title', 'buddypress' ),
			_x( 'All Member Notices', 'Admin Users menu', 'buddypress' ),
			'manage_options',
			'bp-notices',
			array( $this, 'admin_index' )
		);

		add_action( 'load-' . $this->screen_id, array( $this, 'admin_load' ) );
	}

	/**
	 * Catch save/update requests or load the screen.
	 *
	 * @since 3.0.0
	 */
	public function admin_load() {
		if ( ! empty( $_POST['bp_notice']['send'] ) ) {
			$notice = wp_parse_args( $_POST['bp_notice'], array(
				'subject' => '',
				'content' => ''
			) );

			if ( messages_send_notice( $notice['subject'], $notice['content'] ) ) {
				$redirect_to = add_query_arg( 'success', 1, $this->url );

			// Notice could not be sent.
			} else {
				$redirect_to = add_query_arg( 'error', 1, $this->url );
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}

		$this->list_table = new BP_Messages_Notices_List_Table( array( 'screen' => get_current_screen()->id ) );
	}

	/**
	 * Generate content for the bp-notices admin screen.
	 *
	 * @since 3.0.0
	 */
	public function admin_index() {
		$this->list_table->prepare_items();
		?>
		<div class="wrap">
			<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

				<h1 class="wp-heading-inline"><?php echo esc_html_x( 'All Member Notices', 'Notices admin page title', 'buddypress' ); ?></h1>

					<a id="add_notice" class="page-title-action" href="#"><?php esc_html_e( 'Add New Notice', 'buddypress' ); ?></a>

				<hr class="wp-header-end">

			<?php else : ?>

				<h1>
					<?php echo esc_html_x( 'All Member Notices', 'Notices admin page title', 'buddypress' ); ?>
					<a id="add_notice" class="add-new-h2" href="#"><?php esc_html_e( 'Add New Notice', 'buddypress' ); ?></a>
				</h1>

			<?php endif; ?>

			<form action=<?php echo esc_url( $this->url ); ?> method="post">
				<table class="widefat">
					<tr>
						<td><label for="bp_notice_subject"><?php esc_html_e( 'Subject', 'buddypress' ); ?></label></td>
						<td><input type="text" class="widefat" id="bp_notice_subject" name="bp_notice[subject]"/></td>
					</tr>
					<tr>
						<td><label for="bp_notice_content"><?php esc_html_e( 'Content', 'buddypress' ); ?></label></td>
						<td><textarea class="widefat" id="bp_notice_content" name="bp_notice[content]"></textarea></td>
					</tr>
					<tr class="submit">
						<td>&nbsp;</td>
						<td style="float:right">
							<input type="reset" value="<?php esc_attr_e( 'Cancel Notice', 'buddypress' ); ?>" class="button-secondary">
							<input type="submit" value="<?php esc_attr_e( 'Save Notice', 'buddypress' ); ?>" name="bp_notice[send]" class="button-primary">
						</td>
					</tr>
				</table>
			<form>

			<?php if ( isset( $_GET['success'] ) || isset( $_GET['error'] ) ) : ?>

				<div id="message" class="<?php echo isset( $_GET['success'] ) ? 'updated' : 'error'; ?>">

					<p>
						<?php
						if ( isset( $_GET['error'] ) ) :
							esc_html_e( 'Notice was not created. Please try again.', 'buddypress' );
						else :
							esc_html_e( 'Notice successfully created.', 'buddypress' );
						endif;
						?>
					</p>

				</div>

			<?php endif; ?>

			<?php $this->list_table->display(); ?>

		</div>
		<?php
	}
}
