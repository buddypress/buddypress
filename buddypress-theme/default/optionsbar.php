<div id="optionsbar"<?php get_options_class() ?>>
	<h3><?php bp_get_options_title() ?></h3>

	<?php if ( bp_has_options_avatar() ) : ?>

		<p class="avatar">
			<?php bp_get_options_avatar() ?>
		</p>

	<?php endif; ?>

	<?php if ( bp_has_options_nav() ) : ?>

		<ul id="options-nav"<?php has_icons() ?>>
			<?php bp_get_options_nav() ?>
		</ul>

	<?php else : ?>

		<?php get_sidebar() ?>

	<?php endif; ?>
	
</div>