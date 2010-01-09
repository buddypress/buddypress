<div class="item-list-tabs" id="user-subnav">
	<ul>
		<li id="blogs-filter-select" class="last filter">
			<?php _e( 'Order By:', 'buddypress' ) ?>
			<select id="blogs-all">
				<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
				<option value="newest"><?php _e( 'Newest', 'buddypress' ) ?></option>
				<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

				<?php do_action( 'bp_member_blog_order_options' ) ?>
			</select>
		</li>
	</ul>
</div><!-- .item-list-tabs -->

<?php do_action( 'bp_before_member_blogs_content' ) ?>

<div class="blogs myblogs">
	<?php
		// The loop will be loaded here via AJAX on page load to retain selected settings and not waste cycles.
		// If you're concerned about no-script functionality, uncomment the following line.

		// locate_template( array( 'blogs/blogs-loop.php' ), true );
	?>
</div><!-- .blogs -->

<?php do_action( 'bp_after_member_blogs_content' ) ?>
