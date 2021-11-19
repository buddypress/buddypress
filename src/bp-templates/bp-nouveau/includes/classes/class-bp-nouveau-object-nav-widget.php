<?php
/**
 * BP Sidebar Item Nav_Widget class.
 *
 * @since 3.0.0
 * @version 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Sidebar Item Nav_Widget.
 *
 * Adds a widget to move avatar/item nav into the sidebar.
 *
 * @since 3.0.0
 */
class BP_Nouveau_Object_Nav_Widget extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 * @since 9.0.0 Adds the `show_instance_in_rest` property to Widget options.
	 */
	public function __construct() {
		$widget_ops = array(
			'description'           => __( 'Displays BuddyPress primary nav in the sidebar of your site. Make sure to use it as the first widget of the sidebar and only once.', 'buddypress' ),
			'classname'             => 'widget_nav_menu buddypress_object_nav',
			'show_instance_in_rest' => true,
		);

		parent::__construct(
			'bp_nouveau_sidebar_object_nav_widget',
			__( '(BuddyPress) Primary navigation', 'buddypress' ),
			$widget_ops
		);
	}

	/**
	 * Register the widget
	 *
	 * @since 3.0.0
	 */
	public static function register_widget() {
		register_widget( 'BP_Nouveau_Object_Nav_Widget' );
	}

	/**
	 * Displays the output, the button to post new support topics
	 *
	 * @since 3.0.0
	 *
	 * @param mixed   $args     Arguments
	 * @param unknown $instance
	 */
	public function widget( $args, $instance ) {
		if ( ! is_buddypress() || bp_is_group_create() ) {
			return;
		}

		/**
		 * Filters the nav widget args for the BP_Nouveau_Object_Nav_Widget widget.
		 *
		 * @since 3.0.0
		 *
		 * @param array $value Array of arguments {
		 *     @param bool $bp_nouveau_widget_title Whether or not to assign a title for the widget.
		 * }
		 */
		$item_nav_args = bp_parse_args(
			$instance,
			apply_filters(
				'bp_nouveau_object_nav_widget_args',
				array( 'bp_nouveau_widget_title' => true )
			),
			'widget_object_nav'
		);

		$title = '';

		if ( ! empty( $item_nav_args['bp_nouveau_widget_title'] ) ) {
			if ( bp_is_group() ) {
				$title = bp_get_current_group_name();
			} elseif ( bp_is_user() ) {
				$title = bp_get_displayed_user_fullname();
			} elseif ( bp_get_directory_title( bp_current_component() ) ) {
				$title = bp_get_directory_title( bp_current_component() );
			}
		}

		/**
		 * Filters the BP_Nouveau_Object_Nav_Widget widget title.
		 *
		 * @since 3.0.0
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if ( bp_is_user() ) {
			bp_get_template_part( 'members/single/parts/item-nav' );
		} elseif ( bp_is_group() ) {
			bp_get_template_part( 'groups/single/parts/item-nav' );
		} elseif ( bp_is_directory() ) {
			bp_get_template_part( 'common/nav/directory-nav' );
		}

		echo $args['after_widget'];
	}

	/**
	 * Update the new support topic widget options (title)
	 *
	 * @since 3.0.0
	 *
	 * @param array $new_instance The new instance options
	 * @param array $old_instance The old instance options
	 *
	 * @return array the instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                            = $old_instance;
		$instance['bp_nouveau_widget_title'] = (bool) $new_instance['bp_nouveau_widget_title'];

		return $instance;
	}

	/**
	 * Output the new support topic widget options form
	 *
	 * @since 3.0.0
	 *
	 * @param $instance Instance
	 *
	 * @return string HTML Output
	 */
	public function form( $instance ) {
		$defaults = array(
			'bp_nouveau_widget_title' => true,
		);

		$instance = bp_parse_args(
			(array) $instance,
			$defaults,
			'widget_object_nav_form'
		);

		$bp_nouveau_widget_title = (bool) $instance['bp_nouveau_widget_title'];
		?>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $bp_nouveau_widget_title, true ); ?> id="<?php echo $this->get_field_id( 'bp_nouveau_widget_title' ); ?>" name="<?php echo $this->get_field_name( 'bp_nouveau_widget_title' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'bp_nouveau_widget_title' ); ?>"><?php esc_html_e( 'Include navigation title', 'buddypress' ); ?></label>
		</p>

		<?php
	}
}
