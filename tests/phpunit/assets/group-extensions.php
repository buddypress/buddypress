<?php

/**
 * The following implementations of BP_Group_Extension act as dummy plugins
 * for our unit tests
 */

class BPTest_Group_Extension_Parse_Legacy_Properties extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );
		$this->name = $class_name;
		$this->slug = sanitize_title( $class_name );
		$this->admin_name = $this->name . ' Edit';
		$this->admin_slug = $this->slug . '-edit';
		$this->create_name = $this->name . ' Create';
		$this->create_slug = $this->slug . '-create';
		$this->visibility = 'private';
		$this->create_step_position = 58;
		$this->nav_item_position = 63;
		$this->admin_metabox_context = 'high';
		$this->admin_metabox_priority = 'side';
		$this->enable_create_step = false;
		$this->enable_nav_item = true;
		$this->enable_edit_item = false;
		$this->enable_admin_item = true;
		$this->nav_item_name = $this->name . ' Nav';
		$this->display_hook = 'foo_hook';
		$this->template_file = 'foo_template';
	}

	/**
	 * Provides access to protected method unneeded in BP
	 */
	function _parse_legacy_properties() {
		return $this->parse_legacy_properties();
	}

	/**
	 * Provides access to protected property unneeded in BP
	 */
	function _get_legacy_properties_converted() {
		return $this->legacy_properties_converted;
	}
}

class BPTest_Group_Extension_Setup_Screens_Use_Global_Fallbacks extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );
		$this->slug = sanitize_title( $class_name );
		$this->name = $class_name;
	}

	/**
	 * Provides access to protected method unneeded in BP
	 */
	function _get_default_screens() {
		return $this->get_default_screens();
	}

	/**
	 * Provides access to protected method unneeded in BP
	 */
	function _setup_class_info() {
		return $this->setup_class_info();
	}

	function settings_screen( $group_id = null ) {}
	function settings_screen_save( $group_id = null ) {}
}

class BPTest_Group_Extension_Setup_Screens_Define_Edit_Screens_Locally extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );
		$this->slug = sanitize_title( $class_name );
		$this->name = $class_name;
	}

	function edit_screen( $group_id = null ) {}
	function edit_screen_save( $group_id = null ) {}
	function settings_screen( $group_id = null ) {}
	function settings_screen_save( $group_id = null ) {}

	/**
	 * Provides access to protected method unneeded in BP
	 */
	function _get_default_screens() {
		return $this->get_default_screens();
	}

	/**
	 * Provides access to protected method unneeded in BP
	 */
	function _setup_class_info() {
		return $this->setup_class_info();
	}

}

class BPTest_Group_Extension_Access_Root_Property extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'slug' => sanitize_title( $class_name ),
			'name' => $class_name,
			'nav_item_position' => 39,
		);

		parent::init( $args );
	}
}

class BPTest_Group_Extension_Access_Init_Property_Using_Legacy_Location extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'slug' => sanitize_title( $class_name ),
			'name' => $class_name,
			'screens' => array(
				'create' => array(
					'position' => 18,
				),
			),
		);

		parent::init( $args );
	}
}

class BPTest_Group_Extension_Get_Screen_Callback_Fallbacks extends BP_Group_Extension {
	function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'slug' => sanitize_title( $class_name ),
			'name' => $class_name,
		);

		parent::init( $args );
	}

	function settings_screen( $group_id = null ) {}
	function settings_screen_save( $group_id = null ) {}

	function edit_screen( $group_id = null ) {}
	function edit_screen_save( $group_id = null ) {}
}

class BPTest_Group_Extension_Enable_Nav_Item_True extends BP_Group_Extension {
	public function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'name' => $class_name,
			'slug' => sanitize_title( $class_name ),
			'enable_nav_item' => true,
		);

		parent::init( $args );
	}
}

class BPTest_Group_Extension_Enable_Nav_Item_False extends BP_Group_Extension {
	public function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'name' => $class_name,
			'slug' => sanitize_title( $class_name ),
			'enable_nav_item' => false,
		);

		parent::init( $args );
	}
}

class BPTest_Group_Extension_Visibility_Private extends BP_Group_Extension {
	public function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'name' => $class_name,
			'slug' => sanitize_title( $class_name ),
			'visibility' => 'private',
		);

		parent::init( $args );
	}
}

class BPTest_Group_Extension_Visibility_Public extends BP_Group_Extension {
	public function __construct() {
		$class_name = get_class( $this );

		$args = array(
			'name' => $class_name,
			'slug' => sanitize_title( $class_name ),
			'visibility' => 'public',
		);

		parent::init( $args );
	}
}
