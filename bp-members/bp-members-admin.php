<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BP_Members_Admin' ) ) :
/**
 * Load Members admin area.
 *
 * @package BuddyPress
 * @subpackage membersAdministration
 *
 * @since BuddyPress (2.0.0)
 */
class BP_Members_Admin {

	/** Directory *************************************************************/

	/**
	 * Path to the BP Members Admin directory.
	 *
	 * @var string $admin_dir
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * URL to the BP Members Admin directory.
	 *
	 * @var string $admin_url
	 */
	public $admin_url = '';

	/**
	 * URL to the BP Members Admin CSS directory.
	 *
	 * @var string $css_url
	 */
	public $css_url = '';

	/**
	 * URL to the BP Members Admin JS directory.
	 *
	 * @var string
	 */
	public $js_url = '';

	/** Other *****************************************************************/

	/**
	 * Screen id for edit user's profile page.
	 *
	 * @access public
	 * @var string
	 */
	public $user_page = '';

	/**
	 * Setup BP Members Admin.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @uses buddypress() to get BuddyPress main instance
	 */
	public static function register_members_admin() {
		if( ! is_admin() )
			return;

		$bp = buddypress();

		if( empty( $bp->members->admin ) ) {
			$bp->members->admin = new self;
		}

		return $bp->members->admin;
	}

	/**
	 * Constructor method.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @access private
	 * @since BuddyPress (2.0.0)
	 */
	private function setup_globals() {
		$bp = buddypress();

		// Paths and URLs
		$this->admin_dir = trailingslashit( $bp->plugin_dir  . 'bp-members/admin' ); // Admin path
		$this->admin_url = trailingslashit( $bp->plugin_url  . 'bp-members/admin' ); // Admin URL
		$this->css_url   = trailingslashit( $this->admin_url . 'css' ); // Admin CSS URL
		$this->js_url    = trailingslashit( $this->admin_url . 'js'  ); // Admin CSS URL

		// The Edit Profile Screen id
		$this->user_page = '';

		// The screen ids to load specific css for
		$this->screen_id = array();

		// The stats metabox default position
		$this->stats_metabox = new StdClass();

		// The WordPress edit user url
		$this->edit_url = bp_get_admin_url( 'user-edit.php' );

		// BuddyPress edit user's profile url
		$this->edit_profile_url = add_query_arg( 'page', 'bp-profile-edit', bp_get_admin_url( 'users.php' ) );
	}

	/**
	 * Set admin-related actions and filters.
	 *
	 * @access private
	 * @since BuddyPress (2.0.0)
	 */
	private function setup_actions() {

		/** Actions ***************************************************/

		// Add some page specific output to the <head>
		add_action( 'bp_admin_head',            array( $this, 'admin_head'      ), 999    );

		// Add menu item to all users menu
		add_action( bp_core_admin_hook(),       array( $this, 'admin_menus'     ),   5    );

		// Enqueue all admin JS and CSS
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' )         );

		// Create the Profile Navigation (WordPress/Community)
		add_action( 'edit_user_profile',        array( $this, 'profile_nav'     ),  99, 1 );


		/** Filters ***************************************************/

		// Add a row action to users listing
		add_filter( bp_core_do_network_admin() ? 'ms_user_row_actions' : 'user_row_actions', array( $this, 'row_actions' ), 10, 2 );

	}

	/**
	 * Create the All Users > Edit Profile submenu.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @uses add_users_page() To add the Edit Profile page in Users section.
	 */
	public function admin_menus() {

		// Manage user's profile
		$hook = $this->user_page = add_users_page(
			__( 'Edit Profile',  'buddypress' ),
			__( 'Edit Profile',  'buddypress' ),
			'bp_moderate',
			'bp-profile-edit',
			array( &$this, 'user_admin' )
		);

		$edit_page = 'user-edit';

		if ( bp_core_do_network_admin() ) {
			$edit_page       .= '-network';
			$this->user_page .= '-network';
		}

		$this->screen_id = array( $edit_page, $this->user_page );

		add_action( "admin_head-$hook", array( $this, 'modify_admin_menu_highlight' ) );
		add_action( "load-$hook",       array( $this, 'user_admin_load' ) );

	}

	/**
	 * Add some specific styling to the Edit User and Edit User's Profile page.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function enqueue_scripts() {
		if ( ! in_array( get_current_screen()->id, $this->screen_id ) ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$css = $this->css_url . "admin{$min}.css";
		$css = apply_filters( 'bp_members_admin_css', $css );
		wp_enqueue_style( 'bp-members-css', $css, array(), bp_get_version() );

		// Only load javascript for BuddyPress profile
		if ( get_current_screen()->id == $this->user_page ) {
			$js = $this->js_url . "admin{$min}.js";
			$js = apply_filters( 'bp_members_admin_js', $js );
			wp_enqueue_script( 'bp-members-js', $js, array( 'jquery' ), bp_get_version(), true );
		}

		// Plugins may want to hook here to load some css/js
		do_action( 'bp_members_admin_enqueue_scripts', get_current_screen()->id, $this->screen_id );
	}

	/**
	 * Create the Profile navigation in Edit User & Edit Profile pages.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function profile_nav( $user = null, $active = 'WordPress' ) {

		if ( empty( $user->ID ) ) {
			return;
		}

		$query_args = array( 'user_id' => $user->ID );

		if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
			$query_args['wp_http_referer'] = urlencode( wp_unslash( $_REQUEST['wp_http_referer'] ) );
		}

		$community_url = add_query_arg( $query_args, $this->edit_profile_url );
		$wordpress_url = add_query_arg( $query_args, $this->edit_url         );

		$bp_active = false;
		$wp_active = ' nav-tab-active';
		if ( 'BuddyPress' === $active ) {
			$bp_active = ' nav-tab-active';
			$wp_active = false;
		} ?>

		<ul id="profile-nav" class="nav-tab-wrapper">
			<li class="nav-tab<?php echo esc_attr( $wp_active ); ?>"><a href="<?php echo esc_url( $wordpress_url );?>"><?php _e( 'WordPress Profile' ); ?></a></li>
			<li class="nav-tab<?php echo esc_attr( $bp_active ); ?>"><a href="<?php echo esc_url( $community_url );?>"><?php _e( 'Community Profile' ); ?></a></li>

			<?php do_action( 'bp_members_admin_profile_nav', $active, $user ); ?>
		</ul>

		<?php
	}

	/**
	 * Highlight the Users menu if on Edit Profile pages.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function modify_admin_menu_highlight() {
		global $plugin_page, $submenu_file;

		// Only Show the All users menu
		if ( 'bp-profile-edit' ==  $plugin_page ) {
			$submenu_file = 'users.php';
		}
	}

	/**
	 * Remove the Edit Profile submenu page.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_head() {
		// Remove submenu to force using Profile Navigation
		remove_submenu_page( 'users.php', 'bp-profile-edit' );
	}

	/**
	 * Set up the user's profile admin page.
	 *
	 * Loaded before the page is rendered, this function does all initial
	 * setup, including: processing form requests, registering contextual
	 * help, and setting up screen options.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function user_admin_load() {

		if ( ! $user_id = intval( $_GET['user_id'] ) ) {
			wp_die( __( 'No users were found', 'buddypress' ) );
		}

		// only edit others profile
		if ( get_current_user_id() == $user_id ) {
			bp_core_redirect( get_edit_user_link( $user_id ) );
		}

		// Build redirection URL
		$redirect_to = remove_query_arg( array( 'action', 'error', 'updated', 'spam', 'ham', 'delete_avatar' ), $_SERVER['REQUEST_URI'] );
		$doaction = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

		if ( ! empty( $_REQUEST['user_status'] ) ) {
			$spam = ( 'spam' == $_REQUEST['user_status'] ) ? true : false ;

			if ( $spam != bp_is_user_spammer( $user_id ) ) {
				$doaction = $_REQUEST['user_status'];
			}
		}

		// Call an action for plugins to hook in early
		do_action_ref_array( 'bp_members_admin_load', array( $doaction, $_REQUEST ) );

		// Allowed actions
		$allowed_actions = apply_filters( 'bp_members_admin_allowed_actions', array( 'update', 'delete_avatar', 'spam', 'ham' ) );

		// Prepare the display of the Community Profile screen
		if ( ! in_array( $doaction, $allowed_actions ) ) {
			add_screen_option( 'layout_columns', array( 'default' => 2, 'max' => 2, ) );

			get_current_screen()->add_help_tab( array(
				'id'      => 'bp-profile-edit-overview',
				'title'   => __( 'Overview', 'buddypress' ),
				'content' =>
				'<p>' . __( 'This is the admin view of a user&#39;s profile.', 'buddypress' ) . '</p>' .
				'<p>' . __( 'You can edit the different fields of his extended profile from the main metabox', 'buddypress' ) . '</p>' .
				'<p>' . __( 'You can get some interesting informations about him on right side metaboxes', 'buddypress' ) . '</p>'
			) );

			// Help panel - sidebar links
			get_current_screen()->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.buddypress.org/buddypress-site-administration/managing-user-profiles/">Managing Profiles</a>', 'buddypress' ) . '</p>' .
				'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
			);

			// Register metaboxes for the edit screen.
			add_meta_box( 'submitdiv', _x( 'Status', 'members user-admin edit screen', 'buddypress' ), array( &$this, 'user_admin_status_metabox' ), get_current_screen()->id, 'side', 'core' );

			// In case xprofile is not active
			$this->stats_metabox->context = 'normal';
			$this->stats_metabox->priority = 'core';

			/**
			 * xProfile Hooks to load the profile fields if component is active
			 * Plugins should not use this hook, please use 'bp_members_admin_user_metaboxes' instead
			 */
			do_action_ref_array( 'bp_members_admin_xprofile_metabox', array( $user_id, get_current_screen()->id, $this->stats_metabox ) );

			// If xProfile is inactive, difficult to know what's profile we're on
			$display_name = false;
			if ( 'normal' == $this->stats_metabox->context ) {
				$display_name = ' - ' . esc_html( bp_core_get_user_displayname( $user_id ) );
			}

			// User Stat metabox
			add_meta_box( 'bp_members_admin_user_stats',    _x( 'Stats' . $display_name, 'members user-admin edit screen', 'buddypress' ), array( &$this, 'user_admin_stats_metabox' ), get_current_screen()->id, sanitize_key( $this->stats_metabox->context ), sanitize_key( $this->stats_metabox->priority ) );

			// Custom metabox ?
			do_action( 'bp_members_admin_user_metaboxes' );

			// Enqueue javascripts
			wp_enqueue_script( 'postbox' );
			wp_enqueue_script( 'dashboard' );
			wp_enqueue_script( 'comment' );

		// Spam or Ham user
		} else if ( in_array( $doaction, array( 'spam', 'ham' ) ) ) {

			check_admin_referer( 'edit-bp-profile_' . $user_id );

			if ( bp_core_process_spammer_status( $user_id, $doaction ) ) {
				$redirect_to = add_query_arg( 'updated', $doaction, $redirect_to );
			} else {
				$redirect_to = add_query_arg( 'error', $doaction, $redirect_to );
			}

			bp_core_redirect( $redirect_to );

		// Update other stuff once above ones are done
		} else {
			$this->redirect = $redirect_to;

			do_action_ref_array( 'bp_members_admin_update_user', array( $doaction, $user_id, $_REQUEST, $this->redirect ) );

			bp_core_redirect( $this->redirect );
		}
	}

	/**
	 * Display the user's profile.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 */
	public function user_admin() {

		if ( ! current_user_can( 'bp_moderate' ) ) {
			die( '-1' );
		}

		$user = get_user_to_edit( $_GET['user_id'] );

		// Construct URL for form
		$form_url        = remove_query_arg( array( 'action', 'error', 'updated', 'spam', 'ham' ), $_SERVER['REQUEST_URI'] );
		$form_url        = esc_url( add_query_arg( 'action', 'update', $form_url ) );
		$wp_http_referer = remove_query_arg( array( 'action', 'updated' ), $_REQUEST['wp_http_referer'] );

		// Prepare notice for admin
		$notice = array();

		if ( ! empty( $_REQUEST['updated'] ) ) {
			switch ( $_REQUEST['updated'] ) {
			case 'avatar':
				$notice = array(
					'class'   => 'updated',
					'message' => esc_html__( 'Avatar was deleted successfully!', 'buddypress' )
				);
				break;
			case 'ham' :
				$notice = array(
					'class'   => 'updated',
					'message' => esc_html__( 'User removed as spammer.', 'buddypress' )
				);
				break;
			case 'spam' :
				$notice = array(
					'class'   => 'updated',
					'message' => esc_html__( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' )
				);
				break;
			case 1 :
				$notice = array(
					'class'   => 'updated',
					'message' => esc_html__( 'Profile updated.', 'buddypress' )
				);
				break;
			}
		}

		if ( ! empty( $_REQUEST['error'] ) ) {
			switch ( $_REQUEST['error'] ) {
			case 'avatar':
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'There was a problem deleting that avatar, please try again.', 'buddypress' )
				);
				break;
			case 'ham' :
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'User could not be removed as spammer.', 'buddypress' )
				);
				break;
			case 'spam' :
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'User could not be marked as spammer.', 'buddypress' )
				);
				break;
			case 1 :
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'An error occured while trying to update the profile.', 'buddypress' )
				);
				break;
			case 2:
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'Please make sure you fill in all required fields in this profile field group before saving.', 'buddypress' )
				);
				break;
			case 3:
				$notice = array(
					'class'   => 'error',
					'message' => esc_html__( 'There was a problem updating some of your profile information, please try again.', 'buddypress' )
				);
				break;
			}
		}

		if ( ! empty( $notice ) ) :
			if ( 'updated' === $notice['class'] ) : ?>
				<div id="message" class="<?php echo esc_attr( $notice['class'] ); ?>">
			<?php else: ?>
				<div class="<?php echo esc_attr( $notice['class'] ); ?>">
			<?php endif; ?>
				<p><?php echo $notice['message']; ?></p>
				<?php if ( !empty( $wp_http_referer ) && ( 'updated' === $notice['class'] ) ) : ?>
					<p><a href="<?php echo esc_url( $wp_http_referer ); ?>"><?php _e( '&larr; Back to Users', 'buddypress' ); ?></a></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="wrap"  id="community-profile-page">
			<?php screen_icon( 'users' ); ?>
			<h2>
				<?php
				_e( 'Edit User', 'buddypress' );
				if ( current_user_can( 'create_users' ) ) { ?>
					<a href="user-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'user', 'buddypress' ); ?></a>
				<?php } elseif ( is_multisite() && current_user_can( 'promote_users' ) ) { ?>
					<a href="user-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add Existing', 'user', 'buddypress' ); ?></a>
				<?php }
				?>
			</h2>

			<?php if ( ! empty( $user ) ) :

				$this->profile_nav( $user, 'BuddyPress' ); ?>

				<form action="<?php echo esc_attr( $form_url ); ?>" id="your-profile" method="post">
					<div id="poststuff">

						<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
							<div id="post-body-content">
							</div><!-- #post-body-content -->

							<div id="postbox-container-1" class="postbox-container">
								<?php do_meta_boxes( get_current_screen()->id, 'side', $user ); ?>
							</div>

							<div id="postbox-container-2" class="postbox-container">
								<?php do_meta_boxes( get_current_screen()->id, 'normal',   $user ); ?>
								<?php do_meta_boxes( get_current_screen()->id, 'advanced', $user ); ?>
							</div>
						</div><!-- #post-body -->

					</div><!-- #poststuff -->

					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field( 'meta-box-order',  'meta-box-order-nonce', false ); ?>
					<?php wp_nonce_field( 'edit-bp-profile_' . $user->ID ); ?>

				</form>

			<?php else : ?>
				<p><?php printf( __( 'No user found with this ID. <a href="%s">Go back and try again</a>.', 'buddypress' ), esc_url( bp_get_admin_url( 'users.php' ) ) ); ?></p>
			<?php endif; ?>

		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Render the Status metabox for user's profile screen.
	 *
	 * Actions are:
	 * - Update profile fields if xProfile component is active
	 * - Spam/Unspam user
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @param WP_User $user The WP_User object to be edited.
	 */
	public function user_admin_status_metabox( $user = null ) {

		// bail if no user id or if the user has not activated his account yet..
		if ( empty( $user->ID ) ) {
			return;
		}

		if ( ( isset( $user->user_status ) && 2 == $user->user_status ) ) {
			echo '<p class="not-activated">' . esc_html__( 'User has not activated his account yet', 'buddypress' ) . '</p><br/>';
			return;
		}
		?>

		<div class="submitbox" id="submitcomment">
			<div id="minor-publishing">
				<div id="minor-publishing-actions">
					<div id="preview-action">
						<a class="button preview" href="<?php echo esc_attr( bp_core_get_user_domain( $user->ID ) ); ?>" target="_blank"><?php esc_html_e( 'View Profile', 'buddypress' ); ?></a>
					</div>

					<div class="clear"></div>
				</div><!-- #minor-publishing-actions -->

				<div id="misc-publishing-actions">
					<div class="misc-pub-section" id="comment-status-radio">
						<label class="approved"><input type="radio" name="user_status" value="ham" <?php checked( bp_is_user_spammer( $user->ID ), false ); ?>><?php esc_html_e( 'Active', 'buddypress' ); ?></label><br />
						<label class="spam"><input type="radio" name="user_status" value="spam" <?php checked( bp_is_user_spammer( $user->ID ), true ); ?>><?php esc_html_e( 'Spammer', 'buddypress' ); ?></label>
					</div>

					<div class="misc-pub-section curtime misc-pub-section-last">
						<?php
						// translators: Publish box date format, see http://php.net/date
						$datef = __( 'M j, Y @ G:i', 'buddypress' );
						$date  = date_i18n( $datef, strtotime( $user->user_registered ) );
						?>
						<span id="timestamp"><?php printf( __( 'Registered on: <strong>%1$s</strong>', 'buddypress' ), $date ); ?></span>
					</div>
				</div> <!-- #misc-publishing-actions -->

				<div class="clear"></div>
			</div><!-- #minor-publishing -->

			<div id="major-publishing-actions">
				<div id="publishing-action">
					<?php submit_button( esc_html__( 'Update Profile', 'buddypress' ), 'primary', 'save', false, array( 'tabindex' => '4' ) ); ?>
				</div>
				<div class="clear"></div>
			</div><!-- #major-publishing-actions -->

		</div><!-- #submitcomment -->

		<?php
	}

	/**
	 * Render the fallback metabox in case a user has been marked as a spammer.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @param WP_User $user The WP_User object to be edited.
	 */
	public function user_admin_spammer_metabox( $user = null ) {
		?>
		<p><?php printf( __( '%s has been marked as a spammer, this user&#39;s BuddyPress datas were removed', 'buddypress' ), esc_html( bp_core_get_user_displayname( $user->ID ) ) ) ;?></p>
		<?php
	}

	/**
	 * Render the Stats metabox to moderate inappropriate images.
	 * 
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @param WP_User $user The WP_User object to be edited.
	 */
	public function user_admin_stats_metabox( $user = null ) {

		if ( empty( $user->ID ) ) {
			return;
		}

		// If account is not activated last activity is the time user registered
		if ( isset( $user->user_status ) && 2 == $user->user_status ) {
			$last_active = $user->user_registered;

		// Account is activated, getting user's last activity
		} else {
			$last_active = bp_get_user_last_activity( $user->ID );
		}

		$datef = __( 'M j, Y @ G:i', 'buddypress' );
		$date  = date_i18n( $datef, strtotime( $last_active ) ); ?>

		<ul>
			<li class="bp-members-profile-stats"><?php printf( __( 'Last active: <strong>%1$s</strong>', 'buddypress' ), $date ); ?></li>

			<?php
			// Loading other stats only if user has activated his account
			if ( empty( $user->user_status ) ) {
				do_action( 'bp_members_admin_user_stats', array( 'user_id' => $user->ID ), $user );
			}
			?>
		</ul>

		<?php
	}

	/**
	 * Add a link to Profile in Users listing row actions.
	 *
	 * @access public
	 * @since BuddyPress (2.0.0)
	 *
	 * @param array $actions WordPress row actions (edit, delete).
	 * @param object $user The object for the user row.
	 * @return array Merged actions.
	 */
	public function row_actions( $actions = '', $user = null ) {
		// only edit others profile
		if ( get_current_user_id() == $user->ID ) {
			return $actions;
		}

		$edit_profile = add_query_arg( array(
			'user_id'         => $user->ID,
			'wp_http_referer' => urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
		), $this->edit_profile_url );

		$edit_action = $actions['edit'];
		unset( $actions['edit'] );

		$new_edit_actions = array(
			'edit'         => $edit_action,
			'edit-profile' => '<a href="' . esc_url( $edit_profile ) . '">' . esc_html__( 'Profile', 'buddypress' ) . '</a>'
		);

		return array_merge( $new_edit_actions, $actions );
	}
}
endif; // class_exists check

// Load the BP Members admin
add_action( 'bp_init', array( 'BP_Members_Admin','register_members_admin' ) );
