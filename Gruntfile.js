/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	var SOURCE_DIR = 'src/',
		BUILD_DIR  = 'build/',

		// CSS
		BP_CSS = [
			'**/*.css'
		],

		// CSS exclusions, for excluding files from certain tasks, e.g. cssjanus
		BP_EXCLUDED_CSS = [
			'!**/*-rtl.css'
		],

		// JavaScript - Core
		BP_JS = [
			'**/*.js'
		],

		// JavaScript exclusions, for excluding from certain tasks e.g jshint
		BP_EXCLUDED_JS = [
			'!bp-core/deprecated/js/**/*.js', // Depracted
			'!bp-core/js/jquery.atwho.js',    // External 3rd party library
			'!bp-core/js/jquery.caret.js',    // External 3rd party library
			'!bp-core/js/jquery-cookie.js'    // External 3rd party library
		];

	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['Gruntfile.js']
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: BP_JS.concat( BP_EXCLUDED_JS ),

				/**
				 * Limit JSHint's run to a single specified file:
				 *
				 * grunt jshint:core --file=filename.js
				 *
				 * Optionally, include the file path:
				 *
				 * grunt jshint:core --file=path/to/filename.js
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
				extDot: 'last',
				ext: '-rtl.css',
				src: BP_CSS.concat( BP_EXCLUDED_CSS ),
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
			all: [ BUILD_DIR ]
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
			}
		},
		uglify: {
			core: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				extDot: 'last',
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
			minify: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: BP_CSS,
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
					src: [BUILD_DIR + '/**/*.js' ]
				}
			},
			src: {
				files: {
					src: [SOURCE_DIR + '/**/*.js' ]
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
	grunt.registerTask( 'build',         ['jsvalidate:src', 'jshint', 'cssjanus'] );
	grunt.registerTask( 'build-commit',  ['build', 'checktextdomain', 'imagemin'] );
	grunt.registerTask( 'build-release', ['build-commit', 'clean:all', 'copy:files', 'uglify', 'jsvalidate:build', 'cssmin', 'makepot', 'exec:bbpress', 'exec:bpdefault', 'test'] );

	// Testing tasks.
	grunt.registerMultiTask( 'phpunit', 'Runs PHPUnit tests, including the ajax and multisite tests.', function() {
		grunt.util.spawn( {
			args: this.data.args,
			cmd:  this.data.cmd,
			opts: { stdio: 'inherit' }
		}, this.async() );
	});

	grunt.registerTask( 'test', 'Run all unit test tasks.', ['phpunit'] );

	grunt.registerTask( 'jstest', 'Runs all javascript tasks.', [ 'jsvalidate:src', 'jshint' ] );

	// Travis CI Task
	grunt.registerTask( 'travis', ['jshint', 'test'] );

	// Patch task.
	grunt.renameTask( 'patch_wordpress', 'patch' );

	// Default task.
	grunt.registerTask( 'default', ['build'] );
};
