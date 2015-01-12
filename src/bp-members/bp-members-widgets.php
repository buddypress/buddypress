<?php
/**
 * BuddyPress Members Widgets
 *
 * @package BuddyPress
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register bp-members widgets.
 *
 * Previously, these widgets were registered in bp-core.
 *
 * @since BuddyPress (2.2.0)
 */
function bp_members_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Members_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Whos_Online_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Recently_Active_Widget");') );
}
add_action( 'bp_register_widgets', 'bp_members_register_widgets' );

/**
 * Members Widget.
 *
 * @since BuddyPress (1.0.3)
 */
class BP_Core_Members_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		$widget_ops = array(
			'description' => __( 'A dynamic list of recently active, popular, and newest members', 'buddypress' ),
			'classname' => 'widget_bp_core_members_widget buddypress widget',
		);
		parent::__construct( false, $name = _x( '(BuddyPress) Members', 'widget name', 'buddypress' ), $widget_ops );

		if ( is_active_widget( false, false, $this->id_base ) && !is_admin() && !is_network_admin() ) {
			wp_enqueue_script( 'bp-widget-members' );
		}
	}

	/**
	 * Display the Members widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	function widget( $args, $instance ) {

		extract( $args );

		if ( !$instance['member_default'] )
			$instance['member_default'] = 'active';

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;

		$title = $instance['link_title'] ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) . '">' . $title . '</a>' : $title;

		echo $before_title
		   . $title
		   . $after_title;

		$members_args = array(
			'user_id'         => 0,
			'type'            => $instance['member_default'],
			'per_page'        => $instance['max_members'],
			'max'             => $instance['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>
			<div class="item-options" id="members-list-options">
				<a href="<?php bp_members_directory_permalink(); ?>" id="newest-members" <?php if ( $instance['member_default'] == 'newest' ) : ?>class="selected"<?php endif; ?>><?php _e( 'Newest', 'buddypress' ) ?></a>
				|  <a href="<?php bp_members_directory_permalink(); ?>" id="recently-active-members" <?php if ( $instance['member_default'] == 'active' ) : ?>class="selected"<?php endif; ?>><?php _e( 'Active', 'buddypress' ) ?></a>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					| <a href="<?php bp_members_directory_permalink(); ?>" id="popular-members" <?php if ( $instance['member_default'] == 'popular' ) : ?>class="selected"<?php endif; ?>><?php _e( 'Popular', 'buddypress' ) ?></a>

				<?php endif; ?>
			</div>

			<ul id="members-list" class="item-list">
				<?php while ( bp_members() ) : bp_the_member(); ?>
					<li class="vcard">
						<div class="item-avatar">
							<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_avatar() ?></a>
						</div>

						<div class="item">
							<div class="item-title fn"><a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_name() ?></a></div>
							<div class="item-meta">
								<span class="activity">
								<?php
									if ( 'newest' == $instance['member_default'] )
										bp_member_registered();
									if ( 'active' == $instance['member_default'] )
										bp_member_last_active();
									if ( 'popular' == $instance['member_default'] )
										bp_member_total_friend_count();
								?>
								</span>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php wp_nonce_field( 'bp_core_widget_members', '_wpnonce-members' ); ?>
			<input type="hidden" name="members_widget_max" id="members_widget_max" value="<?php echo esc_attr( $instance['max_members'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php _e('No one has signed up yet!', 'buddypress') ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	/**
	 * Update the Members widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] 	    = strip_tags( $new_instance['title'] );
		$instance['max_members']    = strip_tags( $new_instance['max_members'] );
		$instance['member_default'] = strip_tags( $new_instance['member_default'] );
		$instance['link_title']	    = (bool)$new_instance['link_title'];

		return $instance;
	}

	/**
	 * Output the Members widget options form.
	 *
	 * @param $instance Settings for this widget.
	 */
	function form( $instance ) {
		$defaults = array(
			'title' 	 => __( 'Members', 'buddypress' ),
			'max_members' 	 => 5,
			'member_default' => 'active',
			'link_title' 	 => false
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title 		= strip_tags( $instance['title'] );
		$max_members 	= strip_tags( $instance['max_members'] );
		$member_default = strip_tags( $instance['member_default'] );
		$link_title	= (bool)$instance['link_title'];
		?>

		<p><label for="bp-core-widget-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="<?php echo $this->get_field_name('link_title') ?>"><input type="checkbox" name="<?php echo $this->get_field_name('link_title') ?>" value="1" <?php checked( $link_title ) ?> /> <?php _e( 'Link widget title to Members directory', 'buddypress' ) ?></label></p>

		<p><label for="bp-core-widget-members-max"><?php _e('Max members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" /></label></p>

		<p>
			<label for="bp-core-widget-groups-default"><?php _e('Default members to show:', 'buddypress'); ?>
			<select name="<?php echo $this->get_field_name( 'member_default' ) ?>">
				<option value="newest" <?php if ( $member_default == 'newest' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Newest', 'buddypress' ) ?></option>
				<option value="active" <?php if ( $member_default == 'active' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Active', 'buddypress' ) ?></option>
				<option value="popular"  <?php if ( $member_default == 'popular' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Popular', 'buddypress' ) ?></option>
			</select>
			</label>
		</p>

	<?php
	}
}

/**
 * Who's Online Widget.
 *
 * @since BuddyPress (1.0.3)
 */
class BP_Core_Whos_Online_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		$widget_ops = array(
			'description' => __( 'Profile photos of online users', 'buddypress' ),
			'classname' => 'widget_bp_core_whos_online_widget buddypress widget',
		);
		parent::__construct( false, $name = _x( "(BuddyPress) Who's Online", 'widget name', 'buddypress' ), $widget_ops );
	}

	/**
	 * Display the Who's Online widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	function widget($args, $instance) {

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		echo $before_title
		   . $title
		   . $after_title;

		$members_args = array(
			'user_id'         => 0,
			'type'            => 'online',
			'per_page'        => $instance['max_members'],
			'max'             => $instance['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>
			<div class="avatar-block">
				<?php while ( bp_members() ) : bp_the_member(); ?>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_avatar() ?></a>
					</div>
				<?php endwhile; ?>
			</div>
		<?php else: ?>

			<div class="widget-error">
				<?php _e( 'There are no users currently online', 'buddypress' ) ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	/**
	 * Update the Who's Online widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Who's Online widget options form.
	 *
	 * @param $instance Settings for this widget.
	 */
	function form( $instance ) {
		$defaults = array(
			'title' => __( "Who's Online", 'buddypress' ),
			'max_members' => 15
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		$max_members = strip_tags( $instance['max_members'] );
		?>

		<p><label for="bp-core-widget-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="bp-core-widget-members-max"><?php _e('Max Members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

/**
 * Recently Active Members Widget.
 *
 * @since BuddyPress (1.0.3)
 */
class BP_Core_Recently_Active_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	function __construct() {
		$widget_ops = array(
			'description' => __( 'Profile photos of recently active members', 'buddypress' ),
			'classname' => 'widget_bp_core_recently_active_widget buddypress widget',
		);
		parent::__construct( false, $name = _x( '(BuddyPress) Recently Active Members', 'widget name', 'buddypress' ), $widget_ops );
	}

	/**
	 * Display the Recently Active widget.
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	function widget( $args, $instance ) {

		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		echo $before_title
		   . $title
		   . $after_title;

		$members_args = array(
			'user_id'         => 0,
			'type'            => 'active',
			'per_page'        => $instance['max_members'],
			'max'             => $instance['max_members'],
			'populate_extras' => true,
			'search_terms'    => false,
		);

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>
			<div class="avatar-block">
				<?php while ( bp_members() ) : bp_the_member(); ?>
					<div class="item-avatar">
						<a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_avatar() ?></a>
					</div>
				<?php endwhile; ?>
			</div>
		<?php else: ?>

			<div class="widget-error">
				<?php _e( 'There are no recently active members', 'buddypress' ) ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	/**
	 * Update the Recently Active widget options.
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	/**
	 * Output the Recently Active widget options form.
	 *
	 * @param $instance Settings for this widget.
	 */
	function form( $instance ) {
		$defaults = array(
			'title' => __( 'Recently Active Members', 'buddypress' ),
			'max_members' => 15
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		$max_members = strip_tags( $instance['max_members'] );
		?>

		<p><label for="bp-core-widget-members-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="bp-core-widget-members-max"><?php _e('Max Members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

/**
 * AJAX request handler for Members widgets.
 *
 * @since BuddyPress (1.0.0)
 */
function bp_core_ajax_widget_members() {

	check_ajax_referer( 'bp_core_widget_members' );

	switch ( $_POST['filter'] ) {
		case 'newest-members':
			$type = 'newest';
			break;

		case 'recently-active-members':
			$type = 'active';
			break;

		case 'popular-members':
			if ( bp_is_active( 'friends' ) )
				$type = 'popular';
			else
				$type = 'active';

			break;
	}

	$members_args = array(
		'user_id'         => 0,
		'type'            => $type,
		'per_page'        => $_POST['max-members'],
		'max'             => $_POST['max-members'],
		'populate_extras' => 1,
		'search_terms'    => false,
	);

	if ( bp_has_members( $members_args ) ) : ?>
		<?php echo '0[[SPLIT]]'; // return valid result. TODO: remove this. ?>
		<?php while ( bp_members() ) : bp_the_member(); ?>
			<li class="vcard">
				<div class="item-avatar">
					<a href="<?php bp_member_permalink() ?>"><?php bp_member_avatar() ?></a>
				</div>

				<div class="item">
					<div class="item-title fn"><a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_name() ?></a></div>
					<?php if ( 'active' == $type ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_last_active() ?></span></div>
					<?php elseif ( 'newest' == $type ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_registered() ?></span></div>
					<?php elseif ( bp_is_active( 'friends' ) ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_total_friend_count() ?></span></div>
					<?php endif; ?>
				</div>
			</li>
		<?php endwhile; ?>

	<?php else: ?>
		<?php echo "-1[[SPLIT]]<li>"; ?>
		<?php _e( 'There were no members found, please try another filter.', 'buddypress' ) ?>
		<?php echo "</li>"; ?>
	<?php endif;
}
add_action( 'wp_ajax_widget_members', 'bp_core_ajax_widget_members' );
add_action( 'wp_ajax_nopriv_widget_members', 'bp_core_ajax_widget_members' );
