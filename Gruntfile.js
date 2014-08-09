/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	var path   = require( 'path' ),
	SOURCE_DIR = 'src/',
	BUILD_DIR  = 'build/',

	BP_CSS = [
		'**/*.css',
		'!**/*-rtl.css'  // Exclude RTL files
	],

	BP_JS = [
		'**/*.js',

		// Exclude third-party libraries.
		'!bp-core/js/jquery.atwho.js',
		'!bp-core/js/jquery.caret.js',

		// Exclude legacy templates.
		'!bp-templates/**/*.js',

		// Exclude anything in any deprecated folders.
		'!**/deprecated/**/*.js'
	];

	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );


	grunt.initConfig( {
		pkg: grunt.file.readJSON('package.json'),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['Gruntfile.js']
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: BP_JS,

				/**
				 * Limit JSHint's run to a single specified file: grunt jshint:core --file=filename.js
				 * Optionally, include the file path: grunt jshint:core --file=path/to/filename.js
				 *
				 * @param {String} filepath
				 * @returns {Bool}
				 */
				filter: function( filepath ) {
					var index, file = grunt.option( 'file' );

					// Don't filter when no target file is specified
					if ( ! file ) {
						return true;
					}

					// Normalise filepath for Windows
					filepath = filepath.replace( /\\/g, '/' );
					index = filepath.lastIndexOf( '/' + file );

					// Match only the filename passed from cli
					if ( filepath === file || ( -1 !== index && index === filepath.length - ( file.length + 1 ) ) ) {
						return true;
					}

					return false;
				}
			}
		},
		cssjanus: {
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: SOURCE_DIR,
				ext: '-rtl.css',
				src: BP_CSS,
				options: { generateExactDuplicates: true }
			},
			dynamic: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: SOURCE_DIR,
				ext: '-rtl.css',
				src: [],
				options: { generateExactDuplicates: true }
			}
		},

		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: 'buddypress',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: SOURCE_DIR + '**/*.php',
				expand: true
			}
		},
		makepot: {
			target: {
				options: {
					cwd: BUILD_DIR,
					domainPath: '.',
					mainFile: 'bp-loader.php',
					potFilename: 'buddypress.pot',
					processPot: function( pot ) {
						pot.headers['report-msgid-bugs-to'] = 'https://buddypress.trac.wordpress.org';
						pot.headers['last-translator'] = 'JOHN JAMES JACOBY <jjj@buddypress.org>';
						pot.headers['language-team'] = 'ENGLISH <jjj@buddypress.org>';
						return pot;
					},
					type: 'wp-plugin'
				}
			}
		},
		imagemin: {
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: ['**/*.{gif,jpg,jpeg,png}'],
				dest: SOURCE_DIR
			}
		},

		clean: {
			all: [ BUILD_DIR ],
			dynamic: {
				cwd: BUILD_DIR,
				dot: true,
				expand: true,
				src: []
			}
		},
		copy: {
			files: {
				files: [
					{
						cwd: SOURCE_DIR,
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['**', '!**/.{svn,git}/**']
					}
				]
			},
			dynamic: {
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				dot: true,
				expand: true,
				src: []
			}
		},
		uglify: {
			core: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				expand: true,
				ext: '.min.js',
				src: BP_JS
			},
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
				'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
				'https://wordpress.org/plugins/buddypress/ */\n'
			}
		},
		cssmin: {
			ltr: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				expand: true,
				ext: '.min.css',
				src: BP_CSS,
				options: {
					banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
					'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
					'https://wordpress.org/plugins/buddypress/ */'
				}
			},
			rtl: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				expand: true,
				ext: '.min.css',
				src: BP_CSS.map( function( filename ) {
					return filename.replace( '.css', '-rtl.css' );
				}),
				options: {
					banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
					'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
					'https://wordpress.org/plugins/buddypress/ */'
				}
			}
		},

		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
			'multisite': {
				cmd: 'phpunit',
				args: ['-c', 'tests/phpunit/multisite.xml']
			}
		},
		exec: {
			bbpress: {
				command: 'svn export --force https://bbpress.svn.wordpress.org/tags/1.2 bbpress',
				cwd: BUILD_DIR + 'bp-forums',
				stdout: false
			},
			bpdefault: {
				command: 'svn export --force https://github.com/buddypress/BP-Default.git/trunk bp-themes/bp-default',
				cwd: BUILD_DIR,
				stdout: false
			}
		},

		jsvalidate:{
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			},
			build: {
				files: {
					src: [BUILD_DIR + '/**/js/*.js', BUILD_DIR + '/**/admin/js/*.js']
				}
			}
		},

		patch: {
			options: {
				tracUrl: 'buddypress.trac.wordpress.org'
			}
		}
	});


	/**
	 * Register tasks.
	 */
	grunt.registerTask( 'build',         ['jsvalidate:build', 'jshint', 'cssjanus:core'] );
	grunt.registerTask( 'build-commit',  ['build', 'checktextdomain', 'imagemin'] );
	grunt.registerTask( 'build-release', ['build-commit', 'clean:all', 'copy:files', 'uglify:core', 'cssmin:ltr', 'cssmin:rtl', 'makepot', 'exec:bbpress', 'exec:bpdefault', 'test'] );

	// Testing tasks.
	grunt.registerMultiTask( 'phpunit', 'Runs PHPUnit tests, including the ajax and multisite tests.', function() {
		grunt.util.spawn( {
			args: this.data.args,
			cmd:  this.data.cmd,
			opts: { stdio: 'inherit' }
		}, this.async() );
	});

	grunt.registerTask( 'test', 'Run all unit test tasks.', ['phpunit'] );

	grunt.registerTask( 'jstest', 'Runs all javascript tasks.', [ 'jsvalidate', 'jshint' ] );

	// Travis CI Task
	grunt.registerTask( 'travis', ['jshint', 'test'] );

	// Patch task.
	grunt.renameTask( 'patch_wordpress', 'patch' );

	// Default task.
	grunt.registerTask( 'default', ['build'] );

	/**
	 * Add a listener to the watch task.
	 *
	 * On `watch:all`, automatically updates the `copy:dynamic` and `clean:dynamic` configurations so that only the changed files are updated.
	 * On `watch:rtl`, automatically updates the `cssjanus:dynamic` configuration.
	 */
	grunt.event.on( 'watch', function( action, filepath, target ) {
		if ( target !== 'all' && target !== 'rtl' ) {
			return;
		}

		var relativePath = path.relative( SOURCE_DIR, filepath ),
		cleanSrc = ( action === 'deleted' ) ? [ relativePath ] : [],
		copySrc  = ( action === 'deleted' ) ? [] : [ relativePath ];

		grunt.config( ['clean', 'dynamic', 'src'], cleanSrc );
		grunt.config( ['copy', 'dynamic', 'src'], copySrc );
		grunt.config( ['cssjanus', 'dynamic', 'src'], copySrc );
	});
};