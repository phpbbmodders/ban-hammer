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
 * The automatic ban after a number of warnings, on both phpBB 3.3 and 4.0:
 * with the limit at 2, the first MCP warning leaves the user alone and the
 * second bans them, with a reason saying it was automatic.
 *
 * @group functional
 */
class autoban_test extends \phpbb_functional_test_case
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

		// See confirm_bypass_test: grant the moderator permissions directly.
		$auth_admin = new \auth_admin();
		$auth_admin->acl_set('user', 0, $admin_user_id, array('m_ban' => ACL_YES, 'm_warn' => ACL_YES), 0, true);
	}

	public function test_second_warning_bans()
	{
		$victim_id = $this->create_user('bhautobanvictim');

		$db = $this->get_db();
		$db->sql_query('UPDATE ' . CONFIG_TABLE . "
			SET config_value = '2'
			WHERE config_name = 'bh_autoban_warnings'");
		$this->purge_cache();

		$this->login();

		$this->warn($victim_id);
		$this->assertSame(array(), $this->user_ban_reasons($victim_id), 'One warning is below the limit: no ban');

		$this->warn($victim_id);
		$reasons = $this->user_ban_reasons($victim_id);
		$this->assertCount(1, $reasons, 'The second warning reaches the limit: banned');
		$this->assertStringContainsString('2', $reasons[0], 'The reason names the number of warnings');
	}

	/**
	 * Warn a user through the MCP, as a moderator does.
	 *
	 * @param int $user_id
	 * @return void
	 */
	protected function warn($user_id)
	{
		$crawler = self::request('GET', 'mcp.php?i=warn&mode=warn_user&u=' . (int) $user_id . '&sid=' . $this->sid);
		$form = $crawler->filter('input[name="action[add_warning]"]')->form(array(
			'warning'	=> 'autoban probe warning',
		));
		self::submit($form);
	}

	/**
	 * The reasons of the active username bans on a user, from whichever
	 * table the running phpBB version keeps bans in.
	 *
	 * @param int $user_id
	 * @return array
	 */
	protected function user_ban_reasons($user_id)
	{
		$db = $this->get_db();

		if (defined('BANS_TABLE'))
		{
			$sql = 'SELECT ban_reason
				FROM ' . BANS_TABLE . "
				WHERE ban_mode = 'user'
					AND ban_userid = " . (int) $user_id;
		}
		else
		{
			$sql = 'SELECT ban_reason
				FROM ' . BANLIST_TABLE . '
				WHERE ban_userid = ' . (int) $user_id;
		}
		$result = $db->sql_query($sql);
		$reasons = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$reasons[] = $row['ban_reason'];
		}
		$db->sql_freeresult($result);

		return $reasons;
	}
}
