/**
 * WordPress dependencies.
 */
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * BuddyPress dependencies.
 */
import {
	currentPostId,
	activityTypes,
} from '@buddypress/block-data';

const editDynamicActivitiesBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { postId, maxActivities, type, title } = attributes;
	const post = currentPostId();
	const types = activityTypes();

	if ( ! postId && post ) {
		setAttributes( { postId: post } );
		if ( ! attributes.postId ) {
			attributes.postId = post;
		}
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true } className="bp-latest-activities">
					<TextControl
						label={ __( 'Title', 'buddypress' ) }
						value={ title }
						onChange={ ( text ) => {
							setAttributes( { title: text } );
						} }
					/>
					<RangeControl
						label={ __( 'Maximum amount to display', 'buddypress' ) }
						value={ maxActivities }
						onChange={ ( value ) =>
							setAttributes( { maxActivities: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<SelectControl
						multiple
						label={ __( 'Type', 'buddypress' ) }
						value={ type }
						options={ types }
						onChange={ ( option ) => {
							setAttributes( { type: option } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/latest-activities" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editDynamicActivitiesBlock;
