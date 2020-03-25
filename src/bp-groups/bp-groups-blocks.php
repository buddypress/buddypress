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
 * Add BP Groups blocks specific settings to the BP Blocks Editor ones.
 *
 * @since 6.0.0
 *
 * @param array $bp_editor_settings BP blocks editor settings.
 * @return array BP Groups blocks editor settings.
 */
function bp_groups_editor_settings( $bp_editor_settings = array() ) {
	$bp = buddypress();

	return array_merge(
		$bp_editor_settings,
		array(
			'groups' => array(
				'isAvatarEnabled'     => $bp->avatar && $bp->avatar->show_avatars && ! bp_disable_group_avatar_uploads(),
				'isCoverImageEnabled' => bp_is_active( 'groups', 'cover_image' ),
			),
		)
	);
}
add_filter( 'bp_blocks_editor_settings', 'bp_groups_editor_settings' );

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

	$block_args = wp_parse_args(
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
	$group_link        = bp_get_group_permalink( $group );
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
					<img src="%2$s" alt="%3$s" class="avatar">
				</a>
			</div>',
			esc_url( $group_link ),
			esc_url( $avatar ),
			// Translators: %s is the group's name.
			sprintf( esc_html__( 'Group Profile photo of %s', 'buddypress' ), $group_name )
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
				<a href="%1$s" class="button large primary button-primary" role="button">%2$s</a>
			</div>',
			esc_url( $group_link ),
			esc_html__( 'Visit Group', 'buddypress' )
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
