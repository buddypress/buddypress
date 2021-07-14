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
import editDynamicActivitiesBlock from './latest-activities/edit';
import transforms from './latest-activities/transforms';

registerBlockType( 'bp/latest-activities', {
	title: __( 'Latest Activities', 'buddypress' ),
	description: __( 'Display the latest updates of the post author (when used into a page or post), of the displayed user (when viewing their profile) or of your community.', 'buddypress' ),
	icon: {
		background: '#fff',
		foreground: '#d84800',
		src: 'buddicons-activity',
	},
	category: 'buddypress',
	attributes: {
		title: {
			type: 'string',
			default: __( 'Latest updates', 'buddypress' ),
		},
		maxActivities: {
			type: 'number',
			default: 5
		},
		type: {
			type: 'array',
			default: ['activity_update'],
		},
		postId: {
			type: 'number',
			default: 0,
		},
	},
	edit: editDynamicActivitiesBlock,
	transforms: transforms,
} );
