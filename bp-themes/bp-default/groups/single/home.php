<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">
			<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php locate_template( array( 'groups/single/group-header.php' ), true ) ?>

			<div id="item-body">
				<?php do_action( 'template_notices' ) ?>

				<?php if ( 'admin' == bp_current_action() ) : ?>
					<?php locate_template( array( 'groups/single/admin.php' ), true ) ?>

				<?php elseif ( 'members' == bp_current_action() ) : ?>
					<?php locate_template( array( 'groups/single/members.php' ), true ) ?>

				<?php elseif ( 'send-invites' == bp_current_action() ) : ?>
					<?php locate_template( array( 'groups/single/send-invites.php' ), true ) ?>

				<?php elseif ( 'request-membership' == bp_current_action() ) : ?>
					<?php locate_template( array( 'groups/single/request-membership.php' ), true ) ?>

				<?php elseif ( 'forum' == bp_current_action() ) : ?>
					<?php locate_template( array( 'groups/single/forum.php' ), true ) ?>

				<?php else : ?>
					<?php locate_template( array( 'groups/single/activity.php' ), true ) ?>

				<?php endif; ?>

				<?php do_action( 'bp_directory_members_content' ) ?>

			</div>

			<div id="item-menu">
				<?php bp_group_avatar() ?>

				<?php if ( bp_group_is_visible() ) : ?>

					<?php if ( bp_group_has_news() ) : ?>
						<?php do_action( 'bp_before_group_news' ) ?>

						<h3><?php _e( 'Latest News', 'buddypress' ); ?></h3>
						<p><?php bp_group_news() ?></p>

						<?php do_action( 'bp_after_group_news' ) ?>
					<?php endif; ?>

					<h3><?php _e( 'Group Admins', 'buddypress' ) ?></h3>
					<?php bp_group_list_admins() ?>

					<?php do_action( 'bp_after_group_menu_admins' ) ?>

					<?php if ( bp_group_has_moderators() ) : ?>
						<?php do_action( 'bp_before_group_menu_mods' ) ?>

						<h3><?php _e( 'Group Mods' , 'buddypress' ) ?></h3>
						<?php bp_group_list_mods() ?>

						<?php do_action( 'bp_after_group_menu_mods' ) ?>
					<?php endif; ?>

				<?php endif; ?>
			</div>

			<?php endwhile; endif; ?>
		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>
