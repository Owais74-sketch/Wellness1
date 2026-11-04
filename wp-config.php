<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wellness' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'B~v$bD{OvgL?aw{<h$!f-R=-0>/gJ9%+n66p/0<~K9it}&+!8P.Q%zYJU+Mg?(FL' );
define( 'SECURE_AUTH_KEY',  'u=_rGIk?|q`Q/w+r_^>n??!y6i4*h2%X7t>%uhg`u.Z+YqS-j9jJ![/.tqZ/)BKV' );
define( 'LOGGED_IN_KEY',    '-oob!=bx/CQS+ukwo^q{-,_o,n?{dt]Yb1cr!++AfRDWf&`d$jnd `5C~qyRvsX2' );
define( 'NONCE_KEY',        '4`_Z1|>Lcw3iKlp08ns9+<]DdRBBB,BqeREx>Y(tjyLOdJnq -XB{&9wLJhxKb66' );
define( 'AUTH_SALT',        'e8.-eY<&lJ|4+QmLql,`]dQK>JQUaIV4}m1&vt*IaAR1r,;u]>rIAVFC?qE:asdc' );
define( 'SECURE_AUTH_SALT', ',Cp`R3h:iJ^noHOa4g=|Q/<xeloQUvuRB)mji.>Z;9~%UjSb/61O(d`|ut1,IIZr' );
define( 'LOGGED_IN_SALT',   '-emEi#e+ Vpw|)o[t*z!h|Fp}D*OTjCT.&I,!9*XJ7In.[OjEB6_gSA|>6OX+(;b' );
define( 'NONCE_SALT',       'A]}RLHP`VLL3%Qgw@7M E$tM?:m{TzsO|Tf>tOGN:A55,.~PevJ8YIZ06dm?t.mX' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
