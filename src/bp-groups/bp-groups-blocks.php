<?php
/**
 * BP Groups Blocks Functions.
 *
 * @package BuddyPress
 * @subpackage GroupsBlocks
 * @since 6.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Callback function to render the BP Group Block.
 *
 * @since 6.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_groups_render_group_block( $attributes = array() ) {
	$bp = buddypress();

	$block_args = bp_parse_args(
		$attributes,
		array(
			'itemID'              => 0,
			'avatarSize'          => 'full',
			'displayDescription'  => true,
			'displayActionButton' => true,
			'displayCoverImage'   => true,
		)
	);

	if ( ! $block_args['itemID'] ) {
		return;
	}

	// Set the group ID and container classes.
	$group_id          = (int) $block_args['itemID'];
	$container_classes = array( 'bp-block-group' );

	// Group object.
	$group = groups_get_group( $group_id );

	if ( ! $group->id ) {
		return;
	}

	// Avatar variables.
	$avatar           = '';
	$avatar_container = '';

	// Cover image variable.
	$cover_image     = '';
	$cover_style     = '';
	$cover_container = '';

	// Group name/link/description variables.
	$group_name        = bp_get_group_name( $group );
	$group_link        = bp_get_group_url( $group );
	$group_description = '';
	$group_content     = '';

	// Group action button.
	$action_button         = '';
	$display_action_button = (bool) $block_args['displayActionButton'];

	if ( $bp->avatar && $bp->avatar->show_avatars && ! bp_disable_group_avatar_uploads() && in_array( $block_args['avatarSize'], array( 'thumb', 'full' ), true ) ) {
		$avatar = bp_core_fetch_avatar(
			array(
				'item_id' => $group->id,
				'object'  => 'group',
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
			esc_url( $group_link ),
			esc_url( $avatar ),
			/* Translators: %s is the group's name. */
			sprintf( esc_attr__( 'Group Profile photo of %s', 'buddypress' ), esc_html( $group_name ) )
		);
	}

	$display_cover_image = (bool) $block_args['displayCoverImage'];
	if ( bp_is_active( 'groups', 'cover_image' ) && $display_cover_image ) {
		$cover_image = bp_attachments_get_attachment(
			'url',
			array(
				'item_id'    => $group->id,
				'object_dir' => 'groups',
			)
		);

		if ( $cover_image ) {
			$cover_style = sprintf(
				' style="background-image: url( %s );"',
				esc_url( $cover_image )
			);
		}

		$cover_container = sprintf(
			'<div class="bp-group-cover-image"%s></div>',
			$cover_style
		);

		$container_classes[] = 'has-cover';
	}

	$display_description = (bool) $block_args['displayDescription'];
	if ( $display_description ) {
		$group_description = bp_get_group_description( $group );
		$group_content     = sprintf(
			'<div class="group-description-content">%s</div>',
			$group_description
		);

		$container_classes[] = 'has-description';
	}

	if ( $display_action_button ) {
		$action_button = sprintf(
			'<div class="bp-profile-button">
				<a href="%1$s" class="button large primary button-primary wp-block-button__link wp-element-button" role="button">%2$s</a>
			</div>',
			esc_url( $group_link ),
			esc_html__( 'View Group', 'buddypress' )
		);
	}

	$output = sprintf(
		'<div class="%1$s">
			%2$s
			<div class="group-content">
				%3$s
				<div class="group-description">
					<strong><a href="%4$s">%5$s</a></strong>
					%6$s
					%7$s
				</div>
			</div>
		</div>',
		implode( ' ', array_map( 'sanitize_html_class', $container_classes ) ),
		$cover_container,
		$avatar_container,
		esc_url( $group_link ),
		esc_html( $group_name ),
		$group_content,
		$action_button
	);

	// Compact all interesting parameters.
	$params = array_merge( $block_args, compact( 'group_name', 'group_link', 'group_description', 'avatar', 'cover_image' ) );

	/**
	 * Filter here to edit the output of the single group block.
	 *
	 * @since 6.0.0
	 *
	 * @param string          $output The HTML output of the block.
	 * @param BP_Groups_Group $group  The group object.
	 * @param array           $params The block extended parameters.
	 */
	return apply_filters( 'bp_groups_render_group_block_output', $output, $group, $params );
}

/**
 * Callback function to render the BP Groups Block.
 *
 * @since 7.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_groups_render_groups_block( $attributes = array() ) {
	$bp = buddypress();

	$block_args = bp_parse_args(
		$attributes,
		array(
			'itemIDs'          => array(),
			'avatarSize'       => 'full',
			'displayGroupName' => true,
			'extraInfo'        => 'none',
			'layoutPreference' => 'list',
			'columns'          => '2',
		)
	);

	$group_ids = wp_parse_id_list( $block_args['itemIDs'] );
	if ( ! array_filter( $group_ids ) ) {
		return '';
	}

	// Make sure the avatar size exists.
	if ( ! in_array( $block_args['avatarSize'], array( 'thumb', 'full' ), true ) ) {
		$block_args['avatarSize'] = 'none';
	}

	$container_classes = sprintf( 'bp-block-groups avatar-%s', $block_args['avatarSize'] );
	if ( 'grid' === $block_args['layoutPreference'] ) {
		$container_classes .= sprintf( ' is-grid columns-%d', (int) $block_args['columns'] );
	}

	$query = groups_get_groups(
		array(
			'include' => $group_ids,
		)
	);

	// Initialize the output and the groups.
	$output = '';
	$groups = $query['groups'];

	foreach ( $groups as $group ) {
		$has_description    = false;
		$group_item_classes = 'group-content';

		if ( 'list' === $block_args['layoutPreference'] && 'description' === $block_args['extraInfo'] && isset( $group->description ) && $group->description ) {
			$has_description    = true;
			$group_item_classes = 'group-content has-description';
		}

		$output .= sprintf( '<div class="%s">', $group_item_classes );

		// Get Member link.
		$group_link = bp_get_group_url( $group );

		// Set the Avatar output.
		if ( $bp->avatar && $bp->avatar->show_avatars && ! bp_disable_group_avatar_uploads() && 'none' !== $block_args['avatarSize'] ) {
			$output .= sprintf(
				'<div class="item-header-avatar">
					<a href="%1$s">
						<img loading="lazy" src="%2$s" alt="%3$s" class="avatar">
					</a>
				</div>',
				esc_url( $group_link ),
				esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $group->id,
							'object'  => 'group',
							'type'    => $block_args['avatarSize'],
							'html'    => false,
						)
					)
				),
				/* Translators: %s is the group's name. */
				sprintf( esc_attr__( 'Group Profile photo of %s', 'buddypress' ), esc_html( $group->name ) )
			);
		}

		$output .= '<div class="group-description">';

		if ( $block_args['displayGroupName'] ) {
			$output .= sprintf(
				'<strong><a href="%1$s">%2$s</a></strong>',
				esc_url( $group_link ),
				esc_html( $group->name )
			);
		}

		// Add the latest activity the group posted.
		if ( $has_description && $group->description ) {
			$output .= sprintf(
				'<div class="group-description-content">%s</div>',
				bp_get_group_description( $group )
			);
		} elseif ( 'active' === $block_args['extraInfo'] ) {
			$output .= sprintf(
				'<time datetime="%1$s">%2$s</time>',
				esc_attr( bp_core_get_iso8601_date( $group->last_activity ) ),
				/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
				sprintf( esc_html__( 'Active %s', 'buddypress' ), bp_get_group_last_active( $group ) )
			);
		} elseif ( 'popular' === $block_args['extraInfo'] ) {
			$total_member_count = $group->total_member_count;

			$output .= sprintf(
				'<div class="group-meta">%s</div>',
				/* translators: %d: the number of group members. */
				esc_html( sprintf( _n( '%d member', '%d members', $total_member_count, 'buddypress' ), $total_member_count ) )
			);
		}

		$output .= '</div></div>';
	}

	// Set the final output.
	$output = sprintf( '<div class="%1$s">%2$s</div>', $container_classes, $output );

	/**
	 * Filter here to edit the output of the groups block.
	 *
	 * @since 7.0.0
	 *
	 * @param string $output     The HTML output of the block.
	 * @param array  $block_args The block arguments.
	 * @param array  $groups     The list of BP_Groups_Group objects.
	 */
	return apply_filters( 'bp_groups_render_groups_block_output', $output, $block_args, $groups );
}

/**
 * Adds specific script data for the BP Groups blocks.
 *
 * Only used for the BP Dynamic Groups block.
 *
 * @since 9.0.0
 */
function bp_groups_blocks_add_script_data() {
	$dynamic_groups_blocks = array_filter( buddypress()->groups->block_globals['bp/dynamic-groups']->items );

	if ( ! $dynamic_groups_blocks ) {
		return;
	}

	$path = sprintf(
		'/%1$s/%2$s/%3$s',
		bp_rest_namespace(),
		bp_rest_version(),
		buddypress()->groups->id
	);

	wp_localize_script(
		'bp-dynamic-groups-script',
		'bpDynamicGroupsSettings',
		array(
			'path'  => ltrim( $path, '/' ),
			'root'  => esc_url_raw( get_rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);

	// Include the common JS template.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_dynamic_template_part( 'assets/widgets/dynamic-groups.php' );

	// List the block specific props.
	wp_add_inline_script(
		'bp-dynamic-groups-script',
		sprintf( 'var bpDynamicGroupsBlocks = %s;', wp_json_encode( array_values( $dynamic_groups_blocks ) ) ),
		'before'
	);
}

/**
 * Callback function to render the Dynamic Groups Block.
 *
 * @since 9.0.0
 *
 * @param array $attributes The block attributes.
 * @return string           HTML output.
 */
function bp_groups_render_dynamic_groups_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'title'        => '',
			'maxGroups'    => 5,
			'groupDefault' => 'active',
			'linkTitle'    => false,
		)
	);

	if ( ! $block_args['title'] ) {
		$block_args['title'] = __( 'Groups', 'buddypress' );
	}

	$classnames         = 'widget_bp_groups_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$max_groups = (int) $block_args['maxGroups'];
	$no_groups  = __( 'There are no groups to display.', 'buddypress' );

	/** This filter is documented in buddypress/src/bp-groups/classes/class-bp-groups-widget.php */
	$separator = apply_filters( 'bp_groups_widget_separator', '|' );

	// Make sure the widget ID is unique.
	$widget_id             = uniqid( 'groups-list-' );
	$groups_directory_link = bp_get_groups_directory_url();

	// Set the Block's title.
	if ( true === $block_args['linkTitle'] ) {
		$widget_content = sprintf(
			'<h2 class="widget-title"><a href="%1$s">%2$s</a></h2>',
			esc_url( $groups_directory_link ),
			esc_html( $block_args['title'] )
		);
	} else {
		$widget_content = sprintf( '<h2 class="widget-title">%s</h2>', esc_html( $block_args['title'] ) );
	}

	$item_options = array(
		'newest'       => array(
			'class' => '',
			'label' => _x( 'Newest', 'Groups', 'buddypress' ),
		),
		'active'       => array(
			'class' => '',
			'label' => _x( 'Active', 'Groups', 'buddypress' ),
		),
		'popular'      => array(
			'class' => '',
			'label' => _x( 'Popular', 'Groups', 'buddypress' ),
		),
		'alphabetical' => array(
			'class' => '',
			'label' => _x( 'Alphabetical', 'Groups', 'buddypress' ),
		),
	);

	$item_options_output = array();
	$separator_output    = sprintf( ' <span class="bp-separator" role="separator">%s</span> ', esc_html( $separator ) );

	foreach ( $item_options as $item_type => $item_attr ) {
		if ( $block_args['groupDefault'] === $item_type ) {
			$item_attr['class'] = ' class="selected"';
		}

		$item_options_output[] = sprintf(
			'<a href="%1$s" data-bp-sort="%2$s"%3$s>%4$s</a>',
			esc_url( $groups_directory_link ),
			esc_attr( $item_type ),
			$item_attr['class'],
			esc_html( $item_attr['label'] )
		);
	}

	$preview      = '';
	$default_args = array(
		'type'            => $block_args['groupDefault'],
		'per_page'        => $max_groups,
		'populate_extras' => true,
	);

	// Previewing the Block inside the editor.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$bp_query = groups_get_groups( $default_args );
		$preview  = sprintf( '<div class="widget-error">%s</div>', $no_groups );

		if ( is_array( $bp_query['groups'] ) && 0 < count( $bp_query['groups'] ) ) {
			$preview = '';
			foreach ( $bp_query['groups'] as $group ) {
				if ( 'newest' === $block_args['groupDefault'] ) {
					/* translators: %s is time elapsed since the group was created */
					$extra = sprintf( __( 'Created %s', 'buddypress' ), bp_get_group_date_created( $group ) );
				} elseif ( 'popular' === $block_args['groupDefault'] ) {
					$count = (int) $group->total_member_count;

					/* translators: %s is the number of Group members */
					$extra = sprintf( _n( '%s member', '%s members', $count, 'buddypress' ), bp_core_number_format( $count ) );
				} else {
					/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
					$extra = sprintf( __( 'Active %s', 'buddypress' ), bp_get_group_last_active( $group ) );
				}

				$preview .= bp_get_dynamic_template_part(
					'assets/widgets/dynamic-groups.php',
					'php',
					array(
						'data.link'              => bp_get_group_url( $group ),
						'data.name'              => bp_get_group_name( $group ),
						'data.avatar_urls.thumb' => bp_core_fetch_avatar(
							array(
								'item_id' => $group->id,
								'html'    => false,
								'object'  => 'group',
							)
						),
						'data.avatar_alt'        => esc_attr(
							sprintf(
								/* Translators: %s is the group's name. */
								__( 'Group Profile photo of %s', 'buddypress' ),
								bp_get_group_name( $group )
							)
						),
						'data.id'                => $group->id,
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
			buddypress()->groups->id
		);

		$default_path = add_query_arg(
			$default_args,
			$path
		);

		$preloaded_groups = rest_preload_api_request( '', $default_path );

		buddypress()->groups->block_globals['bp/dynamic-groups']->items[ $widget_id ] = (object) array(
			'selector'   => $widget_id,
			'query_args' => $default_args,
			'preloaded'  => reset( $preloaded_groups ),
		);

		// Only enqueue common/specific scripts and data once per page load.
		if ( ! has_action( 'wp_footer', 'bp_groups_blocks_add_script_data' ) ) {
			wp_set_script_translations( 'bp-dynamic-groups-script', 'buddypress' );
			wp_enqueue_script( 'bp-dynamic-groups-script' );

			add_action( 'wp_footer', 'bp_groups_blocks_add_script_data', 1 );
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
