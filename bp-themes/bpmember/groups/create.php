<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_creation_tabs(); ?>
	</ul>
</div>

<div id="content">	
	<h2><?php _e( 'Create a Group', 'buddypress' ) ?> <?php bp_group_creation_stage_title() ?></h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<form action="<?php bp_group_creation_form_action() ?>" method="post" id="create-group-form" class="standard-form" enctype="multipart/form-data">
	
		<!-- Group creation step 1: Basic group details -->
		<?php if ( bp_is_group_creation_step( 'group-details' ) ) : ?>

			<label for="group-name">* <?php _e('Group Name', 'buddypress') ?></label>
			<input type="text" name="group-name" id="group-name" value="<?php bp_new_group_name() ?>" />
	
			<label for="group-desc">* <?php _e('Group Description', 'buddypress') ?></label>
			<textarea name="group-desc" id="group-desc"><?php bp_new_group_description() ?></textarea>
	
			<label for="group-news"><?php _e('Recent News', 'buddypress') ?></label>
			<textarea name="group-news" id="group-news"><?php bp_new_group_news() ?></textarea>
		
			<?php do_action( 'groups_custom_group_fields_editable' ) ?>

			<?php wp_nonce_field( 'groups_create_save_group-details' ) ?>

		<?php endif; ?>

		<!-- Group creation step 2: Group settings -->		
		<?php if ( bp_is_group_creation_step( 'group-settings' ) ) : ?>

			<?php if ( function_exists('bp_wire_install') ) : ?>
			<div class="checkbox">
				<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php if ( bp_get_new_group_enable_wire() ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
			</div>
			<?php endif; ?>
	
			<?php if ( function_exists('bp_forums_setup') ) : ?>
				<?php if ( bp_forums_is_installed_correctly() ) : ?>
					<div class="checkbox">
						<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php if ( bp_get_new_group_enable_forum() ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
					</div>
				<?php else : ?>
					<?php if ( is_site_admin() ) : ?>
						<div class="checkbox">
							<label><input type="checkbox" disabled="disabled" name="disabled" id="disabled" value="0" /> <?php printf( __('<strong>Attention Site Admin:</strong> Group forums require the <a href="%s">correct setup and configuration</a> of a bbPress installation.', 'buddypress' ), $bp->root_domain . '/wp-admin/admin.php?page=' . BP_PLUGIN_DIR . '/bp-forums/bp-forums-admin.php' ) ?></label>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>

			<h3><?php _e( 'Privacy Options', 'buddypress' ); ?></h3>

			<div class="radio">
				<label><input type="radio" name="group-status" value="public"<?php if ( 'public' == bp_get_new_group_status() || !bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> /> 
					<strong><?php _e( 'This is a public group', 'buddypress' ) ?></strong>
					<ul>
						<li><?php _e( 'Any site member can join this group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will be visible to any site member.', 'buddypress' ) ?></li>
					</ul>
				</label>
		
				<label><input type="radio" name="group-status" value="private"<?php if ( 'private' == bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> />
					<strong><?php _e( 'This is a private group', 'buddypress' ) ?></strong>
					<ul>
						<li><?php _e( 'Only users who request membership and are accepted can join the group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
					</ul>
				</label>
		
				<label><input type="radio" name="group-status" value="hidden"<?php if ( 'hidden' == bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> />
					<strong><?php _e('This is a hidden group', 'buddypress') ?></strong>
					<ul>
						<li><?php _e( 'Only users who are invited can join the group.', 'buddypress' ) ?></li>
						<li><?php _e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ) ?></li>
						<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
					</ul>
				</label>
			</div>

			<?php wp_nonce_field( 'groups_create_save_group-settings' ) ?>
	
		<?php endif; ?>
		
		<!-- Group creation step 3: Avatar Uploads -->	
		<?php if ( bp_is_group_creation_step( 'group-avatar' ) ) : ?>

			<div class="left-menu">
				<?php bp_new_group_avatar( 'type=full' ) ?>
			</div>
		
			<div class="main-column">
				<p><?php _e("Upload an image to use as an avatar for this group. The image will be shown on the main group page, and in search results.", 'buddypress') ?></p>
			
				<?php bp_new_group_avatar_upload_form() ?>
				
				<p><?php _e( 'To skip the avatar upload process or move onto the next step, hit the "Next Step" button.', 'buddypress' ) ?></p>
			</div>

			<?php wp_nonce_field( 'groups_create_save_group-avatar' ) ?>
		
		<?php endif; ?>
		
		<!-- Group creation step 4: Invite friends to group -->	
		<?php if ( bp_is_group_creation_step( 'group-invites' ) ) : ?>

			<div class="left-menu">
				
				<h4><?php _e( 'Select Friends', 'buddypress' ) ?> <img id="ajax-loader" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></h4>
				
				<div id="invite-list">
					<ul>
						<?php bp_new_group_invite_friend_list() ?>
					</ul>
					
					<?php wp_nonce_field( 'groups_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
				</div>
				
			</div>

			<div class="main-column">
		
				<div id="message" class="info">
					<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
				</div>

				<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
				<ul id="friend-list" class="item-list">
				<?php if ( bp_group_has_invites() ) : ?>
					
					<?php while ( bp_group_invites() ) : bp_group_the_invite(); ?>

						<li id="<?php bp_group_invite_item_id() ?>">
							<?php bp_group_invite_user_avatar() ?>
							
							<h4><?php bp_group_invite_user_link() ?></h4>
							<span class="activity"><?php bp_group_invite_user_last_active() ?></span>
							
							<div class="action">
								<a class="remove" href="<?php bp_group_invite_user_remove_invite_url() ?>" id="<?php bp_group_invite_item_id() ?>"><?php _e( 'Remove Invite', 'buddypress' ) ?></a> 
							</div>
						</li>

					<?php endwhile; ?>
					
					<?php wp_nonce_field( 'groups_send_invites', '_wpnonce_send_invites' ) ?>
				<?php endif; ?>
				</ul>
	
				<?php wp_nonce_field( 'groups_create_save_group-invites' ) ?>
					
		<?php endif; ?>
		
		<?php do_action( 'groups_custom_create_steps' ) // Allow plugins to add custom group creation steps ?>
		
		<div id="previous-next">
			<!-- Previous Button -->
			<?php if ( !bp_is_first_group_creation_step() ) : ?>
				<input type="button" value="&larr; <?php _e('Previous Step', 'buddypress') ?>" id="group-creation-previous" name="previous" onclick="location.href='<?php bp_group_creation_previous_link() ?>'" />
			<?php endif; ?>

			<!-- Next Button -->
			<?php if ( !bp_is_last_group_creation_step() && !bp_is_first_group_creation_step() ) : ?>
				<input type="submit" value="<?php _e('Next Step', 'buddypress') ?> &rarr;" id="group-creation-next" name="save" />
			<?php endif;?>
			
			<!-- Create Button -->
			<?php if ( bp_is_first_group_creation_step() ) : ?>
				<input type="submit" value="<?php _e('Create Group and Continue', 'buddypress') ?> &rarr;" id="group-creation-create" name="save" />
			<?php endif; ?>
			
			<!-- Finish Button -->
			<?php if ( bp_is_last_group_creation_step() ) : ?>
				<input type="submit" value="<?php _e('Finish', 'buddypress') ?> &rarr;" id="group-creation-finish" name="save" />
			<?php endif; ?>
		</div>

		<!-- Don't leave out this hidden field -->
		<input type="hidden" name="group_id" id="group_id" value="<?php bp_new_group_id() ?>" />
	</form>
	
</div>

<?php get_footer() ?>