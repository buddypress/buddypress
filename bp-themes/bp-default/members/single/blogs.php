<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>

			<div class="item-list-tabs" id="user-subnav">
				<ul>
					<li id="blogs-filter-select" class="last filter">
						<?php _e( 'Order By:', 'buddypress' ) ?>
						<select id="blogs-all">
							<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
							<option value="newest"><?php _e( 'Newest', 'buddypress' ) ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

							<?php do_action( 'bp_blogs_directory_order_options' ) ?>
						</select>
					</li>
				</ul>
			</div>

			<div class="blogs myblogs">
				<?php // 'blogs/blogs-loop.php' loaded here via AJAX. ?>
			</div>

			<?php do_action( 'bp_directory_members_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>