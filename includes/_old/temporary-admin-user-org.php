<?php
/**
 * Plugin Name: Temporary Admin User
 * Plugin URI:  https://github.com/norcross/temporary-admin-user
 * Description: An extention for creating WooCommerce data.
 * Version:     0.0.2
 * Author:      Andrew Norcross
 * Author URI:  http://andrewnorcross.com
 * Text Domain: temporary-admin-user
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package TempAdminUser
 */

/*
Plugin Name: Temporary Admin User
Plugin URI: http://andrewnorcross.com/plugins/
Description: Create a temporary WordPress admin user to provide access on support issues, etc.
Version: 0.0.1
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

	The MIT License (MIT)

	Copyright (c) 2014 Andrew Norcross

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
*/

if( ! defined( 'TMP_ADMIN_USER_BASE ' ) ) {
	define( 'TMP_ADMIN_USER_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'TMP_ADMIN_USER_DIR' ) ) {
	define( 'TMP_ADMIN_USER_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'TMP_ADMIN_USER_VER' ) ) {
	define( 'TMP_ADMIN_USER_VER', '0.0.1' );
}

// Start up the engine
class TempAdminUser_Core
{
	/**
	 * Static property to hold our singleton instance
	 * @var TempAdminUser_Core
	 */
	static $instance = false;

	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return TempAdminUser_Core
	 */
	private function __construct() {
		add_action( 'plugins_loaded',                   array( $this, 'textdomain'              )           );
		add_action( 'plugins_loaded',                   array( $this, 'load_files'              )           );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {

		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * load textdomain for international goodness
	 *
	 * @return null
	 */
	public function textdomain() {
		load_plugin_textdomain( 'temporary-admin-user', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * load our secondary files
	 *
	 * @return void
	 */
	public function load_files() {

		// load the files needed across the board
		require_once( TMP_ADMIN_USER_DIR . 'lib/process.php'   );
		require_once( TMP_ADMIN_USER_DIR . 'lib/utilities.php' );

		// load the remaining files on admin only
		if ( is_admin() ) {
			require_once( TMP_ADMIN_USER_DIR . 'lib/admin.php'     );
			require_once( TMP_ADMIN_USER_DIR . 'lib/layout.php'    );
		}
	}

/// end class
}

// Instantiate our class
$TempAdminUser_Core = TempAdminUser_Core::getInstance();
