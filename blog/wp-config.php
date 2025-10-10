<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u590097272_H53t1' );

/** Database username */
define( 'DB_USER', 'u590097272_TAPhE' );

/** Database password */
define( 'DB_PASSWORD', 'zlhXaS7RQ9' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '+}&w@esn-C7jm(r.>Y/jhDr@xjm]|;T;f^,rr7f3U>#.lV3fWr_$)KVZ>xzk6{Kj' );
define( 'SECURE_AUTH_KEY',   'q,n37ogCkX/@uOOCZ++zF>$zTl}@!5c[@LEmjEwXG0P$&jsQHc<<H/d|Y;ie! B;' );
define( 'LOGGED_IN_KEY',     '@JF48_wh=n4gr YT&@3hPc&g`qi%YE3QSwXtH$KO*/ t|(Tjop= kl/3obbIaz2Y' );
define( 'NONCE_KEY',         'x*uv8fC[QpY:^c2#XHKT$G3!l@s5!?FiN@-lp!KOBv(_k#4,i{PP|<9e,xAY.wW;' );
define( 'AUTH_SALT',         '_+Z}.L@e3Pa(aKEm`asZv9+j::{&+>nY4$ez^5=2q^7qvOmum>,p&i3d+xE&U`}X' );
define( 'SECURE_AUTH_SALT',  'f3Zm[#ee[+0`]};sn}LvII783R~|<vJ1`DKxzr4@<$qTl~1g}];amG%]FtaR4(%K' );
define( 'LOGGED_IN_SALT',    'DHS`q-4S^88(Qg^5%IBUt+mFhx+[FD(n7~Y./!QFykm4p-<JH@#l~]z2!30+,#UE' );
define( 'NONCE_SALT',        'C-q;Nx*l}7KR/gmJDHS&8]!ZgJvv@YkuV?KrNWKF[7y]M1mYBwfF@.p7SNuj9RKY' );
define( 'WP_CACHE_KEY_SALT', 'rIisk,f=8=K^eH*#p=fQg:=r8<l=5vq[RzHXxFUIti$1qH#6<~?)Ug:G;`$,PlO>' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'eeb79082eba4658e93027d17262eac56' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
