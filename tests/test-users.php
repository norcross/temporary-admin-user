<?php
/**
 * Tests for the TempAdminUser_Users class.
 *
 * @package TempAdminUser
 */

/**
 * Test the behavior of temporary users.
 */
class UsersTest extends WP_UnitTestCase {

    public function test_create_username() {
        $this->assertEquals( 'test', TempAdminUser_Users::create_username( 'test@example.com' ) );
    }

    public function test_create_username_handles_duplicates() {
        $this->factory()->user->create( [
            'user_login' => 'test',
        ] );

        $username = TempAdminUser_Users::create_username( 'test@example.com' );

        $this->assertNotEquals( 'test', $username, 'Cannot use "test", as that login is already taken.' );
        $this->assertRegExp( '/test[A-Z0-9+]/', $username, 'Expected "test" with a random string appended.' );
    }

	public function test_create_new_user() {
        $user_id = $this->generate_test_user( 'test@example.com', 'hour' );
        $user    = get_userdata( $user_id );
        $meta    = get_user_meta( $user_id );

        $this->assertContains(
            'administrator',
            $user->roles,
            'The user should have an administrator role.'
        );
        $this->assertTrue(
            (bool) $meta['_tmp_admin_user_flag'][0],
            'The temporary admin user should be flagged as such.'
        );
        $this->assertEquals(
            $meta['_tmp_admin_user_created'][0] + HOUR_IN_SECONDS,
            (int) $meta['_tmp_admin_user_expires'][0],
            'The temporary admin should expire one hour after its creation.'
        );
    }

    public function test_get_temp_users() {
        $this->generate_test_user( 'test1@example.com' );
        $this->generate_test_user( 'test2@example.com' );
        $this->generate_test_user( 'test3@example.com' );

        $users = TempAdminUser_Users::get_temp_users();

        $this->assertCount( 3, $users, 'Expected to see three temporary users.' );
    }

    public function test_get_temp_users_only_counts_temporary_admins() {
        $this->factory()->user->create( [
            'role' => 'administrator',
        ] );

        $this->assertFalse(
            TempAdminUser_Users::get_temp_users(),
            'Only temporary administrators should be counted.'
        );
    }

    /**
     * Generate a new temporary admin user.
     *
     * @param string $email    Optional. The email address for the user. Default is test@example.com.
     * @param string $duration Optional. How long this user should exist. Default is one hour.
     *
     * @return int The ID of the newly-created user.
     */
    protected function generate_test_user( $email = 'test@example.com', $duration = 'hour' ) {
        wp_set_current_user( $this->factory()->user->create( [
            'role' => 'administrator',
        ] ) );

        $method = new ReflectionMethod( 'TempAdminUser_Users::create_new_user' );
        $method->setAccessible( true );

        return $method->invoke( new TempAdminUser_Users, $email, $duration );
    }
}
