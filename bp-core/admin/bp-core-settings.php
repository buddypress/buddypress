<?php

/**
 * BuddyPress Admin Settings
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main settings section description for the settings page
 *
 * @since BuddyPress (r2786)
 */
function bp_admin_setting_callback_main_section() {
?>

	<p><?php _e( 'Main community settings for enabling features and setting time limits', 'buddypress' ); ?></p>

<?php
}

/**
 * Edit lock setting field
 *
 * @since BuddyPress (r2737)
 *
 * @uses bp_form_option() To output the option value
 */
function bp_admin_setting_callback_profile_sync() {
?>

	<input id="bp-disable-profile-sync" name="bp-disable-profile-sync" type="checkbox" value="1" <?php checked( !bp_disable_profile_sync( false ) ); ?> />
	<label for="bp-disable-profile-sync"><?php _e( 'Enable BuddyPress to WordPress profile syncing', 'buddypress' ); ?></label>

<?php
}

/**
 * Throttle setting field
 *
 * @since BuddyPress (r2737)
 *
 * @uses bp_form_option() To output the option value
 */
function bp_admin_setting_callback_admin_bar() {
?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( !bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php _e( 'Show the admin bar for guest/anonymous users', 'buddypress' ); ?></label>

<?php
}

/**
 * Allow favorites setting field
 *
 * @since BuddyPress (r2786)
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_avatar_uploads() {
?>

	<input id="bp-disable-avatar-uploads" name="bp-disable-avatar-uploads" type="checkbox" value="1" <?php checked( !bp_disable_avatar_uploads( true ) ); ?> />
	<label for="bp-disable-avatar-uploads"><?php _e( 'Allow members to upload avatars', 'buddypress' ); ?></label>

<?php
}

/**
 * Allow subscriptions setting field
 *
 * @since BuddyPress (r2737)
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_account_deletion() {
?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( !bp_disable_account_deletion( true ) ); ?> />
	<label for="bp-disable-account-deletion"><?php _e( 'Allow members to delete their own accounts', 'buddypress' ); ?></label>

<?php
}

/**
 * Use the WordPress editor setting field
 *
 * @since BuddyPress (r3586)
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_use_wp_editor() {
?>

	<input id="_bp_use_wp_editor" name="_bp_use_wp_editor" type="checkbox" id="_bp_use_wp_editor" value="1" <?php checked( bp_use_wp_editor( true ) ); ?> />
	<label for="_bp_use_wp_editor"><?php _e( 'Use the fancy WordPress editor to create and edit topics and replies', 'buddypress' ); ?></label>

<?php
}

/** Activity *******************************************************************/

/**
 * Groups settings section description for the settings page
 *
 * @since BuddyPress (1.6)
 */
function bp_admin_setting_callback_activity_section() {
?>

	<p><?php _e( 'Settings for the Actvity component', 'buddypress' ); ?></p>

<?php
}

/**
 * Allow Akismet setting field
 *
 * @since BuddyPress (r3575)
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_activity_akismet() {
?>

	<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
	<label for="_bp_enable_akismet"><?php _e( 'Allow Akismet to scan for activity stream spam', 'buddypress' ); ?></label>

<?php
}

/** Groups Section ************************************************************/

/**
 * Groups settings section description for the settings page
 *
 * @since BuddyPress (1.6)
 */
function bp_admin_setting_callback_groups_section() {
?>

	<p><?php _e( 'Settings for the Groups component', 'buddypress' ); ?></p>

<?php
}

/**
 * Allow topic and reply revisions
 *
 * @since BuddyPress (1.6)
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_group_creation() {
?>

	<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox"value="1" <?php checked( !bp_restrict_group_creation( true ) ); ?> />
	<label for="bp_restrict_group_creation"><?php _e( 'Enable group creation', 'buddypress' ); ?></label>

<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyPress (r2643)
 *
 * @uses screen_icon() To display the screen icon
 * @uses settings_fields() To output the hidden fields for the form
 * @uses do_settings_sections() To output the settings sections
 */
function bp_core_admin_settings() {
	global $wp_settings_fields;
	
	// We're saving our own options, until the WP Settings API is updated to work with Multisite
	$form_action = add_query_arg( 'page', 'bp-settings', bp_core_do_network_admin() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) );

	if ( !empty( $_POST['submit'] ) ) {
		check_admin_referer( 'buddypress-options' );
		
		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings
		if ( isset( $wp_settings_fields['buddypress'] ) ) {
			foreach( (array) $wp_settings_fields['buddypress'] as $section => $settings ) {
				foreach( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[$setting_name] ) ? $_POST[$setting_name] : '';
					
					bp_update_option( $setting_name, $value );
				}
			}
		}
		
		// Some legacy options are not registered with the Settings API
		$legacy_options = array(
			'bp-disable-profile-sync',
			'hide-loggedout-adminbar',
			'bp-disable-avatar-uploads',
			'bp-disable-account-deletion',
			'bp_restrict_group_creation'
		);
		
		foreach( $legacy_options as $legacy_option ) {
			// Note: Each of these options is represented by its opposite in the UI
			// Ie, the Profile Syncing option reads "Enable Sync", so when it's checked,
			// the corresponding option should be unset
			$value = isset( $_POST[$legacy_option] ) ? '' : 1;
			bp_update_option( $legacy_option, $value );
		}
	}
?>

	<div class="wrap">

		<?php screen_icon( 'buddypress' ); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Settings', 'buddypress' ) ); ?></h2>

		<form action="<?php echo $form_action ?>" method="post">

			<?php settings_fields( 'buddypress' ); ?>

			<?php do_settings_sections( 'buddypress' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Changes', 'buddypress' ); ?>" />
			</p>
		</form>
	</div>

<?php
}

/**
 * Contextual help for BuddyPress settings page
 *
 * @since BuddyPress (r3119)
 */
function bp_core_admin_settings_help() {

	$bp_contextual_help[] = __('This screen provides access to basic BuddyPress settings.', 'buddypress' );
	$bp_contextual_help[] = __('In the Main Settings you have a number of options:',     'buddypress' );
	$bp_contextual_help[] =
		'<ul>' .
			'<li>' . __( 'You can choose to lock a post after a certain number of minutes. "Locking post editing" will prevent the author from editing some amount of time after saving a post.',              'buddypress' ) . '</li>' .
			'<li>' . __( '"Throttle time" is the amount of time required between posts from a single author. The higher the throttle time, the longer a user will need to wait between posting to the forum.', 'buddypress' ) . '</li>' .
			'<li>' . __( 'You may choose to allow favorites, which are a way for users to save and later return to topics they favor. This is enabled by default.',                                            'buddypress' ) . '</li>' .
			'<li>' . __( 'You may choose to allow subscriptions, which allows users to subscribe for notifications to topics that interest them. This is enabled by default.',                                 'buddypress' ) . '</li>' .
			'<li>' . __( 'You may choose to allow "Anonymous Posting", which will allow guest users who do not have accounts on your site to both create topics as well as replies.',                          'buddypress' ) . '</li>' .
		'</ul>';

	$bp_contextual_help[] = __( 'Per Page settings allow you to control the number of topics and replies will appear on each of those pages. This is comparable to the WordPress "Reading Settings" page, where you can set the number of posts that should show on blog pages and in feeds.', 'buddypress' );
	$bp_contextual_help[] = __( 'The Forums section allows you to control the permalink structure for your forums. Each "base" is what will be displayed after your main URL and right before your permalink slug.', 'buddypress' );
	$bp_contextual_help[] = __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.', 'buddypress' );
	$bp_contextual_help[] = __( '<strong>For more information:</strong>', 'buddypress' );
	$bp_contextual_help[] =
		'<ul>' .
			'<li>' . __( '<a href="http://buddypress.org/documentation/">BuddyPress Documentation</a>', 'buddypress' ) . '</li>' .
			'<li>' . __( '<a href="http://buddypress.org/forums/">BuddyPress Support Forums</a>',       'buddypress' ) . '</li>' .
		'</ul>' ;

	// Empty the default $contextual_help var
	$contextual_help = '';

	// Wrap each help item in paragraph tags
	foreach( $bp_contextual_help as $paragraph )
		$contextual_help .= '<p>' . $paragraph . '</p>';

	// Add help
	add_contextual_help( 'settings_page_buddypress', $contextual_help );
}

/**
 * Output settings API option
 *
 * @since BuddyPress (r3203)
 *
 * @uses bp_get_bp_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function bp_form_option( $option, $default = '' , $slug = false ) {
	echo bp_get_form_option( $option, $default, $slug );
}
	/**
	 * Return settings API option
	 *
	 * @since BuddyPress (r3203)
	 *
	 * @uses bp_get_option()
	 * @uses esc_attr()
	 * @uses apply_filters()
	 *
	 * @param string $option
	 * @param string $default
	 * @param bool $slug
	 */
	function bp_get_form_option( $option, $default = '', $slug = false ) {

		// Get the option and sanitize it
		$value = bp_get_option( $option, $default );

		// Slug?
		if ( true === $slug )
			$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
		else
			$value = esc_attr( $value );

		// Fallback to default
		if ( empty( $value ) )
			$value = $default;

		// Allow plugins to further filter the output
		return apply_filters( 'bp_get_form_option', $value, $option );
	}

/**
 * Used to check if a BuddyPress slug conflicts with an existing known slug.
 *
 * @since BuddyPress (r3306)
 *
 * @param string $slug
 * @param string $default
 *
 * @uses bp_get_form_option() To get a sanitized slug string
 */
function bp_form_slug_conflict_check( $slug, $default ) {

	// Only set the slugs once ver page load
	static $the_core_slugs = array();

	// Get the form value
	$this_slug = bp_get_form_option( $slug, $default, true );

	if ( empty( $the_core_slugs ) ) {

		// Slugs to check
		$core_slugs = apply_filters( 'bp_slug_conflict_check', array(

			/** WordPress Core ****************************************************/

			// Core Post Types
			'post_base'       => array( 'name' => __( 'Posts'         ), 'default' => 'post',          'context' => 'WordPress' ),
			'page_base'       => array( 'name' => __( 'Pages'         ), 'default' => 'page',          'context' => 'WordPress' ),
			'revision_base'   => array( 'name' => __( 'Revisions'     ), 'default' => 'revision',      'context' => 'WordPress' ),
			'attachment_base' => array( 'name' => __( 'Attachments'   ), 'default' => 'attachment',    'context' => 'WordPress' ),
			'nav_menu_base'   => array( 'name' => __( 'Menus'         ), 'default' => 'nav_menu_item', 'context' => 'WordPress' ),

			// Post Tags
			'tag_base'        => array( 'name' => __( 'Tag base'      ), 'default' => 'tag',           'context' => 'WordPress' ),

			// Post Categories
			'category_base'   => array( 'name' => __( 'Category base' ), 'default' => 'category',      'context' => 'WordPress' ),

		) );

		/** bbPress Core ******************************************************/

		if ( defined( 'BBP_VERSION' ) ) {

			// Forum archive slug
			$core_slugs['_bbp_root_slug']          = array( 'name' => __( 'Forums base', 'buddypress' ), 'default' => 'forums', 'context' => 'buddypress' );

			// Topic archive slug
			$core_slugs['_bbp_topic_archive_slug'] = array( 'name' => __( 'Topics base', 'buddypress' ), 'default' => 'topics', 'context' => 'buddypress' );

			// Forum slug
			$core_slugs['_bbp_forum_slug']         = array( 'name' => __( 'Forum slug',  'buddypress' ), 'default' => 'forum',  'context' => 'buddypress' );

			// Topic slug
			$core_slugs['_bbp_topic_slug']         = array( 'name' => __( 'Topic slug',  'buddypress' ), 'default' => 'topic',  'context' => 'buddypress' );

			// Reply slug
			$core_slugs['_bbp_reply_slug']         = array( 'name' => __( 'Reply slug',  'buddypress' ), 'default' => 'reply',  'context' => 'buddypress' );

			// User profile slug
			$core_slugs['_bbp_user_slug']          = array( 'name' => __( 'User base',   'buddypress' ), 'default' => 'users',  'context' => 'buddypress' );

			// View slug
			$core_slugs['_bbp_view_slug']          = array( 'name' => __( 'View base',   'buddypress' ), 'default' => 'view',   'context' => 'buddypress' );

			// Topic tag slug
			$core_slugs['_bbp_topic_tag_slug']     = array( 'name' => __( 'Topic tag slug', 'buddypress' ), 'default' => 'topic-tag', 'context' => 'buddypress' );
		}

		/** BuddyPress Core *******************************************************/

		global $bp;

		// Loop through root slugs and check for conflict
		if ( !empty( $bp->pages ) ) {
			foreach ( $bp->pages as $page => $page_data ) {
				$page_base    = $page . '_base';
				$page_title   = sprintf( __( '%s page', 'buddypress' ), $page_data->title );
				$core_slugs[$page_base] = array( 'name' => $page_title, 'default' => $page_data->slug, 'context' => 'buddypress' );
			}
		}

		// Set the static
		$the_core_slugs = apply_filters( 'bp_slug_conflict', $core_slugs );
	}

	// Loop through slugs to check
	foreach( $the_core_slugs as $key => $value ) {

		// Get the slug
		$slug_check = bp_get_form_option( $key, $value['default'], true );

		// Compare
		if ( ( $slug != $key ) && ( $slug_check == $this_slug ) ) : ?>

			<span class="attention"><?php printf( __( 'Possible %1$s conflict: <strong>%2$s</strong>', 'buddypress' ), $value['context'], $value['name'] ); ?></span>

		<?php endif;
	}
}

?>
