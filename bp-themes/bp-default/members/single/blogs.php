<div class="item-list-tabs" id="subnav">
	<ul>
		<?php bp_get_options_nav() ?>

		<li id="blogs-order-select" class="last filter">
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
	<?php locate_template( array( 'blogs/blogs-loop.php' ), true ) ?>
</div><!-- .blogs -->

<?php do_action( 'bp_after_member_blogs_content' ) ?>
