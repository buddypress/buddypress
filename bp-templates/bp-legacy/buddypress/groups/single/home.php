<div id="buddypress">

	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

	<?php do_action( 'bp_before_group_home_content' ); ?>

	<div id="item-header" role="complementary">

		<?php bp_get_template_part( 'groups/single/group-header' ); ?>

	</div><!-- #item-header -->

	<div id="item-nav">
		<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
			<ul>

				<?php bp_get_options_nav(); ?>

				<?php do_action( 'bp_group_options_nav' ); ?>

			</ul>
		</div>
	</div><!-- #item-nav -->

	<div id="item-body">

		<?php do_action( 'bp_before_group_body' );

		if ( bp_is_group_admin_page() && bp_group_is_visible() ) :
			bp_get_template_part( 'groups/single/admin' );

		elseif ( bp_is_group_members() && bp_group_is_visible() ) :
			bp_get_template_part( 'groups/single/members' );

		elseif ( bp_is_group_invites() && bp_group_is_visible() ) :
			bp_get_template_part( 'groups/single/send-invites' );

		elseif ( bp_is_group_forum() && bp_group_is_visible() && bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) :
			bp_get_template_part( 'groups/single/forum' );

		elseif ( bp_is_group_membership_request() ) :
			bp_get_template_part( 'groups/single/request-membership' );

		elseif ( bp_group_is_visible() && bp_is_active( 'activity' ) ) :
			bp_get_template_part( 'groups/single/activity' );

		elseif ( bp_group_is_visible() ) :
			bp_get_template_part( 'groups/single/members' );

		// The group is not visible, show the status message
		elseif ( !bp_group_is_visible() ) :

			do_action( 'bp_before_group_status_message' ); ?>

			<div id="message" class="info">
				<p><?php bp_group_status_message(); ?></p>
			</div>

			<?php do_action( 'bp_after_group_status_message' );

		// If nothing sticks, just load a group front template if one exists.
		else :
			bp_get_template_part( 'groups/single/plugins' );

		endif;

		do_action( 'bp_after_group_body' ); ?>

	</div><!-- #item-body -->

	<?php do_action( 'bp_after_group_home_content' ); ?>

	<?php endwhile; endif; ?>

</div><!-- #buddypress -->
