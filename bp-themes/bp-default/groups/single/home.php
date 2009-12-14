<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">
			<?php do_action( 'template_notices' ) ?>

			<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php locate_template( array( 'groups/single/group-header.php' ), true ) ?>

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

			<?php endwhile; endif; ?>
		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>
