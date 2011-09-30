<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* Register widgets for groups component */
function groups_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Groups_Widget");') );
}
add_action( 'bp_register_widgets', 'groups_register_widgets' );

/*** GROUPS WIDGET *****************/

class BP_Groups_Widget extends WP_Widget {
	function bp_groups_widget() {
		$this->_construct();
	}

	function __construct() {
		$widget_ops = array( 'description' => __( 'A dynamic list of recently active, popular, and newest groups', 'buddypress' ) );
		parent::__construct( false, __( 'Groups', 'buddypress' ), $widget_ops );

		if ( is_active_widget( false, false, $this->id_base ) && !is_admin() && !is_network_admin() ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
				wp_enqueue_script( 'groups_widget_groups_list-js', BP_PLUGIN_URL . '/bp-groups/js/widget-groups.dev.js', array( 'jquery' ) );
			else
				wp_enqueue_script( 'groups_widget_groups_list-js', BP_PLUGIN_URL . '/bp-groups/js/widget-groups.js', array( 'jquery' ) );
		}
	}

	function widget( $args, $instance ) {
		global $bp;

		$user_id = apply_filters( 'bp_group_widget_user_id', '0' );

		extract( $args );

		if ( empty( $instance['group_default'] ) )
			$instance['group_default'] = 'popular';

		if ( empty( $instance['title'] ) )
			$instance['title'] = __( 'Groups', 'buddypress' );

		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title; ?>

		<?php if ( bp_has_groups( 'user_id=' . $user_id . '&type=' . $instance['group_default'] . '&max=' . $instance['max_groups'] ) ) : ?>
			<div class="item-options" id="groups-list-options">
				<a href="<?php echo site_url( bp_get_groups_root_slug() ); ?>" id="newest-groups"<?php if ( $instance['group_default'] == 'newest' ) : ?> class="selected"<?php endif; ?>><?php _e("Newest", 'buddypress') ?></a> |
				<a href="<?php echo site_url( bp_get_groups_root_slug() ); ?>" id="recently-active-groups"<?php if ( $instance['group_default'] == 'active' ) : ?> class="selected"<?php endif; ?>><?php _e("Active", 'buddypress') ?></a> |
				<a href="<?php echo site_url( bp_get_groups_root_slug() ); ?>" id="popular-groups" <?php if ( $instance['group_default'] == 'popular' ) : ?> class="selected"<?php endif; ?>><?php _e("Popular", 'buddypress') ?></a>
			</div>

			<ul id="groups-list" class="item-list">
				<?php while ( bp_groups() ) : bp_the_group(); ?>
					<li>
						<div class="item-avatar">
							<a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></div>
							<div class="item-meta">
								<span class="activity">
								<?php
									if ( 'newest' == $instance['group_default'] )
										printf( __( 'created %s', 'buddypress' ), bp_get_group_date_created() );
									if ( 'active' == $instance['group_default'] )
										printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() );
									else if ( 'popular' == $instance['group_default'] )
										bp_group_member_count();
								?>
								</span>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
			<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo esc_attr( $instance['max_groups'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php _e('There are no groups to display.', 'buddypress') ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['max_groups'] = strip_tags( $new_instance['max_groups'] );
		$instance['group_default'] = strip_tags( $new_instance['group_default'] );

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title'         => __( 'Groups', 'buddypress' ),
			'max_groups'    => 5,
			'group_default' => 'active'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		$max_groups = strip_tags( $instance['max_groups'] );
		$group_default = strip_tags( $instance['group_default'] );
		?>

		<p><label for="bp-groups-widget-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="bp-groups-widget-groups-max"><?php _e('Max groups to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_groups' ); ?>" name="<?php echo $this->get_field_name( 'max_groups' ); ?>" type="text" value="<?php echo esc_attr( $max_groups ); ?>" style="width: 30%" /></label></p>

		<p>
			<label for="bp-groups-widget-groups-default"><?php _e('Default groups to show:', 'buddypress'); ?>
			<select name="<?php echo $this->get_field_name( 'group_default' ); ?>">
				<option value="newest" <?php if ( $group_default == 'newest' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Newest', 'buddypress' ) ?></option>
				<option value="active" <?php if ( $group_default == 'active' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Active', 'buddypress' ) ?></option>
				<option value="popular"  <?php if ( $group_default == 'popular' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Popular', 'buddypress' ) ?></option>
			</select>
			</label>
		</p>
	<?php
	}
}

function groups_ajax_widget_groups_list() {
	global $bp;

	check_ajax_referer('groups_widget_groups_list');

	switch ( $_POST['filter'] ) {
		case 'newest-groups':
			$type = 'newest';
		break;
		case 'recently-active-groups':
			$type = 'active';
		break;
		case 'popular-groups':
			$type = 'popular';
		break;
	}

	if ( bp_has_groups( 'type=' . $type . '&per_page=' . $_POST['max_groups'] . '&max=' . $_POST['max_groups'] ) ) : ?>
		<?php echo "0[[SPLIT]]"; ?>

		<ul id="groups-list" class="item-list">
			<?php while ( bp_groups() ) : bp_the_group(); ?>
				<li>
					<div class="item-avatar">
						<a href="<?php bp_group_permalink() ?>"><?php bp_group_avatar_thumb() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></div>
						<div class="item-meta">
							<span class="activity">
								<?php
								if ( 'newest-groups' == $_POST['filter'] ) {
									printf( __( 'created %s', 'buddypress' ), bp_get_group_date_created() );
								} else if ( 'recently-active-groups' == $_POST['filter'] ) {
									printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() );
								} else if ( 'popular-groups' == $_POST['filter'] ) {
									bp_group_member_count();
								}
								?>
							</span>
						</div>
					</div>
				</li>

			<?php endwhile; ?>
		</ul>
		<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
		<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo esc_attr( $_POST['max_groups'] ); ?>" />

	<?php else: ?>

		<?php echo "-1[[SPLIT]]<li>" . __("No groups matched the current filter.", 'buddypress'); ?>

	<?php endif;

}
add_action( 'wp_ajax_widget_groups_list', 'groups_ajax_widget_groups_list' );
?>