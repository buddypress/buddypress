<?php do_action( 'bp_before_member_header' ) ?>

<div id="item-header-avatar">
	<a href="<?php bp_user_link() ?>">
		<?php bp_displayed_user_avatar( 'type=full' ) ?>
	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<h2 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_displayed_user_fullname() ?></a> <span class="highlight">@<?php bp_displayed_user_username() ?> <span>?</span></span></h2>
	<span class="activity"><?php bp_last_activity( bp_displayed_user_id() ) ?></span>

	<?php do_action( 'bp_before_member_header_meta' ) ?>

	<div id="item-meta">
		<?php if ( function_exists( 'bp_activity_latest_update' ) ) : ?>
			<div id="latest-update">
				<?php bp_activity_latest_update( bp_displayed_user_id() ) ?>
			</div>
		<?php endif; ?>

		<div id="item-buttons">

			<?php do_action( 'bp_member_header_actions' ); ?>

		</div><!-- #item-buttons -->

		<?php
		 /***
		  * If you'd like to show specific profile fields here use:
		  * bp_profile_field_data( 'field=About Me' ); -- Pass the name of the field
		  */
		?>

		<?php do_action( 'bp_profile_header_meta' ) ?>

	</div><!-- #item-meta -->

</div><!-- #item-header-content -->

<?php do_action( 'bp_after_member_header' ) ?>

<?php do_action( 'template_notices' ) ?>