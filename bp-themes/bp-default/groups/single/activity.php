<div class="item-list-tabs no-ajax" id="user-subnav">
	<ul>
		<li class="feed"><a href="<?php bp_group_activity_feed_link() ?>" title="RSS Feed"><?php _e( 'RSS', 'buddypress' ) ?></a></li>

		<?php do_action('bp_activity_group_syndication_options') ?>

		<li id="activity-filter-select" class="last">
			<select>
				<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
				<option value="new_wire_post"><?php _e( 'Show Updates', 'buddypress' ) ?></option>
				<option value="new_forum_topic"><?php _e( 'Show New Forum Topics', 'buddypress' ) ?></option>
				<option value="new_forum_post"><?php _e( 'Show Forum Replies', 'buddypress' ) ?></option>
				<option value="joined_group"><?php _e( 'Show New Group Memberships', 'buddypress' ) ?></option>

				<?php do_action( 'bp_activity_group_filter_options' ) ?>
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
