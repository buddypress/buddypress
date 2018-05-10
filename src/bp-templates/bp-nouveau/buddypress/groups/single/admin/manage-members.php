<?php
/**
 * BP Nouveau Group's manage members template.
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<h2 class="bp-screen-title <?php if ( bp_is_group_create() ) { echo esc_attr( 'creation-step-name' ); } ?>">
	<?php _e( 'Manage Group Members', 'buddypress' ); ?>
</h2>

	<p class="bp-help-text"><?php _e( 'Manage your group members; promote to moderators, admins or demote or ban.', 'buddypress' ); ?></p>

	<dl class="groups-manage-members-list">

	<dt class="admin-section section-title"><?php _e( 'Administrators', 'buddypress' ); ?></dt>

	<?php if ( bp_has_members( '&include=' . bp_group_admin_ids() ) ) : ?>
		<dd class="admin-listing">
			<ul id="admins-list" class="item-list single-line">

				<?php while ( bp_members() ) : bp_the_member(); ?>
				<li class="member-entry clearfix">

					<?php echo bp_core_fetch_avatar( array( 'item_id' => bp_get_member_user_id(), 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => '' ) ); ?>
					<p class="list-title member-name">
						<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
					</p>

					<?php if ( count( bp_group_admin_ids( false, 'array' ) ) > 1 ) : ?>

						<p class="action text-links-list">
							<a class="button confirm admin-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php _e( 'Demote to Member', 'buddypress' ); ?></a>
						</p>

					<?php endif; ?>

				</li>
				<?php endwhile; ?>

			</ul>
		</dd>
	<?php endif; ?>

	<?php if ( bp_group_has_moderators() ) : ?>

		<dt class="moderator-section section-title"><?php _e( 'Moderators', 'buddypress' ); ?></dt>

		<dd class="moderator-listing">
		<?php if ( bp_has_members( '&include=' . bp_group_mod_ids() ) ) : ?>
			<ul id="mods-list" class="item-list single-line">

				<?php while ( bp_members() ) : bp_the_member(); ?>
				<li class="members-entry clearfix">

					<?php echo bp_core_fetch_avatar( array( 'item_id' => bp_get_member_user_id(), 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => '' ) ); ?>
					<p class="list-title member-name">
						<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
					</p>

					<div class="members-manage-buttons action text-links-list">
						<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => bp_get_member_user_id() ) ); ?>" class="button confirm mod-promote-to-admin"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>
						<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php _e( 'Demote to Member', 'buddypress' ); ?></a>
					</div>

				</li>

				<?php endwhile; ?>

			</ul>

		<?php endif; ?>
	</dd>
	<?php endif ?>


	<dt class="gen-members-section section-title"><?php esc_html_e( 'Members', 'buddypress' ); ?></dt>

	<dd class="general-members-listing">
		<?php if ( bp_group_has_members( 'per_page=15&exclude_banned=0' ) ) : ?>

			<?php if ( bp_group_member_needs_pagination() ) : ?>

				<?php bp_nouveau_pagination( 'top' ) ; ?>

			<?php endif; ?>

			<ul id="members-list" class="item-list single-line">
				<?php while ( bp_group_members() ) : bp_group_the_member(); ?>

					<li class="<?php bp_group_member_css_class(); ?> members-entry clearfix">
						<?php bp_group_member_avatar_mini(); ?>

						<p class="list-title member-name">
							<?php bp_group_member_link(); ?>
							<span class="banned warn"><?php if ( bp_get_group_member_is_banned() ) _e( '(banned)', 'buddypress' ); ?></span>
						</p>

						<?php bp_nouveau_groups_manage_members_buttons( array( 'container' => 'div', 'container_classes' => array( 'members-manage-buttons', 'text-links-list' ), 'parent_element' => '  ' ) ) ; ?>

					</li>

				<?php endwhile; ?>
			</ul>
	</dd>

</dl>

	<?php else:

		bp_nouveau_user_feedback( 'group-manage-members-none' );

	endif; ?>

