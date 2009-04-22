<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><?php _e( 'Group Settings', 'buddypress' ); ?></h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<form action="<?php bp_group_admin_form_action('group-settings') ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post">
			
			<?php if ( function_exists('bp_wire_install') ) : ?>
			<div class="checkbox">
				<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php bp_group_show_wire_setting() ?>/> <?php _e( 'Enable comment wire', 'buddypress' ) ?></label>
			</div>
			<?php endif; ?>
			
			<?php if ( function_exists('bp_forums_setup') ) : ?>
				<?php if ( bp_forums_is_installed_correctly() ) : ?>
					<div class="checkbox">
						<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php bp_group_show_forum_setting() ?> /> <?php _e( 'Enable discussion forum', 'buddypress' ) ?></label>
					</div>
				<?php endif; ?>
			<?php endif; ?>
			
			<?php if ( function_exists('bp_albums_install') ) : ?>
			<div class="checkbox with-suboptions">
				<label><input type="checkbox" name="group-show-photos" id="group-show-photos" value="1"<?php bp_group_show_photos_setting() ?> /> <?php _e( 'Enable photo gallery', 'buddypress' ) ?></label>
				
				<?php if ( bp_group_photos_enabled() ) : ?>
				<div class="sub-options">
					<label><input type="radio" name="group-photos-status" value="all"<?php bp_group_show_photos_upload_setting('member') ?> /> <?php _e( 'All members can upload photos', 'buddypress' ) ?></label>
					<label><input type="radio" name="group-photos-status" value="admins"<?php bp_group_show_photos_upload_setting('admin') ?> /> <?php _e( 'Only group admins can upload photos', 'buddypress' ) ?></label>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<h3><?php _e( 'Privacy Options', 'buddypress' ); ?></h3>
		
			<div class="radio">
				<label>
					<input type="radio" name="group-status" value="public"<?php bp_group_show_status_setting('public') ?> /> 
					<strong><?php _e( 'This is a public group', 'buddypress' ) ?></strong>
					<ul>
						<li><?php _e( 'Any site member can join this group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will be visible to any site member.', 'buddypress' ) ?></li>
					</ul>
				</label>
				
				<label>
					<input type="radio" name="group-status" value="private"<?php bp_group_show_status_setting('private') ?> />
					<strong><?php _e( 'This is a private group', 'buddypress' ) ?></strong>
					<ul>
						<li><?php _e( 'Only users who request membership and are accepted can join the group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
					</ul>
				</label>
				
				<label>
					<input type="radio" name="group-status" value="hidden"<?php bp_group_show_status_setting('hidden') ?> />
					<?php _e( 'This is a hidden group', 'buddypress' ) ?></strong>
					<ul>
						<li><?php _e( 'Only users who are invited can join the group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
					</ul>
				</label>
			</div>
			
			<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />
			
			<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &raquo;" id="save" name="save" /></p>
			
			<?php wp_nonce_field( 'groups_edit_group_settings' ) ?>
		</form>
</div>

<?php endwhile; endif; ?>

<?php get_footer() ?>
