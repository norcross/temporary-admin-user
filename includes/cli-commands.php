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

		// Set the duration.
		$set_duration   = sanitize_text_field( $parse_cli_args['duration'] );

		// Get the data for the selected duration.
		$get_range_data = Helpers\get_user_durations( $set_duration );

		// Bail without an existing date range to work with.
		if ( empty( $get_range_data ) ) {
			WP_CLI::error( __( 'The provided duration was not valid. Please try again.', 'temporary-admin-user' ) );
		}

		// Now attempt the new user.
		$maybe_new_user = Process\create_new_user( $cleaned_email, $set_duration );

		// Handle a failed user creation.
		if ( empty( $maybe_new_user ) ) {
			WP_CLI::error( __( 'There was an error creating the user. Please check your error logs.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( sprintf( __( 'Success! A new user was created with the following ID: %d ', 'temporary-admin-user' ), absint( $maybe_new_user ) ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Promote a current user.
	 *
	 * ## OPTIONS
	 *
	 * [--id]
	 * : The existing user ID.
	 *
	 * [--email]
	 * : The existing user email.
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
	 *     wp tmp-admin-user promote-user --id=50
	 *     wp tmp-admin-user promote-user --email=someone@example.com
	 *
	 * @alias promote-user
	 *
	 * @when after_wp_load
	 */
	function promote_current_user( $args = [], $assoc_args = [] ) {

		// Parse out the associatives.
		$parse_cli_args = wp_parse_args( $assoc_args, [
			'id'       => 0,
			'email'    => '',
			'duration' => 'day',
		]);

		// Make sure we have an ID or an email.
		if ( empty( $parse_cli_args['id'] ) && empty( $parse_cli_args['email'] ) ) {
			WP_CLI::error( __( 'An email address or user ID is required.', 'temporary-admin-user' ) );
		}

		// Set the duration.
		$set_duration   = sanitize_text_field( $parse_cli_args['duration'] );

		// Get the data for the selected duration.
		$get_range_data = Helpers\get_user_durations( $set_duration );

		// Bail without an existing date range to work with.
		if ( empty( $get_range_data ) ) {
			WP_CLI::error( __( 'The provided duration was not valid. Please try again.', 'temporary-admin-user' ) );
		}

		// Set an empty.
		$valid_user_id  = 0;

		// Check by user ID first.
		if ( ! empty( $parse_cli_args['id'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'id', absint( $parse_cli_args['id'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Check by email now first.
		if ( empty( $valid_user_id ) && ! empty( $parse_cli_args['email'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'email', sanitize_email( $parse_cli_args['email'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Make sure a valid ID was determined.
		if ( empty( $valid_user_id ) ) {
			WP_CLI::error( __( 'A valid user could not be found with that ID or email address. Please try again.', 'temporary-admin-user' ) );
		}

		// Check if the user is one we created.
		$check_creation = Helpers\confirm_user_via_plugin( $valid_user_id );

		// Flag if the user isn't ours.
		if ( empty( $check_creation ) ) {
			WP_CLI::error( __( 'The requested user was not created with this plugin and cannot be modified.', 'temporary-admin-user' ) );
		}

		// Now promote it.
		$promote_user   = Process\promote_existing_user( $valid_user_id, $set_duration );

		// Flag if the restriction didn't work.
		if ( empty( $promote_user ) ) {
			WP_CLI::error( __( 'The requested user could not be promoted. Please try again.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( __( 'Success! The requested user was promoted.', 'temporary-admin-user' ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Restrict a current user.
	 *
	 * ## OPTIONS
	 *
	 * [--id]
	 * : The existing user ID.
	 *
	 * [--email]
	 * : The existing user email.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tmp-admin-user restrict-user --id=50
	 *     wp tmp-admin-user restrict-user --email=someone@example.com
	 *
	 * @alias restrict-user
	 *
	 * @when after_wp_load
	 */
	function restrict_current_user( $args = [], $assoc_args = [] ) {

		// Parse out the associatives.
		$parse_cli_args = wp_parse_args( $assoc_args, [
			'id'    => 0,
			'email' => '',
		]);

		// Make sure we have an ID or an email.
		if ( empty( $parse_cli_args['id'] ) && empty( $parse_cli_args['email'] ) ) {
			WP_CLI::error( __( 'An email address or user ID is required.', 'temporary-admin-user' ) );
		}

		// Set an empty.
		$valid_user_id  = 0;

		// Check by user ID first.
		if ( ! empty( $parse_cli_args['id'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'id', absint( $parse_cli_args['id'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Check by email now first.
		if ( empty( $valid_user_id ) && ! empty( $parse_cli_args['email'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'email', sanitize_email( $parse_cli_args['email'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Make sure a valid ID was determined.
		if ( empty( $valid_user_id ) ) {
			WP_CLI::error( __( 'A valid user could not be found with that ID or email address. Please try again.', 'temporary-admin-user' ) );
		}

		// Check if the user is one we created.
		$check_creation = Helpers\confirm_user_via_plugin( $valid_user_id );

		// Flag if the user isn't ours.
		if ( empty( $check_creation ) ) {
			WP_CLI::error( __( 'The requested user was not created with this plugin and cannot be modified.', 'temporary-admin-user' ) );
		}

		// Now restrict it.
		$restrict_user  = Process\restrict_existing_user( $valid_user_id );

		// Flag if the restriction didn't work.
		if ( empty( $restrict_user ) ) {
			WP_CLI::error( __( 'The requested user could not be restricted. Please try again.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( __( 'Success! The requested user was restricted.', 'temporary-admin-user' ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Restrict all existing users.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tmp-admin-user restrict-all-users
	 *
	 * @alias restrict-all-users
	 *
	 * @when after_wp_load
	 */
	function restrict_all_users( $args = [], $assoc_args = [] ) {

		// Output the confirm.
		WP_CLI::confirm( __( 'Are you sure you want to restrict all users?', 'temporary-admin-user' ), $assoc_args );

		// Now restrict them.
		$restrict_users = Process\restrict_all_users();

		// Flag if the restriction didn't work.
		if ( empty( $restrict_users ) ) {
			WP_CLI::error( __( 'There was an error with one or more of the users. Please check the admin area.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( __( 'Success! All existing users have been restricted.', 'temporary-admin-user' ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Delete a current user.
	 *
	 * ## OPTIONS
	 *
	 * [--id]
	 * : The existing user ID.
	 *
	 * [--email]
	 * : The existing user email.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tmp-admin-user delete-user --id=50
	 *     wp tmp-admin-user delete-user --email=someone@example.com
	 *
	 * @alias delete-user
	 *
	 * @when after_wp_load
	 */
	function delete_current_user( $args = [], $assoc_args = [] ) {

		// Parse out the associatives.
		$parse_cli_args = wp_parse_args( $assoc_args, [
			'id'    => 0,
			'email' => '',
		]);

		// Make sure we have an ID or an email.
		if ( empty( $parse_cli_args['id'] ) && empty( $parse_cli_args['email'] ) ) {
			WP_CLI::error( __( 'An email address or user ID is required.', 'temporary-admin-user' ) );
		}

		// Set an empty.
		$valid_user_id  = 0;

		// Check by user ID first.
		if ( ! empty( $parse_cli_args['id'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'id', absint( $parse_cli_args['id'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Check by email now first.
		if ( empty( $valid_user_id ) && ! empty( $parse_cli_args['email'] ) ) {

			// Attempt to get the user.
			$maybe_has_user = get_user_by( 'email', sanitize_email( $parse_cli_args['email'] ) );

			// If it worked, we have a valid ID. So set it.
			if ( ! empty( $maybe_has_user ) ) {
				$valid_user_id  = $maybe_has_user->ID;
			}
		}

		// Make sure a valid ID was determined.
		if ( empty( $valid_user_id ) ) {
			WP_CLI::error( __( 'A valid user could not be found with that ID or email address. Please try again.', 'temporary-admin-user' ) );
		}

		// Output the confirm.
		WP_CLI::confirm( __( 'Are you sure you want to delete this user?', 'temporary-admin-user' ), $assoc_args );

		// Check if the user is one we created.
		$check_creation = Helpers\confirm_user_via_plugin( $valid_user_id );

		// Flag if the user isn't ours.
		if ( empty( $check_creation ) ) {
			WP_CLI::error( __( 'The requested user was not created with this plugin and cannot be modified.', 'temporary-admin-user' ) );
		}

		// Now delete it.
		$delete_user    = Process\delete_existing_user( $valid_user_id );

		// Flag if the restriction didn't work.
		if ( empty( $delete_user ) ) {
			WP_CLI::error( __( 'The requested user could not be deleted. Please try again.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( __( 'Success! The requested user was deleted.', 'temporary-admin-user' ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * Delete all existing users.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tmp-admin-user delete-all-users
	 *
	 * @alias delete-all-users
	 *
	 * @when after_wp_load
	 */
	function delete_all_users( $args = [], $assoc_args = [] ) {

		// Output the confirm.
		WP_CLI::confirm( __( 'Are you sure you want to delete all users?', 'temporary-admin-user' ), $assoc_args );

		// Now delete them.
		$delete_users   = Process\delete_all_users();

		// Flag if the restriction didn't work.
		if ( empty( $delete_users ) ) {
			WP_CLI::error( __( 'There was an error with one or more of the users. Please check the admin area.', 'temporary-admin-user' ) );
		}

		// Tell me I did a good job.
		WP_CLI::success( __( 'Success! All existing users have been deleted.', 'temporary-admin-user' ) );
		WP_CLI::halt( 0 );
	}

	// End all custom CLI commands.
}
