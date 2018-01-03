<?php
/**
 * @group core
 * @group BP_Email
 */
class BP_Tests_Email extends BP_UnitTestCase_Emails {
	protected $u1;
	protected $u2;

	public function setUp() {
		parent::setUp();

		$this->u1 = self::factory()->user->create( array(
			'display_name' => 'Unit Test',
			'user_email'   => 'test1@example.com',
		) );

		$this->u2 = self::factory()->user->create( array(
			'display_name' => 'Unit Test2',
			'user_email'   => 'test2@example.com',
		) );
	}

	public function test_valid_subject() {
		$message = 'test';
		$email   = new BP_Email( 'activity-at-message' );

		$email->set_subject( $message )->set_tokens( array( 'poster.name' => 'example' ) );
		$this->assertSame( $message, $email->get_subject() );
	}

	public function test_valid_html_content() {
		$message = '<b>test</b>';
		$email   = new BP_Email( 'activity-at-message' );

		$email->set_content_html( $message );
		$email->set_content_type( 'html' );

		$this->assertSame( $message, $email->get_content() );
	}

	public function test_valid_plaintext_content() {
		$message = 'test';
		$email   = new BP_Email( 'activity-at-message' );

		$email->set_content_plaintext( $message );
		$email->set_content_type( 'plaintext' );

		$this->assertSame( $message, $email->get_content() );
	}

	public function test_valid_template() {
		$message = 'test';
		$email   = new BP_Email( 'activity-at-message' );

		$email->set_template( $message );
		$this->assertSame( $message, $email->get_template() );
	}

	public function test_tokens() {
		$email          = new BP_Email( 'activity-at-message' );
		$default_tokens = $email->get_tokens();
		$tokens         = array( 'test1' => 'hello', 'test2' => 'world' );

		$email->set_tokens( $tokens );

		$this->assertSame(
			array_keys( $tokens + $default_tokens ),
			array_keys( $email->get_tokens() )
		);

		$this->assertSame(
			array_values( $tokens + $default_tokens ),
			array_values( $email->get_tokens() )
		);
	}

	public function test_headers() {
		$email           = new BP_Email( 'activity-at-message' );
		$default_headers = $email->get_headers();
		$headers         = array( 'custom_header' => 'custom_value' );

		$email->set_headers( $headers );

		$this->assertSame( $headers + $default_headers, $email->get_headers() );
	}

	public function test_validation() {
		$email = new BP_Email( 'activity-at-message' );
		$email->set_from( 'test1@example.com' )->set_to( 'test2@example.com' )->set_subject( 'testing' );
		$email->set_content_html( 'testing' )->set_tokens( array( 'poster.name' => 'example' ) );

		$this->assertTrue( $email->validate() );
	}

	public function test_invalid_characters_are_stripped_from_tokens() {
		$email          = new BP_Email( 'activity-at-message' );
		$default_tokens = $email->get_tokens();

		$email->set_tokens( array( 'te{st}1' => 'hello world' ) );

		$this->assertSame(
			array_keys( array( 'test1' => 'hello world' ) + $default_tokens ),
			array_keys( $email->get_tokens() )
		);
	}

	public function test_token_are_escaped() {
		$token = '<blink>';
		$email = new BP_Email( 'activity-at-message' );
		$email->set_content_html( '{{test}}' )->set_tokens( array( 'test' => $token ) );

		$this->assertSame(
			esc_html( $token ),
			$email->get_content( 'replace-tokens' )
		);
	}

	public function test_token_are_not_escaped() {
		$token = '<blink>';
		$email = new BP_Email( 'activity-at-message' );
		$email->set_content_html( '{{{test}}}' )->set_tokens( array( 'test' => $token ) );

		$this->assertSame(
			$token,
			$email->get_content( 'replace-tokens' )
		);
	}

	public function test_invalid_headers() {
		$email           = new BP_Email( 'activity-at-message' );
		$default_headers = $email->get_headers();
		$headers         = array( 'custom:header' => 'custom:value' );

		$email->set_headers( $headers );

		$this->assertNotSame( $headers + $default_headers, $email->get( 'headers' ) );
		$this->assertSame( array( 'customheader' => 'customvalue' ) + $default_headers, $email->get( 'headers' ) );
	}

	public function test_validation_with_missing_required_data() {
		$email  = new BP_Email( 'activity-at-message' );
		$email->set_from( 'test1@example.com' )->set_to( 'test2@example.com' )->set_subject( 'testing' );  // Content
		$email->set_tokens( array( 'poster.name' => 'example' ) );
		$result = $email->validate();

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'missing_parameter', $result->get_error_code() );
	}

	public function test_validation_with_missing_template() {
		$email  = new BP_Email( 'activity-at-message' );
		$email->set_from( 'test1@example.com' )->set_to( 'test2@example.com' )->set_subject( 'testing' );
		$email->set_content_html( 'testing' )->set_tokens( array( 'poster.name' => 'example' ) )->set_template( '' );
		$result = $email->validate();

		// Template has a default value, but it can't be blank.
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'missing_parameter', $result->get_error_code() );
	}

	public function test_invalid_tags_should_be_removed_from_html_content() {
		$message = '<b>hello world</b><iframe src="https://example.com"></iframe><b>hello world</b>';
		$email   = new BP_Email( 'activity-at-message' );

		$email->set_content_html( $message );
		$email->set_content_type( 'html' );

		$this->assertSame( '<b>hello world</b><b>hello world</b>', $email->get_content() );
	}

	public function test_multiple_recipients_are_supported_by_address() {
		$email1 = 'test1@example.com';
		$email2 = 'test2@example.com';
		$email  = new BP_Email( 'activity-at-message' );

		$email->set_to( array( $email1, $email2 ) );
		$addresses = $email->get_to();

		$this->assertCount( 2, $addresses );
		$this->assertSame( $email1, $addresses[0]->get_address() );
		$this->assertSame( $email2, $addresses[1]->get_address() );
	}

	public function test_multiple_recipients_are_supported_by_wp_user_object() {
		$user1 = get_user_by( 'id', $this->u1 );
		$user2 = get_user_by( 'id', $this->u2 );
		$email = new BP_Email( 'activity-at-message' );

		$email->set_to( array( $user1, $user2 ) );
		$addresses = $email->get_to();

		$this->assertCount( 2, $addresses );
		$this->assertSame( $user1->user_email, $addresses[0]->get_address() );
		$this->assertSame( $user2->user_email, $addresses[1]->get_address() );
	}

	public function test_multiple_recipients_are_supported_by_wp_user_id() {
		$user1 = get_user_by( 'id', $this->u1 );
		$user2 = get_user_by( 'id', $this->u2 );
		$email = new BP_Email( 'activity-at-message' );
		$email->set_to( array( $this->u1, $this->u2 ) );
		$addresses = $email->get_to();

		$this->assertCount( 2, $addresses );
		$this->assertSame( $user1->user_email, $addresses[0]->get_address() );
		$this->assertSame( $user2->user_email, $addresses[1]->get_address() );
	}

	public function test_multiple_recipients_are_supported() {
		$user1 = get_user_by( 'id', $this->u1 );
		$user2 = get_user_by( 'id', $this->u2 );
		$user3 = 'test3@example.com';
		$email = new BP_Email( 'activity-at-message' );

		$email->set_to( array( $user1, $this->u2, $user3 ) );
		$addresses = $email->get_to();

		$this->assertCount( 3, $addresses );
		$this->assertSame( $user1->user_email, $addresses[0]->get_address() );
		$this->assertSame( $user2->user_email, $addresses[1]->get_address() );
		$this->assertSame( $user3,             $addresses[2]->get_address() );
	}

	public function test_replacing_existing_recipients_with_new_recipients() {
		$email              = new BP_Email( 'activity-at-message' );
		$original_recipient = 'test1@example.com';
		$new_recipient      = 'test2@example.com';

		$email->set_to( $original_recipient );
		$addresses = $email->get_to();
		$this->assertSame( $original_recipient, $addresses[0]->get_address() );

		$email->set_to( $new_recipient );
		$addresses = $email->get_to();
		$this->assertSame( $new_recipient, $addresses[0]->get_address() );
	}

	public function test_appending_new_recipients_to_existing_recipients() {
		$email              = new BP_Email( 'activity-at-message' );
		$original_recipient = 'test1@example.com';
		$new_recipient      = 'test2@example.com';

		$email->set_to( $original_recipient );
		$addresses = $email->get_to();
		$this->assertSame( $original_recipient, $addresses[0]->get_address() );

		$email->set_to( $new_recipient, '', 'add' );
		$addresses = $email->get_to();
		$this->assertSame( $original_recipient, $addresses[0]->get_address() );
		$this->assertSame( $new_recipient, $addresses[1]->get_address() );
	}

	public function test_sending_email() {
		require_once( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin-schema.php' );
		bp_core_install_emails();

		$user1  = get_user_by( 'id', $this->u1 );
		$result = bp_send_email( 'activity-comment', $this->u1, array(
			'tokens' => array(
				'comment.id'                => 123,
				'commenter.id'              => $this->u2,
				'usermessage'               => 'hello world',
				'original_activity.user_id' => $this->u1,
				'poster.name'               => 'name',
				'thread.url'                => 'http://example.com',
				'unsubscribe'               => 'http://example.com',
			),
		) );

		$this->assertTrue( $result );
	}

	public function test_html_entities_are_decoded_in_email_subject() {
		// Emulate custom post title for an email post type.
		$subject = "It's pretty <new & magical.";

		$email = new BP_Email( 'activity-at-message' );
		$email->set_subject( $subject )->set_tokens( array( 'poster.name' => 'blah' ) );

		// Subject always has to have tokens replaced before sending.
		$this->assertSame( $subject, $email->get_subject( 'replace-tokens' ) );
	}

	public function test_html_entities_are_decoded_in_email_recipient_names() {
		// Raw display name.
		$name = "Test o'Toole";

		// Emulate rendered {poster.name} token.
		$token = apply_filters( 'bp_core_get_user_displayname', $name );

		$email = new BP_Email( 'activity-at-message' );
		$email->set_subject( '{{poster.name}}' )->set_tokens( array( 'poster.name' => $token ) );

		// Subject always has to have tokens replaced before sending.
		$this->assertSame( $name, $email->get_subject( 'replace-tokens' ) );
	}

}
