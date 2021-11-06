<?php
/**
 * BuddyPress Admin Slug Functions.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the page mapping admin panel.
 *
 * @since 1.6.0
 * @todo Use settings API
 */
function bp_core_admin_slugs_settings() {
	bp_core_admin_tabbed_screen_header( __( 'BuddyPress Settings', 'buddypress' ), __( 'Pages', 'buddypress' ) );
?>

	<div class="buddypress-body">
		<form action="" method="post" id="bp-admin-page-form">

			<?php bp_core_admin_slugs_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-pages-submit" id="bp-admin-pages-submit" value="<?php esc_attr_e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-pages-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Generate a list of directory pages, for use when building Components panel markup.
 *
 * @since 2.4.1
 *
 * @return array
 */
function bp_core_admin_get_directory_pages() {
	$bp = buddypress();
	$directory_pages = array();

	// Loop through loaded components and collect directories.
	if ( is_array( $bp->loaded_components ) ) {
		foreach( $bp->loaded_components as $component_slug => $component_id ) {

			// Only components that need directories should be listed here.
			if ( isset( $bp->{$component_id} ) && !empty( $bp->{$component_id}->has_directory ) ) {

				// The component->name property was introduced in BP 1.5, so we must provide a fallback.
				$directory_pages[$component_id] = !empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
			}
		}
	}

	/** Directory Display *****************************************************/

	/**
	 * Filters the loaded components needing directory page association to a WordPress page.
	 *
	 * @since 1.5.0
	 *
	 * @param array $directory_pages Array of available components to set associations for.
	 */
	return apply_filters( 'bp_directory_pages', $directory_pages );
}

/**
 * Generate a list of static pages, for use when building Components panel markup.
 *
 * By default, this list contains 'register' and 'activate'.
 *
 * @since 2.4.1
 *
 * @return array
 */
function bp_core_admin_get_static_pages() {
	$static_pages = array(
		'register' => __( 'Register', 'buddypress' ),
		'activate' => __( 'Activate', 'buddypress' ),
	);

	/**
	 * Filters the default static pages for BuddyPress setup.
	 *
	 * @since 1.6.0
	 *
	 * @param array $static_pages Array of static default static pages.
	 */
	return apply_filters( 'bp_static_pages', $static_pages );
}

/**
 * Creates reusable markup for page setup on the Components and Pages dashboard panel.
 *
 * @package BuddyPress
 * @since 1.6.0
 * @todo Use settings API
 */
function bp_core_admin_slugs_options() {

	// Get the existing WP pages.
	$existing_pages = bp_core_get_directory_page_ids();

	// Set up an array of components (along with component names) that have directory pages.
	$directory_pages = bp_core_admin_get_directory_pages();

	if ( ! empty( $directory_pages ) ) : ?>

		<h3><?php esc_html_e( 'Directories', 'buddypress' ); ?></h3>

		<p><?php esc_html_e( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddypress' ); ?></p>

		<table class="form-table">
			<tbody>

				<?php foreach ( $directory_pages as $name => $label ) : ?>

					<tr valign="top">
						<th scope="row">
							<label for="bp_pages[<?php echo esc_attr( $name ) ?>]"><?php echo esc_html( $label ) ?></label>
						</th>

						<td>

							<?php if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() ); ?>

							<?php echo wp_dropdown_pages( array(
								'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
								'echo'             => false,
								'show_option_none' => __( '- None -', 'buddypress' ),
								'selected'         => ! empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
							) ); ?>

							<?php if ( ! empty( $existing_pages[ $name ] ) && get_post( $existing_pages[ $name ] ) ) : ?>

								<a href="<?php echo esc_url( get_permalink( $existing_pages[$name] ) ); ?>" class="button-secondary" target="_bp">
									<?php esc_html_e( 'View', 'buddypress' ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'buddypress' ); ?></span>
								</a>

							<?php endif; ?>

							<?php if ( ! bp_is_root_blog() ) restore_current_blog(); ?>

						</td>
					</tr>


				<?php endforeach ?>

				<?php

				/**
				 * Fires after the display of default directories.
				 *
				 * Allows plugins to add their own directory associations.
				 *
				 * @since 1.5.0
				 */
				do_action( 'bp_active_external_directories' ); ?>

			</tbody>
		</table>

	<?php

	endif;

	/** Static Display ********************************************************/

	$static_pages = bp_core_admin_get_static_pages();

	if ( ! empty( $static_pages ) ) : ?>

		<h3><?php esc_html_e( 'Registration', 'buddypress' ); ?></h3>

		<?php if ( bp_allow_access_to_registration_pages() ) : ?>
			<p>
				<?php esc_html_e( 'Associate WordPress Pages with the following BuddyPress Registration pages.', 'buddypress' ); ?>
				<?php esc_html_e( 'These pages will only be reachable by users who are not logged in.', 'buddypress' ); ?>
			</p>

			<table class="form-table">
				<tbody>

					<?php foreach ( $static_pages as $name => $label ) : ?>

						<tr valign="top">
							<th scope="row">
								<label for="bp_pages[<?php echo esc_attr( $name ) ?>]"><?php echo esc_html( $label ) ?></label>
							</th>

							<td>

								<?php if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() ); ?>

								<?php echo wp_dropdown_pages( array(
									'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
									'echo'             => false,
									'show_option_none' => __( '- None -', 'buddypress' ),
									'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
								) ) ?>

								<?php if ( ! bp_is_root_blog() ) restore_current_blog(); ?>

							</td>
						</tr>

					<?php endforeach; ?>

					<?php

					/**
					 * Fires after the display of default static pages for BuddyPress setup.
					 *
					 * @since 1.5.0
					 */
					do_action( 'bp_active_external_pages' ); ?>

				</tbody>
			</table>
		<?php else : ?>
			<?php if ( is_multisite() ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: the link to the Network settings page */
						esc_html_x( 'Registration is currently disabled. Before associating a page is allowed, please enable registration by selecting either the "User accounts may be registered" or "Both sites and user accounts can be registered" option on %s.', 'Disabled registration message for multisite config', 'buddypress' ),
						sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( network_admin_url( 'settings.php' ) ),
							esc_html_x( 'this page', 'Link text for the Multisite’s network settings page', 'buddypress' )
						)
					);
					?>
				</p>
			<?php else : ?>
				<p>
					<?php
					printf(
						/* translators: %s: the link to the Site general options page */
						esc_html_x( 'Registration is currently disabled. Before associating a page is allowed, please enable registration by clicking on the "Anyone can register" checkbox on %s.', 'Disabled registration message for regular site config', 'buddypress' ),
						sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( admin_url( 'options-general.php' ) ),
							esc_html_x( 'this page', 'Link text for the Site’s general options page', 'buddypress' )
						)
					);
					?>
				</p>
			<?php endif; ?>
		<?php endif;
	endif;
}

/**
 * Handle saving of the BuddyPress slugs.
 *
 * @since 1.6.0
 * @todo Use settings API
 */
function bp_core_admin_slugs_setup_handler() {

	if ( isset( $_POST['bp-admin-pages-submit'] ) ) {
		if ( ! check_admin_referer( 'bp-admin-pages-setup' ) ) {
			return false;
		}

		// Then, update the directory pages.
		if ( isset( $_POST['bp_pages'] ) ) {
			$valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

			$new_directory_pages = array();
			foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
				if ( isset( $valid_pages[ $key ] ) ) {
					$new_directory_pages[ $key ] = (int) $value;
				}
			}
			bp_core_update_directory_page_ids( $new_directory_pages );
		}

		$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'bp_admin_init', 'bp_core_admin_slugs_setup_handler' );
