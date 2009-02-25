<?php

class bbPress_Live_Widget_Forums
{
	var $options;

	function bbPress_Live_Widget_Forums()
	{
		if ( !$this->options = get_option( 'bbpress_live_widget_forums' ) ) {
			$this->options = array();
		}

		add_action( 'widgets_init', array($this, 'init') );
	}

	function init()
	{
		$widget_options = array(
			'classname' => 'bbpress_live_widget_forums',
			'description' => __( 'Forum lists from your bbPress forums', 'buddypress' )
		);

		$control_options = array(
			'height' => 350,
			'id_base' => 'bbpress_live_widget_forums'
		);

		if ( !count($this->options) ) {
			$options = array(-1 => false);
		} else {
			$options = $this->options;
		}
		foreach ( $options as $instance => $option ) {
			wp_register_sidebar_widget(
				'bbpress_live_widget_forums-' . $instance,
				__('bbPress Forum list', 'buddypress'),
				array($this, 'display'),
				$widget_options,
				array( 'number' => $instance )
			);

			wp_register_widget_control(
				'bbpress_live_widget_forums-' . $instance,
				__('bbPress Forum list', 'buddypress'),
				array($this, 'control'),
				$control_options,
				array( 'number' => $instance )
			);
		}
	}

	function display( $args, $instance = false )
	{
		if ( is_array( $instance ) ) {
			$instance = $instance['number'];
		}

		if ( !$instance || !is_numeric($instance) || 1 > $instance ) {
			return;
		}

		global $bbpress_live;

		extract($args);

		echo $before_widget;
		if ( $this->options[$instance]['title'] ) {
			echo $before_title;
			echo $this->options[$instance]['title'];
			echo $after_title;
		}

		if ( $forums = $bbpress_live->get_forums($this->options[$instance]['parent'], $this->options[$instance]['depth']) ) {
			switch ($this->options[$instance]['layout']) {
				default:
				case 'list':
					echo '<ol>';
					foreach ( $forums as $forum ) {
						echo '<li>';
						echo '<a href="' . $forum['forum_uri'] . '">' . $forum['forum_name'] . '</a> ';
						echo '</li>';
					}
					echo '</ol>';
					break;

				case 'table':
					echo '<table>';
					echo '<tr>';
					echo '<th>'. __('Forum', 'buddypress') . '</th>';
					echo '<th>'. __('Topics', 'buddypress') . '</th>';
					echo '<th>'. __('Posts', 'buddypress') . '</th>';
					echo '</tr>';
					foreach ( $forums as $forum ) {
						echo '<tr>';
						echo '<td><a href="' . $forum['forum_uri'] . '">' . $forum['forum_name'] . '</a></td>';
						echo '<td>' . $forum['topics'] . '</td>';
						echo '<td>' . $forum['posts'] . '</td>';
						echo '</tr>';
					}
					echo '</table>';
					break;
			}
		}
		echo $after_widget;
	}

	function control( $instance = false )
	{
		if ( is_array( $instance ) ) {
			$instance = $instance['number'];
		}

		if ( !$instance || !is_numeric($instance) || 1 > $instance ) {
			$instance = '%i%';
		}

		$options = $this->options;

		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( isset( $_POST['bbpress_live_widget_forums'] ) ) {
				foreach ( $_POST['bbpress_live_widget_forums'] as $_instance => $_value ) {
					if ( !$_value ) {
						continue;
					}
					$options[$_instance]['title'] = strip_tags( stripslashes( $_POST['bbpress_live_widget_forums'][$_instance]['title'] ) );
					$options[$_instance]['parent'] = strip_tags( stripslashes( $_POST['bbpress_live_widget_forums'][$_instance]['parent'] ) );
					$options[$_instance]['depth'] = (int) $_POST['bbpress_live_widget_forums'][$_instance]['depth'];
					$layout = $_POST['bbpress_live_widget_forums'][$_instance]['layout'];
					if ( in_array( $layout, array('list', 'table') ) ) {
						$options[$_instance]['layout'] = $layout;
					} else {
						$options[$_instance]['layout'] = 'list';
					}
				}
				if ( $this->options != $options ) {
					$this->options = $options;
					update_option('bbpress_live_widget_forums', $this->options);
				}
			} else {
				$this->options = array();
				delete_option('bbpress_live_widget_forums');
			}
		}

		$options['%i%']['title'] = '';
		$options['%i%']['parent'] = '';
		$options['%i%']['depth'] = '';
		$options['%i%']['layout'] = 'list';

		$title = attribute_escape( stripslashes( $options[$instance]['title'] ) );
		$parent = attribute_escape( stripslashes( $options[$instance]['parent'] ) );
		if ( !$depth = $options[$instance]['depth'] ) {
			$depth = '';
		}
		if ( !$options[$instance]['layout'] ) {
			$options[$instance]['layout'] = 'list';
		}
		$layout = array(
			'list' => '',
			'table' => ''
		);
		$layout[$options[$instance]['layout']] = ' checked="checked"';
?>
			<p>
				<label for="bbpress_live_widget_forums_title_<?php echo $instance; ?>">
					<?php _e('Title:', 'buddypress'); ?>
					<input class="widefat" id="bbpress_live_widget_forums_title_<?php echo $instance; ?>" name="bbpress_live_widget_forums[<?php echo $instance; ?>][title]" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>
			<p>
				<label for="bbpress_live_widget_forums_parent_<?php echo $instance; ?>">
					<?php _e('Parent forum id or slug (optional):', 'buddypress'); ?>
					<input class="widefat" id="bbpress_live_widget_forums_parent_<?php echo $instance; ?>" name="bbpress_live_widget_forums[<?php echo $instance; ?>][parent]" type="text" value="<?php echo $parent; ?>" />
				</label>
			</p>
			<p>
				<label for="bbpress_live_widget_forums_depth_<?php echo $instance; ?>">
					<?php _e('Hierarchy depth:', 'buddypress'); ?>
					<input style="width: 25px;" id="bbpress_live_widget_forums_depth_<?php echo $instance; ?>" name="bbpress_live_widget_forums[<?php echo $instance; ?>][depth]" type="text" value="<?php echo $depth; ?>" />
				</label>
			</p>
			<div>
				<p style="margin-bottom: 0;">
					<?php _e('Layout style:', 'buddypress'); ?>
				</p>
				<div>
					<label for="bbpress_live_widget_forums_list_<?php echo $instance; ?>">
						<input id="bbpress_live_widget_forums_list_<?php echo $instance; ?>" name="bbpress_live_widget_forums[<?php echo $instance; ?>][layout]" type="radio" value="list"<?php echo $layout['list']; ?> /> <?php _e('ordered list', 'buddypress'); ?>
					</label>
				</div>
				<div>
					<label for="bbpress_live_widget_forums_table_<?php echo $instance; ?>">
						<input id="bbpress_live_widget_forums_table_<?php echo $instance; ?>" name="bbpress_live_widget_forums[<?php echo $instance; ?>][layout]" type="radio" value="table"<?php echo $layout['table']; ?> /> <?php _e('table', 'buddypress'); ?>
					</label>
				</div>
			</div>
			<input type="hidden" id="bbpress_live_widget_forums_submit" name="bbpress_live_widget_forums[<?php echo $instance; ?>][submit]" value="1" />
<?php
	}
}



class bbPress_Live_Widget_Topics
{
	var $options;

	function bbPress_Live_Widget_Topics()
	{
		if ( !$this->options = get_option( 'bbpress_live_widget_topics' ) ) {
			$this->options = array();
		}

		add_action( 'widgets_init', array($this, 'init') );
	}

	function init()
	{
		$widget_options = array(
			'classname' => 'bbpress_live_widget_topics',
			'description' => __( 'The latest topics from your bbPress forums', 'buddypress' )
		);

		$control_options = array(
			'height' => 350,
			'id_base' => 'bbpress_live_widget_topics'
		);

		if ( !count($this->options) ) {
			$options = array(-1 => false);
		} else {
			$options = $this->options;
		}
		foreach ( $options as $instance => $option ) {
			wp_register_sidebar_widget(
				'bbpress_live_widget_topics-' . $instance,
				__('bbPress latest topics', 'buddypress'),
				array($this, 'display'),
				$widget_options,
				array( 'number' => $instance )
			);

			wp_register_widget_control(
				'bbpress_live_widget_topics-' . $instance,
				__('bbPress latest topics', 'buddypress'),
				array($this, 'control'),
				$control_options,
				array( 'number' => $instance )
			);
		}
	}

	function display( $args, $instance = false )
	{
		if ( is_array( $instance ) ) {
			$instance = $instance['number'];
		}

		if ( !$instance || !is_numeric($instance) || 1 > $instance ) {
			return;
		}

		global $bbpress_live;

		extract($args);

		echo $before_widget;
		if ( $this->options[$instance]['title'] ) {
			echo $before_title;
			echo $this->options[$instance]['title'];
			echo $after_title;
		}

		if ( $topics = $bbpress_live->get_topics($this->options[$instance]['forum'], $this->options[$instance]['number']) ) {
			switch ($this->options[$instance]['layout']) {
				default:
				case 'list':
					echo '<ol>';
					foreach ( $topics as $topic ) {
						echo '<li>';
						echo '<a href="' . $topic['topic_uri'] . '">' . $topic['topic_title'] . '</a> ';
						printf( __( '%1$s posted %2$s ago', 'buddypress' ), $topic['topic_last_poster_display_name'], $topic['topic_time_since'] );
						echo '</li>';
					}
					echo '</ol>';
					break;

				case 'table':
					echo '<table>';
					echo '<tr>';
					echo '<th>'. __('Topic', 'buddypress') . '</th>';
					echo '<th>'. __('Posts', 'buddypress') . '</th>';
					echo '<th>'. __('Last Poster', 'buddypress') . '</th>';
					echo '<th>'. __('Freshness', 'buddypress') . '</th>';
					echo '</tr>';
					foreach ( $topics as $topic ) {
						echo '<tr>';
						echo '<td><a href="' . $topic['topic_uri'] . '">' . $topic['topic_title'] . '</a></td>';
						echo '<td>' . $topic['topic_posts'] . '</td>';
						echo '<td>' . $topic['topic_last_poster_display_name'] . '</td>';
						echo '<td>' . $topic['topic_time_since'] . '</td>';
						echo '</tr>';
					}
					echo '</table>';
					break;
			}
		}
		echo $after_widget;
	}

	function control( $instance = false )
	{
		if ( is_array( $instance ) ) {
			$instance = $instance['number'];
		}

		if ( !$instance || !is_numeric($instance) || 1 > $instance ) {
			$instance = '%i%';
		}

		$options = $this->options;

		if ( 'POST' == strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( isset( $_POST['bbpress_live_widget_topics'] ) ) {
				foreach ( $_POST['bbpress_live_widget_topics'] as $_instance => $_value ) {
					if ( !$_value ) {
						continue;
					}
					$options[$_instance]['title'] = strip_tags( stripslashes( $_POST['bbpress_live_widget_topics'][$_instance]['title'] ) );
					$options[$_instance]['forum'] = strip_tags( stripslashes( $_POST['bbpress_live_widget_topics'][$_instance]['forum'] ) );
					$options[$_instance]['number'] = (int) $_POST['bbpress_live_widget_topics'][$_instance]['number'];
					$layout = $_POST['bbpress_live_widget_topics'][$_instance]['layout'];
					if ( in_array( $layout, array('list', 'table') ) ) {
						$options[$_instance]['layout'] = $layout;
					} else {
						$options[$_instance]['layout'] = 'list';
					}
				}
				if ( $this->options != $options ) {
					$this->options = $options;
					update_option('bbpress_live_widget_topics', $this->options);
				}
			} else {
				$this->options = array();
				delete_option('bbpress_live_widget_topics');
			}
		}

		$options['%i%']['title'] = '';
		$options['%i%']['forum'] = '';
		$options['%i%']['number'] = 5;
		$options['%i%']['layout'] = 'list';

		$title = attribute_escape( stripslashes( $options[$instance]['title'] ) );
		$forum = attribute_escape( stripslashes( $options[$instance]['forum'] ) );
		if ( !$number = (int) $options[$instance]['number'] ) {
			$number = 5;
		}
		if ( !$options[$instance]['layout'] ) {
			$options[$instance]['layout'] = 'list';
		}
		$layout = array(
			'list' => '',
			'table' => ''
		);
		$layout[$options[$instance]['layout']] = ' checked="checked"';
?>
			<p>
				<label for="bbpress_live_widget_topics_title_<?php echo $instance; ?>">
					<?php _e('Title:', 'buddypress'); ?>
					<input class="widefat" id="bbpress_live_widget_topics_title_<?php echo $instance; ?>" name="bbpress_live_widget_topics[<?php echo $instance; ?>][title]" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>
			<p>
				<label for="bbpress_live_widget_topics_forum_<?php echo $instance; ?>">
					<?php _e('Forum id or slug (optional):', 'buddypress'); ?>
					<input class="widefat" id="bbpress_live_widget_topics_forum_<?php echo $instance; ?>" name="bbpress_live_widget_topics[<?php echo $instance; ?>][forum]" type="text" value="<?php echo $forum; ?>" />
				</label>
			</p>
			<p>
				<label for="bbpress_live_widget_topics_number_<?php echo $instance; ?>">
					<?php _e('Number of topics to show:', 'buddypress'); ?>
					<input style="width: 25px;" id="bbpress_live_widget_topics_number_<?php echo $instance; ?>" name="bbpress_live_widget_topics[<?php echo $instance; ?>][number]" type="text" value="<?php echo $number; ?>" />
				</label>
			</p>
			<div>
				<p style="margin-bottom: 0;">
					<?php _e('Layout style:', 'buddypress'); ?>
				</p>
				<div>
					<label for="bbpress_live_widget_topics_list_<?php echo $instance; ?>">
						<input id="bbpress_live_widget_topics_list_<?php echo $instance; ?>" name="bbpress_live_widget_topics[<?php echo $instance; ?>][layout]" type="radio" value="list"<?php echo $layout['list']; ?> /> <?php _e('ordered list', 'buddypress'); ?>
					</label>
				</div>
				<div>
					<label for="bbpress_live_widget_topics_table_<?php echo $instance; ?>">
						<input id="bbpress_live_widget_topics_table_<?php echo $instance; ?>" name="bbpress_live_widget_topics[<?php echo $instance; ?>][layout]" type="radio" value="table"<?php echo $layout['table']; ?> /> <?php _e('table', 'buddypress'); ?>
					</label>
				</div>
			</div>
			<input type="hidden" id="bbpress_live_widget_topics_submit" name="bbpress_live_widget_topics[<?php echo $instance; ?>][submit]" value="1" />
<?php
	}
}

class bbPress_Live_Fetch
{
	var $endpoint = false;
	var $result;
	var $readonly_methods = array(
		'bb.getForums',
		'bb.getTopics'
	);

	function bbPress_Live_Fetch()
	{
		$this->options = array(
			'target_uri'    => '',
			'username' => '',
			'password' => '',
			'always_use_auth' => false
		);

		if ( $options = get_option( 'bbpress_live_fetch' ) ) {
			$this->options = array_merge( $this->options, $options );
		}
		
		$this->set_endpoint( $this->options['target_uri'] );
	}

	function set_endpoint( $uri = false )
	{
		$old_endpoint = $this->endpoint;
		if ( $new_endpoint = discover_pingback_server_uri( $uri ) ) {
			$this->endpoint = $new_endpoint;
		}
		return $old_endpoint;
	}

	function query( $method, $args = false, $username = false )
	{
		if (!$method) {
			return false;
		}
		
		$client = new IXR_Client( $this->endpoint );
		$client->debug = false;
		$client->timeout = 3;
		$client->useragent .= ' -- bbPress Live Data/0.1.2';

		if ( !$username ) {
			$username = $this->options['username'];
		} else {
			$username = array( $this->options['username'], $username );
		}
		
		$password = $this->options['password'];
		
		array_unshift( $args, $username, $password );

		if ( !$client->query( $method, $args ) ) {
			//var_dump( $client->message, $client->error ); die;
			return false;
		}
		
		return $client->getResponse();
	}
}


class bbPress_Live
{
	var $options;
	var $fetch;

	function bbPress_Live()
	{
		$this->options = array(
			'cache_enabled' => false,
			'cache_timeout' => 3600,
			'widget_forums' => true,
			'widget_topics' => true,
			'post_to_topic' => false,
			'post_to_topic_forum' => false,
			'post_to_topic_delay' => 60,
			'host_all_comments' => false
		);

		if ( $options = get_option('buddypress') ) {
			$this->options = array_merge( $this->options, $options );
		}

		$this->fetch = new bbPress_Live_Fetch();

		if ( $this->options['widget_forums'] ) {
			new bbPress_Live_Widget_Forums();
		}

		if ( $this->options['widget_topics'] ) {
			new bbPress_Live_Widget_Topics();
		}
	}

	function cache_update( $key, $value )
	{
		if ( !$key ) {
			return false;
		}

		if ( !$value ) {
			return $this->cache_delete( $key );
		}

		$cache = array(
			'time' => time(),
			'content' => $value
		);

		if ( !update_option( 'bbpress_live_cache_' . $key, $cache ) ) {
			return false;
		}

		return $cache['time'];
	}

	function cache_delete( $key )
	{
		if ( !$key ) {
			return false;
		}

		if ( !delete_option( 'bbpress_live_cache_' . $key ) ) {
			return false;
		}

		return true;
	}

	function cache_get( $key )
	{
		if ( !$key ) {
			return false;
		}

		if ( !$this->options['cache_enabled'] ) {
			return false;
		}

		$cache = get_option( 'bbpress_live_cache_' . $key );

		if ( ( (int) $cache['time'] + (int) $this->options['cache_timeout'] ) < time() ) {
			return false;
		}

		return $cache['content'];
	}

	function get_forums( $parent = 0, $depth = 0 )
	{
		$key = md5('forums_' . $parent . '_' . $depth);

		if ( $forums = $this->cache_get( $key ) ) {
			return $forums;
		}

		if ( !$forums = $this->fetch->query( 'bb.getForums', array($parent, $depth) ) ) {
			return false;
		}

		$this->cache_update( $key, $forums );

		return $forums;
	}

	function get_topics( $forum = 0, $number = 0, $page = 1 )
	{
		$key = md5('topics_' . $forum . '_' . $number . '_' . $page);

		if ( $topics = $this->cache_get( $key ) ) {
			return $topics;
		}

		if ( !$topics = $this->fetch->query( 'bb.getTopics', array($forum, $number, $page) ) ) {
			return false;
		}

		$this->cache_update( $key, $topics );

		return $topics;
	}
	
	function get_topic_details( $topic_id ) 
	{
		$key = md5( 'topic_' . $topic_id );
		
		if ( $topic = $this->cache_get( $key ) ) {
			return $topic;
		}
				
		if ( !$topic = $this->fetch->query( 'bb.getTopic', array( $topic_id ) ) ) {
			return false;
		}
		
		$this->cache_update( $key, $topic );
		
		return $topic;
	}

	function new_forum( $name = '', $desc = '', $parent = 0, $order = 0, $is_category = false )
	{				
		if ( !$forum = $this->fetch->query( 'bb.newForum', array( array( 'name' => $name, 'description' => $desc, 'parent' => $parent, 'order' => $order, 'is_category' => $is_category ) ) ) ) {
			return false;
		}
		
		return $forum;
	}
	
	function get_posts( $topic = 0, $number = 0, $page = 1 )
	{		
		$key = md5('posts_' . $topic . '_' . $number . '_' . $page);

		if ( $posts = $this->cache_get( $key ) ) {
			return $posts;
		}

		if ( !$posts = $this->fetch->query( 'bb.getPosts', array( $topic, $number, $page ) ) ) {
			return false;
		}

		$this->cache_update( $key, $posts );

		return $posts;		
	}
	
	function get_post( $post_id = 0 )
	{	
		$key = md5( 'post_' . $post_id );
		
		if ( $post = $this->cache_get( $key ) ) {
			return $post;
		}
				
		if ( !$post = $this->fetch->query( 'bb.getPost', array( $post_id ) ) ) {
			return false;
		}

		$this->cache_update( $key, $post );
		
		return $post;		
	}
	
	function new_post( $post_text = '', $topic = 0 )
	{
		global $current_user;
		
		if ( !$post = $this->fetch->query( 'bb.newPost', array( array( 'text' => $post_text, 'topic_id' => $topic ) ), $current_user->user_login ) ) {
			return false;
		}
		
		$key = md5( 'post_' . $post->post_id );
		$this->cache_update( $key, $post );
		
		return $post;
	}
	
	function new_topic( $title = '', $topic_text = '', $tags = '', $forum = 0 )
	{
		global $current_user;
		
		if ( !$topic = $this->fetch->query( 'bb.newTopic', array( array( 'title' => $title, 'text' => $topic_text, 'tags' => $tags, 'forum_id' => (int)$forum ) ), $current_user->user_login ) ) {
			return false;
		}
		
		$key = md5( 'topic_' . $topic->topic_id );
		$this->cache_update( $key, $post );
		
		return $topic;
	}

}


?>