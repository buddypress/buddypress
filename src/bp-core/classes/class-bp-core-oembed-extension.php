<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * API for responding and returning a custom oEmbed request.
 *
 * @since 2.6.0
 */
abstract class BP_Core_oEmbed_Extension {

	/** START PROPERTIES ****************************************************/

	/**
	 * (required) The slug endpoint.
	 *
	 * Should be your component id.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $slug_endpoint = '';

	/** END PROPERTIES ******************************************************/

	/**
	 * Constructor.
	 */
	final public function __construct() {
		$this->setup_properties();

		// Some rudimentary logic checking.
		if ( empty( $this->slug_endpoint ) ) {
			return;
		}

		$this->setup_hooks();
		$this->custom_hooks();
	}

	/** REQUIRED METHODS ****************************************************/

	/**
	 * Add content for your oEmbed response here.
	 *
	 * @since 2.6.0
	 *
	 * @return null
	 */
	abstract protected function content();

	/**
	 * Add a check for when you are on the page you want to oEmbed.
	 *
	 * You'll want to return a boolean here. eg. bp_is_single_activity().
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	abstract protected function is_page();

	/**
	 * Validate the URL to see if it matches your item ID.
	 *
	 * @since 2.6.0
	 *
	 * @param string $url URL to validate.
	 * @return int Your item ID
	 */
	abstract protected function validate_url_to_item_id( $url );

	/**
	 * Set the oEmbed response data.
	 *
	 * @since 2.6.0
	 *
	 * @param int $item_id Your item ID to do checks against.
	 * @return array Should contain 'content', 'title', 'author_url', 'author_name' as array
	 *               keys. 'author_url' and 'author_name' is optional; the rest are required.
	 */
	abstract protected function set_oembed_response_data( $item_id );

	/**
	 * Sets the fallback HTML for the oEmbed response.
	 *
	 * In a WordPress oEmbed item, the fallback HTML is a <blockquote>.  This is
	 * usually hidden after the <iframe> is loaded.
	 *
	 * @since 2.6.0
	 *
	 * @param int $item_id Your item ID to do checks against.
	 * @return string Fallback HTML you want to output.
	 */
	abstract protected function set_fallback_html( $item_id );

	/** OPTIONAL METHODS ****************************************************/

	/**
	 * If your oEmbed endpoint requires additional arguments, set them here.
	 *
	 * @see register_rest_route() View the $args parameter for more info.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	protected function set_route_args() {
		return array();
	}

	/**
	 * Set the iframe title.
	 *
	 * If not set, this will fallback to WP's 'Embedded WordPress Post'.
	 *
	 * @since 2.6.0
	 *
	 * @param int $item_id The item ID to do checks for.
	 */
	protected function set_iframe_title( $item_id ) {}

	/**
	 * Do what you need to do here to initialize any custom hooks.
	 *
	 * @since 2.6.0
	 */
	protected function custom_hooks() {}

	/**
	 * Set permalink for oEmbed link discovery.
	 *
	 * This method will be called on the page we want to oEmbed.  In most cases,
	 * you will not need to override this method.  However, if you need to, do
	 * override in your extended class.
	 *
	 * @since 2.6.0
	 */
	protected function set_permalink() {
		$url = bp_get_requested_url();

		// Remove querystring from bp_get_requested_url().
		if ( false !== strpos( bp_get_requested_url(), '?' ) ) {
			$url = substr( bp_get_requested_url(), 0, strpos( bp_get_requested_url(), '?' ) );
		}

		return $url;
	}

	/** HELPERS *************************************************************/

	/**
	 * Get the item ID when filtering the oEmbed HTML.
	 *
	 * Should only be used during the 'embed_html' hook.
	 *
	 * @since 2.6.0
	 */
	protected function get_item_id() {
		return $this->is_page() ? $this->validate_url_to_item_id( $this->set_permalink() ) : buddypress()->{$this->slug_endpoint}->embedid_in_progress;
	}

	/** SET UP **************************************************************/

	/**
	 * Set up properties.
	 *
	 * @since 2.6.0
	 */
	protected function setup_properties() {
		$this->slug_endpoint = sanitize_title( $this->slug_endpoint );
	}

	/**
	 * Hooks! We do the dirty work here, so you don't have to! :)
	 *
	 * More hooks are available in the setup_template_parts() method.
	 *
	 * @since 2.6.0
	 */
	protected function setup_hooks() {
		add_action( 'rest_api_init',    array( $this, 'register_route' ) );
		add_action( 'bp_embed_content', array( $this, 'inject_content' ) );

		add_filter( 'embed_template', array( $this, 'setup_template_parts' ) );
		add_filter( 'post_embed_url', array( $this, 'filter_embed_url' ) );
		add_filter( 'embed_html',     array( $this, 'filter_embed_html' ) );
		add_filter( 'oembed_discovery_links', array( $this, 'add_oembed_discovery_links' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'oembed_xml_request' ), 20, 4 );
	}

	/** HOOKS ***************************************************************/

	/**
	 * Register the oEmbed REST API route.
	 *
	 * @since 2.6.0
	 */
	public function register_route() {
		/** This filter is documented in wp-includes/class-wp-oembed-controller.php */
		$maxwidth = apply_filters( 'oembed_default_width', 600 );

		// Required arguments.
		$args = array(
			'url'      => array(
				'required'          => true,
				'sanitize_callback' => 'esc_url_raw',
			),
			'format'   => array(
				'default'           => 'json',
				'sanitize_callback' => 'wp_oembed_ensure_format',
			),
			'maxwidth' => array(
				'default'           => $maxwidth,
				'sanitize_callback' => 'absint',
			)
		);

		// Merge custom arguments here.
		$args = $args + (array) $this->set_route_args();

		register_rest_route( 'oembed/1.0', "/embed/{$this->slug_endpoint}", array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_item' ),
				'args'     => $args
			),
		) );
	}

	/**
	 * Set up custom embed template parts for BuddyPress use.
	 *
	 * @since 2.6.0
	 *
	 * @param string $template File path to current embed template.
	 * @return string
	 */
	public function setup_template_parts( $template ) {
		// Determine if we're on our BP page.
		if ( ! $this->is_page() || is_404() ) {
			return $template;
		}

		// Set up some BP-specific embed template overrides.
		add_action( 'get_template_part_embed', array( $this, 'content_buffer_start' ), -999, 2 );
		add_action( 'get_footer',              array( $this, 'content_buffer_end' ), -999 );

		// Return the original WP embed template.
		return $template;
	}

	/**
	 * Start object buffer.
	 *
	 * We're going to override WP's get_template_part( 'embed, 'content' ) call
	 * and inject our own template for BuddyPress use.
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 */
	public function content_buffer_start( $slug, $name ) {
		if ( 'embed' !== $slug || 'content' !== $name ) {
			return;
		}

		// Start the buffer to wipe out get_template_part( 'embed, 'content' ).
		ob_start();
	}

	/**
	 * End object buffer.
	 *
	 * We're going to override WP's get_template_part( 'embed, 'content' ) call
	 * and inject our own template for BuddyPress use.
	 *
	 * @since 2.6.0
	 *
	 * @param string $name Template name.
	 */
	public function content_buffer_end( $name ) {
		if ( 'embed' !== $name || is_404() ) {
			return;
		}

		// Wipe out get_template_part( 'embed, 'content' ).
		ob_end_clean();

		// Start our custom BuddyPress embed template!
		echo '<div ';
		post_class( 'wp-embed' );
		echo '>';

		// Template part for our embed header.
		bp_get_asset_template_part( 'embeds/header', bp_current_component() );

		/**
		 * Inject BuddyPress embed content on this hook.
		 *
		 * You shouldn't really need to use this if you extend the
		 * {@link BP_oEmbed_Component} class.
		 *
		 * @since 2.6.0
		 */
		do_action( 'bp_embed_content' );

		// Template part for our embed footer.
		bp_get_asset_template_part( 'embeds/footer', bp_current_component() );

		echo '</div>';
	}

	/**
	 * Adds oEmbed discovery links on single activity pages.
	 *
	 * @since 2.6.0
	 *
	 * @param string $retval Current discovery links.
	 * @return string
	 */
	public function add_oembed_discovery_links( $retval ) {
		if ( ! $this->is_page() ) {
			return $retval;
		}

		$permalink = $this->set_permalink();
		if ( empty( $permalink ) ) {
			return $retval;
		}

		add_filter( 'rest_url' , array( $this, 'filter_rest_url' ) );

		$retval = '<link rel="alternate" type="application/json+oembed" href="' . esc_url( get_oembed_endpoint_url( $permalink ) ) . '" />' . "\n";

		if ( class_exists( 'SimpleXMLElement' ) ) {
			$retval .= '<link rel="alternate" type="text/xml+oembed" href="' . esc_url( get_oembed_endpoint_url( $permalink, 'xml' ) ) . '" />' . "\n";
		}

		remove_filter( 'rest_url' , array( $this, 'filter_rest_url' ) );

		return $retval;
	}

	/**
	 * Fetch our oEmbed response data to return.
	 *
	 * A simplified version of {@link get_oembed_response_data()}.
	 *
	 * @since 2.6.0
	 *
	 * @link http://oembed.com/ View the 'Response parameters' section for more details.
	 *
	 * @param array $item  Custom oEmbed response data.
	 * @param int   $width The requested width.
	 * @return array
	 */
	protected function get_oembed_response_data( $item, $width ) {
		$data = wp_parse_args( $item, array(
			'version'       => '1.0',
			'provider_name' => get_bloginfo( 'name' ),
			'provider_url'  => get_home_url(),
			'author_name'   => get_bloginfo( 'name' ),
			'author_url'    => get_home_url(),
			'title'         => ucfirst( $this->slug_endpoint ),
			'type'          => 'rich',
		) );

		/** This filter is documented in /wp-includes/embed.php */
		$min_max_width = apply_filters( 'oembed_min_max_width', array(
			'min' => 200,
			'max' => 600
		) );

		$width  = min( max( $min_max_width['min'], $width ), $min_max_width['max'] );
		$height = max( ceil( $width / 16 * 9 ), 200 );

		$data['width']  = absint( $width );
		$data['height'] = absint( $height );

		// Set 'html' parameter.
		if ( 'video' === $data['type'] || 'rich' === $data['type'] ) {
			// Fake a WP post so we can use get_post_embed_html().
			$post = new stdClass;
			$post->post_content = $data['content'];
			$post->post_title   = $data['title'];

			$data['html'] = get_post_embed_html( $data['width'], $data['height'], $post );
		}

		// Remove temporary parameters.
		unset( $data['content'] );

		return $data;
	}

	/**
	 * Callback for the API endpoint.
	 *
	 * Returns the JSON object for the item.
	 *
	 * @since 2.6.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|array oEmbed response data or WP_Error on failure.
	 */
	public function get_item( $request ) {
		$url = $request['url'];

		$data = false;

		$item_id = (int) $this->validate_url_to_item_id( $url );

		if ( ! empty( $item_id ) ) {
			// Add markers to tell that we're embedding a single activity.
			// This is needed for various oEmbed response data filtering.
			if ( ! isset( buddypress()->{$this->slug_endpoint} ) || ! buddypress()->{$this->slug_endpoint} ) {
				buddypress()->{$this->slug_endpoint} = new stdClass;
			}
			buddypress()->{$this->slug_endpoint}->embedurl_in_progress = $url;
			buddypress()->{$this->slug_endpoint}->embedid_in_progress  = $item_id;

			// Save custom route args as well.
			$custom_args = array_keys( (array) $this->set_route_args() );
			if ( ! empty( $custom_args ) ) {
				buddypress()->{$this->slug_endpoint}->embedargs_in_progress = array();

				foreach( $custom_args as $arg ) {
					if ( isset( $request[ $arg ] ) ) {
						buddypress()->{$this->slug_endpoint}->embedargs_in_progress[ $arg ] = $request[ $arg ];
					}
				}
			}

			// Grab custom oEmbed response data.
			$item = $this->set_oembed_response_data( $item_id );

			// Set oEmbed response data.
			$data = $this->get_oembed_response_data( $item, $request['maxwidth'] );
		}

		if ( ! $data ) {
			return new WP_Error( 'oembed_invalid_url', get_status_header_desc( 404 ), array( 'status' => 404 ) );
		}

		return $data;
	}

	/**
	 * If oEmbed request wants XML, return XML instead of JSON.
	 *
	 * Basically a copy of {@link _oembed_rest_pre_serve_request()}. Unfortunate
	 * that we have to duplicate this just for a URL check.
	 *
	 * @since 2.6.0
	 *
	 * @param bool                      $served  Whether the request has already been served.
	 * @param WP_HTTP_ResponseInterface $result  Result to send to the client. Usually a WP_REST_Response.
	 * @param WP_REST_Request           $request Request used to generate the response.
	 * @param WP_REST_Server            $server  Server instance.
	 * @return bool
	 */
	public function oembed_xml_request( $served, $result, $request, $server ) {
		$params = $request->get_params();

		if ( ! isset( $params['format'] ) || 'xml' !== $params['format'] ) {
			return $served;
		}

		// Validate URL against our oEmbed endpoint. If not valid, bail.
		// This is our mod to _oembed_rest_pre_serve_request().
		$query_params = $request->get_query_params();
		if ( false === $this->validate_url_to_item_id( $query_params['url'] ) ) {
			return $served;
		}

		// Embed links inside the request.
		$data = $server->response_to_data( $result, false );

		if ( ! class_exists( 'SimpleXMLElement' ) ) {
			status_header( 501 );
			die( get_status_header_desc( 501 ) );
		}

		$result = _oembed_create_xml( $data );

		// Bail if there's no XML.
		if ( ! $result ) {
			status_header( 501 );
			return get_status_header_desc( 501 );
		}

		if ( ! headers_sent() ) {
			$server->send_header( 'Content-Type', 'text/xml; charset=' . get_option( 'blog_charset' ) );
		}

		echo $result;

		return true;
	}

	/**
	 * Pass our BuddyPress activity permalink for embedding.
	 *
	 * @since 2.6.0
	 *
	 * @see bp_activity_embed_rest_route_callback()
	 *
	 * @param string $retval Current embed URL.
	 * @return string
	 */
	public function filter_embed_url( $retval ) {
		if ( false === isset( buddypress()->{$this->slug_endpoint}->embedurl_in_progress ) && ! $this->is_page() ) {
			return $retval;
		}

		$url = $this->is_page() ? $this->set_permalink() : buddypress()->{$this->slug_endpoint}->embedurl_in_progress;
		$url = trailingslashit( $url );

		// This is for the 'WordPress Embed' block
		// @see bp_activity_embed_comments_button().
		if ( 'the_permalink' !== current_filter() ) {
			$url = add_query_arg( 'embed', 'true', trailingslashit( $url ) );

			// Add custom route args to iframe.
			if ( isset( buddypress()->{$this->slug_endpoint}->embedargs_in_progress ) && buddypress()->{$this->slug_endpoint}->embedargs_in_progress ) {
				foreach( buddypress()->{$this->slug_endpoint}->embedargs_in_progress as $key => $value ) {
					$url = add_query_arg( $key, $value, $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Filters the embed HTML for our BP oEmbed endpoint.
	 *
	 * @since 2.6.0
	 *
	 * @param string $retval Current embed HTML.
	 * @return string
	 */
	public function filter_embed_html( $retval ) {
		if ( false === isset( buddypress()->{$this->slug_endpoint}->embedurl_in_progress ) && ! $this->is_page() ) {
			return $retval;
		}

		$url = $this->set_permalink();

		$item_id = $this->is_page() ? $this->validate_url_to_item_id( $url ) : buddypress()->{$this->slug_endpoint}->embedid_in_progress;

		// Change 'Embedded WordPress Post' to custom title.
		$custom_title = $this->set_iframe_title( $item_id );
		if ( ! empty( $custom_title ) ) {
			$title_pos = strpos( $retval, 'title=' ) + 7;
			$title_end_pos = strpos( $retval, '"', $title_pos );

			$retval = substr_replace( $retval, esc_attr( $custom_title ), $title_pos, $title_end_pos - $title_pos );
		}

		// Add 'max-width' CSS attribute to IFRAME.
		// This will make our oEmbeds responsive.
		if ( false === strpos( $retval, 'style="max-width' ) ) {
			$retval = str_replace( '<iframe', '<iframe style="max-width:100%"', $retval );
		}

		// Remove default <blockquote>.
		$retval = substr( $retval, strpos( $retval, '</blockquote>' ) + 13 );

		// Set up new fallback HTML
		// @todo Maybe use KSES?
		$fallback_html = $this->set_fallback_html( $item_id );

		/**
		 * Dynamic filter to return BP oEmbed HTML.
		 *
		 * @since 2.6.0
		 *
		 * @var string $retval
		 */
		return apply_filters( "bp_{$this->slug_endpoint}_embed_html", $fallback_html . $retval );
	}

	/**
	 * Append our custom slug endpoint to oEmbed endpoint URL.
	 *
	 * Meant to be used as a filter on 'rest_url' before any call to
	 * {@link get_oembed_endpoint_url()} is used.
	 *
	 * @since 2.6.0
	 *
	 * @see add_oembed_discovery_links()
	 *
	 * @param string $retval Current oEmbed endpoint URL.
	 * @return string
	 */
	public function filter_rest_url( $retval = '' ) {
		return $retval . "/{$this->slug_endpoint}";
	}

	/**
	 * Inject content into the embed template.
	 *
	 * @since 2.6.0
	 */
	public function inject_content() {
		if ( ! $this->is_page() ) {
			return;
		}

		$this->content();
	}
}
