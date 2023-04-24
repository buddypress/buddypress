/**
 * WordPress dependencies.
 */
import {
    InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Disabled,
	Notice,
	PanelBody,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * BuddyPress dependencies.
 */
import { getCurrentWidgetsSidebar } from '@buddypress/block-data';

const editPrimaryNavBlock = ( { attributes, setAttributes, clientId } ) => {
	const blockProps = useBlockProps();
	const { displayTitle } = attributes;
	const currentSidebar = getCurrentWidgetsSidebar( clientId );
	const disabledSidebars = ['sidebar-buddypress-members', 'sidebar-buddypress-groups'];

	if ( currentSidebar && currentSidebar.id && -1 !== disabledSidebars.indexOf( currentSidebar.id ) ) {
		return (
			<Notice status="error" isDismissible={ false }>
				<p>
					{ __( 'The BuddyPress Primary Navigation block shouldn\'t be used into this widget area. Please remove it.', 'buddypress' ) }
				</p>
			</Notice>
		);
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Include navigation title', 'buddypress' ) }
						checked={ !! displayTitle }
						onChange={ () => {
							setAttributes( { displayTitle: ! displayTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/primary-nav" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editPrimaryNavBlock;
