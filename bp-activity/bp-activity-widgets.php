<?php

class BP_Activity_Widget extends WP_Widget {
	function bp_activity_widget() {
		parent::WP_Widget( false, $name = __( 'Site Wide Activity', 'buddypress' ) );

		if ( is_active_widget( false, false, $this->id_base ) )
			wp_enqueue_script( 'activity_widget_js', BP_PLUGIN_URL . '/bp-activity/js/widget-activity.js' );
	}

	function widget($args, $instance) {
		global $bp;

		extract( $args );

		echo $before_widget;
		echo $before_title
		   . $widget_name .
			 ' &nbsp;<span class="ajax-loader"></span>
			  <a class="rss-image" href="' . bp_get_sitewide_activity_feed_link() . '" title="' . __( 'Site Wide Activity RSS Feed', 'buddypress' ) . '">' . __( '[RSS]', 'buddypress' ) . '</a>'
		   . $after_title; ?>

		<?php if ( is_user_logged_in() ) : ?>
		<form action="" method="post" id="whats-new-form" name="whats-new-form">
			<div id="whats-new-avatar">
				<?php bp_loggedin_user_avatar('width=40&height=40') ?>
				<span class="loading"></span>
			</div>

			<h5>
				<?php
					$fullname = (array)explode( ' ', $bp->loggedin_user->fullname );
					printf( __( "What's new %s?", 'buddypress' ), $fullname[0] )
				?>
			</h5>

			<div id="whats-new-content">
				<div id="whats-new-textarea">
					<textarea name="whats-new" id="whats-new" value="" /></textarea>
				</div>

				<div id="whats-new-options">
					<div id="whats-new-submit">
						<span class="ajax-loader"></span> &nbsp;
						<input type="submit" name="aw-whats-new-submit" id="aw-whats-new-submit" value="<?php _e( 'Post Update', 'callisto' ) ?>" />
					</div>

					<div id="whats-new-post-in-box">
						<?php _e( 'Post in', 'callisto' ) ?>:

						<select id="whats-new-post-in" name="whats-new-post-in">
							<option selected="selected" value="0"><?php _e( 'My Profile', 'buddypress' ) ?></option>
							<?php if ( bp_has_groups( 'user_id=' . bp_loggedin_user_id() . '&type=alphabetical&max=100&per_page=100' ) ) : while ( bp_groups() ) : bp_the_group(); ?>
								<option value="<?php bp_group_id() ?>"><?php bp_group_name() ?></option>
							<?php endwhile; endif; ?>
						</select>
					</div>
				</div>

				<div class="clear"></div>

			</div>

			<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>
		</form>
		<?php endif; ?>

		<div class="item-list-tabs">
			<ul>
				<li class="selected" id="activity-all"><a href="<?php bp_root_domain() ?>"><?php _e( 'All Members', 'buddypress' ) ?></a></li>

				<?php if ( is_user_logged_in() ) : ?>
					<li id="activity-friends"><a href="<?php echo bp_loggedin_user_domain() . BP_ACTIVITY_SLUG . '/my-friends/' ?>"><?php _e( 'My Friends', 'buddypress') ?></a></li>
					<li id="activity-groups"><a href="<?php echo bp_loggedin_user_domain() . BP_ACTIVITY_SLUG . '/my-groups/' ?>"><?php _e( 'My Groups', 'buddypress') ?></a></li>
				<?php endif; ?>

				<?php do_action( 'bp_activity_types' ) ?>

				<li id="activity-filter-select">
					<select>
						<option value="-1"><?php _e( 'No Filter', 'buddypress' ) ?></option>
						<option value="new_wire_post"><?php _e( 'Updates Only', 'buddypress' ) ?></option>
						<option value="new_forum_post,new_forum_topic"><?php _e( 'Group Forum Activity Only', 'buddypress' ) ?></option>
						<option value="new_blog_post,new_blog_comment"><?php _e( 'Blog Activity Only', 'buddypress' ) ?></option>

						<?php do_action( 'bp_activity_filter_options' ) ?>
					</select>
				</li>
			</ul>
		</div>

		<div class="activity">
			<?php // The loop will be loaded here via AJAX on page load to retain settings. ?>
		</div>

		<form action="" name="activity-widget-form" id="activity-widget-form" method="post">
			<?php wp_nonce_field( 'activity_filter', '_wpnonce_activity_filter' ) ?>
			<input type="hidden" id="aw-querystring" name="aw-querystring" value="" />
			<input type="hidden" id="aw-oldestpage" name="aw-oldestpage" value="1" />
		</div>

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
register_widget( "BP_Activity_Widget" );

function bp_activity_widget_loop( $type = 'all', $filter = false, $query_string = false, $per_page = 20 ) {
	global $bp;

	if ( !$query_string ) {
		/* Set a valid type */
		if ( !$type || ( 'all' != $type && 'friends' != $type && 'groups' != $type ) )
			$type = 'all';

		if ( ( 'friends' == $type || 'groups' == $type ) && !is_user_logged_in() )
			$type = 'all';

		switch( $type ) {
			case 'friends':
				$friend_ids = implode( ',', friends_get_friend_user_ids( $bp->loggedin_user->id ) );
				$query_string = 'user_id=' . $friend_ids;
				break;
			case 'groups':
				$groups = groups_get_user_groups( $bp->loggedin_user->id );
				$group_ids = implode( ',', $groups['groups'] );
				$query_string = 'object=groups&primary_id=' . $group_ids;
				break;
		}

		/* Build the filter */
		if ( $filter && $filter != '-1' )
			$query_string .= '&action=' . $filter;

		/* Add the per_page param */
		$query_string .= '&per_page=' . $per_page;
	}

	if ( bp_has_activities( $query_string . '&display_comments=threaded' ) ) : ?>
		<?php echo $query_string . '&display_comments=threaded||'; // Pass the qs back to the JS. ?>

		<?php if ( !$_POST['acpage'] || 1 == $_POST['acpage'] ) : ?>
			<ul id="site-wide-stream" class="activity-list item-list">
		<?php endif; ?>

		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li class="<?php bp_activity_css_class() ?>" id="activity-<?php bp_activity_id() ?>">
				<div class="activity-avatar">
					<?php bp_activity_avatar('type=full&width=40&height=40') ?>
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
							<div class="ac-textarea">
								<textarea id="ac-input-<?php bp_activity_id() ?>" class="ac-input" name="ac-input-<?php bp_activity_id() ?>"></textarea>
							</div>
							<input type="submit" name="ac-form-submit" value="<?php _e( 'Post', 'buddypress' ) ?> &rarr;" />
						</div>
						<?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ) ?>
					</form>
					<?php endif; ?>
				</div>

			</li>
		<?php endwhile; ?>
			<li class="load-more">
				<a href="#more"><?php _e( 'Load More', 'buddypress' ) ?></a> &nbsp; <span class="ajax-loader"></span>
			</li>

		<?php if ( !$_POST['acpage'] || 1 == $_POST['acpage']  ) : ?>
			</ul>
		<?php endif; ?>

	<?php else: ?>
<?php echo "-1<div id='message' class='info'><p>" . __( 'No activity found', 'buddypress' ) . '</p></div>'; ?>
	<?php endif;
}

/* The ajax function to reload the activity widget. In here because this is a self contained widget. */
function bp_activity_ajax_widget_filter() {
	bp_activity_widget_loop( $_POST['type'], $_POST['filter'] );
}
add_action( 'wp_ajax_activity_widget_filter', 'bp_activity_ajax_widget_filter' );

/* The ajax function to load older updates at the end of the list */
function bp_activity_ajax_load_older_updates() {
	bp_activity_widget_loop( false, false, $_POST['query_string'] );
}
add_action( 'wp_ajax_aw_get_older_updates', 'bp_activity_ajax_load_older_updates' );



?>
