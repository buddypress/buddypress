/**
 * The notices controller.
 *
 * @file Defines the function to deal with REST API requests.
 * @author BuddyPress
 * @since 15.0.0
 */

const noticesRequest = async( options ) => {
	const path = window.bpNoticesCenterSettings.root + window.bpNoticesCenterSettings.path;
	const url = options.action ? path + options.action : path;
	const nonce = window.bpNoticesCenterSettings.nonce;
	const method = options.method ? options.method : 'GET';

	const response = await fetch( url, {
		method: method,
		headers: {
			'X-WP-Nonce' : nonce,
		}
	} );
	const result = await response.json();
	return result;
}

export default noticesRequest;
