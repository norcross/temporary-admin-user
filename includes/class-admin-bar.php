<?php
/**
 * Our new admin bar item.
 *
 * @package TempAdminUser
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_bar_menu', 'tmp_admin_user_admin_bar', 9999 );
/**
 * Add a menu item under the "new content" for new users.
 *
 * @param  WP_Admin_Bar $wp_admin_bar  The global WP_Admin_Bar object.
 *
 * @return void.
 */
function tmp_admin_user_admin_bar( WP_Admin_Bar $wp_admin_bar ) {

	// Bail if current user doesnt have cap.
	if ( ! current_user_can( apply_filters( 'tmp_admin_user_menu_cap', 'manage_options' ) ) ) {
		return;
	}

	// Add the link to the menu page.
	$wp_admin_bar->add_node(
		array(
			'id'        => 'tmp-admin-user-new',
			'title'     => __( 'Temporary Admin', 'temporary-admin-user' ),
			'href'      => TempAdminUser_Helper::get_menu_link(),
			'parent'    => 'new-content',
			'meta'      => array(
				'title' => __( 'Temporary Admin', 'temporary-admin-user' ),
			),
		)
	);
}
