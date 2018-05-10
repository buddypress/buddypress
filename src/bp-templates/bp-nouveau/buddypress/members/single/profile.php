<?php
/**
 * BuddyPress - Users Profile
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Profile menu', 'buddypress' ); ?>">
	<ul class="subnav">

		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	</ul>
</nav><!-- .item-list-tabs -->

<?php bp_nouveau_member_hook( 'before', 'profile_content' ); ?>

<div class="profile <?php echo bp_current_action(); ?>">

<?php
switch ( bp_current_action() ) :

	// Edit
	case 'edit':
		bp_get_template_part( 'members/single/profile/edit' );
		break;

	// Change Avatar
	case 'change-avatar':
		bp_get_template_part( 'members/single/profile/change-avatar' );
		break;

	// Change Cover Image
	case 'change-cover-image':
		bp_get_template_part( 'members/single/profile/change-cover-image' );
		break;

	// Compose
	case 'public':
		// Display XProfile
		if ( bp_is_active( 'xprofile' ) ) {
			bp_get_template_part( 'members/single/profile/profile-loop' );

		// Display WordPress profile (fallback)
		} else {
			bp_get_template_part( 'members/single/profile/profile-wp' );
		}

		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
?>
</div><!-- .profile -->

<?php
bp_nouveau_member_hook( 'after', 'profile_content' );
