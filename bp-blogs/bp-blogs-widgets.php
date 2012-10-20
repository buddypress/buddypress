<?php

/**
 * BuddyPress Blogs Widgets
 *
 * @package BuddyPress
 * @subpackage BlogsWidgets
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// @todo not use create_function()
function bp_blogs_register_widgets() {
	global $wpdb;

	if ( bp_is_active( 'activity' ) && (int) $wpdb->blogid == bp_get_root_blog_id() )
		add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Blogs_Recent_Posts_Widget");' ) );
}
add_action( 'bp_register_widgets', 'bp_blogs_register_widgets' );

class BP_Blogs_Recent_Posts_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( false, $name = _x( '(BuddyPress) Recent Networkwide Posts', 'widget name', 'buddypress' ) );
	}

	function widget($args, $instance) {

		extract( $args );

		echo $before_widget;
		echo $before_title . $widget_name . $after_title;

		if ( empty( $instance['max_posts'] ) || !$instance['max_posts'] )
			$instance['max_posts'] = 10; ?>

		<?php // Override some of the contextually set parameters for bp_has_activities() ?>
		<?php if ( bp_has_activities( array( 'action' => 'new_blog_post', 'max' => $instance['max_posts'], 'per_page' => $instance['max_posts'], 'user_id' => 0, 'scope' => false, 'object' => false, 'primary_id' => false ) ) ) : ?>

			<ul id="blog-post-list" class="activity-list item-list">

				<?php while ( bp_activities() ) : bp_the_activity(); ?>

					<li>
						<div class="activity-content" style="margin: 0">

							<div class="activity-header">
								<?php bp_activity_action() ?>
							</div>

							<?php if ( bp_get_activity_content_body() ) : ?>
								<div class="activity-inner">
									<?php bp_activity_content_body() ?>
								</div>
							<?php endif; ?>

						</div>
					</li>

				<?php endwhile; ?>

			</ul>

		<?php else : ?>
			<div id="message" class="info">
				<p><?php _e( 'Sorry, there were no posts found. Why not write one?', 'buddypress' ) ?></p>
			</div>
		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_posts'] = strip_tags( $new_instance['max_posts'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_posts' => 10 ) );
		$max_posts = strip_tags( $instance['max_posts'] );
		?>

		<p><label for="bp-blogs-widget-posts-max"><?php _e('Max posts to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_posts' ); ?>" name="<?php echo $this->get_field_name( 'max_posts' ); ?>" type="text" value="<?php echo esc_attr( $max_posts ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}
