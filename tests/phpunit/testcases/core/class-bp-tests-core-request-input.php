<?php
/**
 * Request input tests.
 *
 * @package BuddyPress
 * @subpackage CoreTests
 */

/**
 * Tests request input validation and unslashing.
 *
 * @group core
 * @group BP9065
 */
class BP_Tests_Core_Request_Input extends BP_UnitTestCase {

	const SEARCH_TERM = "O'Reilly \\ Team";

	/**
	 * Original GET data.
	 *
	 * @var array
	 */
	private $get;

	/**
	 * Original POST data.
	 *
	 * @var array
	 */
	private $post;

	/**
	 * Original request data.
	 *
	 * @var array
	 */
	private $request;

	/**
	 * Initial output-buffer level.
	 *
	 * @var int
	 */
	private $buffer_level;

	/**
	 * Original template globals.
	 *
	 * @var array
	 */
	private $template_globals = array();

	/**
	 * Original component query loops.
	 *
	 * @var array
	 */
	private $query_loops = array();

	/**
	 * Set up request and loop state.
	 */
	public function set_up() {
		parent::set_up();

		$this->get          = $_GET;
		$this->post         = $_POST;
		$this->request      = $_REQUEST;
		$this->buffer_level = ob_get_level();

		foreach ( array( 'activities_template', 'blogs_template', 'groups_template', 'members_template', 'messages_template' ) as $global_name ) {
			$this->template_globals[ $global_name ] = array(
				'exists' => array_key_exists( $global_name, $GLOBALS ),
				'value'  => isset( $GLOBALS[ $global_name ] ) ? $GLOBALS[ $global_name ] : null,
			);
		}

		foreach ( array( buddypress()->notifications, buddypress()->members->invitations ) as $component ) {
			$this->query_loops[] = array(
				'component' => $component,
				'exists'    => isset( $component->query_loop ),
				'value'     => isset( $component->query_loop ) ? $component->query_loop : null,
			);
		}
	}

	/**
	 * Restore request and loop state.
	 */
	public function tear_down() {
		while ( ob_get_level() > $this->buffer_level ) {
			ob_end_clean();
		}

		$_GET     = $this->get;
		$_POST    = $this->post;
		$_REQUEST = $this->request;

		foreach ( $this->template_globals as $global_name => $state ) {
			if ( $state['exists'] ) {
				$GLOBALS[ $global_name ] = $state['value'];
			} else {
				unset( $GLOBALS[ $global_name ] );
			}
		}

		foreach ( $this->query_loops as $state ) {
			if ( $state['exists'] ) {
				$state['component']->query_loop = $state['value'];
			} else {
				unset( $state['component']->query_loop );
			}
		}

		parent::tear_down();
	}

	/**
	 * Ensure scalar search values are validated and unslashed.
	 *
	 * @dataProvider search_request_provider
	 *
	 * @param string $loop_function Template loop function.
	 * @param string $hook          Parsed arguments filter.
	 * @param string $request_key   Request key.
	 * @param mixed  $expected      Expected malformed-input fallback.
	 */
	public function test_search_request_values_are_shape_checked_and_unslashed( $loop_function, $hook, $request_key, $expected ) {
		if ( 'bp_has_blogs' === $loop_function && ! is_multisite() ) {
			$this->markTestSkipped( 'Blog queries require multisite.' );
		}

		$_REQUEST = array( $request_key => array( 'invalid' ) );
		$args     = $this->capture_parsed_args( $loop_function, $hook );
		$this->assertSame( $expected, $args['search_terms'] );

		$_REQUEST = array( $request_key => wp_slash( self::SEARCH_TERM ) );
		$args     = $this->capture_parsed_args( $loop_function, $hook );
		$this->assertSame( self::SEARCH_TERM, $args['search_terms'] );
	}

	/**
	 * Data for request search tests.
	 *
	 * @return array[]
	 */
	public function search_request_provider() {
		return array(
			'activity'               => array( 'bp_has_activities', 'bp_after_has_activities_parse_args', 'activity_search', false ),
			'activity-fallback'      => array( 'bp_has_activities', 'bp_after_activity_get_parse_args', 's', false ),
			'blogs'                  => array( 'bp_has_blogs', 'bp_after_has_blogs_parse_args', 'sites_search', false ),
			'blogs-fallback'         => array( 'bp_has_blogs', 'bp_after_has_blogs_parse_args', 's', false ),
			'groups'                 => array( 'bp_has_groups', 'bp_after_has_groups_parse_args', 'groups_search', false ),
			'groups-fallback'        => array( 'bp_has_groups', 'bp_after_has_groups_parse_args', 's', false ),
			'group-members'          => array( 'bp_group_has_members', 'bp_after_group_has_members_parse_args', 'members_search', false ),
			'group-members-fallback' => array( 'bp_group_has_members', 'bp_after_groups_get_group_members_parse_args', 's', false ),
			'members'                => array( 'bp_has_members', 'bp_after_has_members_parse_args', 'members_search', null ),
			'members-fallback'       => array( 'bp_has_members', 'bp_after_core_get_users_parse_args', 's', false ),
			'messages'               => array( 'bp_has_message_threads', 'bp_after_has_message_threads_parse_args', 's', '' ),
			'notifications'          => array( 'bp_has_notifications', 'bp_after_has_notifications_parse_args', 's', '' ),
			'invitations'            => array( 'bp_has_members_invitations', 'bp_after_has_members_invitations_parse_args', 's', '' ),
		);
	}

	/**
	 * Ensure notification types are validated and unslashed.
	 *
	 * @ticket BP9065
	 */
	public function test_notification_type_is_shape_checked_and_unslashed() {
		$_REQUEST = array( 'type' => array( 'invalid' ) );
		$args     = $this->capture_parsed_args( 'bp_has_notifications', 'bp_after_has_notifications_parse_args' );
		$this->assertFalse( $args['component_action'] );

		$_REQUEST = array( 'type' => wp_slash( 'Activity/Comment' ) );
		$args     = $this->capture_parsed_args( 'bp_has_notifications', 'bp_after_has_notifications_parse_args' );
		$this->assertSame( 'activitycomment', $args['component_action'] );
	}

	/**
	 * Ensure hidden search fields ignore arrays and unslash strings.
	 *
	 * @ticket BP9065
	 */
	public function test_hidden_search_fields_ignore_arrays_and_unslash_strings() {
		$functions = array( 'bp_blog_hidden_fields', 'bp_group_hidden_fields', 'bp_member_hidden_fields' );
		$_REQUEST  = array(
			's'              => array( 'invalid' ),
			'blogs_search'   => array( 'invalid' ),
			'groups_search'  => array( 'invalid' ),
			'members_search' => array( 'invalid' ),
			'letter'         => array( 'invalid' ),
		);

		ob_start();
		foreach ( $functions as $function ) {
			call_user_func( $function );
		}
		$this->assertSame( '', ob_get_clean() );

		$_REQUEST = array(
			's'              => wp_slash( self::SEARCH_TERM ),
			'blogs_search'   => wp_slash( self::SEARCH_TERM ),
			'groups_search'  => wp_slash( self::SEARCH_TERM ),
			'members_search' => wp_slash( self::SEARCH_TERM ),
			'letter'         => wp_slash( self::SEARCH_TERM ),
		);

		ob_start();
		foreach ( $functions as $function ) {
			call_user_func( $function );
		}
		$output = ob_get_clean();

		$this->assertStringContainsString( esc_attr( self::SEARCH_TERM ), $output );
		$this->assertStringNotContainsString( esc_attr( wp_slash( self::SEARCH_TERM ) ), $output );
	}

	/**
	 * Ensure list filters discard non-string values.
	 *
	 * @ticket BP9065
	 */
	public function test_group_and_member_type_arrays_discard_non_string_values() {
		$_GET['group_type'] = array( 'foo', array( 'invalid' ), 'bar' );
		$_GET['status']     = array( 'public', array( 'invalid' ), 'private' );
		$group_args         = $this->capture_parsed_args( 'bp_has_groups', 'bp_after_has_groups_parse_args' );

		$this->assertSame(
			array(
				0 => 'foo',
				2 => 'bar',
			),
			$group_args['group_type']
		);

		$this->assertSame(
			array(
				0 => 'public',
				2 => 'private',
			),
			$group_args['status']
		);

		$_GET['member_type'] = array( 'foo', array( 'invalid' ), 'bar' );
		$member_args         = $this->capture_parsed_args( 'bp_has_members', 'bp_after_has_members_parse_args' );

		$this->assertSame(
			array(
				0 => 'foo',
				2 => 'bar',
			),
			$member_args['member_type']
		);
	}

	/**
	 * Ensure xProfile option labels discard non-string values.
	 *
	 * @ticket BP9065
	 */
	public function test_xprofile_option_labels_discard_non_string_values() {
		$field       = new BP_XProfile_Field();
		$field->type = 'selectbox';
		$type        = bp_xprofile_create_field_type( 'selectbox' );

		$_POST['selectbox_option'] = array(
			1 => array( 'invalid' ),
			2 => wp_slash( "O'Reilly" ),
		);

		ob_start();
		$type->admin_new_field_html( $field, 'radio' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'Array', $output );
		$this->assertStringContainsString( 'O&#039;Reilly', $output );
	}

	/**
	 * Capture arguments after BuddyPress has parsed them.
	 *
	 * @param string $loop_function Template loop function.
	 * @param string $hook          Parsed arguments filter.
	 * @return array Parsed arguments.
	 */
	private function capture_parsed_args( $loop_function, $hook ) {
		$args     = null;
		$callback = function ( $parsed_args ) use ( &$args ) {
			$args = $parsed_args;
			return $parsed_args;
		};

		add_filter( $hook, $callback, 999 );

		try {
			call_user_func( $loop_function );
		} finally {
			remove_filter( $hook, $callback, 999 );
		}

		$this->assertTrue( is_array( $args ) );

		return $args;
	}
}
