<?php
/**
 * FM Zoninator Demo
 */

class FM_Zoninator_Demo {

	private static $instance;

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public function __clone() { wp_die( "Please don't __clone FM_Zoninator_Demo" ); }

	public function __wakeup() { wp_die( "Please don't __wakeup FM_Zoninator_Demo" ); }

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new FM_Zoninator_Demo;
			self::$instance->setup();
		}
		return self::$instance;
	}

	public function setup() {
		add_action( 'fm_post_post', array( $this, 'field' ) );
	}

	public function field() {
		$fm = new Zoninator_Field( array(
			'name' => 'zone',
		) );
		$fm->add_meta_box( __( 'FM Zoninator', 'fmz' ), array( 'post' ) );

		$fm = new Zoninator_Field( array(
			'name' => 'zone_2',
		) );
		$fm->add_meta_box( __( 'Can it handle two?', 'fmz' ), array( 'post' ) );
	}
}

function FM_Zoninator_Demo() {
	return FM_Zoninator_Demo::instance();
}
FM_Zoninator_Demo();