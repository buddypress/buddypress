<?php

/* Register widgets for blogs component */
function bp_activity_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Activity_Widget");') );
}
add_action( 'plugins_loaded', 'bp_activity_register_widgets' );

class BP_Activity_Widget extends WP_Widget {
	function bp_activity_widget() {
		parent::WP_Widget( false, $name = __( 'Site Wide Activity', 'buddypress' ) );
	}

	function widget($args, $instance) {
		global $bp;

		extract( $args );

		echo $before_widget;
		echo $before_title
		   . $widget_name .
			 ' <a class="rss-image" href="' . bp_get_sitewide_activity_feed_link() . '" title="' . __( 'Site Wide Activity RSS Feed', 'buddypress' ) . '">' . __( '[RSS]', 'buddypress' ) . '</a>'
		   . $after_title; ?>

	<?php if ( bp_has_activities( 'type=sitewide&max=' . $instance['max_items'] . '&per_page=' . $instance['per_page'] . '&display_comments=threaded' ) ) : ?>

		<?php if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) ) : ?>
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
		<?php endif; ?>

		<ul id="site-wide-stream" class="activity-list">
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li class="<?php bp_activity_css_class() ?>" id="activity-<?php bp_activity_id() ?>">
				<div class="activity-avatar">
					<?php bp_activity_avatar('width=40&height=40') ?>
				</div>

				<div class="activity-content">
					<?php bp_activity_content() ?>

					<?php if ( is_user_logged_in() ) : ?>
					<div class="activity-meta">
						<a href="#acomment-<?php bp_activity_id() ?>" class="acomment-reply" id="acomment-comment-<?php bp_activity_id() ?>"><?php _e( 'Comment', 'buddypress' ) ?> (<?php bp_activity_comment_count() ?>)</a>
					</div>
					<?php endif; ?>
				</div>

				<div class="activity-comments">
					<?php bp_activity_comments() ?>

					<?php if ( is_user_logged_in() ) : ?>
					<form action="" method="post" name="activity-comment-form" id="ac-form-<?php bp_activity_id() ?>" class="ac-form">
						<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( 'width=25&height=25' ) ?></div>
						<div class="ac-reply-content">
							<textarea id="ac-input-<?php bp_activity_id() ?>" class="ac-input" name="ac-input-<?php bp_activity_id() ?>"></textarea>
							<input type="submit" name="ac-form-submit" value="<?php _e( 'Post', 'buddypress' ) ?> &rarr;" />
						</div>
						<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ) ?>
					</form>
					<?php endif; ?>
				</div>

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