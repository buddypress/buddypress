<?php get_header(); ?>

<div id="content" class="widecolumn">
	
	<div id="right-column">
		<?php if ( !function_exists('dynamic_sidebar')
		        || !dynamic_sidebar('right-column') ) : ?>
		
		<div class="widget-error">
			<?php _e( 'Please log in and add widgets to this column.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-3"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
		</div>
		
		<?php endif; ?>
	</div>

	<div id="center-column">
		<?php if ( !function_exists('dynamic_sidebar')
		        || !dynamic_sidebar('center-column') ) : ?>
		
		<div class="widget-error">
			<?php _e( 'Please log in and add widgets to this column.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=sidebar-2"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
		</div>
		
		<?php endif; ?>
	</div>

	<div id="left-column">
		<div class="register bp_core_widget_welcome">
			<h2 class="widgettitle"><?php _e( 'Register for an Account', 'buddypress' ) ?></h2>
			<?php bp_core_signup_do_signup() ?>
		</div>
	</div>
	

</div>

<?php get_footer(); ?>
