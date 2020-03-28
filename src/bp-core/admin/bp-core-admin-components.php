<?php
/**
 * BuddyPress Admin Component Functions.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the Component Setup admin panel.
 *
 * @since 1.6.0
 *
 */
function bp_core_admin_components_settings() {
?>

	<div class="wrap">

		<h1><?php _e( 'BuddyPress Settings', 'buddypress' ); ?> </h1>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Components', 'buddypress' ) ); ?></h2>
		<form action="" method="post" id="bp-admin-component-form">

			<?php bp_core_admin_components_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-component-submit" id="bp-admin-component-submit" value="<?php esc_attr_e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-component-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Creates reusable markup for component setup on the Components and Pages dashboard panel.
 *
 * @since 1.6.0
 *
 * @todo Use settings API
 */
function bp_core_admin_components_options() {

	// Declare local variables.
	$deactivated_components = array();

	/**
	 * Filters the array of available components.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value Active components.
	 */
	$active_components      = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

	// The default components (if none are previously selected).
	$default_components = array(
		'xprofile' => array(
			'title'       => __( 'Extended Profiles', 'buddypress' ),
			'description' => __( 'Customize your community with fully editable profile fields that allow your users to describe themselves.', 'buddypress' )
		),
		'settings' => array(
			'title'       => __( 'Account Settings', 'buddypress' ),
			'description' => __( 'Allow your users to modify their account and notification settings directly from within their profiles.', 'buddypress' )
		),
		'notifications' => array(
			'title'       => __( 'Notifications', 'buddypress' ),
			'description' => __( 'Notify members of relevant activity with a toolbar bubble and/or via email, and allow them to customize their notification settings.', 'buddypress' )
		),
	);

	$optional_components = bp_core_admin_get_components( 'optional' );
	$required_components = bp_core_admin_get_components( 'required' );
	$retired_components  = bp_core_admin_get_components( 'retired'  );

	// Merge optional and required together.
	$all_components = $optional_components + $required_components;

	// If this is an upgrade from before BuddyPress 1.5, we'll have to convert
	// deactivated components into activated ones.
	if ( empty( $active_components ) ) {
		$deactivated_components = bp_get_option( 'bp-deactivated-components' );
		if ( !empty( $deactivated_components ) ) {

			// Trim off namespace and filename.
			$trimmed = array();
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
			}

			// Loop through the optional components to create an active component array.
			foreach ( array_keys( (array) $optional_components ) as $ocomponent ) {
				if ( !in_array( $ocomponent, $trimmed ) ) {
					$active_components[$ocomponent] = 1;
				}
			}
		}
	}

	// On new install, set active components to default.
	if ( empty( $active_components ) ) {
		$active_components = $default_components;
	}

	// Core component is always active.
	$active_components['core'] = $all_components['core'];
	$inactive_components       = array_diff( array_keys( $all_components ) , array_keys( $active_components ) );

	/** Display **************************************************************
	 */

	// Get the total count of all plugins.
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
		case 'retired' :
			$current_components = $retired_components;
			break;
	}

	$component_views = array(
		array(
			'action' => 'all',
			'view'   => sprintf(
				/* translators: %s: the number of installed components */
				_nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $all_count, 'plugins', 'buddypress' ),
				number_format_i18n( $all_count )
			),
		),
		array(
			'action' => 'active',
			'view'   => sprintf(
				/* translators: %s: the number of active components */
				_n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', count( $active_components ), 'buddypress' ),
				number_format_i18n( count( $active_components ) )
			),
		),
		array(
			'action' => 'inactive',
			'view'   => sprintf(
				/* translators: %s: the number of inactive components */
				_n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', count( $inactive_components ), 'buddypress' ),
				number_format_i18n( count( $inactive_components ) )
			),
		),
		array(
			'action' => 'mustuse',
			'view'   => sprintf(
				/* translators: %s: the number of must-Use components */
				_n( 'Must-Use <span class="count">(%s)</span>', 'Must-Use <span class="count">(%s)</span>', count( $required_components ), 'buddypress' ),
				number_format_i18n( count( $required_components ) )
			),
		),
		array(
			'action' => 'retired',
			'view'   => sprintf(
				/* translators: %s: the number of retired components */
				_n( 'Retired <span class="count">(%s)</span>',  'Retired <span class="count">(%s)</span>',  count( $retired_components ),  'buddypress' ),
				number_format_i18n( count( $retired_components ) )
			),
		),
	);
	?>

	<h3 class="screen-reader-text"><?php
		/* translators: accessibility text */
		esc_html_e( 'Filter components list', 'buddypress' );
	?></h3>

	<ul class="subsubsub">
		<?php foreach ( $component_views as $component_view ) : ?>
			<li>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bp-components', 'action' => $component_view['action'] ), bp_get_admin_url( $page ) ) ); ?>" <?php if ( $action === $component_view['action'] ) : ?>class="current"<?php endif; ?>>
					<?php echo wp_kses( $component_view['view'], array( 'span' => array( 'class' => true ) ) ); ?>
				</a><?php echo 'retired' !== $component_view['action'] ? ' |' : ''; ?>
			</li>
		<?php endforeach ;?>
	</ul>

	<h3 class="screen-reader-text"><?php
		/* translators: accessibility text */
		esc_html_e( 'Components list', 'buddypress' );
	?></h3>

	<table class="wp-list-table widefat plugins">
		<thead>
			<tr>
				<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox" <?php checked( empty( $inactive_components ) ); ?>>
					<label class="screen-reader-text" for="cb-select-all-1"><?php
					/* translators: accessibility text */
					_e( 'Enable or disable all optional components in bulk', 'buddypress' );
				?></label></td>
				<th scope="col" id="name" class="manage-column column-title column-primary"><?php _e( 'Component', 'buddypress' ); ?></th>
				<th scope="col" id="description" class="manage-column column-description"><?php _e( 'Description', 'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody id="the-list">

			<?php if ( !empty( $current_components ) ) : ?>

				<?php foreach ( $current_components as $name => $labels ) : ?>

					<?php if ( !in_array( $name, array( 'core', 'members' ) ) ) :
						$class = isset( $active_components[esc_attr( $name )] ) ? 'active' : 'inactive';
					else :
						$class = 'active';
					endif; ?>

					<tr id="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $name ) . ' ' . esc_attr( $class ); ?>">
						<th scope="row" class="check-column">

							<?php if ( !in_array( $name, array( 'core', 'members' ) ) ) : ?>

								<input type="checkbox" id="<?php echo esc_attr( "bp_components[$name]" ); ?>" name="<?php echo esc_attr( "bp_components[$name]" ); ?>" value="1"<?php checked( isset( $active_components[esc_attr( $name )] ) ); ?> /><label for="<?php echo esc_attr( "bp_components[$name]" ); ?>" class="screen-reader-text"><?php
									/* translators: accessibility text */
									printf( __( 'Select %s', 'buddypress' ), esc_html( $labels['title'] ) ); ?></label>

							<?php endif; ?>

						</th>
						<td class="plugin-title column-primary">
							<label for="<?php echo esc_attr( "bp_components[$name]" ); ?>">
								<span aria-hidden="true"></span>
								<strong><?php echo esc_html( $labels['title'] ); ?></strong>
							</label>
						</td>

						<td class="column-description desc">
							<div class="plugin-description">
								<p><?php echo $labels['description']; ?></p>
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

		<tfoot>
			<tr>
				<td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox" <?php checked( empty( $inactive_components ) ); ?>>
					<label class="screen-reader-text" for="cb-select-all-2"><?php
					/* translators: accessibility text */
					_e( 'Enable or disable all optional components in bulk', 'buddypress' );
				?></label></td>
				<th class="manage-column column-title column-primary"><?php _e( 'Component', 'buddypress' ); ?></th>
				<th class="manage-column column-description"><?php _e( 'Description', 'buddypress' ); ?></th>
			</tr>
		</tfoot>

	</table>

	<input type="hidden" name="bp_components[members]" value="1" />

	<?php
}

/**
 * Handle saving the Component settings.
 *
 * @since 1.6.0
 *
 * @todo Use settings API when it supports saving network settings
 */
function bp_core_admin_components_settings_handler() {

	// Bail if not saving settings.
	if ( ! isset( $_POST['bp-admin-component-submit'] ) )
		return;

	// Bail if nonce fails.
	if ( ! check_admin_referer( 'bp-admin-component-setup' ) )
		return;

	// Settings form submitted, now save the settings. First, set active components.
	if ( isset( $_POST['bp_components'] ) ) {

		// Load up BuddyPress.
		$bp = buddypress();

		// Save settings and upgrade schema.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		$submitted = stripslashes_deep( $_POST['bp_components'] );
		$bp->active_components = bp_core_admin_get_active_components_from_submitted_settings( $submitted );

		bp_core_install( $bp->active_components );
		bp_core_add_page_mappings( $bp->active_components );
		bp_update_option( 'bp-active-components', $bp->active_components );
	}

	// Where are we redirecting to?
	$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components', 'updated' => 'true' ), 'admin.php' ) );

	// Redirect.
	wp_redirect( $base_url );
	die();
}
add_action( 'bp_admin_init', 'bp_core_admin_components_settings_handler' );

/**
 * Calculates the components that should be active after save, based on submitted settings.
 *
 * The way that active components must be set after saving your settings must
 * be calculated differently depending on which of the Components subtabs you
 * are coming from:
 * - When coming from All or Active, the submitted checkboxes accurately
 *   reflect the desired active components, so we simply pass them through
 * - When coming from Inactive, components can only be activated - already
 *   active components will not be passed in the $_POST global. Thus, we must
 *   parse the newly activated components with the already active components
 *   saved in the $bp global
 * - When activating a Retired component, the situation is similar to Inactive.
 * - When deactivating a Retired component, no value is passed in the $_POST
 *   global (because the component settings are checkboxes). So, in order to
 *   determine whether a retired component is being deactivated, we retrieve a
 *   list of retired components, and check each one to ensure that its checkbox
 *   is not present, before merging the submitted components with the active
 *   ones.
 *
 * @since 1.7.0
 *
 * @param array $submitted This is the array of component settings coming from the POST
 *                         global. You should stripslashes_deep() before passing to this function.
 * @return array The calculated list of component settings
 */
function bp_core_admin_get_active_components_from_submitted_settings( $submitted ) {
	$current_action = 'all';

	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'active', 'inactive', 'retired' ) ) ) {
		$current_action = $_GET['action'];
	}

	$current_components = buddypress()->active_components;

	switch ( $current_action ) {
		case 'retired' :
			$retired_components = bp_core_admin_get_components( 'retired' );
			foreach ( array_keys( $retired_components ) as $retired_component ) {
				if ( ! isset( $submitted[ $retired_component ] ) ) {
					unset( $current_components[ $retired_component ] );
				}
			} // Fall through.


		case 'inactive' :
			$components = array_merge( $submitted, $current_components );
			break;

		case 'all' :
		case 'active' :
		default :
			$components = $submitted;
			break;
	}

	return $components;
}

/**
 * Return a list of component information.
 *
 * We use this information both to build the markup for the admin screens, as
 * well as to do some processing on settings data submitted from those screens.
 *
 * @since 1.7.0
 *
 * @param string $type Optional; component type to fetch. Default value is 'all', or 'optional', 'retired', 'required'.
 * @return array Requested components' data.
 */
function bp_core_admin_get_components( $type = 'all' ) {
	$components = bp_core_get_components( $type );

	/**
	 * Filters the list of component information.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $components Array of component information.
	 * @param string $type       Type of component list requested.
	 *                           Possible values include 'all', 'optional',
	 *                           'retired', 'required'.
	 */
	return apply_filters( 'bp_core_admin_get_components', $components, $type );
}
