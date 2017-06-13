/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	var SOURCE_DIR = 'src/',
		BUILD_DIR = 'build/',

		BP_CSS = [
			'**/*.css'
		],

		// CSS exclusions, for excluding files from certain tasks, e.g. rtlcss
		BP_EXCLUDED_CSS = [
			'!**/*-rtl.css'
		],

		BP_JS = [
			'**/*.js'
		],

		BP_EXCLUDED_MISC = [
			'!bp-forums/bbpress/**/*'
		],

		// SASS generated "Twenty*"" CSS files
		BP_SCSS_CSS_FILES = [
			'!bp-templates/bp-legacy/css/twenty*.css'
		],

		stylelintConfigCss  = require('stylelint-config-wordpress/index.js'),
		stylelintConfigScss = require('stylelint-config-wordpress/scss.js');

	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		checkDependencies: {
			options: {
				packageManager: 'npm'
			},
			src: {}
		},
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
		sass: {
			styles: {
				cwd: SOURCE_DIR,
				extDot: 'last',
				expand: true,
				ext: '.css',
				flatten: true,
				src: ['bp-templates/bp-legacy/css/*.scss'],
				dest: SOURCE_DIR + 'bp-templates/bp-legacy/css/',
				options: {
					outputStyle: 'expanded',
					indentType: 'tab',
					indentWidth: '1'
				}
			}
		},
		rtlcss: {
			options: {
				opts: {
					processUrls: false,
					autoRename: false,
					clean: true
				},
				saveUnmodified: false
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: SOURCE_DIR,
				extDot: 'last',
				ext: '-rtl.css',
				src: BP_CSS.concat( BP_EXCLUDED_CSS, BP_EXCLUDED_MISC )
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
				cwd: SOURCE_DIR,
				src: ['**/*.php'].concat( BP_EXCLUDED_MISC ),
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
				src: ['**/*.{gif,jpg,jpeg,png}'].concat( BP_EXCLUDED_MISC ),
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
						src: ['**', '!**/.{svn,git}/**'].concat( BP_EXCLUDED_MISC )
					},
					{
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['composer.json']
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
			}
		},
		stylelint: {
			css: {
				options: {
					config: stylelintConfigCss,
					format: 'css'
				},
				expand: true,
				cwd: SOURCE_DIR,
				src: BP_CSS.concat( BP_EXCLUDED_CSS, BP_EXCLUDED_MISC, BP_SCSS_CSS_FILES )
			},
			scss: {
				options: {
					config: stylelintConfigScss,
					format: 'scss'
				},
				expand: true,
				cwd: SOURCE_DIR,
				src: [ 'bp-templates/bp-legacy/css/*.scss' ]
			}
		},
		cssmin: {
			minify: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: BP_CSS
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
			},
			'codecoverage': {
				cmd: 'phpunit',
				args: ['-c', 'tests/phpunit/codecoverage.xml' ]
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
					src: [BUILD_DIR + '/**/*.js']
				}
			},
			src: {
				files: {
					src: [SOURCE_DIR + '/**/*.js'].concat( BP_EXCLUDED_MISC )
				}
			}
		},
		patch: {
			options: {
				tracUrl: 'buddypress.trac.wordpress.org'
			}
		},
		upload_patch: {
			options: {
				tracUrl: 'buddypress.trac.wordpress.org'
			}
		}
	});


	/**
	 * Register tasks.
	 */
	grunt.registerTask( 'src',     ['checkDependencies', 'jsvalidate:src', 'jshint', 'stylelint', 'sass', 'rtlcss'] );
	grunt.registerTask( 'commit',  ['src', 'checktextdomain', 'imagemin'] );
	grunt.registerTask( 'build',   ['commit', 'clean:all', 'copy:files', 'uglify', 'jsvalidate:build', 'cssmin', 'makepot', 'exec:bpdefault'] );
	grunt.registerTask( 'release', ['build', 'exec:bbpress'] );

	// Testing tasks.
	grunt.registerMultiTask( 'phpunit', 'Runs PHPUnit tests, including the ajax and multisite tests.', function() {
		grunt.util.spawn( {
			args: this.data.args,
			cmd:  this.data.cmd,
			opts: { stdio: 'inherit' }
		}, this.async() );
	});

	grunt.registerTask( 'test', 'Run all unit test tasks.', ['phpunit:default', 'phpunit:multisite'] );

	grunt.registerTask( 'jstest', 'Runs all JavaScript tasks.', [ 'jsvalidate:src', 'jshint' ] );

	// Travis CI Tasks.
	grunt.registerTask( 'travis:grunt', 'Runs Grunt build task.', [ 'build' ]);
	grunt.registerTask( 'travis:phpunit', ['jsvalidate:src', 'jshint', 'checktextdomain', 'test'] );
	grunt.registerTask( 'travis:codecoverage', 'Runs PHPUnit tasks with code-coverage generation.', ['phpunit:codecoverage'] );

	// Patch task.
	grunt.renameTask( 'patch_wordpress', 'patch' );

	// Default task.
	grunt.registerTask( 'default', ['src'] );
};
