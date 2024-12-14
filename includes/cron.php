<?php
/**
 * Our various WP-Cron stuff setup.
 *
 * @package TempAdminUser
 */

// Declare our namespace.
namespace Norcross\TempAdminUser\Cron;

// Set our aliases.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;
use Norcross\TempAdminUser\Queries as Queries;

/**
 * Start our engines.
 */
add_action( Core\EXPIRED_CRON, __NAMESPACE__ . '\check_expired_users' );

/**
 * Take our existing cron job and update or remove the schedule.
 *
 * @param  boolean $clear      Whether to remove the existing one.
 * @param  string  $frequency  The new frequency we wanna use.
 *
 * @return void
 */
function modify_refresh_cron( $clear = true, $frequency = '' ) {

	// Pull in the existing one and remove it.
	if ( ! empty( $clear ) ) {

		// Grab the next scheduled stamp.
		$timestamp  = wp_next_scheduled( Core\EXPIRED_CRON );

		// Remove it from the schedule.
		wp_unschedule_event( $timestamp, Core\EXPIRED_CRON );
	}

	// Now schedule our new one, assuming we passed a new frequency.
	if ( ! empty( $frequency ) ) {

		// And schedule the actual event.
		wp_schedule_event( time(), sanitize_text_field( $frequency ), Core\EXPIRED_CRON );
	}
}

/**
 * Run the check for expired users.
 *
 * @return boolean
 */
function check_expired_users() {

	// Get all the active temporary users we have.
	$get_active_users   = Queries\query_active_temporary_users();

	// Bail if we have no users.
	if ( empty( $get_active_users ) ) {
		return true;
	}

	// Include the "now" time so we can calc it later.
	$set_current_time   = current_datetime()->format('U');

	// Loop the users and do the status check.
	foreach ( $get_active_users as $user_id ) {

		// Get our expired timestamp.
		$setexpires = get_user_meta( $user_id, Core\META_PREFIX . 'expires', true );

		// Handle determining if the timestamp expired.
		if ( empty( $setexpires ) || absint( $set_current_time ) <= absint( $setexpires ) ) {
			continue;
		}

		// Process the restriction.
		Process\restrict_existing_user( $user_id );
	}

	// And be done.
	return true;
}
