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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Call our class.
 */
final class TempAdminUser_Core {

	/**
	 * TempAdminUser_Core instance.
	 *
	 * @access private
	 * @since  1.0
	 * @var    TempAdminUser_Core The one true TempAdminUser_Core
	 */
	private static $instance;

	/**
	 * The version number of TempAdminUser_Core.
	 *
	 * @access private
	 * @since  1.0
	 * @var    string
	 */
	private $version = '0.0.2';

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function instance() {

		// Run the check to see if we have the instance yet.
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof TempAdminUser_Core ) ) {

			// Set our instance.
			self::$instance = new TempAdminUser_Core;

			// Set my plugin constants.
			self::$instance->define_constants();

			// Run our version compare.
			if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {

				// Deactivate the plugin.
				deactivate_plugins( TMP_ADMIN_USER_BASE );

				// And display the notice.
				wp_die( sprintf( __( 'Your current version of PHP is below the minimum version required by the Temporary Admin User plugin. Please contact your host and request that your version be upgraded to 5.6 or later. <a href="%s">Click here</a> to return to the plugins page.', 'temporary-admin-user' ), admin_url( '/plugins.php' ) ) );
			}

			// Set my file includes.
			self::$instance->includes();

			// Load our textdomain.
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		// And return the instance.
		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 0.0.1
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'temporary-admin-user' ), '0.0.1' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 0.0.1
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'temporary-admin-user' ), '0.0.1' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function define_constants() {

		// Define our file base.
		if ( ! defined( 'TMP_ADMIN_USER_BASE' ) ) {
			define( 'TMP_ADMIN_USER_BASE', plugin_basename( __FILE__ ) );
		}

		// Set our base directory constant.
		if ( ! defined( 'TMP_ADMIN_USER_DIR' ) ) {
			define( 'TMP_ADMIN_USER_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'TMP_ADMIN_USER_URL' ) ) {
			define( 'TMP_ADMIN_USER_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin root file.
		if ( ! defined( 'TMP_ADMIN_USER_FILE' ) ) {
			define( 'TMP_ADMIN_USER_FILE', __FILE__ );
		}

		// Set our includes directory constant.
		if ( ! defined( 'TMP_ADMIN_USER_INCLS' ) ) {
			define( 'TMP_ADMIN_USER_INCLS', __DIR__ . '/includes' );
		}

		// Set our menu base slug constant.
		if ( ! defined( 'TMP_ADMIN_USER_MENU_BASE' ) ) {
			define( 'TMP_ADMIN_USER_MENU_BASE', 'temporary-admin-user' );
		}

		// Set our version constant.
		if ( ! defined( 'TMP_ADMIN_USER_VER' ) ) {
			define( 'TMP_ADMIN_USER_VER', $this->version );
		}
	}

	/**
	 * Load our actual files in the places they belong.
	 *
	 * @return void
	 */
	public function includes() {

		// And load our classes.
		require_once TMP_ADMIN_USER_INCLS . '/class-helper.php';
		require_once TMP_ADMIN_USER_INCLS . '/class-users.php';

		// Load our admin-specific items.
		if ( is_admin() ) {
			require_once TMP_ADMIN_USER_INCLS . '/class-admin.php';
		}

		// Load our install, cron, deactivate, and uninstall items.
		require_once TMP_ADMIN_USER_INCLS . '/activate.php';
		require_once TMP_ADMIN_USER_INCLS . '/deactivate.php';
		require_once TMP_ADMIN_USER_INCLS . '/uninstall.php';
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory.
		$lang_dir = dirname( plugin_basename( TMP_ADMIN_USER_FILE ) ) . '/languages/';

		/**
		 * Filters the languages directory path to use for TempAdminUser.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'tmp_admin_user_languages_dir', $lang_dir );

		// Traditional WordPress plugin locale filter.

		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used in TempAdminUser.
		 *
		 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'temporary-admin-user' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'temporary-admin-user', $locale );

		// Setup paths to current locale file.
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/temporary-admin-user/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/temporary-admin-user/ folder
			load_textdomain( 'temporary-admin-user', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/temporary-admin-user/languages/ folder
			load_textdomain( 'temporary-admin-user', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'temporary-admin-user', false, $lang_dir );
		}
	}

	// End our class.
}

/**
 * The main function responsible for returning the one true TempAdminUser_Core
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $temp_admin_user = temp_admin_user(); ?>
 *
 * @since 1.0
 * @return TempAdminUser_Core The one true TempAdminUser_Core Instance
 */
function temp_admin_user() {
	return TempAdminUser_Core::instance();
}
temp_admin_user();
