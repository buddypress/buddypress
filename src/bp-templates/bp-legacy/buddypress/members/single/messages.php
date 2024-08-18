<?php
/**
 * BuddyPress - Users Messages
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 15.0.0
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddypress' ); ?>" role="navigation">
	<ul>

		<?php bp_get_options_nav(); ?>

	</ul>

	<?php if ( bp_is_messages_inbox() || bp_is_messages_sentbox() ) : ?>

		<div class="message-search"><?php bp_message_search_form(); ?></div>

	<?php endif; ?>

</div><!-- .item-list-tabs -->

<?php
switch ( bp_current_action() ) :

	// Inbox/Sentbox
	case 'inbox'   :
	case 'sentbox' :

		/**
		 * Fires before the member messages content for inbox and sentbox.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_before_member_messages_content' ); ?>

		<?php if ( bp_is_messages_inbox() ) : ?>
			<h2 class="bp-screen-reader-text">
				<?php
					/* translators: accessibility text */
					esc_html_e( 'Messages inbox', 'buddypress' );
				?>
			</h2>
		<?php elseif ( bp_is_messages_sentbox() ) : ?>
			<h2 class="bp-screen-reader-text">
				<?php
					/* translators: accessibility text */
					esc_html_e( 'Sent Messages', 'buddypress' );
				?>
			</h2>
		<?php endif; ?>

		<div class="messages">
			<?php bp_get_template_part( 'members/single/messages/messages-loop' ); ?>
		</div><!-- .messages -->

		<?php

		/**
		 * Fires after the member messages content for inbox and sentbox.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bp_after_member_messages_content' );
		break;

	// Single Message View
	case 'view' :
		bp_get_template_part( 'members/single/messages/single' );
		break;

	// Compose
	case 'compose' :
		bp_get_template_part( 'members/single/messages/compose' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
