<?php

/* Register widgets for groups component */
function groups_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Groups_Widget");') );	
}
add_action( 'plugins_loaded', 'groups_register_widgets' );

/*** GROUPS WIDGET *****************/

class BP_Groups_Widget extends WP_Widget {
	function bp_groups_widget() {
		parent::WP_Widget( false, $name = 'Groups' );
		wp_enqueue_script( 'groups_widget_groups_list-js', BP_PLUGIN_URL . '/bp-groups/js/widget-groups.js', array('jquery', 'jquery-livequery-pack') );		
		wp_enqueue_style( 'groups_widget_members-css', BP_PLUGIN_URL . '/bp-groups/css/widget-groups.css' );		
	}

	function widget($args, $instance) {
		global $bp;
		
	    extract( $args );
		
		echo $before_widget;
		echo $before_title
		   . $widget_name 
		   . $after_title; ?>
	
		<?php
		if ( empty( $instance['max_groups'] ) || !$instance['max_groups'] )
			$instance['max_groups'] = 5; ?>
		
		<?php if ( bp_has_site_groups( 'type=popular&max=' . $instance['max_groups'] ) ) : ?>
			<div class="item-options" id="groups-list-options">
				<img id="ajax-loader-groups" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" /> 
				<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="newest-groups"><?php _e("Newest", 'buddypress') ?></a> | 
				<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="recently-active-groups"><?php _e("Active", 'buddypress') ?></a> | 
				<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="popular-groups" class="selected"><?php _e("Popular", 'buddypress') ?></a>
			</div>
			
			<ul id="groups-list" class="item-list">
				<?php while ( bp_site_groups() ) : bp_the_site_group(); ?>
					<li>
						<div class="item-avatar">
							<a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_the_site_group_link() ?>" title="<?php bp_the_site_group_name() ?>"><?php bp_the_site_group_name() ?></a></div>
							<div class="item-meta"><span class="activity"><?php bp_the_site_group_member_count() ?></span></div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>		
			<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
			<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo attribute_escape( $instance['max_groups'] ); ?>" />
			
		<?php else: ?>

			<div class="widget-error">
				<?php _e('There are no groups to display.', 'buddypress') ?>
			</div>

		<?php endif; ?>
			
		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_groups'] = strip_tags( $new_instance['max_groups'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_groups' => 5 ) );
		$max_groups = strip_tags( $instance['max_groups'] );
		?>

		<p><label for="bp-groups-widget-groups-max"><?php _e('Max groups to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_groups' ); ?>" name="<?php echo $this->get_field_name( 'max_groups' ); ?>" type="text" value="<?php echo attribute_escape( $max_groups ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}
?>
