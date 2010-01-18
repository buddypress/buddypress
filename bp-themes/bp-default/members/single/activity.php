<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<?php bp_get_options_nav() ?>

		<li class="feed"><a href="<?php bp_activities_member_rss_link() ?>" title="RSS Feed"><?php _e( 'RSS', 'buddypress' ) ?></a></li>

		<?php do_action( 'bp_member_activity_syndication_options' ) ?>

		<li id="activity-filter-select" class="last">
			<select>
				<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
				<option value="activity_update"><?php _e( 'Show Updates', 'buddypress' ) ?></option>
				<option value="new_blog_post"><?php _e( 'Show Blog Posts', 'buddypress' ) ?></option>
				<option value="new_blog_comment"><?php _e( 'Show Blog Comments', 'buddypress' ) ?></option>
				<option value="new_forum_topic"><?php _e( 'Show New Forum Topics', 'buddypress' ) ?></option>
				<option value="new_forum_post"><?php _e( 'Show Forum Replies', 'buddypress' ) ?></option>
				<option value="created_group"><?php _e( 'Show New Groups', 'buddypress' ) ?></option>
				<option value="joined_group"><?php _e( 'Show New Group Memberships', 'buddypress' ) ?></option>
				<option value="friendship_accepted,friendship_created"><?php _e( 'Show Friendship Connections', 'buddypress' ) ?></option>

				<?php do_action( 'bp_member_activity_filter_options' ) ?>
			</select>
		</li>
	</ul>
</div><!-- .item-list-tabs -->

<?php do_action( 'bp_before_member_activity_post_form' ) ?>

<?php if ( is_user_logged_in() && bp_is_my_profile() ) : ?>
	<?php locate_template( array( 'activity/post-form.php'), true ) ?>
<?php endif; ?>

<?php do_action( 'bp_after_member_activity_post_form' ) ?>
<?php do_action( 'bp_before_member_activity_content' ) ?>

<div class="activity">
	<?php
		// The loop will be loaded here via AJAX on page load to retain selected settings and not waste cycles.
		// If you're concerned about no-script functionality, uncomment the following line.

		// locate_template( array( 'activity/activity-loop.php' ), true );
	?>
</div><!-- .activity -->

<?php do_action( 'bp_after_member_activity_content' ) ?>
