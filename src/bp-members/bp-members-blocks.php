<?php
/**
 * BP Members Blocks Functions.
 *
 * @package BuddyPress
 * @subpackage MembersBlocks
 * @since 6.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add BP Members blocks specific settings to the BP Blocks Editor ones.
 *
 * @since 6.0.0
 *
 * @param array $bp_editor_settings BP blocks editor settings.
 * @return array BP Members blocks editor settings.
 */
function bp_members_editor_settings( $bp_editor_settings = array() ) {
	$bp = buddypress();

	return array_merge(
		$bp_editor_settings,
		array(
			'members' => array(
				'isMentionEnabled'    => bp_is_active( 'activity' ) && bp_activity_do_mentions(),
				'isAvatarEnabled'     => $bp->avatar && $bp->avatar->show_avatars,
				'isCoverImageEnabled' => bp_is_active( 'members', 'cover_image' ),
			),
		)
	);
}
add_filter( 'bp_blocks_editor_settings', 'bp_members_editor_settings' );

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

	$block_args = wp_parse_args(
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
	$username   = bp_core_get_username( $member_id );
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
	$member_link  = bp_core_get_user_domain( $member_id );

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
				<a href="%1$s" class="button large primary button-primary" role="button">%2$s</a>
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

	$block_args = wp_parse_args(
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
		$member_link = bp_core_get_user_domain( $member->ID );

		// Set the Avatar output.
		if ( $bp->avatar && $bp->avatar->show_avatars && 'none' !== $block_args['avatarSize'] ) {
			$output .= sprintf(
				'<div class="item-header-avatar">
					<a href="%1$s">
						<img class="avatar" alt="%2$s" src="%3$s" />
					</a>
				</div>',
				esc_url( $member_link ),
				/* translators: %s: member name */
				sprintf( esc_attr__( 'Profile photo of %s', 'buddypress' ), $member->display_name ),
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
