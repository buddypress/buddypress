<?php
/**
 * BP Friends Blocks Functions.
 *
 * @package BuddyPress
 * @subpackage FriendsBlocks
 * @since 9.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds specific script data for the BP Friends blocks.
 *
 * Only used for the BP Friends block.
 *
 * @since 9.0.0
 */
function bp_friends_blocks_add_script_data() {
	$friends_blocks = array_filter( buddypress()->friends->block_globals['bp/friends']->items );

	if ( ! $friends_blocks ) {
		return;
	}

	$path = sprintf(
		'/%1$s/%2$s/%3$s',
		bp_rest_namespace(),
		bp_rest_version(),
		buddypress()->members->id
	);

	wp_localize_script(
		'bp-friends-script',
		'bpFriendsSettings',
		array(
			'path'  => ltrim( $path, '/' ),
			'root'  => esc_url_raw( get_rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);

	// Include the common JS template (Escaping is done there).
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_dynamic_template_part( 'assets/widgets/friends.php' );

	// List the block specific props.
	wp_add_inline_script(
		'bp-friends-script',
		sprintf( 'var bpFriendsBlocks = %s;', wp_json_encode( array_values( $friends_blocks ) ) ),
		'before'
	);
}

/**
 * Callback function to render the BP Friends Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_friends_render_friends_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'maxFriends'    => 5,
			'friendDefault' => 'active',
			'linkTitle'     => false,
			'postId'        => 0, // If the postId attribute is defined, post author friends are needed.
		)
	);

	$user_id = 0;
	if ( $block_args['postId'] ) {
		$user_id = (int) get_post_field( 'post_author', $block_args['postId'] );
	} else {
		$user_id = bp_displayed_user_id();

		if ( ! $user_id && isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );
			$regex        = addcslashes( sprintf( '%s/.*bp/friends', rest_get_url_prefix() ), '/' );

			if ( preg_match( "/{$regex}/", $request_path ) ) {
				$user_id = bp_loggedin_user_id();
			}
		}
	}

	if ( ! $user_id ) {
		return '';
	}

	$classnames         = 'widget_bp_core_friends_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$max_friends = (int) $block_args['maxFriends'];
	$no_friends  = __( 'Sorry, no members were found.', 'buddypress' );

	/**
	 * Filters the separator of the friends block nav links.
	 *
	 * @since 9.0.0
	 *
	 * @param string $separator Separator string. Default '|'.
	 */
	$separator = apply_filters( 'bp_friends_block_nav_links_separator', '|' );

	// Make sure the widget ID is unique.
	$widget_id = uniqid( 'friends-list-' );

	$link = bp_members_get_user_url(
		$user_id,
		bp_members_get_path_chunks( array( bp_get_friends_slug() ) )
	);

	/* translators: %s: member name */
	$title = sprintf( __( '%s\'s Friends', 'buddypress' ), bp_core_get_user_displayname( $user_id ) );

	// Set the Block's title.
	if ( true === $block_args['linkTitle'] ) {
		$widget_content = sprintf(
			'<h2 class="widget-title"><a href="%1$s">%2$s</a></h2>',
			esc_url( $link ),
			esc_html( $title )
		);
	} else {
		$widget_content = sprintf( '<h2 class="widget-title">%s</h2>', esc_html( $title ) );
	}

	$item_options = array(
		'newest'  => array(
			'class' => '',
			'label' => _x( 'Newest', 'Friends', 'buddypress' ),
		),
		'active'  => array(
			'class' => '',
			'label' => _x( 'Active', 'Friends', 'buddypress' ),
		),
		'popular' => array(
			'class' => '',
			'label' => _x( 'Popular', 'Friends', 'buddypress' ),
		),
	);

	$item_options_output = array();
	$separator_output    = sprintf( ' <span class="bp-separator" role="separator">%s</span> ', esc_html( $separator ) );

	foreach ( $item_options as $item_type => $item_attr ) {
		if ( $block_args['friendDefault'] === $item_type ) {
			$item_attr['class'] = ' class="selected"';
		}

		$item_options_output[] = sprintf(
			'<a href="%1$s" data-bp-sort="%2$s"%3$s>%4$s</a>',
			esc_url( $link ),
			esc_attr( $item_type ),
			$item_attr['class'],
			esc_html( $item_attr['label'] )
		);
	}

	$preview      = '';
	$default_args = array(
		'user_id'         => $user_id,
		'type'            => $block_args['friendDefault'],
		'per_page'        => $max_friends,
		'populate_extras' => true,
	);

	// Previewing the Block inside the editor.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$bp_query = bp_core_get_users( $default_args );
		$preview  = sprintf( '<div class="widget-error">%s</div>', $no_friends );

		if ( is_array( $bp_query['users'] ) && 0 < count( $bp_query['users'] ) ) {
			$preview = '';
			foreach ( $bp_query['users'] as $user ) {
				if ( 'newest' === $block_args['friendDefault'] ) {
					/* translators: %s is time elapsed since the registration date happened */
					$extra = sprintf( _x( 'Registered %s', 'The timestamp when the user registered', 'buddypress' ), bp_core_time_since( $user->user_registered ) );
				} elseif ( 'popular' === $block_args['friendDefault'] && isset( $item_options['popular'] ) && isset( $user->total_friend_count ) ) {
					/* translators: %s: total friend count */
					$extra = sprintf( _n( '%s friend', '%s friends', $user->total_friend_count, 'buddypress' ), number_format_i18n( $user->total_friend_count ) );
				} else {
					/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
					$extra = sprintf( __( 'Active %s', 'buddypress' ), bp_core_time_since( $user->last_activity ) );
				}

				$preview .= bp_get_dynamic_template_part(
					'assets/widgets/friends.php',
					'php',
					array(
						'data.link'              => esc_url( bp_members_get_user_url( $user->ID ) ),
						'data.name'              => esc_html( $user->display_name ),
						'data.avatar_urls.thumb' => bp_core_fetch_avatar(
							array(
								'item_id' => $user->ID,
								'html'    => false,
							)
						),
						'data.avatar_alt'        => esc_attr(
							sprintf(
								/* translators: %s: member name */
								__( 'Profile picture of %s', 'buddypress' ),
								esc_html( $user->display_name )
							)
						),
						'data.id'                => $user->ID,
						'data.extra'             => esc_html( $extra ),
					)
				);
			}
		}
	} elseif ( defined( 'WP_USE_THEMES' ) ) {
		// Get corresponding friends.
		$path = sprintf(
			'/%1$s/%2$s/%3$s',
			bp_rest_namespace(),
			bp_rest_version(),
			buddypress()->members->id
		);

		$default_path = add_query_arg(
			$default_args,
			$path
		);

		$preloaded_friends = rest_preload_api_request( '', $default_path );

		buddypress()->friends->block_globals['bp/friends']->items[ $widget_id ] = (object) array(
			'selector'   => $widget_id,
			'query_args' => $default_args,
			'preloaded'  => reset( $preloaded_friends ),
		);

		// Only enqueue common/specific scripts and data once per page load.
		if ( ! has_action( 'wp_footer', 'bp_friends_blocks_add_script_data' ) ) {
			wp_set_script_translations( 'bp-friends-script', 'buddypress' );
			wp_enqueue_script( 'bp-friends-script' );

			add_action( 'wp_footer', 'bp_friends_blocks_add_script_data', 1 );
		}
	}

	$widget_content .= sprintf(
		'<div class="item-options">
			%1$s
		</div>
		<ul id="%2$s" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
			%3$s
		</ul>',
		implode( $separator_output, $item_options_output ),
		esc_attr( $widget_id ),
		$preview
	);

	// Adds a container to make sure the block is styled even when used into the Columns parent block.
	$widget_content = sprintf( '<div class="bp-dynamic-block-container">%s</div>', "\n" . $widget_content . "\n" );

	// Only add a block wrapper if not loaded into a Widgets sidebar.
	if ( ! did_action( 'dynamic_sidebar_before' ) ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$widget_content
		);
	}

	return $widget_content;
}
