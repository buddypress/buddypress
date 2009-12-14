<div id="item-header">
	<?php bp_group_avatar() ?>

	<h2><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a> <span class="activity"><?php printf( __( 'active %s ago', 'buddypress' ), bp_get_group_last_active() ) ?></span></h2>

	<span class="highlight"><?php bp_group_type() ?></span>

	<div id="item-meta">
		<?php bp_group_description() ?>

		<?php bp_group_join_button() ?>

		<?php do_action( 'bp_group_header_content' ) ?>
	</div>

</div>

<div class="item-list-tabs no-ajax" id="user-nav">
	<ul>
		<?php bp_get_options_nav() ?>

		<?php do_action( 'bp_members_directory_member_types' ) ?>
	</ul>
</div>