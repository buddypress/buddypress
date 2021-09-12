<?php
/**
 * BuddyPress Blogs Recent Posts Widget.
 *
 * @package BuddyPress
 * @subpackage BlogsWidgets
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Recent Networkwide Posts widget.
 *
 * @since 1.0.0
 */
class BP_Blogs_Recent_Posts_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 * @since 9.0.0 Adds the `show_instance_in_rest` property to Widget options.
	 */
	public function __construct() {
		$widget_ops = array(
			'description'                 => __( 'A list of recently published posts from across your network.', 'buddypress' ),
			'classname'                   => 'widget_bp_blogs_widget buddypress widget',
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( false, $name = _x( '(BuddyPress) Recent Networkwide Posts', 'widget name', 'buddypress' ), $widget_ops );
	}

	/**
	 * Display the networkwide posts widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		global $activities_template;

		$title = ! empty( $instance['title'] )
			? esc_html( $instance['title'] )
			: __( 'Recent Networkwide Posts', 'buddypress' );

		if ( ! empty( $instance['link_title'] ) ) {
			$title = '<a href="' . bp_get_blogs_directory_permalink() . '">' . esc_html( $title ) . '</a>';
		}

		/**
		 * Filters the Blogs Recent Posts widget title.
		 *
		 * @since 2.2.0
		 * @since 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'];

		$max_limit = bp_get_widget_max_count_limit( __CLASS__ );
		if ( empty( $instance['max_posts'] ) || $instance['max_posts'] > $max_limit ) {
			$instance['max_posts'] = 10;
		}

		$after_widget = $args['after_widget'];

		// Override some of the contextually set parameters for bp_has_activities().
		$args = array(
			'action'     => 'new_blog_post',
			'max'        => $instance['max_posts'],
			'per_page'   => $instance['max_posts'],
			'user_id'    => 0,
			'scope'      => false,
			'object'     => false,
			'primary_id' => false
		);

		// Back up global.
		$old_activities_template = $activities_template;

		?>

		<?php if ( bp_has_activities( $args ) ) : ?>

			<ul id="blog-post-list" class="activity-list item-list">

				<?php while ( bp_activities() ) : bp_the_activity(); ?>

					<li>
						<div class="activity-content" style="margin: 0">
							<div class="activity-header"><?php bp_activity_action(); ?></div>

							<?php if ( bp_get_activity_content_body() ) : ?>

								<div class="activity-inner"><?php bp_activity_content_body(); ?></div>

							<?php endif; ?>

						</div>
					</li>

				<?php endwhile; ?>

			</ul>

		<?php else : ?>

			<div id="message" class="info">
				<p><?php _e( 'Sorry, there were no posts found. Why not write one?', 'buddypress' ); ?></p>
			</div>

		<?php endif; ?>

		<?php echo $after_widget;

		// Restore the global.
		$activities_template = $old_activities_template;
	}

	/**
	 * Update the networkwide posts widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$max_limit = bp_get_widget_max_count_limit( __CLASS__ );

		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['max_posts']  = $new_instance['max_posts'] > $max_limit ? $max_limit : intval( $new_instance['max_posts'] );
		$instance['link_title'] = ! empty( $new_instance['link_title'] );

		return $instance;
	}

	/**
	 * Output the networkwide posts widget options form.
	 *
	 * @param array $instance Settings for this widget.
	 */
	public function form( $instance ) {
		$instance = bp_parse_args(
			(array) $instance,
			array(
				'title'      => __( 'Recent Networkwide Posts', 'buddypress' ),
				'max_posts'  => 10,
				'link_title' => false,
			)
		);

		$max_limit = bp_get_widget_max_count_limit( __CLASS__ );

		$title      = strip_tags( $instance['title'] );
		$max_posts  = $instance['max_posts'] > $max_limit ? $max_limit : intval( $instance['max_posts'] );
		$link_title = (bool) $instance['link_title'];

		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _ex( 'Title:', 'Label for the Title field of the Recent Networkwide Posts widget', 'buddypress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%;" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'link_title' ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( 'link_title' ); ?>" value="1" <?php checked( $link_title ); ?> /> <?php _e( 'Link widget title to Blogs directory', 'buddypress' ); ?></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_posts' ); ?>"><?php _e( 'Max posts to show:', 'buddypress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_posts' ); ?>" name="<?php echo $this->get_field_name( 'max_posts' ); ?>" type="number" min="1" max="<?php echo esc_attr( $max_limit ); ?>" value="<?php echo esc_attr( $max_posts ); ?>" style="width: 30%" /></label></p>
		<?php
	}
}
