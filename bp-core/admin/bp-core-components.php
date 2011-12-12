<?php

/**
 * BuddyPress Admin Component Functions
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Renders the Component Setup admin panel.
 *
 * @package BuddyPress
 * @since BuddyPress (1.6)
 * @uses bp_core_admin_component_options()
 */
function bp_core_admin_components_settings() {
?>

	<div class="wrap">
		<?php screen_icon( 'buddypress'); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Components', 'buddypress' ) ); ?></h2>
		<form action="" method="post" id="bp-admin-component-form">

			<?php bp_core_admin_components_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-component-submit" id="bp-admin-component-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-component-setup' ); ?>

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
 * @package BuddyPress
 * @since BuddyPress (1.6)
 * @todo Use settings API
 */
function bp_core_admin_components_options() {
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
 * Handle saving the Component settings
 *
 * @since BuddyPress (1.6)
 * @todo Use settings API
 * @global WPDB $wpdb
 * @global BuddyPress $bp
 * @return false On failure
 */
function bp_core_admin_components_settings_handler() {
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

		$base_url = bp_get_admin_url(  add_query_arg( array( 'page' => 'bp-components', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'admin_init', 'bp_core_admin_components_settings_handler' );

?>
