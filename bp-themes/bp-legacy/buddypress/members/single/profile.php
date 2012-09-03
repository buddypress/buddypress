<?php

/**
 * BuddyPress - Users Profile
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php if ( bp_is_my_profile() ) : ?>

	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<ul>

			<?php bp_get_options_nav(); ?>

		</ul>
	</div><!-- .item-list-tabs -->

<?php endif; ?>

<?php do_action( 'bp_before_profile_content' ); ?>

<div class="profile" role="main">

	<?php
		// Profile Edit
		if ( bp_is_current_action( 'edit' ) )
			bp_get_template_part( 'members/single/profile/edit' );

		// Change Avatar
		elseif ( bp_is_current_action( 'change-avatar' ) )
			bp_get_template_part( 'members/single/profile/change-avatar' );

		// Display XProfile
		elseif ( bp_is_active( 'xprofile' ) )
			bp_get_template_part( 'members/single/profile/profile-loop' );

		// Display WordPress profile (fallback)
		else
			bp_get_template_part( 'members/single/profile/profile-wp' )
	?>

</div><!-- .profile -->

<?php do_action( 'bp_after_profile_content' ); ?>