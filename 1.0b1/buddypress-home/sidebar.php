<div id="sidebar">
	<?php if ( !function_exists('dynamic_sidebar')
	        || !dynamic_sidebar('blog-sidebar') ) : ?>
	
			<div class="widget-error">
				Please log in and <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-4">add widgets to the blog sidebar</a>.
			</div

	<?php endif; ?>
</div>