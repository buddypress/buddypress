<?php

/* Register widgets for the core component */
function bp_core_register_widgets() {
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Welcome_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Members_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Whos_Online_Widget");') );
	add_action('widgets_init', create_function('', 'return register_widget("BP_Core_Recently_Active_Widget");') );
}
add_action( 'plugins_loaded', 'bp_core_register_widgets' );

/*** WELCOME WIDGET *****************/

class BP_Core_Welcome_Widget extends WP_Widget {
	function bp_core_welcome_widget() {
		parent::WP_Widget( false, $name = 'Welcome' );
	}

	function widget($args, $instance) {
		extract( $args );
	?>
		<?php echo $before_widget; ?>
		<?php echo $before_title
			. $instance['title']
			. $after_title; ?>

		<?php if ( $instance['title'] ) : ?><h3><?php echo attribute_escape( stripslashes( $instance['title'] ) ) ?></h3><?php endif; ?>
		<?php if ( $instance['text'] ) : ?><p><?php echo apply_filters( 'bp_core_welcome_widget_text', $instance['text'] ) ?></p><?php endif; ?>

		<?php if ( !is_user_logged_in() ) { ?>
		<div class="create-account"><div class="visit generic-button"><a href="<?php bp_signup_page() ?>" title="<?php _e('Create Account', 'buddypress') ?>"><?php _e('Create Account', 'buddypress') ?></a></div></div>
		<?php } ?>
	
		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['text'] = strip_tags( wp_filter_post_kses( $new_instance['text'] ) );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags( $instance['title'] );
		$text = strip_tags( $instance['text'] );
		?>
			<p><label for="bp-widget-welcome-title"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo attribute_escape( stripslashes( $title ) ); ?>" /></label></p>
			<p>
				<label for="bp-widget-welcome-text"><?php _e( 'Welcome Text:' , 'buddypress'); ?>
					<textarea id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>" class="widefat" style="height: 100px"><?php echo attribute_escape( stripslashes( $text ) ); ?></textarea>
				</label>
			</p>
	<?php
	}
}
add_filter( 'bp_core_welcome_widget_text', 'attribute_escape' );
add_filter( 'bp_core_welcome_widget_text', 'wptexturize' );
add_filter( 'bp_core_welcome_widget_text', 'convert_smilies' );
add_filter( 'bp_core_welcome_widget_text', 'convert_chars' );
add_filter( 'bp_core_welcome_widget_text', 'stripslashes' );
add_filter( 'bp_core_welcome_widget_text', 'wpautop' );
add_filter( 'bp_core_welcome_widget_text', 'force_balance_tags' );


/*** MEMBERS WIDGET *****************/

class BP_Core_Members_Widget extends WP_Widget {
	function bp_core_members_widget() {
		parent::WP_Widget( false, $name = 'Members' );
		wp_enqueue_script( 'bp_core_widget_members-js', BP_PLUGIN_URL . '/bp-core/js/widget-members.js', array('jquery', 'jquery-livequery-pack') );		
		wp_enqueue_style( 'bp_core_widget_members-css', BP_PLUGIN_URL . '/bp-core/css/widget-members.css' );
	}

	function widget($args, $instance) {
		global $bp;
		
	    extract( $args );
		
		echo $before_widget;
		echo $before_title
		   . $widget_name 
		   . $after_title; ?>

		<?php if ( bp_has_site_members( 'type=newest&max=' . $instance['max_members'] ) ) : ?>
			<div class="item-options" id="members-list-options">
				<img id="ajax-loader-members" src="<?php echo $bp->core->image_base ?>/ajax-loader.gif" height="7" alt="<?php _e( 'Loading', 'buddypress' ) ?>" style="display: none;" /> 
				<a href="<?php echo site_url() . '/' . BP_MEMBERS_SLUG ?>" id="newest-members" class="selected"><?php _e( 'Newest', 'buddypress' ) ?></a> | 
				<a href="<?php echo site_url() . '/' . BP_MEMBERS_SLUG ?>" id="recently-active-members"><?php _e( 'Active', 'buddypress' ) ?></a> | 
				<a href="<?php echo site_url() . '/' . BP_MEMBERS_SLUG ?>" id="popular-members"><?php _e( 'Popular', 'buddypress' ) ?></a>
			</div>
			
			<ul id="members-list" class="item-list">
				<?php while ( bp_site_members() ) : bp_the_site_member(); ?>
					<li class="vcard">
						<div class="item-avatar">
							<a href="<?php bp_the_site_member_link() ?>"><?php bp_the_site_member_avatar() ?></a>
						</div>

						<div class="item">
							<div class="item-title fn"><a href="<?php bp_the_site_member_name() ?>" title="<?php bp_the_site_member_name() ?>"><?php bp_the_site_member_name() ?></a></div>
							<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( bp_get_the_site_member_registered(), __( 'registered %s ago', 'buddypress' ) ) ?></span></div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>		
			<?php wp_nonce_field( 'bp_core_widget_members', '_wpnonce-members' ); ?>
			<input type="hidden" name="members_widget_max" id="members_widget_max" value="<?php echo attribute_escape( $instance['max_members'] ); ?>" />
			
		<?php else: ?>

			<div class="widget-error">
				<?php _e('No one has signed up yet!', 'buddypress') ?>
			</div>

		<?php endif; ?>
			
		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_members' => 5 ) );
		$max_members = strip_tags( $instance['max_members'] );
		?>

		<p><label for="bp-core-widget-members-max"><?php _e('Max Members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo attribute_escape( $max_members ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

/*** WHO'S ONLINE WIDGET *****************/

class BP_Core_Whos_Online_Widget extends WP_Widget {
	function bp_core_whos_online_widget() {
		parent::WP_Widget( false, $name = 'Who\'s Online Avatars' );
	}

	function widget($args, $instance) {
		global $bp;
		
	    extract( $args );
		
		echo $before_widget;
		echo $before_title
		   . $widget_name 
		   . $after_title; ?>
		
		<?php if ( bp_has_site_members( 'type=online&per_page=' . $instance['max_members'] . '&max=' . $instance['max_members'] ) ) : ?>			
			<div class="avatar-block">
				<?php while ( bp_site_members() ) : bp_the_site_member(); ?>
					<div class="item-avatar">
						<a href="<?php bp_the_site_member_link() ?>" title="<?php bp_the_site_member_name() ?>"><?php bp_the_site_member_avatar() ?></a>
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

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_members' => 15 ) );
		$max_members = strip_tags( $instance['max_members'] );
		?>

		<p><label for="bp-core-widget-members-max"><?php _e('Max Members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo attribute_escape( $max_members ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

/*** RECENTLY ACTIVE WIDGET *****************/

class BP_Core_Recently_Active_Widget extends WP_Widget {
	function bp_core_recently_active_widget() {
		parent::WP_Widget( false, $name = 'Recently Active Member Avatars' );
	}

	function widget($args, $instance) {
		global $bp;
		
	    extract( $args );
		
		echo $before_widget;
		echo $before_title
		   . $widget_name 
		   . $after_title; ?>
			
		<?php if ( bp_has_site_members( 'type=active&per_page=' . $instance['max_members'] . '&max=' . $instance['max_members'] ) ) : ?>			
			<div class="avatar-block">
				<?php while ( bp_site_members() ) : bp_the_site_member(); ?>
					<div class="item-avatar">
						<a href="<?php bp_the_site_member_link() ?>" title="<?php bp_the_site_member_name() ?>"><?php bp_the_site_member_avatar() ?></a>
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

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_members'] = strip_tags( $new_instance['max_members'] );

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_members' => 15 ) );
		$max_members = strip_tags( $instance['max_members'] );
		?>

		<p><label for="bp-core-widget-members-max"><?php _e('Max Members to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_members' ); ?>" name="<?php echo $this->get_field_name( 'max_members' ); ?>" type="text" value="<?php echo attribute_escape( $max_members ); ?>" style="width: 30%" /></label></p>
	<?php
	}
}

?>