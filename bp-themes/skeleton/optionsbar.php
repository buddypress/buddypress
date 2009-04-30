<?php
/*
 * /optionsbar.php
 * This is the second level navigation in the theme. It displays all the sub-nav
 * options for a component if you are viewing your own profile area. If you are viewing
 * another user's profile, it will display the top level navigation for that user.
 *
 * Loaded on URL: All URLs
 */
?>

<div id="optionsbar">

	<h3><?php bp_get_options_title() ?></h3>

	<?php do_action( 'bp_options_bar_before' ) ?>
	
	<?php if ( bp_has_options_avatar() ) : ?>

		<p class="avatar">
			<?php bp_get_options_avatar() ?>
		</p>

	<?php endif; ?>
		
		<ul id="options-nav">
			<?php bp_get_options_nav() ?>
		</ul>
	
	<?php do_action( 'bp_options_bar_after' ) ?>

	<div class="clear"></div>	
	
</div>
