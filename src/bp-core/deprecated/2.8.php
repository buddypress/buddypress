<?php
/**
 * Deprecated functions.
 *
 * @deprecated 2.8.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Determines whether the current installation is running PHP 5.3 or greater.
 *
 * BuddyPress 2.8 introduces a minimum PHP requirement of PHP 5.3.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 *
 * @return bool
 */
function bp_core_admin_is_running_php53_or_greater() {
	_deprecated_function( __FUNCTION__, '2.8' );
	return version_compare( PHP_VERSION, '5.3', '>=' );
}

/**
 * Replaces WP's default update notice on plugins.php with an error message, when site is not running PHP 5.3 or greater.
 *
 * Originally hooked to 'load-plugins.php' with priority 100.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 */
function bp_core_admin_maybe_disable_update_row_for_php53_requirement() {
	if ( bp_core_admin_is_running_php53_or_greater() ) {
		return;
	}

	$loader = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';

	remove_action( "after_plugin_row_{$loader}", 'wp_plugin_update_row', 10 );
	add_action( "after_plugin_row_{$loader}", 'bp_core_admin_php52_plugin_row', 10, 2 );
}

/**
 * On the "Dashboard > Updates" page, remove BuddyPress from plugins list if PHP < 5.3.
 *
 * Originally hooked to 'load-update-core.php'.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 */
function bp_core_admin_maybe_remove_from_update_core() {
	if ( bp_core_admin_is_running_php53_or_greater() ) {
		return;
	}

	// Add filter to remove BP from the update plugins list.
	add_filter( 'site_transient_update_plugins', 'bp_core_admin_remove_buddypress_from_update_transient' );
}

/**
 * Filter callback to remove BuddyPress from the update plugins list.
 *
 * Attached to the 'site_transient_update_plugins' filter.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 *
 * @param  object $retval Object of plugin update data.
 * @return object
 */
function bp_core_admin_remove_buddypress_from_update_transient( $retval ) {
	_deprecated_function( __FUNCTION__, '2.8' );

	$loader = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';

	// Remove BP from update plugins list.
	if ( isset( $retval->response[ $loader ] ) ) {
		unset( $retval->response[ $loader ] );
	}

	return $retval;
}

/**
 * Outputs a replacement for WP's default update notice, when site is not running PHP 5.3 or greater.
 *
 * When we see that a site is not running PHP 5.3 and is trying to update to
 * BP 2.8+, we replace WP's default notice with our own, which both provides a
 * link to our documentation of the requirement, and removes the link that
 * allows a single plugin to be updated.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 *
 * @param string $file        Plugin filename. buddypress/bp-loader.php.
 * @param array  $plugin_data Data about the BuddyPress plugin, as returned by the
 *                            plugins API.
 */
function bp_core_admin_php52_plugin_row( $file, $plugin_data ) {
	_deprecated_function( __FUNCTION__, '2.8' );

	if ( is_multisite() && ! is_network_admin() ) {
		return;
	}

	$current = get_site_transient( 'update_plugins' );
	if ( ! isset( $current->response[ $file ] ) ) {
		return false;
	}

	$response = $current->response[ $file ];

	// No need to do this if update is for < BP 2.8.
	if ( version_compare( $response->new_version, '2.8', '<' ) ) {
		return false;
	}

	$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

	if ( is_network_admin() ) {
		$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
	} else {
		$active_class = is_plugin_active( $file ) ? ' active' : '';
	}

	// WP 4.6 uses different markup for the plugin row notice.
	if ( function_exists( 'wp_get_ext_types' ) ) {
		$p = '<p>%s</p>';

	// WP < 4.6.
	} else {
		$p = '%s';

		// Ugh.
		$active_class .= ' not-shiny';
	}

	echo '<tr class="plugin-update-tr' . esc_attr( $active_class ) . '" id="' . esc_attr( $response->slug . '-update' ) . '" data-slug="' . esc_attr( $response->slug ) . '" data-plugin="' . esc_attr( $file ) . '"><td colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="plugin-update colspanchange"><div class="update-message inline notice notice-error notice-alt">';

	printf(
		// phpcs:ignore WordPress.Security.EscapeOutput
		$p,
		esc_html__( 'A BuddyPress update is available, but your system is not compatible.', 'buddypress' ) . ' ' .
		sprintf( esc_html__( 'See %s for more information.', 'buddypress' ), '<a href="https://codex.buddypress.org/getting-started/buddypress-2-8-will-require-php-5-3/">' . esc_html( 'the Codex guide', 'buddypress' ) . '</a>' )
	);

	echo '</div></td></tr>';

	/*
	 * JavaScript to disable the bulk upgrade checkbox.
	 * See WP_Plugins_List_Table::single_row().
	 */
	$checkbox_id = 'checkbox_' . md5( $plugin_data['Name'] );

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo "<script type='text/javascript'>document.getElementById('$checkbox_id').disabled = true;</script>";
}

/**
 * Add an admin notice to installations that are not running PHP 5.3+.
 *
 * @since 2.7.0
 * @deprecated 2.8.0
 */
function bp_core_admin_php53_admin_notice() {
	_deprecated_function( __FUNCTION__, '2.8' );

	// If not on the Plugins page, stop now.
	if ( 'plugins' !== get_current_screen()->parent_base ) {
		return;
	}

	if ( ! current_user_can( 'update_core' ) ) {
		return;
	}

	if ( bp_core_admin_is_running_php53_or_greater() ) {
		return;
	}

	$notice_id = 'bp28-php53';
	if ( bp_get_option( "bp-dismissed-notice-$notice_id" ) ) {
		return;
	}

	$bp  = buddypress();
	$min = bp_core_get_minified_asset_suffix();

	wp_enqueue_script(
		'bp-dismissible-admin-notices',
		"{$bp->plugin_url}bp-core/admin/js/dismissible-admin-notices{$min}.js",
		array( 'jquery' ),
		bp_get_version(),
		true
	);
	?>

	<div id="message" class="error notice is-dismissible bp-is-dismissible" data-noticeid="<?php echo esc_attr( $notice_id ); ?>">
		<p><strong><?php esc_html_e( 'Your site is not ready for BuddyPress 2.8.', 'buddypress' ); ?></strong></p>
		<p>
			<?php
			/* translators: %s: the site's PHP version number */
			printf( esc_html_x( 'Your site is currently running PHP version %s, while BuddyPress 2.8 will require version 5.3+.', 'deprecated string', 'buddypress' ), esc_html( phpversion() ) );
			?>
			&nbsp;
			<?php
			/* translators: %s: the url to a codex page */
			printf( esc_html_x( 'See %s for more information.', 'deprecated string', 'buddypress' ), '<a href="https://codex.buddypress.org/getting-started/buddypress-2-8-will-require-php-5-3/">' . esc_html( 'the Codex guide', 'buddypress' ) . '</a>' );
			?>
		</p>
		<?php wp_nonce_field( "bp-dismissible-notice-$notice_id", "bp-dismissible-nonce-$notice_id" ); ?>
	</div>
	<?php
}

