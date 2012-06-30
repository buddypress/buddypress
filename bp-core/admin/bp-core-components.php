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

	// Load core functions, if needed
	if ( !function_exists( 'bp_get_option' ) )
		require( BP_PLUGIN_DIR . '/bp-core/bp-core-functions.php' );

	// Declare local variables
	$deactivated_components = array();
	$required_components    = array();
	$active_components      = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

	// Optional core components
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
			'description' => __( 'Site-wide and Group forums allow for focused, bulletin-board style conversations. Powered by bbPress.', 'buddypress' )
		),
		'blogs'    => array(
			'title'       => __( 'Site Tracking', 'buddypress' ),
			'description' => __( 'Record activity for new posts and comments from your site.', 'buddypress' )
		)
	);

	// Add blogs tracking if multisite
	if ( is_multisite() ) {
		$optional_components['blogs']['description'] = __( 'Record activity for new sites, posts, and comments across your network.', 'buddypress' );
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

	// Merge optional and required together
	$all_components = $optional_components + $required_components;

	// If this is an upgrade from before BuddyPress 1.5, we'll have to convert
	// deactivated components into activated ones.
	if ( empty( $active_components ) ) {
		$deactivated_components = bp_get_option( 'bp-deactivated-components' );
		if ( !empty( $deactivated_components ) ) {

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
	}

	// On new install, set all components to be active by default
	if ( empty( $active_components ) && ( bp_get_maintenance_mode() == 'install' ) ) {
		$active_components = $optional_components;
	}

	// Core component is always active
	$active_components['core'] = $all_components['core'];
	$inactive_components       = array_diff( array_keys( $all_components ) , array_keys( $active_components ) );

	/** Display ***************************************************************/

	// Get the total count of all plugins
	$all_count = count( $all_components );
	$page      = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';
	$action    = !empty( $_GET['action'] ) ? $_GET['action'] : 'all';
	
	switch( $action ) {
		case 'all' :
			$current_components = $all_components;
			break;
		case 'active' :
			foreach ( array_keys( $active_components ) as $component ) {
				$current_components[$component] = $all_components[$component];
			}
			break;
		case 'inactive' :
			foreach ( $inactive_components as $component ) {
				$current_components[$component] = $all_components[$component];
			}
			break;
		case 'mustuse' :
			$current_components = $required_components;
			break;
	}
	
	// The setup wizard uses different, more descriptive text
	if ( bp_get_maintenance_mode() ) : ?>

		<h3><?php _e( 'Available Components', 'buddypress' ); ?></h3>

		<p><?php _e( 'Each component has a unique purpose, and your community may not need each one.', 'buddypress' ); ?></p>

	<?php endif ?>
		
		<ul class="subsubsub">
			<li><a href="<?php echo add_query_arg( array( 'page' => 'bp-components', 'action' => 'all'      ), bp_get_admin_url( $page ) ); ?>" <?php if ( $action === 'all'      ) : ?>class="current"<?php endif; ?>><?php printf( _nx( 'All <span class="count">(%s)</span>',      'All <span class="count">(%s)</span>',      $all_count,         'plugins', 'buddypress' ), number_format_i18n( $all_count                    ) ); ?></a> | </li>
			<li><a href="<?php echo add_query_arg( array( 'page' => 'bp-components', 'action' => 'active'   ), bp_get_admin_url( $page ) ); ?>" <?php if ( $action === 'active'   ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Active <span class="count">(%s)</span>',   'Active <span class="count">(%s)</span>',   count( $active_components   ), 'buddypress' ), number_format_i18n( count( $active_components   ) ) ); ?></a> | </li>
			<li><a href="<?php echo add_query_arg( array( 'page' => 'bp-components', 'action' => 'inactive' ), bp_get_admin_url( $page ) ); ?>" <?php if ( $action === 'inactive' ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', count( $inactive_components ), 'buddypress' ), number_format_i18n( count( $inactive_components ) ) ); ?></a> | </li>
			<li><a href="<?php echo add_query_arg( array( 'page' => 'bp-components', 'action' => 'mustuse'  ), bp_get_admin_url( $page ) ); ?>" <?php if ( $action === 'mustuse'  ) : ?>class="current"<?php endif; ?>><?php printf( _n(  'Must-Use <span class="count">(%s)</span>', 'Must-Use <span class="count">(%s)</span>', count( $required_components ), 'buddypress' ), number_format_i18n( count( $required_components ) ) ); ?></a></li>
		</ul>

		<table class="widefat fixed plugins" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="cb" class="manage-column column-cb check-column">&nbsp;</th>
					<th scope="col" id="name" class="manage-column column-name" style="width: 190px;"><?php _e( 'Component', 'buddypress' ); ?></th>
					<th scope="col" id="description" class="manage-column column-description"><?php _e( 'Description', 'buddypress' ); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-cb check-column">&nbsp;</th>
					<th scope="col" class="manage-column column-name" style="width: 190px;"><?php _e( 'Component', 'buddypress' ); ?></th>
					<th scope="col" class="manage-column column-description"><?php _e( 'Description', 'buddypress' ); ?></th>
				</tr>
			</tfoot>

			<tbody id="the-list">
				
				<?php if ( !empty( $current_components ) ) : ?>

					<?php foreach ( $current_components as $name => $labels ) : ?>

						<?php if ( !in_array( $name, array( 'core', 'members' ) ) ) :
							$class = isset( $active_components[esc_attr( $name )] ) ? 'active' : 'inactive';
						else :
							$class = 'active';
						endif; ?>

						<tr id="<?php echo $name; ?>" class="<?php echo $name . ' ' . $class; ?>">
							<th scope="row">

								<?php if ( !in_array( $name, array( 'core', 'members' ) ) ) : ?>

									<input type="checkbox" id="bp_components[<?php echo esc_attr( $name ); ?>]" name="bp_components[<?php echo esc_attr( $name ); ?>]" value="1"<?php checked( isset( $active_components[esc_attr( $name )] ) ); ?> />

								<?php endif; ?>

								<label class="screen-reader-text" for="bp_components[<?php echo esc_attr( $name ); ?>]"><?php sprintf( __( 'Select %s', 'buddypress' ), esc_html( $labels['title'] ) );  ?></label>
							</th>
							<td class="plugin-title" style="width: 190px;">
								<span></span>
								<strong><?php echo esc_html( $labels['title'] ); ?></strong>
								<div class="row-actions-visible">
									
								</div>
							</td>

							<td class="column-description desc">
								<div class="plugin-description">
									<p><?php echo $labels['description']; ?></p>
								</div>
								<div class="active second plugin-version-author-uri">
									
								</div>
							</td>
						</tr>

					<?php endforeach ?>

				<?php else : ?>
						
					<tr class="no-items">
						<td class="colspanchange" colspan="3"><?php _e( 'No components found.', 'buddypress' ); ?></td>
					</tr>

				<?php endif; ?>

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
 * @global BuddyPress $bp
 * @return false On failure
 */
function bp_core_admin_components_settings_handler() {
	global $bp;

	if ( isset( $_POST['bp-admin-component-submit'] ) ) {
		if ( !check_admin_referer('bp-admin-component-setup') )
			return false;

		// Settings form submitted, now save the settings. First, set active components
		if ( isset( $_POST['bp_components'] ) ) {
			// Save settings and upgrade schema
			require_once( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-schema.php' );
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
