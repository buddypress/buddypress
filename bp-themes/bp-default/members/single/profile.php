<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>

			<?php if ( bp_is_my_profile() ) : ?>
				<div class="item-list-tabs no-ajax" id="user-subnav">
					<ul>
						<?php bp_get_options_nav() ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="profile">
				<?php if ( 'edit' == bp_current_action() ) : ?>
					<?php locate_template( array( 'members/single/profile/edit.php' ), true ) ?>

				<?php elseif ( 'change-avatar' == bp_current_action() ) : ?>
					<?php locate_template( array( 'members/single/profile/change-avatar.php' ), true ) ?>

				<?php else : ?>
					<?php locate_template( array( 'members/single/profile/profile-loop.php' ), true ) ?>

				<?php endif; ?>
			</div>

			<?php do_action( 'bp_directory_members_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>