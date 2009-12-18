<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<div id="item-header">
				<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="user-nav">
					<ul>
						<?php bp_get_user_nav() ?>

						<?php do_action( 'bp_members_directory_member_types' ) ?>
					</ul>
				</div>
			</div>

			<div id="item-body">
				<?php if ( bp_is_user_activity() || !bp_current_component() ) : ?>
					<?php locate_template( array( 'members/single/activity.php' ), true ) ?>

				<?php elseif ( bp_is_user_blogs() ) : ?>
					<?php locate_template( array( 'members/single/blogs.php' ), true ) ?>

				<?php elseif ( bp_is_user_friends() ) : ?>
					<?php locate_template( array( 'members/single/friends.php' ), true ) ?>

				<?php elseif ( bp_is_user_groups() ) : ?>
					<?php locate_template( array( 'members/single/groups.php' ), true ) ?>

				<?php elseif ( bp_is_user_messages() ) : ?>
					<?php locate_template( array( 'members/single/messages.php' ), true ) ?>

				<?php elseif ( bp_is_user_profile() ) : ?>
					<?php locate_template( array( 'members/single/profile.php' ), true ) ?>

				<?php endif; ?>

				<?php do_action( 'bp_directory_members_content' ) ?>

			</div><!-- #item-body -->

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>