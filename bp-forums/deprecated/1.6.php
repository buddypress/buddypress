<?php

/**
 * BuddyPress Forums Deprecated Functions
 *
 * This file contains all the deprecated functions for BuddyPress forums since
 * version 1.6. This was a major update for the forums component, moving from
 * bbPress 1.x to bbPress 2.x.
 *
 * @package BuddyPress
 * @subpackage Forums
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_forums_bbpress_admin() {

	$action = bp_get_admin_url( 'admin.php?page=bb-forums-setup&reinstall=1' ); ?>

	<div class="wrap">
		<?php screen_icon( 'buddypress' ); ?>

		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Forums', 'buddypress' ) ); ?></h2>

		<?php if ( isset( $_POST['submit'] ) ) : ?>

			<div id="message" class="updated fade">
				<p><?php _e( 'Settings Saved.', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>

		<?php

		if ( isset( $_REQUEST['reinstall'] ) || !bp_forums_is_installed_correctly() ) :

			// Delete the bb-config.php location option
			bp_delete_option( 'bb-config-location' );
			bp_forums_bbpress_install_wizard();

		else : ?>

			<div>
				<h3><?php _e( 'Forums for Groups', 'buddypress' ) ?></h3>

				<p><?php _e( 'Give each individual group its own discussion forum. Choose this if you\'d like to keep your members\' conversations separated into distinct areas.' , 'buddypress' ); ?></p>
				<p class="description"><?php _e( 'You may use an existing bbPress installation if you have one.', 'buddypress' ); ?></p>

				<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
				<ul class="description" style="list-style: square; margin-left: 30px;">
					<li><?php _e( 'Group Integration',           'buddypress' ); ?></p></li>
					<li><?php _e( 'Member Profile Integration',  'buddypress' ); ?></p></li>
					<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
					<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
				</ul>

				<div>
					<a class="button button-primary" href="<?php echo $action ?>"><?php _e( 'Reinstall Group Forums', 'buddypress' ) ?></a> &nbsp;
				</div>
			</div>

		<?php endif; ?>

	</div>
<?php
}

function bp_forums_bbpress_install_wizard() {
	$post_url = bp_get_admin_url( 'admin.php?page=bb-forums-setup' );

	$step = isset( $_REQUEST['step'] ) ? $_REQUEST['step'] : '';

	switch( $step ) {
		case 'existing':
			if ( isset( $_REQUEST['doinstall'] ) && ( 1 == (int) $_REQUEST['doinstall'] ) ) {
				if ( !bp_forums_configure_existing_install() ) {
					_e( 'The bb-config.php file was not found at that location, please try again.', 'buddypress' );
				} else {
					?>
					<h3><?php _e( 'Forums were set up correctly using your existing bbPress install!', 'buddypress' ) ?></h3>
					<p><?php _e( 'BuddyPress will now use its internal copy of bbPress to run the forums on your site. If you wish, you can remove your old bbPress installation files, as long as you keep the bb-config.php file in the same location.', 'buddypress' ) ?></p><?php
				}
			} else { ?>

					<form action="" method="post">
						<h3><?php _e( 'Existing bbPress Installation', 'buddypress' ) ?></h3>
						<p><?php _e( "BuddyPress can make use of your existing bbPress install. Just provide the location of your <code>bb-config.php</code> file, and BuddyPress will do the rest.", 'buddypress' ) ?></p>
						<p><label><code>bb-config.php</code> file location:</label><br /><input style="width: 50%" type="text" name="bbconfigloc" id="bbconfigloc" value="<?php echo str_replace( 'buddypress', '', $_SERVER['DOCUMENT_ROOT'] ) ?>" /></p>
						<p><input type="submit" class="button-primary" value="<?php _e( 'Complete Installation', 'buddypress' ) ?>" /></p>
						<input type="hidden" name="step" value="existing" />
						<input type="hidden" name="doinstall" value="1" />
						<?php wp_nonce_field( 'bp_forums_existing_install_init' ) ?>
					</form>

				<?php
			}
		break;

		case 'new':
			if ( isset( $_REQUEST['doinstall'] ) && 1 == (int) $_REQUEST['doinstall'] ) {
				$result = bp_forums_bbpress_install();

				switch ( $result ) {
					case 1:
						_e( '<p>All done! Configuration settings have been saved to the file <code>bb-config.php</code> in the root of your WordPress install.</p>', 'buddypress' );
						break;
					default:
						// Just write the contents to screen
						_e( '<p>A configuration file could not be created. No problem, but you will need to save the text shown below into a file named <code>bb-config.php</code> in the root directory of your WordPress installation before you can start using the forum functionality.</p>', 'buddypress' ); ?>

						<textarea style="display:block; margin-top: 30px; width: 80%;" rows="50"><?php echo htmlspecialchars( $result ); ?></textarea>

					<?php
						break;
				}
			} else { ?>

				<h3><?php _e( 'New bbPress Installation', 'buddypress' ) ?></h3>
				<p><?php _e( "You've decided to set up a new installation of bbPress for forum management in BuddyPress. This is very simple and is usually just a one click
				process. When you're ready, hit the link below.", 'buddypress' ) ?></p>
				<p><a class="button-primary" href="<?php echo wp_nonce_url( $post_url . '&step=new&doinstall=1', 'bp_forums_new_install_init' ) ?>"><?php _e( 'Complete Installation', 'buddypress' ) ?></a></p>

				<?php
			}
		break;

		default:
			if ( !file_exists( BP_PLUGIN_DIR . '/bp-forums/bbpress/' ) ) { ?>

				<div id="message" class="error">
					<p><?php printf( __( 'bbPress files were not found. To install the forums component you must download a copy of bbPress and make sure it is in the folder: "%s"', 'buddypress' ), 'wp-content/plugins/buddypress/bp-forums/bbpress/' ) ?></p>
				</div>

			<?php } else {

				// Include the plugin install

				add_thickbox();
				wp_enqueue_script( 'plugin-install' );
				wp_admin_css( 'plugin-install' );
			?>

				<div>
					<h3><?php _e( 'Forums for Groups', 'buddypress' ) ?></h3>

					<p><?php _e( 'Give each individual group its own discussion forum. Choose this if you\'d like to keep your members\' conversations separated into distinct areas.' , 'buddypress' ); ?></p>
					<p class="description"><?php _e( 'You may use an existing bbPress installation if you have one.', 'buddypress' ); ?></p>

					<h4 style="margin-bottom: 10px;"><?php _e( 'Features', 'buddypress' ); ?></h4>
					<ul class="description" style="list-style: square; margin-left: 30px;">
						<li><?php _e( 'Group Integration',           'buddypress' ); ?></p></li>
						<li><?php _e( 'Member Profile Integration',  'buddypress' ); ?></p></li>
						<li><?php _e( 'Activity Stream Integration', 'buddypress' ); ?></p></li>
						<li><?php _e( '@ Mention Integration',       'buddypress' ); ?></p></li>
					</ul>

					<div>
						<a class="button button-primary" href="<?php echo $post_url . '&step=new' ?>"><?php _e( 'Install Group Forums', 'buddypress' ) ?></a> &nbsp;
						<a class="button" href="<?php echo $post_url . '&step=existing' ?>"><?php _e( 'Use Existing Installation', 'buddypress' ) ?></a>
					</div>
				</div>

			<?php }
		break;
	}
}
