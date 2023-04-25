/**
 * Front-end Sitewide notices block class.
 *
 * @since 9.0.0
 */
class bpSitewideNoticeBlock {
	constructor( settings ) {
		const { path, dismissPath, root, nonce } = settings;
		this.path = path;
		this.dismissPath = dismissPath;
		this.root = root;
		this.nonce = nonce;
	}

	start() {
		// Listen to each Block's dismiss button clicks
		document.querySelectorAll( '.bp-sitewide-notice-block a.dismiss-notice' ).forEach( ( dismissButton ) => {
			dismissButton.addEventListener( 'click', ( event ) => {
				event.preventDefault();

				fetch( this.root + this.dismissPath, {
					method: 'POST',
					headers: {
						'X-WP-Nonce' : this.nonce,
					}
				} ).then(
					( response ) => response.json()
				).then(
					( data ) => {
						if ( 'undefined' !== typeof data && 'undefined' !== typeof data.dismissed && data.dismissed ) {
							document.querySelectorAll( '.bp-sitewide-notice-block' ).forEach( ( elem ) => {
								elem.remove();
							} );
						}
					}
				);
			} );
		} );
	}
}

// widget_bp_core_sitewide_messages buddypress widget wp-block-bp-sitewide-notices > bp-sitewide-notice > a.dismiss-notice
const settings = window.bpSitewideNoticeBlockSettings || {};
const bpSitewideNotice = new bpSitewideNoticeBlock( settings );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', bpSitewideNotice.start() );
} else {
	bpSitewideNotice.start();
}
