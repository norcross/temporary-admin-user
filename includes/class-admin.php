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
		add_action( 'admin_head',                           array( $this, 'add_settings_css'            )           );
		add_action( 'admin_notices',                        array( $this, 'user_process_results'        )           );
		add_action( 'admin_menu',                           array( $this, 'load_settings_menu'          ),  99      );
	}

	/**
	 * Display a small bit of CSS in the admin head.
	 */
	public function add_settings_css() {

		// Bail if we aren't on the page.
		if ( false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// And output the CSS.
		echo '<style>' . "\n";
			echo 'form.tmp-admin-user-form tr th { width: 140px; padding-bottom: 5px; }' . "\n";
			echo 'form.tmp-admin-user-form tr td { padding-bottom: 5px; }' . "\n";
			echo 'form.tmp-admin-user-form .tmp-admin-user-input { display: inline-block; vertical-align: middle; margin-right: 5px; }' . "\n";
			echo 'form.tmp-admin-user-form .tmp-admin-user-description { display: inline-block; vertical-align: middle; }' . "\n";
			echo 'form.tmp-admin-user-form .tmp-admin-user-label { display: inline-block; vertical-align: middle; font-size: 13px; font-style: italic; }' . "\n";
		echo '</style>' . "\n";
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

			// And handle the notice.
			echo '<div class="notice notice-success is-dismissible tmp-admin-user-message">';
				echo '<p>' . TempAdminUser_Helper::get_admin_messages( 'created' ) . '</p>';
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
			apply_filters( 'tmp_admin_user_cap', 'manage_options' ),
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

		// Close the entire thing.
		echo '</div>';
	}

	/**
	 * Construct the HTML for the new user portion of the admin page.
	 *
	 * @param  string $action  The form action link.
	 *
	 * @return html            The HTML of the new user form portion
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
	 * Set up and process a redirect.
	 *
	 * @param  array  $args  The redirect args.
	 *
	 * @return void
	 */
	public static function admin_page_redirect( $args = array() ) {

		// Bail if we aren't on the page.
		if ( false === $check = TempAdminUser_Helper::check_admin_page() ) {
			return;
		}

		// Check my args.
		$args   = ! empty( $args ) ? wp_parse_args( $args, array( 'tmp-admin-user-result' => 1 ) ) : array( 'tmp-admin-user-result' => 1 );

		// Create the args and add my link.
		$setup  = add_query_arg( $args, TempAdminUser_Helper::get_menu_link() );

		// Do the redirect.
		wp_safe_redirect( $setup );
		exit();
	}

	// End our class.
}

// Call our class.
$TempAdminUser_Admin = new TempAdminUser_Admin();
$TempAdminUser_Admin->init();
