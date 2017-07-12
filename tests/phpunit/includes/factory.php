<?php
class BP_UnitTest_Factory extends WP_UnitTest_Factory {
	public $activity = null;

	function __construct() {
		parent::__construct();

		$this->user = new BP_UnitTest_Factory_For_User( $this );
		$this->activity = new BP_UnitTest_Factory_For_Activity( $this );
		$this->group = new BP_UnitTest_Factory_For_Group( $this );
		$this->message = new BP_UnitTest_Factory_For_Message( $this );
		$this->xprofile_group = new BP_UnitTest_Factory_For_XProfileGroup( $this );
		$this->xprofile_field = new BP_UnitTest_Factory_For_XProfileField( $this );
		$this->notification = new BP_UnitTest_Factory_For_Notification( $this );
		$this->signup = new BP_UnitTest_Factory_For_Signup( $this );
		$this->friendship = new BP_UnitTest_Factory_For_Friendship( $this );
	}
}

class BP_UnitTest_Factory_For_User extends WP_UnitTest_Factory_For_User {
	/**
	 * When creating a new user, it's almost always necessary to have the
	 * last_activity usermeta set right away, so that the user shows up in
	 * directory queries. This is a shorthand wrapper for the user factory
	 * create() method.
	 *
	 * Also set a display name
	 */
	public function create_object( $args ) {
		$r = wp_parse_args( $args, array(
			'role' => 'subscriber',
			'last_activity' => date( 'Y-m-d H:i:s', strtotime( bp_core_current_time() ) - 60*60*24*365 ),
		) );

		$last_activity = $r['last_activity'];
		unset( $r['last_activity'] );

		$user_id = wp_insert_user( $r );

		bp_update_user_last_activity( $user_id, $last_activity );

		if ( bp_is_active( 'xprofile' ) ) {
			$user = new WP_User( $user_id );
			xprofile_set_field_data( 1, $user_id, $user->display_name );
		}

		return $user_id;
	}
}

class BP_UnitTest_Factory_For_Activity extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'component'    => buddypress()->activity->id,
			'content'      => new WP_UnitTest_Generator_Sequence( 'Activity content %s' ),
			'primary_link' => 'http://example.com',
			'type'         => 'activity_update',
			'recorded_time' => bp_core_current_time(),
		);
	}

	function create_object( $args ) {
		if ( ! isset( $args['user_id'] ) )
			$args['user_id'] = get_current_user_id();

		return bp_activity_add( $args );
	}

	function update_object( $activity_id, $fields ) {
		$activity = new BP_Activity_Activity( $activity_id );

		foreach ( $fields as $field_name => $value ) {
			if ( isset( $activity->$field_name ) )
				$activity->$field_name = $value;
		}

		$activity->save();
		return $activity;
	}

	function get_object_by_id( $user_id ) {
		return new BP_Activity_Activity( $user_id );
	}
}

class BP_UnitTest_Factory_For_Group extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'name'         => new WP_UnitTest_Generator_Sequence( 'Group %s' ),
			'description'  => new WP_UnitTest_Generator_Sequence( 'Group description %s' ),
			'slug'         => new WP_UnitTest_Generator_Sequence( 'group-slug-%s' ),
			'status'       => 'public',
			'enable_forum' => true,
			'date_created' => bp_core_current_time(),
		);
	}

	function create_object( $args ) {
		if ( ! isset( $args['creator_id'] ) ) {
			if ( is_user_logged_in() ) {
				$args['creator_id'] = get_current_user_id();

			// Create a user. This is based on from BP_UnitTestCase->create_user().
			} else {
				$last_activity      = date( 'Y-m-d H:i:s', strtotime( bp_core_current_time() ) - 60 * 60 * 24 * 365 );
				$user_factory       = new WP_UnitTest_Factory_For_User();
				$args['creator_id'] = $this->factory->user->create( array( 'role' => 'subscriber' ) );

				bp_update_user_last_activity( $args['creator_id'] , $last_activity );

				if ( bp_is_active( 'xprofile' ) ) {
					$user = new WP_User( $args['creator_id']  );
					xprofile_set_field_data( 1, $args['creator_id'] , $user->display_name );
				}
			}
		}

		$group_id = groups_create_group( $args );
		if ( ! $group_id ) {
			return false;
		}

		groups_update_groupmeta( $group_id, 'total_member_count', 1 );
		$last_activity = isset( $args['last_activity'] ) ? $args['last_activity'] : bp_core_current_time();
		groups_update_groupmeta( $group_id, 'last_activity', $last_activity );

		return $group_id;
	}

	function update_object( $group_id, $fields ) {
		$group = new BP_Groups_Group( $group_id );

		foreach ( $fields as $field_name => $value ) {
			if ( isset( $group->field_name ) )
				$group->field_name = $value;
		}

		$group->save();
		return $group;
	}

	function get_object_by_id( $group_id ) {
		return new BP_Groups_Group( $group_id );
	}
}

class BP_UnitTest_Factory_For_Message extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'sender_id'  => get_current_user_id(),
			'thread_id'  => 0,
			'subject'    => new WP_UnitTest_Generator_Sequence( 'Message subject %s' ),
			'content'    => new WP_UnitTest_Generator_Sequence( 'Message content %s' ),
			'date_sent'  => bp_core_current_time(),
		);
	}

	function create_object( $args ) {
		if ( empty( $args['sender_id'] ) ) {
			$args['sender_id'] = $this->factory->user->create();
		}

		if ( empty( $args['recipients'] ) ) {
			$recipient = $this->factory->user->create_and_get();
			$args['recipients'] = array( $recipient->user_nicename );
		}

		$thread_id = messages_new_message( $args );
		$thread = new BP_Messages_Thread( $thread_id );
		return end( $thread->messages )->id;
	}

	function update_object( $message_id, $fields ) {
		// todo
	}

	function get_object_by_id( $message_id ) {
		return new BP_Messages_Message( $message_id );
	}
}

class BP_UnitTest_Factory_For_XProfileGroup extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'name'         => new WP_UnitTest_Generator_Sequence( 'XProfile group %s' ),
			'description'  => new WP_UnitTest_Generator_Sequence( 'XProfile group description %s' ),
			'slug'         => new WP_UnitTest_Generator_Sequence( 'xprofile-group-slug-%s' ),
		);
	}

	function create_object( $args ) {
		$group_id = xprofile_insert_field_group( $args );
		return $group_id;
	}

	function update_object( $group_id, $fields ) {
	}

	function get_object_by_id( $group_id ) {
		return new BP_XProfile_Group( $group_id );
	}
}

class BP_UnitTest_Factory_For_XProfileField extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'name'        => new WP_UnitTest_Generator_Sequence( 'XProfile field %s' ),
			'description' => new WP_UnitTest_Generator_Sequence( 'XProfile field description %s' ),
			'type'        => 'textbox',
		);
	}

	function create_object( $args ) {
		$field_id = xprofile_insert_field( $args );
		return $field_id;
	}

	function update_object( $field_id, $fields ) {
	}

	function get_object_by_id( $field_id ) {
		return new BP_XProfile_Field( $field_id );
	}
}

class BP_UnitTest_Factory_For_Notification extends WP_UnitTest_Factory_For_Thing {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	public function create_object( $args ) {
		return bp_notifications_add_notification( $args );
	}

	public function update_object( $id, $fields ) {}

	public function get_object_by_id( $id ) {
		return new BP_Notifications_Notification( $id );
	}
}

class BP_UnitTest_Factory_For_Signup extends WP_UnitTest_Factory_For_Thing {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	public function create_object( $args ) {
		return BP_Signup::add( $args );
	}

	public function update_object( $id, $fields ) {}

	public function get_object_by_id( $id ) {
		return new BP_Signup( $id );
	}
}

/**
 * Factory for friendships.
 *
 * @since 2.7.0
 */
class BP_UnitTest_Factory_For_Friendship extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Constructor.
	 *
	 * @since 2.7.0
	 *
	 * @param $factory WP_UnitTest_Factory
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	/**
	 * Create friendship object.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Array of arguments.
	 * @return int Friendship ID.
	 */
	public function create_object( $args ) {
		$friendship = new BP_Friends_Friendship();

		foreach ( array( 'initiator_user_id', 'friend_user_id' ) as $arg ) {
			if ( isset( $args[ $arg ] ) ) {
				$friendship->$arg = $args[ $arg ];
			} else {
				$friendship->$arg = $this->factory->user->create();
			}
		}

		foreach ( array( 'is_confirmed', 'is_limited', 'date_created' ) as $arg ) {
			if ( isset( $args[ $arg ] ) ) {
				$friendship->$arg = $args[ $arg ];
			}
		}

		$friendship->save();

		return $friendship->id;
	}

	/**
	 * Update a friendship object.
	 *
	 * @since 2.7.0
	 *
	 * @todo Implement.
	 *
	 * @param int   $id     ID of the friendship.
	 * @param array $fields Fields to update.
	 */
	public function update_object( $id, $fields ) {}

	/**
	 * Get a friendship object by its ID.
	 *
	 * @since 2.7.0
	 *
	 * @param int $id
	 * @return BP_Friends_Friendship
	 */
	public function get_object_by_id( $id ) {
		return new BP_Friends_Friendship( $id );
	}
}
