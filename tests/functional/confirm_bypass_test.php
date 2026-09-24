<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\tests\functional;

/**
 * Regression test for the cancel=1 confirmation bypass: a bh=1 request with
 * no confirm_key, but cancel=1, used to fall straight through to the ban
 * because confirm_box(false, ...) returns false (instead of rendering a
 * page and exiting) when the request is a cancellation, and the listener
 * didn't check for that before proceeding.
 *
 * @group functional
 */
class confirm_bypass_test extends \phpbb_functional_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbbmodders/banhammer');
	}

	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx, $db, $cache, $phpbb_dispatcher;

		$db = $this->get_db();
		$cache = new \phpbb\cache\driver\dummy();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		if (!class_exists('auth_admin'))
		{
			include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
		}

		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . "
			WHERE username_clean = 'admin'";
		$result = $db->sql_query($sql);
		$admin_user_id = (int) $db->sql_fetchfield('user_id');
		$db->sql_freeresult($result);

		// m_ban isn't part of any default role granted to the test install's
		// admin account, so grant it directly rather than assume it's there.
		// auth_admin's constructor and acl_set(..., true)'s
		// acl_clear_prefetch() both read $db/$cache/$phpbb_dispatcher as
		// globals, not through any constructor argument.
		$auth_admin = new \auth_admin();
		$auth_admin->acl_set('user', 0, $admin_user_id, array('m_ban' => ACL_YES), 0, true);
	}

	public function test_cancel_does_not_bypass_confirmation()
	{
		$victim_id = $this->create_user('bhconfirmvictim');
		$this->login();

		// The exploit: a bh=1 request with no confirm_key, but cancel=1.
		self::request(
			'POST',
			'memberlist.php?mode=viewprofile&u=' . $victim_id . '&bh=1&sid=' . $this->sid,
			array('cancel' => '1')
		);

		$db = $this->get_db();
		$sql = 'SELECT COUNT(*) as cnt
			FROM ' . BANLIST_TABLE . '
			WHERE ban_userid = ' . $victim_id;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		$this->assertSame(0, $count, 'A cancelled confirmation must not execute the ban');
	}
}
