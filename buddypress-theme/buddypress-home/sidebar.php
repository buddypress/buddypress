<div id="sidebar">
	<?php if ( !function_exists('dynamic_sidebar')
	        || !dynamic_sidebar('blog-sidebar') ) : ?>
	
			<div class="widget-error">
				<?php _e( 'Please log in and add widgets to this column.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-4"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
			</div

	<?php endif; ?>
</div>