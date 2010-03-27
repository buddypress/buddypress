<?php

require_once( dirname( dirname( __FILE__ ) ) . '/bp-core-wpabstraction.php' );

if ( function_exists( 'register_theme_directory') )
	register_theme_directory( WP_PLUGIN_DIR . '/buddypress/bp-themes' );

class BP_Core_Setup_Wizard {
	var $current_step;
	var $steps;

	var $current_version;
	var $new_version;
	var $setup_type;

	function bp_core_setup_wizard() {
		$this->current_version = get_site_option( 'bp-db-version' );
		$this->new_version = constant( 'BP_DB_VERSION' );
		$this->setup_type = ( empty( $this->current_version ) && !(int)get_site_option( 'bp-core-db-version' ) ) ? 'new' : 'upgrade';
		$this->current_step = $this->current_step();

		/* Call the save method that will save data and modify $current_step */
		if ( isset( $_POST['save'] ) )
			$this->save( $_POST['save'] );

		/* Build the steps needed for upgrade or new installations */
		$this->steps = $this->add_steps();
	}

	function current_step() {
		if ( isset( $_POST['step'] ) ) {
			$current_step = (int)$_POST['step'] + 1;
		} else {
			if ( !empty( $_COOKIE['bp-wizard-step'] ) )
				$current_step = $_COOKIE['bp-wizard-step'];
			else
				$current_step = 0;
		}

		return $current_step;
	}

	function add_steps() {
		global $wp_rewrite;

		if ( 'new' == $this->setup_type ) {
			/* Setup wizard steps */
			$steps = array(
				__( 'Components', 'buddypress' ),
				__( 'Pages', 'buddypress' ),
				__( 'Permalinks', 'buddypress' ),
				__( 'Theme', 'buddypress' ),
				__( 'Finish', 'buddypress' )
			);

			require_once ( ABSPATH . '/wp-admin/includes/file.php' );

			$home_path = get_home_path();
			if ( !empty( $wp_rewrite->permalink_structure ) && ( file_exists( $home_path . '.htaccess' ) || file_exists( $home_path . 'web.config' ) ) ) {
				unset( $steps[2] );
				$steps = array_merge( array(), $steps );
			}
		} else {
			/* Upgrade wizard steps */
			$steps[] = __( 'Database Upgrade', 'buddypress' );

			if ( $this->current_version < 1225 )
				$steps[] = __( 'Pages', 'buddypress' );

			$steps[] = __( 'Finish', 'buddypress' );
		}

		return $steps;
	}

	function save( $step_name ) {
		/* Save any posted values */
		switch ( $step_name ) {
			case 'db_upgrade': default:
				$result = $this->step_db_upgrade_save();
				break;
			case 'components': default:
				$result = $this->step_components_save();
				break;
			case 'pages': default:
				$result = $this->step_pages_save();
				break;
			case 'permalinks': default:
				$result = $this->step_permalinks_save();
				break;
			case 'theme': default:
				$result = $this->step_theme_save();
				break;
			case 'finish': default:
				$result = $this->step_finish_save();
				break;
		}

		if ( !$result && $this->current_step )
			$this->current_step--;

		if ( 'finish' != $step_name )
			setcookie( 'bp-wizard-step', (int)$this->current_step, time() + 60 * 60 * 24, COOKIEPATH );
	}

	function html() { ?>
		<div class="wrap" id="bp-admin">

			<div id="bp-admin-header">
				<h3><?php _e( 'BuddyPress', 'buddypress' ) ?></h3>
				<h4>
					<?php if ( 'upgrade' == $this->setup_type ) : ?>
						<?php _e( 'Upgrade', 'buddypress' ) ?>
					<?php else : ?>
						<?php _e( 'Setup', 'buddypress' ) ?>
					<?php endif; ?>
				</h4>
			</div>

			<div id="bp-admin-nav">
				<ol>
					<?php foreach( (array)$this->steps as $i => $name ) : ?>
						<li<?php if ( $this->current_step == $i ) : ?> class="current"<?php endif; ?>>
							<?php if ( $this->current_step > $i ) : ?>
								<span class="complete">&nbsp;</span>
							<?php else : ?>
								<?php echo $i + 1 . '. ' ?>
							<?php endif; ?>
							<?php echo esc_attr( $name ) ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<?php do_action( 'bp_admin_notices' ) ?>

			<form action="<?php echo site_url( '/wp-admin/admin.php?page=bp-wizard' ) ?>" method="post" id="bp-admin-form">
				<div id="bp-admin-content">
					<?php switch ( $this->steps[$this->current_step] ) {
						case __( 'Database Upgrade', 'buddypress'):
							$this->step_db_upgrade();
							break;
						case __( 'Components', 'buddypress'):
							$this->step_components();
							break;
						case __( 'Pages', 'buddypress'):
							$this->step_pages();
							break;
						case __( 'Permalinks', 'buddypress'):
							$this->step_permalinks();
							break;
						case __( 'Theme', 'buddypress'):
							$this->step_theme();
							break;
						case __( 'Finish', 'buddypress'):
							$this->step_finish();
							break;
					} ?>
				</div>
			</form>

		</div>
	<?php
	}

	/* Setup Step HTML */

	function step_db_upgrade() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;
	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Upgrade &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<p><?php _e( 'BuddyPress has been updated! Before you can continue using BuddyPress, we have to upgrade your database to the newest version.', 'buddypress' ); ?></p>

		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Upgrade &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="db_upgrade" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_db_upgrade' ) ?>
		</div>
	<?php
	}

	function step_components() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		$disabled_components = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );
	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<p><?php _e( "BuddyPress is made up of a number of individual components, each one adding a distinct feature. The first step is to decide which of these features you'd like to enable on your site. All features are enabled by default, and don't worry, you can change your mind at any point in the future.", 'buddypress' ) ?></p>

		<div class="left-col">

			<div class="component">
				<h5><?php _e( "Activity Streams", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-activity.php]" value="1"<?php if ( !isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-activity.php]" value="0"<?php if ( isset( $disabled_components['bp-activity.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-1.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Global, personal and group activity streams with threaded commenting, direct posting, favoriting and @mentions. All with full RSS feed and email notification support.", 'buddypress' ) ?></p>
			</div>

			<div class="component">
				<h5><?php _e( "Extensible Groups", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-groups.php]" value="1"<?php if ( !isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-groups.php]" value="0"<?php if ( isset( $disabled_components['bp-groups.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-3.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Powerful public, private or hidden groups allow your users to break the discussion down into specific topics with a separate activity stream and member listing.", 'buddypress' ) ?></p>
			</div>

			<div class="component">
				<h5><?php _e( "Private Messaging", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-messages.php]" value="1"<?php if ( !isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-messages.php]" value="0"<?php if ( isset( $disabled_components['bp-messages.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-5.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Private messaging will allow your users to talk to each other directly, and in private. Not just limited to one on one discussions, your users can send messages to multiple recipients.", 'buddypress' ) ?></p>
			</div>

			<div class="component">
				<h5><?php _e( "Blog Tracking", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-blogs.php]" value="1"<?php if ( !isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-blogs.php]" value="0"<?php if ( isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-7.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Track new blogs, new posts and new comments across your entire blog network.", 'buddypress' ) ?></p>
			</div>
		</div>

		<div class="right-col">

			<div class="component">
				<h5><?php _e( "Extended Profiles", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-xprofile.php]" value="1"<?php if ( !isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-xprofile.php]" value="0"<?php if ( isset( $disabled_components['bp-xprofile.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-2.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Fully editable profile fields allow you to define the fields users can fill in to describe themselves. Tailor profile fields to suit your audience.", 'buddypress' ) ?></p>
			</div>

			<div class="component">
				<h5><?php _e( "Friend Connections", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-friends.php]" value="1"<?php if ( !isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-friends.php]" value="0"<?php if ( isset( $disabled_components['bp-friends.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-4.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Let your users make connections so they can track the activity of others, or filter on only those users they care about the most.", 'buddypress' ) ?></p>
			</div>

			<div class="component">
				<h5><?php _e( "Discussion Forums", 'buddypress' ) ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-forums.php]" value="1"<?php if ( !isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-forums.php]" value="0"<?php if ( isset( $disabled_components['bp-forums.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-6.gif' ) ?>" alt="Activity Streams" />
				<p><?php _e( "Full powered discussion forums built directly into groups allow for more conventional in-depth conversations. <strong>NOTE: This will require an extra (but easy) setup step.</strong>", 'buddypress' ) ?></p>
			</div>

		</div>

		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="components" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_components' )?>
		</div>
	<?php
	}

	function step_pages() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		$existing_pages = get_site_option( 'bp-pages' );
		$disabled_components = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

		/* Check for defined slugs */
		if ( defined( 'BP_MEMBERS_SLUG' ) )
			$members_slug = constant( 'BP_MEMBERS_SLUG' );
		else
			$members_slug = __( 'members', 'buddypress' );

		if ( defined( 'BP_GROUPS_SLUG' ) )
			$groups_slug = constant( 'BP_GROUPS_SLUG' );
		else
			$groups_slug = __( 'groups', 'buddypress' );

		if ( defined( 'BP_ACTIVITY_SLUG' ) )
			$activity_slug = constant( 'BP_ACTIVITY_SLUG' );
		else
			$activity_slug = __( 'activity', 'buddypress' );

		if ( defined( 'BP_FORUMS_SLUG' ) )
			$forums_slug = constant( 'BP_FORUMS_SLUG' );
		else
			$forums_slug = __( 'forums', 'buddypress' );

		if ( defined( 'BP_BLOGS_SLUG' ) )
			$blogs_slug = constant( 'BP_BLOGS_SLUG' );
		else
			$blogs_slug = __( 'blogs', 'buddypress' );

		if ( defined( 'BP_REGISTER_SLUG' ) )
			$register_slug = constant( 'BP_REGISTER_SLUG' );
		else
			$register_slug = __( 'register', 'buddypress' );

		if ( defined( 'BP_ACTIVATION_SLUG' ) )
			$activation_slug = constant( 'BP_ACTIVATION_SLUG' );
		else
			$activation_slug = __( 'activate', 'buddypress' );

	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<?php if ( 'new' == $this->setup_type ) : ?>
			<p><?php _e( "BuddyPress needs to use pages to display content and directories.", 'buddypress' ) ?></p>
		<?php else : ?>
			<p><?php _e( "New versions of BuddyPress use WordPress pages to display content. This allows you to easily change the names of pages or move them to a sub page.", 'buddypress' ) ?></p>
		<?php endif; ?>

		<p><?php _e( "Please select the WordPress pages you would like to use to display these. You can either choose an existing page or let BuddyPress auto-create pages for you. If you'd like to manually create pages, please go ahead and do that now, you can come back to this step once you are finished.", 'buddypress' ) ?></p>

		<p><strong><?php _e( 'Please Note:', 'buddypress' ) ?></strong> <?php _e( "If you have manually added BuddyPress navigation links in your theme you may need to remove these from your header.php to avoid duplicate links.", 'buddypress' ) ?></p>

		<table class="form-table">

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Members', 'buddypres' ) ?></h5>
					<p><?php _e( 'Displays member profiles, and a directory of all site members.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[members]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-members-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['members'] ) ?></p>
					<p><input type="radio" name="bp_pages[members]" checked="checked" value="<?php echo $members_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $members_slug ) ?>/</p>
				</td>
			</tr>

			<?php if ( !isset( $disabled_components['bp-activity.php'] ) ) : ?>
			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Site Activity', 'buddypress' ) ?></h5>
					<p><?php _e( "Displays the activity for the entire site, a member's friends, groups and @mentions.", 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[activity]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-activity-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['activity'] ) ?></p>
					<p><input type="radio" name="bp_pages[activity]" checked="checked" value="<?php echo $activity_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $activity_slug ) ?>/</p>
				</td>
			</tr>
			<?php endif; ?>

			<?php if ( !isset( $disabled_components['bp-groups.php'] ) ) : ?>
			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Groups', 'buddypress' ) ?></h5>
					<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[groups]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-groups-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['groups'] ) ?></p>
					<p><input type="radio" name="bp_pages[groups]" checked="checked" value="<?php echo $groups_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $groups_slug ) ?>/</p>
				</td>
			</tr>
			<?php endif; ?>

			<?php if ( !isset( $disabled_components['bp-forums.php'] ) ) : ?>
			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Forums', 'buddypress' ) ?></h5>
					<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[forums]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-forums-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['forums'] ) ?></p>
					<p><input type="radio" name="bp_pages[forums]" checked="checked" value="<?php echo $forums_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $forums_slug ) ?>/</p>
				</td>
			</tr>
			<?php endif; ?>

			<?php if ( bp_core_is_multisite() && !isset( $disabled_components['bp-blogs.php'] ) ) : ?>
			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Blogs', 'buddypress' ) ?></h5>
					<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[blogs]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-blogs-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['blogs'] ) ?></p>
					<p><input type="radio" name="bp_pages[blogs]" checked="checked" value="<?php echo $blogs_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $blogs_slug ) ?>/</p>
				</td>
			</tr>
			<?php endif; ?>

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Register', 'buddypress' ) ?></h5>
					<p><?php _e( 'Displays a site registration page where users can create new accounts.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[register]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-register-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['register'] ) ?></p>
					<p><input type="radio" name="bp_pages[register]" checked="checked" value="<?php echo $register_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $register_slug ) ?>/</p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Activate', 'buddypress' ) ?></h5>
					<p><?php _e( 'The page users will visit to activate their account once they have registered.', 'buddypress' ) ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[activate]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages("name=bp-activate-page&echo=0&show_option_none=".__('- Select -')."&selected=" . $existing_pages['activate'] ) ?></p>
					<p><input type="radio" name="bp_pages[activate]" checked="checked" value="<?php echo $activation_slug ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo site_url( $activation_slug ) ?>/</p>
				</td>
			</tr>
		</table>

		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="pages" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_pages' )?>
		</div>

		<script type="text/javascript">
			jQuery('select').click( function() {
				jQuery(this).parent().children('input').attr( 'checked', 'checked' );
			});
		</script>
	<?php
	}

	function step_permalinks() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		$prefix = '';
		$permalink_structure = get_option('permalink_structure');
		$structures = array( '', $prefix . '/%year%/%monthnum%/%day%/%postname%/', $prefix . '/%year%/%monthnum%/%postname%/', $prefix . '/archives/%post_id%' );

		if ( !got_mod_rewrite() && !iis7_supports_permalinks() )
			$prefix = '/index.php';
	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<p><?php _e( "To make sure the pages we created in the previous step work correctly, you will need to enable permalink support on your site.", 'buddypress' ) ?></p>
		<p><?php printf( __( 'Below are the basic permalink options, please select which permalink setting you would like to use. If you\'d like more advanced options please visit the <a href="%s">permalink settings page</a> then return to complete this setup wizard.', 'buddypress' ), site_url( '/wp-admin/options-permalink.php' ) ) ?>

		<table class="form-table">
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[1] ) ) : ?> checked="checked" <?php endif; ?>value="<?php echo esc_attr( $structures[1] ); ?>" class="tog" <?php checked($structures[1], $permalink_structure); ?> />&nbsp;<?php _e('Day and name'); ?></label></th>
				<td><code><?php echo get_option('home') . $prefix . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/sample-post/'; ?></code></td>
			</tr>
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[2] ) ) : ?> checked="checked" <?php endif; ?> value="<?php echo esc_attr( $structures[2] ); ?>" class="tog" <?php checked($structures[2], $permalink_structure); ?> />&nbsp;<?php _e('Month and name'); ?></label></th>
				<td><code><?php echo get_option('home') . $prefix . '/' . date('Y') . '/' . date('m') . '/sample-post/'; ?></code></td>
			</tr>
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[3] ) ) : ?> checked="checked" <?php endif; ?> value="<?php echo esc_attr( $structures[3] ); ?>" class="tog" <?php checked($structures[3], $permalink_structure); ?> />&nbsp;<?php _e('Numeric'); ?></label></th>
				<td><code><?php echo get_option('home') . $prefix ?>/archives/123</code></td>
			</tr>
		</table>

		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="permalinks" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_permalinks' ) ?>
		</div>
	<?php
	}

	function step_theme() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		require_once( ABSPATH . WPINC . '/plugin.php' );
		$installed_plugins = get_plugins();
		$installed_themes = get_themes();

		$template_pack_installed = false;
		$bp_autotheme_installed = false;
		$bp_theme_installed = false;

		foreach ( (array)$installed_plugins as $plugin ) {
			if ( 'BuddyPress Template Pack' == $plugin['Name'] )
				$template_pack_installed = true;
		}

		foreach ( (array)$installed_themes as $theme ) {
			foreach ( (array)$theme['Tags'] as $tag ) {
				if ( 'BuddyPress Default' != $theme['Name'] && 'buddypress' == $tag ) {
					$bp_theme_installed = true;
					$bp_themes[] = $theme;
				}
			}
		}
	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<p><?php _e( "BuddyPress introduces a whole range of new screens to display content. To display these screens you will need to decide how you want to handle them in your active theme. There are a few different options, please choose the option that best suits your demands and needs.", 'buddypress' ) ?></p>

		<table class="form-table">
			<tr>
				<th>
					<h5><?php _e( 'Use the Default Theme', 'buddypress' ) ?></h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/default.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( 'The default theme contains everything you need to get up and running out of the box. It supports all features and is highly customizable.', 'buddypress' ) ?></p>
					<p><strong><?php _e( 'This is the best choice if you do not have an existing WordPress theme or want to create a child theme from a solid starting point.', 'buddypress' ) ?></strong></p>
					<p><label><input type="radio" name="theme" value="bp_default" checked="checked" /> <?php _e( 'Choose this option', 'buddypress' ) ?></label></p>
				</td>
			</tr>
			<?php /*
			<tr>
				<th>
					<h5>Automatically Upgrade My WordPress Theme</h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/auto_theme.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p>The BuddyPress [plugin name] plugin will automatically upgrade your existing WordPress theme so it can display BuddyPress pages. Your existing theme's page.php template file will be used to show BuddyPress content.</p>
					<p><strong>This is the best choice if you have an existing WordPress theme and simply want to start using BuddyPress features without control of template layout and design.</strong></p>
					<p><label><input type="radio" name="theme" value="auto_wp" disabled="disabled" /> You must first install the [plugin name] before choosing this option</label></p>
					<p><a id="bp-plugin-name" class="thickbox onclick button" href="http://buddypressorg.dev/wp-admin/plugin-install.php?tab=plugin-information&plugin=bp-template-pack&TB_iframe=true&width=640&height=500">+ Install Now</a></p>
				</td>
			</tr>
			*/ ?>
			<tr>
				<th>
					<h5><?php _e( 'Manually Upgrade My WordPress Theme', 'buddypress' ) ?>'</h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/manual_theme.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( 'The BuddyPress template pack plugin will run you through the process of manually upgrading your existing WordPress theme. This usually involves following the step by step instructions and copying the BuddyPress template files into your theme then tweaking the HTML to match.', 'buddypress' ) ?></p>
					<p><strong><?php _e( 'This is the best choice if you have an existing WordPress theme and want complete control over template layout and design.', 'buddypress' ) ?></strong></p>

					<?php if ( !$template_pack_installed ) : ?>
						<p><label><input type="radio" name="theme" value="manual_wp" disabled="disabled" /> <?php _e( 'You must first install the BuddyPress template pack before choosing this option', 'buddypress' ) ?></label></p>
						<p><a id="bp-template-pack" class="thickbox onclick button" href="http://buddypressorg.dev/wp-admin/plugin-install.php?tab=plugin-information&plugin=bp-template-pack&TB_iframe=true&width=640&height=500">+ <?php _e( 'Install Now', 'buddypress' ) ?></a></p>
					<?php else : ?>
						<p><label><input type="radio" name="theme" value="manual_wp" /> <?php _e( 'Choose this option (go to Appearance &rarr; BP Compatibility after setup is complete)', 'buddypress' ) ?></label></p>
						<p><a id="bp-template-pack" class="button installed disabled" href="javascript:void();"><span></span><?php _e( 'Plugin Installed', 'buddypress' ) ?></a></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>
					<h5><?php _e( 'Find a BuddyPress Theme', 'buddypress' ) ?></h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/find.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( "There's growing number of BuddyPress themes available for you to download and use. Browse through the list of available themes to see if there is one that matches your needs.", 'buddypress' ) ?></p>
					<p><strong><?php _e( 'This is the best choice if want to use a theme other than the default and get started straight out of the box.', 'buddypress' ) ?></strong></p>

					<?php if ( !$bp_theme_installed ) : ?>
						<p><label><input type="radio" name="theme" value="third_party" disabled="disabled" /> <?php _e( 'You must first install at least one BuddyPress theme before choosing this option', 'buddypress' ) ?></label></p>
						<p><a id="bp-themes" class="thickbox onclick button" href="<?php echo admin_url( 'theme-install.php?type=tag&s=buddypress&tab=search' ) ?>&TB_iframe=true&width=860&height=500">+ <?php _e( 'Add Themes', 'buddypress' ) ?></a></p>
					<?php else : ?>
						<p><label>
								<input type="radio" name="theme" value="3rd_party" /> <?php _e( 'Choose this option and use the theme:', 'buddypress' ) ?>
							</label>
							<select name="3rd_party_theme">
								<?php foreach( (array)$bp_themes as $theme ) :?>
									<option value="<?php echo $theme['Template'] . ',' . $theme['Stylesheet'] ?>"><?php echo $theme['Name'] ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="theme" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_theme' ) ?>
		</div>

		<script type="text/javascript">
			jQuery('select').click( function() {
				jQuery(this).parent().children('input').attr( 'checked', 'checked' );
			});
		</script>
	<?php
	}

	function step_finish() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;
	?>
		<div class="prev-next submit clear">
			<p><input type="submit" value="<?php _e( 'Finish &amp; Activate &rarr;', 'buddypress' ) ?>" name="submit" /></p>
		</div>

		<?php if ( 'new' == $this->setup_type ) :
			$type = __( 'setup', 'buddypress' );
		?>
			<h2>Setup Complete!</h2>
		<?php else :
			$type = __( 'upgrade', 'buddypress' );
		?>
			<h2>Upgrade Complete!</h2>
		<?php endif; ?>

		<?php?>
		<p><?php printf( __( "You've now completed all of the %s steps and BuddyPress is ready to be activated. Please hit the 'Finish &amp; Activate' button to complete the %s procedure.", 'buddypress' ), $type, $type ) ?></p>


		<div class="submit clear">
			<p><input type="submit" value="<?php _e( 'Finish &amp; Activate &rarr;', 'buddypress' ) ?>" name="submit" /></p>

			<input type="hidden" name="save" value="finish" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />
			<?php wp_nonce_field( 'bpwizard_finish' ) ?>
		</div>

		<p>[TODO: A selection of the best BuddyPress plugins will appear here.]</p>

	<?php
	}

	/* Save Step Methods */

	function step_db_upgrade_save() {
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_db_upgrade' );

			if ( $this->current_version < 1225 )
				$this->upgrade_1_3();

			return true;
		}

		return false;
	}

	function step_components_save() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['bp_components'] ) ) {
			check_admin_referer( 'bpwizard_components' );

			// Settings form submitted, now save the settings.
			foreach ( (array)$_POST['bp_components'] as $key => $value ) {
				if ( !(int) $value )
					$disabled[$key] = 1;
			}
			update_site_option( 'bp-deactivated-components', $disabled );

			wp_cache_flush();
			bp_core_install( $disabled );

			return true;
		}

		return false;
	}

	function step_pages_save() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['bp_pages'] ) ) {
			check_admin_referer( 'bpwizard_pages' );

			/* Delete any existing pages */
			$existing_pages = get_site_option( 'bp-pages' );

			foreach ( (array)$existing_pages as $page_id )
				wp_delete_post( $page_id, true );

			// Settings form submitted, now save the settings.
			foreach ( (array)$_POST['bp_pages'] as $key => $value ) {
				if ( 'page' == $value ) {
					/* Check for the selected page */
					if ( !empty( $_POST['bp-' . $key . '-page'] ) )
						$bp_pages[$key] = (int)$_POST['bp-' . $key . '-page'];
					else
						$bp_pages[$key] = wp_insert_post( array( 'post_title' => ucwords( $key ), 'post_status' => 'publish', 'post_type' => 'page' ) );
				} else {
					/* Create a new page */
					$bp_pages[$key] = wp_insert_post( array( 'post_title' => ucwords( $value ), 'post_status' => 'publish', 'post_type' => 'page' ) );
				}
			}
			update_site_option( 'bp-pages', $bp_pages );

			return true;
		}

		return false;
	}

	function step_permalinks_save() {
		global $wp_rewrite;

		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_permalinks' );

			$home_path = get_home_path();
			$iis7_permalinks = iis7_supports_permalinks();

			if ( isset( $_POST['permalink_structure'] ) ) {
				$permalink_structure = $_POST['permalink_structure'];

				if ( !empty($permalink_structure) )
					$permalink_structure = preg_replace( '#/+#', '/', '/' . $_POST['permalink_structure'] );
				if ( ( defined( 'VHOST' ) && constant( 'VHOST' ) == 'no' ) && $permalink_structure != '' && $current_site->domain.$current_site->path == $current_blog->domain.$current_blog->path )
					$permalink_structure = '/blog' . $permalink_structure;

				$wp_rewrite->set_permalink_structure( $permalink_structure );
			}

			$writable = false;
			if ( $iis7_permalinks ) {
				if ( ( !file_exists( $home_path . 'web.config' ) && win_is_writable( $home_path ) ) || win_is_writable( $home_path . 'web.config' ) )
					$writable = true;
			} else {
				if ( ( !file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) ) || is_writable( $home_path . '.htaccess' ) )
					$writable = true;
			}

			$usingpi = false;
			if ( $wp_rewrite->using_index_permalinks() )
				$usingpi = true;

			$wp_rewrite->flush_rules();

			if ( $iis7_permalinks || ( !$usingpi && !$writable ) ) {
				function _bp_core_wizard_step_permalinks_message() {
					global $wp_rewrite;

					?><div id="message" class="updated fade"><p>
					<?php
						_e( 'Oops, there was a problem creating a configuration file. ', 'buddypress' );

						if ( $iis7_permalinks ) {
							if ( $permalink_structure && ! $usingpi && ! $writable ) {
								_e( 'If your <code>web.config</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so this is the url rewrite rule you should have in your <code>web.config</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all. Then insert this rule inside of the <code>/&lt;configuration&gt;/&lt;system.webServer&gt;/&lt;rewrite&gt;/&lt;rules&gt;</code> element in <code>web.config</code> file.' )
								?><br /><br /><textarea rows="9" class="large-text readonly" style="background: #fff;" name="rules" id="rules" readonly="readonly"><?php echo esc_html($wp_rewrite->iis7_url_rewrite_rules()); ?></textarea></p><?php
							} else if ( $permalink_structure && ! $usingpi && $writable )
								_e( 'Permalink structure updated. Remove write access on web.config file now!' );
						} else {
							_e( 'If your <code>.htaccess</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.' );
							?><br /><br /><textarea rows="6" class="large-text readonly" style="background: #fff;" name="rules" id="rules" readonly="readonly"><?php echo esc_html($wp_rewrite->mod_rewrite_rules()); ?></textarea><?php
						}
					?>
					<br /><br />
					<?php
						if ( empty( $iis7_permalinks ) )
							_e( 'Paste all these rules into a new <code>.htaccess</code> file in the root of your WordPress installation and save the file. Once you\'re done, please hit the "Save and Next" button to continue.', 'buddypress' );
					?>
					</p></div><?php
				}
				add_action( 'bp_admin_notices', '_bp_core_wizard_step_permalinks_message' );

				return false;
			}

			return true;
		}

		return false;
	}

	function step_theme_save() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['theme'] ) ) {
			check_admin_referer( 'bpwizard_theme' );

			require_once( ABSPATH . WPINC . '/plugin.php' );
			$installed_plugins = get_plugins();

			switch ( $_POST['theme'] ) {
				case 'bp_default':
					/* Activate the bp-default theme */
					switch_theme( 'bp-default', 'bp-default' );
					break;

				case 'manual_wp':
					foreach ( $installed_plugins as $key => $plugin ) {
						if ( 'BuddyPress Template Pack' == $plugin['Name'] )
							activate_plugin( $key );
					}
					break;

				case '3rd_party':
					if ( empty( $_POST['3rd_party_theme'] ) )
						return false;

					$theme = explode( ',', $_POST['3rd_party_theme'] );
					switch_theme( $theme[0], $theme[1] );
					break;

				default:
					return false;
					break;
			}

			return true;
		}

		return false;
	}

	function step_finish_save() {
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_finish' );

			/* Update the DB version in the database */
			update_site_option( 'bp-db-version', constant( 'BP_DB_VERSION' ) );
			delete_site_option( 'bp-core-db-version' );

			/* Delete the setup cookie */
			@setcookie( 'bp-wizard-step', '', time() - 3600, COOKIEPATH );

			/* Redirect to the BuddyPress dashboard */
			wp_redirect( site_url( 'wp-admin/admin.php?page=bp-general-settings' ) );

			return true;
		}

		return false;
	}

	/* Database upgrade methods based on version numbers */
	function upgrade_1_3() {
		/* Run the schema install to upgrade tables */
		bp_core_install();

		/* Delete old database version options */
		delete_site_option( 'bp-activity-db-version' );
		delete_site_option( 'bp-blogs-db-version' );
		delete_site_option( 'bp-friends-db-version' );
		delete_site_option( 'bp-groups-db-version' );
		delete_site_option( 'bp-messages-db-version' );
		delete_site_option( 'bp-xprofile-db-version' );
	}
}

function bp_core_setup_wizard_init() {
	global $bp_wizard;

	$bp_wizard = new BP_Core_Setup_Wizard;
}
add_action( 'admin_menu', 'bp_core_setup_wizard_init' );

function bp_core_install( $disabled = false ) {
	global $wpdb;

	if ( empty( $disabled ) )
		$disabled = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

	require_once( dirname( __FILE__ ) . '/bp-core-schema.php' );

	/* Core DB Tables */
	bp_core_install_notifications();

	/* Activity Streams */
	if ( empty( $disabled['bp-activity.php'] ) )
		bp_core_install_activity_streams();

	/* Friend Connections */
	if ( empty( $disabled['bp-friends.php'] ) )
		bp_core_install_friends();

	/* Extensible Groups */
	if ( empty( $disabled['bp-groups.php'] ) )
		bp_core_install_groups();

	/* Private Messaging */
	if ( empty( $disabled['bp-messages.php'] ) )
		bp_core_install_private_messaging();

	/* Extended Profiles */
	if ( empty( $disabled['bp-xprofile.php'] ) )
		bp_core_install_extended_profiles();

	/* Only install blog tables if this is a multisite installation */
	if ( bp_core_is_multisite() && empty( $disabled['bp-blogs.php'] ) )
		bp_core_install_blog_tracking();
}

function bp_core_upgrade( $disabled ) {
	global $wpdb;

	require_once( dirname( __FILE__ ) . '/bp-core-schema.php' );
}

function bp_upgrade_db_stuff() {
	/* Rename the old user activity cached table if needed. */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$wpdb->base_prefix}bp_activity_user_activity_cached%'" ) )
		$wpdb->query( "RENAME TABLE {$wpdb->base_prefix}bp_activity_user_activity_cached TO {$bp->activity->table_name}" );

	/* Rename fields from pre BP 1.2 */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$bp->activity->table_name}%'" ) ) {
		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_action'" ) )
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_action type varchar(75) NOT NULL" );

		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_name'" ) )
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_name component varchar(75) NOT NULL" );
	}

	// On first installation - record all existing blogs in the system.
	if ( !(int)$bp->site_options['bp-blogs-first-install'] && bp_core_is_multisite() ) {
		bp_blogs_record_existing_blogs();
		add_site_option( 'bp-blogs-first-install', 1 );
	}

	if ( bp_core_is_multisite() )
		bp_core_add_illegal_names();

	/* Upgrade and remove the message threads table if it exists */
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$wpdb->base_prefix}bp_messages_threads%'" ) ) {
		$upgrade = BP_Messages_Thread::upgrade_tables();

		if ( $upgrade )
			$wpdb->query( "DROP TABLE {$wpdb->base_prefix}bp_messages_threads" );
	}

}

/**
 * bp_core_add_admin_menu_page()
 *
 * A better version of add_admin_menu_page() that allows positioning of menus.
 *
 * @package BuddyPress Core
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	global $menu, $admin_page_hooks, $_registered_pages;

	$defaults = array(
		'page_title' => '',
		'menu_title' => '',
		'access_level' => 2,
		'file' => false,
		'function' => false,
		'icon_url' => false,
		'position' => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$file = plugin_basename( $file );

	$admin_page_hooks[$file] = sanitize_title( $menu_title );

	$hookname = get_plugin_page_hookname( $file, '' );
	if ( ! empty( $function ) && !empty ( $hookname ) )
		add_action( $hookname, $function );

	if ( empty($icon_url) )
		$icon_url = 'images/generic.png';
	elseif ( is_ssl() && 0 === strpos($icon_url, 'http://') )
		$icon_url = 'https://' . substr($icon_url, 7);

	do {
		$position++;
	} while ( !empty( $menu[$position] ) );

	$menu[$position] = array ( $menu_title, $access_level, $file, $page_title, 'menu-top ' . $hookname, $hookname, $icon_url );

	$_registered_pages[$hookname] = true;

	return $hookname;
}

function bp_core_wizard_message() {
	if ( isset( $_GET['updated'] ) )
		$message = __( 'Installation was successful. The available options have now been updated, please continue with your selection.', 'buddypress' );
	else
		return false;
?>
	<div id="message" class="updated">
		<p><?php echo esc_attr( $message ) ?></p>
	</div>
<?php
}
add_action( 'bp_admin_notices', 'bp_core_wizard_message' );

/* Alter thickbox screens so the entire plugin download and install interface is contained within. */
function bp_core_wizard_thickbox() {
?>
	<script type="text/javascript">
		jQuery('p.action-button a').attr( 'target', '' );

		if ( window.location != window.parent.location ) {
			jQuery('#adminmenu, #wphead, #footer, #update-nag, #screen-meta').hide();
			jQuery('#wpbody').css( 'margin', '15px' );
			jQuery('body').css( 'min-width', '30px' );
			jQuery('#wpwrap').css( 'min-height', '30px' );
			jQuery('a').removeClass( 'thickbox thickbox-preview onclick' );
			jQuery('body.update-php div.wrap p:last').hide();
			jQuery('body.update-php div.wrap p:last').after( '<p><a class="button" target="_parent" href="<?php echo site_url( '/wp-admin/admin.php?page=bp-wizard' ) ?>&updated=1"><?php _e( 'Finish', 'buddypress' ) ?> &rarr;</a></p>' );
		}
	</script>
<?php
}
add_action( 'admin_footer', 'bp_core_wizard_thickbox' );

/**
 * bp_core_add_admin_menu()
 *
 * Adds the "BuddyPress" admin submenu item to the Site Admin tab.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses add_submenu_page() WP function to add a submenu item
 */
function bp_core_add_admin_menu() {
	global $bp_wizard;

	if ( !current_user_can( 'activate_plugins' ) )
		return false;

	if ( '' == get_site_option( 'bp-db-version' ) && !(int)get_site_option( 'bp-core-db-version' ) )
		$status = __( 'Setup', 'buddypress' );
	else
		$status = __( 'Upgrade', 'buddypress' );

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	bp_core_add_admin_menu_page( array(
		'menu_title' => __( 'BuddyPress', 'buddypress' ),
		'page_title' => __( 'BuddyPress', 'buddypress' ),
		'access_level' => 10, 'file' => 'bp-wizard',
		'function' => '',
		'position' => 2
	) );

	$hook = add_submenu_page( 'bp-wizard', $status, $status, 'manage_options', 'bp-wizard', array( $bp_wizard, 'html' ) );

	/* Add a hook for css/js */
	add_action( "admin_print_styles-$hook", 'bp_core_add_admin_menu_styles' );
}
add_action( 'admin_menu', 'bp_core_add_admin_menu' );

function bp_core_add_admin_menu_styles() {
	wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', plugins_url( $path = '/buddypress' ) . '/bp-core/css/admin.css' ) );
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );
?>
	<style type="text/css">
		ul#adminmenu li.toplevel_page_bp-wizard .wp-menu-image a { background-image: url( <?php echo plugins_url( 'buddypress/bp-core/images/admin_menu_icon.png' ) ?> ) !important; background-position: -1px -32px; }
		ul#adminmenu li.toplevel_page_bp-wizard:hover .wp-menu-image a { background-position: -1px 0; }
		ul#adminmenu li.toplevel_page_bp-wizard .wp-menu-image a img { display: none; }
	</style>
<?php
}

?>