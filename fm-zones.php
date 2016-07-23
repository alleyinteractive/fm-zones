<?php

/*
	Plugin Name: Fieldmanager Zones
	Plugin URI: https://github.com/alleyinteractive/fm-zones
	Description: Fieldmanager field which acts as a Zoninator clone.
	Version: 0.1.10
	Author: Alley Interactive
	Author URI: http://www.alleyinteractive.com/
*/
/*
	Copyright 2010-2015 Mohammad Jangda, Automattic
	Copyright 2015 Alley Interactive

	The following code is a derivative work of code from the Automattic plugin
	Zoninator, which is licensed GPLv2. This code therefore is also licensed
	under the terms of the GNU Public License, verison 2.

	This program is free software; you can redistribute it and/or modify
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

function fmz_load_fieldmanager_zone_field() {
	if ( class_exists( 'Fieldmanager_Field' ) && ! class_exists( 'Fieldmanager_Zone_Field' ) ) {
		define( 'FMZ_PATH', dirname( __FILE__ ) );
		define( 'FMZ_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
		define( 'FMZ_VERSION', '0.1.11' );
		require_once( FMZ_PATH . '/php/class-fieldmanager-zone-field.php' );
	}
}
add_action( 'after_setup_theme', 'fmz_load_fieldmanager_zone_field' );
