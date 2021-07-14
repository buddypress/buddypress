/**
 * WordPress dependencies.
 */
const {
	blockEditor: {
		InspectorControls,
	},
	components: {
		Disabled,
		PanelBody,
		RangeControl,
		SelectControl,
		TextControl,
		ToggleControl,
	},
	element: {
		Fragment,
		createElement,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * BuddyPress dependencies.
 */
const {
	blockComponents: {
		ServerSideRender,
	},
} = bp;

/**
 * Internal dependencies.
 */
import { TYPES } from './constants';

const editDynamicGroupsBlock = ( { attributes, setAttributes } ) => {
	const { title, maxGroups, groupDefault, linkTitle } = attributes;

	return (
		<Fragment>
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
				<ServerSideRender block="bp/dynamic-groups" attributes={ attributes } />
			</Disabled>
		</Fragment>
	);
};

export default editDynamicGroupsBlock;
