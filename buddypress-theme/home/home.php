<?php get_header(); ?>

<div id="content" class="widecolumn">

	<div id="right-column">
		<?php if ( !function_exists('dynamic_sidebar')
		        || !dynamic_sidebar('right-column') ) : ?>
		
		<div class="widget-error">
			Please log in and <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-3">add widgets to this column</a>.
		</div>
		
		<?php endif; ?>
	</div>

	<div id="center-column">
		<?php if ( !function_exists('dynamic_sidebar')
		        || !dynamic_sidebar('center-column') ) : ?>
		
		<div class="widget-error">
			Please log in and <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-2">add widgets to this column</a>.
		</div>
		
		<?php endif; ?>
	</div>

	<div id="left-column">
		<?php if ( !function_exists('dynamic_sidebar')
		        || !dynamic_sidebar('left-column') ) : ?>

		<div class="widget-error">
			Please log in and <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-1">add widgets to this column</a>.
		</div>		
		
		<?php endif; ?>
	</div>

</div>

<?php get_footer(); ?>
