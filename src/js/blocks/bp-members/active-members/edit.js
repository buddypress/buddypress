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
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const editActiveMembersBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { title, maxMembers } = attributes;
	const defaultTitle = title || __( 'Recently Active Members', 'buddypress' );
	const ssrAttributes = {
		...attributes,
		title: defaultTitle,
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Title', 'buddypress' ) }
						value={ defaultTitle }
						onChange={ ( text ) => {
							setAttributes( { title: text } );
						} }
					/>
					<RangeControl
						label={ __( 'Max members to show', 'buddypress' ) }
						value={ maxMembers }
						onChange={ ( value ) =>
							setAttributes( { maxMembers: value } )
						}
						min={ 1 }
						max={ 15 }
						required
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/active-members" attributes={ ssrAttributes } />
			</Disabled>
		</div>
	);
};

export default editActiveMembersBlock;
