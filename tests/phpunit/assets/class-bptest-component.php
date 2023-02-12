<?php

// Testing Component Class.
class BPTest_Component extends BP_Component {
	/**
	 * Globals to test.
	 *
	 * @var array
	 */
	public $globals = array();

	// Start the `test` component setup process.
	public function __construct( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'id'      => 'example',
				'name'    => 'Example Component',
				'globals' => array(
					'slug' => 'example',
				),
			)
		);

		$this->globals = $r['globals'];

		parent::start(
			$r['id'],
			$r['name']
		);
	}

	// Setup Test Globals.
	public function setup_globals( $args = array() ) {
		parent::setup_globals( $this->globals );
	}

	public function add_rewrite_tags( $rewrite_tags = array() ) {
		parent::add_rewrite_tags( $rewrite_tags );
	}

	public function add_rewrite_rules( $rewrite_rules = array() ) {
		parent::add_rewrite_rules( $rewrite_rules );
	}

	public function add_permastructs( $permastructs = array() ) {
		parent::add_permastructs( $permastructs );
	}
}
