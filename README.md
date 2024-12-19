Temporary Admin User
====================

Create admin users for support that expire.

## Contributors
* [Andrew Norcross](https://github.com/norcross)

## About

Create a temporary WordPress admin user to provide access on support issues, etc. Allows for extending, restricting, and deleting users created by the plugin.

## Features

* Dedicated table for users created with the plugin
* Built-in processes to manage existing users
* Available actions and filters for modifying default values
* A suite of CLI commands

### Frequenty Asked Questions

#### Do user accounts expire automatically?

Yes. There is a built in WP-Cron job that will check the user accounts created by the plugin and compare the stored expiration date. Users that have expired will be automatically set to "Subscriber".

#### I don't want to send the new user email. Can I prevent that?

Absolutely! There's a filter.

~~~php
/**
 * Don't send the new user email when creating a new user.
 *
 * @return boolean
 */
add_filter( 'temporary_admin_user_disable_new_user_email', '__return_true' );
~~~

#### Can I change the default ranges?

Sure can. There is a filter that impacts both promoting and extending a user, and then a more specific one for each action.

~~~php
/**
 * Change the default times for user actions.
 *
 * @param  string  $duration  The current duration range. Default is 'day'.
 * @param  integer $user_id   The individual user ID being updated.
 *
 * @return string             One of the ranges to get a timestamp from.
 */
function prefix_set_default_action_range( $duration, $user_id ) {
	return 'week';
}
add_filter( 'temporary_admin_user_default_user_action_range', 'prefix_set_default_action_range', 10, 2 );
~~~

~~~php
/**
 * Change the default times for extending, but not promoting users.
 *
 * @param  string  $duration  The current duration range. Default is 'day'.
 * @param  integer $user_id   The individual user ID being updated.
 *
 * @return string             One of the ranges to get a timestamp from.
 */
function prefix_set_default_extend_range( $duration, $user_id ) {
	return 'week';
}
add_filter( 'temporary_admin_user_default_user_extend_range', 'prefix_set_default_extend_range', 10, 2 );
~~~

#### Are there other hooks in the plugin?

You betcha. I would suggest reading the source code to get a better idea of what else can be done. And I am open to suggestions.

#### How do the CLI commands work?

Check in the Help Tab above the admin table, and all the commands and their options are explained. You can also type `wp tmp-admin-user` in the connected terminal and all the commands are shown.
