<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

		<form action="" method="post" id="blogs-directory-form" class="dir-form">

			<h2><?php _e( 'Blogs Directory', 'buddypress' ) ?><?php if ( is_user_logged_in() && bp_blog_signup_enabled() ) : ?> &nbsp;<a class="button" href="<?php echo bp_root_domain() . '/' . BP_BLOGS_SLUG . '/create/' ?>"><?php _e( 'Create a Blog', 'buddypress' ) ?></a><?php endif; ?></h2>

			<div id="blog-dir-search" class="dir-search">
				<?php bp_directory_blogs_search_form() ?>
			</div>

			<div class="item-list-tabs">
				<ul>
					<li class="selected" id="blogs-all"><a href="<?php bp_root_domain() ?>"><?php printf( __( 'All Blogs (%d)', 'buddypress' ), bp_get_total_blog_count() ) ?></a></li>

					<?php if ( is_user_logged_in() && bp_total_blogs_for_user( bp_loggedin_user_id() ) ) : ?>
						<li id="blogs-myblogs"><a href="<?php echo bp_loggedin_user_domain() . BP_BLOGS_SLUG . '/my-blogs/' ?>"><?php printf( __( 'My Blogs (%d)', 'buddypress' ), bp_total_blogs_for_user( bp_loggedin_user_id() ) ) ?></a></li>
					<?php endif; ?>

					<?php do_action( 'bp_blogs_directory_member_types' ) ?>

					<li id="blogs-order-select" class="last filter">

						<?php _e( 'Order By:', 'buddypress' ) ?>
						<select>
							<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
							<option value="newest"><?php _e( 'Newest', 'buddypress' ) ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

							<?php do_action( 'bp_blogs_directory_order_options' ) ?>
						</select>
					</li>
				</ul>
			</div>

			<div id="blogs-dir-list" class="blogs dir-list">
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