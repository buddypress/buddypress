<?php

/**
 * @group settings
 */
class BP_Tests_Settings_BpSettingsSanitizeNotificationSettings extends BP_UnitTestCase {
	protected $core_notification_settings = array(
		'notification_messages_new_message',
		'notification_activity_new_mention',
		'notification_activity_new_reply',
		'notification_groups_invite',
		'notification_groups_group_updated',
		'notification_groups_admin_promotion',
		'notification_groups_membership_request',
		'notification_friends_friendship_request',
		'notification_friends_friendship_accepted',
	);

	public function test_should_ignore_setting_not_added_via_bp_notification_settings_callback() {
		$settings = array(
			'foo' => 'yes',
		);

		$sanitized = bp_settings_sanitize_notification_settings( $settings );

		$this->assertArrayNotHasKey( 'foo', $sanitized );
	}

	public function test_should_accept_setting_added_via_bp_notification_settings_callback() {
		$settings = array(
			'foo' => 'yes',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertArrayHasKey( 'foo', $sanitized );
		$this->assertSame( 'yes', $sanitized['foo'] );
	}

	public function test_should_sanitize_invalid_values_to_no_for_core_settings() {

		$settings = array();
		foreach ( $this->core_notification_settings as $key ) {
			$settings[ $key ] = 'foo';
		}

		add_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );

		$expected = array();
		foreach ( $this->core_notification_settings as $key ) {
			$expected[ $key ] = 'no';
		}

		$this->assertEqualSets( $expected, $sanitized );
	}

	public function test_should_not_sanitize_values_for_custom_setting() {
		$settings = array(
			'foo' => 'bar',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertArrayHasKey( 'foo', $sanitized );
		$this->assertSame( 'bar', $sanitized['foo'] );
	}

	public function add_custom_notification_setting() {
		echo '<input name="notifications[foo]" value="yes" />';
	}

	public function add_core_notification_settings() {
		foreach ( $this->core_notification_settings as $key ) {
			echo '<input name="notifications[' . $key . ']" value="yes" />';
		}
	}
}
