<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><?php _e( 'Manage Members', 'buddypress' ); ?></h2>
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
			
			<div class="info-group">
				<h4><?php _e( 'Administrators', 'buddypress' ); ?></h4>
				<?php bp_group_admin_memberlist( true ) ?>
			</div>
			
			<?php if ( bp_group_has_moderators() ) : ?>
			<div class="info-group">
				<h4><?php _e( 'Moderators', 'buddypress' ) ?></h4>
				<?php bp_group_mod_memberlist( true ) ?>
			</div>
			<?php endif; ?>
			
			<div class="info-group">
				<h4><?php _e("Members", "buddypress"); ?></h4>
				
				<form action="<?php bp_group_admin_form_action('manage-members') ?>" name="group-members-form" id="group-members-form" class="standard-form" method="post">
				<?php if ( bp_group_has_members( 'per_page=15&exclude_banned=false' ) ) : ?>

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
						<?php if ( bp_get_group_member_is_banned() ) : ?>
							<li class="banned-user">
								<?php bp_group_member_avatar_mini() ?>

								<h5><?php bp_group_member_link() ?> <?php _e( '(banned)', 'buddypress') ?> <span class="small"> &mdash; <a href="<?php bp_group_member_unban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ) ?>"><?php _e( 'Remove Ban', 'buddypress' ); ?></a> </h5>
						<?php else : ?>
							<li>
								<?php bp_group_member_avatar_mini() ?>
								<h5><?php bp_group_member_link() ?>  <span class="small"> &mdash; <a href="<?php bp_group_member_ban_link() ?>" title="<?php _e( 'Kick and ban this member', 'buddypress' ); ?>"><?php _e( 'Kick &amp; Ban', 'buddypress' ); ?></a> | <a href="<?php bp_group_member_promote_link() ?>" title="<?php _e( 'Promote this member', 'buddypress' ); ?>"><?php _e( 'Promote to Moderator', 'buddypress' ); ?></a></span></h5>

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

<?php get_footer() ?>
