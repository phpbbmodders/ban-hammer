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
 * Bans a user from their profile and checks what was stored, on both
 * phpBB 3.3 and 4.0. Regression test for phpBB 4.0 support: 4.0's
 * user_ban() takes one argument fewer than 3.3's, so calling it with 3.3's
 * arguments stored the wrong value as the ban reason.
 *
 * @group functional
 */
class ban_user_test extends \phpbb_functional_test_case
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

		// See confirm_bypass_test: m_ban isn't granted to the test admin.
		$auth_admin = new \auth_admin();
		$auth_admin->acl_set('user', 0, $admin_user_id, array('m_ban' => ACL_YES), 0, true);
	}

	public function test_ban_from_profile_stores_reasons()
	{
		$victim_id = $this->create_user('bhbanvictim');

		// Test users share one email address; give this one its own, so its
		// email ban can't lock out users other tests log in as.
		$db = $this->get_db();
		$db->sql_query('UPDATE ' . USERS_TABLE . "
			SET user_email = 'bhbanvictim@example.com'
			WHERE user_id = " . (int) $victim_id);

		$this->login();

		$crawler = self::request(
			'POST',
			'memberlist.php?mode=viewprofile&u=' . $victim_id . '&bh=1&sid=' . $this->sid,
			array(
				'ban_time'			=> 30,
				'ban_email'			=> 1,
				'bh_reason'			=> 'staff reason probe',
				'bh_reason_user'	=> 'user reason probe',
			)
		);

		$form = $crawler->selectButton('confirm')->form();
		self::submit($form);

		// phpBB 4.0 renamed the banlist table to bans and changed its columns.
		if (defined('BANS_TABLE'))
		{
			$sql = 'SELECT ban_mode, ban_reason, ban_reason_display AS reason_user, ban_end
				FROM ' . BANS_TABLE . "
				WHERE (ban_mode = 'user' AND ban_userid = " . (int) $victim_id . ")
					OR (ban_mode = 'email' AND ban_item = 'bhbanvictim@example.com')";
		}
		else
		{
			$sql = "SELECT CASE WHEN ban_userid <> 0 THEN 'user' ELSE 'email' END AS ban_mode,
					ban_reason, ban_give_reason AS reason_user, ban_end
				FROM " . BANLIST_TABLE . '
				WHERE ban_userid = ' . (int) $victim_id . "
					OR ban_email = 'bhbanvictim@example.com'";
		}
		$result = $db->sql_query($sql);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$modes = array_column($rows, 'ban_mode');
		sort($modes);
		$this->assertSame(array('email', 'user'), $modes, 'The username and the email address should both be banned');

		foreach ($rows as $row)
		{
			$this->assertSame('staff reason probe', $row['ban_reason'], 'The staff reason should be stored on the ' . $row['ban_mode'] . ' ban');
			$this->assertSame('user reason probe', $row['reason_user'], 'The user reason should be stored on the ' . $row['ban_mode'] . ' ban');
			$this->assertGreaterThan(time() + 25 * 60, (int) $row['ban_end'], 'The ' . $row['ban_mode'] . ' ban should last 30 minutes');
		}
	}
}
