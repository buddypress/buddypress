<?php

/* Register widgets for groups component */
function groups_register_widgets() {
	global $current_blog;
	
	/* Site welcome widget */
	wp_register_sidebar_widget( 'buddypress-groups', __( 'Groups', 'buddypress' ), 'groups_widget_groups_list' );
	wp_register_widget_control( 'buddypress-groups', __( 'Groups', 'buddypress' ), 'groups_widget_groups_list_control' );
	
	/* Include the javascript needed for activated widgets only */
	if ( is_active_widget( 'groups_widget_groups_list' ) ) {
		wp_enqueue_script( 'groups_widget_groups_list-js', BP_PLUGIN_URL . '/bp-groups/js/widget-groups.js', array('jquery', 'jquery-livequery-pack') );		
		wp_enqueue_style( 'groups_widget_members-css', BP_PLUGIN_URL . '/bp-groups/css/widget-groups.css' );		
	}
}
add_action( 'plugins_loaded', 'groups_register_widgets' );

/*** GROUPS WIDGET *****************/

function groups_widget_groups_list($args) {
	global $current_blog, $bp;
	
    extract($args);
	$options = get_blog_option( $current_blog->blog_id, 'groups_widget_groups_list' );
?>
	<?php echo $before_widget; ?>
	<?php echo $before_title
		. $widget_name 
		. $after_title; ?>

	<?php 
	if ( empty( $options['max_groups'] ) || !$options['max_groups'] )
		$options['max_groups'] = 5;
		
	if ( !$groups = wp_cache_get( 'popular_groups', 'bp' ) ) {
		$groups = groups_get_popular( $options['max_groups'], 1 );
		wp_cache_set( 'popular_groups', $groups, 'bp' );
	}
	?>

	<?php if ( $groups['groups'] ) : ?>
		<div class="item-options" id="groups-list-options">
			<img id="ajax-loader-groups" src="<?php echo $bp->groups->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" /> 
			<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="newest-groups"><?php _e("Newest", 'buddypress') ?></a> | 
			<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="recently-active-groups"><?php _e("Active", 'buddypress') ?></a> | 
			<a href="<?php echo site_url() . '/' . $bp->groups->slug ?>" id="popular-groups" class="selected"><?php _e("Popular", 'buddypress') ?></a>
		</div>
		<ul id="groups-list" class="item-list">
			<?php foreach ( $groups['groups'] as $group_id ) : ?>
				<?php 
				if ( !$group = wp_cache_get( 'groups_group_nouserdata_' . $group_id->group_id, 'bp' ) ) {
					$group = new BP_Groups_Group( $group_id->group_id, false, false );
					wp_cache_set( 'groups_group_nouserdata_' . $group_id->group_id, $group, 'bp' );
				}	
				?>
				<li>
					<div class="item-avatar">
						<a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_avatar_thumb( $group ); ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php echo bp_get_group_permalink( $group ) ?>" title="<?php echo bp_get_group_name( $group ) ?>"><?php echo bp_get_group_name( $group ) ?></a></div>
						<div class="item-meta">
						<span class="activity">
							<?php 
							if ( 1 == $group->total_member_count )
								echo $group->total_member_count . __(' member', 'buddypress');
							else
								echo $group->total_member_count . __(' members', 'buddypress');
							?>
						</span></div>
					</div>
				</li>
				<?php $counter++; ?>	
			<?php endforeach; ?>
		</ul>
		
		<?php 
		if ( function_exists('wp_nonce_field') )
			wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' );
		?>
		
		<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo attribute_escape( $options['max_groups'] ); ?>" />
		
	<?php else: ?>
		<div class="widget-error">
			<?php _e('There are no groups to display.', 'buddypress') ?>
		</div>
	<?php endif; ?>
	
	<?php echo $after_widget; ?>
<?php
}

function groups_widget_groups_list_control() {
	global $current_blog;
	
	$options = $newoptions = get_blog_option( $current_blog->blog_id, 'groups_widget_groups_list');

	if ( $_POST['groups-widget-groups-list-submit'] ) {
		$newoptions['max_groups'] = strip_tags( stripslashes( $_POST['groups-widget-groups-list-max'] ) );
	}
	
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_blog_option( $current_blog->blog_id, 'groups_widget_groups_list', $options );
	}

?>
		<p><label for="groups-widget-groups-list-max"><?php _e('Maximum number of groups to show:', 'buddypress'); ?><br /> <input class="widefat" id="groups-widget-groups-list-max" name="groups-widget-groups-list-max" type="text" value="<?php echo attribute_escape( $options['max_groups'] ); ?>" style="width: 30%" /></label></p>
		<input type="hidden" id="groups-widget-groups-list-submit" name="groups-widget-groups-list-submit" value="1" />
<?php
}
