<?php
/*
 * /groups/list-members.php
 * Displays a list of all the admins, mods and regular group members.
 * 
 * Loads: '/groups/group-menu.php' (displays group avatar, mod and admin list)
 *
 * Loaded on URL:
 * 'http://example.org/groups/[group-slug]/members/
 */
?>

<?php get_header() ?>

<div id="main">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="page-menu">
		<?php load_template( TEMPLATEPATH . '/groups/group-menu.php' ) ?>
	</div>

	<div class="main-column">	
			
		<div id="group-name">
			<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
			<p class="status"><?php bp_group_type() ?></p>
		</div>
	
		<div class="info-group">
			
			<h4><?php _e( 'Administrators', 'buddypress' ); ?></h4>
			<?php bp_group_admin_memberlist() ?>
			
		</div>
	
		<?php if ( bp_group_has_moderators() ) : ?>
			
			<div class="info-group">
				
				<h4><?php _e( 'Moderators', 'buddypress' ); ?></h4>
				<?php bp_group_mod_memberlist() ?>
				
			</div>
			
		<?php endif; ?>

		<div class="info-group">
			
			<h4><?php _e( 'Group Members', 'buddypress' ); ?></h4>
		
			<form action="<?php bp_group_form_action('members') ?>" method="post" id="group-members-form">
			<?php if ( bp_group_has_members() ) : ?>
			
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
								<?php bp_add_friend_button( bp_group_member_id() ) ?>
							</div>
						<?php endif; ?>
					</li>
					
				<?php endwhile; ?>
				</ul>
				
			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( 'This group has no members.', 'buddypress' ); ?></p>
				</div>

			<?php endif;?>
		
			<input type="hidden" name="group_id" id="group_id" value="<?php bp_group_id() ?>" />
			</form>
		</div>
	
	</div>
	
	<?php endwhile; endif; ?>
</div>

<?php get_footer() ?>