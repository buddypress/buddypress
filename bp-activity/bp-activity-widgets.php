<?php

/* Register widgets for blogs component */
function bp_activity_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Activity_Widget");') );
}
add_action( 'plugins_loaded', 'bp_activity_register_widgets' );

class BP_Activity_Widget extends WP_Widget {
	function bp_activity_widget() {
		parent::WP_Widget( false, $name = 'Site Wide Activity' );
		wp_enqueue_style( 'bp-activity-widget-activity-css', BP_PLUGIN_URL . '/bp-activity/css/widget-activity.css' );		
	}

	function widget($args, $instance) {
		global $bp;
		
	    extract( $args );
		
		echo $before_widget;
		echo $before_title
		   . $widget_name 
		   . $after_title; ?>
	
		<?php
		if ( empty( $instance['max_items'] ) || !$instance['max_items'] )
			$instance['max_items'] = 30; ?>
		
		<?php 
		if ( !$activity = wp_cache_get( 'sitewide_activity', 'bp' ) ) {
			$activity = bp_activity_get_sitewide_activity( $instance['max_items'] );
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

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_items'] = strip_tags( $new_instance['max_items'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_items' => 30 ) );
		$max_items = strip_tags( $instance['max_items'] );
		?>

		<p><label for="bp-core-widget-members-max"><?php _e('Max items to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo attribute_escape( $max_items ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}
?>