<?php
/**
 * BuddyPress Members Widget.
 *
 * @package BuddyPress
 * @subpackage MembersWidgets
 * @since 1.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Members Widget.
 *
 * @since 1.0.3
 */
class BP_Core_Members_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Setup widget name & description.
		$name        = _x( '(BuddyPress) Members', 'widget name', 'buddypress' );
		$description = __( 'A dynamic list of recently active, popular, and newest members', 'buddypress' );

		// Call WP_Widget constructor.
		parent::__construct( false, $name, array(
			'description'                 => $description,
			'classname'                   => 'widget_bp_core_members_widget buddypress widget',
			'customize_selective_refresh' => true,
		) );

		if ( is_customize_preview() || is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'bp-widget-members' );
	}

	/**
	 * Display the Members widget.
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
		 * Filters the title of the Members widget.
		 *
		 * @since 1.8.0
		 * @since 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );
		$title = $settings['link_title'] ? '<a href="' . bp_get_members_directory_permalink() . '">' . $title . '</a>' : $title;

		/**
		 * Filters the separator of the member widget links.
		 *
		 * @since 2.4.0
		 *
		 * @param string $separator Separator string. Default '|'.
		 */
		$separator = apply_filters( 'bp_members_widget_separator', '|' );

		// Output before widget HTMl, title (and maybe content before & after it).
		echo $args['before_widget']
		   . $args['before_title']
		   . $title
		   . $args['after_title'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => $settings['member_default'],
			'per_page'        => $settings['max_members'],
			'max'             => $settings['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);

		// Back up the global.
		$old_members_template = $members_template;

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>

			<div class="item-options" id="members-list-options">
				<a href="<?php bp_members_directory_permalink(); ?>" id="newest-members" <?php if ( 'newest' === $settings['member_default'] ) : ?>class="selected"<?php endif; ?>><?php esc_html_e( 'Newest', 'buddypress' ); ?></a>
				<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
				<a href="<?php bp_members_directory_permalink(); ?>" id="recently-active-members" <?php if ( 'active' === $settings['member_default'] ) : ?>class="selected"<?php endif; ?>><?php esc_html_e( 'Active', 'buddypress' ); ?></a>

				<?php if ( bp_is_active( 'friends' ) ) : ?>
					<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
					<a href="<?php bp_members_directory_permalink(); ?>" id="popular-members" <?php if ( 'popular' === $settings['member_default'] ) : ?>class="selected"<?php endif; ?>><?php esc_html_e( 'Popular', 'buddypress' ); ?></a>

				<?php endif; ?>

			</div>

			<ul id="members-list" class="item-list">

				<?php while ( bp_members() ) : bp_the_member(); ?>

					<li class="vcard">
						<div class="item-avatar">
							<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name(); ?>"><?php bp_member_avatar(); ?></a>
						</div>

						<div class="item">
							<div class="item-title fn"><a href="<?php bp_member_permalink(); ?>" title="<?php bp_member_name(); ?>"><?php bp_member_name(); ?></a></div>
							<div class="item-meta">
								<span class="activity"><?php
									if ( 'newest' === $settings['member_default'] ) :
										bp_member_registered();
									elseif ( 'active' === $settings['member_default'] ) :
										bp_member_last_active();
									elseif ( 'popular' === $settings['member_default'] ) :
										bp_member_total_friend_count();
									endif; ?></span>
							</div>
						</div>
					</li>

				<?php endwhile; ?>

			</ul>

			<?php wp_nonce_field( 'bp_core_widget_members', '_wpnonce-members', false ); ?>

			<input type="hidden" name="members_widget_max" id="members_widget_max" value="<?php echo esc_attr( $settings['max_members'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php esc_html_e( 'No one has signed up yet!', 'buddypress' ); ?>
			</div>

		<?php endif; ?>

		<?php echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Members widget options.
	 *
	 * @since 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['max_members']    = strip_tags( $new_instance['max_members'] );
		$instance['member_default'] = strip_tags( $new_instance['member_default'] );
		$instance['link_title']	    = (bool) $new_instance['link_title'];

		return $instance;
	}

	/**
	 * Output the Members widget options form.
	 *
	 * @since 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings       = $this->parse_settings( $instance );
		$title          = strip_tags( $settings['title'] );
		$max_members    = strip_tags( $settings['max_members'] );
		$member_default = strip_tags( $settings['member_default'] );
		$link_title     = (bool) $settings['link_title']; ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_title' ) ?>">
				<input type="checkbox" name="<?php echo $this->get_field_name( 'link_title' ) ?>" id="<?php echo $this->get_field_id( 'link_title' ) ?>" value="1" <?php checked( $link_title ) ?> />
				<?php esc_html_e( 'Link widget title to Members directory', 'buddypress' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddypress' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'member_default' ) ?>"><?php esc_html_e( 'Default members to show:', 'buddypress' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'member_default' ) ?>" id="<?php echo $this->get_field_id( 'member_default' ) ?>">
				<option value="newest"  <?php if ( 'newest'  === $member_default ) : ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Newest',  'buddypress' ); ?></option>
				<option value="active"  <?php if ( 'active'  === $member_default ) : ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Active',  'buddypress' ); ?></option>
				<option value="popular" <?php if ( 'popular' === $member_default ) : ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Popular', 'buddypress' ); ?></option>
			</select>
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
			'title' 	     => __( 'Members', 'buddypress' ),
			'max_members' 	 => 5,
			'member_default' => 'active',
			'link_title' 	 => false
		), 'members_widget_settings' );
	}
}
