module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'nccp',
			},
			target: {
				files: {
					src: [ '*.php', 'php/*.php' ]
				}
			}
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'fm-zones.php',
					exclude: [ 'tests', 'node_modules', 'vendor', 'bin' ],
					potFilename: 'fm-zones.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );

	grunt.util.linefeed = '\n';

};
