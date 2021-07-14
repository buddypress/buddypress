/**
 * WordPress dependencies.
 */
const {
	blocks: {
		registerBlockType,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * Internal dependencies.
 */
import editLoginForm from './login-form/edit';
import transforms from './login-form/transforms';

registerBlockType( 'bp/login-form', {
	title: __( 'Login Form', 'buddypress' ),
	description: __( 'Show a Log In form to logged-out visitors, and a Log Out link to those who are logged in.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'admin-users',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: '',
		},
	},
	edit: editLoginForm,
	transforms: transforms,
} );
