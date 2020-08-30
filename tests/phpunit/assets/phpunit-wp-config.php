<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tests-wordpress');

/** MySQL database username */
define( 'DB_USER', 'root');

/** MySQL database password */
define( 'DB_PASSWORD', '');

/** MySQL hostname */
define( 'DB_HOST', 'mysql');

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'a89b7f2679fa896912a0bc6663b8c1dee8845e0e');
define( 'SECURE_AUTH_KEY',  '0819cb8ebad69f537adc5351b095a91e3fee5fbd');
define( 'LOGGED_IN_KEY',    'ea0ab2035e22b76f751a44df6a89d85c44f9ba1f');
define( 'NONCE_KEY',        '4665de6759a3f771be0509b8964c0dee8b12cc3e');
define( 'AUTH_SALT',        'cd8ff2945b6e3bf3257845f6f1c14de673f38bb1');
define( 'SECURE_AUTH_SALT', 'd9c8a7a96d41e863c0e8517632e0d4f1ab135d49');
define( 'LOGGED_IN_SALT',   '318a8828a24ea9fa51d10a0aabb92d419c716e3e');
define( 'NONCE_SALT',       '08e12a5848e0f7516e1b527497239c41f55904ab');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wptests_';

/**
 * Test with WordPress debug mode (default).
 */
define( 'WP_DEBUG', true );

// Set Site domain, email and title constants.
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test BP' );

define( 'WP_PHP_BINARY', 'php' );

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', '/var/www/html/' );
}
