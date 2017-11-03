<?php
/**
 * @group core
 * @group BP_Email_Recipient
 */
class BP_Email_Recipient_Tests extends BP_UnitTestCase {
	protected $u1;

	public function setUp() {
		parent::setUp();

		$this->u1 = self::factory()->user->create( array(
			'display_name' => 'Unit Test',
			'user_email'   => 'test@example.com',
		) );
	}

	public function test_return_with_address_and_name() {
		$email     = 'test@example.com';
		$name      = 'Unit Test';
		$recipient = new BP_Email_Recipient( $email, $name );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertSame( $name, $recipient->get_name() );
	}

	public function test_return_with_array() {
		$email     = 'test@example.com';
		$name      = 'Unit Test';
		$recipient = new BP_Email_Recipient( array( $email => $name ) );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertSame( $name, $recipient->get_name() );
	}

	public function test_return_with_user_id() {
		$recipient = new BP_Email_Recipient( $this->u1 );

		$this->assertSame( 'test@example.com', $recipient->get_address() );
		$this->assertSame( 'Unit Test', $recipient->get_name() );
	}

	public function test_return_with_wp_user_object() {
		$recipient = new BP_Email_Recipient( get_user_by( 'id', $this->u1 ) );

		$this->assertSame( 'test@example.com', $recipient->get_address() );
		$this->assertSame( 'Unit Test', $recipient->get_name() );
	}

	public function test_return_with_known_address_and_optional_name() {
		$email     = 'test@example.com';
		$name      = 'Custom';
		$recipient = new BP_Email_Recipient( $email, $name );

		$this->assertSame( 'test@example.com', $recipient->get_address() );
		$this->assertSame( 'Custom', $recipient->get_name() );
	}

	public function test_return_with_known_address_and_empty_name() {
		$email     = 'test@example.com';
		$recipient = new BP_Email_Recipient( $email );

		$this->assertSame( 'test@example.com', $recipient->get_address() );

		// Should fallback to WP user name.
		$this->assertSame( 'Unit Test', $recipient->get_name() );
	}

	public function test_return_with_unknown_address_and_optional_name() {
		$email     = 'unknown@example.com';
		$name      = 'Custom';
		$recipient = new BP_Email_Recipient( $email, $name );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertSame( $name, $recipient->get_name() );
	}

	public function test_return_with_unknown_address_and_empty_name() {
		$email     = 'unknown@example.com';
		$recipient = new BP_Email_Recipient( $email );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertEmpty( $recipient->get_name() );
	}

	public function test_return_with_unknown_array_and_optional_name() {
		$email     = 'unknown@example.com';
		$name      = 'Custom';
		$recipient = new BP_Email_Recipient( array( $email => $name ) );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertSame( $name, $recipient->get_name() );
	}

	public function test_return_with_unknown_array_and_empty_name() {
		$email     = 'unknown@example.com';
		$recipient = new BP_Email_Recipient( array( $email ) );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertEmpty( $recipient->get_name() );
	}

	public function test_return_with_known_array_and_optional_name() {
		$email     = 'test@example.com';
		$name      = 'Custom';
		$recipient = new BP_Email_Recipient( array( $email => $name ) );

		$this->assertSame( $email, $recipient->get_address() );
		$this->assertSame( $name, $recipient->get_name() );
	}

	public function test_return_with_known_array_and_empty_name() {
		$email     = 'test@example.com';
		$recipient = new BP_Email_Recipient( array( $email ) );

		$this->assertSame( $email, $recipient->get_address() );

		// Should fallback to WP user name.
		$this->assertSame( 'Unit Test', $recipient->get_name() );
	}

	public function test_should_return_empty_string_if_user_id_id_invalid() {
		$recipient = new BP_Email_Recipient( time() );

		$this->assertEmpty( $recipient->get_address() );
		$this->assertEmpty( $recipient->get_name() );
	}

	public function test_get_wp_user_object_from_email_address() {
		$recipient = new BP_Email_Recipient( 'test@example.com' );
		$recipient = $recipient->get_user( 'search-email' );

		$this->assertSame( $this->u1, $recipient->ID );
		$this->assertSame( 'test@example.com', $recipient->user_email );
	}
}
