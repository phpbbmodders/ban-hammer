<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\tests\cron;

/**
 * Regression coverage for two bugs in restriction_expiry::run():
 *
 * - it used to read the *current* bh_restrict_group_id ACP setting for
 *   every expiring row instead of the group actually applied when each
 *   restriction was created, stranding a user if that setting had since
 *   changed;
 * - it used to call group_user_add(..., true) to restore the original
 *   default group, which silently does nothing because the user was
 *   never removed from that group while restricted (group_user_add()
 *   sees them as already a member and returns before setting the
 *   default).
 *
 * The fixture seeds two independent, differently-grouped restrictions to
 * prove each row is handled using its own recorded group, not a shared
 * config value.
 */
class restriction_expiry_test extends \phpbb_database_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbbmodders/banhammer');
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/restriction_expiry.xml');
	}

	public function test_expiry_restores_expired_restriction_only()
	{
		global $phpbb_root_path, $phpEx, $phpbb_dispatcher, $cache, $phpbb_container, $phpbb_log, $user, $auth;

		$db = $this->new_dbal();

		// group_user_del()/group_user_attributes() (includes/functions_user.php)
		// need these globals, same as phpBB core's own test for
		// group_user_attributes() (tests/functions_user/group_user_attributes_test.php).
		$user = new \phpbb_mock_user();
		$user->ip = '';
		$user->data['user_id'] = 2;
		$cache = new \phpbb_mock_cache();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		$auth = $this->createMock('\phpbb\auth\auth');
		$auth->expects($this->any())
			->method('acl_clear_prefetch');
		$cache_driver = new \phpbb\cache\driver\dummy();
		$phpbb_container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
		$phpbb_container
			->expects($this->any())
			->method('get')
			->with('cache.driver')
			->willReturn($cache_driver);
		$phpbb_log = new \phpbb\log\log($db, $user, $auth, $phpbb_dispatcher, $phpbb_root_path, 'adm/', $phpEx, LOG_TABLE);

		// Deliberately not the group either row actually used (8 or 9): a
		// stale/irrelevant config value must not affect which group gets
		// cleared for each row.
		$config = new \phpbb\config\config(array('bh_restrict_last_run' => 0, 'bh_restrict_group_id' => 999));

		$task = new \phpbbmodders\banhammer\cron\task\restriction_expiry(
			$config,
			$db,
			'phpbb_banhammer_restrict',
			$phpbb_root_path,
			$phpEx
		);

		$task->run();

		// User 2's expired restriction: removed from the restrict group (8),
		// default restored to their original group (7).
		$this->assertFalse($this->is_group_member($db, 2, 8), 'User 2 should no longer be in the restrict group');
		$this->assertTrue($this->is_group_member($db, 2, 7), 'User 2 should still be in their original group');
		$this->assertEquals(7, $this->get_default_group($db, 2), "User 2's default group should be restored to their original group");
		$this->assertEquals(0, $this->count_restrict_rows($db, 2), "User 2's tracking row should be gone");

		// User 3's restriction has not expired: left completely alone.
		$this->assertTrue($this->is_group_member($db, 3, 9), 'User 3 should still be in their restrict group');
		$this->assertEquals(9, $this->get_default_group($db, 3), "User 3's default group should be unchanged");
		$this->assertEquals(1, $this->count_restrict_rows($db, 3), "User 3's tracking row should remain");
	}

	protected function is_group_member($db, $user_id, $group_id)
	{
		$sql = 'SELECT COUNT(*) as cnt
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . (int) $user_id . '
				AND group_id = ' . (int) $group_id;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		return $count > 0;
	}

	protected function get_default_group($db, $user_id)
	{
		$sql = 'SELECT group_id
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$group_id = (int) $db->sql_fetchfield('group_id');
		$db->sql_freeresult($result);

		return $group_id;
	}

	protected function count_restrict_rows($db, $user_id)
	{
		$sql = 'SELECT COUNT(*) as cnt
			FROM phpbb_banhammer_restrict
			WHERE user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		return $count;
	}
}
