<?php do_action( 'bp_before_options_bar' ) ?>

<div id="optionsbar">
	
	<h3><?php bp_get_options_title() ?></h3>

	<?php do_action( 'bp_inside_before_options_bar' ) ?>
	
	<?php if ( bp_has_options_avatar() ) : ?>

		<p class="avatar">
			<?php bp_get_options_avatar() ?>
		</p>

	<?php endif; ?>
		
		<ul id="options-nav">
			<?php bp_get_options_nav() ?>
		</ul>
	
	<?php do_action( 'bp_inside_after_options_bar' ) ?>

</div>

<?php do_action( 'bp_after_options_bar' ) ?>