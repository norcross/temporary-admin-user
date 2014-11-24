<?php
/**
 * Temporary Admin User - Admin Module
 *
 * Contains all the admin related functionality for individual sites
 *
 * @package Temporary Admin User
 */
/*  Copyright 2014 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

 // Start up the engine
class TempAdminUser_Admin {

	/**
	 * this is our constructor.
	 * there are many like it, but this one is mine
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts',            array( $this, 'scripts_styles'          ),  10      );
		add_action( 'admin_notices',                    array( $this, 'admin_notices'           ),  10      );
		add_action( 'admin_menu',                       array( $this, 'admin_menu'              )           );
	}

	/**
	 * load our CSS and JS files for the new user creation page
	 *
	 * @param  string  $hook      the page hook for the admin
	 *
	 * @return null
	 */
	public function scripts_styles( $hook ) {

		// bail if we aren't on our page
		if ( empty( $hook ) || ! empty( $hook ) && $hook !== 'users_page_temporary-admin-user' ) {
			return;
		}

		// set our file suffixes
		$css_fx = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';
		$js_fx  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.js' : '.min.js';

		// and our version
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : TMP_ADMIN_USER_VER;

		// load te CSS file
		wp_enqueue_style( 'tempadmin-user', plugins_url( 'css/tmpadmin' . $css_fx, __FILE__ ), array(), $vers, 'all' );

		// load and localize
		wp_enqueue_script( 'tempadmin-user', plugins_url( 'js/tmpadmin' . $js_fx, __FILE__ ), array( 'jquery' ), $vers, true );
		wp_localize_script( 'tempadmin-user', 'tempAdminData', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'makeNonce' => wp_create_nonce( 'tempadmin_make_js' ),
			'deltNonce' => wp_create_nonce( 'tempadmin_delete_js' ),
			'demtNonce' => wp_create_nonce( 'tempadmin_demote_js' ),
			'noEmail'   => TempAdminUser_Utilities::get_admin_messages( 'noemail' )
		));
	}

	/**
	 * display message on saved settings
	 * @return [HTML] message above page
	 */
	public function admin_notices() {

		// first check to make sure we're on our settings
		if ( empty( $_REQUEST['page'] ) || empty( $_REQUEST['page'] ) && $_REQUEST['page'] !== 'temporary-admin-user' ) {
			return;
		}

		// if we have neither success or error, bail
		if ( empty( $_REQUEST['success'] ) && empty( $_REQUEST['error'] ) ) {
			return;
		}

		// if we have success, just return that and go on your way
		if ( ! empty( $_REQUEST['success'] ) ) {

			// get my success type
			$type   = ! empty( $_REQUEST['type'] ) ? $_REQUEST['type'] : 'created';

			// display the message
			echo '<div id="message" class="updated below-h2 tempadmin-message">';
				echo '<p>' . TempAdminUser_Utilities::get_admin_messages( $type ) . '</p>';
			echo '</div>';

			return;
		}

		// get my type (error code)
		$type   = ! empty( $_REQUEST['errcode'] ) ? $_REQUEST['errcode'] : '';

		// set an empty
		$text   = '';

		// now our error code checks
		switch ( $type ) {

			case 'NONCE_FAILED':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'nonce' );
				break;

			case 'NO_EMAIL':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'noemail' );
				break;

			case 'USED_EMAIL':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'usedemail' );
				break;

			case 'NO_TYPE':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'notype' );
				break;

			case 'BAD_TYPE':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'badtype' );
				break;

			case 'NO_CREATE':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'nocreate' );
				break;

			case 'NO_USERS':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'nousers' );
				break;

			case 'NO_DEMOTE':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'nodemote' );
				break;

			case 'NO_DELETE':

				$text   = TempAdminUser_Utilities::get_admin_messages( 'nodelete' );
				break;

			default:

				$text   = TempAdminUser_Utilities::get_admin_messages( 'default' );

		// end all case breaks
		}

		echo '<div id="message" class="error below-h2 tempadmin-message">';
			echo '<p>' . esc_attr( $text ) . '</p>';
		echo '</div>';

		return;
	}

	/**
	 * create admin page for temporary users
	 *
	 * @return null
	 */
	public function admin_menu() {
		add_users_page( __( 'Temporary Users', 'temporary-admin-user' ), __( 'Temporary Users', 'temporary-admin-user' ), apply_filters( 'tempadmin_user_cap', 'manage_options' ), 'temporary-admin-user', array( __class__, 'admin_settings' ) );
	}

	/**
	 * display admin page for creating and managing temporary users
	 *
	 * @return null
	 */
	public static function admin_settings() {

		// check our user permissions again
		if ( ! current_user_can( apply_filters( 'tempadmin_user_cap', 'manage_options' ) ) ) {
			echo __( 'You do not have permission to access this page.', 'temporary-admin-user' );
			die();
		}

		// begin the markup for the settings page
		echo '<div class="wrap tempadmin-settings-wrap">';
			echo '<h2>' . __( 'Manage Temporary Users', 'temporary-admin-user' ) . '</h2>';

			// display our new user form
			echo '<div class="tempadmin-settings-box tempadmin-new-user-box">';
				echo '<h3>' . __( 'Create New User', 'temporary-admin-user' ) . '</h3>';
				echo TempAdminUser_Layout::new_user_form();
			echo '</div>';

			// display our existing active users
			echo '<div id="tempadmin-users-active" class="tempadmin-settings-box tempadmin-users-list-box">';
			echo '<form method="post">';
				echo '<div class="tempadmin-users-list-title-row">';
					echo '<h3>';
						echo '<span class="tempadmin-users-title-text">' . __( 'Active User Accounts', 'temporary-admin-user' ) . ' </span>';
						echo '<span class="tempadmin-users-list-action tempadmin-users-active-action">';
							echo TempAdminUser_Layout::user_action_button( __( 'Demote Selected Users', 'temporary-admin-user' ), 'demote' );
						echo '</span>';
					echo '</h3>';
				echo '</div>';

				echo '<div class="tempadmin-users-list-data">';
					echo TempAdminUser_Layout::existing_user_list( 'administrator' );
				echo '</div>';

			echo '</form>';
			echo '</div>';

			// display our existing expired users
			echo '<div id="tempadmin-users-expired" class="tempadmin-settings-box tempadmin-users-list-box">';
			echo '<form method="post">';

				echo '<div class="tempadmin-users-list-title-row">';
					echo '<h3>';
						echo '<span class="tempadmin-users-title-text">' . __( 'Expired User Accounts', 'temporary-admin-user' ) . ' </span>';
						echo '<span class="tempadmin-users-list-action tempadmin-users-expired-action">';
							echo TempAdminUser_Layout::user_action_button( __( 'Delete Selected Users', 'temporary-admin-user' ), 'delete' );
						echo '</span>';
					echo '</h3>';
				echo '</div>';

				echo '<div class="tempadmin-users-list-data">';
					echo TempAdminUser_Layout::existing_user_list( 'subscriber' );
				echo '</div>';

			echo '</form>';
			echo '</div>';

		// close the markup for the settings page
		echo '</div>';
	}

/// end class
}


// Instantiate our class
new TempAdminUser_Admin();
