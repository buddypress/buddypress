<?php
/**
 * @group core
 * @group BP_Media_Extractor
 */
class BP_Tests_Media_Extractor extends BP_UnitTestCase {
	public static $media_extractor = null;
	public static $richtext        = '';


	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$media_extractor = new BP_Media_Extractor();
		self::$richtext        = "Hello world.

		This sample text is used to test the media extractor parsing class. @paulgibbs thinks it's pretty cool.
		Another thing really cool is this @youtube:

		https://www.youtube.com/watch?v=2mjvfnUAfyo

		This video is literally out of the world, but uses a different protocol to the embed above:

		http://www.youtube.com/watch?v=KaOC9danxNo

		<a href='https://example.com'>Testing a regular link.</a>
		<strong>But we should throw in some markup and maybe even an <img src='http://example.com/image.gif'>.
		<a href='http://example.com'><img src='http://example.com/image-in-a-link.gif' /></a></strong>.
		It definitely does not like <img src='data:1234567890A'>data URIs</img>. @

		The parser only extracts wp_allowed_protocols() protocols, not something like <a href='phone:004400'>phone</a>.

		[caption id='example']Here is a caption shortcode.[/caption]

		There are two types of [gallery] shortcodes; one like that, and another with IDs specified.

		Audio shortcodes:
		[audio src='http://example.com/source.mp3'] 
		[audio src='http://example.com/source.wav' loop='on' autoplay='off' preload='metadata'].

		The following shortcode should be picked up by the shortcode extractor, but not the audio extractor, because
		it has an unrecognised file extension (for an audio file). [audio src='http://example.com/not_audio.gif']
		<a href='http://example.com/more_audio.mp3'>This should be picked up, too</a>.

		Video shortcodes:
		[video src='http://example.com/source.ogv']
		[video src='http://example.com/source.webm' loop='on' autoplay='off' preload='metadata']

		The following shortcode should be picked up by the shortcode extractor, but not the video extractor, because
		it has an unrecognised file extension (for a video file). [video src='http://example.com/not_video.mp3']
		";
	}


	/**
	 * General.
	 */

	public function test_check_media_extraction_return_types() {
		$media = self::$media_extractor->extract( self::$richtext );

		foreach ( array( 'has', 'embeds', 'images', 'links', 'mentions', 'shortcodes', 'audio' ) as $key ) {
			$this->assertArrayHasKey( $key, $media );
			$this->assertInternalType( 'array', $media[ $key ] );
		}

		foreach ( $media['has'] as $item ) {
			$this->assertInternalType( 'int', $item );
		}

		foreach ( $media['links'] as $item ) {
			$this->assertArrayHasKey( 'url', $item );
			$this->assertInternalType( 'string', $item['url'] );
			$this->assertNotEmpty( $item['url'] );
		}

		foreach ( $media['mentions'] as $item ) {
			$this->assertArrayHasKey( 'name', $item );
			$this->assertInternalType( 'string', $item['name'] );
			$this->assertNotEmpty( $item['name'] );
		}

		foreach ( $media['images'] as $item ) {
			$this->assertArrayHasKey( 'height', $item );
			$this->assertInternalType( 'int', $item['height'] );

			$this->assertArrayHasKey( 'width', $item );
			$this->assertInternalType( 'int', $item['width'] );

			$this->assertArrayHasKey( 'source', $item );
			$this->assertInternalType( 'string', $item['source'] );
			$this->assertNotEmpty( $item['source'] );

			$this->assertArrayHasKey( 'url', $item );
			$this->assertInternalType( 'string', $item['url'] );
			$this->assertNotEmpty( $item['url'] );
		}

		foreach ( $media['shortcodes'] as $shortcode_type => $item ) {
			$this->assertArrayHasKey( 'attributes', $item );
			$this->assertInternalType( 'array', $item['attributes'] );

			$this->assertArrayHasKey( 'content', $item );
			$this->assertInternalType( 'string', $item['content'] );

			$this->assertArrayHasKey( 'type', $item );
			$this->assertInternalType( 'string', $item['type'] );

			$this->assertArrayHasKey( 'original', $item );
			$this->assertInternalType( 'string', $item['original'] );
		}

		foreach ( $media['embeds'] as $item ) {
			$this->assertArrayHasKey( 'url', $item );
			$this->assertInternalType( 'string', $item['url'] );
			$this->assertNotEmpty( $item['url'] );
		}

		foreach ( $media['audio'] as $item ) {
			$this->assertArrayHasKey( 'url', $item );
			$this->assertInternalType( 'string', $item['url'] );
			$this->assertNotEmpty( $item['url'] );

			$this->assertArrayHasKey( 'source', $item );
			$this->assertInternalType( 'string', $item['source'] );
			$this->assertNotEmpty( $item['source'] );
		}
	}

	public function test_check_media_extraction_counts_are_correct() {
		$media = self::$media_extractor->extract( self::$richtext );
		$types = array_keys( $media );

		foreach ( $types as $type ) {
			if ( $type === 'has' ) {
				continue;
			}

			$this->assertArrayHasKey( $type, $media['has'] );
			$this->assertSame( count( $media[ $type ] ), $media['has'][ $type ], "Difference with the 'has' count for {$type}." );
		}
	}


	public function test_extract_multiple_media_types_from_content() {
		$this->factory->user->create( array( 'user_login' => 'paulgibbs' ) );
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::LINKS | BP_Media_Extractor::MENTIONS );

		$this->assertNotEmpty( $media['links'] );
		$this->assertNotEmpty( $media['mentions'] );
		$this->assertArrayNotHasKey( 'shortcodes', $media );
	}

	public function test_extract_media_from_a_wp_post() {
		$post_id = $this->factory->post->create( array( 'post_content' => self::$richtext ) );
		$media   = self::$media_extractor->extract( get_post( $post_id ), BP_Media_Extractor::LINKS );

		$this->assertArrayHasKey( 'links', $media );
		$this->assertSame( 'https://example.com', $media['links'][0]['url'] );
		$this->assertSame( 'http://example.com',  $media['links'][1]['url'] );
	}


	/**
	 * Link extraction.
	 */

	public function test_extract_links_from_content() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::LINKS );

		$this->assertArrayHasKey( 'links', $media );
		$this->assertSame( 'https://example.com', $media['links'][0]['url'] );
		$this->assertSame( 'http://example.com',  $media['links'][1]['url'] );
	}

	public function test_extract_no_links_from_content_with_invalid_links() {
		$richtext = "This is some sample text, with links, but not the kinds we want.		
		<a href=''>Empty links should be ignore<a/> and
		<a href='phone:004400'>weird protocols should be ignored, too</a>.
		";

		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::LINKS );
		$this->assertSame( 0, $media['has']['links'] );
	}


	/**
	 * at-mentions extraction.
	 */

	public function test_extract_mentions_from_content_with_activity_enabled() {
		$this->factory->user->create( array( 'user_login' => 'paulgibbs' ) );
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::MENTIONS );

		$this->assertArrayHasKey( 'user_id', $media['mentions'][0] );
		$this->assertSame( 'paulgibbs', $media['mentions'][0]['name'] );
	}

	public function test_extract_mentions_from_content_with_activity_disabled() {
		$this->factory->user->create( array( 'user_login' => 'paulgibbs' ) );
		$was_activity_enabled = false;

		// Turn activity off.
		if ( isset( buddypress()->active_components['activity'] ) ) {
			unset( buddypress()->active_components['activity'] );
			$was_activity_enabled = true;
		}


		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::MENTIONS );

		$this->assertArrayNotHasKey( 'user_id', $media['mentions'][0] );
		$this->assertSame( 'paulgibbs', $media['mentions'][0]['name'] );


		// Turn activity on.
		if ( $was_activity_enabled ) {
			buddypress()->active_components['activity'] = 1;
		}
	}


	/**
	 * Shortcodes extraction.
	 */

	public function test_extract_shortcodes_from_content() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::SHORTCODES );

		$this->assertArrayHasKey( 'shortcodes', $media );

		$this->assertSame( 'caption', $media['shortcodes'][0]['type'] );
		$this->assertSame( 'Here is a caption shortcode.', $media['shortcodes'][0]['content'] );
		$this->assertSame( 'example', $media['shortcodes'][0]['attributes']['id'] );

		$this->assertSame( 'gallery', $media['shortcodes'][1]['type'] );
		$this->assertEmpty( $media['shortcodes'][1]['content'] );

		$this->assertSame( 'audio', $media['shortcodes'][2]['type'] );
		$this->assertEmpty( $media['shortcodes'][2]['content'] );
		$this->assertSame( 'http://example.com/source.mp3', $media['shortcodes'][2]['attributes']['src'] );

		$this->assertSame( 'audio', $media['shortcodes'][3]['type'] );
		$this->assertEmpty( $media['shortcodes'][3]['content'] );
		$this->assertSame( 'http://example.com/source.wav', $media['shortcodes'][3]['attributes']['src'] );
		$this->assertSame( 'on', $media['shortcodes'][3]['attributes']['loop'] );
		$this->assertSame( 'off', $media['shortcodes'][3]['attributes']['autoplay'] );
		$this->assertSame( 'metadata', $media['shortcodes'][3]['attributes']['preload'] );
	}

	public function test_extract_no_shortcodes_from_content_with_unregistered_shortcodes() {
		$richtext = 'This sample text has some made-up [fake]shortcodes[/fake].';

		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::SHORTCODES );
		$this->assertSame( 0, $media['has']['shortcodes'] );
	}


	/**
	 * oEmbeds extraction.
	 */

	public function test_extract_oembeds_from_content() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::EMBEDS );

		$this->assertArrayHasKey( 'embeds', $media );
		$this->assertSame( 'https://www.youtube.com/watch?v=2mjvfnUAfyo', $media['embeds'][0]['url'] );
		$this->assertSame( 'http://www.youtube.com/watch?v=KaOC9danxNo',  $media['embeds'][1]['url'] );
	}


	/**
	 * Images extraction (src tags).
	 */

	// both quote styles
	public function test_extract_images_from_content_with_src_tags() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::IMAGES );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'html' ) ) );
	
		$this->assertSame( 'http://example.com/image.gif',           $media[0]['url'] );
		$this->assertSame( 'http://example.com/image-in-a-link.gif', $media[1]['url'] );
	}

	// empty src attributes, data: URIs
	public function test_extract_no_images_from_content_with_invalid_src_tags() {
		$richtext = 'This sample text will contain images with invalid src tags, like this:
		<img src="data://abcd"> or <img src="phone://0123" />.
		';

		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::IMAGES );

		$this->assertArrayHasKey( 'images', $media );
		$this->assertSame( 0, $media['has']['images'] );
	}


	/**
	 * Images extraction (galleries).
	 */

	public function test_extract_images_from_content_with_galleries_variant_no_ids() {
		// To test the [gallery] shortcode, we need to create a post and an attachment.
		$post_id       = $this->factory->post->create( array( 'post_content' => self::$richtext ) );
		$attachment_id = $this->factory->attachment->create_object( 'image.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );
		wp_update_attachment_metadata( $attachment_id, array( 'width' => 100, 'height' => 100 ) );


		// Extract the gallery images.
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'galleries' ) ) );
		$this->assertCount( 1, $media );

		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/image.jpg', $media[0]['url'] );
	}

	public function test_extract_images_from_content_with_galleries_variant_ids() {
		// To test the [gallery] shortcode, we need to create a post and attachments.
		$attachment_ids = array();
		foreach ( range( 1, 3 ) as $i ) {
			$attachment_id = $this->factory->attachment->create_object( "image{$i}.jpg", 0, array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment'
			) );

			wp_update_attachment_metadata( $attachment_id, array( 'width' => 100, 'height' => 100 ) );
			$attachment_ids[] = $attachment_id;
		}

		$attachment_ids = join( ',', $attachment_ids );
		$post_id        = $this->factory->post->create( array( 'post_content' => "[gallery ids='{$attachment_ids}']" ) );


		// Extract the gallery images.
		$media = self::$media_extractor->extract( '', BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'galleries' ) ) );
		$this->assertCount( 3, $media );

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->assertSame( 'http://' . WP_TESTS_DOMAIN . "/wp-content/uploads/image{$i}.jpg", $media[ $i - 1 ]['url'] );
		}
	}

	public function test_extract_no_images_from_content_with_invalid_galleries_variant_no_ids() {
		$post_id = $this->factory->post->create( array( 'post_content' => self::$richtext ) );
		$media   = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'galleries' ) ) );
		$this->assertCount( 0, $media );
	}

	public function test_extract_no_images_from_content_with_invalid_galleries_variant_ids() {
		$post_id = $this->factory->post->create( array( 'post_content' => '[gallery ids="117,4529"]' ) );
		$media   = self::$media_extractor->extract( '', BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'galleries' ) ) );
		$this->assertCount( 0, $media );
	}


	/**
	 * Images extraction (thumbnail).
	 */

	public function test_extract_no_images_from_content_with_featured_image() {
		$post_id      = $this->factory->post->create( array( 'post_content' => self::$richtext ) );
		$thumbnail_id = $this->factory->attachment->create_object( 'image.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment'
		) );
		set_post_thumbnail( $post_id, $thumbnail_id );


		// Extract the gallery images.
		$media = self::$media_extractor->extract( '', BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'featured_images' ) ) );
		$this->assertCount( 1, $media );

		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/image.jpg', $media[0]['url'] );
	}

	public function test_extract_images_from_content_without_featured_image() {
		$post_id = $this->factory->post->create( array( 'post_content' => self::$richtext ) );
		$media   = self::$media_extractor->extract( '', BP_Media_Extractor::IMAGES, array(
			'post' => get_post( $post_id ),
		) );

		$this->assertArrayHasKey( 'images', $media );
		$media = array_values( wp_list_filter( $media['images'], array( 'source' => 'featured_images' ) ) );
		$this->assertCount( 0, $media );
	}


	/**
	 * Audio extraction.
	 */

	public function test_extract_audio_from_content() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::AUDIO );

		$this->assertArrayHasKey( 'audio', $media );
		$this->assertCount( 3, $media['audio'] );

		$this->assertSame( 'shortcodes', $media['audio'][0]['source'] );
		$this->assertSame( 'shortcodes', $media['audio'][1]['source'] );
		$this->assertSame( 'html',       $media['audio'][2]['source'] );

		$this->assertSame( 'http://example.com/source.mp3',     $media['audio'][0]['url'] );
		$this->assertSame( 'http://example.com/source.wav',     $media['audio'][1]['url'] );
		$this->assertSame( 'http://example.com/more_audio.mp3', $media['audio'][2]['url'] );
	}

	public function test_extract_audio_shortcode_with_no_src_param() {
		$richtext = '[audio http://example.com/a-song.mp3]';
		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::AUDIO );

		$this->assertArrayHasKey( 'audio', $media );
		$this->assertCount( 1, $media['audio'] );
		$this->assertSame( 'http://example.com/a-song.mp3', $media['audio'][0]['url'] );
	}

	public function test_extract_no_audio_from_invalid_content() {
		$richtext = '[audio src="http://example.com/not_audio.gif"]
		<a href="http://example.com/more_not_audio.mp33">Hello</a>.';

		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::AUDIO );
		$this->assertSame( 0, $media['has']['audio'] );
	}

	public function test_extract_no_audio_from_empty_audio_shortcode() {
		$media = self::$media_extractor->extract( '[audio]', BP_Media_Extractor::AUDIO );
		$this->assertSame( 0, $media['has']['audio'] );
	}


	/**
	 * Video extraction.
	 */

	public function test_extract_video_from_content() {
		$media = self::$media_extractor->extract( self::$richtext, BP_Media_Extractor::VIDEOS );

		$this->assertArrayHasKey( 'videos', $media );
		$this->assertCount( 2, $media['videos'] );

		$this->assertSame( 'shortcodes', $media['videos'][0]['source'] );
		$this->assertSame( 'shortcodes', $media['videos'][1]['source'] );

		$this->assertSame( 'http://example.com/source.ogv',  $media['videos'][0]['url'] );
		$this->assertSame( 'http://example.com/source.webm', $media['videos'][1]['url'] );
	}


	public function test_extract_video_shortcode_with_no_src_param() {
		$richtext = '[video http://example.com/source.ogv]';
		$media = self::$media_extractor->extract( $richtext, BP_Media_Extractor::VIDEOS );

		$this->assertArrayHasKey( 'videos', $media );
		$this->assertCount( 1, $media['videos'] );
		$this->assertSame( 'http://example.com/source.ogv', $media['videos'][0]['url'] );
	}

	public function test_extract_no_video_from_invalid_content() {
		$richtext = '[video src="http://example.com/not_video.mp3"]';
		$media    = self::$media_extractor->extract( $richtext, BP_Media_Extractor::VIDEOS );

		$this->assertSame( 0, $media['has']['videos'] );
	}

	public function test_extract_no_videos_from_empty_video_shortcodes() {
		$media = self::$media_extractor->extract( '[video]', BP_Media_Extractor::VIDEOS );
		$this->assertSame( 0, $media['has']['videos'] );
	}
}
