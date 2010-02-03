<?php

/* Register widgets for blogs component */
function bp_blogs_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Blogs_Recent_Posts_Widget");') );
}
add_action( 'template_redirect', 'bp_blogs_register_widgets' );

class BP_Blogs_Recent_Posts_Widget extends WP_Widget {
	function bp_blogs_recent_posts_widget() {
		parent::WP_Widget( false, $name = __( 'Recent Site Wide Posts', 'buddypress' ) );
	}

	function widget($args, $instance) {
		global $bp;

	    extract( $args );

		echo $before_widget;
		echo $before_title
		   . $widget_name
		   . $after_title; ?>

		<?php
		if ( empty( $instance['max_posts'] ) || !$instance['max_posts'] )
			$instance['max_posts'] = 10; ?>

		<?php $posts = bp_blogs_get_latest_posts( null, $instance['max_posts'] ) ?>
		<?php $counter = 0; ?>

		<?php if ( $posts ) : ?>
			<div class="item-options" id="recent-posts-options">
				<?php _e("Site Wide", 'buddypress') ?>
			</div>
			<ul id="recent-posts" class="item-list">
				<?php foreach ( (array)$posts as $post ) : ?>
					<li>
						<div class="item-avatar">
							<a href="<?php echo bp_post_get_permalink( $post, $post->blog_id ) ?>" title="<?php echo apply_filters( 'the_title', $post->post_title ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $post->post_author, 'type' => 'thumb' ) ) ?></a>
						</div>

						<div class="item">
							<h4 class="item-title"><a href="<?php echo bp_post_get_permalink( $post, $post->blog_id ) ?>" title="<?php echo apply_filters( 'the_title', $post->post_title ) ?>"><?php echo apply_filters( 'the_title', $post->post_title ) ?></a></h4>
							<?php if ( !$counter ) : ?>
								<div class="item-content"><?php echo bp_create_excerpt($post->post_content) ?></div>
							<?php endif; ?>
							<div class="item-meta"><em><?php printf( __( 'by %s from the blog <a href="%s">%s</a>', 'buddypress' ), bp_core_get_userlink( $post->post_author ), get_blog_option( $post->blog_id, 'siteurl' ), get_blog_option( $post->blog_id, 'blogname' ) ) ?></em></div>
						</div>
					</li>
					<?php $counter++; ?>
				<?php endforeach; ?>
			</ul>
		<?php else: ?>
			<div class="widget-error">
				<?php _e('There are no recent blog posts, why not write one?', 'buddypress') ?>
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

		<p><label for="bp-blogs-widget-posts-max"><?php _e('Max posts to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_posts' ); ?>" name="<?php echo $this->get_field_name( 'max_posts' ); ?>" type="text" value="<?php echo attribute_escape( $max_posts ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}
?>