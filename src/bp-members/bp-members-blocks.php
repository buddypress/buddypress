<?php
/**
 * BP Members Blocks Functions.
 *
 * @package BuddyPress
 * @subpackage MembersBlocks
 * @since 6.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Callback function to render the BP Member Block.
 *
 * @since 6.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_members_render_member_block( $attributes = array() ) {
	$bp = buddypress();

	$block_args = bp_parse_args(
		$attributes,
		array(
			'itemID'              => 0,
			'avatarSize'          => 'full',
			'displayMentionSlug'  => true,
			'displayActionButton' => true,
			'displayCoverImage'   => true,
		)
	);

	if ( ! $block_args['itemID'] ) {
		return;
	}

	// Set the member ID and container classes.
	$member_id         = (int) $block_args['itemID'];
	$container_classes = array( 'bp-block-member' );

	// Mention variables.
	$username   = bp_members_get_user_slug( $member_id );
	$at_mention = '';

	// Avatar variables.
	$avatar           = '';
	$avatar_container = '';

	// Cover image variable.
	$cover_image     = '';
	$cover_style     = '';
	$cover_container = '';

	// Member name variables.
	$display_name = bp_core_get_user_displayname( $member_id );
	$member_link  = bp_members_get_user_url( $member_id );

	// Member action button.
	$action_button         = '';
	$display_action_button = (bool) $block_args['displayActionButton'];

	if ( $bp->avatar && $bp->avatar->show_avatars && in_array( $block_args['avatarSize'], array( 'thumb', 'full' ), true ) ) {
		$avatar = bp_core_fetch_avatar(
			array(
				'item_id' => $member_id,
				'object'  => 'user',
				'type'    => $block_args['avatarSize'],
				'html'    => false,
			)
		);

		$container_classes[] = 'avatar-' . $block_args['avatarSize'];
	} else {
		$container_classes[] = 'avatar-none';
	}

	if ( $avatar ) {
		$avatar_container = sprintf(
			'<div class="item-header-avatar">
				<a href="%1$s">
					<img loading="lazy" src="%2$s" alt="%3$s" class="avatar">
				</a>
			</div>',
			esc_url( $member_link ),
			esc_url( $avatar ),
			/* translators: %s: member name */
			sprintf( esc_html__( 'Profile photo of %s', 'buddypress' ), $display_name )
		);
	}

	$display_cover_image = (bool) $block_args['displayCoverImage'];
	if ( bp_is_active( 'members', 'cover_image' ) && $display_cover_image ) {
		$cover_image = bp_attachments_get_attachment(
			'url',
			array(
				'item_id' => $member_id,
			)
		);

		if ( $cover_image ) {
			$cover_style = sprintf(
				' style="background-image: url( %s );"',
				esc_url( $cover_image )
			);
		}

		$cover_container = sprintf(
			'<div class="bp-member-cover-image"%s></div>',
			$cover_style
		);

		$container_classes[] = 'has-cover';
	}

	$display_mention_slug = (bool) $block_args['displayMentionSlug'];
	if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && $display_mention_slug ) {
		$at_mention = sprintf(
			'<span class="user-nicename">@%s</span>',
			esc_html( $username )
		);
	}

	if ( $display_action_button ) {
		$action_button = sprintf(
			'<div class="bp-profile-button">
				<a href="%1$s" class="button large primary button-primary wp-block-button__link wp-element-button" role="button">%2$s</a>
			</div>',
			esc_url( $member_link ),
			esc_html__( 'View Profile', 'buddypress' )
		);
	}

	$output = sprintf(
		'<div class="%1$s">
			%2$s
			<div class="member-content">
				%3$s
				<div class="member-description">
					<strong><a href="%4$s">%5$s</a></strong>
					%6$s
					%7$s
				</div>
			</div>
		</div>',
		implode( ' ', array_map( 'sanitize_html_class', $container_classes ) ),
		$cover_container,
		$avatar_container,
		esc_url( $member_link ),
		esc_html( $display_name ),
		$at_mention,
		$action_button
	);

	// Compact all interesting parameters.
	$params = array_merge( $block_args, compact( 'username', 'display_name', 'member_link', 'avatar', 'cover_image' ) );

	/**
	 * Filter here to edit the output of the single member block.
	 *
	 * @since 6.0.0
	 *
	 * @param string          $output The HTML output of the block.
	 * @param array           $params The block extended parameters.
	 */
	return apply_filters( 'bp_members_render_member_block_output', $output, $params );
}

/**
 * Callback function to render the BP Members Block.
 *
 * @since 7.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_members_render_members_block( $attributes = array() ) {
	$bp = buddypress();

	$block_args = bp_parse_args(
		$attributes,
		array(
			'itemIDs'            => array(),
			'avatarSize'         => 'full',
			'displayMentionSlug' => true,
			'displayUserName'    => true,
			'extraData'          => 'none',
			'layoutPreference'   => 'list',
			'columns'            => '2',
		)
	);

	$member_ids = wp_parse_id_list( $block_args['itemIDs'] );
	if ( ! array_filter( $member_ids ) ) {
		return '';
	}

	// Make sure the avatar size exists.
	if ( ! in_array( $block_args['avatarSize'], array( 'thumb', 'full' ), true ) ) {
		$block_args['avatarSize'] = 'none';
	}

	$container_classes = sprintf( 'bp-block-members avatar-%s', $block_args['avatarSize'] );
	if ( 'grid' === $block_args['layoutPreference'] ) {
		$container_classes .= sprintf( ' is-grid columns-%d', (int) $block_args['columns'] );
	}

	$query_args = array(
		'user_ids' => $member_ids,
	);

	if ( 'none' !== $block_args['extraData'] ) {
		$query_args['populate_extras'] = true;
	}

	$query = bp_core_get_users( $query_args );

	// Initialize the output and the members.
	$output  = '';
	$members = $query['users'];

	foreach ( $members as $member ) {
		$has_activity        = false;
		$member_item_classes = 'member-content';

		if ( 'list' === $block_args['layoutPreference'] && 'latest_update' === $block_args['extraData'] && isset( $member->latest_update ) && $member->latest_update ) {
			$has_activity        = true;
			$member_item_classes = 'member-content has-activity';
		}

		$output .= sprintf( '<div class="%s">', $member_item_classes );

		// Get Member link.
		$member_link = bp_members_get_user_url( $member->ID );

		// Set the Avatar output.
		if ( $bp->avatar && $bp->avatar->show_avatars && 'none' !== $block_args['avatarSize'] ) {
			$output .= sprintf(
				'<div class="item-header-avatar">
					<a href="%1$s">
						<img loading="lazy" class="avatar" alt="%2$s" src="%3$s" />
					</a>
				</div>',
				esc_url( $member_link ),
				/* translators: %s: member name */
				esc_attr( sprintf( __( 'Profile photo of %s', 'buddypress' ), $member->display_name ) ),
				esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $member->ID,
							'object'  => 'user',
							'type'    => $block_args['avatarSize'],
							'html'    => false,
						)
					)
				)
			);
		}

		$output .= '<div class="member-description">';

		// Add the latest activity the member posted.
		if ( $has_activity ) {
			$activity_content = '';
			$activity_data    = maybe_unserialize( $member->latest_update );

			if ( isset( $activity_data['content'] ) ) {
				$activity_content = apply_filters( 'bp_get_activity_content', $activity_data['content'] );
			}

			$display_name = '';
			if ( $block_args['displayUserName'] ) {
				$display_name = $member->display_name;
			}

			$mention_name = '';
			if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && $block_args['displayMentionSlug'] ) {
				$mention_name = '(@' . $member->user_nicename . ')';
			}

			$output .= sprintf(
				'<blockquote class="wp-block-quote">
					%1$s
					<cite>
						<span>%2$s</span>
						%3$s
					</cite>
				</blockquote>',
				$activity_content,
				esc_html( $display_name ),
				esc_html( $mention_name )
			);
		} else {
			if ( $block_args['displayUserName'] ) {
				$output .= sprintf(
					'<strong><a href="%1$s">%2$s</a></strong>',
					esc_url( $member_link ),
					esc_html( $member->display_name )
				);
			}

			if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() && $block_args['displayMentionSlug'] ) {
				$output .= sprintf(
					'<span class="user-nicename">@%s</span>',
					esc_html( $member->user_nicename )
				);
			}

			if ( 'last_activity' === $block_args['extraData'] ) {
				$output .= sprintf(
					'<time datetime="%1$s">%2$s</time>',
					esc_attr( bp_core_get_iso8601_date( $member->last_activity ) ),
					/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
					sprintf( esc_html__( 'Active %s', 'buddypress' ), bp_core_time_since( $member->last_activity ) )
				);
			}
		}

		$output .= '</div></div>';
	}

	// Set the final output.
	$output = sprintf( '<div class="%1$s">%2$s</div>', $container_classes, $output );

	/**
	 * Filter here to edit the output of the members block.
	 *
	 * @since 7.0.0
	 *
	 * @param string $output     The HTML output of the block.
	 * @param array  $block_args The block arguments.
	 * @param array  $members    The list of WP_User objects.
	 */
	return apply_filters( 'bp_members_render_members_block_output', $output, $block_args, $members );
}

/**
 * Adds specific script data for the BP Members blocks.
 *
 * Only used for the BP Dynamic Members block.
 *
 * @since 9.0.0
 */
function bp_members_blocks_add_script_data() {
	$dynamic_members_blocks = array_filter( buddypress()->members->block_globals['bp/dynamic-members']->items );

	if ( ! $dynamic_members_blocks ) {
		return;
	}

	$path = sprintf(
		'/%1$s/%2$s/%3$s',
		bp_rest_namespace(),
		bp_rest_version(),
		buddypress()->members->id
	);

	wp_localize_script(
		'bp-dynamic-members-script',
		'bpDynamicMembersSettings',
		array(
			'path'  => ltrim( $path, '/' ),
			'root'  => esc_url_raw( get_rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);

	// Include the common JS template (Escaping is done there).
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_dynamic_template_part( 'assets/widgets/dynamic-members.php' );

	// List the block specific props.
	wp_add_inline_script(
		'bp-dynamic-members-script',
		sprintf( 'var bpDynamicMembersBlocks = %s;', wp_json_encode( array_values( $dynamic_members_blocks ) ) ),
		'before'
	);
}

/**
 * Callback function to render the Dynamic Members Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_members_render_dynamic_members_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title'         => '',
			'maxMembers'    => 5,
			'memberDefault' => 'active',
			'linkTitle'     => false,
		)
	);

	if ( ! $block_args['title'] ) {
		$block_args['title'] = __( 'Members', 'buddypress' );
	}

	$classnames         = 'widget_bp_core_members_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$max_members = (int) $block_args['maxMembers'];
	$no_members  = __( 'No members found.', 'buddypress' );

	/** This filter is documented in buddypress/src/bp-members/classes/class-bp-core-members-widget.php */
	$separator = apply_filters( 'bp_members_widget_separator', '|' );

	// Make sure the widget ID is unique.
	$widget_id              = uniqid( 'members-list-' );
	$members_directory_link = bp_get_members_directory_permalink();

	// Set the Block's title.
	if ( true === $block_args['linkTitle'] ) {
		$widget_content = sprintf(
			'<h2 class="widget-title"><a href="%1$s">%2$s</a></h2>',
			esc_url( $members_directory_link ),
			esc_html( $block_args['title'] )
		);
	} else {
		$widget_content = sprintf( '<h2 class="widget-title">%s</h2>', esc_html( $block_args['title'] ) );
	}

	$item_options = array(
		'newest' => array(
			'class' => '',
			'label' => _x( 'Newest', 'Members', 'buddypress' ),
		),
		'active' => array(
			'class' => '',
			'label' => _x( 'Active', 'Members', 'buddypress' ),
		),
	);

	if ( bp_is_active( 'friends' ) ) {
		$item_options['popular'] = array(
			'class' => '',
			'label' => _x( 'Popular', 'Members', 'buddypress' ),
		);
	}

	$item_options_output = array();
	$separator_output    = sprintf( ' <span class="bp-separator" role="separator">%s</span> ', esc_html( $separator ) );

	foreach ( $item_options as $item_type => $item_attr ) {
		if ( $block_args['memberDefault'] === $item_type ) {
			$item_attr['class'] = ' class="selected"';
		}

		$item_options_output[] = sprintf(
			'<a href="%1$s" data-bp-sort="%2$s"%3$s>%4$s</a>',
			esc_url( $members_directory_link ),
			esc_attr( $item_type ),
			$item_attr['class'],
			esc_html( $item_attr['label'] )
		);
	}

	$preview      = '';
	$default_args = array(
		'type'            => $block_args['memberDefault'],
		'per_page'        => $max_members,
		'populate_extras' => true,
	);

	// Previewing the Block inside the editor.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$bp_query = bp_core_get_users( $default_args );
		$preview  = sprintf( '<div class="widget-error">%s</div>', $no_members );

		if ( is_array( $bp_query['users'] ) && 0 < count( $bp_query['users'] ) ) {
			$preview = '';
			foreach ( $bp_query['users'] as $user ) {
				if ( 'newest' === $block_args['memberDefault'] ) {
					/* translators: %s is time elapsed since the registration date happened */
					$extra = sprintf( _x( 'Registered %s', 'The timestamp when the user registered', 'buddypress' ), bp_core_time_since( $user->user_registered ) );
				} elseif ( 'popular' === $block_args['memberDefault'] && isset( $item_options['popular'] ) && isset( $user->total_friend_count ) ) {
					/* translators: %s: total friend count */
					$extra = sprintf( _n( '%s friend', '%s friends', $user->total_friend_count, 'buddypress' ), number_format_i18n( $user->total_friend_count ) );
				} else {
					/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
					$extra = sprintf( __( 'Active %s', 'buddypress' ), bp_core_time_since( $user->last_activity ) );
				}

				$preview .= bp_get_dynamic_template_part(
					'assets/widgets/dynamic-members.php',
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
		// Get corresponding members.
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

		$preloaded_members = rest_preload_api_request( '', $default_path );

		buddypress()->members->block_globals['bp/dynamic-members']->items[ $widget_id ] = (object) array(
			'selector'   => $widget_id,
			'query_args' => $default_args,
			'preloaded'  => reset( $preloaded_members ),
		);

		// Only enqueue common/specific scripts and data once per page load.
		if ( ! has_action( 'wp_footer', 'bp_members_blocks_add_script_data' ) ) {
			wp_set_script_translations( 'bp-dynamic-members-script', 'buddypress' );
			wp_enqueue_script( 'bp-dynamic-members-script' );

			add_action( 'wp_footer', 'bp_members_blocks_add_script_data', 1 );
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

/**
 * Common function to render the Recently Active & Online Members Blocks.
 *
 * @since 9.0.0
 *
 * @param array $block_args {
 *    Optional. An array of Block arguments.
 *
 *    @type string $title      The title of the Block.
 *    @type int    $maxMembers The maximum number of members to show. Defaults to `0`.
 *    @type string $noMembers  The string to output when there are no members to show.
 *    @type string $classname  The name of the CSS class to use.
 *    @type string $type       The type of filter to perform. Possible values are `online`, `active`,
 *                             `newest`, `alphabetical`, `random` or `popular`.
 * }
 * @return string HTML output.
 */
function bp_members_render_members_avatars_block( $block_args = array() ) {
	$args = bp_parse_args(
		$block_args,
		array(
			'title'      => '',
			'maxMembers' => 0,
			'noMembers'  => '',
			'classname'  => '',
			'type'       => 'active',
		),
		''
	);

	$title              = $args['title'];
	$max_members        = (int) $args['maxMembers'];
	$no_members         = $args['noMembers'];
	$classname          = sanitize_key( $args['classname'] );
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => sprintf( '%s buddypress widget', $classname ),
		)
	);
	$type               = sanitize_key( $args['type'] );

	if ( $title ) {
		$widget_content = sprintf( '<h2 class="widget-title">%s</h2>', esc_html( $title ) );
	} else {
		$widget_content = '';
	}

	// Query Users.
	$query = bp_core_get_users(
		array(
			'user_id'         => 0,
			'type'            => $type,
			'per_page'        => $max_members,
			'max'             => $max_members,
			'populate_extras' => true,
			'search_terms'    => false,
		)
	);

	// Build the output for online members.
	if ( isset( $query['total'] ) && 1 <= (int) $query['total'] ) {
		$members        = $query['users'];
		$member_avatars = array();

		foreach ( $members as $member ) {
			$member_avatars[] = sprintf(
				'<div class="item-avatar">
					<a href="%1$s" class="bp-tooltip" data-bp-tooltip="%2$s">
						<img loading="lazy" src="%3$s" class="avatar user-%4$s-avatar avatar-50 photo" width="50" height="50" alt="%5$s">
					</a>
				</div>',
				esc_url( bp_members_get_user_url( $member->ID ) ),
				esc_html( $member->display_name ),
				bp_core_fetch_avatar(
					array(
						'item_id' => $member->ID,
						'html'    => false,
					)
				),
				esc_attr( $member->ID ),
				esc_html(
					sprintf(
						/* translators: %s: member name */
						__( 'Profile picture of %s', 'buddypress' ),
						$member->display_name
					)
				)
			);
		}

		$widget_content .= sprintf(
			'<div class="avatar-block">
				%s
			</div>',
			implode( "\n", $member_avatars )
		);

		// Only enqueue BP Tooltips if there is some content to style.
		wp_enqueue_style( 'bp-tooltips' );
	} else {
		$widget_content .= sprintf(
			'<div class="widget-error">
				%s
			</div>',
			esc_html( $no_members )
		);
	}

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

/**
 * Callback function to render the Online Members Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_members_render_online_members_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title'      => '',
			'maxMembers' => 15,
			'noMembers'  => __( 'There are no users currently online', 'buddypress' ),
			'classname'  => 'widget_bp_core_whos_online_widget',
		),
		'members_widget_settings'
	);

	$block_args['type'] = 'online';

	if ( ! $block_args['title'] ) {
		$block_args['title'] = __( 'Who\'s Online', 'buddypress' );
	}

	return bp_members_render_members_avatars_block( $block_args );
}

/**
 * Callback function to render the Recently Active Members Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_members_render_active_members_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title'      => '',
			'maxMembers' => 15,
			'noMembers'  => __( 'There are no recently active members', 'buddypress' ),
			'classname'  => 'widget_bp_core_recently_active_widget',
		),
		'recently_active_members_widget_settings'
	);

	$block_args['type'] = 'active';

	if ( ! $block_args['title'] ) {
		$block_args['title'] = __( 'Recently Active Members', 'buddypress' );
	}

	return bp_members_render_members_avatars_block( $block_args );
}
