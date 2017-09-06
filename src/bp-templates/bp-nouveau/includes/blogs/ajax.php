<?php
/**
 * Blogs Ajax functions
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers friends AJAX actions.
 *
 * @todo this funciton CANNOT be run when the file is included (like it is now). Move to a function and hook to something.
 */
bp_nouveau_register_ajax_actions( array(
	array( 'blogs_filter' => array( 'function' => 'bp_nouveau_ajax_object_template_loader', 'nopriv' => true ) ),
) );
