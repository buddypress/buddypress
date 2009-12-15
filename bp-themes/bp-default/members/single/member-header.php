<h2 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_displayed_user_fullname() ?></a> <span class="activity"><?php bp_last_activity( bp_displayed_user_id() ) ?></span></h2>

<div id="item-meta">
	<div id="latest-update">
		<?php bp_activity_latest_update( bp_displayed_user_id() ) ?>
	</div>

	<div id="item-buttons">
		<?php if ( function_exists('bp_add_friend_button') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>

		<?php if ( function_exists('bp_send_message_button') ) : ?>
			<?php bp_send_message_button() ?>
		<?php endif; ?>
	</div>

	<?php
	 /***
	  * If you'd like to show specific profile fields here use:
	  * bp_profile_field_data( 'field=About Me' ); -- Pass the name of the field
	  */
	?>

	<?php do_action( 'bp_profile_header_content' ) ?>

</div>

<?php do_action( 'template_notices' ) ?>

<div class="item-list-tabs no-ajax" id="user-nav">
	<ul>
		<?php bp_get_user_nav() ?>

		<?php do_action( 'bp_members_directory_member_types' ) ?>
	</ul>
</div>