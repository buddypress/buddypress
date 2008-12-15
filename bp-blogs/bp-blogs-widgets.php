<?php

/* Register widgets for blogs component */
function bp_blogs_register_widgets() {
	global $current_blog;

	/* Latest Posts Widget */
	register_sidebar_widget( __('Recent Blog Posts', 'buddypress'), 'bp_blogs_widget_recent_posts');
	register_widget_control( __('Recent Blog Posts', 'buddypress'), 'bp_blogs_widget_recent_posts_control' );
}
add_action( 'plugins_loaded', 'bp_blogs_register_widgets' );


function bp_blogs_widget_recent_posts($args) {
	global $current_blog;
	
    extract($args);
	$options = get_blog_option( $current_blog->blog_id, 'bp_blogs_widget_recent_posts' );
?>
	<?php echo $before_widget; ?>
	<?php echo $before_title
		. $widget_name 
		. $after_title; ?>

	<?php if ( (int)$current_blog->blog_id == 1 ) : ?>
		<?php $posts = bp_blogs_get_latest_posts( null, $options['max_posts'] ) ?>
		<?php $counter = 0; ?>
		
		
		<?php if ( $posts ) : ?>
			<div class="item-options" id="recent-posts-options">
				<?php _e("Site Wide", 'buddypress') ?>
			</div>
			<ul id="recent-posts" class="item-list">
				<?php foreach ( $posts as $post ) : ?>
					<li>
						<div class="item-avatar">
							<a href="<?php echo bp_post_get_permalink( $post, $post->blog_id ) ?>" title="<?php echo apply_filters( 'the_title', $post->post_title ) ?>"><?php echo bp_core_get_avatar( $post->post_author, 1 ) ?></a>
						</div>

						<div class="item">
							<h4 class="item-title"><a href="<?php echo bp_post_get_permalink( $post, $post->blog_id ) ?>" title="<?php echo apply_filters( 'the_title', $post->post_title ) ?>"><?php echo apply_filters( 'the_title', $post->post_title ) ?></a></h4>
							<?php if ( !$counter ) : ?>
								<div class="item-content"><?php echo bp_create_excerpt($post->post_content) ?></div>
							<?php endif; ?>
							<div class="item-meta"><em>by <?php echo bp_core_get_userlink($post->post_author) ?> from the blog "<a href="<?php echo get_blog_option($post->blog_id, 'siteurl') ?>"><?php echo get_blog_option($post->blog_id, 'blogname') ?></a>"</em></div>
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
	<?php endif; ?>

	<?php echo $after_widget; ?>
<?php
}

function bp_blogs_widget_recent_posts_control() {
	global $current_blog;
	
	$options = $newoptions = get_blog_option( $current_blog->blog_id, 'bp_blogs_widget_recent_posts');

	if ( $_POST['bp-blogs-widget-recent-posts-submit'] ) {
		$newoptions['max_posts'] = strip_tags( stripslashes( $_POST['bp-blogs-widget-recent-posts-max'] ) );
	}
	
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_blog_option( $current_blog->blog_id, 'bp_blogs_widget_recent_posts', $options );
	}

	$max_posts = attribute_escape( $options['max_posts'] );
?>
		<p><label for="bp-blogs-widget-recent-posts-max"><?php _e('Max Number of Posts:', 'buddypress'); ?> <input class="widefat" id="bp-blogs-widget-recent-posts-max" name="bp-blogs-widget-recent-posts-max" type="text" value="<?php echo $max_posts; ?>" style="width: 30%" /></label></p>
		<input type="hidden" id="bp-blogs-widget-recent-posts-submit" name="bp-blogs-widget-recent-posts-submit" value="1" />
<?php
}

?>