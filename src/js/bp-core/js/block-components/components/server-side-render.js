/**
 * WordPress dependencies.
 */
const {
	element: {
		createElement,
	},
} = wp;

/**
 * Compatibility Server Side Render.
 *
 * @since 9.0.0
 */
 export default function ServerSideRender( props ) {
	const CompatibiltyServerSideRender = wp.serverSideRender ? wp.serverSideRender : wp.editor.ServerSideRender;

	return (
		<CompatibiltyServerSideRender { ...props } />
	);
}
