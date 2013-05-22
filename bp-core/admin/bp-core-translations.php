<?php
/**
 * Handles automatic download of translations
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since BuddyPress (1.8)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates an instance of the BP_Translate class that downloads translations for BuddyPress automatically
 *
 * @since BuddyPress (1.8)
 */
function bp_automatic_translations() {
	if ( ! current_user_can( 'update_plugins' ) )
		return;

	if ( ! is_admin() || ( is_multisite() && ! is_network_admin() ) )
		return;

	// Store a reference to the BP_Translate object in the BP global for other plugins
	buddypress()->translate = BP_Translate::get_instance();
}
add_action( 'bp_admin_init', 'bp_automatic_translations' );

/**
 * If we're in the WordPress dashboard, and a pending translation is available, bump the update count.
 *
 * This has to be hooked before admin_init due to wp_get_update_data() being invoked in wp-admin/menu.php before the admin_init action is called.
 *
 * @since BuddyPress (1.8)
 */
function bp_admin_maybe_bump_update_count() {
	if ( ! current_user_can( 'update_plugins' ) )
		return;

	if ( ! is_admin() || ( is_multisite() && ! is_network_admin() ) )
		return;

	add_filter( 'wp_get_update_data', array( 'BP_Translate', 'maybe_bump_update_count' ) );
}
add_action( 'bp_init', 'bp_admin_maybe_bump_update_count' );

/**
 * Fetch translations from http://translate.wordpress.org/ and display an update prompt on the admin dashboard.
 *
 * @since BuddyPress (1.8)
 */
class BP_Translate {

	/**
	 * Singleton instance of the BP_Translate class
	 *
	 * @since BuddyPress (1.8)
	 * @var BP_Translate
	 */
	private static $instance;

	/**
	 * Return the singleton instance of the BP_Translate class
	 *
	 * @return BP_Translate
	 * @since BuddyPress (1.8)
	 */
	static public function get_instance() {
		if ( ! self::$instance )
			self::$instance = new BP_Translate;

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since BuddyPress (1.8)
	 */
	public function __construct() {
		$this->register_actions();
		$this->register_cron();     // Intentionally after actions
	}

	/**
	 * Hook into actions necessary to automate the translation process and customise wp-admin
	 *
	 * @since BuddyPress (1.8)
	 */
	protected function register_actions() {
		add_action( 'core_upgrade_preamble',                               array( __CLASS__, 'updates_screen' ) );
		add_action( 'update-core-custom_do-update-buddypress-translation', array( __CLASS__, 'updates_screen_iframe' ) );
		add_action( 'update-custom_update-buddypress-translation',         array( __CLASS__, 'update_translation' ) );
	}

	/**
	 * Register cron task to check for language updates
	 *
	 * @since BuddyPress (1.8)
	 */
	protected function register_cron() {
		if ( ! wp_next_scheduled( 'bp_translate_update_check' ) )
			wp_schedule_event( time(), 'daily', 'bp_translate_update_check' );
	}

	/**
	 * Get the current locale
	 *
	 * @return string
	 * @since BuddyPress (1.8)
	 */
	static public function get_locale() {
		return apply_filters( 'buddypress_locale', get_locale() );
	}

	/**
	 * Get the GlotPress locale code for the current locale
	 *
	 * @return string|bool Returns bool if an error occured, otherwise the GlotPress locale as a string
	 * @since BuddyPress (1.8)
	 */
	static public function get_glotpress_locale() {
		static $glotpress_locale;
		if ( ! empty( $glotpress_locale ) )
			return $glotpress_locale;

		// Get the list of available translations from translate.wordpress.org
		$translations = wp_remote_get( sprintf( 'https://translate.wordpress.org/api/projects/buddypress/%1$s', buddypress()->glotpress_version ) );
		if ( is_wp_error( $translations ) || wp_remote_retrieve_response_code( $translations ) !== 200 )
			return false;

		$translations = json_decode( wp_remote_retrieve_body( $translations ) );
		if ( is_null( $translations ) )
			return false;

		// Does the requested $locale have an available translation?
		$translations = array_shift( wp_list_filter( $translations->translation_sets, array( 'wp_locale' => BP_Translate::get_locale() ) ) );
		if ( empty( $translations ) )
			return false;

		$glotpress_locale = $translations->locale;
		return $glotpress_locale;
	}

	/**
	 * If in the WordPress dashboard, maybe bump the "available updates" count if there's a pending translation.
	 *
	 * @param array $data Counts and UI strings for available updates
	 * @return array
	 * @since BuddyPress (1.8)
	 */
	static public function maybe_bump_update_count( $data ) {
		if ( current_user_can( 'update_plugins' ) && bp_is_translation_update_pending() )
			$data['counts']['total']++;

		return $data;
	}

	/**
	 * If we have a pending translation, display a message on the wp-admin/update-core.php screen.
	 *
	 * @since BuddyPress (1.8)
	 */
	static public function updates_screen() {

		if ( BP_Translate::get_locale() === 'en_US' || ! bp_is_translation_update_pending() )
			return;
	?>
		<h3><?php _e( 'BuddyPress Translation', 'buddypress' ); ?></h3>
		<p><?php _e( 'An updated version of the current BuddyPress translation is available. Click &#8220;Update Translation&#8221;.', 'buddypress' ); ?></p>

		<form method="post" action="<?php echo esc_url( 'update-core.php?action=do-update-buddypress-translation' ); ?>" name="update-buddypress-translation" class="upgrade">
			<?php wp_nonce_field( 'update-buddypress-translation' ); ?>

			<p><input class="button" type="submit" value="<?php esc_attr_e( 'Update Translation', 'buddypress' ); ?>" name="upgrade" /></p>
		</form>
	<?php
	}

	/**
	 * We're going to update the BuddyPress translation; output an iframe in which the magic will happen.
	 *
	 * This copies the implementation for the Plugin and Theme updates wherein the work is done in a separate
	 * request that is iframed in. This allows WordPress to recover from any errors during the process.
	 *
	 * @since BuddyPress (1.8)
	 */
	static public function updates_screen_iframe() {

		if ( ! current_user_can( 'update_plugins' ) )
			wp_die( __( 'You do not have sufficient permissions to update this site.', 'buddypress' ) );

		check_admin_referer( 'update-buddypress-translation' );

		// If no pending translation updates, redirect away.
		if ( ! bp_is_translation_update_pending() ) {
			wp_redirect( admin_url('update-core.php') );
			exit;
		}

		require_once( ABSPATH . 'wp-admin/admin-header.php' );
		$title = __( 'Update BuddyPress Translation', 'buddypress' );
		$url   = wp_nonce_url( 'update.php?action=update-buddypress-translation', 'update-buddypress-translation' );

		echo '<div class="wrap">';
		screen_icon( 'plugins' );
		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<iframe src=' . esc_url( $url ) . ' style="width: 100%; height: 100%; min-height: 750px;" frameborder="0"></iframe>';
		echo '</div>';

		include( ABSPATH . 'wp-admin/admin-footer.php' );
	}

	/**
	 * Download the latest version of the current locale's translation from translate.wordpress.org
	 *
	 * @since BuddyPress (1.8)
	 */
	static public function update_translation() {

		// @todo Not sure if this does anything
		if ( ! defined( 'IFRAME_REQUEST' ) )
			define( 'IFRAME_REQUEST', true );

		if ( ! current_user_can( 'update_plugins' ) || BP_Translate::get_locale() === 'en_US' )
			wp_die( __( 'You do not have sufficient permissions to update this site.', 'buddypress' ) );

		check_admin_referer( 'update-buddypress-translation' );
		iframe_header();

		echo '<p>'  . __( 'The update process is starting. This process may take a while on some hosts, so please be patient.', 'buddypress' ) . '</p>';
		echo '<h4>' . __( 'Updating BuddyPress Translation', 'buddypress' ) . '</h4>';

		// Download the .mo to a local temporary file
		$url = 'https://translate.wordpress.org/projects/buddypress/%1$s/%2$s/default/export-translations?format=mo';
		$tmp = download_url( sprintf( $url, buddypress()->glotpress_version, BP_Translate::get_glotpress_locale() ) );

		if ( is_wp_error( $tmp ) ) {
			$css_class = 'error';
			$message   = __( 'Error: failure updating translation.', 'buddypress' );
			$message  .= '</p><p><strong>' . $tmp->get_error_message() . '</strong>';

		} else {
			$css_class  = 'updated';
			$message    = __( 'Translation updated succesfully!', 'buddypress' );
			$upload_dir = wp_upload_dir();
			$new_file   = sprintf( '%s/buddypress/buddypress-%s.mo', $upload_dir['basedir'], BP_Translate::get_locale() );

			// Check the target folder exists
			@mkdir( $upload_dir['basedir'] . '/buddypress' );

			// Move the file into place
			@copy( $tmp, $new_file );
			@unlink( $tmp );

			// Store the current timestamp for future checks, for the IF-MODIFIED-SINCE header
			bp_update_option( '_bp_translation_version', time() );

			// Clear the pending translation flag
			bp_delete_option( '_bp_translation_pending' );
		}
	?>

	<div class="<?php echo esc_attr( $css_class ); ?>">
		<p><?php echo $message; ?></p>
	</div>

	<p><a href="<?php echo self_admin_url( 'update-core.php' ); ?>" target="_parent"><?php _e( 'Return to WordPress Updates', 'buddypress' ); ?></a></p>

	<?php
		iframe_footer();
	}
}
