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
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Internal dependencies.
 */
import { TYPES } from './constants';

const editDynamicGroupsBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { title, maxGroups, groupDefault, linkTitle } = attributes;
	const defaultTitle = title || __( 'Groups', 'buddypress' );
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
						label={ __( 'Max groups to show', 'buddypress' ) }
						value={ maxGroups }
						onChange={ ( value ) =>
							setAttributes( { maxGroups: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<SelectControl
						label={ __( 'Default groups to show', 'buddypress' ) }
						value={ groupDefault }
						options={ TYPES }
						onChange={ ( option ) => {
							setAttributes( { groupDefault: option } );
						} }
					/>
					<ToggleControl
						label={ __( 'Link block title to Groups directory', 'buddypress' ) }
						checked={ !! linkTitle }
						onChange={ () => {
							setAttributes( { linkTitle: ! linkTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/dynamic-groups" attributes={ ssrAttributes } />
			</Disabled>
		</div>
	);
};

export default editDynamicGroupsBlock;
