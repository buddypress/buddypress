<?php
/**
 * Blogs Template tags
 *
 * @since 3.0.0
 * @version 6.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Template tag to wrap all Legacy actions that was used
 * before the blogs directory content
 *
 * @since 3.0.0
 */
function bp_nouveau_before_blogs_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_before_directory_blogs_page' );

	/**
	 * Fires before the display of the blogs.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_before_directory_blogs' );

	/**
	 * Fires before the display of the blogs listing content.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_before_directory_blogs_content' );

	/**
	 * Fires before the display of the blogs list tabs.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_before_directory_blogs_tabs' );
}

/**
 * Template tag to wrap all Legacy actions that was used after the blogs directory content
 *
 * @since 3.0.0
 */
function bp_nouveau_after_blogs_directory_content() {
	/**
	 * Fires inside and displays the blogs content.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_directory_blogs_content' );

	/**
	 * Fires after the display of the blogs listing content.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_after_directory_blogs_content' );

	/**
	 * Fires at the bottom of the blogs directory template file.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_after_directory_blogs' );
}

/**
 * Fire specific hooks into the blogs create template
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_blogs_create_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a create a blog hook
	$hook[] = 'create_blog';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Fire an isolated hook inside the blogs loop
 *
 * @since 3.0.0
 */
function bp_nouveau_blogs_loop_item() {
	/**
	 * Fires after the listing of a blog item in the blogs loop.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_directory_blogs_item' );
}

/**
 * Output the action buttons inside the blogs loop.
 *
 * @since 3.0.0
 *
 * @param array $args See bp_nouveau_wrapper() for the description of parameters.
 */
function bp_nouveau_blogs_loop_buttons( $args = array() ) {
	if ( empty( $GLOBALS['blogs_template'] ) ) {
		return;
	}

	$args['type'] = 'loop';

	$output = join( ' ', bp_nouveau_get_blogs_buttons( $args ) );

	ob_start();
	/**
	 * Fires inside the blogs action listing area.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_directory_blogs_actions' );
	$output .= ob_get_clean();

	if ( ! $output ) {
		return;
	}

	bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
}

	/**
	 * Get the action buttons for the current blog in the loop.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type Type of Group of buttons to get.
	 *
	 * @return array
	 */
	function bp_nouveau_get_blogs_buttons( $args ) {
		$type = ( ! empty( $args['type'] ) ) ? $args['type'] : 'loop';

		// @todo Not really sure why BP Legacy needed to do this...
		if ( 'loop' !== $type && is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return array();
		}

		$buttons = array();

		if ( isset( $GLOBALS['blogs_template']->blog ) ) {
			$blog = $GLOBALS['blogs_template']->blog;
		}

		if ( empty( $blog->blog_id ) ) {
			return $buttons;
		}

		/*
		 * If the 'container' is set to 'ul', set a var $parent_element to li,
		 * otherwise simply pass any value found in args or set var false.
		 */
		if ( ! empty( $args['container'] ) && 'ul' === $args['container'] ) {
			$parent_element = 'li';
		} elseif ( ! empty( $args['parent_element'] ) ) {
			$parent_element = $args['parent_element'];
		} else {
			$parent_element = false;
		}

		/*
		 * If we have a arg value for $button_element passed through
		 * use it to default all the $buttons['button_element'] values
		 * otherwise default to 'a' (anchor)
		 * Or override & hardcode the 'element' string on $buttons array.
		 *
		 * Icons sets a class for icon display if not using the button element
		 */
		$icons = '';
		if ( ! empty( $args['button_element'] ) ) {
			$button_element = $args['button_element'] ;
		} else {
			$button_element = 'a';
			$icons = ' icons';
		}

		/*
		 * This filter workaround is waiting for a core adaptation
		 * so that we can directly get the groups button arguments
		 * instead of the button.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/7126
		 */
		add_filter( 'bp_get_blogs_visit_blog_button', 'bp_nouveau_blogs_catch_button_args', 100, 1 );

		bp_get_blogs_visit_blog_button();

		remove_filter( 'bp_get_blogs_visit_blog_button', 'bp_nouveau_blogs_catch_button_args', 100, 1 );

		if ( isset( bp_nouveau()->blogs->button_args ) && bp_nouveau()->blogs->button_args ) {
			$button_args = bp_nouveau()->blogs->button_args ;

			// If we pass through parent classes add them to $button array
			$parent_class = '';
			if ( ! empty( $args['parent_attr']['class'] ) ) {
				$parent_class = $args['parent_attr']['class'];
			}

			// Set defaults if not set.
			$button_args = array_merge( array(
				'wrapper_id' => '',
				'link_id'    => '',
				'link_rel'   => ''
			), $button_args );

			$buttons['visit_blog'] = array(
				'id'                => 'visit_blog',
				'position'          => 5,
				'component'         => $button_args['component'],
				'must_be_logged_in' => $button_args['must_be_logged_in'],
				'block_self'        => $button_args['block_self'],
				'parent_element'    => $parent_element,
				'button_element'    => $button_element,
				'link_text'         => $button_args['link_text'],
				'parent_attr'       => array(
					'id'              => $button_args['wrapper_id'],
					'class'           => $parent_class,
				),
				'button_attr'       => array(
					'href'             => $button_args['link_href'],
					'id'               => $button_args['link_id'],
					'class'            => $button_args['link_class'] . ' button',
					'rel'              => $button_args['link_rel'],
					'title'            => '',
				),
			);

			unset( bp_nouveau()->blogs->button_args );
		}

		/**
		 * Filter to add your buttons, use the position argument to choose where to insert it.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $buttons The list of buttons.
		 * @param object $blog    The current blog object.
		 * @param string $type    Whether we're displaying a blogs loop or a the blogs single item (in the future!).
		 */
		$buttons_group = apply_filters( 'bp_nouveau_get_blogs_buttons', $buttons, $blog, $type );

		if ( ! $buttons_group ) {
			return array();
		}

		// It's the first entry of the loop, so build the Group and sort it
		if ( ! isset( bp_nouveau()->blogs->group_buttons ) || ! is_a( bp_nouveau()->blogs->group_buttons, 'BP_Buttons_Group' ) ) {
			$sort = true;
			bp_nouveau()->blogs->group_buttons = new BP_Buttons_Group( $buttons_group );

		// It's not the first entry, the order is set, we simply need to update the Buttons Group
		} else {
			$sort = false;
			bp_nouveau()->blogs->group_buttons->update( $buttons_group );
		}

		$return = bp_nouveau()->blogs->group_buttons->get( $sort );

		if ( ! $return ) {
			return array();
		}

		/**
		 * Leave a chance to adjust the $return
		 *
		 * @since 3.0.0
		 *
		 * @param array  $return  The list of buttons ordered.
		 * @param object $blog    The current blog object.
		 * @param string $type    Whether we're displaying a blogs loop or a the blogs single item (in the future!).
		 */
		do_action_ref_array( 'bp_nouveau_return_blogs_buttons', array( &$return, $blog, $type ) );

		return $return;
	}

/**
 * Check if the Sites has a latest post
 *
 * @since 3.0.0
 *
 * @return bool True if the sites has a latest post. False otherwise.
 */
function bp_nouveau_blog_has_latest_post() {
	return (bool) bp_get_blog_latest_post_title();
}
