<div id="buddypress">

	<?php do_action( 'bp_before_member_plugin_template' ); ?>

	<div id="item-header">

		<?php bp_get_template_part( 'members/single/member-header' ) ?>

	</div><!-- #item-header -->

	<div id="item-nav">
		<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
			<ul>

				<?php bp_get_displayed_user_nav(); ?>

				<?php do_action( 'bp_member_options_nav' ); ?>

			</ul>
		</div>
	</div><!-- #item-nav -->

	<div id="item-body" role="main">

		<?php do_action( 'bp_before_member_body' ); ?>

		<div class="item-list-tabs no-ajax" id="subnav">
			<ul>

				<?php bp_get_options_nav(); ?>

				<?php do_action( 'bp_member_plugin_options_nav' ); ?>

			</ul>
		</div><!-- .item-list-tabs -->

		<h3><?php do_action( 'bp_template_title' ); ?></h3>

		<?php do_action( 'bp_template_content' ); ?>

		<?php do_action( 'bp_after_member_body' ); ?>

	</div><!-- #item-body -->

	<?php do_action( 'bp_after_member_plugin_template' ); ?>

</div><!-- #buddypress -->
