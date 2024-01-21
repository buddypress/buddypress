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
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const editRecentPostsBlock = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { title, maxPosts, linkTitle } = attributes;
	const defaultTitle = title || __( 'Recent Networkwide Posts', 'buddypress' );

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
						label={ __( 'Max posts to show', 'buddypress' ) }
						value={ maxPosts }
						onChange={ ( value ) =>
							setAttributes( { maxPosts: value } )
						}
						min={ 1 }
						max={ 10 }
						required
					/>
					<ToggleControl
						label={ __( 'Link block title to Blogs directory', 'buddypress' ) }
						checked={ !! linkTitle }
						onChange={ () => {
							setAttributes( { linkTitle: ! linkTitle } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<ServerSideRender block="bp/recent-posts" attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default editRecentPostsBlock;
