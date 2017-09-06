<?php
/**
 * Mesages classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( is_admin() && current_user_can( 'activate_plugins' ) ) :

	class BP_Nouveau_Notices_List_Table extends WP_List_Table {
		public function __construct( $args = array() ) {
			parent::__construct( array(
				'plural' => 'notices',
				'singular' => 'notice',
				'ajax' => true,
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			) );
		}

		public function ajax_user_can() {
			return bp_current_user_can( 'bp_moderate' );
		}

		public function prepare_items() {
			$page     = $this->get_pagenum();
			$per_page = $this->get_items_per_page( 'bp_nouveau_notices_per_page' );

			$this->items = BP_Messages_Notice::get_notices( array(
				'pag_num'  => $per_page,
				'pag_page' => $page
			) );

			$this->set_pagination_args( array(
				'total_items' => BP_Messages_Notice::get_total_notice_count(),
				'per_page' => $per_page,
			) );
		}

		public function get_columns() {
			return apply_filters( 'bp_nouveau_notices_list_table_get_columns', array(
				'subject'   => _x( 'Subject', 'Admin Notices column header', 'buddypress' ),
				'message'   => _x( 'Content', 'Admin Notices column header', 'buddypress' ),
				'date_sent' => _x( 'Created', 'Admin Notices column header', 'buddypress' ),
			) );
		}

		public function single_row( $item ) {
			$class = '';

			if ( ! empty( $item->is_active ) ) {
				$class = ' class="notice-active"';
			}

			echo "<tr{$class}>";
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		public function column_subject( $item ) {
			$actions = array(
				'activate_deactivate' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
					'page' => 'bp-notices',
					'activate' => $item->id
				), bp_get_admin_url( 'users.php' ) ) ), 'messages_activate_notice' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="activate">' . esc_html__( 'Activate Notice', 'buddypress' ) . '</a>',
				'delete' => '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
					'page' => 'bp-notices',
					'delete' => $item->id
				), bp_get_admin_url( 'users.php' ) ) ), 'messages_delete_thread' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="delete">' . esc_html__( 'Delete Notice', 'buddypress' ) . '</a>',
			);

			if ( ! empty( $item->is_active ) ) {
				$actions['activate_deactivate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
					'page' => 'bp-notices',
					'deactivate' => $item->id
				), bp_get_admin_url( 'users.php' ) ) ), 'messages_deactivate_notice' ) . '" data-bp-notice-id="' . $item->id . '" data-bp-action="deactivate">' . esc_html__( 'Deactivate Notice', 'buddypress' ) . '</a>';
			}

			echo '<strong>' . apply_filters( 'bp_get_message_notice_subject', $item->subject ) . '</strong> ' . $this->row_actions( $actions );
		}

		public function column_message( $item ) {
			echo apply_filters( 'bp_get_message_notice_text', $item->message );
		}

		public function column_date_sent( $item ) {
			echo apply_filters( 'bp_get_message_notice_post_date', bp_format_time( strtotime( $item->date_sent ) ) );
		}
	}
endif;


class BP_Nouveau_Admin_Notices {

	public static function register_notices_admin() {
		if ( ! is_admin() || ! bp_is_active( 'messages' ) ) {
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
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	protected function setup_globals() {
		$this->screen_id = '';
		$this->url       = add_query_arg( array( 'page' => 'bp-notices' ), bp_get_admin_url( 'users.php' ) );
	}

	protected function setup_actions() {
		add_action( bp_core_admin_hook(), array( $this, 'admin_menu' ) );
	}

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

		$this->list_table = new BP_Nouveau_Notices_List_Table( array( 'screen' => get_current_screen()->id ) );
	}

	public function admin_index() {
		$this->list_table->prepare_items();
		?>
		<div class="wrap">

			<h1>
				<?php echo esc_html_x( 'All Member Notices', 'Notices admin page title', 'buddypress' ); ?>
				<a id="add_notice" class="add-new-h2" href="#"><?php esc_html_e( 'Add New Notice', 'buddypress' ); ?></a>
			</h1>

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

				<div id="message" class="<?php echo isset( $_GET['success'] ) ? 'updated' : 'error' ; ?>">

					<p>
						<?php if ( isset( $_GET['error'] ) ) :
							esc_html_e( 'Notice was not created. Please try again.', 'buddypress' );
						else:
							esc_html_e( 'Notice successfully created.', 'buddypress' );
						endif; ?>
					</p>

				</div>

			<?php endif; ?>

			<?php $this->list_table->display(); ?>

		</div>
		<?php
	}
}
add_action( 'bp_init', array( 'BP_Nouveau_Admin_Notices', 'register_notices_admin' ) );
