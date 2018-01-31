<?php
/**
 * Our general admin setup.
 *
 * @package TempAdminUser
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Call our class.
 */
class TempAdminUser_Admin {

	/**
	 * The slugs being used for the menus.
	 */
	public static $hook_slug = 'users_page_temporary-admin-user';

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {

		// Bail if this not the admin.
		if ( ! is_admin() ) {
			return;
		}

		// Load our actions and filters.
		add_action( 'admin_head',                           array( $this, 'user_table_style'            )           );
		add_action( 'admin_enqueue_scripts',                array( $this, 'load_admin_assets'           ),  10      );
		add_action( 'admin_notices',                        array( $this, 'user_process_results'        )           );
		add_action( 'admin_menu',                           array( $this, 'load_settings_menu'          ),  99      );
	}

	/**
	 * Add a small bit of CSS on the user table.
	 *
	 * @return void
	 */
	public function user_table_style() {

		// Call the global $pagenow variable.
		global $pagenow;

		// Bail if this isn't a user page at all.
		if ( empty( $pagenow ) || 'users.php' !== esc_attr( $pagenow ) ) {
			return;
		}

		// And output the CSS.
		echo '<style>' . "\n";
			echo '.column-tmp-admin { width: 32px; text-align: center; }' . "\n";
		echo '</style>' . "\n";
	}

	/**
	 * Load our admin side JS and CSS.
	 *
	 * @todo add conditional loading for the assets.
	 *
	 * @return void
	 */
	public function load_admin_assets( $hook ) {

		// Check my hook before moving forward.
		if ( self::$hook_slug !== esc_attr( $hook ) ) {
			return;
		}

		// Set a file suffix structure based on whether or not we want a minified version.
		$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'temporary-admin-user' : 'temporary-admin-user.min';

		// Set a version for whether or not we're debugging.
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : TMP_ADMIN_USER_VER;

		// Load our CSS file.
		wp_enqueue_style( 'temporary-admin-user', TMP_ADMIN_USER_ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );

		// And our JS.
		// wp_enqueue_script( 'temporary-admin-user', TMP_ADMIN_USER_ASSETS_URL . '/js/' . $file . '.js', array( 'jquery' ), $vers, true );
	}

	/**
	 * Display the results based on some actions.
	 *
	 * @return void
	 */
	public function user_process_results() {

		// Bail if we aren't on the page.
		if ( false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// Check for the flag.
		if ( empty( $_GET['tmp-admin-user-result'] ) ) {
			return;
		}

		// Do the new user success.
		if ( ! empty( $_GET['success'] ) ) {

			// Get the action.
			$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';

			// And handle the notice.
			echo '<div class="notice notice-success is-dismissible tmp-admin-user-message">';
				echo '<p>' . TempAdminUser_Helper::get_admin_messages( $action ) . '</p>';
			echo '</div>';

			// And bail.
			return;
		}

		// Do the error handling.
		if ( empty( $_GET['success'] ) ) {

			// Grab my error code.
			$code   = ! empty( $_GET['errcode'] ) ? sanitize_text_field( $_GET['errcode'] ) : 'unknown';

			// And handle the notice.
			echo '<div class="notice notice-error is-dismissible tmp-admin-user-message">';
				echo '<p>' . TempAdminUser_Helper::get_admin_messages( $code ) . '</p>';
			echo '</div>';

			// And bail.
			return;
		}
	}

	/**
	 * Load our menu item.
	 *
	 * @return void
	 */
	public function load_settings_menu() {

		// Don't even show the menu page for temp users.
		if ( false === $access = TempAdminUser_Users::check_user_perm() ) {
			return;
		}

		// Add our submenu page.
		add_users_page(
			__( 'Temporary Users', 'temporary-admin-user' ),
			__( 'Temporary Users', 'temporary-admin-user' ),
			apply_filters( 'tmp_admin_user_menu_cap', 'manage_options' ),
			TMP_ADMIN_USER_MENU_BASE,
			array( __class__, 'settings_page_view' )
		);
	}

	/**
	 * Our actual admin page for things.
	 *
	 * @return mixed
	 */
	public static function settings_page_view() {

		// Check our user permissions again.
		if ( false === $access = TempAdminUser_Users::check_user_perm() ) {
			wp_die( __( 'You do not have permission to access this page.', 'temporary-admin-user' ) );
		}

		// Get my action link (the menu slug).
		$action = TempAdminUser_Helper::get_menu_link();

		// Wrap the entire thing.
		echo '<div class="wrap tmp-admin-user-settings-wrap">';

			// Handle the title.
			echo '<h1 class="tmp-admin-user-settings-title">' . get_admin_page_title() . '</h1>';

			// Load the new user form.
			echo self::new_user_form( $action );

			// Wrap it in a div.
			echo '<div class="tmp-admin-user-existing-table">';

				// Handle the existing users.
				echo '<h3 class="tmp-admin-user-settings-subtitle">' . esc_html__( 'Existing Users', 'temporary-admin-user' ) . '</h3>';

				// Load the existing user table.
				self::existing_user_table( $action );

			// Close the table thing.
			echo '</div>';

		// Close the entire thing.
		echo '</div>';
	}

	/**
	 * Construct the HTML for the new user portion of the admin page.
	 *
	 * @param  $action  The form action.
	 *
	 * @return html     The HTML of the new user form portion
	 */
	public static function new_user_form( $action = '' ) {

		// Fetch my time ranges.
		$ranges = TempAdminUser_Helper::get_user_durations();

		// Create an empty.
		$build  = '';

		// Begin the form markup for the wrapper on the field box.
		$build .= '<form class="tmp-admin-user-form" id="tmp-admin-user-form-new" action="' . esc_url( $action ) . '" method="post">';

			// Output the hidden action and nonce fields.
			$build .= '<input name="tmp-admin-new-user-request" id="tmp-admin-new-user-request" value="1" type="hidden">';
			$build .= wp_nonce_field( 'tmp-admin-new-user-nonce', 'tmp-admin-new-user-nonce', false, false );

			// Output the table options.
			$build .= '<table class="form-table">';
			$build .= '<tbody>';

			// Handle the email field.
			$build .= '<tr>';
				$build .= '<th scope="row">' . esc_html__( 'Email Address', 'temporary-admin-user' ) . '</th>';
				$build .= '<td>';
					$build .= '<input name="tmp-admin-new-user-email" id="tmp-admin-new-user-email" value="" class="tmp-admin-user-input regular-text" type="email">';
				$build .= '</td>';
			$build .= '</tr>';

			// If we have ranges, show them. otherwise just a hidden field.
			if ( empty( $ranges ) ) {
				$build .= '<input name="tmp-admin-new-user-duration" id="tmp-admin-new-user-duration" value="day" type="hidden">';
			} else {

				$build .= '<tr>';
					$build .= '<th scope="row">' . esc_html__( 'Account Expiration', 'temporary-admin-user' ) . '</th>';
					$build .= '<td>';

					// Output the select field.
					$build .= '<select name="tmp-admin-new-user-duration" id="tmp-admin-new-user-duration" class="tmp-admin-user-input">';

					'<option value="">' . esc_html__( '(Select)', 'temporary-admin-user' ) . '</option>';

					// Loop my frequencies to make the select field.
					foreach ( $ranges as $range => $args ) {
						$build .= '<option value="' . esc_attr( $range ) . '">' . esc_html( $args['label'] ) . '</option>';
					}

					// Close the select.
					$build .= '</select>';

					// Close the row.
					$build .= '</td>';
				$build .= '</tr>';
			}

			// Close up the table.
			$build .= '</tbody>';
			$build .= '</table>';

			// Handle our submit button.
			$build .= get_submit_button( __( 'Create New User', 'temporary-admin-user' ), 'primary', 'tmp-admin-new-user-submit', true, array( 'id' => 'tmp-admin-new-user-submit' ) );

		// Close the  markup for the wrapper on the field box.
		$build .= '</form>';

		// Return the markup.
		return $build;
	}

	/**
	 * Construct the HTML for existing users.
	 *
	 * @return html            The HTML of the new user form portion
	 */
	public static function existing_user_table( $action = '' ) {

		// Bail if no users exist.
		if ( false === $users = TempAdminUser_Users::get_temp_users() ) {

			// Echo out the message.
			echo '<p>' . esc_html__( 'No temporary users have been created.', 'temporary-admin-user' ) . '</p>';

			// And be done.
			return;
		}

		//preprint( $users, true );

		// Call our class.
		$user_table = new TemporaryAdminUsers_Table();

		// And output the table.
		$user_table->prepare_items();

		// And handle the display
		echo '<form class="tmp-admin-user-form" id="tmp-admin-user-form-table" action="' . esc_url( $action ) . '" method="post">';

		// The actual table itself.
		$user_table->display();

		// And close it up.
		echo '</form>';
	}

	// End our class.
}

// Call our class.
$TempAdminUser_Admin = new TempAdminUser_Admin();
$TempAdminUser_Admin->init();
