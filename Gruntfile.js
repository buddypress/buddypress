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
			'!**/*-rtl.css',
			'!**/blocks/*/index.css'
		],

		BP_JS = [
			'**/*.js'
		],

		BP_EXCLUDED_JS = [
			'!src/js/**',
			'!**/bp-core/admin/js/dismissible-admin-notices.js',
			'!**/bp-members/admin/js/admin.js',
			'!**/blocks/*/*.js'
		],

		BP_EXCLUDED_MISC = [
		],

		// SASS generated "Twenty*"" CSS files
		BP_SCSS_CSS_FILES = [
			'!bp-templates/bp-legacy/css/twenty*.css',
			'!bp-templates/bp-nouveau/css/buddypress.css',
			'!bp-templates/bp-nouveau/css/twenty*.css',
			'!bp-templates/bp-nouveau/css/primary-nav.css',
			'!bp-templates/bp-nouveau/sass/priority-nav.scss',
			'!bp-templates/bp-nouveau/sass/bp-tooltips.scss',
			'!bp-core/admin/css/hello.css',
			'!src/js/**',
			'!**/blocks/*/index.css'
		],

		sass = require('node-sass');

	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );
	require( 'phplint' ).gruntPlugin( grunt );

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
				 * @returns {Boolean}
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
					return filepath === file || (-1 !== index && index === filepath.length - (file.length + 1));
				}
			}
		},
		sass: {
			options: {
				implementation: sass,
				outputStyle: 'expanded',
				indentType: 'tab',
				indentWidth: '1'
			},
			legacy: {
				cwd: SOURCE_DIR,
				extDot: 'last',
				expand: true,
				ext: '.css',
				flatten: true,
				src: ['bp-templates/bp-legacy/css/*.scss'],
				dest: SOURCE_DIR + 'bp-templates/bp-legacy/css/'
			},
			nouveau: {
				cwd: SOURCE_DIR,
				extDot: 'last',
				expand: true,
				ext: '.css',
				flatten: true,
				src: ['bp-templates/bp-nouveau/sass/buddypress.scss', 'bp-templates/bp-nouveau/sass/twenty*.scss', 'bp-templates/bp-nouveau/sass/primary-nav.scss', 'bp-templates/bp-nouveau/sass/priority-nav.scss', 'bp-templates/bp-nouveau/sass/bp-tooltips.scss'],
				dest: SOURCE_DIR + 'bp-templates/bp-nouveau/css/'
			},
			core: {
				cwd: SOURCE_DIR,
				extDot: 'last',
				expand: true,
				ext: '.css',
				flatten: true,
				src: ['bp-core/sass/*.scss'],
				dest: SOURCE_DIR + 'bp-core/css/'
			},
			admin: {
				cwd: SOURCE_DIR,
				extDot: 'last',
				expand: true,
				ext: '.css',
				flatten: true,
				src: ['bp-core/admin/sass/*.scss'],
				dest: SOURCE_DIR + 'bp-core/admin/css/'
			}
		},
		rtlcss: {
			options: {
				opts: {
					processUrls: false,
					autoRename: false,
					clean: true
				},
				saveUnmodified: true
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
		// Remove this when all WPCS issues are resolved.
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
		imagemin: {
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: ['**/*.{gif,jpg,jpeg,png}'].concat( BP_EXCLUDED_MISC ),
				dest: SOURCE_DIR
			}
		},
		clean: {
			all: [ BUILD_DIR ],
			bp_admin_modern_js: [ 'dist/' ],
			cli: [
				BUILD_DIR + 'cli/features/',
				BUILD_DIR + 'cli/*.{yml,json,lock,xml,xml.dist,md}',
				BUILD_DIR + 'cli/{.gitignore,.distignore,.editorconfig,.travis.yml}'
			]
		},
		copy: {
			files: {
				files: [
					{
						cwd: SOURCE_DIR,
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['**', '!**/.{svn,git,cache}/**', '!js/**'].concat( BP_EXCLUDED_MISC )
					},
					{
						dest: BUILD_DIR,
						dot: true,
						expand: true,
						src: ['composer.json']
					}
				]
			},
			bp_admin_modern_js: {
				cwd: 'dist/',
				dest: SOURCE_DIR,
				dot: true,
				expand: true,
				filter: 'isFile',
				src: ['bp-activity/**/*.js', 'bp-blogs/**/*.js', 'bp-core/**/*.js', 'bp-friends/**/*.js', 'bp-groups/**/*.js', 'bp-members/**/*.js', 'bp-messages/**/*.js', 'bp-notifications/**/*.js', 'bp-settings/**/*.js', 'bp-xprofile/**/*.js']
			}
		},
		uglify: {
			core: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				extDot: 'last',
				expand: true,
				ext: '.min.js',
				src: BP_JS.concat( BP_EXCLUDED_JS, BP_EXCLUDED_MISC )
			}
		},
		stylelint: {
			css: {
				options: {
					configFile: '.stylelintrc',
					format: 'css'
				},
				expand: true,
				cwd: SOURCE_DIR,
				src: BP_CSS.concat( BP_EXCLUDED_CSS, BP_EXCLUDED_MISC, BP_SCSS_CSS_FILES )
			},
			scss: {
				options: {
					configFile: '.stylelintrc',
					format: 'scss'
				},
				expand: true,
				cwd: SOURCE_DIR,
				src: [ '**/*.scss' ]
			}
		},
		phplint: {
			files: ['src/**/*.php'].concat( BP_EXCLUDED_MISC ),
			options: {
				stdout: false,
				stderr: true
			}
		},
		postcss: {
			options: {
				map: false,
				processors: [
					require('autoprefixer')( {
						cascade: false
					} )
				],
				failOnError: false
			},
			css: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: SOURCE_DIR,
				src: BP_CSS.concat( BP_EXCLUDED_CSS, BP_EXCLUDED_MISC )
			}
		},
		cssmin: {
			minify: {
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: BP_CSS.concat( ['!**/blocks/*/index.css'] )
			}
		},
		phpunit: {
			'default': {
				cmd: './vendor/phpunit/phpunit/phpunit',
				args: ['-c', 'phpunit.xml.dist']
			},
			'multisite': {
				cmd: './vendor/phpunit/phpunit/phpunit',
				args: ['-c', 'tests/phpunit/multisite.xml']
			},
			'codecoverage': {
				cmd: './vendor/phpunit/phpunit/phpunit',
				args: ['-c', 'tests/phpunit/codecoverage.xml' ]
			}
		},
		exec: {
			cli: {
				command: 'npm run download:cli',
				cwd: BUILD_DIR,
				stdout: false
			},
			phpcs_escape: {
				command: 'composer run phpcs-escape',
				stdout: true
			},
			phpcompat: {
				command: 'composer run phpcompat',
				stdout: true
			},
			makepot: {
				command: 'wp i18n make-pot build build/buddypress.pot --headers=\'{"Project-Id-Version": "BuddyPress", "Report-Msgid-Bugs-To": "https://buddypress.trac.wordpress.org", "Last-Translator": "JOHN JAMES JACOBY <jjj@buddypress.org>", "Language-Team": "ENGLISH <jjj@buddypress.org>"}\'',
				stdout: true
			},
			modernJS_src: {
				command: 'npm run build:admin',
				cwd: SOURCE_DIR,
				stdout: true
			},
			blocks_src: {
				command: 'npm run build:blocks',
				cwd: SOURCE_DIR,
				stdout: true
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
					src: [BUILD_DIR + '/**/*.js'].concat( BP_EXCLUDED_JS, BP_EXCLUDED_MISC )
				}
			},
			src: {
				files: {
					src: [SOURCE_DIR + '/**/*.js'].concat( BP_EXCLUDED_JS, BP_EXCLUDED_MISC )
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
	grunt.registerTask( 'src', ['checkDependencies', 'jsvalidate:src', 'jshint', 'stylelint', 'sass', 'postcss', 'rtlcss'] );
	grunt.registerTask( 'style', ['stylelint', 'sass', 'postcss', 'rtlcss'] );
	grunt.registerTask( 'makepot', ['exec:makepot'] );
	grunt.registerTask( 'commit', ['src', 'checktextdomain', 'imagemin', 'phplint', 'exec:phpcs_escape', 'exec:phpcompat'] );
	grunt.registerTask( 'commit:blocks', ['commit', 'exec:blocks_src', 'exec:modernJS_src'] );
	grunt.registerTask( 'build', ['commit:blocks', 'clean:all', 'copy:files', 'uglify:core', 'jsvalidate:build', 'cssmin', 'makepot', 'exec:cli', 'clean:cli'] );
	grunt.registerTask( 'release', ['build'] );
	grunt.registerTask( 'move:admin:js', [ 'copy:bp_admin_modern_js', 'clean:bp_admin_modern_js' ] );

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

	// Patch task.
	grunt.renameTask( 'patch_wordpress', 'patch' );

	// Default task.
	grunt.registerTask( 'default', ['src'] );
};
