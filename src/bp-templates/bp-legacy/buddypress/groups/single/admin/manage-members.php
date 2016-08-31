<?php
/**
 * BuddyPress - Groups Admin - Manage Members
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<h2 class="bp-screen-reader-text"><?php _e( 'Manage Members', 'buddypress' ); ?></h2>

<?php

/**
 * Fires before the group manage members admin display.
 *
 * @since 1.1.0
 */
do_action( 'bp_before_group_manage_members_admin' ); ?>

<div class="bp-widget">
	<h3><?php _e( 'Administrators', 'buddypress' ); ?></h3>

	<?php if ( bp_has_members( '&include='. bp_group_admin_ids() ) ) : ?>

	<ul id="admins-list" class="item-list single-line">

		<?php while ( bp_members() ) : bp_the_member(); ?>
		<li>
			<?php echo bp_core_fetch_avatar( array( 'item_id' => bp_get_member_user_id(), 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_member_name() ) ) ); ?>
			<h5>
				<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
				<?php if ( count( bp_group_admin_ids( false, 'array' ) ) > 1 ) : ?>
				<span class="small">
					<a class="button confirm admin-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php _e( 'Demote to Member', 'buddypress' ); ?></a>
				</span>
				<?php endif; ?>
			</h5>
		</li>
		<?php endwhile; ?>

	</ul>

	<?php endif; ?>

</div>

<?php if ( bp_group_has_moderators() ) : ?>
	<div class="bp-widget">
		<h3><?php _e( 'Moderators', 'buddypress' ); ?></h3>

		<?php if ( bp_has_members( '&include=' . bp_group_mod_ids() ) ) : ?>
			<ul id="mods-list" class="item-list single-line">

				<?php while ( bp_members() ) : bp_the_member(); ?>
				<li>
					<?php echo bp_core_fetch_avatar( array( 'item_id' => bp_get_member_user_id(), 'type' => 'thumb', 'width' => 30, 'height' => 30, 'alt' => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_member_name() ) ) ); ?>
					<h5>
						<a href="<?php bp_member_permalink(); ?>"> <?php bp_member_name(); ?></a>
						<span class="small">
							<a href="<?php bp_group_member_promote_admin_link( array( 'user_id' => bp_get_member_user_id() ) ); ?>" class="button confirm mod-promote-to-admin"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>
							<a class="button confirm mod-demote-to-member" href="<?php bp_group_member_demote_link( bp_get_member_user_id() ); ?>"><?php _e( 'Demote to Member', 'buddypress' ); ?></a>
						</span>
					</h5>
				</li>
				<?php endwhile; ?>

			</ul>

		<?php endif; ?>
	</div>
<?php endif; ?>


<div class="bp-widget">
	<h3><?php _e( "Members", 'buddypress' ); ?></h3>

	<?php if ( bp_group_has_members( 'per_page=15&exclude_banned=0' ) ) : ?>

		<?php if ( bp_group_member_needs_pagination() ) : ?>

			<div class="pagination no-ajax">

				<div id="member-count" class="pag-count">
					<?php bp_group_member_pagination_count(); ?>
				</div>

				<div id="member-admin-pagination" class="pagination-links">
					<?php bp_group_member_admin_pagination(); ?>
				</div>

			</div>

		<?php endif; ?>

		<ul id="members-list" class="item-list single-line">
			<?php while ( bp_group_members() ) : bp_group_the_member(); ?>

				<li class="<?php bp_group_member_css_class(); ?>">
					<?php bp_group_member_avatar_mini(); ?>

					<h5>
						<?php bp_group_member_link(); ?>

						<?php if ( bp_get_group_member_is_banned() ) _e( '(banned)', 'buddypress' ); ?>

						<span class="small">

						<?php if ( bp_get_group_member_is_banned() ) : ?>

							<a href="<?php bp_group_member_unban_link(); ?>" class="button confirm member-unban" title="<?php esc_attr_e( 'Unban this member', 'buddypress' ); ?>"><?php _e( 'Remove Ban', 'buddypress' ); ?></a>

						<?php else : ?>

							<a href="<?php bp_group_member_ban_link(); ?>" class="button confirm member-ban"><?php _e( 'Kick &amp; Ban', 'buddypress' ); ?></a>
							<a href="<?php bp_group_member_promote_mod_link(); ?>" class="button confirm member-promote-to-mod"><?php _e( 'Promote to Mod', 'buddypress' ); ?></a>
							<a href="<?php bp_group_member_promote_admin_link(); ?>" class="button confirm member-promote-to-admin"><?php _e( 'Promote to Admin', 'buddypress' ); ?></a>

						<?php endif; ?>

							<a href="<?php bp_group_member_remove_link(); ?>" class="button confirm"><?php _e( 'Remove from group', 'buddypress' ); ?></a>

							<?php

							/**
							 * Fires inside the display of a member admin item in group management area.
							 *
							 * @since 1.1.0
							 */
							do_action( 'bp_group_manage_members_admin_item' ); ?>

						</span>
					</h5>
				</li>

			<?php endwhile; ?>
		</ul>

		<?php if ( bp_group_member_needs_pagination() ) : ?>

			<div class="pagination no-ajax">

				<div id="member-count" class="pag-count">
					<?php bp_group_member_pagination_count(); ?>
				</div>

				<div id="member-admin-pagination" class="pagination-links">
					<?php bp_group_member_admin_pagination(); ?>
				</div>

			</div>

		<?php endif; ?>

	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'This group has no members.', 'buddypress' ); ?></p>
		</div>

	<?php endif; ?>

</div>

<?php

/**
 * Fires after the group manage members admin display.
 *
 * @since 1.1.0
 */
do_action( 'bp_after_group_manage_members_admin' ); ?>
