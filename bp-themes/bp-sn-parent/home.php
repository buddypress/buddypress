<?php get_header(); ?>

	<div id="content">

		<?php do_action( 'bp_before_home' ) ?>

		<div id="third-section" class="widget-section">
			<?php if ( !function_exists('dynamic_sidebar')
			        || !dynamic_sidebar('third-section') ) : ?>
		
			<div class="widget-error">
				<?php _e( 'Please log in and add widgets to this section.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=first-section"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
			</div>
		
			<?php endif; ?>
		</div>
		
		<div id="second-section" class="widget-section">
			<?php if ( !function_exists('dynamic_sidebar')
			        || !dynamic_sidebar('second-section') ) : ?>
		
			<div class="widget-error">
				<?php _e( 'Please log in and add widgets to this section.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=second-section"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
			</div>
		
			<?php endif; ?>
		</div>

		<div id="first-section" class="widget-section">
			<?php if ( !function_exists('dynamic_sidebar')
			        || !dynamic_sidebar('first-section') ) : ?>

			<div class="widget-error">
				<?php _e( 'Please log in and add widgets to this section.', 'buddypress' ) ?> <a href="<?php echo get_option('siteurl') ?>/wp-admin/widgets.php?s=&amp;show=&amp;sidebar=third-section"><?php _e( 'Add Widgets', 'buddypress' ) ?></a>
			</div>		
		
			<?php endif; ?>
		</div>

		<?php do_action( 'bp_after_home' ) ?>

	</div>

<?php get_footer(); ?>
