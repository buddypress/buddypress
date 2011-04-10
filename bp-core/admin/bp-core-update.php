<?php

class BP_Core_Setup_Wizard {
	var $current_step;
	var $steps;

	var $database_version;
	var $is_network_activate;
	var $new_version;
	var $setup_type;

	function bp_core_setup_wizard() {
		// Look for current DB version
		if ( !$this->database_version = get_site_option( 'bp-db-version' ) ) {
			if ( $this->database_version = get_option( 'bp-db-version' ) ) {
				$this->is_network_activate = true;
			} else {
				if ( !$this->current_step() ) {
					setcookie( 'bp-wizard-step', 0, time() + 60 * 60 * 24, COOKIEPATH );
					$_COOKIE['bp-wizard-step'] = 0;
				}
			}
		}

		$this->new_version  = constant( 'BP_DB_VERSION' );
		$this->setup_type   = ( empty( $this->database_version ) && !(int)get_site_option( 'bp-core-db-version' ) ) ? 'install' : 'update';
		$this->current_step = $this->current_step();

		// Call the save method that will save data and modify $current_step
		if ( isset( $_POST['save'] ) )
			$this->save( $_POST['save'] );

		// Build the steps needed for update or new installations
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

		// Setup wizard steps
		$steps = array();

		if ( 'install' == $this->setup_type ) {
			$steps = array(
				__( 'Components', 'buddypress' ),
				__( 'Pages',      'buddypress' ),
				__( 'Permalinks', 'buddypress' ),
				__( 'Theme',      'buddypress' ),
				__( 'Finish',     'buddypress' )
			);

		// Update wizard steps
		} else {
			if ( $this->is_network_activate )
				$steps[] = __( 'Multisite Update', 'buddypress' );

			if ( $this->database_version < $this->new_version )
				$steps[] = __( 'Database Update', 'buddypress' );

			if ( $this->database_version < 1225 || !bp_core_get_page_meta() )
				$steps[] = __( 'Pages', 'buddypress' );

			$steps[] = __( 'Finish', 'buddypress' );
		}

		return $steps;
	}

	function save( $step_name ) {

		// Save any posted values
		switch ( $step_name ) {
			case 'db_update': default:
				$result = $this->step_db_update_save();
				break;

			case 'ms_update': default:
				$result = $this->step_ms_update_save();
				break;

			case 'ms_pages': default:
				$result = $this->step_ms_update_save();
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

	function html() {

		// Update or Setup
		$type = ( 'update' == $this->setup_type ) ? __( 'Update', 'buddypress' ) : __( 'Setup', 'buddypress' );

		?>

		<div class="wrap" id="bp-admin">

			<?php screen_icon( 'buddypress' ); ?>

			<h2><?php printf( __( 'BuddyPress %s', 'buddypress' ), $type ); ?></h2>

			<?php
				do_action( 'bp_admin_notices' );

				$step_count  = count( $this->steps ) - 1;
				$wiz_or_set  = $this->current_step >= $step_count ? 'bp-general-settings' : 'bp-wizard';
				$form_action = is_multisite() ? network_admin_url( add_query_arg( array( 'page' => $wiz_or_set ), 'admin.php' ) ) : admin_url( add_query_arg( array( 'page' => $wiz_or_set ), 'admin.php' ) );
			?>

			<form action="<?php echo $form_action; ?>" method="post" id="bp-admin-form">
				<div id="bp-admin-nav">
					<ol>

						<?php foreach( (array)$this->steps as $i => $name ) : ?>

							<li<?php if ( $this->current_step == $i ) : ?> class="current"<?php endif; ?>>
								<?php if ( $this->current_step > $i ) : ?>

									<span class="complete">&nbsp;</span>

								<?php else :

									echo $i + 1 . '. ';

								endif;

								echo esc_attr( $name ) ?>

							</li>

						<?php endforeach; ?>

					</ol>

					<?php if ( __( 'Finish', 'buddypress' ) == $this->steps[$this->current_step] ) : ?>

						<div class="prev-next submit clear">
							<input type="submit" value="<?php _e( 'Finish &amp; Activate &rarr;', 'buddypress' ); ?>" name="submit" />
						</div>

					<?php else : ?>

						<div class="prev-next submit clear">
							<input type="submit" value="<?php _e( 'Save &amp; Next &rarr;', 'buddypress' ); ?>" name="submit" />
						</div>

					<?php endif; ?>

				</div>

				<div id="bp-admin-content">

					<?php switch ( $this->steps[$this->current_step] ) {
						case __( 'Database Update', 'buddypress') :
							$this->step_db_update();
							break;

						case __( 'Multisite Update', 'buddypress') :
							$this->step_ms_update();
							break;

						case __( 'Blog Directory', 'buddypress') :
							$this->step_ms_update();
							break;

						case __( 'Components', 'buddypress') :
							$this->step_components();
							break;

						case __( 'Pages', 'buddypress') :
							$this->step_pages();
							break;

						case __( 'Permalinks', 'buddypress') :
							$this->step_permalinks();
							break;

						case __( 'Theme', 'buddypress') :
							$this->step_theme();
							break;

						case __( 'Finish', 'buddypress') :
							$this->step_finish();
							break;

					} ?>

				</div>
			</form>
		</div>

	<?php
	}

	/** Screen methods ********************************************************/

	function step_db_update() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false; ?>

		<p><?php _e( 'Before you can continue using BuddyPress, a few minor adjustments need to be made. These changes are not distructive and will not remove or change any existing settings.', 'buddypress' ); ?></p>

		<div class="submit clear">
			<input type="hidden" name="save" value="db_update" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php wp_nonce_field( 'bpwizard_db_update' ) ?>

		</div>

	<?php
	}

	function step_ms_update() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		if ( defined( 'BP_BLOGS_SLUG' ) )
			$blogs_slug = constant( 'BP_BLOGS_SLUG' );
		else
			$blogs_slug = 'blogs';

		if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
			$existing_pages = get_blog_option( BP_ROOT_BLOG, 'bp-pages' );
		else
			$existing_pages = get_option( 'bp-pages' );

		if ( !empty( $existing_pages['blogs'] ) )
			$existing_blog_page = '&selected=' . $existing_pages['blogs'];
		else
			$existing_blog_page = '';
		?>

		<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( 'select' ).change( function() {
					jQuery( this ).siblings( 'input[@type=radio]' ).click();
				});
			});
		</script>

		<p><?php printf( __( 'BuddyPress has detected a recent change to WordPress Multisite, which allows members of your community to have their own WordPress blogs. You can enable or disable this feature at any time at <a href="%s">Network Options</a>.', 'buddypress' ), admin_url( 'ms-options.php' ) ); ?></p>

		<p><?php _e( "Please select the WordPress page you would like to use to display the blog directory. You can either choose an existing page or let BuddyPress auto-create a page for you. If you'd like to manually create pages, please go ahead and do that now, you can come back to this step once you are finished.", 'buddypress' ) ?></p>

		<p><strong><?php _e( 'Please Note:', 'buddypress' ) ?></strong> <?php _e( "If you have manually added BuddyPress navigation links in your theme you may need to remove these from your header.php to avoid duplicate links.", 'buddypress' ) ?></p>

		<table class="form-table">

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Blogs', 'buddypress' ); ?></h5>
					<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ); ?></p>
				</th>
				<td>
					<p><input type="radio" name="bp_pages[blogs]" checked="checked" value="<?php echo $blogs_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo site_url( $blogs_slug ); ?>/</p>
					<p><input type="radio" name="bp_pages[blogs]" value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-blogs-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . $existing_blog_page ); ?></p>
				</td>
			</tr>

		</table>

		<p><?php _e( 'Would you like to enable blog tracking, which tracks blog activity across your network?', 'buddypress' ); ?></p>

		<div class="left-col">

			<div class="component">
				<h5><?php _e( "Blog Tracking", 'buddypress' ); ?></h5>

				<div class="radio">
					<input type="radio" name="bp_components[bp-blogs.php]" value="1"<?php if ( !isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Enabled', 'buddypress' ) ?> &nbsp;
					<input type="radio" name="bp_components[bp-blogs.php]" value="0"<?php if ( isset( $disabled_components['bp-blogs.php'] ) ) : ?> checked="checked" <?php endif; ?>/> <?php _e( 'Disabled', 'buddypress' ) ?>
				</div>

				<img src="<?php echo plugins_url( 'buddypress/screenshot-7.gif' ) ?>" alt="Activity Streams" />

				<p><?php _e( "Track new blogs, new posts and new comments across your entire blog network.", 'buddypress' ) ?></p>

			</div>
		</div>

		<div class="submit clear">
			<input type="hidden" name="save" value="ms_update" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php wp_nonce_field( 'bpwizard_ms_update' ); ?>

		</div>

	<?php
	}

	function step_components() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		if ( !function_exists( 'bp_core_admin_component_options' ) )
			require ( WP_PLUGIN_DIR . '/buddypress/bp-core/admin/bp-core-admin.php' );

		$disabled_components = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) ); ?>

		<p><?php _e( "BuddyPress bundles several individual social components together, each one adding a distinct feature. This first step decides which features are enabled on your site; all features are enabled by default. Don't worry, you can change your mind at any point in the future.", 'buddypress' ); ?></p>

		<?php bp_core_admin_component_options(); ?>

		<div class="submit clear">
			<input type="hidden" name="save" value="components" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php wp_nonce_field( 'bpwizard_components' ); ?>

		</div>

	<?php
	}

	function step_pages() {
		global $bp;

		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		// Determine where to get the pages from
		if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
			$existing_pages = get_blog_option( BP_ROOT_BLOG, 'bp-pages' );
		else
			$existing_pages = get_option( 'bp-pages' );

		// Get disabled components
		$disabled_components = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

		// Check for defined slugs
		$members_slug    = !empty( $bp->members->slug    ) ? $bp->members->slug    : __( 'members',  'buddypress' );

		// Groups
		$groups_slug     = !empty( $bp->groups->slug     ) ? $bp->groups->slug     : __( 'groups',   'buddypress' );

		// Activity
		$activity_slug   = !empty( $bp->activity->slug   ) ? $bp->activity->slug   : __( 'activity', 'buddypress' );

		// Forums
		$forums_slug     = !empty( $bp->forums->slug     ) ? $bp->forums->slug     : __( 'forums',   'buddypress' );

		// Blogs
		$blogs_slug      = !empty( $bp->blogs->slug      ) ? $bp->blogs->slug      : __( 'blogs',    'buddypress' );

		// Register
		$register_slug   = !empty( $bp->register->slug   ) ? $bp->register->slug   : __( 'register', 'buddypress' );

		// Activation
		$activation_slug = !empty( $bp->activation->slug ) ? $bp->activation->slug : __( 'activate', 'buddypress' );

		?>

		<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( 'select' ).change( function() {
					jQuery( this ).siblings( 'input[@type=radio]' ).click();
				});
			});
		</script>

		<p><?php _e( 'BuddyPress now uses WordPress pages to display content. This allows you to easily change the names of pages or move them to a sub page.', 'buddypress' ); ?></p>

		<p><?php _e( 'Either choose an existing page or let BuddyPress auto-create pages for you. To manually create custom pages, come back to this step once you are finished.', 'buddypress' ); ?></p>

		<p><strong><?php _e( 'Please Note:', 'buddypress' ); ?></strong> <?php _e( 'If you have manually added BuddyPress navigation links in your theme you may need to remove these from your header.php to avoid duplicate links.', 'buddypress' ); ?></p>

		<table class="form-table">

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Members', 'buddypres' ); ?></h5>
					<p><?php _e( 'Displays member profiles, and a directory of all site members.', 'buddypress' ); ?></p>
				</th>
				<td>
					<p><label><input type="radio" name="bp_pages[members]" <?php checked( empty( $existing_pages['members'] ) ); ?>  value="<?php echo $members_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo home_url( $members_slug ); ?>/</label></p>
					<p><label><input type="radio" name="bp_pages[members]" <?php checked( !empty( $existing_pages['members'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-members-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['members'] ); ?></label></p>
				</td>
			</tr>

			<?php if ( !isset( $disabled_components['bp-activity.php'] ) ) : ?>

				<tr valign="top">
					<th scope="row">
						<h5><?php _e( 'Site Activity', 'buddypress' ); ?></h5>
						<p><?php _e( "Displays the activity for the entire site, a member's friends, groups and @mentions.", 'buddypress' ); ?></p>
					</th>
					<td>
						<p><label><input type="radio" name="bp_pages[activity]" <?php checked( empty( $existing_pages['activity'] ) ); ?>  value="<?php echo $activity_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo home_url( $activity_slug ); ?>/</label></p>
						<p><label><input type="radio" name="bp_pages[activity]" <?php checked( !empty( $existing_pages['activity'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-activity-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['activity'] ); ?></label></p>
					</td>
				</tr>

			<?php endif; ?>

			<?php if ( !isset( $disabled_components['bp-groups.php'] ) ) : ?>

				<tr valign="top">
					<th scope="row">
						<h5><?php _e( 'Groups', 'buddypress' ); ?></h5>
						<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ); ?></p>
					</th>
					<td>
						<p><label><input type="radio" name="bp_pages[groups]" <?php checked( empty( $existing_pages['groups'] ) ); ?>  value="<?php echo $groups_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo home_url( $groups_slug ); ?>/</label></p>
						<p><label><input type="radio" name="bp_pages[groups]" <?php checked( !empty( $existing_pages['groups'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-groups-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['groups'] ); ?></label></p>
					</td>
				</tr>

			<?php endif; ?>

			<?php if ( !isset( $disabled_components['bp-forums.php'] ) ) : ?>

				<tr valign="top">
					<th scope="row">
						<h5><?php _e( 'Forums', 'buddypress' ); ?></h5>
						<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ); ?></p>
					</th>
					<td>
						<p><label><input type="radio" name="bp_pages[forums]" <?php checked( empty( $existing_pages['forums'] ) ); ?>  value="<?php echo $forums_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo home_url( $forums_slug ); ?>/</label></p>
						<p><label><input type="radio" name="bp_pages[forums]" <?php checked( !empty( $existing_pages['forums'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-forums-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['forums'] ); ?></label></p>
					</td>
				</tr>

			<?php endif; ?>

			<?php if ( is_multisite() && !isset( $disabled_components['bp-blogs.php'] ) ) : ?>

				<tr valign="top">
					<th scope="row">
						<h5><?php _e( 'Blogs', 'buddypress' ); ?></h5>
						<p><?php _e( 'Displays individual groups as well as a directory of groups.', 'buddypress' ); ?></p>
					</th>
					<td>
						<p><label><input type="radio" name="bp_pages[blogs]" <?php checked( empty( $existing_pages['blogs'] ) ); ?>  value="<?php echo $blogs_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo home_url( $blogs_slug ); ?>/</label></p>
						<p><label><input type="radio" name="bp_pages[blogs]" <?php checked( !empty( $existing_pages['blogs'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-blogs-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['blogs'] ); ?></label></p>
					</td>
				</tr>

			<?php endif; ?>

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Register', 'buddypress' ); ?></h5>
					<p><?php _e( 'Displays a site registration page where users can create new accounts.', 'buddypress' ); ?></p>
				</th>
				<td>
					<p><label><input type="radio" name="bp_pages[register]" <?php checked( empty( $existing_pages['register'] ) ); ?>  value="<?php echo $register_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ) ?> <?php echo home_url( $register_slug ) ?>/</label></p>
					<p><label><input type="radio" name="bp_pages[register]" <?php checked( !empty( $existing_pages['register'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ) ?> <?php echo wp_dropdown_pages( "name=bp-register-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['register'] ); ?></label></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<h5><?php _e( 'Activate', 'buddypress' ); ?></h5>
					<p><?php _e( 'The page users will visit to activate their account once they have registered.', 'buddypress' ); ?></p>
				</th>
				<td>
					<p><label><input type="radio" name="bp_pages[activate]" <?php checked( empty( $existing_pages['activate'] ) ); ?>  value="<?php echo $activation_slug; ?>" /> <?php _e( 'Automatically create a page at:', 'buddypress' ); ?> <?php echo home_url( $activation_slug ); ?>/</label></p>
					<p><label><input type="radio" name="bp_pages[activate]" <?php checked( !empty( $existing_pages['activate'] ) ); ?> value="page" /> <?php _e( 'Use an existing page:', 'buddypress' ); ?> <?php echo wp_dropdown_pages( "name=bp-activate-page&echo=0&show_option_none=" . __( '- Select -', 'buddypress' ) . "&selected=" . $existing_pages['activate'] ); ?></label></p>
				</td>
			</tr>
		</table>

		<div class="submit clear">
			<input type="hidden" name="save" value="pages" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php wp_nonce_field( 'bpwizard_pages' ); ?>

		</div>

	<?php
	}

	function step_permalinks() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		$prefix              = '';
		$permalink_structure = get_option( 'permalink_structure' );
		$structures          = array( '', $prefix . '/%year%/%monthnum%/%day%/%postname%/', $prefix . '/%year%/%monthnum%/%postname%/', $prefix . '/archives/%post_id%' );

		if ( !got_mod_rewrite() && !iis7_supports_permalinks() )
			$prefix = '/index.php'; ?>

		<p><?php _e( 'To make sure the pages created in the previous step work correctly, pretty permalinks must be active on your site.', 'buddypress' ); ?></p>
		<p><?php printf( __( 'Please select the permalink setting you would like to use. For more advanced options please visit the <a href="%s">permalink settings page</a> first, and complete this setup wizard later.', 'buddypress' ), admin_url( 'options-permalink.php' ) ); ?>

		<table class="form-table">
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[1] ) ) : ?> checked="checked" <?php endif; ?>value="<?php echo esc_attr( $structures[1] ); ?>" class="tog" <?php checked( $structures[1], $permalink_structure ); ?> />&nbsp;<?php _e( 'Day and name' ); ?></label></th>
				<td><code><?php echo get_home_url() . $prefix . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/sample-post/'; ?></code></td>
			</tr>
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[2] ) ) : ?> checked="checked" <?php endif; ?> value="<?php echo esc_attr( $structures[2] ); ?>" class="tog" <?php checked( $structures[2], $permalink_structure ); ?> />&nbsp;<?php _e( 'Month and name' ); ?></label></th>
				<td><code><?php echo get_home_url() . $prefix . '/' . date('Y') . '/' . date('m') . '/sample-post/'; ?></code></td>
			</tr>
			<tr>
				<th><label><input name="permalink_structure" type="radio"<?php if ( empty( $permalink_structure ) || false != strpos( $permalink_structure, $structures[3] ) ) : ?> checked="checked" <?php endif; ?> value="<?php echo esc_attr( $structures[3] ); ?>" class="tog" <?php checked( $structures[3], $permalink_structure ); ?> />&nbsp;<?php _e( 'Numeric' ); ?></label></th>
				<td><code><?php echo get_home_url() . $prefix ?>/archives/123</code></td>
			</tr>
		</table>

		<div class="submit clear">
			<input type="hidden" name="save" value="permalinks" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && empty( $_POST['skip-htaccess'] ) ) : ?>

				<input type="hidden" name="skip-htaccess" value="skip-htaccess" />

			<?php endif; ?>

			<?php wp_nonce_field( 'bpwizard_permalinks' ); ?>

		</div>

	<?php
	}

	function step_theme() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		require_once( ABSPATH . WPINC . '/plugin.php' );
		$installed_plugins = get_plugins();
		$installed_themes  = get_themes();

		$template_pack_installed = false;
		$bp_autotheme_installed  = false;
		$bp_theme_installed      = false;

		foreach ( (array)$installed_plugins as $plugin ) {
			if ( 'BuddyPress Template Pack' == $plugin['Name'] ) {
				$template_pack_installed = true;
			}
		}

		foreach ( (array)$installed_themes as $theme ) {
			foreach ( (array)$theme['Tags'] as $tag ) {
				if ( ( 'BuddyPress Default' != $theme['Name'] ) && ( 'buddypress' == $tag ) ) {
					$bp_theme_installed = true;
					$bp_themes[] = $theme;
				}
			}
		}
	?>

		<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( 'select' ).change( function() {
					jQuery( this ).siblings( 'input[@type=radio]' ).click();
				});
			});
		</script>

		<p><?php _e( "BuddyPress introduces a whole range of new screens to display content. To display these screens you will need to decide how you want to handle them in your active theme. There are a few different options, please choose the option that best suits your demands and needs.", 'buddypress' ); ?></p>

		<table class="form-table">
			<tr>
				<th>
					<h5><?php _e( 'Use the Default Theme', 'buddypress' ); ?></h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/default.jpg' ); ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( 'The default theme contains everything you need to get up and running out of the box. It supports all features and is highly customizable.', 'buddypress' ); ?></p>
					<p><strong><?php _e( 'This is the best choice if you do not have an existing WordPress theme or want to create a child theme from a solid starting point.', 'buddypress' ); ?></strong></p>
					<p><label><input type="radio" name="theme" value="bp_default" checked="checked" /> <?php _e( 'Choose this option', 'buddypress' ); ?></label></p>
				</td>
			</tr>

			<?php /*
			<tr>
				<th>
					<h5>Automatically Update My WordPress Theme</h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/auto_theme.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p>The BuddyPress [plugin name] plugin will automatically update your existing WordPress theme so it can display BuddyPress pages. Your existing theme's page.php template file will be used to show BuddyPress content.</p>
					<p><strong>This is the best choice if you have an existing WordPress theme and simply want to start using BuddyPress features without control of template layout and design.</strong></p>
					<p><label><input type="radio" name="theme" value="auto_wp" disabled="disabled" /> You must first install the [plugin name] before choosing this option</label></p>
					<p><a id="bp-plugin-name" class="thickbox onclick button" href="http://buddypressorg.dev/wp-admin/plugin-install.php?tab=plugin-information&plugin=bp-template-pack&TB_iframe=true&width=640&height=500">+ Install Now</a></p>
				</td>
			</tr>
			*/ ?>

			<tr>
				<th>
					<h5><?php _e( 'Manually Update My WordPress Theme', 'buddypress' ); ?>'</h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/manual_theme.jpg' ); ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( 'The BuddyPress template pack plugin will run you through the process of manually upgrading your existing WordPress theme. This usually involves following the step by step instructions and copying the BuddyPress template files into your theme then tweaking the HTML to match.', 'buddypress' ); ?></p>
					<p><strong><?php _e( 'This is the best choice if you have an existing WordPress theme and want complete control over template layout and design.', 'buddypress' ); ?></strong></p>

					<?php if ( empty( $template_pack_installed ) ) : ?>

						<p><label><input type="radio" name="theme" value="manual_wp" disabled="disabled" /> <?php _e( 'You must first install the BuddyPress template pack before choosing this option', 'buddypress' ); ?></label></p>
						<p><a id="bp-template-pack" class="thickbox onclick button" href="http://buddypressorg.dev/wp-admin/plugin-install.php?tab=plugin-information&plugin=bp-template-pack&TB_iframe=true&width=640&height=500">+ <?php _e( 'Install Now', 'buddypress' ); ?></a></p>

					<?php else : ?>

						<p><label><input type="radio" name="theme" value="manual_wp" /> <?php _e( 'Choose this option (go to Appearance &rarr; BP Compatibility after setup is complete)', 'buddypress' ); ?></label></p>
						<p><a id="bp-template-pack" class="button installed disabled" href="javascript:void();"><span></span><?php _e( 'Plugin Installed', 'buddypress' ); ?></a></p>

					<?php endif; ?>

				</td>
			</tr>

			<tr>
				<th>
					<h5><?php _e( 'Find a BuddyPress Theme', 'buddypress' ); ?></h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/find.jpg' ); ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( "There's growing number of BuddyPress themes available for you to download and use. Browse through the list of available themes to see if there is one that matches your needs.", 'buddypress' ); ?></p>
					<p><strong><?php _e( 'This is the best choice if want to use a theme other than the default and get started straight out of the box.', 'buddypress' ); ?></strong></p>

					<?php if ( empty( $bp_theme_installed ) ) : ?>

						<p><label><input type="radio" name="theme" value="third_party" disabled="disabled" /> <?php _e( 'You must first install at least one BuddyPress theme before choosing this option', 'buddypress' ); ?></label></p>
						<p><a id="bp-themes" class="thickbox onclick button" href="<?php echo admin_url( 'theme-install.php?type=tag&s=buddypress&tab=search' ); ?>&TB_iframe=true&width=860&height=500">+ <?php _e( 'Add Themes', 'buddypress' ); ?></a></p>

					<?php else : ?>

						<p><label>
								<input type="radio" name="theme" value="3rd_party" /> <?php _e( 'Choose this option and use the theme:', 'buddypress' ); ?>
							</label>
							<select name="3rd_party_theme">

								<?php foreach( (array)$bp_themes as $theme ) : ?>

									<option value="<?php echo $theme['Template'] . ',' . $theme['Stylesheet']; ?>"><?php echo $theme['Name']; ?></option>

								<?php endforeach; ?>

							</select>
						</p>

					<?php endif; ?>

				</td>
			</tr>

			<tr>
				<th>
					<h5><?php _e( 'Do Not Change Theme', 'buddypress' ) ?></h5>
					<img src="<?php echo plugins_url( '/buddypress/bp-core/images/existing.jpg' ) ?>" alt="bp-default" />
				</th>
				<td>
					<p><?php _e( "You are happy with your current theme and plan on changing it later.", 'buddypress' ) ?></p>
					<p><strong><?php _e( 'This is the best choice if you have a highly customized theme on your site already, and want to manually integrate BuddyPress into your site over time.', 'buddypress' ) ?></strong></p>

					<p><label><input type="radio" name="theme" value="do_not_change" /> <?php _e( 'Choose this option', 'buddypress' ) ?></label></p>

				</td>
			</tr>
		</table>

		<div class="submit clear">
			<input type="hidden" name="save" value="theme" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ) ?>" />

			<?php wp_nonce_field( 'bpwizard_theme' ) ?>

		</div>

	<?php
	}

	function step_finish() {
		if ( !current_user_can( 'activate_plugins' ) )
			return false;

		// What type of action is happening here?
		$type = ( 'install' == $this->setup_type ) ? __( 'setup', 'buddypress' ) : __( 'update', 'buddypress' ); ?>

		<p><?php printf( __( "The BuddyPress %1\$s is complete, and your site is ready to go!", 'buddypress' ), $type, $type ); ?></p>

		<div class="submit clear">
			<input type="hidden" name="save" value="finish" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $this->current_step ); ?>" />

			<?php wp_nonce_field( 'bpwizard_finish' ); ?>

		</div>

	<?php
	}

	/** Save Step Methods *****************************************************/

	function step_db_update_save() {
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_db_update' );

			// Run the schema install to update tables
			bp_core_install();

			if ( $this->database_version < 1225 )
				$this->update_1_3();

			return true;
		}

		return false;
	}

	function step_ms_update_save() {
		global $wpdb;

		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_ms_update' );

			if ( !$disabled = get_option( 'bp-deactivated-components' ) )
				$disabled = array();

			// Transfer important settings from blog options to site options
			$options = array(
				'bp-db-version'             => $this->database_version,
				'bp-deactivated-components' => $disabled,
				'avatar-default'            => get_option( 'avatar-default' )
			);
			bp_core_activate_site_options( $options );

			if ( !(int) $_POST['bp_components']['bp-blogs.php'] ) {
				if ( empty( $disabled ) ) {
					$disabled = array();
				}

				$disabled['bp-blogs.php'] = 1;

			} else {

				// Make sure that the pages are created on the BP_ROOT_BLOG, no matter which Dashboard the setup is being run on
				if ( !empty( $wpdb->blogid ) && ( $wpdb->blogid != BP_ROOT_BLOG ) && ( !defined( 'BP_ENABLE_MULTIBLOG' ) ) )
					switch_to_blog( BP_ROOT_BLOG );

				$existing_pages = get_option( 'bp-pages' );
				$bp_pages       = $this->setup_pages( (array)$_POST['bp_pages'] );
				$bp_pages       = array_merge( (array)$existing_pages, (array)$bp_pages );

				update_option( 'bp-pages', $bp_pages );

				if ( !empty( $wpdb->blogid ) && ( $wpdb->blogid != BP_ROOT_BLOG ) && ( !defined( 'BP_ENABLE_MULTIBLOG' ) ) )
					restore_current_blog();

				unset( $disabled['bp-blogs.php'] );

				bp_core_install( $disabled );
			}

			update_site_option( 'bp-deactivated-components', $disabled );

			return true;
		}

		return false;
	}


	function step_components_save() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['bp_components'] ) ) {
			check_admin_referer( 'bpwizard_components' );

			$active_components = array();

			// Settings form submitted, now save the settings.
			foreach ( (array)$_POST['bp_components'] as $key => $value )
				$active_components[$key] = 1;

			update_site_option( 'bp-active-components', $active_components );

			wp_cache_flush();
			bp_core_install();

			return true;
		}

		return false;
	}

	function step_pages_save() {
		global $wpdb;

		if ( isset( $_POST['submit'] ) && isset( $_POST['bp_pages'] ) ) {
			check_admin_referer( 'bpwizard_pages' );

			// Make sure that the pages are created on the BP_ROOT_BLOG, no matter which Dashboard the setup is being run on
			if ( !empty( $wpdb->blogid ) && ( $wpdb->blogid != BP_ROOT_BLOG ) && ( !defined( 'BP_ENABLE_MULTIBLOG' ) ) )
				switch_to_blog( BP_ROOT_BLOG );

			// Delete any existing pages
			$existing_pages = bp_core_update_get_page_meta( 'bp-pages' );

			foreach ( (array)$existing_pages as $page_id )
				wp_delete_post( $page_id, true );

			$bp_pages = $this->setup_pages( (array)$_POST['bp_pages'] );

			update_option( 'bp-pages', $bp_pages );

			if ( !empty( $wpdb->blogid ) && ( $wpdb->blogid != BP_ROOT_BLOG ) && ( !defined( 'BP_ENABLE_MULTIBLOG' ) ) )
				restore_current_blog();

			return true;
		}

		return false;
	}

	function step_permalinks_save() {
		global $wp_rewrite, $current_site, $current_blog;

		// Prevent debug notices
		$iis7_permalinks = $usingpi = $writable = false;

		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_permalinks' );

			$home_path       = get_home_path();
			$iis7_permalinks = iis7_supports_permalinks();

			if ( isset( $_POST['permalink_structure'] ) ) {
				$permalink_structure = $_POST['permalink_structure'];

				if ( !empty( $permalink_structure ) )
					$permalink_structure = preg_replace( '#/+#', '/', '/' . $_POST['permalink_structure'] );

				if ( ( defined( 'VHOST' ) && constant( 'VHOST' ) == 'no' ) && $permalink_structure != '' && $current_site->domain . $current_site->path == $current_blog->domain . $current_blog->path )
					$permalink_structure = '/blog' . $permalink_structure;

				$wp_rewrite->set_permalink_structure( $permalink_structure );
			}

			if ( !empty( $iis7_permalinks ) ) {
				if ( ( !file_exists( $home_path . 'web.config' ) && win_is_writable( $home_path ) ) || win_is_writable( $home_path . 'web.config' ) ) {
					$writable = true;
				}
			} else {
				if ( ( !file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) ) || is_writable( $home_path . '.htaccess' ) ) {
					$writable = true;
				}
			}

			if ( $wp_rewrite->using_index_permalinks() )
				$usingpi = true;

			$wp_rewrite->flush_rules();

			if ( !empty( $iis7_permalinks ) || ( empty( $usingpi ) && empty( $writable ) ) ) {

				function _bp_core_wizard_step_permalinks_message() {
					global $wp_rewrite; ?>

					<div id="message" class="updated fade"><p>

						<?php
							_e( 'Oops, there was a problem creating a configuration file. ', 'buddypress' );

							if ( !empty( $iis7_permalinks ) ) {

								if ( !empty( $permalink_structure ) && empty( $usingpi ) && empty( $writable ) ) {

									_e( 'If your <code>web.config</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so this is the url rewrite rule you should have in your <code>web.config</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all. Then insert this rule inside of the <code>/&lt;configuration&gt;/&lt;system.webServer&gt;/&lt;rewrite&gt;/&lt;rules&gt;</code> element in <code>web.config</code> file.' ); ?>

									<br /><br />

									<textarea rows="9" class="large-text readonly" style="background: #fff;" name="rules" id="rules" readonly="readonly"><?php echo esc_html( $wp_rewrite->iis7_url_rewrite_rules() ); ?></textarea>

								<?php

								} else if ( !empty( $permalink_structure ) && empty( $usingpi ) && !empty( $writable ) ); {
									_e( 'Permalink structure updated. Remove write access on web.config file now!' );
								}

							} else {

								_e( 'If your <code>.htaccess</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.' ); ?>

								<br /><br />

								<textarea rows="6" class="large-text readonly" style="background: #fff;" name="rules" id="rules" readonly="readonly"><?php echo esc_html( $wp_rewrite->mod_rewrite_rules() ); ?></textarea>

							<?php } ?>

						<br /><br />

						<?php
							if ( empty( $iis7_permalinks ) )
								_e( 'Paste all these rules into a new <code>.htaccess</code> file in the root of your WordPress installation and save the file. Once you\'re done, please hit the "Save and Next" button to continue.', 'buddypress' );
						?>

					</p></div>

				<?php
				}

				if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && !empty( $_POST['skip-htaccess'] ) ) {
					return true;
				} else {
					add_action( 'bp_admin_notices', '_bp_core_wizard_step_permalinks_message' );
					return false;
				}
			}

			return true;
		}

		return false;
	}

	function step_theme_save() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['theme'] ) ) {
			check_admin_referer( 'bpwizard_theme' );

			switch ( $_POST['theme'] ) {

				// Activate the bp-default theme
				case 'bp_default' :
					switch_theme( 'bp-default', 'bp-default' );
					break;

				// Activate Template Pack plugin
				case 'manual_wp' :

					// Include
					require_once( ABSPATH . WPINC . '/plugin.php' );
					$installed_plugins = get_plugins();

					foreach ( $installed_plugins as $key => $plugin ) {
						if ( 'BuddyPress Template Pack' == $plugin['Name'] ) {
							activate_plugin( $key );
						}
					}
					break;

				// Pick a theme from the repo
				case '3rd_party' :
					if ( empty( $_POST['3rd_party_theme'] ) )
						return false;

					$theme = explode( ',', $_POST['3rd_party_theme'] );
					switch_theme( $theme[0], $theme[1] );
					break;

				// Keep existing theme
				case 'do_not_change' :
					return true;
					break;
			}

			return true;
		}

		return false;
	}

	function step_finish_save() {
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'bpwizard_finish' );

			// Update the DB version in the database
			update_site_option( 'bp-db-version', constant( 'BP_DB_VERSION' ) );
			delete_site_option( 'bp-core-db-version' );

			// Delete the setup cookie
			@setcookie( 'bp-wizard-step', '', time() - 3600, COOKIEPATH );

			// Redirect to the BuddyPress dashboard
			$redirect = is_multisite() ? network_admin_url( add_query_arg( array( 'page' => 'bp-general-settings' ) ) ) : admin_url( add_query_arg( array( 'page' => 'bp-general-settings' ) ) );

			wp_redirect( $redirect );

			return true;
		}

		return false;
	}

	function setup_pages( $pages ) {
		foreach ( $pages as $key => $value ) {
			if ( 'page' == $value ) {
				// Check for the selected page
				if ( !empty( $_POST['bp-' . $key . '-page'] ) )
					$bp_pages[$key] = (int)$_POST['bp-' . $key . '-page'];
				else
					$bp_pages[$key] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $key ), 'post_status' => 'publish', 'post_type' => 'page' ) );
			} else {
				// Create a new page
				$bp_pages[$key] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $value ), 'post_status' => 'publish', 'post_type' => 'page' ) );
			}
		}

		return $bp_pages;
	}

	// Database update methods based on version numbers
	function update_1_3() {
		// Delete old database version options
		delete_site_option( 'bp-activity-db-version' );
		delete_site_option( 'bp-blogs-db-version'    );
		delete_site_option( 'bp-friends-db-version'  );
		delete_site_option( 'bp-groups-db-version'   );
		delete_site_option( 'bp-messages-db-version' );
		delete_site_option( 'bp-xprofile-db-version' );
	}

	/**
	 * Reset the cookie so the install script starts over
	 */
	function reset_cookie() {
		@setcookie( 'bp-wizard-step', '', time() - 3600, COOKIEPATH );
	}
}

function bp_core_setup_wizard_init() {
	global $bp_wizard;

	$bp_wizard = new BP_Core_Setup_Wizard;
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_core_setup_wizard_init', 7 );

function bp_core_install( $disabled = false ) {
	global $wpdb;

	if ( empty( $disabled ) )
		$disabled = apply_filters( 'bp_deactivated_components', get_site_option( 'bp-deactivated-components' ) );

	require_once( dirname( __FILE__ ) . '/bp-core-schema.php' );

	// Core DB Tables
	bp_core_install_notifications();

	// Activity Streams
	if ( empty( $disabled['bp-activity.php'] ) )
		bp_core_install_activity_streams();

	// Friend Connections
	if ( empty( $disabled['bp-friends.php'] ) )
		bp_core_install_friends();

	// Extensible Groups
	if ( empty( $disabled['bp-groups.php'] ) )
		bp_core_install_groups();

	// Private Messaging
	if ( empty( $disabled['bp-messages.php'] ) )
		bp_core_install_private_messaging();

	// Extended Profiles
	if ( empty( $disabled['bp-xprofile.php'] ) )
		bp_core_install_extended_profiles();

	// Only install blog tables if this is a multisite installation
	if ( is_multisite() && empty( $disabled['bp-blogs.php'] ) )
		bp_core_install_blog_tracking();
}

function bp_core_update( $disabled ) {
	global $wpdb;

	require_once( dirname( __FILE__ ) . '/bp-core-schema.php' );
}

function bp_update_db_stuff() {
	// Rename the old user activity cached table if needed.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$wpdb->base_prefix}bp_activity_user_activity_cached%'" ) )
		$wpdb->query( "RENAME TABLE {$wpdb->base_prefix}bp_activity_user_activity_cached TO {$bp->activity->table_name}" );

	// Rename fields from pre BP 1.2
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$bp->activity->table_name}%'" ) ) {
		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_action'" ) )
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_action type varchar(75) NOT NULL" );

		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_name'" ) )
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_name component varchar(75) NOT NULL" );
	}

	// On first installation - record all existing blogs in the system.
	if ( !(int)$bp->site_options['bp-blogs-first-install'] && is_multisite() ) {
		bp_blogs_record_existing_blogs();
		add_site_option( 'bp-blogs-first-install', 1 );
	}

	if ( is_multisite() )
		bp_core_add_illegal_names();

	// Update and remove the message threads table if it exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$wpdb->base_prefix}bp_messages_threads%'" ) ) {
		$update = BP_Messages_Thread::update_tables();

		if ( $update )
			$wpdb->query( "DROP TABLE {$wpdb->base_prefix}bp_messages_threads" );
	}

}

/**
 * A better version of add_admin_menu_page() that allows positioning of menus.
 *
 * @package BuddyPress Core
 */
function bp_core_update_add_admin_menu_page( $args = '' ) {
	global $menu, $admin_page_hooks, $_registered_pages;

	$defaults = array(
		'page_title' => '',
		'menu_title' => '',
		'capability' => 'manage_options',
		'menu_slug'  => '',
		'function'   => false,
		'icon_url'   => false,
		'position'   => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( is_multisite() )
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	else
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
}

function bp_core_wizard_message() {
	if ( isset( $_GET['updated'] ) )
		$message = __( 'Installation was successful. The available options have now been updated, please continue with your selection.', 'buddypress' );
	else
		return false; ?>

	<div id="message" class="updated">
		<p><?php echo esc_attr( $message ) ?></p>
	</div>

<?php
}
add_action( 'bp_admin_notices', 'bp_core_wizard_message' );

// Alter thickbox screens so the entire plugin download and install
// interface is contained within.
function bp_core_wizard_thickbox() {
	$form_action = is_multisite() ? network_admin_url( add_query_arg( array( 'page' => 'bp-wizard', 'updated' => '1' ), 'admin.php' ) ) : admin_url( add_query_arg( array( 'page' => 'bp-wizard', 'updated' => '1' ), 'admin.php' ) ); ?>

	<script type="text/javascript">
		jQuery('p.action-button a').attr( 'target', '' );

		if ( window.location != window.parent.location ) {
			jQuery('#adminmenu, #wphead, #footer, #update-nag, #screen-meta').hide();
			jQuery('#wpbody').css( 'margin', '15px' );
			jQuery('body').css( 'min-width', '30px' );
			jQuery('#wpwrap').css( 'min-height', '30px' );
			jQuery('a').removeClass( 'thickbox thickbox-preview onclick' );
			jQuery('body.update-php div.wrap p:last').hide();
			jQuery('body.update-php div.wrap p:last').after( '<p><a class="button" target="_parent" href="<?php echo $form_action; ?>"><?php _e( 'Finish', 'buddypress' ) ?> &rarr;</a></p>' );
		}
	</script>

<?php
}
add_action( 'admin_footer', 'bp_core_wizard_thickbox' );

/**
 * Adds the "BuddyPress" admin submenu item to the Site Admin tab.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses add_submenu_page() WP function to add a submenu item
 */
function bp_core_update_add_admin_menu() {
	global $bp, $bp_wizard;

	// Only load this version of the menu if this is an upgrade or a new installation
	if ( empty( $bp->maintenence_mode ) )
		return false;

	if ( !current_user_can( 'activate_plugins' ) )
		return false;

	if ( '' == get_site_option( 'bp-db-version' ) && !(int)get_site_option( 'bp-core-db-version' ) && !$bp_wizard->is_network_activate )
		$status = __( 'Setup', 'buddypress' );
	else
		$status = __( 'Update', 'buddypress' );

	// Add the administration tab under the "Site Admin" tab for site administrators
	bp_core_update_add_admin_menu_page( array(
		'menu_title' => __( 'BuddyPress', 'buddypress' ),
		'page_title' => __( 'BuddyPress', 'buddypress' ),
		'capability' => 'manage_options',
		'menu_slug'  => 'bp-wizard',
		'function'   => '',
		'position'   => 3
	) );

	$hook = add_submenu_page( 'bp-wizard', $status, $status, 'manage_options', 'bp-wizard', array( $bp_wizard, 'html' ) );

	// Add a hook for css/js
	add_action( "admin_print_styles-$hook", 'bp_core_update_add_admin_menu_styles' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu',  'bp_core_update_add_admin_menu', 9 );

function bp_core_update_add_admin_menu_styles() {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', plugins_url( '/buddypress' ) . '/bp-core/css/admin.dev.css' ), array(), BP_VERSION );
	else
		wp_enqueue_style( 'bp-admin-css', apply_filters( 'bp_core_admin_css', plugins_url( '/buddypress' ) . '/bp-core/css/admin.css' ), array(), BP_VERSION );

	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' ); ?>

	<style type="text/css">
		/* Wizard Icon */
		ul#adminmenu li.toplevel_page_bp-wizard .wp-menu-image a img { display: none; }
		ul#adminmenu li.toplevel_page_bp-wizard .wp-menu-image a { background-image: url( <?php echo plugins_url( 'buddypress/bp-core/images/admin_menu_icon.png' ) ?> ) !important; background-position: -1px -32px; }
		ul#adminmenu li.toplevel_page_bp-wizard:hover .wp-menu-image a,
		ul#adminmenu li.toplevel_page_bp-wizard.wp-has-current-submenu .wp-menu-image a {
			background-position: -1px 0;
		}

		/* Settings Icon */
		ul#adminmenu li.toplevel_page_bp-general-settings .wp-menu-image a img { display: none; }
		ul#adminmenu li.toplevel_page_bp-general-settings .wp-menu-image a { background-image: url( <?php echo plugins_url( 'buddypress/bp-core/images/admin_menu_icon.png' ) ?> ) !important; background-position: -1px -32px; }
		ul#adminmenu li.toplevel_page_bp-general-settings:hover .wp-menu-image a,
		ul#adminmenu li.toplevel_page_bp-general-settings.wp-has-current-submenu .wp-menu-image a {
			background-position: -1px 0;
		}
	</style>

<?php
}
add_action( 'admin_head', 'bp_core_update_add_admin_menu_styles' );

/**
 * Fetches BP pages from the meta table, depending on setup
 *
 * @package BuddyPress Core
 * @since 1.3
 *
 * @return array $page_ids
 */
function bp_core_update_get_page_meta() {
	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
		$page_ids = get_blog_option( BP_ROOT_BLOG, 'bp-pages' );
	else
		$page_ids = get_option( 'bp-pages' );

	return $page_ids;
}

/**
 * Adds an admin nag about running the BP upgrade/install wizard
 *
 * @package BuddyPress Core
 * @since 1.3
 * @global $pagenow The current admin page
 */
function bp_core_update_nag() {
	global $bp, $pagenow;

	if ( !is_super_admin() )
		return;

	if ( 'admin.php' == $pagenow && ( empty( $_GET['page'] ) || 'bp-wizard' == $_GET['page'] ) )
		return;

	$url = is_multisite() ? network_admin_url( 'admin.php?page=bp-wizard' ) : admin_url( 'admin.php?page=bp-wizard' );

	switch( $bp->maintenence_mode ) {
		case 'update':
			$msg = sprintf( __( 'BuddyPress has been updated! Please run the <a href="%s">update wizard</a>.', 'buddypress' ), $url );
			break;

		default:
		case 'install':
			$msg = sprintf( __( 'BuddyPress was successfully installed! Please run the <a href="%s">installation wizard</a>.', 'buddypress' ), $url );
			break;
	}

	echo '<div class="update-nag">' . $msg . '</div>';
}
add_action( 'admin_notices', 'bp_core_update_nag', 5 );

?>
