<?php
/**
 * BuddyPress Members Recently Active widget.
 *
 * @package BuddyPress
 * @subpackage MembersWidgets
 * @since 1.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Recently Active Members Widget.
 *
 * @since 1.0.3
 */
class BP_Core_Recently_Active_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$name        = _x( '(BuddyPress) Recently Active Members', 'widget name', 'buddypress' );
		$description = __( 'Profile photos of recently active members', 'buddypress' );
		parent::__construct( false, $name, array(
			'description'                 => $description,
			'classname'                   => 'widget_bp_core_recently_active_widget buddypress widget',
			'customize_selective_refresh' => true,
		) );
	}

	/**
	 * Display the Recently Active widget.
	 *
	 * @since 1.0.3
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
		 * Filters the title of the Recently Active widget.
		 *
		 * @since 1.8.0
		 * @since 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'];

		$max_limit   = bp_get_widget_max_count_limit( __CLASS__ );
		$max_members = $settings['max_members'] > $max_limit ? $max_limit : (int) $settings['max_members'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => 'active',
			'per_page'        => $max_members,
			'max'             => $max_members,
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
						<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip="<?php bp_member_name(); ?>"><?php bp_member_avatar(); ?></a>
					</div>

				<?php endwhile; ?>

			</div>

		<?php else: ?>

			<div class="widget-error">
				<?php esc_html_e( 'There are no recently active members', 'buddypress' ); ?>
			</div>

		<?php endif; ?>

		<?php echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Recently Active widget options.
	 *
	 * @since 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$max_limit = bp_get_widget_max_count_limit( __CLASS__ );

		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['max_members'] = $new_instance['max_members'] > $max_limit ? $max_limit : intval( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Recently Active widget options form.
	 *
	 * @since 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {
		$max_limit = bp_get_widget_max_count_limit( __CLASS__ );

		// Get widget settings.
		$settings    = $this->parse_settings( $instance );
		$title       = strip_tags( $settings['title'] );
		$max_members = $settings['max_members'] > $max_limit ? $max_limit : intval( $settings['max_members'] );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="number" min="1" max="<?php echo esc_attr( $max_limit ); ?>" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

	<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since 2.3.0
	 *
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args( $instance, array(
			'title' 	     => __( 'Recently Active Members', 'buddypress' ),
			'max_members' 	 => 15,
		), 'recently_active_members_widget_settings' );
	}
}
