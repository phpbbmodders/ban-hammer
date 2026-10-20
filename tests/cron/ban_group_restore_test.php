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
 * Coverage for the ban_group_restore cron task, which moves users back out
 * of the Ban Hammer group once their ban is over. phpBB 4.0 only fires
 * core.session_set_custom_ban for users who are still banned, so without
 * this task a user whose ban ended there stayed in the ban group for good.
 *
 * Runs on both phpBB 3.3 and 4.0: the test's bans go into whichever ban
 * storage the running version uses.
 */
class ban_group_restore_test extends \phpbb_database_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbbmodders/banhammer');
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/ban_group_restore.xml');
	}

	public function test_restores_only_users_no_longer_banned()
	{
		global $phpbb_root_path, $phpEx, $phpbb_dispatcher, $cache, $phpbb_container, $phpbb_log, $user, $auth, $db, $config;

		$db = $this->new_dbal();

		// group_user_del()/group_user_attributes() need these globals, see
		// restriction_expiry_test.
		$user = new \phpbb_mock_user();
		$user->ip = '';
		$user->data['user_id'] = 2;
		$cache = new \phpbb_mock_cache();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		$auth = $this->createMock('\phpbb\auth\auth');
		$auth->expects($this->any())
			->method('acl_clear_prefetch');
		$cache_driver = new \phpbb\cache\driver\dummy();
		$group_helper = $this->createMock('\phpbb\group\helper');
		$notification_manager = $this->createMock('\phpbb\notification\manager');

		// User 3 is still banned by username, user 6 by email domain only and
		// user 7's registration IP is in a banned range. phpbb_get_banned_user_ids()
		// covers the username ban (on 4.0 through the ban manager); the task
		// has to check the wildcard email ban itself on both versions.
		$ban_manager = null;
		if (defined('BANS_TABLE'))
		{
			$ban_manager = $this->createMock('\phpbb\ban\manager');
			$ban_manager->method('get_banned_users')
				->willReturn(array(3 => 0));

			$ban_rows = array(
				array('user', '3', 3),
				array('email', '*@spam.example', 0),
				array('ip', '198.51.100.0/24', 0),
			);
			foreach ($ban_rows as $ban_row)
			{
				$db->sql_query('INSERT INTO ' . BANS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
					'ban_mode'				=> $ban_row[0],
					'ban_item'				=> $ban_row[1],
					'ban_userid'			=> $ban_row[2],
					'ban_start'				=> time(),
					'ban_end'				=> 0,
					'ban_reason'			=> '',
					'ban_reason_display'	=> '',
				)));
			}
		}
		else
		{
			$ban_rows = array(
				array('ban_userid' => 3),
				array('ban_email' => '*@spam.example'),
				array('ban_ip' => '198.51.100.*'),
			);
			foreach ($ban_rows as $ban_row)
			{
				$db->sql_query('INSERT INTO ' . BANLIST_TABLE . ' ' . $db->sql_build_array('INSERT', $ban_row + array(
					'ban_start'		=> time(),
					'ban_end'		=> 0,
					'ban_exclude'	=> 0,
				)));
			}
		}

		$phpbb_container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
		$phpbb_container
			->method('get')
			->willReturnCallback(function ($id) use ($cache_driver, $group_helper, $notification_manager, $ban_manager)
			{
				switch ($id)
				{
					case 'ban.manager':
						return $ban_manager;
					case 'cache.driver':
						return $cache_driver;
					case 'notification_manager':
						return $notification_manager;
					default:
						return $group_helper;
				}
			});
		$phpbb_log = new \phpbb\log\log($db, $user, $auth, $phpbb_dispatcher, $phpbb_root_path, 'adm/', $phpEx, LOG_TABLE);

		$config = new \phpbb\config\config(array('bh_ban_group_last_run' => 0));

		$restore = new \phpbbmodders\banhammer\core\ban_group_restore(
			$db,
			$phpbb_root_path,
			$phpEx,
			'phpbb_banhammer_restrict',
			'phpbb_banhammer_ban_group'
		);
		$task = new \phpbbmodders\banhammer\cron\task\ban_group_restore($config, $restore);

		$this->assertTrue($task->should_run(), 'The task should run when it has never run before');

		$task->run();

		$this->assertFalse($task->should_run(), 'The task should not run again straight away');

		// User 2: no longer banned, membership created by the ban.
		$this->assertFalse($this->is_group_member($db, 2, 8), 'User 2 should no longer be in the ban group');
		$this->assertEquals(7, $this->get_default_group($db, 2), "User 2's default group should be restored");
		$this->assertEquals(0, $this->count_ban_group_rows($db, 2), "User 2's tracking row should be gone");

		// User 3: still banned, left completely alone.
		$this->assertTrue($this->is_group_member($db, 3, 8), 'User 3 should still be in the ban group');
		$this->assertEquals(8, $this->get_default_group($db, 3), "User 3's default group should be unchanged");
		$this->assertEquals(1, $this->count_ban_group_rows($db, 3), "User 3's tracking row should remain");

		// User 4: no longer banned, but was a member before the ban.
		$this->assertTrue($this->is_group_member($db, 4, 8), 'User 4 should keep their pre-existing ban group membership');
		$this->assertEquals(7, $this->get_default_group($db, 4), "User 4's default group should be restored");
		$this->assertEquals(0, $this->count_ban_group_rows($db, 4), "User 4's tracking row should be gone");

		// User 5: no longer banned, but restricted into the same group.
		$this->assertTrue($this->is_group_member($db, 5, 8), 'User 5 should keep the membership their restriction needs');
		$this->assertEquals(7, $this->get_default_group($db, 5), "User 5's default group should be restored");
		$this->assertEquals(0, $this->count_ban_group_rows($db, 5), "User 5's tracking row should be gone");

		// User 6: username ban gone, but still banned by email domain.
		$this->assertTrue($this->is_group_member($db, 6, 8), 'User 6 should still be in the ban group');
		$this->assertEquals(8, $this->get_default_group($db, 6), "User 6's default group should be unchanged");
		$this->assertEquals(1, $this->count_ban_group_rows($db, 6), "User 6's tracking row should remain");

		// User 7: username ban gone. Their registration IP is still in a
		// banned range, but that says nothing about where they visit from
		// now, so it must not keep them in the ban group.
		$this->assertFalse($this->is_group_member($db, 7, 8), 'User 7 should no longer be in the ban group');
		$this->assertEquals(7, $this->get_default_group($db, 7), "User 7's default group should be restored");
		$this->assertEquals(0, $this->count_ban_group_rows($db, 7), "User 7's tracking row should be gone");
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

	protected function count_ban_group_rows($db, $user_id)
	{
		$sql = 'SELECT COUNT(*) as cnt
			FROM phpbb_banhammer_ban_group
			WHERE user_id = ' . (int) $user_id;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		return $count;
	}
}
