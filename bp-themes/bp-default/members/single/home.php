<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<div id="item-header">
				<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>
			</div>

			<div id="item-body">
				<?php if ( 'home' == bp_current_component() || 'activity' == bp_current_component() || !bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/activity.php' ), true ) ?>

				<?php elseif ( 'blogs' == bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/blogs.php' ), true ) ?>

				<?php elseif ( 'friends' == bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/friends.php' ), true ) ?>

				<?php elseif ( 'groups' == bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/groups.php' ), true ) ?>

				<?php elseif ( 'messages' == bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/messages.php' ), true ) ?>

				<?php elseif ( 'profile' == bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/profile.php' ), true ) ?>

				<?php else : ?>
					<?php locate_template( array( 'members/single/plugins.php' ), true ) ?>

				<?php endif; ?>

				<?php do_action( 'bp_directory_members_content' ) ?>

			</div><!-- #item-body -->

			<div id="item-menu">
				<?php bp_displayed_user_avatar( 'type=full' ) ?>

				<?php
				 /***
				  * If you'd like to show specific profile fields here use:
				  * bp_profile_field_data( 'field=About Me' ); -- Pass the name of the field
				  */
				?>

				<?php do_action( 'bp_before_profile_random_friends_loop' ) ?>

				<?php if ( bp_has_members( 'user_id=' . bp_displayed_user_id() . '&type=random&max=20' ) ) : ?>

					<h3><a href="<?php echo bp_displayed_user_domain() . BP_FRIENDS_SLUG ?>"><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?></a></h3>

					<ul class="avatars">
					<?php while ( bp_members() ) : bp_the_member(); ?>
						<li>
							<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_avatar('width=30&height=30') ?></a>
						</li>
					<?php endwhile; ?>
					</ul>

				<?php endif; ?>

				<?php do_action( 'bp_after_profile_random_friends_loop' ) ?>
				<?php do_action( 'bp_before_profile_random_groups_loop' ) ?>

				<?php if ( function_exists( 'bp_has_groups' ) ) : ?>

					<?php if ( bp_has_groups( 'user_id=' . bp_displayed_user_id() . '&type=random&max=20' ) ) : ?>

						<h3><a href="<?php echo bp_displayed_user_domain() . BP_GROUPS_SLUG ?>"><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?></a></h3>

						<ul class="avatars">
						<?php while ( bp_groups() ) : bp_the_group(); ?>
							<li>
								<a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_avatar('width=30&height=30') ?></a>
							</li>
						<?php endwhile; ?>
						</ul>

					<?php endif; ?>

				<?php endif; ?>

				<?php do_action( 'bp_after_profile_random_groups_loop' ) ?>
			</div>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>