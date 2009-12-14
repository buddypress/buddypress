<div class="item-list-tabs no-ajax" id="group-subnav">
	<ul>
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<?php do_action( 'bp_before_group_admin_content' ) ?>

<form action="<?php bp_group_admin_form_action() ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post" enctype="multipart/form-data">

<?php do_action( 'template_notices' ) ?>

<?php /* Edit Group Details */ ?>
<?php if ( bp_is_group_admin_screen( 'edit-details' ) ) : ?>

	<?php do_action( 'bp_before_group_details_admin' ); ?>

	<label for="group-name">* <?php _e( 'Group Name', 'buddypress' ) ?></label>
	<input type="text" name="group-name" id="group-name" value="<?php bp_group_name() ?>" />

	<label for="group-desc">* <?php _e( 'Group Description', 'buddypress' ) ?></label>
	<textarea name="group-desc" id="group-desc"><?php bp_group_description_editable() ?></textarea>

	<label for="group-news"><?php _e( 'Recent News', 'buddypress' ) ?></label>
	<textarea name="group-news" id="group-news"><?php bp_group_news_editable() ?></textarea>

	<?php do_action( 'groups_custom_group_fields_editable' ) ?>

	<p>
		<label for="group-notifiy-members"><?php _e( 'Notify group members of changes via email', 'buddypress' ); ?></label>
		<input type="radio" name="group-notify-members" value="1" /> <?php _e( 'Yes', 'buddypress' ); ?>&nbsp;
		<input type="radio" name="group-notify-members" value="0" checked="checked" /> <?php _e( 'No', 'buddypress' ); ?>&nbsp;
	</p>

	<?php do_action( 'bp_after_group_details_admin' ); ?>

	<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &rarr;" id="save" name="save" /></p>
	<?php wp_nonce_field( 'groups_edit_group_details' ) ?>

<?php endif; ?>

<?php /* Manage Group Settings */ ?>
<?php if ( bp_is_group_admin_screen( 'group-settings' ) ) : ?>

	<?php do_action( 'bp_before_group_settings_admin' ); ?>

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
			<strong><?php _e( 'This is a hidden group', 'buddypress' ) ?></strong>
			<ul>
				<li><?php _e( 'Only users who are invited can join the group.', 'buddypress' ) ?></li>
				<li><?php _e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ) ?></li>
				<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
			</ul>
		</label>
	</div>

	<?php do_action( 'bp_after_group_settings_admin' ); ?>

	<p><input type="submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> &rarr;" id="save" name="save" /></p>
	<?php wp_nonce_field( 'groups_edit_group_settings' ) ?>

<?php endif; ?>

<?php /* Group Avatar Settings */ ?>
<?php if ( bp_is_group_admin_screen( 'group-avatar' ) ) : ?>

	<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>

			<p><?php _e("Upload an image to use as an avatar for this group. The image will be shown on the main group page, and in search results.", 'buddypress') ?></p>

			<p>
				<input type="file" name="file" id="file" />
				<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
				<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
			</p>

			<?php if ( bp_get_group_has_avatar() ) : ?>
				<p><?php _e( "If you'd like to remove the existing avatar but not upload a new one, please use the delete avatar button.", 'buddypress' ) ?></p>

				<div class="generic-button" id="delete-group-avatar-button">
					<a class="edit" href="<?php bp_group_avatar_delete_link() ?>" title="<?php _e( 'Delete Avatar', 'buddypress' ) ?>"><?php _e( 'Delete Avatar', 'buddypress' ) ?></a>
				</div>
			<?php endif; ?>

			<?php wp_nonce_field( 'bp_avatar_upload' ) ?>

	<?php endif; ?>

	<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

		<h3><?php _e( 'Crop Avatar', 'buddypress' ) ?></h3>

		<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

		<div id="avatar-crop-pane">
			<img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e( 'Avatar preview', 'buddypress' ) ?>" />
		</div>

		<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />

		<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
		<input type="hidden" id="x" name="x" />
		<input type="hidden" id="y" name="y" />
		<input type="hidden" id="w" name="w" />
		<input type="hidden" id="h" name="h" />

		<?php wp_nonce_field( 'bp_avatar_cropstore' ) ?>

	<?php endif; ?>

<?php endif; ?>

<?php /* Manage Group Members */ ?>
<?php if ( bp_is_group_admin_screen( 'manage-members' ) ) : ?>

	<?php do_action( 'bp_before_group_manage_members_admin' ); ?>

	<div class="bp-widget">
		<h4><?php _e( 'Administrators', 'buddypress' ); ?></h4>
		<?php bp_group_admin_memberlist( true ) ?>
	</div>

	<?php if ( bp_group_has_moderators() ) : ?>

		<div class="bp-widget">
			<h4><?php _e( 'Moderators', 'buddypress' ) ?></h4>
			<?php bp_group_mod_memberlist( true ) ?>
		</div>

	<?php endif; ?>

	<div class="bp-widget">
		<h4><?php _e("Members", "buddypress"); ?></h4>

		<?php if ( bp_group_has_members( 'per_page=15&exclude_banned=false' ) ) : ?>

			<?php if ( bp_group_member_needs_pagination() ) : ?>

				<div class="pagination">

					<div id="member-count" class="pag-count">
						<?php bp_group_member_pagination_count() ?>
					</div>

					<div id="member-admin-pagination" class="pagination-links">
						<?php bp_group_member_admin_pagination() ?>
					</div>

				</div>

			<?php endif; ?>

			<ul id="members-list" class="item-list single-line">
				<?php while ( bp_group_members() ) : bp_group_the_member(); ?>

					<?php if ( bp_get_group_member_is_banned() ) : ?>

						<li class="banned-user">
							<?php bp_group_member_avatar_mini() ?>

							<h5><?php bp_group_member_link() ?> <?php _e( '(banned)', 'buddypress') ?> <span class="small"> &mdash; <a href="<?php bp_group_member_unban_link() ?>" class="confirm" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Remove Ban', 'buddypress' ); ?></a> </h5>

					<?php else : ?>

						<li>
							<?php bp_group_member_avatar_mini() ?>
							<h5><?php bp_group_member_link() ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>" class="confirm" title="<?php _e( 'Kick and ban this member', 'buddypress' ); ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ); ?></a> | <a href="<?php bp_group_member_promote_mod_link() ?>" class="confirm" title="<?php _e( 'Promote to Mod', 'buddypress' ); ?>"><?php _e( 'Promote to Mod', 'buddypress' ); ?></a> | <a href="<?php bp_group_member_promote_admin_link() ?>" class="confirm" title="<?php _e( 'Promote to Admin', 'buddypress' ); ?>"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a></span></h5>

					<?php endif; ?>

							<?php do_action( 'bp_group_manage_members_admin_item' ); ?>
						</li>

				<?php endwhile; ?>
			</ul>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'This group has no members.', 'buddypress' ); ?></p>
			</div>

		<?php endif; ?>

	</div>

	<?php do_action( 'bp_after_group_manage_members_admin' ); ?>

<?php endif; ?>

<?php /* Manage Membership Requests */ ?>
<?php if ( bp_is_group_admin_screen( 'membership-requests' ) ) : ?>

	<?php do_action( 'bp_before_group_membership_requests_admin' ); ?>

	<?php if ( bp_group_has_membership_requests() ) : ?>

		<ul id="request-list" class="item-list">
			<?php while ( bp_group_membership_requests() ) : bp_group_the_membership_request(); ?>

				<li>
					<?php bp_group_request_user_avatar_thumb() ?>
					<h4><?php bp_group_request_user_link() ?> <span class="comments"><?php bp_group_request_comment() ?></span></h4>
					<span class="activity"><?php bp_group_request_time_since_requested() ?></span>

					<?php do_action( 'bp_group_membership_requests_admin_item' ); ?>

					<div class="action">

						<div class="generic-button accept">
							<a href="<?php bp_group_request_accept_link() ?>"><?php _e( 'Accept', 'buddypress' ); ?></a>
						</div>

					 &nbsp;

						<div class="generic-button reject">
							<a href="<?php bp_group_request_reject_link() ?>"><?php _e( 'Reject', 'buddypress' ); ?></a>
						</div>

						<?php do_action( 'bp_group_membership_requests_admin_item_action' ); ?>

					</div>
				</li>

			<?php endwhile; ?>
		</ul>

	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'There are no pending membership requests.', 'buddypress' ); ?></p>
		</div>

	<?php endif; ?>

	<?php do_action( 'bp_after_group_membership_requests_admin' ); ?>

<?php endif; ?>

<?php do_action( 'groups_custom_edit_steps' ) // Allow plugins to add custom group edit screens ?>

<?php /* Delete Group Option */ ?>
<?php if ( bp_is_group_admin_screen( 'delete-group' ) ) : ?>

	<?php do_action( 'bp_before_group_delete_admin' ); ?>

	<div id="message" class="info">
		<p><?php _e( 'WARNING: Deleting this group will completely remove ALL content associated with it. There is no way back, please be careful with this option.', 'buddypress' ); ?></p>
	</div>

	<input type="checkbox" name="delete-group-understand" id="delete-group-understand" value="1" onclick="if(this.checked) { document.getElementById('delete-group-button').disabled = ''; } else { document.getElementById('delete-group-button').disabled = 'disabled'; }" /> <?php _e( 'I understand the consequences of deleting this group.', 'buddypress' ); ?>

	<?php do_action( 'bp_after_group_delete_admin' ); ?>

	<div class="submit">
		<input type="submit" disabled="disabled" value="<?php _e( 'Delete Group', 'buddypress' ) ?> &rarr;" id="delete-group-button" name="delete-group-button" />
	</div>

	<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />

	<?php wp_nonce_field( 'groups_delete_group' ) ?>

<?php endif; ?>

<?php /* This is important, don't forget it */ ?>
<input type="hidden" name="group-id" id="group-id" value="<?php bp_group_id() ?>" />

</form>

<?php do_action( 'bp_after_group_admin_content' ) ?>