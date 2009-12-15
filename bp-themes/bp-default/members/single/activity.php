<div class="item-list-tabs" id="user-subnav">
	<ul>
		<li id="activity-filter-select" class="last">
			<select>
				<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
				<option value="new_wire_post"><?php _e( 'Updates Only', 'buddypress' ) ?></option>
				<option value="new_blog_post"><?php _e( 'New Blog Posts Only', 'buddypress' ) ?></option>
				<option value="new_blog_comment"><?php _e( 'New Blog Comments Only', 'buddypress' ) ?></option>
				<option value="new_forum_topic"><?php _e( 'New Group Forum Topics Only', 'buddypress' ) ?></option>
				<option value="new_forum_post"><?php _e( 'New Group Forum Replies Only', 'buddypress' ) ?></option>
				<option value="friendship_accepted,friendship_created"><?php _e( 'New Friendships Only', 'buddypress' ) ?></option>

				<?php do_action( 'bp_activity_filter_options' ) ?>
			</select>
		</li>
	</ul>
</div>

<?php if ( is_user_logged_in() && bp_is_my_profile() ) : ?>
	<?php locate_template( array( 'activity/post-form.php'), true ) ?>
<?php endif; ?>

<div class="activity">
	<?php // 'activity/activity-loop.php' loaded here via AJAX. ?>
</div>

<?php do_action( 'bp_directory_members_content' ) ?>