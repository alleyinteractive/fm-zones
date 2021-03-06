<?php

/**
 * Tests the Fieldmanager Zone Field
 *
 * @group field
 * @group zone
 */
class Test_Fieldmanager_Zone_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );

		$this->data_posts = array(
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-02 00:00:00' ) ),
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-03 00:00:00' ) ),
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-04 00:00:00' ) ),
		);
	}

	public function test_zone_field_markup() {
		$args = array(
			'name'          => 'test_fm_zone',
			'datasource'    => new Fieldmanager_Datasource_Zone_Field(),
			'default_value' => rand_str(),
		);

		$fm = new Fieldmanager_Zone_Field( $args );
		ob_start();
		$fm->add_meta_box( 'Test Zone Field', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]hidden[\'"][^>]+id=[\'"]fieldmanager-' . preg_quote( $args['name'] ) . '-nonce[\'"][^>]+/', $html );
		$this->assertRegExp( '/<div[^>]+class=[\'"]fm\-zone\-posts\-wrapper[\'"][^>]+data\-current=[\'"]\[\][\'"][^>]+data\-limit=[\'"]0[\'"][^>]+data\-placeholders=[\'"]0[\'"]>/', $html );
		$this->assertRegExp( '/<select[^>]+class=[\'"]zone-post-latest[\'"][^>]+/', $html );

		$args['post_limit'] = 2;
		$fm = new Fieldmanager_Zone_Field( $args );
		ob_start();
		$fm->add_meta_box( 'Test Zone Field', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<div[^>]+class=[\'"]fm\-zone\-posts\-wrapper[\'"][^>]+data\-current=[\'"]\[\][\'"][^>]+data\-limit=[\'"]2[\'"][^>]+data\-placeholders=[\'"]0[\'"]>/', $html );
		unset( $args['post_limit'] );
	}

	public function test_legacy_no_datasource() {
		$args = array(
			'name'          => 'test_fm_zone',
			'default_value' => rand_str(),
		);

		$fm = new Fieldmanager_Zone_Field( $args );
		ob_start();
		$fm->add_meta_box( 'Test Zone Field', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]hidden[\'"][^>]+id=[\'"]fieldmanager-' . preg_quote( $args['name'] ) . '-nonce[\'"][^>]+/', $html );
		$this->assertRegExp( '/<div[^>]+class=[\'"]fm\-zone\-posts\-wrapper[\'"][^>]+data\-current=[\'"]\[\][\'"][^>]+data\-limit=[\'"]0[\'"][^>]+data\-placeholders=[\'"]0[\'"]>/', $html );
		$this->assertRegExp( '/<select[^>]+class=[\'"]zone-post-latest[\'"][^>]+/', $html );
	}

	public function test_ajax() {
		$datasource = new Fieldmanager_Datasource_Zone_Field();

		$items = $datasource->get_items_for_ajax( 'test' );
		$items = $items['data'];

		$this->assertSame( 3, count( $items ) );

		// we expect them to be returned in reverse chronological order
		// could have created the samples in that order but this seems more explicit...
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['id'] );
		$this->assertSame( $this->data_posts[1]->ID, $items[1]['id'] );
		$this->assertSame( $this->data_posts[0]->ID, $items[2]['id'] );

		$items = $datasource->get_items_for_ajax( $this->data_posts[1]->ID );
		$items = $items['data'];
		$this->assertSame( $this->data_posts[1]->ID, $items[0]['id'] );

		$items = $datasource->get_items_for_ajax( get_permalink( $this->data_posts[2]->ID ) );
		$items = $items['data'];
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['id'] );
	}

	public function test_ajax_legacy() {
		$fm = new Fieldmanager_Zone_Field();
		$datasource = $fm->datasource;

		$items = $datasource->get_items_for_ajax( 'test' );
		$items = $items['data'];

		$this->assertSame( 3, count( $items ) );

		// we expect them to be returned in reverse chronological order
		// could have created the samples in that order but this seems more explicit...
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['id'] );
		$this->assertSame( $this->data_posts[1]->ID, $items[1]['id'] );
		$this->assertSame( $this->data_posts[0]->ID, $items[2]['id'] );

		$items = $datasource->get_items_for_ajax( $this->data_posts[1]->ID );
		$items = $items['data'];
		$this->assertSame( $this->data_posts[1]->ID, $items[0]['id'] );

		$items = $datasource->get_items_for_ajax( get_permalink( $this->data_posts[2]->ID ) );
		$items = $items['data'];
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['id'] );
	}

	public function test_ajax_args() {
		$ajax_args = array(
			'foo' => 'bar',
		);

		$fm = new Fieldmanager_Zone_Field( array(
			'ajax_args' => $ajax_args,
		) );

		ob_start();
		$fm->add_meta_box( 'Test Zone Field', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$expected = esc_attr( wp_json_encode( $ajax_args ) );
		$this->assertRegExp( '/data\-args=[\'"]' . preg_quote( $expected ) . '[\'"]/', $html );
	}
}
