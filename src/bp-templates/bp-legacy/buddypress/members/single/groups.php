<?php
/**
 * BuddyPress - Users Groups
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 11.0.0
 */
?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddypress' ); ?>" role="navigation">
	<ul>
		<?php bp_get_options_nav(); ?>

		<?php if ( ! bp_is_current_action( 'invites' ) ) : ?>

			<li id="groups-order-select" class="last filter">

				<label for="groups-order-by"><?php esc_html_e( 'Order By:', 'buddypress' ); ?></label>
				<select id="groups-order-by">
					<option value="active"><?php esc_html_e( 'Last Active', 'buddypress' ); ?></option>
					<option value="popular"><?php esc_html_e( 'Most Members', 'buddypress' ); ?></option>
					<option value="newest"><?php esc_html_e( 'Newly Created', 'buddypress' ); ?></option>
					<option value="alphabetical"><?php esc_html_e( 'Alphabetical', 'buddypress' ); ?></option>

					<?php

					/**
					 * Fires inside the members group order options select input.
					 *
					 * @since 1.2.0
					 */
					do_action( 'bp_member_group_order_options' ); ?>

				</select>
			</li>

		<?php endif; ?>

	</ul>
</div><!-- .item-list-tabs -->

<?php

switch ( bp_current_action() ) :

	// Home/My Groups
	case 'my-groups' :

		/**
		 * Fires before the display of member groups content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_before_member_groups_content' ); ?>

		<?php if ( is_user_logged_in() ) : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				esc_html_e( 'My groups', 'buddypress' );
			?></h2>
		<?php else : ?>
			<h2 class="bp-screen-reader-text"><?php
				/* translators: accessibility text */
				esc_html_e( 'Member\'s groups', 'buddypress' );
			?></h2>
		<?php endif; ?>

		<div class="groups mygroups">

			<?php bp_get_template_part( 'groups/groups-loop' ); ?>

		</div>

		<?php

		/**
		 * Fires after the display of member groups content.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_after_member_groups_content' );
		break;

	// Group Invitations
	case 'invites' :
		bp_get_template_part( 'members/single/groups/invites' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
