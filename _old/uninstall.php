<?php
/**
 * Delete various options when uninstalling the plugin.
 *
 * @return void
 */
function tmp_admin_user_uninstall() {

	// Include our action so that we may add to this later.
	do_action( 'tmp_admin_user_uninstall_process' );

	// If we added the filter, delete everyone on plugin uninstall.
	if ( false !== $check = apply_filters( 'tmp_admin_user_delete_on_uninstall', false ) ) {
		TempAdminUser_Users::update_all_users( 'delete' );
	}

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_uninstall_hook( TMP_ADMIN_USER_FILE, 'tmp_admin_user_uninstall' );

