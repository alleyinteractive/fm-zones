<?php

/*
	Plugin Name: FM Zoninator Field
	Plugin URI: https://github.com/alleyinteractive/fm-zoninator
	Description: Fieldmanager field which acts as a Zoninator clone.
	Version: 0.1.0
	Author: Alley Interactive
	Author URI: http://www.alleyinteractive.com/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'FMZ_PATH', dirname( __FILE__ ) );
define( 'FMZ_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'FMZ_VERSION', '0.1.0' );

add_action( 'after_setup_theme', function() {
	require_once( __DIR__ . '/php/class-zoninator-field.php' );
	require_once( __DIR__ . '/php/class-fm-zoninator-demo.php' );
} );

add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'fm-zoninator-jquery-ui', FMZ_URL . '/static/jquery-ui/smoothness/jquery-ui-zoninator.css', false, FMZ_VERSION, 'all' );
	wp_enqueue_style( 'fm-zoninator-styles', FMZ_URL . '/static/css/fm-zoninator.css', false, FMZ_VERSION, 'all' );
	wp_enqueue_script( 'fm-zoninator-script', FMZ_URL . '/static/js/fm-zoninator.js', array( 'jquery', 'underscore' ), FMZ_VERSION, true );
}, 20 );