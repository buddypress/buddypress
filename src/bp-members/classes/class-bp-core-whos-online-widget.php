<?php
/**
 * BuddyPress Members Who's Online Widget.
 *
 * @package BuddyPress
 * @subpackage MembersWidgets
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Who's Online Widget.
 *
 * @since 1.0.3
 */
class BP_Core_Whos_Online_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	public function __construct() {
		$name        = _x( "(BuddyPress) Who's Online", 'widget name', 'buddypress' );
		$description = __( 'Profile photos of online users', 'buddypress' );
		parent::__construct( false, $name, array(
			'description' => $description,
			'classname' => 'widget_bp_core_whos_online_widget buddypress widget',
		) );
	}

	/**
	 * Display the Who's Online widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		global $members_template;

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		/**
		 * Filters the title of the Who's Online widget.
		 *
		 * @since 1.8.0
		 * @since 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );

		echo $args['before_widget']
		   . $args['before_title']
		   . $title
		   . $args['after_title'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => 'online',
			'per_page'        => $settings['max_members'],
			'max'             => $settings['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);

		// Back up global.
		$old_members_template = $members_template;

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>

			<div class="avatar-block">

				<?php while ( bp_members() ) : bp_the_member(); ?>

					<div class="item-avatar">
						<a href="<?php bp_member_permalink(); ?>" title="<?php bp_member_name(); ?>"><?php bp_member_avatar(); ?></a>
					</div>

				<?php endwhile; ?>

			</div>

		<?php else: ?>

			<div class="widget-error">
				<?php esc_html_e( 'There are no users currently online', 'buddypress' ); ?>
			</div>

		<?php endif; ?>

		<?php echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Who's Online widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Who's Online widget options form.
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings    = $this->parse_settings( $instance );
		$title       = strip_tags( $settings['title'] );
		$max_members = strip_tags( $settings['max_members'] ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

	<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since 2.3.0
	 *
	 * @uses bp_parse_args() To merge widget settings into defaults.
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args( $instance, array(
			'title' 	     => __( "Who's Online", 'buddypress' ),
			'max_members' 	 => 15,
		), 'members_widget_settings' );
	}
}
