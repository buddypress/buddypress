<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Add an extra update message to the update plugin notification.
 *
 * @package BuddyPress Core
 */
function bp_core_update_message() {
	echo '<p style="color: red; margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">' . __( 'IMPORTANT: <a href="http://codex.buddypress.org/buddypress-site-administration/upgrading-buddypress/">Read this before attempting to update BuddyPress</a>', 'buddypress' ) . '</p>';
}
add_action( 'in_plugin_update_message-buddypress/bp-loader.php', 'bp_core_update_message' );

/**
 * Output the tabs in the admin area
 *
 * @since 1.5
 * @param string $active_tab Name of the tab that is active
 */
function bp_core_admin_tabs( $active_tab = '' ) {

	// Declare local variables
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	// Setup core admin tabs
	$tabs = array(
		'0' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-general-settings' ), 'admin.php' ) ),
			'name' => __( 'Components', 'buddypress' )
		),
		'1' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings'    ), 'admin.php' ) ),
			'name' => __( 'Pages', 'buddypress' )
		),
		'2' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings'         ), 'admin.php' ) ),
			'name' => __( 'Settings', 'buddypress' )
		)
	);

	// If forums component is active, add additional tab
	if ( bp_is_active( 'forums' ) ) {
		$tabs['3'] = array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bb-forums-setup'     ), 'admin.php' ) ),
			'name' => __( 'Forums', 'buddypress' )
		);
	}

	// Loop through tabs and build navigation
	foreach( $tabs as $tab_id => $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . $tab_data['href'] . '" class="' . $tab_class . '">' . $tab_data['name'] . '</a>';
	}

	// Output the tabs
	echo $tabs_html;

	// Do other fun things
	do_action( 'bp_admin_tabs' );
}

/**
 * Renders the Settings admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 */
function bp_core_admin_settings() {
	global $wpdb, $bp;

	$ud = get_userdata( $bp->loggedin_user->id );

	if ( isset( $_POST['bp-admin-submit'] ) && isset( $_POST['bp-admin'] ) ) {
		if ( !check_admin_referer('bp-admin') )
			return false;

		// Settings form submitted, now save the settings.
		foreach ( (array)$_POST['bp-admin'] as $key => $value )
			bp_update_option( $key, $value );

	} ?>

	<div class="wrap">

		<?php screen_icon( 'buddypress' ); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Settings', 'buddypress' ) ); ?></h2>

		<?php if ( isset( $_POST['bp-admin'] ) ) : ?>

			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved', 'buddypress' ); ?></p>
			</div>

		<?php endif; ?>

		<form action="" method="post" id="bp-admin-form">

			<table class="form-table">
				<tbody>

					<?php if ( bp_is_active( 'xprofile' ) ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Disable BuddyPress to WordPress profile syncing?', 'buddypress' ) ?></th>
							<td>
								<input type="radio" name="bp-admin[bp-disable-profile-sync]"<?php if ( (int)bp_get_option( 'bp-disable-profile-sync' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-profile-sync" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp-disable-profile-sync]"<?php if ( !(int)bp_get_option( 'bp-disable-profile-sync' ) || '' == bp_get_option( 'bp-disable-profile-sync' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-profile-sync" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<tr>
						<th scope="row"><?php _e( 'Hide admin bar for logged out users?', 'buddypress' ) ?></th>
						<td>
							<input type="radio" name="bp-admin[hide-loggedout-adminbar]"<?php if ( (int)bp_get_option( 'hide-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-hide-loggedout-adminbar-yes" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[hide-loggedout-adminbar]"<?php if ( !(int)bp_get_option( 'hide-loggedout-adminbar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-hide-loggedout-adminbar-no" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e( 'Disable avatar uploads? (Gravatars will still work)', 'buddypress' ) ?></th>
						<td>
							<input type="radio" name="bp-admin[bp-disable-avatar-uploads]"<?php if ( (int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-disable-avatar-uploads-yes" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[bp-disable-avatar-uploads]"<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?> checked="checked"<?php endif; ?> id="bp-admin-disable-avatar-uploads-no" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php _e( 'Disable user account deletion?', 'buddypress' ) ?></th>
						<td>
							<input type="radio" name="bp-admin[bp-disable-account-deletion]"<?php if ( (int)bp_get_option( 'bp-disable-account-deletion' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-account-deletion" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
							<input type="radio" name="bp-admin[bp-disable-account-deletion]"<?php if ( !(int)bp_get_option( 'bp-disable-account-deletion' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-account-deletion" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
						</td>
					</tr>

					<?php if ( bp_is_active( 'activity' ) ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Disable activity stream commenting on blog and forum posts?', 'buddypress' ) ?></th>
							<td>
								<input type="radio" name="bp-admin[bp-disable-blogforum-comments]"<?php if ( (int)bp_get_option( 'bp-disable-blogforum-comments' ) || false === bp_get_option( 'bp-disable-blogforum-comments' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-blogforum-comments" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp-disable-blogforum-comments]"<?php if ( !(int)bp_get_option( 'bp-disable-blogforum-comments' ) ) : ?> checked="checked"<?php endif; ?> id="bp-disable-blogforum-comments" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<?php if ( bp_is_active( 'groups' ) ) : ?>

						<tr>
							<th scope="row"><?php _e( 'Restrict group creation to Site Admins?', 'buddypress' ) ?></th>
							<td>
								<input type="radio" name="bp-admin[bp_restrict_group_creation]"<?php checked( '1', bp_get_option( 'bp_restrict_group_creation', '0' ) ); ?>id="bp-restrict-group-creation" value="1" /> <?php _e( 'Yes', 'buddypress' ) ?> &nbsp;
								<input type="radio" name="bp-admin[bp_restrict_group_creation]"<?php checked( '0', bp_get_option( 'bp_restrict_group_creation', '0' ) ); ?>id="bp-restrict-group-creation" value="0" /> <?php _e( 'No', 'buddypress' ) ?>
							</td>
						</tr>

					<?php endif; ?>

					<?php do_action( 'bp_core_admin_screen_fields' ) ?>

				</tbody>
			</table>

			<?php do_action( 'bp_core_admin_screen' ); ?>

			<p class="submit">
				<input class="button-primary" type="submit" name="bp-admin-submit" id="bp-admin-submit" value="<?php _e( 'Save Settings', 'buddypress' ); ?>" />
			</p>

			<?php wp_nonce_field( 'bp-admin' ); ?>

		</form>

	</div>

<?php
}

function bp_core_admin_component_setup_handler() {
	global $wpdb, $bp;

	if ( isset( $_POST['bp-admin-component-submit'] ) ) {
		if ( !check_admin_referer('bp-admin-component-setup') )
			return false;

		// Settings form submitted, now save the settings. First, set active components
		if ( isset( $_POST['bp_components'] ) ) {
			// Save settings and upgrade schema
			require( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-update.php' );
			$bp->active_components = stripslashes_deep( $_POST['bp_components'] );
			bp_core_install( $bp->active_components );

			bp_update_option( 'bp-active-components', $bp->active_components );
		}

		$base_url = bp_get_admin_url(  add_query_arg( array( 'page' => 'bp-general-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'admin_init', 'bp_core_admin_component_setup_handler' );

function bp_core_admin_pages_setup_handler() {
	global $wpdb, $bp;

	if ( isset( $_POST['bp-admin-pages-submit'] ) || isset( $_POST['bp-admin-pages-single'] ) ) {
		if ( !check_admin_referer( 'bp-admin-pages-setup' ) )
			return false;

		// Then, update the directory pages
		if ( isset( $_POST['bp_pages'] ) ) {

			$directory_pages = array();

			foreach ( (array)$_POST['bp_pages'] as $key => $value ) {
				if ( !empty( $value ) ) {
					$directory_pages[$key] = (int)$value;
				}
			}
			bp_core_update_directory_page_ids( $directory_pages );
		}

		$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'admin_init', 'bp_core_admin_pages_setup_handler' );

/**
 * Renders the Component Setup admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 * @uses bp_core_admin_component_options()
 */
function bp_core_admin_component_setup() {
?>

	<div class="wrap">

		<?php screen_icon( 'buddypress'); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Components', 'buddypress' ) ); ?></h2>

		<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>

			<div id="message" class="updated fade">

				<p><?php _e( 'Settings Saved', 'buddypress' ); ?></p>

			</div>

		<?php endif; ?>

		<form action="" method="post" id="bp-admin-component-form">

			<?php bp_core_admin_component_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-component-submit" id="bp-admin-component-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-component-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Renders the Component Setup admin panel.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 * @uses bp_core_admin_component_options()
 */
function bp_core_admin_page_setup() {
?>

	<div class="wrap">

		<?php screen_icon( 'buddypress'); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Pages', 'buddypress' ) ); ?></h2>

		<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>

			<div id="message" class="updated fade">

				<p><?php _e( 'Settings Saved', 'buddypress' ); ?></p>

			</div>

		<?php endif; ?>

		<form action="" method="post" id="bp-admin-page-form">

			<?php bp_core_admin_page_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-pages-submit" id="bp-admin-pages-submit" value="<?php _e( 'Save All', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-pages-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Creates reusable markup for component setup on the Components and Pages dashboard panel.
 *
 * This markup has been abstracted so that it can be used both during the setup wizard as well as
 * when BP has been fully installed.
 *
 * @package BuddyPress Core
 * @since 1.5
 */
function bp_core_admin_component_options() {
	global $bp_wizard;

	// Load core functions, if needed
	if ( !function_exists( 'bp_get_option' ) )
		require( BP_PLUGIN_DIR . '/bp-core/bp-core-functions.php' );

	$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

	// An array of strings looped over to create component setup markup
	$optional_components = array(
		'xprofile' => array(
			'title'       => __( 'Extended Profiles', 'buddypress' ),
			'description' => __( 'Customize your community with fully editable profile fields that allow your users to describe themselves.', 'buddypress' )
		),
		'settings' => array(
			'title'       => __( 'Account Settings', 'buddypress' ),
			'description' => __( 'Allow your users to modify their account and notification settings directly from within their profiles.', 'buddypress' )
		),
		'friends'  => array(
			'title'       => __( 'Friend Connections', 'buddypress' ),
			'description' => __( 'Let your users make connections so they can track the activity of others and focus on the people they care about the most.', 'buddypress' )
		),
		'messages' => array(
			'title'       => __( 'Private Messaging', 'buddypress' ),
			'description' => __( 'Allow your users to talk to each other directly and in private. Not just limited to one-on-one discussions, messages can be sent between any number of members.', 'buddypress' )
		),
		'activity' => array(
			'title'       => __( 'Activity Streams', 'buddypress' ),
			'description' => __( 'Global, personal, and group activity streams with threaded commenting, direct posting, favoriting and @mentions, all with full RSS feed and email notification support.', 'buddypress' )
		),
		'groups'   => array(
			'title'       => __( 'User Groups', 'buddypress' ),
			'description' => __( 'Groups allow your users to organize themselves into specific public, private or hidden sections with separate activity streams and member listings.', 'buddypress' )
		),
		'forums'   => array(
			'title'       => __( 'Discussion Forums', 'buddypress' ),
			'description' => __( 'Full-powered discussion forums built directly into groups allow for more conventional in-depth conversations. NOTE: This will require an extra (but easy) setup step.', 'buddypress' )
		),
		'blogs'    => array(
			'title'       => __( 'Site Tracking', 'buddypress' ),
			'description' => __( 'Make BuddyPress aware of new posts and new comments from your site.', 'buddypress' )
		)
	);

	if ( is_multisite() )
		$optional_components['blogs']['description'] = __( 'Make BuddyPress aware of new sites, new posts and new comments from across your entire network.', 'buddypress' );

	// If this is an upgrade from before BuddyPress 1.5, we'll have to convert deactivated
	// components into activated ones
	if ( empty( $active_components ) ) {
		$deactivated_components = bp_get_option( 'bp-deactivated-components' );

		// Trim off namespace and filename
		$trimmed = array();
		foreach ( (array) $deactivated_components as $component => $value ) {
			$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
		}

		// Loop through the optional components to create an active component array
		foreach ( (array) $optional_components as $ocomponent => $ovalue ) {
			if ( !in_array( $ocomponent, $trimmed ) ) {
				$active_components[$ocomponent] = 1;
			}
		}
	}

	// Required components
	$required_components = array(
		'core' => array(
			'title'       => __( 'BuddyPress Core', 'buddypress' ),
			'description' => __( 'It&#8216;s what makes <del>time travel</del> BuddyPress possible!', 'buddypress' )
		),
		'members' => array(
			'title'       => __( 'Community Members', 'buddypress' ),
			'description' => __( 'Everything in a BuddyPress community revolves around its members.', 'buddypress' )
		),
	);

	// On new install, set all components to be active by default
	if ( !empty( $bp_wizard ) && 'install' == $bp_wizard->setup_type && empty( $active_components ) )
		$active_components = $optional_components;

	?>

	<?php /* The setup wizard uses different, more descriptive text here */ ?>
	<?php if ( empty( $bp_wizard ) ) : ?>

		<h3><?php _e( 'Available Components', 'buddypress' ); ?></h3>

		<p><?php _e( 'Each component has a unique purpose, and your community may not need each one.', 'buddypress' ); ?></p>

	<?php endif ?>

	<table class="form-table">
		<tbody>

			<?php foreach ( $optional_components as $name => $labels ) : ?>

				<tr valign="top">
					<th scope="row"><?php echo esc_html( $labels['title'] ); ?></th>

					<td>
						<label for="bp_components[<?php echo esc_attr( $name ); ?>]">
							<input type="checkbox" id="bp_components[<?php echo esc_attr( $name ); ?>]" name="bp_components[<?php echo esc_attr( $name ); ?>]" value="1"<?php checked( isset( $active_components[esc_attr( $name )] ) ); ?> />

							<?php echo $labels['description']; ?>

						</label>

					</td>
				</tr>

			<?php endforeach ?>

		</tbody>
	</table>

	<?php if ( empty( $bp_wizard ) ) : ?>

		<h3><?php _e( 'Required Components', 'buddypress' ); ?></h3>

		<p><?php _e( 'The following components are required by BuddyPress and cannot be turned off.', 'buddypress' ); ?></p>

	<?php endif ?>

	<table class="form-table">
		<tbody>

			<?php foreach ( $required_components as $name => $labels ) : ?>

				<tr valign="top">
					<th scope="row"><?php echo esc_html( $labels['title'] ); ?></th>

					<td>
						<label for="bp_components[<?php echo esc_attr( $name ); ?>]">
							<input type="checkbox" id="bp_components[<?php echo esc_attr( $name ); ?>]" name="" disabled="disabled" value="1"<?php checked( true ); ?> />

							<?php echo $labels['description']; ?>

						</label>

					</td>
				</tr>

			<?php endforeach ?>

		</tbody>
	</table>

	<input type="hidden" name="bp_components[members]" value="1" />

	<?php
}

/**
 * Creates reusable markup for page setup on the Components and Pages dashboard panel.
 *
 * This markup has been abstracted so that it can be used both during the setup wizard as well as
 * when BP has been fully installed.
 *
 * @package BuddyPress Core
 * @since 1.5
 */
function bp_core_admin_page_options() {
	global $bp;

	// Get the existing WP pages
	$existing_pages = bp_core_get_directory_page_ids();

	// Set up an array of components (along with component names) that have
	// directory pages.
	$directory_pages = array();

	foreach( $bp->loaded_components as $component_slug => $component_id ) {

		// Only components that need directories should be listed here
		if ( isset( $bp->{$component_id} ) && !empty( $bp->{$component_id}->has_directory ) ) {

			// component->name was introduced in BP 1.5, so we must provide a fallback
			$component_name = !empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );

			$directory_pages[$component_id] = $component_name;
		}
	}

	$directory_pages = apply_filters( 'bp_directory_pages', $directory_pages );

	?>

	<h3><?php _e( 'Directories', 'buddypress' ); ?></h3>

	<p><?php _e( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddypress' ); ?></p>

	<table class="form-table">
		<tbody>

			<?php foreach ( $directory_pages as $name => $label ) : ?>
				<?php $disabled = !bp_is_active( $name ) ? ' disabled="disabled"' : ''; ?>

				<tr valign="top">
					<th scope="row">
						<label for="bp_pages[<?php echo esc_attr( $name ) ?>]"><?php echo esc_html( $label ) ?></label>
					</th>

					<td>
						<?php if ( !bp_is_root_blog() )
							switch_to_blog( bp_get_root_blog_id() ) ?>

						<?php echo wp_dropdown_pages( array(
							'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
							'echo'             => false,
							'show_option_none' => __( '- None -', 'buddypress' ),
							'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
						) ); ?>

						<a href="<?php echo admin_url( add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) ); ?>" class="button-secondary"><?php _e( 'New Page' ); ?></a>
						<input class="button-primary" type="submit" name="bp-admin-pages-single" value="<?php _e( 'Save', 'buddypress' ) ?>" />

						<?php if ( !empty( $existing_pages[$name] ) ) : ?>

							<a href="<?php echo get_permalink( $existing_pages[$name] ); ?>" class="button-secondary" target="_bp"><?php _e( 'View' ); ?></a>

						<?php endif; ?>

						<?php if ( !bp_is_root_blog() )
							restore_current_blog() ?>

					</td>
				</tr>


			<?php endforeach ?>

			<?php do_action( 'bp_active_external_directories' ); ?>

		</tbody>
	</table>

	<?php

	// Static pages
	$static_pages = array(
		'register' => __( 'Sign-up',    'buddypress' ),
		'activate' => __( 'Activation', 'buddypress' ),
	); ?>

	<h3><?php _e( 'Registration', 'buddypress' ); ?></h3>

	<p><?php _e( 'Associate WordPress Pages with the following BuddyPress Registration pages.', 'buddypress' ); ?></p>

	<table class="form-table">
		<tbody>

			<?php foreach ( $static_pages as $name => $label ) : ?>

				<tr valign="top">
					<th scope="row">
						<label for="bp_pages[<?php echo esc_attr( $name ) ?>]"><?php echo esc_html( $label ) ?></label>
					</th>

					<td>
						<?php echo wp_dropdown_pages( array(
							'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
							'echo'             => false,
							'show_option_none' => __( '- None -', 'buddypress' ),
							'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
						) ) ?>

						<a href="<?php echo admin_url( add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) ); ?>" class="button-secondary"><?php _e( 'New Page' ); ?></a>
						<input class="button-primary" type="submit" name="bp-admin-pages-single" value="<?php _e( 'Save', 'buddypress' ) ?>" />

						<?php if ( !empty( $existing_pages[$name] ) ) : ?>

							<a href="<?php echo get_permalink( $existing_pages[$name] ); ?>" class="button-secondary" target="_bp"><?php _e( 'View' ); ?></a>

						<?php endif; ?>

					</td>
				</tr>

			<?php endforeach ?>

			<?php do_action( 'bp_active_external_pages' ); ?>

		</tbody>
	</table>

	<?php
}

/**
 * Loads admin panel styles and scripts.
 *
 * @package BuddyPress Core
 * @since {@internal Unknown}}
 */
function bp_core_add_admin_menu_styles() {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', BP_PLUGIN_URL . '/bp-core/css/admin.dev.css' ), array(), '20110723' );
	else
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', BP_PLUGIN_URL . '/bp-core/css/admin.css' ), array(), '20110723' );

	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );
}

?>