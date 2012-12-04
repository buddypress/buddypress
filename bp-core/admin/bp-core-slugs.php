<?php

/**
 * BuddyPress Admin Slug Functions
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Renders the page mapping admin panel.
 *
 * @since BuddyPress (1.6)
 * @todo Use settings API
 * @uses bp_core_admin_component_options()
 */
function bp_core_admin_slugs_settings() {
?>

	<div class="wrap">
		<?php screen_icon( 'buddypress'); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Pages', 'buddypress' ) ); ?></h2>
		<form action="" method="post" id="bp-admin-page-form">

			<?php bp_core_admin_slugs_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="bp-admin-pages-submit" id="bp-admin-pages-submit" value="<?php _e( 'Save Settings', 'buddypress' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'bp-admin-pages-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Creates reusable markup for page setup on the Components and Pages dashboard panel.
 *
 * @package BuddyPress
 * @since BuddyPress (1.6)
 * @todo Use settings API
 */
function bp_core_admin_slugs_options() {
	global $bp;

	// Get the existing WP pages
	$existing_pages = bp_core_get_directory_page_ids();

	// Set up an array of components (along with component names) that have
	// directory pages.
	$directory_pages = array();

	// Loop through loaded components and collect directories
	if ( is_array( $bp->loaded_components ) ) {
		foreach( $bp->loaded_components as $component_slug => $component_id ) {

			// Only components that need directories should be listed here
			if ( isset( $bp->{$component_id} ) && !empty( $bp->{$component_id}->has_directory ) ) {

				// component->name was introduced in BP 1.5, so we must provide a fallback
				$directory_pages[$component_id] = !empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
			}
		}
	}

	/** Directory Display *****************************************************/

	$directory_pages = apply_filters( 'bp_directory_pages', $directory_pages );

	if ( !empty( $directory_pages ) ) : ?>

		<h3><?php _e( 'Directories', 'buddypress' ); ?></h3>

		<p><?php _e( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddypress' ); ?></p>

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
								'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
							) ); ?>

							<a href="<?php echo admin_url( add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) ); ?>" class="button-secondary"><?php _e( 'New Page', 'buddypress' ); ?></a>
							<input class="button-primary" type="submit" name="bp-admin-pages-single" value="<?php _e( 'Save', 'buddypress' ) ?>" />

							<?php if ( !empty( $existing_pages[$name] ) ) : ?>

								<a href="<?php echo get_permalink( $existing_pages[$name] ); ?>" class="button-secondary" target="_bp"><?php _e( 'View', 'buddypress' ); ?></a>

							<?php endif; ?>

							<?php if ( ! bp_is_root_blog() ) restore_current_blog(); ?>

						</td>
					</tr>


				<?php endforeach ?>

				<?php do_action( 'bp_active_external_directories' ); ?>

			</tbody>
		</table>

	<?php

	endif;

	/** Static Display ********************************************************/

	// Static pages
	$static_pages = array(
		'register' => __( 'Register', 'buddypress' ),
		'activate' => __( 'Activate', 'buddypress' ),
	);

	$static_pages = apply_filters( 'bp_static_pages', $static_pages );

	if ( !empty( $static_pages ) ) : ?>

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

							<?php if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() ); ?>

							<?php echo wp_dropdown_pages( array(
								'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
								'echo'             => false,
								'show_option_none' => __( '- None -', 'buddypress' ),
								'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
							) ) ?>

							<a href="<?php echo admin_url( add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) ); ?>" class="button-secondary"><?php _e( 'New Page', 'buddypress' ); ?></a>
							<input class="button-primary" type="submit" name="bp-admin-pages-single" value="<?php _e( 'Save', 'buddypress' ) ?>" />

							<?php if ( !empty( $existing_pages[$name] ) ) : ?>

								<a href="<?php echo get_permalink( $existing_pages[$name] ); ?>" class="button-secondary" target="_bp"><?php _e( 'View', 'buddypress' ); ?></a>

							<?php endif; ?>

							<?php if ( ! bp_is_root_blog() ) restore_current_blog(); ?>

						</td>
					</tr>

				<?php endforeach ?>

				<?php do_action( 'bp_active_external_pages' ); ?>

			</tbody>
		</table>

		<?php
	endif;
}

/**
 * Handle saving of the BuddyPress slugs
 *
 * @since BuddyPress (1.6)
 * @todo Use settings API
 * @return False if referer does not check out
 */
function bp_core_admin_slugs_setup_handler() {

	if ( isset( $_POST['bp-admin-pages-submit'] ) || isset( $_POST['bp-admin-pages-single'] ) ) {
		if ( !check_admin_referer( 'bp-admin-pages-setup' ) )
			return false;

		// Then, update the directory pages
		if ( isset( $_POST['bp_pages'] ) ) {

			$directory_pages = array();

			foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
				if ( !empty( $value ) ) {
					$directory_pages[$key] = (int) $value;
				}
			}
			bp_core_update_directory_page_ids( $directory_pages );
		}

		$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'bp_admin_init', 'bp_core_admin_slugs_setup_handler' );
