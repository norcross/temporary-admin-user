<?php
/**
 * Delete various options when deactivating the plugin.
 *
 * @return void
 */
function tmp_admin_user_deactivate() {

	// Pull in our scheduled cron and unschedule it.
	$timestamp  = wp_next_scheduled( 'tmp_admin_user_check_expired' );
	wp_unschedule_event( $timestamp, 'tmp_admin_user_check_expired' );

	// If we added the filter, restrict everyone on plugin uninstall.
	if ( false !== $check = apply_filters( 'tmp_admin_user_restrict_on_deactivate', true ) ) {
		TempAdminUser_Users::update_all_users( 'restrict' );
	}

	// Include our action so that we may add to this later.
	do_action( 'tmp_admin_user_deactivate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( TMP_ADMIN_USER_FILE, 'tmp_admin_user_deactivate' );

