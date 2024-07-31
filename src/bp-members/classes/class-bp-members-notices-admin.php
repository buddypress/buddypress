<?php
/**
 * BuddyPress members component Site-wide Notices admin screen.
 *
 * @package buddypress\bp-members\classes\class-bp-members-notices-admin
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Notices Admin class.
 */
#[AllowDynamicProperties]
class BP_Members_Notices_Admin {

	/**
	 * The ID returned by `add_users_page()`.
	 *
	 * @since 15.0.0
	 * @var string
	 */
	public $screen_id = '';

	/**
	 * The URL of the admin screen.
	 *
	 * @since 15.0.0
	 * @var string
	 */
	public $url = '';

	/**
	 * The current instance of the BP_Members_Notices_List_Table class.
	 *
	 * @since 15.0.0
	 * @var BP_Members_Notices_List_Table|string
	 */
	public $list_table = '';

	/**
	 * Create a new instance or access the current instance of this class.
	 *
	 * @since 15.0.0
	 *
	 * @return BP_Members_Notices_Admin
	 */
	public static function register_notices_admin() {

		if ( ! is_admin() || ! bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}

		$bp = buddypress();

		if ( empty( $bp->members->admin->notices ) ) {
			$bp->members->admin->notices = new self;
		}

		return $bp->members->admin->notices;
	}

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Populate the classs variables.
	 *
	 * @since 15.0.0
	 */
	protected function setup_globals() {
		$this->url = add_query_arg( array( 'page' => 'bp-notices' ), bp_get_admin_url( 'users.php' ) );
	}

	/**
	 * Add action hooks.
	 *
	 * @since 15.0.0
	 */
	protected function setup_actions() {
		add_action( bp_core_admin_hook(), array( $this, 'admin_menu' ) );
	}

	/**
	 * Add the 'Site Notices' admin menu item.
	 *
	 * @since 15.0.0
	 */
	public function admin_menu() {
		// Bail if current user cannot moderate community.
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}

		$this->screen_id = add_users_page(
			_x( 'Manage Member Notices', 'Notices admin page title', 'buddypress' ),
			_x( 'Manage Member Notices', 'Admin Users menu', 'buddypress' ),
			'manage_options',
			'bp-notices',
			array( $this, 'admin_index' )
		);

		add_action( 'load-' . $this->screen_id, array( $this, 'admin_load' ) );
	}

	/**
	 * Catch save/update requests or load the screen.
	 *
	 * @since 15.0.0
	 */
	public function admin_load() {
		$redirect_to = false;

		// Catch new notice saves.
		if ( ! empty( $_POST['bp_notice']['send'] ) || ! empty( $_POST['bp_notice']['update'] ) ) {

			check_admin_referer( 'save-notice', 'ns-nonce' );

			$notice = bp_parse_args(
				$_POST['bp_notice'],
				array(
					'id'       => 0,
					'subject'  => '',
					'content'  => '',
					'target'   => '',
					'priority' => 2,
					'link'     => '',
					'text'     => '',
				)
			);

			if ( bp_members_save_notice( $notice ) ) {
				$redirect_to = add_query_arg( 'success', 'create', $this->url );

			// Notice could not be sent.
			} else {
				$redirect_to = add_query_arg( 'error', 'create', $this->url );
			}
		}

		// Catch activation/deactivation/delete requests
		if ( ! empty( $_GET['notice_id'] ) && ! empty( $_GET['notice_action'] ) ) {
			$notice_id = absint( $_GET['notice_id'] );

			check_admin_referer( 'messages-' . $_GET['notice_action'] . '-notice-' . $notice_id );

			$success = false;
			switch ( $_GET['notice_action'] ) {
				case 'activate':
					$notice = new BP_Members_Notice( $notice_id );
					$success = $notice->activate();
					break;
				case 'deactivate':
					$notice = new BP_Members_Notice( $notice_id );
					$success = $notice->deactivate();
					break;
				case 'delete':
					$notice = new BP_Members_Notice( $notice_id );
					$success = $notice->delete();
					break;
			}
			if ( $success ) {
				$redirect_to = add_query_arg( 'success', 'update', $this->url );

			// Notice could not be updated.
			} else {
				$redirect_to = add_query_arg( 'error', 'update', $this->url );
			}
		}

		if ( $redirect_to ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

		$this->list_table = new BP_Members_Notices_List_Table(
			array(
				'screen' => get_current_screen()->id
			)
		);
	}

	/**
	 * Decides whether to load the Notices or Notice admin output.
	 *
	 * @since 15.0.0
	 */
	public function admin_index() {
		$notice_id = 0;
		if ( isset( $_GET['nid'] ) ) {
			$notice_id = (int) wp_unslash( $_GET['nid'] );
		}

		if ( ! $notice_id ) {
			$this->manage_notices();
		} else {
			$this->edit_notice( $notice_id );
		}
	}

	/**
	 * Output the form to create/update notices.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Members_Notice|null The Notice object when updating. Null otherwise.
	 */
	public function notice_form( $notice = null ) {
		$is_edit    = false;
		$class_name = 'new-notice';
		$content    = '';

		$parsed_notice_content = bp_get_parsed_notice_block( $notice );
		if ( empty( $parsed_notice_content['attrs'] ) ) {
			$parsed_notice_content['attrs'] = array(
				'link' => '',
				'text' => '',
			);
		}

		if ( $notice instanceof BP_Members_Notice ) {
			$is_edit     = true;
			$class_name  = 'edit-notice';
			$form_values = get_object_vars( $notice );

			if ( ! empty( $parsed_notice_content['innerHTML'] ) ) {
				$content = $parsed_notice_content['innerHTML'];
			}
		} else {
			$form_values             = get_class_vars( 'BP_Members_Notice' );
			$form_values['target']   = 'community';
			$form_values['priority'] = 2;
		}

		$form_values = array_merge( $form_values, $parsed_notice_content['attrs'] );
		?>
		<div class="form-wrap">
			<?php if ( ! $is_edit ) : ?>
				<h2 class="bp-new-notice"><?php esc_html_e( 'Add New Notice', 'buddypress' ); ?></h2>
			<?php endif; ?>
			<form action="<?php echo esc_url( wp_nonce_url( $this->url, 'save-notice', 'ns-nonce' ) ); ?>" method="post" class="<?php echo esc_attr( $class_name ); ?>">
				<div class="form-field form-required">
					<label for="bp_notice_subject"><?php esc_html_e( 'Title', 'buddypress' ); ?></label>
					<div class="form-input">
						<input type="text" value="<?php echo esc_attr( $form_values['subject'] ); ?>" class="bp-panel-input regular-text code" id="bp_notice_subject" name="bp_notice[subject]" size="40" aria-required="true" aria-describedby="bp-subject-description" />
						<p id="bp-subject-description"><?php esc_html_e( 'The title of your notice.', 'buddypress' ); ?></p>
					</div>
				</div>
				<div class="form-field form-required">
					<label for="bp_notice_content"><?php esc_html_e( 'Content', 'buddypress' ); ?></label>
					<div class="form-input">
						<textarea class="bp-panel-textarea regular-text code" id="bp_notice_content" name="bp_notice[content]" rows="5" cols="40" aria-describedby="bp-content-description"><?php echo esc_textarea( $content ); ?></textarea>
						<p id="bp-content-description"><?php esc_html_e( 'The content of your notice.', 'buddypress' ); ?></p>
					</div>
				</div>
				<div class="form-field form-required">
					<label for="bp_notice_target"><?php esc_html_e( 'Targeted audience', 'buddypress' ); ?></label>
					<div class="form-input">
						<select id="bp_notice_target" name="bp_notice[target]" class="bp-panel-select" aria-required="true" aria-describedby="bp-target-description" required>
							<option value="community" <?php selected( 'community', $form_values['target'] ); ?>><?php esc_html_e( 'All community members', 'buddypress' ); ?></option>
							<option value="admins" <?php selected( 'admins', $form_values['target'] ); ?>><?php esc_html_e( 'All administrators', 'buddypress' ); ?></option>
							<option value="contributors" <?php selected( 'contributors', $form_values['target'] ); ?>><?php esc_html_e( 'All contributors', 'buddypress' ); ?></option>
						</select>
						<p id="bp-target-description"><?php esc_html_e( 'Choose the people who will be noticed.', 'buddypress' ); ?></p>
					</div>
				</div>
				<div class="form-field form-required">
					<label for="bp_notice_priority"><?php esc_html_e( 'Priority', 'buddypress' ); ?></label>
					<div class="form-input">
						<select id="bp_notice_priority" name="bp_notice[priority]" class="bp-panel-select" aria-required="true" aria-describedby="bp-priority-description" required>
							<option value="1" <?php selected( 1, $form_values['priority'] ); ?>><?php esc_html_e( 'High', 'buddypress' ); ?></option>
							<option value="2" <?php selected( 2, $form_values['priority'] ); ?>><?php esc_html_e( 'Regular', 'buddypress' ); ?></option>
							<option value="3" <?php selected( 3, $form_values['priority'] ); ?>><?php esc_html_e( 'Low', 'buddypress' ); ?></option>
						</select>
						<p id="bp-priority-description"><?php esc_html_e( 'Notices having the higher priority will be displayed first.', 'buddypress' ); ?></p>
					</div>
				</div>
				<div class="form-field">
					<label for="bp_notice_link"><?php esc_html_e( 'Action button link', 'buddypress' ); ?></label>
					<div class="form-input">
						<input type="url" value="<?php echo esc_url( $form_values['link'] ); ?>" class="bp-panel-input regular-text code" id="bp_notice_link" name="bp_notice[link]" size="40" aria-describedby="bp-link-description" />
						<p id="bp-link-description"><?php esc_html_e( 'The action button link to head user to.', 'buddypress' ); ?></p>
					</div>
				</div>
				<div class="form-field">
					<label for="bp_notice_text"><?php esc_html_e( 'Action button text', 'buddypress' ); ?></label>
					<div class="form-input">
						<input type="text" value="<?php echo esc_attr( $form_values['text'] ); ?>" class="bp-panel-input regular-text code" id="bp_notice_text" name="bp_notice[text]" size="40" aria-describedby="bp-text-description" />
						<p id="bp-text-description"><?php esc_html_e( 'The text of the action button.', 'buddypress' ); ?></p>
					</div>
				</div>
				<p class="submit">
					<?php if ( ! $is_edit ) : ?>
						<input type="submit" value="<?php esc_attr_e( 'Publish Notice', 'buddypress' ); ?>" name="bp_notice[send]" class="button button-primary save alignleft">
					<?php else : ?>
						<input type="hidden" value="<?php echo esc_attr( $form_values['id'] ); ?>" name="bp_notice[id]" />
						<input type="submit" value="<?php esc_attr_e( 'Update Notice', 'buddypress' ); ?>" name="bp_notice[update]" class="button button-primary save alignleft">
					<?php endif; ?>
					<span class="spinner"></span>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Generate content to manage all notices.
	 *
	 * @since 15.0.0
	 */
	public function manage_notices() {
		$this->list_table->prepare_items();
		?>
		<div class="wrap nosubsub">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Member Notices', 'Notices admin page title', 'buddypress' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['success'] ) || isset( $_GET['error'] ) ) : ?>
				<div id="message" class="<?php echo isset( $_GET['success'] ) ? 'updated' : 'error'; ?> notice is-dismissible">
					<p>
						<?php
						if ( isset( $_GET['error'] ) ) {
							if ( 'create' === $_GET['error'] ) {
								esc_html_e( 'Notice was not created. Please try again.', 'buddypress' );
							} else {
								esc_html_e( 'Notice was not updated. Please try again.', 'buddypress' );
							}
						} else {
							if ( 'create' === $_GET['success'] ) {
								esc_html_e( 'Notice successfully created.', 'buddypress' );
							} else {
								esc_html_e( 'Notice successfully updated.', 'buddypress' );
							}
						}
						?>
					</p>
				</div>
			<?php endif; ?>

			<div id="col-container" class="wp-clearfix">
				<div id="col-left">
					<div class="col-wrap">
						<?php $this->notice_form(); ?>
					</div>
				</div><!-- /col-left -->
				<div id="col-right">
					<div class="col-wrap">
						<?php $this->list_table->display(); ?>
					</div>
				</div><!-- /col-right -->
			</div><!-- /col-container -->
		</div>
		<?php
	}

	/**
	 * Generate content to edit a specific notice.
	 *
	 * @since 15.0.0
	 *
	 * @param integer $id The notice ID. Required.
	 */
	public function edit_notice( $id ) {
		$notice = new BP_Members_Notice( $id );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Edit Notice', 'Notice admin page title', 'buddypress' ); ?></h1>
			<hr class="wp-header-end">
			<?php if ( ! empty( $notice->id ) ) : ?>
				<div id="col-container" class="wp-clearfix">
					<div class="col-wrap">
						<?php $this->notice_form( $notice ); ?>
					</div>
				</div><!-- /col-container -->
			<?php endif; ?>
		</div>
		<?php
	}
}
