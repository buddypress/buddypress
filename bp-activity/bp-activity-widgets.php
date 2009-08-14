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
		   . $widget_name . 
			 ' <a class="rss-image" href="' . bp_get_sitewide_activity_feed_link() . '" title="' . __( 'Site Wide Activity RSS Feed', 'buddypress' ) . '">' . __( '[RSS]', 'buddypress' ) . '</a>' 
		   . $after_title; ?>
	
	<?php if ( bp_has_activities( 'type=sitewide&max=' . $instance['max_items'] . '&per_page=' . $instance['per_page'] ) ) : ?>
		<div class="pagination">
			<div class="pag-count" id="activity-count">
				<?php bp_activity_pagination_count() ?>
			</div>
		
			<div class="pagination-links" id="activity-pag">
				&nbsp; <?php bp_activity_pagination_links() ?>
			</div>
		</div>
		
		<ul id="activity-filter-links">
			<?php bp_activity_filter_links() ?>
		</ul>
		
		<ul id="site-wide-stream" class="activity-list">
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li class="<?php bp_activity_css_class() ?>">
				<div class="activity-avatar">
					<?php bp_activity_user_avatar() ?>
				</div>
				
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

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_items'] = strip_tags( $new_instance['max_items'] );
		$instance['per_page'] = strip_tags( $new_instance['per_page'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_items' => 200, 'per_page' => 25 ) );
		$per_page = strip_tags( $instance['per_page'] );
		$max_items = strip_tags( $instance['max_items'] );
		?>

		<p><label for="bp-activity-widget-sitewide-per-page"><?php _e('Number of Items Per Page:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'per_page' ); ?>" name="<?php echo $this->get_field_name( 'per_page' ); ?>" type="text" value="<?php echo attribute_escape( $per_page ); ?>" style="width: 30%" /></label></p>
		<p><label for="bp-core-widget-members-max"><?php _e('Max items to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo attribute_escape( $max_items ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}
?>