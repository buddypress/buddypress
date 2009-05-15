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
		. $widget_name 
		. $after_title; ?>
		
	<?php 
	if ( empty( $options['max_items'] ) ) {
		$options['max_items'] = 20;
	}
	?>
	
	<?php 
	if ( !$activity = wp_cache_get( 'sitewide_activity', 'bp' ) ) {
		$activity = bp_activity_get_sitewide_activity( $options['max_items'] );
		wp_cache_set( 'sitewide_activity', $activity, 'bp' );
	}
	?>

	<?php if ( $activity['activities'] ) : ?>
		<div class="item-options" id="activity-list-options">
			<img src="<?php echo $bp->activity->image_base; ?>/rss.png" alt="<?php _e( 'RSS Feed', 'buddypress' ) ?>" /> <a href="<?php bp_sitewide_activity_feed_link() ?>" title="<?php _e( 'Site Wide Activity RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a>
		</div>
		<ul id="site-wide-stream" class="activity-list">
		<?php foreach( $activity['activities'] as $item ) : ?>
			<li class="<?php echo $item['component_name'] ?>">
				<?php echo apply_filters( 'bp_get_activity_content', bp_activity_content_filter( $item['content'], $item['date_recorded'], '', true, false, true ) ); ?>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<div class="widget-error">
			<?php _e('There has been no recent site activity.', 'buddypress') ?>
		</div>
	<?php endif; ?>

	<?php echo $after_widget; ?>
<?php
}

function bp_activity_widget_sitewide_activity_control() {
	global $current_blog;
	
	$options = $newoptions = get_blog_option( $current_blog->blog_id, 'bp_activity_widget_sitewide_activity');

	if ( $_POST['bp-activity-widget-sitewide-submit'] ) {
		$newoptions['max_items'] = strip_tags( stripslashes( $_POST['bp-activity-widget-sitewide-items-max'] ) );
	}
	
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_blog_option( $current_blog->blog_id, 'bp_activity_widget_sitewide_activity', $options );
	}

?>
		<p><label for="bp-activity-widget-sitewide-items-max"><?php _e('Max Number of Items:', 'buddypress'); ?> <input class="widefat" id="bp-activity-widget-sitewide-items-max" name="bp-activity-widget-sitewide-items-max" type="text" value="<?php echo attribute_escape( $options['max_items'] ); ?>" style="width: 30%" /></label></p>
		<input type="hidden" id="bp-activity-widget-sitewide-submit" name="bp-activity-widget-sitewide-submit" value="1" />
<?php
}

?>