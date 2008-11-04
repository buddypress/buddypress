<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; <a href="<?php bp_group_admin_permalink() ?>">Group Admin</a> &raquo; Group Settings</h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<form action="<?php bp_group_admin_form_action('group-settings') ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post">
			
			<div class="checkbox">
				<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php bp_group_show_wire_setting() ?>/> <?php _e('Enable comment wire', 'buddypress') ?></label>
			</div>
			<div class="checkbox">
				<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php bp_group_show_forum_setting() ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
			</div>
			<div class="checkbox with-suboptions">
				<label><input type="checkbox" name="group-show-photos" id="group-show-photos" value="1"<?php bp_group_show_photos_setting() ?> /> <?php _e('Enable photo gallery', 'buddypress') ?></label>
				
				<?php if ( bp_group_photos_enabled() ) : ?>
				<div class="sub-options">
					<label><input type="radio" name="group-photos-status" value="all"<?php bp_group_show_photos_upload_setting('member') ?> /> <?php _e('All members can upload photos', 'buddypress') ?></label>
					<label><input type="radio" name="group-photos-status" value="admins"<?php bp_group_show_photos_upload_setting('admin') ?> /> <?php _e('Only group admins can upload photos', 'buddypress') ?></label>
				</div>
				<?php endif; ?>
			</div>
		
			<h3><?php _e('Privacy Options', 'buddypress'); ?></h3>
		
			<div class="radio">
				<label><input type="radio" name="group-status" value="public"<?php bp_group_show_status_setting('public') ?> /> <strong><?php _e('This is an open group', 'buddypress') ?></strong><br /><?php _e('This group will be free to join and will appear in group search results.', 'buddypress'); ?></label>
				<label><input type="radio" name="group-status" value="private"<?php bp_group_show_status_setting('private') ?> /> <strong><?php _e('This is a closed group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join but will still appear in group search results.', 'buddypress'); ?></label>
				<label><input type="radio" name="group-status" value="hidden"<?php bp_group_show_status_setting('hidden') ?> /> <strong><?php _e('This is a hidden group', 'buddypress') ?></strong><br /><?php _e('This group will require an invite to join and will only be visible to invited members. It will not appear in search results or on member profiles.', 'buddypress'); ?></label>
			</div>

			<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />
			
			<p><input type="submit" value="<?php _e('Save Changes', 'buddypress') ?> &raquo;" id="save" name="save" /></p>
		
		</form>
</div>

<?php endwhile; endif; ?>