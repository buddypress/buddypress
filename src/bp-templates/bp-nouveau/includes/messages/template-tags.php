<?php
/**
 * Messages template tags
 *
 * @since 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks into the private messages template.
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_messages_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a message hook
	$hook[] = 'message';

	if ( $suffix ) {
		if ( 'compose_content' === $suffix ) {
			$hook[2] = 'messages';
		}

		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Prints the JS Templates of the private messages UI.
 *
 * @since 10.0.0
 */
function bp_nouveau_messages_print_templates() {
	bp_get_template_part( 'common/js-templates/messages/index' );
}

/**
 * Prints the HTML placeholders of the private messages UI.
 *
 * @since 10.0.0
 */
function bp_nouveau_messages_print_placeholders() {
	?>
	<div class="subnav-filters filters user-subnav bp-messages-filters" id="subsubnav"></div>

	<div class="bp-messages-feedback"></div>
	<div class="bp-messages-content"></div>
	<?php
}

/**
 * Load the new Messages User Interface
 *
 * @since 3.0.0
 */
function bp_nouveau_messages_member_interface() {
	/**
	 * Fires before the member messages content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_member_messages_content' );

	/**
	 * Load the JS templates to manage Priveate Messages into site's footer.
	 *
	 * @since 10.0.0 Hook to the `wp_footer` action to print the JS templates.
	 */
	add_action( 'wp_footer', 'bp_nouveau_messages_print_templates' );
	bp_nouveau_messages_print_placeholders();

	/**
	 * Private hook to preserve backward compatibility with plugins needing the above placeholders to be located
	 * into: `bp-templates/bp-nouveau/buddypress/common/js-templates/messahges/index.php`.
	 *
	 * @since 10.0.0
	 */
	do_action( '_bp_nouveau_messages_print_placeholders' );

	// Load the Private messages UI


	/**
	 * Fires after the member messages content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_member_messages_content' );
}

/**
 * Output the Member's messages search form.
 *
 * @since  3.0.0
 * @since  3.2.0 Move the function into Template Tags and use a template part.
 */
function bp_nouveau_message_search_form() {
	/**
	 * Filters the private message component search form.
	 *
	 * @since 2.2.0
	 *
	 * @param string $search_form_html HTML markup for the message search form.
	 */
	$search_form_html = apply_filters(
		'bp_message_search_form',
		bp_buffer_template_part( 'common/js-templates/messages/search-form', null, false )
	);

	echo wp_kses(
		$search_form_html,
		array(
			'form'   => array(
				'action'         => true,
				'method'         => true,
				'id'             => true,
				'class'          => true,
				'data-bp-search' => true,
			),
			'label'  => array(
				'for'   => true,
				'class' => true,
			),
			'input'  => array(
				'type'        => true,
				'id'          => true,
				'name'        => true,
				'placeholder' => true,
				'class'       => true,
			),
			'button' => array(
				'type'  => true,
				'name'  => true,
				'id'    => true,
				'class' => true,
			),
			'span'   => array(
				'class'       => true,
				'aria-hidden' => true,
			),
		)
	);
}
