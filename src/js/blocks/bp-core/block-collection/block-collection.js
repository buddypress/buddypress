/**
 * WordPress dependencies.
 */
import { registerBlockCollection } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockCollection( 'bp', {
	title: __( 'BuddyPress', 'buddypress' ),
	icon: 'buddicons-buddypress-logo',
} );
