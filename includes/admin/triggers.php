<?php
/**
 * Handle the processing involves.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Admin\Triggers;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Process as Process;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\new_user_form_request' );
add_action( 'admin_init', __NAMESPACE__ . '\modify_user_action_request' );

/**
 * Add a new user when requested on the form.
 *
 * @return void
 */
function new_user_form_request() {

	// Confirm we requested this action.
	$confirm_action = filter_input( INPUT_POST, 'tmp-admin-new-user-submit', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening after this.

	// Make sure it is what we want.
	if ( empty( $confirm_action ) || 'go' !== $confirm_action ) {
		return;
	}

	// Bail on a non authorized user.
	if ( ! current_user_can( 'promote_users' ) ) { // phpcs:ignore -- the nonce check is happening soon.
		wp_die( esc_html__( 'You are not authorized to perform this function.', 'temporary-admin-user' ), __( 'Temporary Admin Users', 'temporary-admin-user' ), [ 'back_link' => true ] );
	}

	// Make sure we have a nonce.
	$confirm_nonce  = filter_input( INPUT_POST, 'tmp-admin-new-user-nonce', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening after this.

	// Handle the nonce check.
	if ( empty( $confirm_nonce ) || ! wp_verify_nonce( $confirm_nonce, Core\NONCE_PREFIX . 'new_user' ) ) {

		// Let them know they had a failure.
		wp_die( esc_html__( 'There was an error validating the nonce.', 'temporary-admin-user' ), esc_html__( 'Temporary Admin Users', 'temporary-admin-user' ), [ 'back_link' => true ] );
	}

	// Now get the two items we needed to be passed.
	$confirm_email  = filter_input( INPUT_POST, 'tmp-admin-new-user-email', FILTER_SANITIZE_EMAIL );
	$confirm_durtn  = filter_input( INPUT_POST, 'tmp-admin-new-user-duration', FILTER_SANITIZE_SPECIAL_CHARS );

	// Bail without email.
	if ( empty( $confirm_email ) ) {
		Helpers\redirect_admin_action_result( 'no-email' );
	}

	// Check if the email address exists.
	if ( email_exists( $confirm_email ) ) {
		Helpers\redirect_admin_action_result( 'email-exists' );
	}

	// Bail without a duration.
	if ( empty( $confirm_durtn ) ) {
		Helpers\redirect_admin_action_result( 'no-duration' );
	}

	// OK, we got this far, now make a new user.
	$maybe_new_user = Process\create_new_user( $confirm_email, $confirm_durtn );

	// Handle a failed user creation.
	if ( empty( $maybe_new_user ) ) {
		Helpers\redirect_admin_action_result( 'failed-new-user' );
	}

	// We are good, so redirect with the affermative.
	Helpers\redirect_admin_action_result( '', 'new-user-created', true );
}

/**
 * Handle one of the modification requests.
 *
 * @return void
 */
function modify_user_action_request() {

	// Confirm we are on the proper page..
	$confirm_admin  = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening soon.

	// Make sure it is what we want.
	if ( empty( $confirm_admin ) || Core\MENU_ROOT !== $confirm_admin ) {
		return;
	}

	// Confirm we requested this user modification.
	$confirm_modify = filter_input( INPUT_GET, 'tmp-admin-single-user-modify', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening soon.

	// Make sure it is what we want.
	if ( empty( $confirm_modify ) || 'yes' !== $confirm_modify ) {
		return;
	}

	// Bail on a non authorized user.
	if ( ! current_user_can( 'promote_users' ) ) { // phpcs:ignore -- the nonce check is happening soon.
		wp_die( esc_html__( 'You are not authorized to perform this function.', 'temporary-admin-user' ), __( 'Temporary Admin Users', 'temporary-admin-user' ), [ 'back_link' => true ] );
	}

	// Make sure we have a user ID, which is needed for the nonce check.
	$confirm_userid = filter_input( INPUT_GET, 'tmp-admin-single-user-id', FILTER_SANITIZE_NUMBER_INT ); // phpcs:ignore -- the nonce check is happening soon.

	// Bail without a user ID.
	if ( empty( $confirm_userid ) ) {
		Helpers\redirect_admin_action_result( 'no-user-id' );
	}

	// Make sure we have a nonce.
	$confirm_nonce  = filter_input( INPUT_GET, 'tmp-admin-single-user-nonce', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening after this.

	// Handle the nonce check.
	if ( empty( $confirm_nonce ) || ! wp_verify_nonce( $confirm_nonce, Core\NONCE_PREFIX . 'user_action_' . $confirm_userid ) ) {

		// Let them know they had a failure.
		wp_die( esc_html__( 'There was an error validating the nonce.', 'temporary-admin-user' ), esc_html__( 'Temporary Admin Users', 'temporary-admin-user' ), [ 'back_link' => true ] );
	}

	// Confirm we requested this action.
	$confirm_action = filter_input( INPUT_GET, 'tmp-admin-single-user-request', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening soon.

	// Bail without an action we can use.
	if ( empty( $confirm_action ) || ! in_array( $confirm_action, ['extend', 'promote', 'restrict', 'delete'], true ) ) {
		Helpers\redirect_admin_action_result( 'invalid-user-action' );
	}

	// Set a default range for all the actions that involve a time range.
	$default_range  = apply_filters( Core\HOOK_PREFIX . 'default_user_action_range', 'day', $confirm_userid );

	// Now do a switch and handle the appropriate action.
	switch ( $confirm_action ) {

		// Promote back an existing user.
		case 'extend' :

			// Now filter the extended range on it's own.
			$extend_range   = apply_filters( Core\HOOK_PREFIX . 'default_user_extend_range', $default_range, $confirm_userid );

			// Attempt the restriction.
			$attempt_change = Process\extend_existing_user( $confirm_userid, $extend_range );

			// Handle a possible failure.
			if ( false === $attempt_change ) {
				Helpers\redirect_admin_action_result( 'user-extend-error' );
			}

			// We are good, so redirect with the affermative.
			Helpers\redirect_admin_action_result( '', 'user-extend-success', true );

			// Nothing else to do here.
			break;

		// Promote back an existing user.
		case 'promote' :

			// Now filter the promoted range on it's own.
			$promote_range  = apply_filters( Core\HOOK_PREFIX . 'default_user_promote_range', $default_range, $confirm_userid );

			// Attempt the restriction.
			$attempt_change = Process\promote_existing_user( $confirm_userid, $promote_range );

			// Handle a possible failure.
			if ( false === $attempt_change ) {
				Helpers\redirect_admin_action_result( 'user-promote-error' );
			}

			// We are good, so redirect with the affermative.
			Helpers\redirect_admin_action_result( '', 'user-promote-success', true );

			// Nothing else to do here.
			break;

		// Restrict an existing user.
		case 'restrict' :

			// Attempt the restriction.
			$attempt_change = Process\restrict_existing_user( $confirm_userid );

			// Handle a possible failure.
			if ( false === $attempt_change ) {
				Helpers\redirect_admin_action_result( 'user-restrict-error' );
			}

			// We are good, so redirect with the affermative.
			Helpers\redirect_admin_action_result( '', 'user-restrict-success', true );

			// Nothing else to do here.
			break;

		// Delete an existing user.
		case 'delete' :

			// Attempt the delete.
			$attempt_change = Process\delete_existing_user( $confirm_userid );

			// Handle a possible failure.
			if ( false === $attempt_change ) {
				Helpers\redirect_admin_action_result( 'user-delete-error' );
			}

			// We are good, so redirect with the affermative.
			Helpers\redirect_admin_action_result( '', 'user-delete-success', true );

			// Nothing else to do here.
			break;
	}

	// Handle if we somehow got here.
	Helpers\redirect_admin_action_result( 'unknown-action-error' );
}
