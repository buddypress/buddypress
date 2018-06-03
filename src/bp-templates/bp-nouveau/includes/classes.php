<?php
/**
 * Common Classes
 *
 * @since 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds a group of BP_Button
 *
 * @since 3.0.0
 */
class BP_Buttons_Group {

	/**
	 * The parameters of the Group of buttons
	 *
	 * @var array
	 */
	protected $group = array();

	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional array having the following parameters {
	 *     @type string $id                A string to use as the unique ID for the button. Required.
	 *     @type int    $position          Where to insert the Button. Defaults to 99.
	 *     @type string $component         The Component's the button is build for (eg: Activity, Groups..). Required.
	 *     @type bool   $must_be_logged_in Whether the button should only be displayed to logged in users. Defaults to True.
	 *     @type bool   $block_self        Optional. True if the button should be hidden when a user is viewing his own profile.
	 *                                     Defaults to False.
	 *     @type string $parent_element    Whether to use a wrapper. Defaults to false.
	 *     @type string $parent_attr       set an array of attributes for the parent element.
	 *     @type string $button_element    Set this to 'button', 'img', or 'a', defaults to anchor.
	 *     @type string $button_attr       Any attributes required for the button_element
	 *     @type string $link_text         The text of the link. Required.
	 * }
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $arg ) {
			$r = bp_parse_args(
				(array) $arg,
				array(
					'id'                => '',
					'position'          => 99,
					'component'         => '',
					'must_be_logged_in' => true,
					'block_self'        => false,
					'parent_element'    => false,
					'parent_attr'       => array(),
					'button_element'    => 'a',
					'button_attr'       => array(),
					'link_text'         => '',
				),
				'buttons_group_constructor'
			);

			// Just don't set the button if a param is missing
			if ( empty( $r['id'] ) || empty( $r['component'] ) || empty( $r['link_text'] ) ) {
				continue;
			}

			$r['id'] = sanitize_key( $r['id'] );

			// If the button already exist don't add it
			if ( isset( $this->group[ $r['id'] ] ) ) {
				continue;
			}

			/*
			 * If, in bp_nouveau_get_*_buttons(), we pass through a false value for 'parent_element'
			 * but we have attributtes for it in the array, let's default to setting a div.
			 *
			 * Otherwise, the original false value will be passed through to BP buttons.
			 * @todo: this needs review, probably trying to be too clever
			 */
			if ( ( ! empty( $r['parent_attr'] ) ) && false === $r['parent_element'] ) {
				$r['parent_element'] = 'div';
			}

			$this->group[ $r['id'] ] = $r;
		}
	}


	/**
	 * Sort the Buttons of the group according to their position attribute
	 *
	 * @since 3.0.0
	 *
	 * @param array the list of buttons to sort.
	 *
	 * @return array the list of buttons sorted.
	 */
	public function sort( $buttons ) {
		$sorted = array();

		foreach ( $buttons as $button ) {
			$position = 99;

			if ( isset( $button['position'] ) ) {
				$position = (int) $button['position'];
			}

			// If position is already taken, move to the first next available
			if ( isset( $sorted[ $position ] ) ) {
				$sorted_keys = array_keys( $sorted );

				do {
					$position += 1;
				} while ( in_array( $position, $sorted_keys, true ) );
			}

			$sorted[ $position ] = $button;
		}

		ksort( $sorted );
		return $sorted;
	}

	/**
	 * Get the BuddyPress buttons for the group
	 *
	 * @since 3.0.0
	 *
	 * @param bool $sort whether to sort the buttons or not.
	 *
	 * @return array An array of HTML links.
	 */
	public function get( $sort = true ) {
		$buttons = array();

		if ( empty( $this->group ) ) {
			return $buttons;
		}

		if ( true === $sort ) {
			$this->group = $this->sort( $this->group );
		}

		foreach ( $this->group as $key_button => $button ) {
			// Reindex with ids.
			if ( true === $sort ) {
				$this->group[ $button['id'] ] = $button;
				unset( $this->group[ $key_button ] );
			}

			$buttons[ $button['id'] ] = bp_get_button( $button );
		}

		return $buttons;
	}

	/**
	 * Update the group of buttons
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional. See the __constructor for a description of this argument.
	 */
	public function update( $args = array() ) {
		foreach ( $args as $id => $params ) {
			if ( isset( $this->group[ $id ] ) ) {
				$this->group[ $id ] = bp_parse_args(
					$params,
					$this->group[ $id ],
					'buttons_group_update'
				);
			}
		}
	}
}

/**
 * BP Sidebar Item Nav_Widget
 *
 * Adds a widget to move avatar/item nav into the sidebar
 *
 * @since 3.0.0
 *
 * @uses WP_Widget
 */
class BP_Nouveau_Object_Nav_Widget extends WP_Widget {
	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description' => __( 'Displays BuddyPress primary nav in the sidebar of your site. Make sure to use it as the first widget of the sidebar and only once.', 'buddypress' ),
			'classname'   => 'widget_nav_menu buddypress_object_nav',
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
