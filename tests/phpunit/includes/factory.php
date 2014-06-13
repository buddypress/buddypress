<?php
class BP_UnitTest_Factory extends WP_UnitTest_Factory {
	public $activity = null;

	function __construct() {
		parent::__construct();

		$this->activity = new BP_UnitTest_Factory_For_Activity( $this );
		$this->group = new BP_UnitTest_Factory_For_Group( $this );
		$this->message = new BP_UnitTest_Factory_For_Message( $this );
		$this->xprofile_group = new BP_UnitTest_Factory_For_XProfileGroup( $this );
		$this->xprofile_field = new BP_UnitTest_Factory_For_XProfileField( $this );
		$this->notification = new BP_UnitTest_Factory_For_Notification( $this );
		$this->signup = new BP_UnitTest_Factory_For_Signup( $this );
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
			$args['creator_id'] = get_current_user_id();
		}

		$group_id = groups_create_group( $args );

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
			'recipients' => array(),
			'subject'    => new WP_UnitTest_Generator_Sequence( 'Message subject %s' ),
			'content'    => new WP_UnitTest_Generator_Sequence( 'Message content %s' ),
			'date_sent'  => bp_core_current_time(),
		);
	}

	function create_object( $args ) {
		$message_id = messages_new_message( $args );
		return $message_id;
	}

	function update_object( $group_id, $fields ) {
		// todo
	}

	function get_object_by_id( $group_id ) {
		// todo
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
			'name'         => new WP_UnitTest_Generator_Sequence( 'XProfile field %s' ),
			'description'  => new WP_UnitTest_Generator_Sequence( 'XProfile field description %s' ),
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
