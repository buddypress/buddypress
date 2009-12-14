<?php get_header() ?>

	<?php do_action( 'bp_before_directory_groups_content' ) ?>

	<div id="content">
		<div class="padder">

		<form action="" method="post" id="groups-directory-form" class="dir-form">
			<h2><?php _e( 'Groups Directory', 'buddypress' ) ?><?php if ( is_user_logged_in() ) : ?> &nbsp;<a class="button" href="<?php echo bp_root_domain() . '/' . BP_GROUPS_SLUG . '/create/' ?>"><?php _e( 'Create a Group', 'buddypress' ) ?></a><?php endif; ?></h2>

			<div id="group-dir-search" class="dir-search">
				<?php bp_directory_groups_search_form() ?>
			</div>

			<div class="item-list-tabs">
				<ul>
					<li class="selected" id="groups-all"><a href="<?php bp_root_domain() ?>"><?php printf( __( 'All Groups (%d)', 'buddypress' ), groups_get_total_group_count() ) ?></a></li>

					<?php if ( is_user_logged_in() && groups_total_groups_for_user( bp_loggedin_user_id() ) ) : ?>
						<li id="groups-mygroups"><a href="<?php echo bp_loggedin_user_domain() . BP_GROUPS_SLUG . '/my-groups/' ?>"><?php printf( __( 'My Groups (%d)', 'buddypress' ), groups_total_groups_for_user( bp_loggedin_user_id() ) ) ?></a></li>
					<?php endif; ?>

					<?php do_action( 'bp_members_directory_group_types' ) ?>

					<li id="groups-order-select" class="last filter">

						<?php _e( 'Order By:', 'buddypress' ) ?>
						<select>
							<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
							<option value="popular"><?php _e( 'Most Members', 'buddypress' ) ?></option>
							<option value="newest"><?php _e( 'Newly Created', 'buddypress' ) ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

							<?php do_action( 'bp_groups_directory_order_options' ) ?>
						</select>
					</li>
				</ul>
			</div>

			<div id="groups-dir-list" class="groups dir-list">
				<?php /* 'groups/groups-loop.php' is loaded here via AJAX */ ?>
			</div>

			<?php do_action( 'bp_directory_groups_content' ) ?>

			<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ) ?>

		</form>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_groups_content' ) ?>

<?php get_footer() ?>