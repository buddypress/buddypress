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
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * BuddyPress dependencies.
 */
import { currentPostId } from '@buddypress/block-data';

/**
 * Internal dependencies.
 */
import { TYPES } from './constants';

const editDynamicFriendsBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { postId, maxFriends, friendDefault, linkTitle } = attributes;
	const post = currentPostId();

	if ( ! postId && post ) {
		setAttributes( { postId: post } );
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'buddypress' ) } initialOpen={ true }>
					<RangeControl
						label={ __( 'Max friends to show', 'buddypress' ) }
						value={ maxFriends }
						onChange={ ( value ) =>
							setAttributes( { maxFriends: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<SelectControl
						label={ __( 'Default members to show', 'buddypress' ) }
						value={ friendDefault }
						options={ TYPES }
						onChange={ ( option ) => {
							setAttributes( { friendDefault: option } );
						} }
					/>
					<ToggleControl
						label={ __( 'Link block title to Member\'s profile friends page', 'buddypress' ) }
						checked={ !! linkTitle }
						onChange={ () => {
							setAttributes( { linkTitle: ! linkTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/friends" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editDynamicFriendsBlock;
