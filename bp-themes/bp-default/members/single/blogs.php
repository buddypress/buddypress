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
