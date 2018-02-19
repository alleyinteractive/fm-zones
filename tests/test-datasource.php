<?php

/**
 * Tests the Fieldmanager Datasource Zone Field
 *
 * @group datasource
 * @group zone
 */
class Test_Fieldmanager_Datasource_Zone_Field extends WP_UnitTestCase {

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

		$this->datasource = new Fieldmanager_Datasource_Zone_Field();
	}

	public function test_prepare_datasource_items_against_post_datasource() {
		$original = new Fieldmanager_Datasource_Post();
		$original_prepared = $this->datasource->prepare_datasource_items( $original->get_items() );

		$prepared = $this->datasource->get_items();

		$this->assertEquals( $original_prepared, $prepared );
	}

	public function test_prepare_datasource_items() {
		$mocked = array(
			$this->post_id => 'Test label',
		);

		$expected = array( $this->post );

		$prepared = $this->datasource->prepare_datasource_items( $mocked );

		$this->assertEquals( $expected, $prepared );
	}

	public function test_format_items() {
		$formatted = $this->datasource->format_posts( $this->data_posts );

		$this->assertEquals( 3, count( $formatted ) );

		$first = array_shift( $formatted );

		$keys = array(
			'id',
			'post_status',
			'post_type',
			'title',
			'date',
			'thumb',
			'link',
		);

		foreach ( $keys as $key ) {
			$this->assertArrayHasKey( $key, $first );
		}
	}

	public function test_query_filter() {
		$this->markTestIncomplete();
	}

	public function test_exclude_posts() {
		$this->markTestIncomplete();
	}
}
