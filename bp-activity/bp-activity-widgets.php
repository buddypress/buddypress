<?php

/* Register widgets for blogs component */
function bp_activity_register_widgets() {
	global $current_blog;

	/* Site Wide Activity Widget */
	wp_register_sidebar_widget( 'buddypress-activity', __('Site Wide Activity', 'buddypress'), 'bp_activity_widget_sitewide_activity');
	wp_register_widget_control( 'buddypress-activity', __('Site Wide Activity', 'buddypress'), 'bp_activity_widget_sitewide_activity_control' );

	if ( is_active_widget( 'bp_activity_widget_sitewide_activity' ) ) {
		wp_enqueue_style( 'bp-activity-widget-activity-css', BP_PLUGIN_URL . '/bp-activity/css/widget-activity.css' );		
	}
}
add_action( 'plugins_loaded', 'bp_activity_register_widgets' );


function bp_activity_widget_sitewide_activity($args) {
	global $bp, $current_blog;
	
    extract($args);
	$options = get_blog_option( $current_blog->blog_id, 'bp_activity_widget_sitewide_activity' );
?>
	<?php echo $before_widget; ?>
	<?php echo $before_title
		. $widget_name . 
		' <a href="' . bp_get_sitewide_activity_feed_link() . '" title="' . __( 'Site Wide Activity RSS Feed', 'buddypress' ) . '"><img src="' . $bp->activity->image_base . '/rss.png" alt="' . __( 'RSS Feed', 'buddypress' ) . '" /></a>' 
		. $after_title; ?>
		
	<?php 
	if ( !$options['per_page'] || empty( $options['per_page'] ) )
		$options['per_page'] = 20;

	if ( !$options['max_items'] || empty( $options['max_items'] ) )
		$options['max_items'] = 200;
	?>

	<?php if ( bp_has_activities( 'type=sitewide&max=' . $options['max_items'] . '&per_page=' . $options['per_page'] ) ) : ?>
		<div class="pag-count" id="activity-count">
			<?php bp_activity_pagination_count() ?>
		</div>
		
		<div class="pagination-links" id="activity-pag">
			&nbsp; <?php bp_activity_pagination_links() ?>
		</div>

		<ul id="activity-filter-links">
			<?php bp_activity_filter_links() ?>
		</ul>
		
		<ul id="site-wide-stream" class="activity-list">
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li class="<?php bp_activity_css_class() ?>">
				<?php bp_activity_content() ?>
			</li>
		<?php endwhile; ?>
		</ul>

	<?php else: ?>

		<div class="widget-error">
			<?php _e('There has been no recent site activity.', 'buddypress') ?>
		</div>

	<?php endif;?>

	<?php echo $after_widget; ?>
<?php
}

function bp_activity_widget_sitewide_activity_control() {
	global $current_blog;
	
	$options = $newoptions = get_blog_option( $current_blog->blog_id, 'bp_activity_widget_sitewide_activity');

	if ( $_POST['bp-activity-widget-sitewide-submit'] ) {
		$newoptions['max_items'] = strip_tags( stripslashes( $_POST['bp-activity-widget-sitewide-items-max'] ) );
	}

	if ( $_POST['bp-activity-widget-sitewide-submit'] ) {
		$newoptions['per_page'] = strip_tags( stripslashes( $_POST['bp-activity-widget-sitewide-per-page'] ) );
	}
	
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_blog_option( $current_blog->blog_id, 'bp_activity_widget_sitewide_activity', $options );
	}

?>
		<p><label for="bp-activity-widget-sitewide-per-page"><?php _e('Number of Items Per Page:', 'buddypress'); ?> <input class="widefat" id="bp-activity-widget-sitewide-per-page" name="bp-activity-widget-sitewide-per-page" type="text" value="<?php echo attribute_escape( $options['per_page'] ); ?>" style="width: 30%" /></label></p>
		<p><label for="bp-activity-widget-sitewide-items-max"><?php _e('Max Number of Items:', 'buddypress'); ?> <input class="widefat" id="bp-activity-widget-sitewide-items-max" name="bp-activity-widget-sitewide-items-max" type="text" value="<?php echo attribute_escape( $options['max_items'] ); ?>" style="width: 30%" /></label></p>

		<input type="hidden" id="bp-activity-widget-sitewide-submit" name="bp-activity-widget-sitewide-submit" value="1" />
<?php
}

?>