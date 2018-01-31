<?php
/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function tmp_admin_user_activate() {

	// Include our action so that we may add to this later.
	do_action( 'tmp_admin_user_activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( TMP_ADMIN_USER_FILE, 'tmp_admin_user_activate' );
