<div class="item-list-tabs" id="user-subnav">
	<ul>
		<li id="activity-filter-select" class="last">
			<select>
				<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
				<option value="new_wire_post"><?php _e( 'Updates Only', 'buddypress' ) ?></option>
				<option value="new_forum_post,new_forum_topic"><?php _e( 'Group Forum Activity Only', 'buddypress' ) ?></option>
				<option value="new_blog_post,new_blog_comment"><?php _e( 'Blog Activity Only', 'buddypress' ) ?></option>

				<?php do_action( 'bp_activity_filter_options' ) ?>
			</select>
		</li>
	</ul>
</div>

<?php if ( is_user_logged_in() && bp_group_is_member() ) : ?>
	<?php locate_template( array( 'activity/post-form.php'), true ) ?>
<?php endif; ?>

<div class="activity single-group">
	<?php // 'activity/activity-loop.php' loaded here via AJAX. ?>
</div>
