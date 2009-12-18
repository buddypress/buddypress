<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

		<form action="" method="post" id="members-directory-form" class="dir-form">

			<h2><?php _e( 'Members Directory', 'buddypress' ) ?></h2>

			<div id="members-dir-search" class="dir-search">
				<?php bp_directory_members_search_form() ?>
			</div>

			<div class="item-list-tabs">
				<ul>
					<li class="selected" id="members-all"><a href="<?php bp_root_domain() ?>"><?php printf( __( 'All Members (%s)', 'buddypress' ), bp_get_total_member_count() ) ?></a></li>

					<?php if ( is_user_logged_in() && bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>
						<li id="members-friends"><a href="<?php echo bp_loggedin_user_domain() . BP_FRIENDS_SLUG . '/my-friends/' ?>"><?php printf( __( 'My Friends (%s)', 'buddypress' ), bp_get_total_friend_count( bp_loggedin_user_id() ) ) ?></a></li>
					<?php endif; ?>

					<?php do_action( 'bp_members_directory_member_types' ) ?>

					<li id="members-order-select" class="last filter">

						<?php _e( 'Order By:', 'buddypress' ) ?>
						<select>
							<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
							<option value="newest"><?php _e( 'Newest Registered', 'buddypress' ) ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

							<?php do_action( 'bp_members_directory_order_options' ) ?>
						</select>
					</li>
				</ul>
			</div>

			<div id="members-dir-list" class="members dir-list">
				<?php /* 'members/members-loop.php' is loaded here via AJAX */ ?>
			</div>

			<?php do_action( 'bp_directory_members_content' ) ?>

			<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ) ?>

		</form>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>