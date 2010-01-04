<?php get_header() ?>

	<?php do_action( 'bp_before_directory_activity_content' ) ?>

	<div id="content">
		<div class="padder">

			<?php if ( !is_user_logged_in() ) : ?>
				<h2><?php _e( 'Site Activity', 'buddypress' ) ?></h2>
			<?php endif; ?>

			<div class="widget_bp_activity_widget">

				<?php if ( is_user_logged_in() ) : ?>
					<?php locate_template( array( 'activity/post-form.php'), true ) ?>
				<?php endif; ?>

				<div class="clear"></div>

				<?php do_action( 'template_notices' ) ?>

				<div class="item-list-tabs">
					<ul>
						<li class="selected" id="activity-all"><a href="<?php bp_root_domain() ?>"><?php printf( __( 'All Members (%s)', 'buddypress' ), bp_get_total_site_member_count() ) ?></a></li>

						<?php if ( is_user_logged_in() ) : ?>

							<?php if ( bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>
								<li id="activity-friends"><a href="<?php echo bp_loggedin_user_domain() . BP_ACTIVITY_SLUG . '/my-friends/' ?>"><?php printf( __( 'My Friends (%s)', 'buddypress' ), bp_get_total_friend_count( bp_loggedin_user_id() ) ) ?></a></li>
							<?php endif; ?>

							<?php if ( bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) : ?>
								<li id="activity-groups"><a href="<?php echo bp_loggedin_user_domain() . BP_ACTIVITY_SLUG . '/my-groups/' ?>"><?php printf( __( 'My Groups (%s)', 'buddypress' ), bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) ?></a></li>
							<?php endif; ?>

							<?php if ( bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) : ?>
								<li id="activity-favorites"><a href="<?php echo bp_loggedin_user_domain() . BP_ACTIVITY_SLUG . '/my-favorites/' ?>"><?php printf( __( 'My Favorites (<span>%s</span>)', 'buddypress' ), bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) ?></a></li>
							<?php endif; ?>

						<?php endif; ?>

						<?php do_action( 'bp_activity_type_tabs' ) ?>

						<li id="activity-filter-select" class="last">
							<select>
								<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
								<option value="new_wire_post"><?php _e( 'Show Updates', 'buddypress' ) ?></option>
								<option value="new_blog_post"><?php _e( 'Show Blog Posts', 'buddypress' ) ?></option>
								<option value="new_blog_comment"><?php _e( 'Show Blog Comments', 'buddypress' ) ?></option>
								<option value="new_forum_topic"><?php _e( 'Show New Forum Topics', 'buddypress' ) ?></option>
								<option value="new_forum_post"><?php _e( 'Show Forum Replies', 'buddypress' ) ?></option>
								<option value="created_group"><?php _e( 'Show New Groups', 'buddypress' ) ?></option>
								<option value="joined_group"><?php _e( 'Show New Group Memberships', 'buddypress' ) ?></option>
								<option value="friendship_accepted,friendship_created"><?php _e( 'Show Friendship Connections', 'buddypress' ) ?></option>
								<option value="new_member"><?php _e( 'Show New Members', 'buddypress' ) ?></option>

								<?php do_action( 'bp_activity_filter_options' ) ?>
							</select>
						</li>
					</ul>
				</div>

				<div class="activity">
					<?php // The loop will be loaded here via AJAX on page load to retain selected settings. ?>
				</div>

				<form action="" name="activity-widget-form" id="activity-widget-form" method="post">
					<?php wp_nonce_field( 'activity_filter', '_wpnonce_activity_filter' ) ?>
					<input type="hidden" id="aw-querystring" name="aw-querystring" value="" />
					<input type="hidden" id="aw-oldestpage" name="aw-oldestpage" value="1" />
				</form>

			</div><!-- .widget_bp_activity_widget -->

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_activity_content' ) ?>

<?php get_footer() ?>