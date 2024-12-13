<?php
/**
 * Set up and render the markup pieces.
 *
 * @package TempAdminUser
 */

// Call our namepsace.
namespace Norcross\TempAdminUser\Admin\Markup;

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Queries as Queries;

/**
 * Construct the HTML for the new user portion of the admin page.
 *
 * @param  boolean $echo  Whether to echo or return it.
 *
 * @return HTML           The HTML of the new user form portion.
 */
function render_new_user_form( $echo = true ) {

	// Fetch my time ranges.
	$ranges = Queries\get_user_durations();

	// Get my form action link.
	$action = Helpers\get_admin_menu_link();

	// Create an empty.
	$build  = '';

	// Begin the form markup for the wrapper on the field box.
	$build .= '<form class="tmp-admin-user-form" id="tmp-admin-user-form-new" action="' . esc_url( $action ) . '" method="post">';

		// Output the nonce field.
		$build .= wp_nonce_field( Core\NONCE_PREFIX . 'new_user', 'tmp-admin-new-user-nonce', false, false );

		// Output the table options.
		$build .= '<table class="form-table">';
		$build .= '<tbody>';

		// Handle the email field.
		$build .= '<tr>';
			$build .= '<th scope="row">' . esc_html__( 'Email Address', 'temporary-admin-user' ) . '</th>';
			$build .= '<td>';
				$build .= '<input autocomplete="off" name="tmp-admin-new-user-email" id="tmp-admin-new-user-email" value="" class="tmp-admin-user-input regular-text" type="email" data-1p-ignore>';
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

					// Add the "empty" one.
					$build .= '<option value="0">' . esc_html__( '(Select)', 'temporary-admin-user' ) . '</option>';

					// Loop my frequencies to make the select field.
					foreach ( $ranges as $range => $args ) {
						$build .= '<option value="' . esc_attr( $range ) . '" ' . selected( $range, 'day', false ) . '>' . esc_html( $args['label'] ) . '</option>';
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
		$build .= '<p class="submit tmp-admin-new-user-submit-wrap">';
			$build .= '<button class="button button-primary" id="tmp-admin-new-user-submit" name="tmp-admin-new-user-submit" type="submit" value="go">' . esc_html__( 'Create New User', 'temporary-admin-user' ) . '</button>';
		$build .= '</p>';

	// Close the  markup for the wrapper on the field box.
	$build .= '</form>';

	// Return the markup.
	if ( false === $echo ) {
		return $build;
	}

	// Show it.
	echo $build;
}

/**
 * Build the markup for an admin notice.
 *
 * @param  string  $notice       The actual message to display.
 * @param  string  $result       Which type of message it is.
 * @param  boolean $dismiss      Whether it should be dismissable.
 * @param  boolean $show_button  Show the dismiss button (for Ajax calls).
 * @param  boolean $echo         Whether to echo out the markup or return it.
 *
 * @return HTML
 */
function display_admin_notice_markup( $notice = '', $result = 'error', $dismiss = true, $show_button = false, $echo = true ) {

	// Bail without the required message text.
	if ( empty( $notice ) ) {
		return;
	}

	// Set my base class.
	$class  = 'notice notice-' . esc_attr( $result ) . ' tmp-admin-users-admin-notice-message';

	// Add the dismiss class.
	if ( $dismiss ) {
		$class .= ' is-dismissible';
	}

	// Set an empty.
	$build  = '';

	// Start the notice markup.
	$build .= '<div class="' . esc_attr( $class ) . '">';

		// Display the actual message.
		$build .= '<p><strong>' . wp_kses_post( $notice ) . '</strong></p>';

		// Show the button if we set dismiss and button variables.
		$build .= $dismiss && $show_button ? '<button type="button" class="notice-dismiss">' . screen_reader_text() . '</button>' : '';

	// And close the div.
	$build .= '</div>';

	// Echo it if requested.
	if ( ! empty( $echo ) ) {
		echo $build; // WPCS: XSS ok.
	}

	// Just return it.
	return $build;
}
