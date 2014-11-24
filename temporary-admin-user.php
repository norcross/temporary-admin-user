<?php
/*
Plugin Name: Temporary Admin User
Plugin URI: http://andrewnorcross.com/plugins/
Description: Create a temporary WordPress admin user to provide access on support issues, etc.
Version: 0.0.1
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

	Copyright 2013 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
