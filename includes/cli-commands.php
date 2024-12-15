<?php
/**
 * The functionality tied to the WP-CLI stuff.
 *
 * @package TempAdminUser
 */

// Call our namepsace (same as the base).
namespace Norcross\TempAdminUser;

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Process as Process;

// Pull in the CLI items.
use WP_CLI;
use WP_CLI_Command;

/**
 * Add CLI commands to handle individual and bulk user changes.
 */
class TempAdminUserCommands extends WP_CLI_Command {

	/**
	 * Create a new temporary user.
	 *
	 * ## OPTIONS
	 *
	 * [--email]
	 * : The email address to use for the new admin.
	 *
	 * [--duration]
	 * : How long the account will be active for.
	 * ---
	 * default: day
	 * options:
	 *   - fifteen
	 *   - halfhour
	 *   - hour
	 *   - day
	 *   - week
	 *   - month
	 *   - year
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp tmp-admin-user new-user --email=someone@example.com
	 *     wp tmp-admin-user new-user --email=someone@example.com --duration=week
	 *
	 * @alias new-user
	 *
	 * @when after_wp_load
	 */
	function create_new_user( $args = [], $assoc_args = [] ) {

		// Parse out the associatives.
		$parse_cli_args = wp_parse_args( $assoc_args, [
			'email'    => '',
			'duration' => 'day',
		]);

		// Make sure we have an email.
		if ( empty( $parse_cli_args['email'] ) ) {
			WP_CLI::error( __( 'An email address is required.', 'temporary-admin-user' ) );
		}

		// Now sanitize the email.
		$cleaned_email  = sanitize_email( $parse_cli_args['email'] );

		// Make sure we have an email after.
		if ( empty( $cleaned_email ) ) {
			WP_CLI::error( __( 'The provided email address was invalid. Please try again.', 'temporary-admin-user' ) );
		}

		// Get the data for the selected duration.
		$get_range_data = Helpers\get_user_durations( sanitize_text_field( $parse_cli_args['duration'] ) );

		// Bail without an existing date range to work with.
		if ( empty( $get_range_data ) ) {
			WP_CLI::error( __( 'The provided duration was not valid. Please try again.', 'temporary-admin-user' ) );
		}

		// Now attempt the new user.
		$maybe_new_user = Process\create_new_user( $cleaned_email, sanitize_text_field( $parse_cli_args['duration'] ) );

		// Handle a failed user creation.
		if ( empty( $maybe_new_user ) ) {
			WP_CLI::error( __( 'There was an error creating the user. Please check your error logs.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( sprintf( __( 'Success! A new user was created with the following ID: %d ', 'temporary-admin-user' ), absint( $maybe_new_user ) ) );
		WP_CLI::halt( 0 );
	}

	// End all custom CLI commands.
}
