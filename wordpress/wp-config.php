<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp_topart');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'admin123');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', 'wp_');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'UR,n0Q9k8MaXOak<Ys7wf}+-|N}OJBD#&DKwam<6b v_CcFIC]l{:?n,<gkB.+CE');
define('SECURE_AUTH_KEY',  '|F@<FUvp+^lZz?]>q}])]TkD@c}T5x+-e,6rM*tO|?bRI~8&P-VR+-)r[+`frOje');
define('LOGGED_IN_KEY',    'MS/$m&[*Fs B>TtY(xE&mV)o/o%|+^(F6gfhdNF2EdSG,Sv?)ds#Q$kVoB3-J;I<');
define('NONCE_KEY',        'B6|Ji.Zn8EB|nB@9Kh6.C*H0CON0J%>* ^,2;TTV28}xV..ypr(iPjd7)04SAcdf');
define('AUTH_SALT',        '1gMO-@PeEYa$0JqAQU!8*]fO%17qDhwa|oA:2`k7t%g3?NuLFiH&g[t-swG/}42-');
define('SECURE_AUTH_SALT', 'OA+LW-4a ArvyHJ/i)Z]Get>=Gwi-f%rvJ0(^|1C`Rtn.]TX;zpW,&Rd2q6ZN-E)');
define('LOGGED_IN_SALT',   'V:o*9aZY!*eI!M@cR6]wNebj@B6SZ&LMT0^A|K =EI)ap@`,;.8msIwh|3MBC*XX');
define('NONCE_SALT',       'IENO+9}LtLifkky}X92<*iB?uX}96H~k6}$9{-e<YHPdHP|nT_e3J74Ce1P=+TC]');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

