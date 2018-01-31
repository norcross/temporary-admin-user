<?php
/**
 * Delete various options when deactivating the plugin.
 *
 * @return void
 */
function tmp_admin_user_deactivate() {

	// Include our action so that we may add to this later.
	do_action( 'tmp_admin_user_deactivate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( TMP_ADMIN_USER_FILE, 'tmp_admin_user_deactivate' );

