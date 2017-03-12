/* jshint node:true */
'use strict';
module.exports = function(grunt) {
	var SOURCE_DIR = 'src/',
		BUILD_DIR = 'build/',
		ASSETS_DIR = 'assets/';

	var browserSync = require("browser-sync");
	// Load all tasks
	require("load-grunt-tasks")(grunt);
	// Show elapsed time
	require("time-grunt")(grunt);

	var css_source_files = [
		"assets/css/qts-default.css",
		"assets/css/qts-settings.css"
	];
	var css_dist_files = [
		"assets/css/qts-default.min.css",
		"assets/css/qts-settings.min.css"
	];

	var js_source_files = [
		"assets/js/qts-nav-menu.js",
		"assets/js/qts-settings.js",
		"assets/js/qts-settings-upgrade.js",
	];

	// Project configuration.
	grunt.initConfig({
		uglify: {
			js: {
				files: [{
					expand: true,
					src:    js_source_files,
					ext:    '.min.js'
				}]
			}
		},
		autoprefixer: {
			options: {
				browsers: ["last 2 versions"]
			},
			dev: {
				options: {
					map: {
						prev: "assets/css/"
					}
				},
				expand:  true,
				flatten: true,
				src:     css_source_files,
			},
			build: {
				src: css_dist_files
			}
		},
		qunit: {
			files: [
				'tests/qunit/**/*.html',
				'!tests/qunit/editor/**'
			]
		},
		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
		},
		watch: {
			config: {
				files: 'Gruntfile.js'
			},
			test_qunit: {
				files: [
					'tests/qunit/**',
					'!tests/qunit/editor/**'
				],
				tasks: ['qunit']
			},
			test_phpunit: {
				files: [
					'tests/test-*php',
					'**/*php'
				],
				tasks: ['phpunit']
			}
		}
	});




	// Register tasks.
	grunt.registerTask("build", [
		"uglify",
		"autoprefixer:build",
	]);

	grunt.registerTask("bs-init", function () {
		var done = this.async();
		browserSync({
			port: 3002,
			ui: false,
			watchTask: true,
			proxy: "qtranslate.dev",
			logPrefix: "qts",
			files: [
				"assets/css/qts-default.min.css",
				"assets/css/qts-settings.min.css",
				"assets/js/qts-nav-menu.min.js",
				"assets/js/qts-settings.min.js",
				"assets/js/qts-settings-upgrade.min.js",
				"**/*php",
				"*.php"
			]
		});
	});

	grunt.registerTask( 'precommit:php', [
		'phpunit'
	] );

	// Testing tasks.
	grunt.registerMultiTask('phpunit', 'Runs PHPUnit tests, including the ajax, external-http, and multisite tests.', function() {
		grunt.util.spawn({
			//cmd: this.data.cmd,
			cmd: './vendor/bin/phpunit',
//			args: this.data.args,
			opts: {stdio: 'inherit'}
		}, this.async());
	});

	grunt.registerTask('qunit:compiled', 'Runs QUnit tests on compiled as well as uncompiled scripts.',
		['build', 'copy:qunit', 'qunit']);

	grunt.registerTask('test', 'Runs all QUnit and PHPUnit tasks.', ['qunit:compiled', 'phpunit']);

	// Travis CI tasks.
	grunt.registerTask('travis:js', 'Runs Javascript Travis CI tasks.', [ 'jshint:corejs', 'qunit:compiled' ]);
	grunt.registerTask('travis:phpunit', 'Runs PHPUnit Travis CI tasks.', 'phpunit');

	// Default task.
	grunt.registerTask('default', ['build']);
/*
	// JSHint task.
	grunt.registerTask( 'jshint:corejs', [
		'jshint:grunt',
		'jshint:tests',
		'jshint:themes',
		'jshint:core',
		'jshint:media'
	] );


	grunt.registerTask( 'watch', function() {
		if ( ! this.args.length || this.args.indexOf( 'browserify' ) > -1 ) {
			grunt.config( 'browserify.options', {
				browserifyOptions: {
					debug: true
				},
				watch: true
			} );

			grunt.task.run( 'browserify' );
		}

		grunt.task.run( '_' + this.nameArgs );
	} );


	grunt.registerTask( 'precommit:js', [
		'browserify',
		'jshint:corejs',
		'uglify:bookmarklet',
		'uglify:masonry',
		'qunit:compiled'
	] );

	grunt.registerTask( 'precommit:css', [
		'postcss:core'
	] );


	grunt.registerTask( 'build', [
		'clean:all',
		'copy:all',
		'cssmin:core',
		'colors',
		'rtl',
		'cssmin:rtl',
		'cssmin:colors',
		'uglify:core',
		'uglify:embed',
		'uglify:jqueryui',
		'concat:tinymce',
		'compress:tinymce',
		'clean:tinymce',
		'concat:emoji',
		'includes:emoji',
		'includes:embed',
		'jsvalidate:build'
	] );

	// Testing tasks.
	grunt.registerMultiTask('phpunit', 'Runs PHPUnit tests, including the ajax, external-http, and multisite tests.', function() {
		grunt.util.spawn({
			cmd: this.data.cmd,
			args: this.data.args,
			opts: {stdio: 'inherit'}
		}, this.async());
	});

	grunt.registerTask('qunit:compiled', 'Runs QUnit tests on compiled as well as uncompiled scripts.',
		['build', 'copy:qunit', 'qunit']);

	grunt.registerTask('test', 'Runs all QUnit and PHPUnit tasks.', ['qunit:compiled', 'phpunit']);

	// Travis CI tasks.
	grunt.registerTask('travis:js', 'Runs Javascript Travis CI tasks.', [ 'jshint:corejs', 'qunit:compiled' ]);
	grunt.registerTask('travis:phpunit', 'Runs PHPUnit Travis CI tasks.', 'phpunit');

	// Default task.
	grunt.registerTask('default', ['build']);
	*/

};
