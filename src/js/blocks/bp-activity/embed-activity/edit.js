/**
 * WordPress dependencies.
 */
import {
	RichText,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Placeholder,
	Disabled,
	SandBox,
	Button,
	ExternalLink,
	Spinner,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * BuddyPress dependencies.
 */
import { embedScriptURL } from '@buddypress/block-data';

const EditEmbedActivity = ( {
	attributes,
	setAttributes,
	isSelected,
	preview,
	fetching
} ) => {
	const blockProps = useBlockProps();
	const { url, caption } = attributes;
	const label = __( 'BuddyPress Activity URL', 'buddypress' );
	const [ value, setURL ] = useState( url );
	const [ isEditingURL, setIsEditingURL ] = useState( ! url );

	const onSubmit = ( event ) => {
		if ( event ) {
			event.preventDefault();
		}

		setIsEditingURL( false );
		setAttributes( { url: value } );
	};

	const switchBackToURLInput = ( event ) => {
		if ( event ) {
			event.preventDefault();
		}

		setIsEditingURL( true );
	};

	const editToolbar = (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon="edit"
					title={ __( 'Edit URL', 'buddypress' ) }
					onClick={ switchBackToURLInput }
				/>
			</ToolbarGroup>
		</BlockControls>
	);

	if ( isEditingURL ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="buddicons-activity"
					label={ label }
					className="wp-block-embed"
					instructions={ __( 'Paste the link to the activity content you want to display on your site.', 'buddypress' ) }
				>
					<form onSubmit={ onSubmit }>
						<input
							type="url"
							value={ value || '' }
							className="components-placeholder__input"
							aria-label={ label }
							placeholder={ __( 'Enter URL to embed here…', 'buddypress' ) }
							onChange={ ( event ) => setURL( event.target.value ) }
						/>
						<Button variant="primary" type="submit">
							{ __( 'Embed', 'buddypress' ) }
						</Button>
					</form>
					<div className="components-placeholder__learn-more">
						<ExternalLink
							href={ __( 'https://codex.buddypress.org/activity-embeds/', 'buddypress' ) }
						>
							{ __( 'Learn more about activity embeds', 'buddypress' ) }
						</ExternalLink>
					</div>
				</Placeholder>
			</div>
		);
	}

	if ( fetching ) {
		return (
			<div className="wp-block-embed is-loading">
				<Spinner />
				<p>{ __( 'Embedding…', 'buddypress' ) }</p>
			</div>
		);
	}

	if ( ! preview || ! preview['x_buddypress'] || 'activity' !== preview['x_buddypress'] ) {
		return (
			<div { ...blockProps }>
				{ editToolbar }
				<Placeholder
					icon="buddicons-activity"
					label={ label }
				>
					<p className="components-placeholder__error">
						{ __( 'The URL you provided is not a permalink to a public BuddyPress Activity. Please use another URL.', 'buddypress' ) }
					</p>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ ! isEditingURL && editToolbar }
			<figure className="wp-block-embed is-type-bp-activity">
				<div className="wp-block-embed__wrapper">
					<Disabled>
						<SandBox
							html={ preview && preview.html ? preview.html : '' }
							scripts={ [ embedScriptURL ] }
						/>
					</Disabled>
				</div>
				{ ( ! RichText.isEmpty( caption ) || isSelected ) && (
					<RichText
						tagName="figcaption"
						placeholder={ __( 'Write caption…', 'buddypress' ) }
						value={ caption }
						onChange={ ( value ) => setAttributes( { caption: value } ) }
						inlineToolbar
					/>
				) }
			</figure>
		</div>
	);
}

const editEmbedActivityBlock = compose( [
	withSelect( ( select, ownProps ) => {
		const { url } = ownProps.attributes;
		const {
			getEmbedPreview,
			isRequestingEmbedPreview,
		} = select( 'core' );

		const preview = !! url && getEmbedPreview( url );
		const fetching = !! url && isRequestingEmbedPreview( url );

		return {
			preview: preview,
			fetching: fetching,
		};
	} ),
] )( EditEmbedActivity );

export default editEmbedActivityBlock;
