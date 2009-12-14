<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>

			<div class="item-list-tabs no-ajax" id="user-subnav">
				<ul>
					<?php if ( bp_is_my_profile() ) : ?>
						<?php bp_get_options_nav() ?>
					<?php endif; ?>

					<?php if ( 'invites' != bp_current_action() ) : ?>
					<li id="members-order-select" class="last filter">

						<?php _e( 'Order By:', 'buddypress' ) ?>
						<select id="groups-all">
							<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
							<option value="popular"><?php _e( 'Most Members', 'buddypress' ) ?></option>
							<option value="newest"><?php _e( 'Newly Created', 'buddypress' ) ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

							<?php do_action( 'bp_groups_directory_order_options' ) ?>
						</select>
					</li>
					<?php endif; ?>
				</ul>
			</div>

			<?php if ( 'invites' == bp_current_action() ) : ?>
				<?php locate_template( array( 'members/single/groups/invites.php' ), true ) ?>

			<?php else : ?>

				<div class="groups mygroups">
					<?php // 'members/members-loop.php' loaded here via AJAX. ?>
				</div>

			<?php endif; ?>

			<?php do_action( 'bp_directory_members_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>