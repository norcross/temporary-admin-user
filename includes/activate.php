<?php
/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function tmp_admin_user_activate() {

	// Make sure this event hasn't been scheduled.
	if ( ! wp_next_scheduled( 'tmp_admin_user_check_expired' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'tmp_admin_user_check_expired' );
	}

	// Include our action so that we may add to this later.
	do_action( 'tmp_admin_user_activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( TMP_ADMIN_USER_FILE, 'tmp_admin_user_activate' );
