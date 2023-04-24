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
 * BuddyPress dependencies.
 */
import { isActive } from '@buddypress/block-data';

/**
 * Internal dependencies.
 */
import { TYPES } from './constants';

const editDynamicMembersBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { title, maxMembers, memberDefault, linkTitle } = attributes;
	const sortTypes = !! isActive( 'friends' ) ? TYPES : TYPES.filter( ( type ) => 'popular' !== type.value );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Title', 'buddypress' ) }
						value={ title }
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
						max={ 10 }
						required
					/>
					<SelectControl
						label={ __( 'Default members to show', 'buddypress' ) }
						value={ memberDefault }
						options={ sortTypes }
						onChange={ ( option ) => {
							setAttributes( { memberDefault: option } );
						} }
					/>
					<ToggleControl
						label={ __( 'Link block title to Members directory', 'buddypress' ) }
						checked={ !! linkTitle }
						onChange={ () => {
							setAttributes( { linkTitle: ! linkTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/dynamic-members" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editDynamicMembersBlock;
