<div id="optionsbar">
	<h3>Options Bar</h3>

	<?php if ( bp_get_options_avatar() ) : ?>
		<div class="avatar">
			<?php bp_get_options_avatar() ?>
		</div>
	<?php endif; ?>

	<?php bp_get_options_nav() ?>

</div>