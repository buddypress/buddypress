<?php
/**
 * @group routing
 * @see https://buddypress.trac.wordpress.org/ticket/9330
 * @group core
 */
class BP_Tests_Routing_Directory_Homepage extends BP_UnitTestCase {
	/**
	 * @dataProvider content_requests
	 */
	public function test_directory_homepage_preserves_content_requests( $component, $request, $permalinks ) {
		if ( 'blogs' === $component && ! is_multisite() ) {
			$this->markTestSkipped( 'The Sites directory requires multisite.' );
		}

		$pages     = bp_core_get_directory_pages();
		$old_front = get_option( 'show_on_front' );
		$old_page  = get_option( 'page_on_front' );
		$old_links = get_option( 'permalink_structure' );
		$old_user  = get_current_user_id();
		$is_draft  = in_array( $request, array( 'draft post preview', 'draft page preview' ), true );
		$is_page   = in_array( $request, array( 'page', 'draft page preview' ), true );
		$post_args = array( 'post_type' => $is_page ? 'page' : 'post' );
		if ( $is_draft ) {
			$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
			self::set_current_user( $user_id );
			$post_args['post_author'] = $user_id;
			$post_args['post_status'] = 'draft';
		}
		$post_id = self::factory()->post->create( $post_args );
		$args      = array( 'p' => $post_id );

		if ( $is_page ) {
			$args = array( 'page_id' => $post_id );
		} elseif ( 'search' === $request ) {
			$args = array( 's' => 'example' );
		}
		if ( 'preview' === $request || $is_draft ) {
			$args['preview'] = 'true';
		}

		$this->set_permalink_structure( $permalinks );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages->{$component}->id );

		try {
			$this->go_to( add_query_arg( $args, home_url( '/' ) ) );
			$this->assertNotEquals( 1, (int) get_query_var( buddypress()->{$component}->rewrite_ids['directory'] ), 'A content request must not become a BuddyPress directory.' );
			if ( $is_draft ) {
				$this->assertTrue( is_preview() );
				$this->assertFalse( is_404() );
			}
			if ( 'search' === $request ) {
				$this->assertTrue( is_search() );
			} else {
				$this->assertSame( $post_id, get_queried_object_id() );
			}
		} finally {
			self::set_current_user( $old_user );
			update_option( 'show_on_front', $old_front );
			update_option( 'page_on_front', $old_page );
			$this->set_permalink_structure( $old_links );
		}
	}

	public static function content_requests() {
		$cases = array();
		foreach ( array( 'activity', 'groups', 'members', 'blogs' ) as $component ) {
			foreach ( array( 'post', 'page', 'search', 'preview', 'draft post preview', 'draft page preview' ) as $request ) {
				foreach ( array( 'plain' => '', 'pretty' => '/%postname%/' ) as $name => $permalinks ) {
					$cases[ "$component $request $name" ] = array( $component, $request, $permalinks );
				}
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider directory_homepage_urls
	 */
	public function test_directory_as_homepage( $component, $query_string, $permalinks ) {
		if ( 'blogs' === $component && ! is_multisite() ) {
			$this->markTestSkipped( 'The Sites directory requires multisite.' );
		}

		$pages     = bp_core_get_directory_pages();
		$old_front = get_option( 'show_on_front' );
		$old_page  = get_option( 'page_on_front' );
		$old_links = get_option( 'permalink_structure' );

		$this->set_permalink_structure( $permalinks );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages->{$component}->id );

		try {
			$this->go_to( home_url( '/' ) . $query_string );
			$this->assertSame( 1, (int) get_query_var( buddypress()->{$component}->rewrite_ids['directory'] ) );
			$this->assertTrue( bp_is_directory() );
			$this->assertFalse( is_404() );
			$this->assertSame( (int) $pages->{$component}->id, get_queried_object_id() );
		} finally {
			update_option( 'show_on_front', $old_front );
			update_option( 'page_on_front', $old_page );
			$this->set_permalink_structure( $old_links );
		}
	}

	/**
	 * @dataProvider ajax_homepage_referrers
	 */
	public function test_ajax_directory_homepage( $component, $query_string, $permalinks, $expected, $ordinary_homepage = false ) {
		$bp         = buddypress();
		$old_stack  = $bp->canonical_stack;
		$old_front  = get_option( 'show_on_front' );
		$old_page   = get_option( 'page_on_front' );
		$old_links  = get_option( 'permalink_structure' );
		$old_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		$front_page = $ordinary_homepage ? self::factory()->post->create( array( 'post_type' => 'page' ) ) : bp_core_get_directory_pages()->{$component}->id;

		$this->set_permalink_structure( $permalinks );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page );
		set_current_screen( 'dashboard' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		unset( $bp->canonical_stack['requested_url'] );

		try {
			$query   = new WP_Query();
			$referer = wp_parse_url( home_url( '/' ), PHP_URL_PATH ) . $query_string;
			bp_reset_query( $referer, $query );

			$this->assertSame( $expected, $query->is_page( $front_page ) );
			$this->assertSame( (int) $expected, (int) $query->get( $bp->{$component}->rewrite_ids['directory'] ) );
			if ( $ordinary_homepage ) {
				$this->assertSame( 0, (int) $query->get( 'page_id' ) );
			}
			if ( $expected && '?cpage=2' === $query_string ) {
				$this->assertSame( 2, (int) $query->get( 'cpage' ) );
			}
			if ( $expected && in_array( $query_string, array( '?page=2', '?paged=2' ), true ) ) {
				$this->assertSame( 2, (int) $query->get( 'page' ) );
			}
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			$GLOBALS['current_screen'] = $old_screen;
			$bp->canonical_stack = $old_stack;
			update_option( 'show_on_front', $old_front );
			update_option( 'page_on_front', $old_page );
			$this->set_permalink_structure( $old_links );
		}
	}

	public static function ajax_homepage_referrers() {
		$cases = array();
		foreach ( array( 'groups', 'members' ) as $component ) {
			foreach ( array( 'plain' => '', 'pretty' => '/%postname%/' ) as $name => $permalinks ) {
				$cases[ "$component $name bare home" ] = array( $component, '', $permalinks, true );
				$cases[ "$component $name tracking" ]  = array( $component, '?utm_source=newsletter', $permalinks, true );
				$cases[ "$component $name comment page" ] = array( $component, '?cpage=2', $permalinks, true );
				$cases[ "$component $name content page" ] = array( $component, '?page=2', $permalinks, true );
				$cases[ "$component $name paged home" ]  = array( $component, '?paged=2', $permalinks, true );
				$cases[ "$component $name comment page with search" ] = array( $component, '?cpage=2&s=example', $permalinks, false );
				$cases[ "$component $name paged home with search" ] = array( $component, '?paged=2&s=example', $permalinks, false );
				$cases[ "$component $name post" ]      = array( $component, '?p=123', $permalinks, false );
				$cases[ "$component $name page" ]      = array( $component, '?page_id=123', $permalinks, false );
				$cases[ "$component $name search" ]    = array( $component, '?s=example', $permalinks, false );
				$cases[ "$component $name preview" ]   = array( $component, '?preview=true', $permalinks, false );
			}
		}
		$cases['ordinary plain homepage']  = array( 'members', '', '', false, true );
		$cases['ordinary pretty homepage'] = array( 'members', '', '/%postname%/', false, true );
		return $cases;
	}

	public function test_ajax_directory_homepage_preserves_filtered_public_query_var() {
		$bp         = buddypress();
		$old_stack  = $bp->canonical_stack;
		$old_front  = get_option( 'show_on_front' );
		$old_page   = get_option( 'page_on_front' );
		$old_links  = get_option( 'permalink_structure' );
		$old_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		$front_page = bp_core_get_directory_pages()->members->id;

		$this->set_permalink_structure( '' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page );
		set_current_screen( 'dashboard' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'query_vars', array( $this, 'add_ajax_public_query_var' ) );
		unset( $bp->canonical_stack['requested_url'] );

		try {
			$query = new WP_Query();
			bp_reset_query( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) . '?bp_test_route=other', $query );

			$this->assertFalse( $query->is_page( $front_page ) );
			$this->assertSame( 0, (int) $query->get( $bp->members->rewrite_ids['directory'] ) );
			$this->assertSame( 'other', $query->get( 'bp_test_route' ) );
		} finally {
			remove_filter( 'query_vars', array( $this, 'add_ajax_public_query_var' ) );
			remove_filter( 'wp_doing_ajax', '__return_true' );
			$GLOBALS['current_screen'] = $old_screen;
			$bp->canonical_stack = $old_stack;
			update_option( 'show_on_front', $old_front );
			update_option( 'page_on_front', $old_page );
			$this->set_permalink_structure( $old_links );
		}
	}

	public function add_ajax_public_query_var( $query_vars ) {
		$query_vars[] = 'bp_test_route';
		return $query_vars;
	}

	public static function directory_homepage_urls() {
		$cases      = array();
		$components = array( 'activity', 'groups', 'members', 'blogs' );
		$queries    = array(
			'no query'     => '',
			'arbitrary'    => '?anything=value',
			'tracking'     => '?utm_source=newsletter&ref=test',
			'encoded'      => '?anything=hello%20world',
			'content page' => '?page=2',
			'paged home'   => '?paged=2',
		);
		$structures = array(
			'plain'  => '',
			'pretty' => '/%postname%/',
		);

		foreach ( $components as $component ) {
			foreach ( $structures as $structure_name => $permalinks ) {
				foreach ( $queries as $query_name => $query_string ) {
					$label           = "$component / $structure_name / $query_name";
					$cases[ $label ] = array( $component, $query_string, $permalinks );
				}
			}
		}

		return $cases;
	}
}
