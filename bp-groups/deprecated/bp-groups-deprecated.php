<?php
/***
 * Deprecated Groups Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are 
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run, 
 * and you will miss out on lovely performance and functionality improvements.
 * 
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

function groups_deprecated_globals() {
	global $bp;
	
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	$bp->groups->image_base = BP_PLUGIN_URL . '/bp-groups/deprecated/images';
}
add_action( 'plugins_loaded', 'groups_deprecated_globals', 5 );	
add_action( 'admin_menu', 'groups_deprecated_globals', 2 );

function groups_avatar_upload( $deprecated = true ) { ?>

	<?php if ( !bp_get_avatar_admin_step() ) : ?>

		<p>
			<input type="file" name="file" id="file" /> 
			<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
			<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
		</p>

	<?php endif; ?>

	<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

		<h3><?php _e( 'Crop Group Avatar', 'buddypress' ) ?></h3>
	
		<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

		<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />
	
		<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
		<input type="hidden" name="upload" id="upload" />
		<input type="hidden" id="x" name="x" />
		<input type="hidden" id="y" name="y" />
		<input type="hidden" id="w" name="w" />
		<input type="hidden" id="h" name="h" />

	<?php endif; ?>
<?php
}

function groups_get_avatar_hrefs( $avatars ) {
	global $bp;
	
	$src = $bp->root_domain . '/';

	$thumb_href = str_replace( ABSPATH, $src, stripslashes( $avatars['v1_out'] ) );
	$full_href = str_replace( ABSPATH, $src, stripslashes ( $avatars['v2_out'] ) );
	
	return array( 'thumb_href' => $thumb_href, 'full_href' => $full_href );
}

function groups_get_avatar_path( $avatar ) {
	global $bp;

	$src = $bp->root_domain . '/';

	$path = str_replace( $src, ABSPATH, stripslashes( $avatar ) );
	return $path;
}

function bp_new_group_avatar_upload_form() {
	groups_avatar_upload();
}

/* DEPRECATED - BuddyPress templates are now merged with WordPress themes */
function groups_force_buddypress_theme( $template ) {
	global $bp;
	
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	if ( $bp->current_component != $bp->groups->slug )
	        return $template;

	$member_theme = get_site_option('active-member-theme');

	if ( empty($member_theme) )
	        $member_theme = 'bpmember';

	add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

	return $member_theme;
}
add_filter( 'template', 'groups_force_buddypress_theme' );

/* DEPRECATED - BuddyPress templates are now merged with WordPress themes */
function groups_force_buddypress_stylesheet( $stylesheet ) {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
		
	if ( $bp->current_component != $bp->groups->slug )
		return $stylesheet;

	$member_theme = get_site_option('active-member-theme');

	if ( empty( $member_theme ) )
	        $member_theme = 'bpmember';

	add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

	return $member_theme;
}
add_filter( 'stylesheet', 'groups_force_buddypress_stylesheet', 1, 1 );

function groups_add_js() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( $bp->current_component == $bp->groups->slug )
		wp_enqueue_script( 'bp-groups-js', BP_PLUGIN_URL . '/bp-groups/deprecated/js/general.js' );
}
add_action( 'template_redirect', 'groups_add_js', 1 );

function groups_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-groups-structure', BP_PLUGIN_URL . '/bp-groups/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'groups_add_structure_css' );

function groups_add_directory_js() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	wp_enqueue_script( 'bp-groups-directory-groups', BP_PLUGIN_URL . '/bp-groups/deprecated/js/directory-groups.js', array( 'jquery', 'jquery-livequery-pack' ) );
}
add_action( 'groups_directory_groups_setup', 'groups_add_directory_js' );

/* DEPRECATED - use bp_has_groups( 'type=random' ) template loop */
function bp_groups_random_selection( $total_groups = 5 ) {
	global $bp;
	
	if ( !$group_ids = wp_cache_get( 'groups_random_groups', 'bp' ) ) {
		$group_ids = BP_Groups_Group::get_random( $total_groups, 1 );
		wp_cache_set( 'groups_random_groups', $group_ids, 'bp' );
	}
?>	
	<?php if ( $group_ids['groups'] ) { ?>
		<ul class="item-list" id="random-groups-list">
		<?php 
			for ( $i = 0; $i < count( $group_ids['groups'] ); $i++ ) { 
				if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_ids['groups'][$i]->group_id, 'bp' ) ) {
					$group = new BP_Groups_Group( $group_ids['groups'][$i]->group_id, false, false );
					wp_cache_set( 'groups_group_nouserdata_' . $group_ids['groups'][$i]->group_id, $group, 'bp' );
				}
			?>	
			<li>
				<div class="item-avatar">
					<a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_avatar_thumb( $group ) ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_name( $group ) ?></a></div>
					<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( groups_get_groupmeta( $group->id, 'last_activity' ), __( 'active %s ago', 'buddypress' ) ) ?></span></div>
					<div class="item-meta desc"><?php echo bp_create_excerpt( $group->description ) ?></div>
				</div>
				
				<div class="action">
					<?php bp_group_join_button( $group ) ?>
					<div class="meta">
						<?php $member_count = groups_get_groupmeta( $group->id, 'total_member_count' ) ?>
						<?php echo ucwords($group->status) ?> <?php _e( 'Group', 'buddypress' ) ?> / 
						<?php if ( 1 == $member_count ) : ?>
							<?php printf( __( '%d member', 'buddypress' ), $member_count ) ?>
						<?php else : ?>
							<?php printf( __( '%d members', 'buddypress' ), $member_count ) ?>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="clear"></div>
			</li>
		<?php } ?>
		</ul>
	<?php } else { ?>
		<div id="message" class="info">
			<p><?php _e( "There aren't enough groups to show a random sample just yet.", 'buddypress' ) ?></p>
		</div>		
	<?php } ?>
<?php
}

/* DEPRECATED - use bp_has_groups( 'type=random' ) template loop */
function bp_groups_random_groups( $total_groups = 5 ) {
	global $bp;
	
	if ( !$group_ids = wp_cache_get( 'groups_random_user_groups_' . $bp->displayed_user->id . '_' . $total_groups, 'bp' ) ) {
		$group_ids = groups_get_random_groups_for_user( $bp->displayed_user->id, $total_groups, 1 );
		wp_cache_set( 'groups_random_user_groups_' . $bp->displayed_user->id . '_' . $total_groups, $group_ids, 'bp' );
	}
	
?>	
	<div class="info-group">
		<h4><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> (<?php echo BP_Groups_Member::total_group_count() ?>) <a href="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>"><?php _e('See All', 'buddypress') ?> &raquo;</a></h4>
		<?php if ( $group_ids ) { ?>
			<ul class="horiz-gallery">
			<?php 
			for ( $i = 0; $i < count( $group_ids ); $i++ ) {
				if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_ids[$i], 'bp' ) ) {
					$group = new BP_Groups_Group( $group_ids[$i], false, false );
					wp_cache_set( 'groups_group_nouserdata_' . $group_ids[$i], $group, 'bp' );
				}
			?>				<li>
					<a href="<?php echo bp_get_group_permalink( $group ) ?>"><img src="<?php echo attribute_escape( $group->avatar_thumb ); ?>" class="avatar" alt="<?php _e( 'Group Avatar', 'buddypress' ) ?>" /></a>
					<h5><a href="<?php echo bp_get_group_permalink( $group ) ?>"><?php echo attribute_escape( $group->name ) ?></a></h5>
				</li>
			<?php } ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "You haven't joined any groups yet.", 'buddypress' ), __( "%s hasn't joined any groups yet.", 'buddypress' ) ) ?></p>
			</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
<?php
}

/* DEPRECATED - use group invite template loop (see groups/create.php in skeleton BuddyPress theme) */
function bp_group_send_invite_form( $group = false ) {
	global $bp, $groups_template, $invites;
	
	if ( !$group )
		$group =& $groups_template->group;
?>
	<div class="left-menu">
		<h4><?php _e( 'Select Friends', 'buddypress' ) ?> <img id="ajax-loader" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="Loading" style="display: none;" /></h4>
		<?php bp_group_list_invite_friends() ?>
		<?php wp_nonce_field( 'groups_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
		<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $group->id ) ?>" />
	</div>

	<div class="main-column">
		
		<div id="message" class="info">
			<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
		</div>

		<?php $invites = groups_get_invites_for_group( $bp->loggedin_user->id, $group->id ) ?>
		
		<ul id="friend-list" class="item-list">
			<?php for ( $i = 0; $i < count($invites); $i++ ) {
				if ( !$user = wp_cache_get( 'bp_user_' . $invites[$i], 'bp' ) ) {
					$user = new BP_Core_User( $invites[$i] );
					wp_cache_set( 'bp_user_' . $invites[$i], $user, 'bp' );
				}
				?>
				<li id="uid-<?php echo $user->id ?>">
					<?php echo $user->avatar_thumb ?>
					<h4><?php echo $user->user_link ?></h4>
					<span class="activity"><?php echo $user->last_active ?></span>
					<div class="action">
						<a class="remove" href="<?php echo wp_nonce_url( site_url( $bp->groups->slug . '/' . $group->id . '/invites/remove/' . $user->id ), 'groups_invite_uninvite_user' ) ?>" id="uid-<?php echo $user->id ?>"><?php _e( 'Remove Invite', 'buddypress' ) ?></a> 
					</div>
				</li>
			<?php } // end for ?>
		</ul>
		
		<?php wp_nonce_field( 'groups_send_invites', '_wpnonce_send_invites' ) ?>
	</div>
<?php
}

/* DEPRECATED - use bp_group_has_invites() template loop */
function bp_group_list_invite_friends( $args = '' ) {
	global $bp, $invites;
			
	if ( !function_exists('friends_install') )
		return false;

		$friends = friends_get_friends_invite_list( $bp->loggedin_user->id, $bp->groups->current_group->id );

		if ( $friends ) {
			$invites = groups_get_invites_for_group( $bp->loggedin_user->id, $bp->groups->current_group->id );

	?>
			<div id="invite-list">
				<ul>
					<?php 
						for ( $i = 0; $i < count( $friends ); $i++ ) {
							if ( $invites ) {
								if ( in_array( $friends[$i]['id'], $invites ) ) {
									$checked = ' checked="checked"';
								} else {
									$checked = '';
								} 
							}
					?>
					
					<li><input<?php echo $checked ?> type="checkbox" name="friends[]" id="f-<?php echo $friends[$i]['id'] ?>" value="<?php echo attribute_escape( $friends[$i]['id'] ); ?>" /> <?php echo $friends[$i]['full_name']; ?></li>
					<?php } ?>
				</ul>
			</div>
	<?php
		} else {
			_e( 'No friends to invite.', 'buddypress' );
		}
}

/* DEPRECATED - use bp_group_has_members() template loop */
function bp_group_random_members( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;

	$members = &$group->random_members;
?>	
	<ul class="horiz-gallery">
	<?php for ( $i = 0; $i < count( $members ); $i++ ) { ?>
		<li>
			<a href="<?php echo $members[$i]->user->user_url ?>"><?php echo $members[$i]->user->avatar_thumb ?></a>
			<h5><?php echo $members[$i]->user->user_link ?></h5>
		</li>
	<?php } ?>
	</ul>
	<div class="clear"></div>
<?php
}

/* DEPRECATED - see latest default BuddyPress theme /groups/create.php for replacement template tags */
function bp_group_create_form() {
	global $bp, $invites;
?>
	<form action="<?php echo $bp->displayed_user->domain . $bp->groups->slug ?>/create/step/<?php echo $bp->groups->current_create_step ?>" method="post" id="create-group-form" class="standard-form" enctype="multipart/form-data">
	<?php switch( $bp->groups->current_create_step ) {
		case 'group-details': ?>
			<label for="group-name">* <?php _e('Group Name', 'buddypress') ?></label>
			<input type="text" name="group-name" id="group-name" value="<?php echo attribute_escape( ( $bp->groups->new_group ) ? $bp->groups->current_group->name : $_POST['group-name'] ); ?>" />
		
			<label for="group-desc">* <?php _e('Group Description', 'buddypress') ?></label>
			<textarea name="group-desc" id="group-desc"><?php echo htmlspecialchars( ( $bp->groups->new_group ) ? $bp->groups->current_group->description : $_POST['group-desc'] ); ?></textarea>
		
			<label for="group-news"><?php _e('Recent News', 'buddypress') ?></label>
			<textarea name="group-news" id="group-news"><?php echo htmlspecialchars( ( $bp->groups->new_group ) ? $bp->groups->current_group->news : $_POST['group-news'] ); ?></textarea>
			
			<?php do_action( 'groups_custom_group_fields_editable' ) ?>

			<?php wp_nonce_field( 'groups_create_save_group-details' ) ?>

		<?php break; ?>
		
		<?php case 'group-settings': ?>
			<?php if ( bp_are_previous_group_creation_steps_complete( 'group-settings' ) ) { ?>
				<?php if ( function_exists('bp_wire_install') ) : ?>
				<div class="checkbox">
					<label><input type="checkbox" name="group-show-wire" id="group-show-wire" value="1"<?php if ( $bp->groups->current_group->enable_wire ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable comment wire', 'buddypress') ?></label>
				</div>
				<?php endif; ?>
				
				<?php if ( function_exists('bp_forums_setup') ) : ?>
					<?php if ( bp_forums_is_installed_correctly() ) { ?>
						<div class="checkbox">
							<label><input type="checkbox" name="group-show-forum" id="group-show-forum" value="1"<?php if ( $bp->groups->current_group->enable_forum ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable discussion forum', 'buddypress') ?></label>
						</div>
					<?php } else {
						if ( is_site_admin() ) {
							?>
							<div class="checkbox">
								<label><input type="checkbox" disabled="disabled" name="disabled" id="disabled" value="0" /> <?php printf( __('<strong>Attention Site Admin:</strong> Group forums require the <a href="%s">correct setup and configuration</a> of a bbPress installation.', 'buddypress' ), $bp->root_domain . '/wp-admin/admin.php?page=' . BP_PLUGIN_DIR . '/bp-forums/bp-forums-admin.php' ) ?></label>
							</div>
							<?php
						}
					}?>
				<?php endif; ?>
				
				<?php if ( function_exists('bp_albums_install') ) : ?>
				<div class="checkbox with-suboptions">
					<label><input type="checkbox" name="group-show-photos" id="group-show-photos" value="1"<?php if ( $bp->groups->current_group->enable_photos ) { ?> checked="checked"<?php } ?> /> <?php _e('Enable photo gallery', 'buddypress') ?></label>
					<div class="sub-options"<?php if ( !$bp->groups->current_group->enable_photos ) { ?> style="display: none;"<?php } ?>>
						<label><input type="radio" name="group-photos-status" value="all"<?php if ( !$bp->groups->current_group->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('All members can upload photos', 'buddypress') ?></label>
						<label><input type="radio" name="group-photos-status" value="admins"<?php if ( $bp->groups->current_group->photos_admin_only ) { ?> checked="checked"<?php } ?> /> <?php _e('Only group admins can upload photos', 'buddypress') ?></label>
					</div>
				</div>
				<?php endif; ?>
			
				<h3><?php _e( 'Privacy Options', 'buddypress' ); ?></h3>
			
				<div class="radio">
					<label><input type="radio" name="group-status" value="public"<?php if ( 'public' == $bp->groups->current_group->status ) { ?> checked="checked"<?php } ?> /> 
						<strong><?php _e( 'This is a public group', 'buddypress' ) ?></strong>
						<ul>
							<li><?php _e( 'Any site member can join this group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will be visible to any site member.', 'buddypress' ) ?></li>
						</ul>
					</label>
					
					<label><input type="radio" name="group-status" value="private"<?php if ( 'private' == $bp->groups->current_group->status ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e( 'This is a private group', 'buddypress' ) ?></strong>
						<ul>
							<li><?php _e( 'Only users who request membership and are accepted can join the group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
						</ul>
					</label>
					
					<label><input type="radio" name="group-status" value="hidden"<?php if ( 'hidden' == $bp->groups->current_group->status ) { ?> checked="checked"<?php } ?> />
						<strong><?php _e('This is a hidden group', 'buddypress') ?></strong>
						<ul>
							<li><?php _e( 'Only users who are invited can join the group.', 'buddypress' ) ?></li>
							<li><?php _e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ) ?></li>
							<li><?php _e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ) ?></li>
						</ul>
					</label>
				</div>

				<?php wp_nonce_field( 'groups_create_save_group-settings' ) ?>
			<?php } else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
			<?php } ?>
		<?php break; ?>
		
		<?php case 'group-avatar': ?>
			<?php if ( bp_are_previous_group_creation_steps_complete( 'group-avatar' ) ) { ?>
				<div class="left-menu">
					<?php bp_group_current_avatar() ?>
				</div>
				
				<div class="main-column">
					<p><?php _e("Upload an image to use as an avatar for this group. The image will be shown on the main group page, and in search results.", 'buddypress') ?></p>
					
					<?php groups_avatar_upload() ?>

				</div>
				
				<?php wp_nonce_field( 'groups_step3_save' ) ?>
			<?php } else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
			<?php } ?>

			<?php wp_nonce_field( 'groups_create_save_group-avatar' ) ?>		

		<?php break; ?>
		<?php case 'group-invites': ?>
			<?php 
			if ( bp_are_previous_group_creation_steps_complete( 'group-invites' ) ) {
				$group_link = bp_get_group_permalink( $bp->groups->new_group );
				
				if ( function_exists('friends_install') ) {
					if ( friends_get_friend_count_for_user( $bp->loggedin_user->id ) ) {
						bp_group_send_invite_form( $bp->groups->new_group );
					} else {
						?>
						<div id="message" class="info">
							<p><?php _e( 'Once you build up your friends list you will be able to invite friends to join your group.', 'buddypress' ) ?></p>
						</div>
						<?php
					}
				} ?>

				<?php wp_nonce_field( 'groups_step4_save' ) ?>
				
				<?php
			} else { ?>
				<div id="message" class="info">
					<p><?php _e('Please complete all previous steps first.', 'buddypress'); ?></p>
				</div>
		<?php } ?>

		<?php wp_nonce_field( 'groups_create_save_group-invites' ) ?>

		<?php break; ?>
	<?php } ?>
	
		<?php do_action( 'groups_custom_create_steps' ) // Allow plugins to add custom group creation steps ?>
		
		<div class="clear"></div>
		
		<div id="previous-next">
			<!-- Previous Button -->
			<?php if ( !bp_is_first_group_creation_step() ) : ?>
				<input type="button" value="&larr; <?php _e('Previous Step', 'buddypress') ?>" id="group-creation-previous" style="width: auto;" name="previous" onclick="location.href='<?php bp_group_creation_previous_link() ?>'" />
			<?php endif; ?>

			<!-- Next Button -->
			<?php if ( !bp_is_last_group_creation_step() && !bp_is_first_group_creation_step() ) : ?>
				 &nbsp; <input type="submit" value="<?php _e('Next Step', 'buddypress') ?> &rarr;" id="group-creation-next" name="save" />
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
<?php
}

function groups_ajax_invite_user() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer( 'groups_invite_uninvite_user' );

	if ( !$_POST['friend_id'] || !$_POST['friend_action'] || !$_POST['group_id'] )
		return false;

	if ( !groups_is_user_member( $bp->loggedin_user->id, $_POST['group_id'] ) )
		return false;

	if ( !friends_check_friendship( $bp->loggedin_user->id, $_POST['friend_id'] ) )
		return false;
	
	if ( 'invite' == $_POST['friend_action'] ) {
				
		if ( !groups_invite_user( $_POST['friend_id'], $_POST['group_id'] ) )
			return false;
		
		$user = new BP_Core_User( $_POST['friend_id'] );
		
		echo '<li id="uid-' . $user->id . '">';
		echo $user->avatar_thumb;
		echo '<h4>' . $user->user_link . '</h4>';
		echo '<span class="activity">' . attribute_escape( $user->last_active ) . '</span>';
		echo '<div class="action">
				<a class="remove" href="' . wp_nonce_url( $bp->loggedin_user->domain . $bp->groups->slug . '/' . $_POST['group_id'] . '/invites/remove/' . $user->id, 'groups_invite_uninvite_user' ) . '" id="uid-' . attribute_escape( $user->id ) . '">' . __( 'Remove Invite', 'buddypress' ) . '</a> 
			  </div>';
		echo '</li>';
		
	} else if ( 'uninvite' == $_POST['friend_action'] ) {
		
		if ( !groups_uninvite_user( $_POST['friend_id'], $_POST['group_id'] ) )
			return false;
		
		return true;
		
	} else {
		return false;
	}
}
add_action( 'wp_ajax_groups_invite_user', 'groups_ajax_invite_user' );

function groups_ajax_group_filter() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer( 'group-filter-box' );
	
	load_template( TEMPLATEPATH . '/groups/group-loop.php' );
}
add_action( 'wp_ajax_group_filter', 'groups_ajax_group_filter' );

function groups_ajax_widget_groups_list() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer('groups_widget_groups_list');

	switch ( $_POST['filter'] ) {
		case 'newest-groups':
			$type = 'newest';
		break;
		case 'recently-active-groups':
			$type = 'active';
		break;
		case 'popular-groups':
			$type = 'popular';
		break;
	}

	if ( bp_has_site_groups( 'type=' . $type . '&per_page=' . $_POST['max_groups'] . '&max=' . $_POST['max_groups'] ) ) : ?>
		<?php echo "0[[SPLIT]]"; ?>
				
		<ul id="groups-list" class="item-list">
			<?php while ( bp_site_groups() ) : bp_the_site_group(); ?>
				<li>
					<div class="item-avatar">
						<a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_avatar_thumb() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_the_site_group_link() ?>" title="<?php bp_the_site_group_name() ?>"><?php bp_the_site_group_name() ?></a></div>
						<div class="item-meta">
							<span class="activity">
								<?php 
								if ( 'newest-groups' == $_POST['filter'] ) {
									bp_the_site_group_date_created();
								} else if ( 'recently-active-groups' == $_POST['filter'] ) {
									bp_the_site_group_last_active();
								} else if ( 'popular-groups' == $_POST['filter'] ) {
									bp_the_site_group_member_count();
								}
								?>
							</span>
						</div>
					</div>
				</li>

			<?php endwhile; ?>
		</ul>		
		<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
		<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo attribute_escape( $_POST['max_groups'] ); ?>" />
		
	<?php else: ?>

		<?php echo "-1[[SPLIT]]<li>" . __("No groups matched the current filter.", 'buddypress'); ?>

	<?php endif;
	
}
add_action( 'wp_ajax_widget_groups_list', 'groups_ajax_widget_groups_list' );

function groups_ajax_member_list() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
	?>
	
	<?php if ( bp_group_has_members( 'group_id=' . $_REQUEST['group_id'] ) ) : ?>
		
		<?php if ( bp_group_member_needs_pagination() ) : ?>
			<div id="member-count" class="pag-count">
				<?php bp_group_member_pagination_count() ?>
			</div>

			<div id="member-pagination" class="pagination-links">
				<?php bp_group_member_pagination() ?>
			</div>
		<?php endif; ?>
		
		<ul id="member-list" class="item-list">
		<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
			<li>
				<?php bp_group_member_avatar() ?>
				<h5><?php bp_group_member_link() ?></h5>
				<span class="activity"><?php bp_group_member_joined_since() ?></span>
				
				<?php if ( function_exists( 'friends_install' ) ) : ?>
					<div class="action">
						<?php bp_add_friend_button( bp_get_group_member_id() ) ?>
					</div>
				<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no members.', 'buddypress' ) ?></p>
		</div>

	<?php endif; ?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $_REQUEST['group_id'] ); ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members', 'groups_ajax_member_list' );

function groups_ajax_member_admin_list() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
	?>
	
	<?php if ( bp_group_has_members( 'group_id=' . $_REQUEST['group_id'] . '&per_page=' . $_REQUEST['num'] ) ) : ?>
	
		<?php if ( bp_group_member_needs_pagination() ) : ?>
			<div id="member-count" class="pag-count">
				<?php bp_group_member_pagination_count() ?>
			</div>

			<div id="member-admin-pagination" class="pagination-links">
				<?php bp_group_member_admin_pagination() ?>
			</div>
		<?php endif; ?>
	
		<ul id="members-list" class="item-list single-line">
		<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
			<?php if ( bp_group_member_is_banned() ) : ?>
				<li class="banned-user">
					<?php bp_group_member_avatar_mini() ?>

					<h5><?php bp_group_member_link() ?> <?php _e( '(banned)', 'buddypress' ) ?> <span class="small"> &mdash; <a href="<?php bp_group_member_unban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Remove Ban', 'buddypress' ) ?></a> </h5>
			<?php else : ?>
				<li>
					<?php bp_group_member_avatar_mini() ?>
					<h5><?php bp_group_member_link() ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ) ?></a> | <a href="<?php bp_group_member_promote_link() ?>" title="<?php _e( 'Promote this member', 'buddypress' ) ?>"><?php _e( 'Promote to Moderator', 'buddypress' ) ?></a></span></h5>

			<?php endif; ?>
				</li>
		<?php endwhile; ?>
		</ul>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no members.', 'buddypress' ) ?></p>
		</div>

	<?php endif;?>
	<input type="hidden" name="group_id" id="group_id" value="<?php echo attribute_escape( $_REQUEST['group_id'] ); ?>" />
<?php
}
add_action( 'wp_ajax_get_group_members_admin', 'groups_ajax_member_admin_list' );

function bp_core_ajax_directory_groups() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer('directory_groups');

	load_template( TEMPLATEPATH . '/directories/groups/groups-loop.php' );
}
add_action( 'wp_ajax_directory_groups', 'bp_core_ajax_directory_groups' );

function groups_ajax_joinleave_group() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	if ( groups_is_user_banned( $bp->loggedin_user->id, $_POST['gid'] ) )
		return false;
	
	if ( !$group = new BP_Groups_Group( $_POST['gid'], false, false ) )
		return false;
	
	if ( 'hidden' == $group->status )
		return false;
	
	if ( !groups_is_user_member( $bp->loggedin_user->id, $group->id ) ) {

		if ( 'public' == $group->status ) {
			
			check_ajax_referer( 'groups_join_group' );
			
			if ( !groups_join_group( $group->id ) ) {
				_e( 'Error joining group', 'buddypress' );
			} else {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="leave-group" rel="leave" title="' . __( 'Leave Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
			}	
					
		} else if ( 'private' == $group->status ) {
			
			check_ajax_referer( 'groups_request_membership' );
			
			if ( !groups_send_membership_request( $bp->loggedin_user->id, $group->id ) ) {
				_e( 'Error requesting membership', 'buddypress' );	
			} else {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="membership-requested" rel="membership-requested" title="' . __( 'Membership Requested', 'buddypress' ) . '" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Membership Requested', 'buddypress' ) . '</a>';				
			}		
		}
		
	} else {

		check_ajax_referer( 'groups_leave_group' );
		
		if ( !groups_leave_group( $group->id ) ) {
			_e( 'Error leaving group', 'buddypress' );
		} else {
			if ( 'public' == $group->status ) {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="join-group" rel="join" title="' . __( 'Join Group', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';				
			} else if ( 'private' == $group->status ) {
				echo '<a id="group-' . attribute_escape( $group->id ) . '" class="request-membership" rel="join" title="' . __( 'Request Membership', 'buddypress' ) . '" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . '/request-membership', 'groups_send_membership_request' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
			}
		}
	}
}
add_action( 'wp_ajax_joinleave_group', 'groups_ajax_joinleave_group' );

?>