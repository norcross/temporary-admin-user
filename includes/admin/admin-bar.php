<?php
/**
 * Our new admin bar item.
 *
 * @package TempAdminUser
 */

// Call our namepsace.
namespace Norcross\TempAdminUser\Admin\AdminBar;

// Set our alias items.
use Norcross\TempAdminUser as Core;
use Norcross\TempAdminUser\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_admin_bar_link', 999 );

/**
 * Add a menu item under the "new content" for new users.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return void.
 */
function add_admin_bar_link( \WP_Admin_Bar $wp_admin_bar ) {

	// Bail if current user doesnt have cap.
	if ( ! current_user_can( 'promote_users' ) ) {
		return;
	}

	// Add the link to the menu page.
	$wp_admin_bar->add_node(
		[
			'id'        => 'tmp-admin-user-new',
			'title'     => __( 'Temporary User', 'temporary-admin-user' ),
			'href'      => Helpers\get_admin_menu_link(),
			'parent'    => 'new-content',
			'meta'      => [
				'title' => __( 'Temporary User', 'temporary-admin-user' ),
			],
		]
	);
}
